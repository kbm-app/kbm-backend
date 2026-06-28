<?php

namespace App\Services\Wa;

use Illuminate\Support\Facades\Log;

class NullWaService implements WaServiceInterface
{
    public function kirim(string $nomor, string $pesan): WaResult
    {
        Log::info('[NullWaService] Pesan tidak dikirim (provider=null)', [
            'nomor' => $nomor,
            'pesan' => substr($pesan, 0, 100),
        ]);

        return WaResult::sukses($nomor);
    }

    public function kirimBulk(array $nomors, string $pesan): array
    {
        return array_map(fn ($nomor) => $this->kirim($nomor, $pesan), $nomors);
    }
}
