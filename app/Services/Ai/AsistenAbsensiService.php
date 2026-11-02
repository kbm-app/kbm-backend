<?php

namespace App\Services\Ai;

use App\Events\PertemuanSelesai;
use App\Models\AbsensiPengajar;
use App\Models\Kelas;
use App\Models\Murid;
use App\Models\Pengajar;
use App\Models\Pertemuan;
use App\Models\Program;
use App\Services\AbsensiService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Asisten Input AI untuk super admin: membaca laporan absensi dari pengajar (teks bebas, mis.
 * pesan WA, dan/atau foto lembar absen) menjadi draf sesi + absensi. AI hanya menyusun draf —
 * penyimpanan selalu lewat konfirmasi admin dan memakai AbsensiService seperti input manual.
 */
class AsistenAbsensiService
{
    private const STATUS_MURID    = ['hadir', 'izin', 'sakit', 'alpha', 'terlambat'];
    private const STATUS_PENGAJAR = ['hadir', 'berhalangan', 'digantikan'];

    private const SYSTEM_PROMPT = <<<'PROMPT'
Kamu membantu super admin sebuah KBM (Kegiatan Belajar Mengajar) mengaji mencatat laporan absensi yang dikirim pengajar yang belum bisa memakai aplikasi. Laporan bisa berupa teks bebas (sering pesan WhatsApp, bahasa santai, singkatan, emoji) dan/atau foto lembar absen tulisan tangan.

Tugasmu: ubah laporan untuk SATU sesi pertemuan di kelas yang disebutkan menjadi data terstruktur sesuai skema.

Aturan:
- Cocokkan setiap nama murid di laporan ke daftar murid kelas yang diberikan dan pakai id-nya. Nama di laporan sering berupa panggilan, sebagian nama, atau salah ketik (mis. "Aisy" untuk "Aisyah Putri"). Pilih yang paling masuk akal; bila ada dua kandidat yang sama-sama mungkin atau tidak ada yang cocok, JANGAN menebak — masukkan nama aslinya ke nama_tidak_dikenali.
- Status murid: hadir, terlambat, izin, sakit, alpha. "Tidak hadir tanpa keterangan", "bolos", "absen" = alpha. Alasan (mis. "sakit demam", "acara keluarga") masuk ke keterangan.
- status_murid_lain menyatakan status murid kelas yang TIDAK disebut di laporan: "hadir" bila laporan hanya menyebut yang tidak hadir (mis. "yang tidak masuk: ..."), "alpha" bila laporan berupa daftar yang hadir, "tidak_diketahui" bila tidak jelas.
- Pengajar: cocokkan ke daftar pengajar kelas. Pengajar yang mengajar = hadir. Bila pengajar kelas berhalangan dan ada yang menggantikan, beri status digantikan dan isi pengganti_id (pengganti boleh dari daftar pengajar lain). Jangan masukkan pengajar yang tidak disebut atau tersirat di laporan.
- Tanggal dalam format YYYY-MM-DD. Hitung tanggal relatif ("kemarin", "Ahad kemarin") dari tanggal hari ini yang diberikan. Ahad = Minggu. Jam dalam format HH:MM 24 jam ("ba'da maghrib" boleh diperkirakan hanya bila laporan jelas; selain itu null).
- program_id hanya diisi bila laporan menyebut atau jelas menyiratkan program dari daftar; selain itu null.
- materi: ringkas materi yang disampaikan bila disebut.
- Isi catatan dengan hal yang perlu diperiksa admin (bagian foto yang tidak terbaca, laporan berisi lebih dari satu sesi — ambil yang pertama, data yang saling bertentangan, dsb.). Tulis catatan dalam bahasa Indonesia, singkat.
- Jangan mengarang data yang tidak ada di laporan. Lebih baik null daripada salah.
PROMPT;

    public function __construct(
        private AbsensiService $absensi,
        private AiManager $aiManager,
    ) {}

    /**
     * @param  UploadedFile[]  $gambar
     * @return array{hasil: array, referensi: array}
     */
    public function ekstrak(
        Kelas $kelas,
        ?string $teks,
        array $gambar,
        ?string $provider = null,
        ?string $model = null,
    ): array {
        $referensi = $this->referensi($kelas);

        $konteks = [
            'tanggal_hari_ini' => now()->toDateString() . ' (' . now()->locale('id')->translatedFormat('l') . ')',
            'kelas'            => $kelas->nama,
            'murid_kelas'      => array_map(fn ($m) => ['id' => $m['id'], 'nama' => $m['nama']], $referensi['murid']),
            'pengajar_kelas'   => $referensi['pengajar_kelas'],
            'pengajar_lain'    => $referensi['pengajar_lain'],
            'program'          => $referensi['program'],
        ];

        $driver = $this->aiManager->driver($provider);
        $mentah = $driver->ekstrak($konteks, $teks, $gambar, $this->skema(), self::SYSTEM_PROMPT, $model);

        return [
            'hasil'     => $this->rapikan($mentah, $referensi, $kelas),
            'referensi' => $referensi,
        ];
    }

    /**
     * Simpan draf yang sudah dikonfirmasi admin: buka sesi, isi absensi murid & pengajar, lalu
     * (opsional) tutup sesi. Semua atau tidak sama sekali.
     */
    public function simpan(array $data, int $pencatatId): Pertemuan
    {
        $tutup = (bool) ($data['tutup_sesi'] ?? false);

        $pertemuan = DB::transaction(function () use ($data, $pencatatId, $tutup) {
            $pertemuan = $this->absensi->bukaSesi([
                'kelas_id'     => $data['kelas_id'],
                'program_id'   => $data['program_id'],
                'jadwal_id'    => $data['jadwal_id'] ?? null,
                'tanggal'      => $data['tanggal'],
                'jam_mulai'    => $data['jam_mulai'],
                'materi'       => $data['materi'] ?? null,
                'pengajar_ids' => array_column($data['pengajar'], 'pengajar_id'),
            ], $pencatatId);
            // Muat ulang agar kolom default database (status 'berlangsung') ikut terbaca
            $pertemuan->refresh();

            // Hanya peserta sesi (murid aktif yang sudah bergabung pada tanggal sesi)
            $peserta  = $this->absensi->pesertaSesi($pertemuan);
            $bukanPeserta = collect($data['absensi'])->pluck('murid_id')->diff($peserta);
            if ($bukanPeserta->isNotEmpty()) {
                $nama = Murid::whereIn('id', $bukanPeserta)->pluck('nama')->implode(', ');
                throw ValidationException::withMessages([
                    'absensi' => "Murid berikut bukan peserta kelas ini pada tanggal sesi: {$nama}.",
                ]);
            }

            $this->absensi->inputAbsensiBulk($pertemuan, $data['absensi'], $pencatatId);

            foreach ($data['pengajar'] as $p) {
                AbsensiPengajar::where('pertemuan_id', $pertemuan->id)
                    ->where('pengajar_id', $p['pengajar_id'])
                    ->update([
                        'status'       => $p['status'],
                        'pengganti_id' => $p['status'] === 'digantikan' ? $p['pengganti_id'] : null,
                        'keterangan'   => $p['keterangan'] ?? null,
                    ]);
            }

            if ($tutup) {
                $pertemuan = $this->absensi->tutupSesi($pertemuan, $data['jam_selesai'], kirimNotifikasi: false);
            }

            return $pertemuan;
        });

        // Notifikasi WA dikirim setelah commit agar tidak terkirim untuk sesi yang gagal disimpan
        if ($tutup && ($data['kirim_notifikasi'] ?? false)) {
            event(new PertemuanSelesai($pertemuan));
        }

        return $pertemuan->fresh(['kelas', 'program', 'pengajar.user', 'absensiMurid.murid', 'absensiPengajar']);
    }

    /** Data kelas yang dipakai AI untuk mencocokkan nama, juga dipakai layar konfirmasi. */
    private function referensi(Kelas $kelas): array
    {
        $murid = Murid::whereIn('id', $kelas->muridAktif()->pluck('murid_id'))
            ->orderBy('nama')
            ->get(['id', 'nama', 'tanggal_masuk'])
            ->map(fn (Murid $m) => [
                'id'            => $m->id,
                'nama'          => $m->nama,
                'tanggal_masuk' => $m->tanggal_masuk?->toDateString(),
            ])->all();

        $idPengajarKelas = $kelas->pengajarIds();
        $namaPengajar    = fn (Pengajar $p) => ['id' => $p->id, 'nama' => $p->user?->name ?? "Pengajar #{$p->id}"];

        return [
            'murid'          => $murid,
            'pengajar_kelas' => Pengajar::with('user')->whereIn('id', $idPengajarKelas)->get()->map($namaPengajar)->values()->all(),
            'pengajar_lain'  => Pengajar::with('user')->where('is_aktif', true)->whereNotIn('id', $idPengajarKelas)->get()->map($namaPengajar)->values()->all(),
            'program'        => Program::where('is_aktif', true)->orderBy('nama')->get(['id', 'nama'])->toArray(),
        ];
    }

    private function skema(): array
    {
        $nullable = fn (array $tipe) => ['anyOf' => [$tipe, ['type' => 'null']]];

        return [
            'type'       => 'object',
            'properties' => [
                'tanggal'            => $nullable(['type' => 'string', 'description' => 'YYYY-MM-DD']),
                'jam_mulai'          => $nullable(['type' => 'string', 'description' => 'HH:MM']),
                'jam_selesai'        => $nullable(['type' => 'string', 'description' => 'HH:MM']),
                'program_id'         => $nullable(['type' => 'integer']),
                'materi'             => $nullable(['type' => 'string']),
                'status_murid_lain'  => ['type' => 'string', 'enum' => ['hadir', 'alpha', 'tidak_diketahui']],
                'murid'              => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'murid_id'        => ['type' => 'integer'],
                            'nama_di_laporan' => ['type' => 'string'],
                            'status'          => ['type' => 'string', 'enum' => self::STATUS_MURID],
                            'keterangan'      => $nullable(['type' => 'string']),
                        ],
                        'required'             => ['murid_id', 'nama_di_laporan', 'status', 'keterangan'],
                        'additionalProperties' => false,
                    ],
                ],
                'nama_tidak_dikenali' => ['type' => 'array', 'items' => ['type' => 'string']],
                'pengajar'            => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'pengajar_id'  => ['type' => 'integer'],
                            'status'       => ['type' => 'string', 'enum' => self::STATUS_PENGAJAR],
                            'pengganti_id' => $nullable(['type' => 'integer']),
                            'keterangan'   => $nullable(['type' => 'string']),
                        ],
                        'required'             => ['pengajar_id', 'status', 'pengganti_id', 'keterangan'],
                        'additionalProperties' => false,
                    ],
                ],
                'catatan' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => [
                'tanggal', 'jam_mulai', 'jam_selesai', 'program_id', 'materi', 'status_murid_lain',
                'murid', 'nama_tidak_dikenali', 'pengajar', 'catatan',
            ],
            'additionalProperties' => false,
        ];
    }

    /**
     * Saring keluaran AI terhadap data sebenarnya: id yang tidak ada di referensi dibuang (dan
     * dicatat), format tanggal/jam dicek. Admin tetap memeriksa hasilnya di layar konfirmasi.
     */
    private function rapikan(array $mentah, array $referensi, Kelas $kelas): array
    {
        $catatan        = array_values(array_filter((array) ($mentah['catatan'] ?? []), 'is_string'));
        $tidakDikenali  = array_values(array_filter((array) ($mentah['nama_tidak_dikenali'] ?? []), 'is_string'));
        $idMurid        = array_column($referensi['murid'], 'id');
        $idPengajarKelas = array_column($referensi['pengajar_kelas'], 'id');
        $idSemuaPengajar = array_merge($idPengajarKelas, array_column($referensi['pengajar_lain'], 'id'));
        $idProgram      = array_column($referensi['program'], 'id');

        $murid = [];
        foreach ((array) ($mentah['murid'] ?? []) as $m) {
            $id = $m['murid_id'] ?? null;
            if (! in_array($id, $idMurid, true) || ! in_array($m['status'] ?? null, self::STATUS_MURID, true)) {
                $tidakDikenali[] = $m['nama_di_laporan'] ?? "id {$id}";
                continue;
            }
            if (isset($murid[$id])) {
                $catatan[] = "Murid \"{$m['nama_di_laporan']}\" disebut lebih dari sekali; dipakai yang pertama.";
                continue;
            }
            $murid[$id] = [
                'murid_id'        => $id,
                'nama_di_laporan' => (string) ($m['nama_di_laporan'] ?? ''),
                'status'          => $m['status'],
                'keterangan'      => $m['keterangan'] ?? null,
            ];
        }

        $pengajar = [];
        foreach ((array) ($mentah['pengajar'] ?? []) as $p) {
            $id = $p['pengajar_id'] ?? null;
            if (! in_array($id, $idPengajarKelas, true) || isset($pengajar[$id])
                || ! in_array($p['status'] ?? null, self::STATUS_PENGAJAR, true)) {
                continue;
            }
            $pengganti = $p['pengganti_id'] ?? null;
            $pengajar[$id] = [
                'pengajar_id'  => $id,
                'status'       => $p['status'],
                'pengganti_id' => in_array($pengganti, $idSemuaPengajar, true) && $pengganti !== $id ? $pengganti : null,
                'keterangan'   => $p['keterangan'] ?? null,
            ];
        }

        $tanggal = $this->tanggalValid($mentah['tanggal'] ?? null);
        if (($mentah['tanggal'] ?? null) && ! $tanggal) {
            $catatan[] = 'Tanggal dari laporan tidak bisa dibaca, isi manual.';
        }

        if ($tanggal) {
            $sudahAda = Pertemuan::where('kelas_id', $kelas->id)->whereDate('tanggal', $tanggal)->count();
            if ($sudahAda > 0) {
                $catatan[] = "Kelas ini sudah punya {$sudahAda} sesi pada tanggal tersebut. Pastikan laporan ini bukan sesi yang sama.";
            }
        }

        $programId = $mentah['program_id'] ?? null;

        return [
            'tanggal'             => $tanggal,
            'jam_mulai'           => $this->jamValid($mentah['jam_mulai'] ?? null),
            'jam_selesai'         => $this->jamValid($mentah['jam_selesai'] ?? null),
            'program_id'          => in_array($programId, $idProgram, true) ? $programId : null,
            'materi'              => is_string($mentah['materi'] ?? null) ? $mentah['materi'] : null,
            'status_murid_lain'   => in_array($mentah['status_murid_lain'] ?? null, ['hadir', 'alpha'], true)
                ? $mentah['status_murid_lain'] : 'tidak_diketahui',
            'murid'               => array_values($murid),
            'nama_tidak_dikenali' => array_values(array_unique($tidakDikenali)),
            'pengajar'            => array_values($pengajar),
            'catatan'             => $catatan,
        ];
    }

    private function tanggalValid(mixed $nilai): ?string
    {
        if (! is_string($nilai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }
        try {
            $tanggal = Carbon::createFromFormat('!Y-m-d', $nilai);
        } catch (\Throwable) {
            return null;
        }

        return $tanggal->format('Y-m-d') === $nilai ? $nilai : null;
    }

    private function jamValid(mixed $nilai): ?string
    {
        return is_string($nilai) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $nilai) ? $nilai : null;
    }
}
