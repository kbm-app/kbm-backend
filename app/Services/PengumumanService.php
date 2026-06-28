<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Kelas;
use App\Models\Pengumuman;
use App\Models\User;
use App\Models\WaLog;
use App\Services\Wa\WaMessageBuilder;
use App\Services\Wa\WaServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PengumumanService
{
    public function __construct(private WaServiceInterface $wa) {}

    public function kirim(Pengumuman $pengumuman): void
    {
        $penerima = $this->getPenerima($pengumuman);

        if ($penerima->isEmpty()) {
            return;
        }

        $pesan   = WaMessageBuilder::pengumuman($pengumuman->judul, $pengumuman->konten);
        $berhasil = 0;

        foreach ($penerima as $user) {
            $nomor = $user->phone;
            if (! $nomor) {
                continue;
            }

            try {
                $result = $this->wa->kirim($nomor, $pesan);

                WaLog::create([
                    'tipe'          => 'pengumuman',
                    'referensi_id'  => $pengumuman->id,
                    'nomor_tujuan'  => $nomor,
                    'nama_penerima' => $user->name,
                    'pesan'         => $pesan,
                    'status'        => $result->berhasil ? 'terkirim' : 'gagal',
                    'error_message' => $result->errorMessage,
                ]);

                if ($result->berhasil) {
                    $berhasil++;
                }
            } catch (\Throwable $e) {
                Log::error('PengumumanService kirim gagal', [
                    'pengumuman_id' => $pengumuman->id,
                    'user_id'       => $user->id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        $pengumuman->update([
            'terkirim_at'     => now(),
            'jumlah_penerima' => $berhasil,
        ]);
    }

    public function getPenerima(Pengumuman $pengumuman): Collection
    {
        return match ($pengumuman->target) {
            'semua'          => User::where('is_active', true)->whereNotNull('phone')->get(),
            'murid'          => User::where('is_active', true)->where('role', UserRole::Murid)->whereNotNull('phone')->get(),
            'wali_murid'     => User::where('is_active', true)->where('role', UserRole::WaliMurid)->whereNotNull('phone')->get(),
            'pengajar'       => User::where('is_active', true)->where('role', UserRole::Pengajar)->whereNotNull('phone')->get(),
            'kelas_tertentu' => $this->penerimaKelas($pengumuman->kelas_id),
            default          => collect(),
        };
    }

    private function penerimaKelas(?int $kelasId): Collection
    {
        if (! $kelasId) {
            return collect();
        }

        $kelas = Kelas::with(['murid.waliMurid.user', 'pengajar.user'])->find($kelasId);
        if (! $kelas) {
            return collect();
        }

        $userIds = collect();

        // wali murid primary dari setiap murid aktif di kelas
        foreach ($kelas->murid as $murid) {
            $wali = $murid->waliMurid->where('is_primary', true)->first();
            if ($wali?->user_id) {
                $userIds->push($wali->user_id);
            }
        }

        // pengajar yang mengajar di kelas ini
        foreach ($kelas->pengajar as $pengajar) {
            if ($pengajar->user_id) {
                $userIds->push($pengajar->user_id);
            }
        }

        return User::whereIn('id', $userIds->unique())
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->get();
    }
}
