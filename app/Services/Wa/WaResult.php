<?php

namespace App\Services\Wa;

class WaResult
{
    public function __construct(
        public readonly string $nomor,
        public readonly bool $berhasil,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function sukses(string $nomor): self
    {
        return new self(nomor: $nomor, berhasil: true);
    }

    public static function gagal(string $nomor, string $errorMessage): self
    {
        return new self(nomor: $nomor, berhasil: false, errorMessage: $errorMessage);
    }
}
