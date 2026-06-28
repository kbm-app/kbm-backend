<?php

use App\Jobs\KirimReminderJadwalJob;
use App\Jobs\KirimReminderKasJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminder jadwal: setiap hari pukul 07:00
Schedule::job(new KirimReminderJadwalJob)->dailyAt('07:00');

// Reminder kas/shodaqoh: tanggal 1 dan 15 setiap bulan pukul 08:00
Schedule::job(new KirimReminderKasJob)->twiceMonthly(1, 15, '08:00');
