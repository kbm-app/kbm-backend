<?php

namespace App\Listeners;

use App\Events\PertemuanSelesai;
use App\Models\WaLog;
use App\Services\Wa\WaMessageBuilder;
use App\Services\Wa\WaServiceInterface;
use Illuminate\Support\Facades\Log;

class KirimNotifikasiWaliMurid
{
    public function __construct(private WaServiceInterface $wa) {}

    public function handle(PertemuanSelesai $event): void
    {
        $pertemuan = $event->pertemuan;

        $muridAlpha = $pertemuan->absensiMurid()
            ->where('status', 'alpha')
            ->with(['murid.waliMurid'])
            ->get();

        if ($muridAlpha->isEmpty()) {
            return;
        }

        $tanggal     = $pertemuan->created_at->translatedFormat('d F Y');
        $namaKelas   = $pertemuan->kelas->nama ?? '-';
        $namaProgram = $pertemuan->program->nama ?? '-';

        foreach ($muridAlpha as $absensi) {
            $murid = $absensi->murid;
            if (! $murid) {
                continue;
            }

            $waliPrimary = $murid->waliMurid
                ->where('is_primary', true)
                ->first();

            if (! $waliPrimary || ! $waliPrimary->phone) {
                continue;
            }

            $pesan = WaMessageBuilder::absensiAlpha(
                namaMurid:   $murid->nama,
                namaKelas:   $namaKelas,
                namaProgram: $namaProgram,
                tanggal:     $tanggal,
            );

            try {
                $result = $this->wa->kirim($waliPrimary->phone, $pesan);

                WaLog::create([
                    'tipe'          => 'absensi',
                    'referensi_id'  => $pertemuan->id,
                    'nomor_tujuan'  => $waliPrimary->phone,
                    'nama_penerima' => $waliPrimary->nama,
                    'pesan'         => $pesan,
                    'status'        => $result->berhasil ? 'terkirim' : 'gagal',
                    'error_message' => $result->errorMessage,
                ]);
            } catch (\Throwable $e) {
                Log::error('KirimNotifikasiWaliMurid gagal', [
                    'pertemuan_id' => $pertemuan->id,
                    'murid_id'     => $murid->id,
                    'error'        => $e->getMessage(),
                ]);
            }
        }
    }
}
