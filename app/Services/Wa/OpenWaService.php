<?php

namespace App\Services\Wa;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenWaService implements WaServiceInterface
{
    private string $baseUrl;
    private string $apiKey;
    private string $sessionName;
    private ?string $resolvedSessionId = null;

    public function __construct()
    {
        $this->baseUrl     = rtrim(config('wa.base_url'), '/');
        $this->apiKey      = config('wa.api_key');
        $this->sessionName = config('wa.session_id'); // ini adalah name, bukan UUID id
    }

    public function kirim(string $nomor, string $pesan): WaResult
    {
        $nomor     = $this->normalizeNomor($nomor);
        $sessionId = $this->resolveSessionId();

        if (! $sessionId) {
            return WaResult::gagal($nomor, 'Session WA tidak ditemukan. Hubungkan perangkat dari halaman Pengaturan WA.');
        }

        try {
            $response = Http::withHeader('X-Api-Key', $this->apiKey)
                ->timeout(10)
                ->post("{$this->baseUrl}/api/sessions/{$sessionId}/messages/send-text", [
                    'chatId' => $nomor . '@c.us',
                    'text'   => $pesan,
                ]);

            if ($response->successful()) {
                return WaResult::sukses($nomor);
            }

            $errorData = $response->json('message') ?? $response->body();
            $error     = is_string($errorData) ? $errorData : json_encode($errorData);
            Log::warning('OpenWA kirim gagal', ['nomor' => $nomor, 'status' => $response->status(), 'error' => $error]);

            return WaResult::gagal($nomor, $error);
        } catch (\Throwable $e) {
            Log::error('OpenWA exception', ['nomor' => $nomor, 'error' => $e->getMessage()]);

            return WaResult::gagal($nomor, $e->getMessage());
        }
    }

    public function kirimBulk(array $nomors, string $pesan): array
    {
        $results = [];

        foreach ($nomors as $nomor) {
            $results[] = $this->kirim($nomor, $pesan);
            usleep(500_000); // 500ms delay antar pesan
        }

        return $results;
    }

    /**
     * WA_SESSION_ID di .env adalah session *name*, bukan UUID.
     * Resolve ke actual id dengan listing semua sessions.
     * Di-cache per instance untuk efisiensi kirimBulk.
     */
    private function resolveSessionId(): ?string
    {
        if ($this->resolvedSessionId !== null) {
            return $this->resolvedSessionId;
        }

        $response = Http::withHeader('X-Api-Key', $this->apiKey)
            ->timeout(5)
            ->get("{$this->baseUrl}/api/sessions");

        if (! $response->successful()) return null;

        $session = collect($response->json())->firstWhere('name', $this->sessionName);
        $this->resolvedSessionId = $session['id'] ?? null;

        return $this->resolvedSessionId;
    }

    private function normalizeNomor(string $nomor): string
    {
        $nomor = preg_replace('/\D/', '', $nomor);

        if (str_starts_with($nomor, '0')) {
            $nomor = '62' . substr($nomor, 1);
        }

        return $nomor;
    }
}
