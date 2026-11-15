<?php

namespace App\Services\Ai;

use App\Events\PertemuanSelesai;
use App\Models\AbsensiPengajar;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Libur;
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

    /** Seberapa jauh ke belakang jadwal/libur disertakan sebagai konteks AI. */
    private const RENTANG_HARI_KONTEKS = 120;

    private const SYSTEM_PROMPT = <<<'PROMPT'
Kamu membantu super admin sebuah KBM (Kegiatan Belajar Mengajar) mencatat laporan absensi yang dikirim pengajar.
Input yang diberikan super admin bisa berupa:
1. Pesan teks bebas (pesan WhatsApp harian, rangkuman beberapa hari, atau instruksi penyesuaian/pencatatan sesi).
2. Foto lembar absensi:
   - Bisa berupa catatan harian.
   - ATAU berupa LEMBAR REKAP MATRIKS BULANAN (seperti formulir "DAFTAR HADIR" dengan kolom tanggal 1 sampai 31 dan baris nama-nama murid).
3. Kombinasi foto lembar absensi DAN teks pesan/instruksi.
4. Teks pesan/instruksi saja tanpa foto (mis. instruksi pencatatan atau koreksi sesi).

Tugasmu: ekstrak SEMUA sesi pertemuan yang ada di dalam laporan (bisa 1 hari, beberapa hari, atau rekap sebulan penuh) menjadi data terstruktur sesuai skema JSON.

Aturan Penting:
1. Pengecekan Sesi yang Sudah Tercatat (Skip):
   - Di data referensi terdapat 'sesi_sudah_tercatat' (daftar sesi yang SUDAH TERSIMPAN di database untuk kelas ini: tanggal, jam_mulai, jadwal_id).
   - Satu kelas bisa punya LEBIH DARI SATU sesi dalam sehari (misalnya sesi Maghrib dan sesi Isya dengan jam berbeda). Buat objek sesi TERPISAH untuk tiap sesi, masing-masing dengan jadwal_id, jam_mulai, dan jam_selesai-nya sendiri.
   - Bila laporan menyebut sesi yang tanggalnya DAN jadwal/jam-nya sama dengan salah satu 'sesi_sudah_tercatat', JANGAN BUAT objek sesi itu (abaikan / skip) dan tuliskan di 'catatan' bahwa sesi dilewati karena sudah ada di database. Tanggal yang sama dengan jadwal/jam BERBEDA bukan duplikat dan tetap dibuat.
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

7. Instruksi Khusus dan Penyesuaian dari Super Admin (Prioritas Tertinggi):
   - Teks input atau instruksi dari super admin dapat berupa penyesuaian untuk foto/laporan yang dilampirkan, ATAU berupa instruksi pencatatan sesi mandiri.
   - Kamu WAJIB mematuhi dan memprioritaskan instruksi tersebut di atas data mentah:
     * Ubah Jam: Jika admin meminta ubah jam kegiatan pada tanggal atau rentang tanggal tertentu (contoh: "khusus tanggal 10 sampai 15 jam 16:30 - 18:00"), terapkan jam_mulai dan jam_selesai tersebut pada seluruh sesi di rentang tanggal itu.
     * Materi: Jika admin menentukan materi untuk tanggal tertentu (contoh: "materi tanggal 1-10 Bab 1, tanggal 12 ke atas Bab 2"), isi field 'materi' sesuai instruksi.
     * Pengajar Pengganti: Jika admin menginstruksikan pergantian pengajar (contoh: "tanggal 16 pengajar digantikan Ust. Fajar"), beri status 'digantikan' dan isi 'pengganti_id' pada tanggal tersebut.
     * Koreksi Presensi Murid: Jika admin meminta perubahan status murid tertentu (contoh: "Azril izin sakit tanggal 12 sampai 15"), ubah status murid tersebut menjadi 'sakit' dengan keterangan yang diminta.
     * Lewati Tanggal: Jika admin meminta mengabaikan tanggal tertentu (contoh: "abaikan tanggal 14"), JANGAN buat sesi untuk tanggal tersebut.
     * Catat Sesi Mandiri: Jika hanya ada teks instruksi tanpa foto (contoh: "Catat sesi pertemuan Ahad kemarin jam 08:00, materi Tahsin, pengajar Ust. Ahmad, semua murid hadir"), susun draf sesi sesuai instruksi yang diberikan.

8. Jadwal Kelas ('jadwal_kelas') dan Libur ('libur_kelas'):
   - 'jadwal_kelas' berisi jadwal rutin kelas (hari, jam, masa berlaku). Sesi rutin HANYA terjadi pada hari dan tanggal yang sesuai jadwal tersebut.
   - Isi 'jadwal_id' pada tiap sesi dengan id jadwal yang cocok (hari sama dan tanggal masih dalam masa berlaku). Jika tidak ada yang cocok, isi null.
   - Jika jam tidak disebut di laporan atau instruksi, isi jam_mulai dan jam_selesai dengan null — sistem akan mengisinya dari jadwal. JANGAN mengarang jam (misalnya 08:00-09:30).
   - JANGAN membuat sesi pada tanggal yang jatuh di hari tanpa jadwal (mis. Sabtu/Minggu bila jadwal hanya Senin-Jumat) atau pada tanggal libur ('libur_kelas'), KECUALI super admin secara eksplisit meminta sesi khusus/pengganti pada tanggal tersebut. Bila ragu, jangan buat sesinya dan jelaskan di 'catatan'.
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
            'sesi_sudah_tercatat'    => $this->sesiSudahTercatat($kelas->id),
            'jadwal_kelas'           => $this->jadwalKelas($kelas),
            'libur_kelas'            => $this->liburKelas($kelas),
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
        $idKirimNotifikasi = [];

        // Tentukan jadwal & jam tiap sesi dan validasi SEMUANYA sebelum ada yang disimpan
        $rencana = $this->susunRencana($daftarSesi, $kelasId, multi: isset($data['sesi']) && is_array($data['sesi']));

        DB::transaction(function () use ($daftarSesi, $rencana, $kelasId, $pencatatId, &$pertemuanDibuat, &$tanggalDilewati, &$idKirimNotifikasi) {
            foreach ($daftarSesi as $i => $sesi) {
                $tanggal = $sesi['tanggal'];

                // Proteksi duplikasi: skip jika sesi yang sama (tanggal + jadwal/jam) sudah ada di database.
                // Sesi lain di tanggal yang sama (mis. Maghrib dan Isya) bukan duplikat.
                $rencanaSesi = $rencana[$i];
                if (! empty($rencanaSesi['lewati'])
                    || $this->cariSesiSudahAda($kelasId, $tanggal, $rencanaSesi['jadwal_id'], $rencanaSesi['jam_mulai'])) {
                    $tanggalDilewati[] = $tanggal;
                    continue;
                }

                $tutup = (bool) ($sesi['tutup_sesi'] ?? true);

                $pertemuan = $this->absensi->bukaSesi([
                    'kelas_id'     => $kelasId,
                    'program_id'   => $sesi['program_id'],
                    'jadwal_id'    => $rencanaSesi['jadwal_id'],
                    'tanggal'      => $tanggal,
                    'jam_mulai'    => $rencanaSesi['jam_mulai'],
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
                    $pertemuan = $this->absensi->tutupSesi($pertemuan, $rencanaSesi['jam_selesai'], kirimNotifikasi: false);

                    if (! empty($sesi['kirim_notifikasi'])) {
                        $idKirimNotifikasi[] = $pertemuan->id;
                    }
                }

                $pertemuanDibuat[] = $pertemuan->fresh(['kelas', 'program', 'pengajar.user', 'absensiMurid.murid', 'absensiPengajar']);
            }
        });

        // Trigger notifikasi jika diminta (hanya untuk pertemuan yang ditutup)
        foreach ($pertemuanDibuat as $pertemuan) {
            if (in_array($pertemuan->id, $idKirimNotifikasi, true)) {
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
     * Tentukan jadwal_id, jam mulai, dan jam selesai tiap sesi yang akan dibuat, lalu validasi semuanya
     * sebelum ada yang disimpan (agar batch tidak tersimpan setengah-setengah).
     *
     * - Mode multi-sesi: bila jadwal_id kosong, dicari otomatis dari tanggal/kelas/program.
     *   Tanggal libur tidak dikaitkan ke jadwal (sesi di hari libur boleh, tanpa jadwal).
     * - Jam kosong diisi dari jadwal. TIDAK ada jam bawaan: tanpa jam dan tanpa jadwal sesi ditolak.
     * - Sesi yang tidak punya jadwal ("di luar jadwal") hanya diterima bila dikonfirmasi eksplisit
     *   lewat 'konfirmasi_di_luar_jadwal'.
     *
     * @return array<int, array{lewati: true}|array{jadwal_id: ?int, jam_mulai: string, jam_selesai: ?string}>
     *
     * @throws ValidationException
     */
    private function susunRencana(array $daftarSesi, int $kelasId, bool $multi): array
    {
        $rencana   = [];
        $galat     = [];
        $diLuar    = [];

        foreach ($daftarSesi as $i => $sesi) {
            $tanggal = $sesi['tanggal'];
            $awalan  = $multi ? "sesi.{$i}." : '';

            // Sesi yang sama (tanggal + jadwal/jam) yang sudah ada akan dilewati saat penyimpanan
            $jadwalDikirim = ! empty($sesi['jadwal_id']) ? (int) $sesi['jadwal_id'] : null;
            $jamDikirim    = ! empty($sesi['jam_mulai']) ? substr($sesi['jam_mulai'], 0, 5) : null;
            if ($this->cariSesiSudahAda($kelasId, $tanggal, $jadwalDikirim, $jamDikirim)) {
                $rencana[$i] = ['lewati' => true];
                continue;
            }

            $jadwal = null;
            if (! empty($sesi['jadwal_id'])) {
                $jadwal = Jadwal::find($sesi['jadwal_id']); // kecocokannya divalidasi AbsensiService::bukaSesi
            } elseif ($multi) {
                $jadwal = $this->absensi->cariJadwalUntukTanggal($kelasId, $sesi['program_id'] ?? null, $tanggal, $sesi['jam_mulai'] ?? null);
                if ($jadwal && Libur::untukSesi($kelasId, $jadwal->id, $tanggal)->exists()) {
                    $jadwal = null;
                }
            }

            $jamMulai = ! empty($sesi['jam_mulai']) ? substr($sesi['jam_mulai'], 0, 5) : ($jadwal ? substr((string) $jadwal->jam_mulai, 0, 5) : null);
            if (! $jamMulai) {
                $galat["{$awalan}jam_mulai"] = "Jam mulai sesi {$tanggal} wajib diisi (tidak ada jadwal yang bisa dijadikan acuan).";
                continue;
            }

            $jamSelesai = null;
            if (($sesi['tutup_sesi'] ?? true)) {
                $jamSelesai = ! empty($sesi['jam_selesai'])
                    ? substr($sesi['jam_selesai'], 0, 5)
                    : ($jadwal && substr((string) $jadwal->jam_selesai, 0, 5) > $jamMulai ? substr((string) $jadwal->jam_selesai, 0, 5) : null);

                if (! $jamSelesai) {
                    $galat["{$awalan}jam_selesai"] = "Jam selesai sesi {$tanggal} wajib diisi bila sesi langsung ditutup.";
                    continue;
                }
            }

            if ($multi && ! $jadwal && empty($sesi['konfirmasi_di_luar_jadwal'])) {
                $diLuar[] = $tanggal;
            }

            $rencana[$i] = [
                'jadwal_id'   => $jadwal?->id,
                'jam_mulai'   => $jamMulai,
                'jam_selesai' => $jamSelesai,
            ];
        }

        if ($diLuar) {
            $galat['sesi'] = 'Sesi pada tanggal berikut tidak sesuai jadwal kelas (hari tanpa jadwal atau hari libur): '
                . implode(', ', $diLuar) . '. Konfirmasi sebagai sesi di luar jadwal bila memang sesi khusus/pengganti.';
        }

        if ($galat) {
            throw ValidationException::withMessages($galat);
        }

        return $rencana;
    }

    /**
     * Sesi yang sudah tersimpan di database untuk kelas ini (tanggal, jam mulai, jadwal).
     * Digunakan agar AI maupun sistem melewati (skip) sesi yang sama. Satu kelas boleh punya
     * lebih dari satu sesi per hari (mis. Maghrib dan Isya), jadi yang dibandingkan adalah
     * tanggal beserta jadwal/jamnya, bukan tanggal saja.
     *
     * @return array<int, array{tanggal: string, jam_mulai: string, jadwal_id: ?int}>
     */
    public function sesiSudahTercatat(int $kelasId): array
    {
        return Pertemuan::where('kelas_id', $kelasId)
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get(['tanggal', 'jam_mulai', 'jadwal_id'])
            ->map(fn (Pertemuan $p) => [
                'tanggal'   => $p->tanggal->toDateString(),
                'jam_mulai' => substr((string) $p->jam_mulai, 0, 5),
                'jadwal_id' => $p->jadwal_id,
            ])
            ->all();
    }

    /**
     * Cari sesi yang sudah ada dan sama dengan sesi yang akan dibuat: tanggal sama dan
     * (jadwal sama, atau jam mulai sama). Bila jadwal maupun jam belum diketahui, sesi lain
     * di tanggal itu tidak bisa dibedakan sehingga dianggap sama (perilaku lama, mencegah ganda).
     */
    private function cariSesiSudahAda(int $kelasId, string $tanggal, ?int $jadwalId, ?string $jamMulai): ?Pertemuan
    {
        $ada = Pertemuan::where('kelas_id', $kelasId)->whereDate('tanggal', $tanggal)->get(['id', 'jadwal_id', 'jam_mulai']);
        if ($ada->isEmpty()) {
            return null;
        }

        $jam = $jamMulai ? substr($jamMulai, 0, 5) : null;
        if ($jadwalId === null && $jam === null) {
            return $ada->first();
        }

        return $ada->first(fn (Pertemuan $p) => ($jadwalId !== null && (int) $p->jadwal_id === $jadwalId)
            || ($jam !== null && substr((string) $p->jam_mulai, 0, 5) === $jam));
    }

    /**
     * Jadwal rutin kelas (yang masih berlaku atau baru berakhir) sebagai konteks AI.
     *
     * @return array<int, array<string, mixed>>
     */
    private function jadwalKelas(Kelas $kelas): array
    {
        return Jadwal::query()
            ->where(fn ($q) => $q->whereNull('kelas_id')->orWhere('kelas_id', $kelas->id))
            ->where(fn ($q) => $q->whereNull('selesai_berlaku')->orWhere('selesai_berlaku', '>=', now()->subDays(self::RENTANG_HARI_KONTEKS)->toDateString()))
            ->orderBy('mulai_berlaku')
            ->get()
            ->map(fn (Jadwal $j) => [
                'id'              => $j->id,
                'hari'            => $j->hari,
                'jam_mulai'       => substr((string) $j->jam_mulai, 0, 5),
                'jam_selesai'     => substr((string) $j->jam_selesai, 0, 5),
                'program_id'      => $j->program_id,
                'frekuensi'       => $j->frekuensi,
                'minggu_ke'       => $j->minggu_ke,
                'mulai_berlaku'   => $j->mulai_berlaku?->toDateString(),
                'selesai_berlaku' => $j->selesai_berlaku?->toDateString(),
            ])->values()->all();
    }

    /** Libur kelas (atau libur semua kelas) di rentang yang relevan sebagai konteks AI. */
    private function liburKelas(Kelas $kelas): array
    {
        return Libur::query()
            ->where(fn ($q) => $q->whereNull('kelas_id')->orWhere('kelas_id', $kelas->id))
            ->where('tanggal_selesai', '>=', now()->subDays(self::RENTANG_HARI_KONTEKS)->toDateString())
            ->orderBy('tanggal_mulai')
            ->get(['tanggal_mulai', 'tanggal_selesai', 'keterangan', 'jadwal_id'])
            ->map(fn (Libur $l) => [
                'tanggal_mulai'   => $l->tanggal_mulai->toDateString(),
                'tanggal_selesai' => $l->tanggal_selesai->toDateString(),
                'keterangan'      => $l->keterangan,
                'jadwal_id'       => $l->jadwal_id,
            ])->values()->all();
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
                'jadwal_id'          => $nullable(['type' => 'integer', 'description' => 'id dari jadwal_kelas yang cocok, atau null']),
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
                'tanggal', 'jam_mulai', 'jam_selesai', 'program_id', 'jadwal_id', 'materi',
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

        $sesiRapih = [];
        $kunciTerproses = [];

        foreach ((array) ($mentah['sesi'] ?? []) as $s) {
            $tanggal = $this->tanggalValid($s['tanggal'] ?? null);
            if (! $tanggal) {
                continue;
            }

            // Satu kelas boleh punya beberapa sesi per hari (mis. Maghrib dan Isya): sesi dianggap sama
            // bila tanggal DAN jadwal/jam-nya sama.
            $jadwalAiId = is_int($s['jadwal_id'] ?? null) ? $s['jadwal_id'] : null;
            $jamAi      = $this->jamValid($s['jam_mulai'] ?? null);

            // Hindari duplikasi sesi di dalam respons AI itu sendiri
            $kunci = $tanggal . '|' . ($jadwalAiId ?? $jamAi ?? '');
            if (in_array($kunci, $kunciTerproses, true)) {
                continue;
            }
            $kunciTerproses[] = $kunci;

            // Cek apakah sesi ini sudah tercatat di database
            $pertemuanAda = $this->cariSesiSudahAda($kelas->id, $tanggal, $jadwalAiId, $jamAi);
            $sudahAdaDiDb = $pertemuanAda !== null;

            if ($sudahAdaDiDb) {
                $catatan[] = "Sesi tanggal {$tanggal}" . ($jamAi ? " pukul {$jamAi}" : '')
                    . " sudah ada di database (Pertemuan #{$pertemuanAda->id}) — otomatis dilewati.";
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
            $programId = in_array($programId, $idProgram, true) ? $programId : null;
            $jamMulai  = $this->jamValid($s['jam_mulai'] ?? null);
            $jamSelesai = $this->jamValid($s['jam_selesai'] ?? null);

            // Jadwal: pakai jadwal_id dari AI bila benar-benar cocok dengan tanggalnya; kalau tidak, cari sendiri.
            // Jam yang kosong diisi dari jadwal (bukan ditebak/diberi nilai bawaan).
            $jadwal = null;
            $libur  = null;
            $peringatan = [];
            if (! $sudahAdaDiDb) {
                $jadwal = $this->jadwalDariAi($s['jadwal_id'] ?? null, $kelas->id, $tanggal)
                    ?? $this->absensi->cariJadwalUntukTanggal($kelas->id, $programId, $tanggal, $jamMulai);

                $libur = Libur::untukSesi($kelas->id, $jadwal?->id, $tanggal)->first();
                if ($libur) {
                    $peringatan[] = "Tanggal {$tanggal} libur ({$libur->keterangan}).";
                    $jadwal = null; // sesi pada hari libur tidak dikaitkan ke jadwal (lihat AbsensiService::bukaSesi)
                }

                if ($jadwal) {
                    $programId ??= $jadwal->program_id;
                    if ($jamMulai === null) {
                        $jamMulai = substr((string) $jadwal->jam_mulai, 0, 5);
                    }
                    $jadwalSelesai = substr((string) $jadwal->jam_selesai, 0, 5);
                    if ($jamSelesai === null && $jadwalSelesai > $jamMulai) {
                        $jamSelesai = $jadwalSelesai;
                    }
                } elseif (! $libur) {
                    $hariIni = Carbon::parse($tanggal)->locale('id')->translatedFormat('l');
                    $peringatan[] = "Tidak ada jadwal kelas ini pada hari {$hariIni}, {$tanggal} — sesi akan tercatat di luar jadwal.";
                }
            }

            $sesiRapih[] = [
                'tanggal'            => $tanggal,
                'jam_mulai'          => $jamMulai,
                'jam_selesai'        => $jamSelesai,
                'program_id'         => $programId,
                'jadwal_id'          => $jadwal?->id,
                'di_luar_jadwal'     => ! $sudahAdaDiDb && $jadwal === null,
                'libur'              => $libur?->keterangan,
                'peringatan'         => $peringatan,
                'materi'             => is_string($s['materi'] ?? null) ? $s['materi'] : null,
                'status_murid_lain'  => in_array($s['status_murid_lain'] ?? null, ['hadir', 'alpha'], true)
                    ? $s['status_murid_lain'] : 'tidak_diketahui',
                'pengajar'           => array_values($pengajarSesi),
                'absensi'            => array_values($absensiMurid),
                'sudah_ada'          => $sudahAdaDiDb,
                'pertemuan_id_ada'   => $pertemuanAda?->id,
            ];
        }

        // Urutkan sesi secara kronologis (tanggal, lalu jam mulai bila ada beberapa sesi sehari)
        usort($sesiRapih, fn ($a, $b) => strcmp($a['tanggal'] . ' ' . ($a['jam_mulai'] ?? ''), $b['tanggal'] . ' ' . ($b['jam_mulai'] ?? '')));

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
            $hasil['jadwal_id']         = $s1['jadwal_id'];
            $hasil['materi']            = $s1['materi'];
            $hasil['status_murid_lain'] = $s1['status_murid_lain'];
            $hasil['pengajar']          = $s1['pengajar'];
            $hasil['murid']             = $s1['absensi'];
        }

        return $hasil;
    }

    /** jadwal_id dari AI hanya dipercaya bila jadwal itu memang berlaku untuk kelas & tanggal tersebut. */
    private function jadwalDariAi(mixed $jadwalId, int $kelasId, string $tanggal): ?Jadwal
    {
        if (! is_int($jadwalId)) {
            return null;
        }

        return $this->absensi->kandidatJadwal($kelasId, null, $tanggal)->firstWhere('id', $jadwalId);
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
