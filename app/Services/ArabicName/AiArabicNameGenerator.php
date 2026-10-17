<?php

namespace App\Services\ArabicName;

use App\Services\ArabicName\Contracts\ArabicNameGeneratorContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generator nama Arab berbasis AI (OpenAI-compatible Chat Completions).
 *
 * Bersifat opsional: bila kredensial tidak tersedia atau panggilan gagal,
 * otomatis memakai generator kaidah transliterasi (fallback) agar proses
 * import/generate tidak pernah gagal total.
 *
 * Prompt sistem memuat kaidah penulisan nama:
 *  - berdasarkan pelafalan, bukan penggantian huruf satu-per-satu;
 *  - bedakan س/ش، ك/ق، ه/ح، ذ/ز;
 *  - vokal panjang/pendek; harakat opsional;
 *  - ejaan baku nama Arab; nama non-Arab fonetis tanpa terjemahan;
 *  - pisahkan unsur nama.
 */
class AiArabicNameGenerator implements ArabicNameGeneratorContract
{
    public function __construct(private readonly ArabicNameGenerator $fallback) {}

    public function driver(): string
    {
        return 'ai';
    }

    public function generate(?string $latinName): string
    {
        $name = trim((string) $latinName);

        if ($name === '') {
            return '';
        }

        $key = (string) config('arabic_names.ai.key');
        $endpoint = (string) config('arabic_names.ai.endpoint');

        if ($key === '' || $endpoint === '') {
            return $this->fallback->generate($name);
        }

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->timeout((int) config('arabic_names.ai.timeout', 20))
                ->post($endpoint, [
                    'model' => config('arabic_names.ai.model'),
                    'temperature' => 0,
                    'max_tokens' => 80,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $name],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Generator nama Arab AI gagal (HTTP '.$response->status().').');

                return $this->fallback->generate($name);
            }

            $content = (string) data_get($response->json(), 'choices.0.message.content', '');
            $arabic = $this->sanitize($content);

            return $arabic !== '' ? $arabic : $this->fallback->generate($name);
        } catch (\Throwable $e) {
            Log::warning('Generator nama Arab AI exception: '.$e->getMessage());

            return $this->fallback->generate($name);
        }
    }

    /**
     * Ambil hanya teks Arab dari respons (buang penjelasan/kutipan/Latin).
     */
    private function sanitize(string $content): string
    {
        foreach (preg_split('/\R/u', trim($content)) ?: [] as $line) {
            $line = trim($line, " \t\n\r\0\x0B\"'`*-");

            if ($line === '' || ! preg_match('/[\x{0600}-\x{06FF}]/u', $line)) {
                continue;
            }

            $clean = preg_replace('/[^\x{0600}-\x{06FF}\s\-]/u', ' ', $line) ?? '';
            $clean = trim((string) preg_replace('/\s+/u', ' ', $clean));

            if ($clean !== '') {
                return $clean;
            }
        }

        return '';
    }

    private function systemPrompt(): string
    {
        return 'Anda ahli transliterasi nama orang Indonesia ke huruf Arab. '
            .'Tulis berdasarkan PELAFALAN nama, bukan mengganti huruf Latin satu per satu. '
            .'Bedakan bunyi: س=s, ش=sy, ك=k, ق=q, ه=h ringan, ح=h tenggorokan, ذ=dz, ز=z. '
            .'Perhatikan vokal panjang (ا/و/ي) dan vokal pendek; harakat opsional. '
            .'Gunakan ejaan baku untuk nama Arab (mis. Muhammad=محمد, Abdullah=عبد الله, Fatimah=فاطمة). '
            .'Nama non-Arab ditulis fonetis dan tidak diterjemahkan maknanya. '
            .'Pisahkan setiap unsur nama (nama depan/tengah/keluarga) dengan spasi. '
            .'Balas HANYA teks Arab, tanpa penjelasan, tanpa tanda kutip, tanpa transliterasi Latin.';
    }
}
