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
 * Asisten Input AI untuk super admin:
 * Membaca laporan absensi pengajar (pesan WA 1 hari atau multi-hari, dan/atau foto lembar
 * absensi fisik harian maupun rekap matriks bulanan) menjadi draf sesi pertemuan + absensi.
 *
 * Mendukung ekstraksi multi-hari/multi-sesi sekaligus, mendeteksi tanggal yang sudah ada di database
 * agar tidak diduplikasi (di-skip), dan menyimpan secara transaksional ke database.
 */
class AsistenAbsensiService
{
    private const STATUS_MURID    = ['hadir', 'izin', 'sakit', 'alpha', 'terlambat'];
    private const STATUS_PENGAJAR = ['hadir', 'berhalangan', 'digantikan'];

    private const SYSTEM_PROMPT = <<<'PROMPT'
Kamu membantu super admin sebuah KBM (Kegiatan Belajar Mengajar) mencatat laporan absensi yang dikirim pengajar.
Laporan yang diberikan bisa berupa:
1. Pesan teks bebas (misalnya pesan WhatsApp harian, atau rangkuman beberapa hari berturut-turut).
2. Foto lembar absensi:
   - Bisa berupa catatan harian.
   - ATAU berupa LEMBAR REKAP MATRIKS BULANAN (seperti formulir "DAFTAR HADIR" dengan kolom tanggal 1 sampai 31 dan baris nama-nama murid).

Tugasmu: ekstrak SEMUA sesi pertemuan yang ada di dalam laporan (bisa 1 hari, beberapa hari, atau rekap sebulan penuh) menjadi data terstruktur sesuai skema JSON.

Aturan Penting:
1. Pengecekan Tanggal yang Sudah Tercatat (Skip):
   - Di data referensi terdapat 'tanggal_sudah_tercatat' (daftar tanggal YYYY-MM-DD yang SUDAH TERSIMPAN di database untuk kelas ini).
   - Bila laporan menyebut tanggal yang SUDAH ADA di daftar 'tanggal_sudah_tercatat', JANGAN BUAT objek sesi untuk tanggal tersebut (abaikan / skip). Tuliskan di 'catatan' bahwa sesi tanggal tersebut dilewati karena sudah ada di database.
   - Kolom atau tanggal yang bertuliskan "LIBUR" (atau libur KBM) BUKAN sesi pertemuan, JANGAN dibuatkan sesi.

2. Ekstraksi Lembar Matriks Bulanan:
   - Baca nama Bulan dan Tahun di header lembar (contoh: "Bulan: Oktober"). Jika tahun tidak tertulis, asumsikan tahun dari 'tanggal_hari_ini'. Format tanggal setiap sesi: YYYY-MM-DD (contoh: 2026-10-01, 2026-10-02, dst.).
   - Setiap kolom angka tanggal yang berisi kegiatan/kehadiran murid menjadi SATU objek sesi tersendiri di array 'sesi'.
   - Pengajar yang tertulis di header lembar (contoh: "Pengajar: Aldo Jamal Farhan") masukkan ke 'pengajar_umum' dan cocokkan dengan daftar pengajar kelas. Pengajar ini akan otomatis bertugas di seluruh sesi lembar tersebut.
   - Tanda presensi di sel tabel per murid:
     * Tanda titik (.), centang (v), atau paraf = 'hadir'.
     * Huruf 'i' atau kata 'izin' = 'izin'.
     * Huruf 's' atau kata 'sakit' = 'sakit'.
     * Huruf 'a', tanda minus (-), strip, atau kosong tanpa keterangan = 'alpha'.
     * Huruf 't' atau kata 'terlambat' = 'terlambat'.

3. Ekstraksi Laporan Teks (Pesan WhatsApp):
   - Jika laporan memuat lebih dari satu hari/tanggal (mis. "Senin 20 Okt: ..., Selasa 21 Okt: ..."), pisahkan masing-masing menjadi objek sesi tersendiri di array 'sesi'.
   - Tanggal relatif dihitung dari 'tanggal_hari_ini'.

4. Pencocokan Murid:
   - Cocokkan nama murid di laporan/lembar ke daftar 'murid_kelas' yang diberikan dan gunakan 'murid_id'-nya.
   - Nama murid sering disingkat atau panggilan. Cocokkan yang paling masuk akal.
   - Jika ada nama yang sama sekali tidak cocok atau meragukan, JANGAN menebak — masukkan nama aslinya ke 'nama_tidak_dikenali'.

5. Status Murid Lain:
   - Pada lembar matriks, 'status_murid_lain' biasanya 'hadir' (jika yang bertanda titik adalah hadir) atau 'tidak_diketahui'.
   - Pada pesan WA yang hanya mencantumkan yang absen ("yang tidak masuk: A, B"), 'status_murid_lain' adalah 'hadir'.

6. Jam & Program:
   - Jam mulai & selesai dalam format HH:MM (bila tidak disebutkan di laporan, isi null).
   - program_id diisi jika laporan menyebutkan nama program yang cocok dengan referensi.

7. Instruksi Khusus dari Super Admin (Prioritas Tertinggi):
   - Jika laporan memuat bagian '*** INSTRUKSI KHUSUS DARI SUPER ADMIN ***', kamu WAJIB mematuhi dan memprioritaskan instruksi tersebut di atas data mentah:
     * Ubah Jam: Jika admin meminta ubah jam kegiatan pada tanggal atau rentang tanggal tertentu (contoh: "khusus tanggal 10 sampai 15 jam 16:30 - 18:00"), terapkan jam_mulai dan jam_selesai tersebut pada seluruh sesi di rentang tanggal itu.
     * Materi: Jika admin menentukan materi untuk tanggal tertentu (contoh: "materi tanggal 1-10 Bab 1, tanggal 12 ke atas Bab 2"), isi field 'materi' sesuai instruksi.
     * Pengajar Pengganti: Jika admin menginstruksikan pergantian pengajar (contoh: "tanggal 16 pengajar digantikan Ust. Fajar"), beri status 'digantikan' dan isi 'pengganti_id' pada tanggal tersebut.
     * Koreksi Presensi Murid: Jika admin meminta perubahan status murid tertentu (contoh: "Azril izin sakit tanggal 12 sampai 15"), ubah status murid tersebut menjadi 'sakit' dengan keterangan yang diminta.
     * Lewati Tanggal: Jika admin meminta mengabaikan tanggal tertentu (contoh: "abaikan tanggal 14"), JANGAN buat sesi untuk tanggal tersebut.
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
        ?string $instruksi = null,
    ): array {
        $referensi = $this->referensi($kelas);

        $konteks = [
            'tanggal_hari_ini'       => now()->toDateString() . ' (' . now()->locale('id')->translatedFormat('l') . ')',
            'kelas'                  => $kelas->nama,
            'murid_kelas'            => array_map(fn ($m) => ['id' => $m['id'], 'nama' => $m['nama']], $referensi['murid']),
            'pengajar_kelas'         => $referensi['pengajar_kelas'],
            'pengajar_lain'          => $referensi['pengajar_lain'],
            'program'                => $referensi['program'],
            'tanggal_sudah_tercatat' => $this->tanggalSudahTercatat($kelas->id),
        ];

        $driver = $this->aiManager->driver($provider);
        $mentah = $driver->ekstrak($konteks, $teks, $gambar, $this->skema(), self::SYSTEM_PROMPT, $model, $instruksi);

        return [
            'hasil'     => $this->rapikan($mentah, $referensi, $kelas),
            'referensi' => $referensi,
        ];
    }

    /**
     * Simpan draf yang sudah dikonfirmasi admin (bisa 1 sesi atau batch banyak sesi sekaligus).
     *
     * @return array{pertemuan: Pertemuan[], jumlah: int, dilewati: string[]}
     */
    public function simpan(array $data, int $pencatatId): array
    {
        $kelasId = (int) $data['kelas_id'];

        // Normalisasi payload: dukung multi-sesi ('sesi' array) atau single-sesi lama
        $daftarSesi = isset($data['sesi']) && is_array($data['sesi'])
            ? $data['sesi']
            : [$data];

        $pertemuanDibuat = [];
        $tanggalDilewati = [];

        DB::transaction(function () use ($daftarSesi, $kelasId, $pencatatId, &$pertemuanDibuat, &$tanggalDilewati) {
            foreach ($daftarSesi as $sesi) {
                $tanggal = $sesi['tanggal'];

                // Proteksi duplikasi: skip jika sesi pada tanggal ini sudah ada di database
                $sudahAda = Pertemuan::where('kelas_id', $kelasId)->whereDate('tanggal', $tanggal)->exists();
                if ($sudahAda) {
                    $tanggalDilewati[] = $tanggal;
                    continue;
                }

                $tutup = (bool) ($sesi['tutup_sesi'] ?? true);

                $pertemuan = $this->absensi->bukaSesi([
                    'kelas_id'     => $kelasId,
                    'program_id'   => $sesi['program_id'],
                    'jadwal_id'    => $sesi['jadwal_id'] ?? null,
                    'tanggal'      => $tanggal,
                    'jam_mulai'    => $sesi['jam_mulai'] ?? '08:00',
                    'materi'       => $sesi['materi'] ?? null,
                    'pengajar_ids' => array_column($sesi['pengajar'], 'pengajar_id'),
                ], $pencatatId);
                $pertemuan->refresh();

                // Hanya murid yang aktif dan sudah bergabung pada tanggal sesi
                $peserta      = $this->absensi->pesertaSesi($pertemuan);
                $absensiValid = collect($sesi['absensi'] ?? [])
                    ->filter(fn ($a) => in_array($a['murid_id'], $peserta->all(), true))
                    ->values()
                    ->all();

                if (! empty($absensiValid)) {
                    $this->absensi->inputAbsensiBulk($pertemuan, $absensiValid, $pencatatId);
                }

                foreach ($sesi['pengajar'] as $p) {
                    AbsensiPengajar::where('pertemuan_id', $pertemuan->id)
                        ->where('pengajar_id', $p['pengajar_id'])
                        ->update([
                            'status'       => $p['status'],
                            'pengganti_id' => $p['status'] === 'digantikan' ? ($p['pengganti_id'] ?? null) : null,
                            'keterangan'   => $p['keterangan'] ?? null,
                        ]);
                }

                if ($tutup) {
                    $jamSelesai = ! empty($sesi['jam_selesai']) ? $sesi['jam_selesai'] : '09:30';
                    $pertemuan = $this->absensi->tutupSesi($pertemuan, $jamSelesai, kirimNotifikasi: false);
                }

                $pertemuanDibuat[] = $pertemuan->fresh(['kelas', 'program', 'pengajar.user', 'absensiMurid.murid', 'absensiPengajar']);
            }
        });

        // Trigger notifikasi jika diminta (hanya untuk pertemuan yang ditutup)
        foreach ($pertemuanDibuat as $index => $pertemuan) {
            $sesiData = $daftarSesi[$index] ?? [];
            if (! empty($sesiData['tutup_sesi']) && ! empty($sesiData['kirim_notifikasi'])) {
                event(new PertemuanSelesai($pertemuan));
            }
        }

        return [
            'pertemuan' => $pertemuanDibuat,
            'jumlah'    => count($pertemuanDibuat),
            'dilewati'  => $tanggalDilewati,
        ];
    }

    /**
     * Daftar tanggal sesi yang sudah tersimpan di database untuk kelas ini.
     * Digunakan agar AI maupun sistem otomatis melewati (skip) tanggal tersebut.
     *
     * @return string[]
     */
    public function tanggalSudahTercatat(int $kelasId): array
    {
        return Pertemuan::where('kelas_id', $kelasId)
            ->pluck('tanggal')
            ->map(fn ($d) => $d instanceof Carbon ? $d->toDateString() : substr((string) $d, 0, 10))
            ->unique()
            ->values()
            ->all();
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

        $skemaPengajar = [
            'type'       => 'object',
            'properties' => [
                'pengajar_id'  => ['type' => 'integer'],
                'status'       => ['type' => 'string', 'enum' => self::STATUS_PENGAJAR],
                'pengganti_id' => $nullable(['type' => 'integer']),
                'keterangan'   => $nullable(['type' => 'string']),
            ],
            'required'             => ['pengajar_id', 'status', 'pengganti_id', 'keterangan'],
            'additionalProperties' => false,
        ];

        $skemaMurid = [
            'type'       => 'object',
            'properties' => [
                'murid_id'        => ['type' => 'integer'],
                'nama_di_laporan' => ['type' => 'string'],
                'status'          => ['type' => 'string', 'enum' => self::STATUS_MURID],
                'keterangan'      => $nullable(['type' => 'string']),
            ],
            'required'             => ['murid_id', 'nama_di_laporan', 'status', 'keterangan'],
            'additionalProperties' => false,
        ];

        $skemaSesi = [
            'type'       => 'object',
            'properties' => [
                'tanggal'            => ['type' => 'string', 'description' => 'Format YYYY-MM-DD'],
                'jam_mulai'          => $nullable(['type' => 'string', 'description' => 'HH:MM']),
                'jam_selesai'        => $nullable(['type' => 'string', 'description' => 'HH:MM']),
                'program_id'         => $nullable(['type' => 'integer']),
                'materi'             => $nullable(['type' => 'string']),
                'status_murid_lain'  => ['type' => 'string', 'enum' => ['hadir', 'alpha', 'tidak_diketahui']],
                'pengajar'           => [
                    'type'  => 'array',
                    'items' => $skemaPengajar,
                ],
                'absensi'            => [
                    'type'  => 'array',
                    'items' => $skemaMurid,
                ],
            ],
            'required' => [
                'tanggal', 'jam_mulai', 'jam_selesai', 'program_id', 'materi',
                'status_murid_lain', 'pengajar', 'absensi',
            ],
            'additionalProperties' => false,
        ];

        return [
            'type'       => 'object',
            'properties' => [
                'pengajar_umum'       => [
                    'type'  => 'array',
                    'items' => $skemaPengajar,
                ],
                'sesi'                => [
                    'type'  => 'array',
                    'items' => $skemaSesi,
                ],
                'nama_tidak_dikenali' => [
                    'type'  => 'array',
                    'items' => ['type' => 'string'],
                ],
                'catatan'             => [
                    'type'  => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['pengajar_umum', 'sesi', 'nama_tidak_dikenali', 'catatan'],
            'additionalProperties' => false,
        ];
    }

    /**
     * Saring keluaran AI terhadap data sebenarnya:
     * - Cek duplikasi tanggal di database -> tandai 'sudah_ada' dan catat.
     * - Filter ID murid dan pengajar yang valid.
     * - Sediakan fallback pengajar dari pengajar_umum bila sesi tidak merinci pengajar.
     */
    private function rapikan(array $mentah, array $referensi, Kelas $kelas): array
    {
        $catatan         = array_values(array_filter((array) ($mentah['catatan'] ?? []), 'is_string'));
        $tidakDikenali   = array_values(array_filter((array) ($mentah['nama_tidak_dikenali'] ?? []), 'is_string'));
        $idMurid         = array_column($referensi['murid'], 'id');
        $idPengajarKelas = array_column($referensi['pengajar_kelas'], 'id');
        $idSemuaPengajar = array_merge($idPengajarKelas, array_column($referensi['pengajar_lain'], 'id'));
        $idProgram       = array_column($referensi['program'], 'id');

        // Pengajar umum (misal dari header lembar absensi)
        $pengajarUmum = [];
        foreach ((array) ($mentah['pengajar_umum'] ?? []) as $p) {
            $id = $p['pengajar_id'] ?? null;
            if (! in_array($id, $idPengajarKelas, true) || isset($pengajarUmum[$id])
                || ! in_array($p['status'] ?? null, self::STATUS_PENGAJAR, true)) {
                continue;
            }
            $pengganti = $p['pengganti_id'] ?? null;
            $pengajarUmum[$id] = [
                'pengajar_id'  => $id,
                'status'       => $p['status'],
                'pengganti_id' => in_array($pengganti, $idSemuaPengajar, true) && $pengganti !== $id ? $pengganti : null,
                'keterangan'   => $p['keterangan'] ?? null,
            ];
        }

        // Jika tidak ada pengajar_umum tapi kelas hanya punya 1 pengajar, jadikan default
        if (empty($pengajarUmum) && count($referensi['pengajar_kelas']) === 1) {
            $pengajarUmum = [
                [
                    'pengajar_id'  => $referensi['pengajar_kelas'][0]['id'],
                    'status'       => 'hadir',
                    'pengganti_id' => null,
                    'keterangan'   => null,
                ],
            ];
        }

        $daftarTanggalSudahAda = $this->tanggalSudahTercatat($kelas->id);
        $sesiRapih = [];
        $tanggalTerproses = [];

        foreach ((array) ($mentah['sesi'] ?? []) as $s) {
            $tanggal = $this->tanggalValid($s['tanggal'] ?? null);
            if (! $tanggal) {
                continue;
            }

            // Hindari duplikasi tanggal di dalam respons AI itu sendiri
            if (in_array($tanggal, $tanggalTerproses, true)) {
                continue;
            }
            $tanggalTerproses[] = $tanggal;

            // Cek apakah tanggal ini sudah tercatat di database
            $sudahAdaDiDb = in_array($tanggal, $daftarTanggalSudahAda, true);
            $pertemuanAda = $sudahAdaDiDb
                ? Pertemuan::where('kelas_id', $kelas->id)->whereDate('tanggal', $tanggal)->first(['id', 'tanggal'])
                : null;

            if ($sudahAdaDiDb) {
                $catatan[] = "Sesi tanggal {$tanggal} sudah ada di database (Pertemuan #{$pertemuanAda?->id}) — otomatis dilewati.";
            }

            // Pengajar sesi
            $pengajarSesi = [];
            foreach ((array) ($s['pengajar'] ?? []) as $p) {
                $id = $p['pengajar_id'] ?? null;
                if (! in_array($id, $idPengajarKelas, true) || isset($pengajarSesi[$id])
                    || ! in_array($p['status'] ?? null, self::STATUS_PENGAJAR, true)) {
                    continue;
                }
                $pengganti = $p['pengganti_id'] ?? null;
                $pengajarSesi[$id] = [
                    'pengajar_id'  => $id,
                    'status'       => $p['status'],
                    'pengganti_id' => in_array($pengganti, $idSemuaPengajar, true) && $pengganti !== $id ? $pengganti : null,
                    'keterangan'   => $p['keterangan'] ?? null,
                ];
            }
            if (empty($pengajarSesi)) {
                $pengajarSesi = array_values($pengajarUmum);
            }

            // Absensi murid
            $absensiMurid = [];
            foreach ((array) ($s['absensi'] ?? []) as $m) {
                $id = $m['murid_id'] ?? null;
                if (! in_array($id, $idMurid, true) || ! in_array($m['status'] ?? null, self::STATUS_MURID, true)) {
                    $tidakDikenali[] = $m['nama_di_laporan'] ?? "id {$id}";
                    continue;
                }
                if (isset($absensiMurid[$id])) {
                    continue;
                }
                $absensiMurid[$id] = [
                    'murid_id'        => $id,
                    'nama_di_laporan' => (string) ($m['nama_di_laporan'] ?? ''),
                    'status'          => $m['status'],
                    'keterangan'      => $m['keterangan'] ?? null,
                ];
            }

            $programId = $s['program_id'] ?? null;

            $sesiRapih[] = [
                'tanggal'            => $tanggal,
                'jam_mulai'          => $this->jamValid($s['jam_mulai'] ?? null),
                'jam_selesai'        => $this->jamValid($s['jam_selesai'] ?? null),
                'program_id'         => in_array($programId, $idProgram, true) ? $programId : null,
                'materi'             => is_string($s['materi'] ?? null) ? $s['materi'] : null,
                'status_murid_lain'  => in_array($s['status_murid_lain'] ?? null, ['hadir', 'alpha'], true)
                    ? $s['status_murid_lain'] : 'tidak_diketahui',
                'pengajar'           => array_values($pengajarSesi),
                'absensi'            => array_values($absensiMurid),
                'sudah_ada'          => $sudahAdaDiDb,
                'pertemuan_id_ada'   => $pertemuanAda?->id,
            ];
        }

        // Urutkan sesi berdasarkan tanggal secara kronologis
        usort($sesiRapih, fn ($a, $b) => strcmp($a['tanggal'], $b['tanggal']));

        $totalSesi     = count($sesiRapih);
        $sesiBaru      = count(array_filter($sesiRapih, fn ($s) => empty($s['sudah_ada'])));
        $sesiSudahAda  = count(array_filter($sesiRapih, fn ($s) => ! empty($s['sudah_ada'])));

        $hasil = [
            'mode'                => $totalSesi > 1 ? 'multi' : 'single',
            'total_sesi'          => $totalSesi,
            'sesi_baru'           => $sesiBaru,
            'sesi_sudah_ada'      => $sesiSudahAda,
            'sesi'                => $sesiRapih,
            'pengajar_umum'       => array_values($pengajarUmum),
            'nama_tidak_dikenali' => array_values(array_unique($tidakDikenali)),
            'catatan'             => array_values(array_unique($catatan)),
        ];

        // Untuk kompatibilitas single-sesi lama (jika total_sesi === 1)
        if ($totalSesi === 1) {
            $s1 = $sesiRapih[0];
            $hasil['tanggal']           = $s1['tanggal'];
            $hasil['jam_mulai']         = $s1['jam_mulai'];
            $hasil['jam_selesai']       = $s1['jam_selesai'];
            $hasil['program_id']        = $s1['program_id'];
            $hasil['materi']            = $s1['materi'];
            $hasil['status_murid_lain'] = $s1['status_murid_lain'];
            $hasil['pengajar']          = $s1['pengajar'];
            $hasil['murid']             = $s1['absensi'];
        }

        return $hasil;
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
