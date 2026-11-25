<?php

namespace App\Jobs;

use App\Mail\ReminderAbsensiMail;
use App\Services\ReminderAbsensiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email pengingat ke pengajar aktif yang masih punya sesi belum diabsen.
 * Pengajar yang semua sesinya sudah diabsen tidak dikirimi apa pun.
 */
class KirimReminderAbsensiPengajarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Zona waktu operasional KBM (GMT+8); menentukan "hari ini" dan jam selesai sesi. */
    public const ZONA_WAKTU = 'Asia/Makassar';

    public function handle(ReminderAbsensiService $service): void
    {
        $penerima = $service->belumDiabsen(now(self::ZONA_WAKTU));

        foreach ($penerima as $p) {
            try {
                Mail::to($p['email'])->send(new ReminderAbsensiMail($p['nama'], $p['sesi']));
            } catch (\Throwable $e) {
                // Satu kegagalan tidak boleh menghentikan pengiriman ke pengajar lain
                Log::error('KirimReminderAbsensiPengajarJob gagal', [
                    'pengajar_id' => $p['pengajar']->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        Log::info('Reminder absensi pengajar diproses', ['jumlah_penerima' => $penerima->count()]);
    }
}
