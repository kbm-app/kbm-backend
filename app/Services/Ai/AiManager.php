<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiDriverInterface;
use App\Services\Ai\Drivers\AnthropicDriver;
use App\Services\Ai\Drivers\OpenAiDriver;
use InvalidArgumentException;

class AiManager
{
    /**
     * Dapatkan driver AI berdasarkan provider yang dipilih atau dari config default.
     */
    public function driver(?string $provider = null): AiDriverInterface
    {
        $namaProvider = strtolower(trim($provider ?: (string) config('services.ai.provider', 'openai')));

        return match ($namaProvider) {
            'openai'    => app(OpenAiDriver::class),
            'anthropic' => app(AnthropicDriver::class),
            default     => throw new InvalidArgumentException("Provider AI '{$namaProvider}' tidak didukung. Pilihan yang tersedia: openai, anthropic."),
        };
    }
}

