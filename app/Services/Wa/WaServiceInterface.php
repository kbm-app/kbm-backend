<?php

namespace App\Services\Wa;

interface WaServiceInterface
{
    public function kirim(string $nomor, string $pesan): WaResult;

    /** @return WaResult[] */
    public function kirimBulk(array $nomors, string $pesan): array;
}
