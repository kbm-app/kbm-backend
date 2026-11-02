<?php

namespace App\Services\Ai\Contracts;

use Illuminate\Http\UploadedFile;

interface AiDriverInterface
{
    /**
     * @param  array<string, mixed>  $konteksData
     * @param  string|null            $teks
     * @param  UploadedFile[]         $gambar
     * @param  array<string, mixed>  $schema
     * @param  string                 $systemPrompt
     * @param  string|null            $modelOverride
     * @return array<string, mixed>
     */
    public function ekstrak(
        array $konteksData,
        ?string $teks,
        array $gambar,
        array $schema,
        string $systemPrompt,
        ?string $modelOverride = null,
    ): array;
}

