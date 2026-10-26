<?php

namespace App\Services\ArabicName;

use App\Services\Ai\AiProviderService;
use App\Services\ArabicName\Contracts\ArabicNameGeneratorContract;
use Illuminate\Support\Facades\Log;

/**
 * Generator nama Arab berbasis AI (OpenAI-compatible).
 *
 * Provider diambil dari Pengaturan AI (multi-platform, berbayar/gratis/lokal).
 * Bila belum ada provider atau panggilan gagal, otomatis memakai kaidah
 * transliterasi (fallback) agar proses import/generate tidak gagal total.
 */
class AiArabicNameGenerator implements ArabicNameGeneratorContract
{
    public function __construct(
        private readonly ArabicNameGenerator $fallback,
        private readonly AiProviderService $ai,
    ) {}

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

        $provider = $this->ai->resolveProvider();

        if (! $provider) {
            return $this->fallback->generate($name);
        }

        try {
            $result = $this->ai->chat([
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user', 'content' => $name],
            ], ['temperature' => 0, 'max_tokens' => 80], $provider);

            if (! $result['ok']) {
                Log::warning('Generator nama Arab AI gagal: '.($result['error'] ?? '-'));

                return $this->fallback->generate($name);
            }

            $arabic = $this->sanitize($result['content']);

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

            // Perbaiki kesalahan umum: dua alif di awal kata harus menjadi alif maddah.
            $clean = preg_replace('/^اا+/u', 'آ', $clean) ?? $clean;

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
            .'Bedakan bunyi: س=s, ش=sy, ك=k, ق=q, ه=h ringan, ح=h tenggorokan, ذ=dz, ز=z, ف=f, ب=p. '
            .'Jangan menulis dua alif berturut-turut di awal kata (اا). Untuk bunyi /ā/ di awal gunakan آ; '
            .'contoh Aamily = آميلي, bukan ااميلي. '
            .'Bunyi /p/ Indonesia tidak ada padanan persis; gunakan ب, bukan ف; contoh Pradana = برادانا. '
            .'Bunyi /f/ tetap menggunakan ف. '
            .'Perhatikan vokal panjang (ا/و/ي) dan vokal pendek; jangan menduplikasi huruf vokal. '
            .'Gunakan ejaan baku untuk nama Arab (mis. Muhammad=محمد, Abdullah=عبد الله, Fatimah=فاطمة, Sofia=صوفيا). '
            .'Contoh lengkap: Aamily Sofia Qairina Pradana = آميلي صوفيا قايرينا برادانا. '
            .'Nama non-Arab ditulis fonetis dan tidak diterjemahkan maknanya. '
            .'Pisahkan setiap unsur nama (nama depan/tengah/keluarga) dengan spasi. '
            .'Balas HANYA teks Arab, tanpa penjelasan, tanpa tanda kutip, tanpa transliterasi Latin.';
    }
}
