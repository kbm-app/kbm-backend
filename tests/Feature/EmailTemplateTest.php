<?php

namespace Tests\Feature;

use App\Mail\ReminderAbsensiMail;
use App\Mail\ResetPasswordMail;
use App\Mail\SetPasswordMail;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

/** Ketiga email memakai layout bersama (emails.layout) dan warna tema aplikasi. */
class EmailTemplateTest extends TestCase
{
    private function user(): User
    {
        return new User(['name' => 'Umi Salamah', 'email' => 'umi@example.com']);
    }

    private function assertMemakaiLayoutBersama(string $html): void
    {
        $this->assertStringContainsString('#059669', $html, 'tombol/garis kartu harus memakai Emerald 600 (primary aplikasi)');
        $this->assertStringContainsString('border-top: 4px solid #059669', $html);
        $this->assertStringContainsString(config('app.name'), $html);
        $this->assertStringNotContainsString('#2563eb', $html, 'warna biru lama tidak boleh tersisa');
    }

    public function test_set_password(): void
    {
        $html = (new SetPasswordMail($this->user(), 'tok123'))->render();

        $this->assertMemakaiLayoutBersama($html);
        $this->assertStringContainsString('Atur Password Akun Anda', $html);
        $this->assertStringContainsString('Halo <strong', $html);
        $this->assertStringContainsString('Umi Salamah', $html);
        $this->assertStringContainsString('/set-password?token=tok123&amp;email=umi%40example.com', $html);
        $this->assertStringContainsString('berlaku selama 60 menit', $html);
    }

    public function test_reset_password(): void
    {
        $html = (new ResetPasswordMail($this->user(), 'tok456'))->render();

        $this->assertMemakaiLayoutBersama($html);
        $this->assertStringContainsString('Reset Password Akun Anda', $html);
        $this->assertStringContainsString('Atur Password Baru', $html);
        $this->assertStringContainsString('/set-password?token=tok456&amp;email=umi%40example.com', $html);
        $this->assertStringContainsString('password Anda tidak akan berubah', $html);
    }

    public function test_reminder_absensi(): void
    {
        $sesi = collect([
            ['tanggal' => Carbon::parse('2026-11-23'), 'kelas' => 'Kelas 3-2', 'program' => 'Pengajian Rutin',
             'jam_mulai' => '16:10', 'jam_selesai' => '17:00', 'sudah_dibuka' => false],
            ['tanggal' => Carbon::parse('2026-11-24'), 'kelas' => 'Kelas 4', 'program' => 'Pengajian Rutin',
             'jam_mulai' => '17:00', 'jam_selesai' => '17:50', 'sudah_dibuka' => true],
        ]);
        $html = (new ReminderAbsensiMail('Umi Salamah', $sesi))->render();

        $this->assertMemakaiLayoutBersama($html);
        $this->assertStringContainsString('2 sesi belum diabsen', $html);
        $this->assertStringContainsString('Tersebar di 2 hari', $html);
        $this->assertStringContainsString('Senin, 23 November 2026', $html);
        $this->assertStringContainsString('Selasa, 24 November 2026', $html);
        $this->assertStringContainsString('Sesi dibuka, belum diselesaikan', $html);
        $this->assertStringContainsString('Buka Aplikasi', $html);
        $this->assertStringContainsString('dikirim setiap hari pukul 19:00 WITA', $html);
    }
}
