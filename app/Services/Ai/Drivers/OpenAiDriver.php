<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Contracts\AiDriverInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OpenAiDriver implements AiDriverInterface
{
    public function ekstrak(
        array $konteksData,
        ?string $teks,
        array $gambar,
        array $schema,
        string $systemPrompt,
        ?string $modelOverride = null,
        ?string $instruksi = null,
    ): array {
        $apiKey = config('services.openai.key');
        if (! $apiKey) {
            throw new HttpException(503, 'Asisten AI belum dikonfigurasi. Isi OPENAI_API_KEY di .env backend.');
        }

        $model = $modelOverride ?: config('services.openai.model', 'gpt-5-mini');
        $baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');

        $laporanText = trim((string) $teks) !== '' ? $teks : '(tidak ada teks — baca dari foto)';
        $isiPrompt = "Data referensi:\n" . json_encode($konteksData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if (! empty(trim((string) $instruksi))) {
            $isiPrompt .= "\n\n*** INSTRUKSI KHUSUS DARI SUPER ADMIN (PRIORITAS TERTINGGI) ***\n" . trim((string) $instruksi);
        }

        $isiPrompt .= "\n\nLaporan dari pengajar:\n" . $laporanText;

        $userContent = [
            [
                'type' => 'text',
                'text' => $isiPrompt,
            ],
        ];

        foreach ($gambar as $file) {
            $mime = $file->getMimeType();
            $base64 = base64_encode($file->getContent());
            $userContent[] = [
                'type'      => 'image_url',
                'image_url' => [
                    'url' => "data:{$mime};base64,{$base64}",
                ],
            ];
        }

        // Tentukan role sistem/developer: model seri 'o' (reasoning) menggunakan 'developer'
        $systemRole = (str_starts_with($model, 'o1') || str_starts_with($model, 'o3')) ? 'developer' : 'system';

        try {
            $response = Http::withToken($apiKey)
                ->timeout(180)
                ->post(rtrim($baseUrl, '/') . '/chat/completions', [
                    'model'    => $model,
                    'messages' => [
                        ['role' => $systemRole, 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userContent],
                    ],
                    'response_format' => [
                        'type'        => 'json_schema',
                        'json_schema' => [
                            'name'   => 'ekstrak_absensi',
                            'strict' => true,
                            'schema' => $schema,
                        ],
                    ],
                    'max_completion_tokens' => 16000,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Asisten AI (OpenAI): koneksi gagal', ['error' => $e->getMessage()]);
            throw new HttpException(503, 'Tidak bisa terhubung ke layanan OpenAI. Periksa koneksi server.');
        }

        if ($response->status() === 401) {
            Log::error('Asisten AI (OpenAI): API key ditolak', ['body' => $response->body()]);
            throw new HttpException(503, 'API key OpenAI tidak valid. Periksa OPENAI_API_KEY.');
        }

        if ($response->status() === 429) {
            $err = $response->json('error.message') ?? 'Rate limit atau kuota kredit tercapai';
            Log::warning('Asisten AI (OpenAI): 429 rate limit / quota', ['message' => $err]);
            throw new HttpException(429, 'Limit kuota atau rate limit OpenAI tercapai. Periksa saldo kredit akun atau coba lagi nanti.');
        }

        if (! $response->successful()) {
            $errorMsg = $response->json('error.message') ?? $response->body();
            Log::error('Asisten AI (OpenAI): error API', [
                'status' => $response->status(),
                'error'  => $errorMsg,
            ]);
            throw new HttpException(502, "Layanan OpenAI mengembalikan error: {$errorMsg}");
        }

        $rawJson = $response->json('choices.0.message.content');
        if (! $rawJson) {
            $finishReason = $response->json('choices.0.finish_reason');
            if ($finishReason === 'length') {
                throw new HttpException(422, 'Laporan terlalu panjang untuk diproses sekaligus. Pecah menjadi beberapa bagian.');
            }
            if ($finishReason === 'content_filter') {
                throw new HttpException(422, 'Laporan ditolak oleh filter konten OpenAI.');
            }
            Log::error('Asisten AI (OpenAI): respons kosong', ['response' => $response->json()]);
            throw new HttpException(502, 'Respons AI tidak berisi data yang diharapkan.');
        }

        $decoded = json_decode($rawJson, true);
        if (! is_array($decoded)) {
            Log::error('Asisten AI (OpenAI): gagal parse JSON', ['content' => $rawJson]);
            throw new HttpException(502, 'Format data AI tidak valid JSON. Coba lagi.');
        }

        return $decoded;
    }
}

