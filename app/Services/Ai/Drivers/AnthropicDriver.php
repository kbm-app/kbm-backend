<?php

namespace App\Services\Ai\Drivers;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Services\Ai\Contracts\AiDriverInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AnthropicDriver implements AiDriverInterface
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
        $apiKey = config('services.anthropic.key');
        if (! $apiKey) {
            throw new HttpException(503, 'Asisten AI belum dikonfigurasi. Isi ANTHROPIC_API_KEY di .env backend.');
        }

        $model = $modelOverride ?: config('services.anthropic.model', 'claude-opus-5-5');
        $client = new Client(apiKey: $apiKey);

        $content = [];
        foreach ($gambar as $file) {
            $content[] = [
                'type'   => 'image',
                'source' => [
                    'type'      => 'base64',
                    'mediaType' => $file->getMimeType(),
                    'data'      => base64_encode($file->getContent()),
                ],
            ];
        }

        $laporanText = trim((string) $teks) !== '' ? $teks : '(tidak ada teks — baca dari foto)';
        $isiPrompt = "Data referensi:\n" . json_encode($konteksData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if (! empty(trim((string) $instruksi))) {
            $isiPrompt .= "\n\n*** INSTRUKSI KHUSUS DARI SUPER ADMIN (PRIORITAS TERTINGGI) ***\n" . trim((string) $instruksi);
        }

        $isiPrompt .= "\n\nLaporan dari pengajar:\n" . $laporanText;

        $content[] = [
            'type' => 'text',
            'text' => $isiPrompt,
        ];

        try {
            $message = $client->beta->messages->create(
                model: $model,
                maxTokens: 16000,
                system: $systemPrompt,
                messages: [['role' => 'user', 'content' => $content]],
                outputConfig: [
                    'effort' => 'medium',
                    'format' => ['type' => 'json_schema', 'schema' => $schema],
                ],
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
            );
        } catch (AuthenticationException $e) {
            Log::error('Asisten AI (Anthropic): API key ditolak', ['error' => $e->getMessage()]);
            throw new HttpException(503, 'API key Anthropic tidak valid. Periksa ANTHROPIC_API_KEY.');
        } catch (RateLimitException $e) {
            throw new HttpException(429, 'Asisten AI sedang sibuk. Coba lagi sebentar lagi.');
        } catch (APIConnectionException $e) {
            Log::error('Asisten AI (Anthropic): koneksi gagal', ['error' => $e->getMessage()]);
            throw new HttpException(503, 'Tidak bisa terhubung ke layanan AI. Periksa koneksi server.');
        } catch (APIStatusException $e) {
            Log::error('Asisten AI (Anthropic): error API', ['error' => $e->getMessage()]);
            throw new HttpException(502, 'Layanan AI mengembalikan error. Coba lagi.');
        }

        if ($message->stopReason === 'refusal') {
            throw new HttpException(422, 'AI tidak dapat memproses laporan ini. Silakan isi absensi secara manual.');
        }
        if ($message->stopReason === 'max_tokens') {
            throw new HttpException(422, 'Laporan terlalu panjang untuk diproses sekaligus. Pecah menjadi beberapa bagian.');
        }

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $data = json_decode($block->text, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        Log::error('Asisten AI (Anthropic): respons tidak berisi JSON', ['stop_reason' => $message->stopReason]);
        throw new HttpException(502, 'Respons AI tidak bisa dibaca. Coba lagi.');
    }
}

