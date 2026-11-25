<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\KirimReminderAbsensiPengajarJob;
use App\Mail\ReminderAbsensiMail;
use App\Models\User;
use App\Services\ReminderAbsensiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Migrasi memakai SQL khusus PostgreSQL, jadi tes ini harus jalan di PostgreSQL
 * (mis. DB_CONNECTION=pgsql DB_DATABASE=kbm_testing).
 */
class ReminderAbsensiPengajarTest extends TestCase
{
    use RefreshDatabase;

    private int $kelasId;
    private int $programId;
    private int $pengajarId;
    private int $jadwalId;

    protected function setUp(): void
    {
        parent::setUp();

        $now = now();

        $user = User::factory()->create(['role' => UserRole::Pengajar, 'email' => 'ustadz@example.com']);
        $this->programId = DB::table('program')->insertGetId([
            'nama' => 'Pengajian Rutin', 'jenis' => 'pengajian_rutin', 'is_aktif' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->kelasId = DB::table('kelas')->insertGetId([
            'nama' => 'Kelas 5', 'is_aktif' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->pengajarId = DB::table('pengajar')->insertGetId([
            'user_id' => $user->id, 'jenis_kelamin' => 'L', 'tanggal_bergabung' => '2026-01-01',
            'is_aktif' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // Setiap Senin 16:10–17:00 sejak 2026-09-01
        $this->jadwalId = DB::table('jadwal')->insertGetId([
            'program_id' => $this->programId, 'kelas_id' => $this->kelasId, 'frekuensi' => 'mingguan',
            'hari' => 'senin', 'jam_mulai' => '16:10', 'jam_selesai' => '17:00',
            'mulai_berlaku' => '2026-09-01', 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('jadwal_pengajar')->insert([
            'jadwal_id' => $this->jadwalId, 'pengajar_id' => $this->pengajarId, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /** Selasa 2026-11-24 pukul 19:00 WITA. Jendela 14 hari mundur mulai 10 Nov, jadi Senin yang relevan: 16 dan 23 Nov. */
    private function sekarang(): Carbon
    {
        return Carbon::parse('2026-11-24 19:00', 'Asia/Makassar');
    }

    private function buatPertemuan(string $tanggal, string $status, ?int $jadwalId = null): int
    {
        return DB::table('pertemuan')->insertGetId([
            'jadwal_id' => $jadwalId ?? $this->jadwalId, 'program_id' => $this->programId, 'kelas_id' => $this->kelasId,
            'pengajar_id' => $this->pengajarId, 'tanggal' => $tanggal, 'jam_mulai' => '16:10', 'jam_selesai' => '17:00',
            'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function tanggalBelumDiabsen(): array
    {
        $hasil = app(ReminderAbsensiService::class)->belumDiabsen($this->sekarang());

        return $hasil->get($this->pengajarId)['sesi']->map(fn ($s) => $s['tanggal']->toDateString())->all() ?? [];
    }

    public function test_sesi_tanpa_pertemuan_ditagih_per_tanggal(): void
    {
        $this->assertSame(['2026-11-16', '2026-11-23'], $this->tanggalBelumDiabsen());
    }

    public function test_sesi_selesai_atau_batal_tidak_ditagih(): void
    {
        $this->buatPertemuan('2026-11-16', 'selesai');
        $this->buatPertemuan('2026-11-23', 'batal');

        $this->assertNull(app(ReminderAbsensiService::class)->belumDiabsen($this->sekarang())->get($this->pengajarId));
    }

    public function test_sesi_berlangsung_tetap_ditagih(): void
    {
        $this->buatPertemuan('2026-11-16', 'selesai');
        $this->buatPertemuan('2026-11-23', 'berlangsung');

        $this->assertSame(['2026-11-23'], $this->tanggalBelumDiabsen());
    }

    public function test_sesi_tanpa_jadwal_id_di_kelas_dan_program_sama_dianggap_beres(): void
    {
        DB::table('pertemuan')->insert([
            'jadwal_id' => null, 'program_id' => $this->programId, 'kelas_id' => $this->kelasId,
            'pengajar_id' => $this->pengajarId, 'tanggal' => '2026-11-16', 'jam_mulai' => '16:10',
            'status' => 'selesai', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame(['2026-11-23'], $this->tanggalBelumDiabsen());
    }

    public function test_libur_tidak_ditagih(): void
    {
        DB::table('libur')->insert([
            'kelas_id' => null, 'jadwal_id' => null, 'tanggal_mulai' => '2026-11-16', 'tanggal_selesai' => '2026-11-22',
            'keterangan' => 'Libur', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame(['2026-11-23'], $this->tanggalBelumDiabsen());
    }

    public function test_sesi_hari_ini_baru_ditagih_setelah_jam_selesai(): void
    {
        DB::table('jadwal')->where('id', $this->jadwalId)->update(['hari' => 'selasa', 'jam_selesai' => '20:00']);
        $this->assertNotContains('2026-11-24', $this->tanggalBelumDiabsen());

        DB::table('jadwal')->where('id', $this->jadwalId)->update(['jam_selesai' => '18:30']);
        $this->assertContains('2026-11-24', $this->tanggalBelumDiabsen());
    }

    public function test_jadwal_bulanan_hanya_di_minggu_yang_cocok(): void
    {
        // Senin minggu ke-4 (22–28) → hanya 23 Nov
        DB::table('jadwal')->where('id', $this->jadwalId)->update(['frekuensi' => 'bulanan', 'minggu_ke' => 4]);

        $this->assertSame(['2026-11-23'], $this->tanggalBelumDiabsen());
    }

    public function test_pengajar_nonaktif_atau_tanpa_email_dilewati(): void
    {
        DB::table('pengajar')->where('id', $this->pengajarId)->update(['is_aktif' => false]);
        $this->assertNull(app(ReminderAbsensiService::class)->belumDiabsen($this->sekarang())->get($this->pengajarId));

        DB::table('pengajar')->where('id', $this->pengajarId)->update(['is_aktif' => true]);
        DB::table('users')->where('email', 'ustadz@example.com')->update(['email' => '']);
        $this->assertNull(app(ReminderAbsensiService::class)->belumDiabsen($this->sekarang())->get($this->pengajarId));
    }

    public function test_job_mengirim_satu_email_hanya_ke_pengajar_yang_belum_absen(): void
    {
        Mail::fake();
        $this->travelTo($this->sekarang());

        // Pengajar kedua: semua sesinya sudah diabsen
        $user2 = User::factory()->create(['role' => UserRole::Pengajar, 'email' => 'rapi@example.com']);
        $pengajar2 = DB::table('pengajar')->insertGetId([
            'user_id' => $user2->id, 'jenis_kelamin' => 'L', 'tanggal_bergabung' => '2026-01-01',
            'is_aktif' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $kelas2 = DB::table('kelas')->insertGetId(['nama' => 'Kelas 6', 'is_aktif' => true, 'created_at' => now(), 'updated_at' => now()]);
        $jadwal2 = DB::table('jadwal')->insertGetId([
            'program_id' => $this->programId, 'kelas_id' => $kelas2, 'frekuensi' => 'mingguan',
            'hari' => 'senin', 'jam_mulai' => '16:10', 'jam_selesai' => '17:00',
            'mulai_berlaku' => '2026-09-01', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('jadwal_pengajar')->insert(['jadwal_id' => $jadwal2, 'pengajar_id' => $pengajar2, 'created_at' => now(), 'updated_at' => now()]);
        foreach (['2026-11-16', '2026-11-23'] as $tgl) {
            DB::table('pertemuan')->insert([
                'jadwal_id' => $jadwal2, 'program_id' => $this->programId, 'kelas_id' => $kelas2, 'pengajar_id' => $pengajar2,
                'tanggal' => $tgl, 'jam_mulai' => '16:10', 'status' => 'selesai', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        (new KirimReminderAbsensiPengajarJob)->handle(app(ReminderAbsensiService::class));

        Mail::assertSentCount(1);
        Mail::assertSent(ReminderAbsensiMail::class, fn ($m) => $m->hasTo('ustadz@example.com')
            && $m->sesiPerTanggal->keys()->all() === ['2026-11-16', '2026-11-23']);
    }
}
