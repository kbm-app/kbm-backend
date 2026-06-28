<?php

namespace App\Http\Controllers;

use App\Services\Wa\WaServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class WaSettingsController extends Controller
{
    public function __construct(private WaServiceInterface $wa) {}

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    private function openwaHeaders(): array
    {
        return ['X-Api-Key' => config('wa.api_key')];
    }

    private function baseUrl(): string
    {
        return rtrim(config('wa.base_url'), '/');
    }

    /**
     * WA_SESSION_ID di .env adalah session *name*, bukan UUID.
     * OpenWA menyimpan name dan id terpisah — kita list semua session
     * lalu cari yang name-nya cocok untuk mendapat id yang sebenarnya.
     */
    private function resolveSessionId(): ?string
    {
        $response = Http::withHeaders($this->openwaHeaders())
            ->timeout(5)
            ->get($this->baseUrl() . '/api/sessions');

        if (! $response->successful()) return null;

        $session = collect($response->json())->firstWhere('name', config('wa.session_id'));

        return $session['id'] ?? null;
    }

    private function mapStatus(string $openwaStatus): string
    {
        return match ($openwaStatus) {
            'ready'          => 'connected',
            'authenticating' => 'initializing',
            'qr_ready'       => 'qr_ready',
            'initializing'   => 'initializing',
            'created',
            'failed',
            'disconnected'   => 'disconnected',
            default          => 'unknown',
        };
    }

    // ─── Endpoints ───────────────────────────────────────────────────────────────

    public function show(): JsonResponse
    {
        return response()->json([
            'provider'   => config('wa.provider'),
            'base_url'   => config('wa.base_url'),
            'session_id' => config('wa.session_id'),
        ]);
    }

    public function status(): JsonResponse
    {
        if (config('wa.provider') !== 'openwa') {
            return response()->json(['status' => 'disabled', 'phone' => null]);
        }

        try {
            $sessionId = $this->resolveSessionId();

            if (! $sessionId) {
                return response()->json(['status' => 'disconnected', 'phone' => null]);
            }

            $response = Http::withHeaders($this->openwaHeaders())
                ->timeout(5)
                ->get($this->baseUrl() . "/api/sessions/{$sessionId}");

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'status' => $this->mapStatus($data['status'] ?? 'unknown'),
                    'phone'  => $data['phone'] ?? null,
                ]);
            }

            return response()->json(['status' => 'disconnected', 'phone' => null]);
        } catch (\Throwable) {
            return response()->json(['status' => 'disconnected', 'phone' => null]);
        }
    }

    public function qr(): JsonResponse
    {
        if (config('wa.provider') !== 'openwa') {
            return response()->json(['message' => 'Provider bukan OpenWA.'], 422);
        }

        try {
            $sessionId = $this->resolveSessionId();

            if (! $sessionId) {
                return response()->json(['message' => 'Session tidak ditemukan.'], 404);
            }

            $response = Http::withHeaders($this->openwaHeaders())
                ->timeout(10)
                ->get($this->baseUrl() . "/api/sessions/{$sessionId}/qr");

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['message' => 'QR tidak tersedia.'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }

    public function reconnect(): JsonResponse
    {
        if (config('wa.provider') !== 'openwa') {
            return response()->json(['message' => 'Provider bukan OpenWA.'], 422);
        }

        try {
            $headers = $this->openwaHeaders();
            $baseUrl = $this->baseUrl();
            $name    = config('wa.session_id');

            // Cari session berdasarkan name
            $sessionId = $this->resolveSessionId();

            if (! $sessionId) {
                // Belum ada — buat baru dengan name dari config
                $create    = Http::withHeaders($headers)->timeout(5)->post("{$baseUrl}/api/sessions", ['name' => $name]);
                $sessionId = $create->json('id');
            }

            if (! $sessionId) {
                return response()->json(['message' => 'Gagal membuat session WA.'], 503);
            }

            Http::withHeaders($headers)->timeout(5)->post("{$baseUrl}/api/sessions/{$sessionId}/start");

            return response()->json(['message' => 'Session dimulai.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider'   => 'required|in:openwa,fonnte,null',
            'base_url'   => 'nullable|url',
            'api_key'    => 'nullable|string',
            'session_id' => 'nullable|string',
            'token'      => 'nullable|string',
        ]);

        $envPath = base_path('.env');
        $env     = file_get_contents($envPath);

        $replacements = [
            'WA_PROVIDER'   => $data['provider'],
            'WA_BASE_URL'   => $data['base_url'] ?? '',
            'WA_API_KEY'    => $data['api_key'] ?? '',
            'WA_SESSION_ID' => $data['session_id'] ?? '',
            'WA_TOKEN'      => $data['token'] ?? '',
        ];

        foreach ($replacements as $key => $value) {
            if (preg_match("/^{$key}=.*/m", $env)) {
                $env = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $env);
            } else {
                $env .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $env);
        Artisan::call('config:clear');

        return response()->json(['message' => 'Konfigurasi WA berhasil disimpan.']);
    }

    public function test(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->phone) {
            return response()->json([
                'berhasil' => false,
                'pesan'    => 'Nomor HP Anda belum diisi di profil.',
            ], 422);
        }

        $result = $this->wa->kirim($user->phone, '[KBM Masjid] Test koneksi WA berhasil!');

        return response()->json([
            'berhasil' => $result->berhasil,
            'pesan'    => $result->berhasil ? 'Pesan test berhasil dikirim.' : $result->errorMessage,
        ]);
    }
}
