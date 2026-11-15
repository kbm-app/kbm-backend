<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\User;
use App\Services\Ai\AsistenAbsensiService;
use App\Services\Ai\Contracts\AiDriverInterface;
use App\Services\Ai\Drivers\OpenAiDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Asisten AI harus mengaitkan sesi ke jadwal kelas, memakai jam jadwal (bukan nilai bawaan),
 * dan menolak sesi di luar jadwal yang tidak dikonfirmasi.
 *
 * Migrasi memakai SQL khusus PostgreSQL, jadi tes ini harus jalan di PostgreSQL
 * (mis. DB_CONNECTION=pgsql DB_DATABASE=kbm_testing).
 */
class AsistenAbsensiJadwalTest extends TestCase
{
    use RefreshDatabase;

    private int $kelasId;
    private int $programId;
    private int $pengajarId;
    private int $adminId;

    /** @var array<string, int> hari => jadwal_id */
    private array $jadwal = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminId = User::factory()->create(['role' => UserRole::SuperAdmin])->id;
        $userPengajar  = User::factory()->create(['role' => UserRole::Pengajar])->id;
        $now           = now();

        $this->programId = DB::table('program')->insertGetId([
            'nama' => 'Pengajian Rutin', 'jenis' => 'pengajian_rutin', 'is_aktif' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->kelasId = DB::table('kelas')->insertGetId([
            'nama' => 'Kelas 5', 'is_aktif' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->pengajarId = DB::table('pengajar')->insertGetId([
            'user_id' => $userPengajar, 'jenis_kelamin' => 'L', 'tanggal_bergabung' => '2026-01-01',
            'is_aktif' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('kelas_pengajar')->insert([
            'kelas_id' => $this->kelasId, 'pengajar_id' => $this->pengajarId, 'peran' => 'utama',
            'tahun_ajaran' => '2026/2027', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $muridId = DB::table('murid')->insertGetId([
            'nama' => 'Hafid', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2015-01-01',
            'tanggal_masuk' => '2026-07-01', 'status' => 'aktif', 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('murid_kelas')->insert([
            'murid_id' => $muridId, 'kelas_id' => $this->kelasId, 'tahun_ajaran' => '2026/2027',
            'tanggal_masuk' => '2026-07-01', 'status' => 'aktif', 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Jadwal Kelas 5: Senin–Jumat 16:10–17:00 sejak 2026-09-26
        foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat'] as $hari) {
            $id = DB::table('jadwal')->insertGetId([
                'program_id' => $this->programId, 'kelas_id' => $this->kelasId, 'frekuensi' => 'mingguan',
                'hari' => $hari, 'jam_mulai' => '16:10', 'jam_selesai' => '17:00',
                'mulai_berlaku' => '2026-09-26', 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('jadwal_pengajar')->insert([
                'jadwal_id' => $id, 'pengajar_id' => $this->pengajarId, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $this->jadwal[$hari] = $id;
        }
    }

    /** Draf sesi seperti yang dikirim layar konfirmasi ke endpoint simpan. */
    private function sesi(string $tanggal, array $override = []): array
    {
        return array_merge([
            'tanggal'          => $tanggal,
            'program_id'       => $this->programId,
            'jadwal_id'        => null,
            'jam_mulai'        => null,
            'jam_selesai'      => null,
            'materi'           => null,
            'pengajar'         => [['pengajar_id' => $this->pengajarId, 'status' => 'hadir', 'pengganti_id' => null, 'keterangan' => null]],
            'absensi'          => [],
            'tutup_sesi'       => true,
            'kirim_notifikasi' => false,
        ], $override);
    }

    private function simpan(array $daftarSesi): array
    {
        return app(AsistenAbsensiService::class)->simpan(['kelas_id' => $this->kelasId, 'sesi' => $daftarSesi], $this->adminId);
    }

    /** Ganti driver AI dengan keluaran tetap; mengembalikan konteks yang diterima driver. */
    private function fakeAi(array $mentah): \ArrayObject
    {
        $konteks = new \ArrayObject();

        $this->app->bind(OpenAiDriver::class, fn () => new class($mentah, $konteks) implements AiDriverInterface {
            public function __construct(private array $mentah, private \ArrayObject $konteks) {}

            public function ekstrak(array $konteksData, ?string $teks, array $gambar, array $schema, string $systemPrompt, ?string $modelOverride = null, ?string $instruksi = null): array
            {
                $this->konteks->exchangeArray($konteksData);

                return $this->mentah;
            }
        });
        config(['services.ai.provider' => 'openai']);

        return $konteks;
    }

    private function sesiAi(string $tanggal, ?int $jadwalId = null, ?string $mulai = null, ?string $selesai = null): array
    {
        return [
            'tanggal' => $tanggal, 'jam_mulai' => $mulai, 'jam_selesai' => $selesai, 'program_id' => $this->programId,
            'jadwal_id' => $jadwalId, 'materi' => null, 'status_murid_lain' => 'hadir', 'pengajar' => [], 'absensi' => [],
        ];
    }

    // ---- simpan ----------------------------------------------------------------------------

    public function test_sesi_hari_kerja_otomatis_terkait_jadwal_dan_memakai_jam_jadwal(): void
    {
        $hasil = $this->simpan([$this->sesi('2026-10-05')]); // Senin

        $pertemuan = Pertemuan::findOrFail($hasil['pertemuan'][0]->id);
        $this->assertSame($this->jadwal['senin'], $pertemuan->jadwal_id);
        $this->assertSame('16:10', substr($pertemuan->jam_mulai, 0, 5));
        $this->assertSame('17:00', substr($pertemuan->jam_selesai, 0, 5));
        $this->assertSame('selesai', $pertemuan->status);
    }

    public function test_jam_dari_laporan_tetap_dipakai_tetapi_sesi_tetap_terkait_jadwal(): void
    {
        $hasil = $this->simpan([$this->sesi('2026-10-06', ['jam_mulai' => '16:20', 'jam_selesai' => '17:10'])]); // Selasa

        $pertemuan = Pertemuan::findOrFail($hasil['pertemuan'][0]->id);
        $this->assertSame($this->jadwal['selasa'], $pertemuan->jadwal_id);
        $this->assertSame('16:20', substr($pertemuan->jam_mulai, 0, 5));
        $this->assertSame('17:10', substr($pertemuan->jam_selesai, 0, 5));
    }

    public function test_sesi_akhir_pekan_tanpa_konfirmasi_ditolak_dan_batch_tidak_tersimpan_setengah(): void
    {
        try {
            $this->simpan([
                $this->sesi('2026-10-05'),                                              // Senin: valid
                $this->sesi('2026-10-03', ['jam_mulai' => '08:00', 'jam_selesai' => '09:30']), // Sabtu: tanpa jadwal
            ]);
            $this->fail('Seharusnya ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('sesi', $e->errors());
            $this->assertStringContainsString('2026-10-03', $e->errors()['sesi'][0]);
        }

        $this->assertSame(0, Pertemuan::count(), 'Tidak boleh ada sesi yang tersimpan bila ada yang ditolak.');
    }

    public function test_sesi_di_luar_jadwal_dengan_konfirmasi_tersimpan_tanpa_jadwal(): void
    {
        $hasil = $this->simpan([$this->sesi('2026-10-03', [
            'jam_mulai' => '08:00', 'jam_selesai' => '09:30', 'konfirmasi_di_luar_jadwal' => true,
        ])]);

        $pertemuan = Pertemuan::findOrFail($hasil['pertemuan'][0]->id);
        $this->assertNull($pertemuan->jadwal_id);
        $this->assertSame('08:00', substr($pertemuan->jam_mulai, 0, 5));
    }

    public function test_tanpa_jam_dan_tanpa_jadwal_ditolak_tanpa_jam_bawaan(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->simpan([$this->sesi('2026-10-03', ['konfirmasi_di_luar_jadwal' => true])]); // Sabtu, jam kosong
        } finally {
            $this->assertSame(0, Pertemuan::count());
        }
    }

    public function test_tanggal_libur_tidak_dikaitkan_ke_jadwal_dan_butuh_konfirmasi(): void
    {
        DB::table('libur')->insert([
            'kelas_id' => $this->kelasId, 'tanggal_mulai' => '2026-10-05', 'tanggal_selesai' => '2026-10-05',
            'keterangan' => 'Libur uji', 'created_at' => now(), 'updated_at' => now(),
        ]);

        try {
            $this->simpan([$this->sesi('2026-10-05', ['jam_mulai' => '16:10', 'jam_selesai' => '17:00'])]);
            $this->fail('Seharusnya ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('sesi', $e->errors());
        }

        $hasil = $this->simpan([$this->sesi('2026-10-05', ['jam_mulai' => '16:10', 'jam_selesai' => '17:00', 'konfirmasi_di_luar_jadwal' => true])]);
        $this->assertNull(Pertemuan::findOrFail($hasil['pertemuan'][0]->id)->jadwal_id);
    }

    public function test_tanggal_yang_sudah_ada_dilewati(): void
    {
        $this->simpan([$this->sesi('2026-10-05')]);
        $hasil = $this->simpan([$this->sesi('2026-10-05'), $this->sesi('2026-10-06')]);

        $this->assertSame(['2026-10-05'], $hasil['dilewati']);
        $this->assertSame(1, $hasil['jumlah']);
        $this->assertSame(2, Pertemuan::count());
    }

    // ---- ekstrak ---------------------------------------------------------------------------

    public function test_ekstrak_mengisi_jadwal_jam_dan_menandai_sesi_di_luar_jadwal(): void
    {
        $konteks = $this->fakeAi([
            'pengajar_umum' => [], 'nama_tidak_dikenali' => [], 'catatan' => [],
            'sesi' => [
                $this->sesiAi('2026-10-05'),                                   // Senin, AI tidak mengisi jadwal
                $this->sesiAi('2026-10-06', $this->jadwal['senin']),            // Selasa, AI salah menyebut jadwal Senin
                $this->sesiAi('2026-10-04', null, '08:00', '09:30'),            // Minggu
            ],
        ]);

        $hasil = app(AsistenAbsensiService::class)->ekstrak(Kelas::findOrFail($this->kelasId), 'laporan', [])['hasil'];
        $sesi  = collect($hasil['sesi'])->keyBy('tanggal');

        // Jadwal dikirim ke AI sebagai konteks
        $this->assertCount(5, $konteks['jadwal_kelas']);

        $this->assertSame($this->jadwal['senin'], $sesi['2026-10-05']['jadwal_id']);
        $this->assertSame('16:10', $sesi['2026-10-05']['jam_mulai']);
        $this->assertSame('17:00', $sesi['2026-10-05']['jam_selesai']);
        $this->assertFalse($sesi['2026-10-05']['di_luar_jadwal']);

        // jadwal_id yang salah hari dikoreksi ke jadwal Selasa
        $this->assertSame($this->jadwal['selasa'], $sesi['2026-10-06']['jadwal_id']);

        // Minggu: tanpa jadwal, jam dari laporan dipertahankan, ada peringatan
        $this->assertNull($sesi['2026-10-04']['jadwal_id']);
        $this->assertTrue($sesi['2026-10-04']['di_luar_jadwal']);
        $this->assertSame('08:00', $sesi['2026-10-04']['jam_mulai']);
        $this->assertNotEmpty($sesi['2026-10-04']['peringatan']);
    }

    public function test_ekstrak_menandai_tanggal_libur(): void
    {
        DB::table('libur')->insert([
            'kelas_id' => $this->kelasId, 'tanggal_mulai' => '2026-10-05', 'tanggal_selesai' => '2026-10-05',
            'keterangan' => 'Libur uji', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->fakeAi([
            'pengajar_umum' => [], 'nama_tidak_dikenali' => [], 'catatan' => [],
            'sesi' => [$this->sesiAi('2026-10-05')],
        ]);

        $sesi = app(AsistenAbsensiService::class)->ekstrak(Kelas::findOrFail($this->kelasId), 'laporan', [])['hasil']['sesi'][0];

        $this->assertSame('Libur uji', $sesi['libur']);
        $this->assertNull($sesi['jadwal_id']);
        $this->assertTrue($sesi['di_luar_jadwal']);
    }
}
