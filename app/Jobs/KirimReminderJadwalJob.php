<?php

namespace App\Jobs;

use App\Models\Jadwal;
use App\Models\MuridKelas;
use App\Models\WaLog;
use App\Models\WaliMurid;
use App\Services\Wa\WaMessageBuilder;
use App\Services\Wa\WaServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class KirimReminderJadwalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(WaServiceInterface $wa): void
    {
        $hariIni  = strtolower(now()->locale('id')->dayName);
        $jadwals  = Jadwal::aktif()->where('hari', $hariIni)->with(['program', 'kelas'])->get();

        if ($jadwals->isEmpty()) {
            return;
        }

        foreach ($jadwals as $jadwal) {
            if (! $jadwal->kelas_id) {
                continue;
            }

            $pesan = WaMessageBuilder::reminderJadwal(
                namaProgram: $jadwal->program->nama ?? '-',
                namaKelas:   $jadwal->kelas->nama ?? '-',
                hari:        ucfirst($hariIni),
                jamMulai:    $jadwal->jam_mulai,
            );

            $nomorTerkirim = collect();

            // kirim ke pengajar kelas
            $pengajarUsers = $jadwal->kelas->pengajarAktif ?? collect();
            foreach ($pengajarUsers as $kelasGuru) {
                $nomor = $kelasGuru->pengajar?->user?->phone;
                $nama  = $kelasGuru->pengajar?->user?->name ?? '-';
                if ($nomor && ! $nomorTerkirim->contains($nomor)) {
                    $this->kirimDanLog($wa, $jadwal->id, $nomor, $nama, $pesan);
                    $nomorTerkirim->push($nomor);
                }
            }

            // kirim ke wali murid aktif di kelas
            $muridKelas = MuridKelas::where('kelas_id', $jadwal->kelas_id)
                ->where('status', 'aktif')
                ->whereNull('tanggal_keluar')
                ->with(['murid.waliMurid'])
                ->get();

            foreach ($muridKelas as $mk) {
                $wali  = $mk->murid?->waliMurid?->where('is_primary', true)->first();
                $nomor = $wali?->phone;
                $nama  = $wali?->nama ?? '-';
                if ($nomor && ! $nomorTerkirim->contains($nomor)) {
                    $this->kirimDanLog($wa, $jadwal->id, $nomor, $nama, $pesan);
                    $nomorTerkirim->push($nomor);
                }
            }
        }
    }

    private function kirimDanLog(WaServiceInterface $wa, int $jadwalId, string $nomor, string $nama, string $pesan): void
    {
        try {
            $result = $wa->kirim($nomor, $pesan);

            WaLog::create([
                'tipe'          => 'jadwal',
                'referensi_id'  => $jadwalId,
                'nomor_tujuan'  => $nomor,
                'nama_penerima' => $nama,
                'pesan'         => $pesan,
                'status'        => $result->berhasil ? 'terkirim' : 'gagal',
                'error_message' => $result->errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::error('KirimReminderJadwalJob gagal', ['jadwal_id' => $jadwalId, 'nomor' => $nomor, 'error' => $e->getMessage()]);
        }
    }
}
