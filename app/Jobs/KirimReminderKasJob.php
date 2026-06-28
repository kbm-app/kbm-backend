<?php

namespace App\Jobs;

use App\Models\Kelas;
use App\Models\MuridKelas;
use App\Models\WaLog;
use App\Services\Wa\WaMessageBuilder;
use App\Services\Wa\WaServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class KirimReminderKasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(WaServiceInterface $wa): void
    {
        $bulan  = now()->locale('id')->translatedFormat('F Y');
        $kelas  = Kelas::where('is_aktif', true)->get();

        foreach ($kelas as $satuKelas) {
            $pesan = WaMessageBuilder::reminderKas(
                namaKelas: $satuKelas->nama,
                bulan:     $bulan,
            );

            $muridKelas = MuridKelas::where('kelas_id', $satuKelas->id)
                ->where('status', 'aktif')
                ->whereNull('tanggal_keluar')
                ->with(['murid.waliMurid'])
                ->get();

            $nomorTerkirim = collect();

            foreach ($muridKelas as $mk) {
                $wali  = $mk->murid?->waliMurid?->where('is_primary', true)->first();
                $nomor = $wali?->phone;
                $nama  = $wali?->nama ?? '-';

                if (! $nomor || $nomorTerkirim->contains($nomor)) {
                    continue;
                }

                try {
                    $result = $wa->kirim($nomor, $pesan);

                    WaLog::create([
                        'tipe'          => 'kas',
                        'referensi_id'  => $satuKelas->id,
                        'nomor_tujuan'  => $nomor,
                        'nama_penerima' => $nama,
                        'pesan'         => $pesan,
                        'status'        => $result->berhasil ? 'terkirim' : 'gagal',
                        'error_message' => $result->errorMessage,
                    ]);

                    $nomorTerkirim->push($nomor);
                } catch (\Throwable $e) {
                    Log::error('KirimReminderKasJob gagal', ['kelas_id' => $satuKelas->id, 'nomor' => $nomor, 'error' => $e->getMessage()]);
                }
            }
        }
    }
}
