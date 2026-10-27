<?php

namespace App\Services\Ai;

use App\Models\AiProviderSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Layanan AI generik (Chat Completions, OpenAI-compatible).
 *
 * Dipakai generator nama Arab saat ini dan dapat dipakai modul lain
 * (RPM, soal, analisis) dengan memanggil `chat()`.
 * Urutan provider: default DB → provider aktif pertama → fallback config env.
 */
class AiProviderService
{
    public function isConfigured(): bool
    {
        try {
            return Schema::hasTable('ai_provider_settings')
                && AiProviderSetting::query()->where('is_active', true)->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public function defaultProvider(): ?AiProviderSetting
    {
        try {
            if (! Schema::hasTable('ai_provider_settings')) {
                return $this->transientDeepSeekProvider();
            }

            return AiProviderSetting::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->first() ?: $this->transientDeepSeekProvider();
        } catch (\Throwable) {
            return $this->transientDeepSeekProvider();
        }
    }

    /**
     * Provider sesuai kualitas: flash (cepat) atau pro (analisis berat).
     */
    public function providerForQuality(string $quality = 'flash'): ?AiProviderSetting
    {
        return $this->providersForQuality($quality)[0] ?? null;
    }

    /**
     * Provider aktif dari Pengaturan AI. Provider default selalu didahulukan;
     * provider aktif lain menjadi cadangan. ENV hanya dipakai bila tabel
     * provider belum berisi provider aktif.
     *
     * @return array<int, AiProviderSetting>
     */
    public function providersForQuality(string $quality = 'flash'): array
    {
        try {
            if (! Schema::hasTable('ai_provider_settings')) {
                return array_values(array_filter([$this->transientDeepSeekProvider()]));
            }

            $providers = AiProviderSetting::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get()
                ->unique('id')
                ->values();

            if ($providers->isNotEmpty()) {
                return $providers->all();
            }

            return array_values(array_filter([$this->transientDeepSeekProvider()]));
        } catch (\Throwable) {
            return array_values(array_filter([$this->transientDeepSeekProvider()]));
        }
    }

    /**
     * Provider sementara dari ENV jika DB belum dikonfigurasi.
     */
    public function transientDeepSeekProvider(?string $model = null): ?AiProviderSetting
    {
        $endpoint = (string) config('ai.deepseek.endpoint', '');
        $key = (string) config('ai.deepseek.api_key', '');

        if ($endpoint === '' || $key === '') {
            return null;
        }

        $provider = new AiProviderSetting([
            'name' => 'DeepSeek ENV',
            'provider' => 'deepseek',
            'endpoint' => $endpoint,
            'model' => $model ?: (string) config('ai.models.flash', 'deepseek-flash'),
            'is_active' => true,
            'is_default' => true,
        ]);
        $provider->api_key = $key;

        return $provider;
    }

    /**
     * Estimasi biaya berdasarkan token (USD), memakai config/ai.php.
     *
     * @param  array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int}  $usage
     */
    public function estimateCost(AiProviderSetting $provider, array $usage): float
    {
        $pricing = config('ai.pricing.'.$provider->model);
        if (! is_array($pricing)) {
            return 0.0;
        }

        $input = (int) ($usage['prompt_tokens'] ?? 0);
        $output = (int) ($usage['completion_tokens'] ?? 0);

        return round(
            ($input / 1_000_000) * (float) ($pricing['input'] ?? 0)
            + ($output / 1_000_000) * (float) ($pricing['output'] ?? 0),
            6
        );
    }

    /**
     * Provider DB default, atau provider sementara dari config env.
     */
    public function resolveProvider(): ?AiProviderSetting
    {
        $provider = $this->defaultProvider();

        if ($provider) {
            return $provider;
        }

        $endpoint = (string) config('arabic_names.ai.endpoint');

        if ($endpoint === '') {
            return null;
        }

        $transient = new AiProviderSetting([
            'name' => 'Config ENV',
            'provider' => 'custom',
            'endpoint' => $endpoint,
            'model' => (string) config('arabic_names.ai.model', 'gpt-4o-mini'),
            'is_active' => true,
            'is_default' => true,
        ]);
        $transient->api_key = (string) config('arabic_names.ai.key');

        return $transient;
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array{temperature?: float, max_tokens?: int, timeout?: int, tools?: array, tool_choice?: string}  $options
     * @return array{ok: bool, content: string, status: int, error: ?string, tool_calls: array, finish_reason: ?string, usage: array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int}, cost: float}
     */
    public function chat(array $messages, array $options = [], ?AiProviderSetting $provider = null): array
    {
        $provider = $provider ?: $this->resolveProvider();

        if (! $provider || ! filled($provider->endpoint)) {
            return ['ok' => false, 'content' => '', 'status' => 0, 'error' => 'Belum ada provider AI yang dikonfigurasi.', 'tool_calls' => [], 'finish_reason' => null, 'usage' => [], 'cost' => 0.0];
        }

        $headers = ['Accept' => 'application/json'];

        $apiKey = $provider->effectiveApiKey();
        if (filled($apiKey)) {
            $headers['Authorization'] = 'Bearer '.$apiKey;
        }

        if ($provider->provider === 'openrouter') {
            $headers['HTTP-Referer'] = (string) config('app.url');
            $headers['X-Title'] = (string) config('app.name');
        }

        // Header tambahan (opsional) dari kolom extra.
        foreach (($provider->extra['headers'] ?? []) as $key => $value) {
            if (is_string($key) && is_scalar($value)) {
                $headers[$key] = (string) $value;
            }
        }

        $payload = [
            'model' => $provider->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.2,
            'max_tokens' => $options['max_tokens'] ?? 800,
        ];

        if (! empty($options['tools'])) {
            $payload['tools'] = $options['tools'];
            $payload['tool_choice'] = $options['tool_choice'] ?? 'auto';
        }

        // DeepSeek V4 mendukung mode thinking. Untuk pertanyaan data pendek
        // mode ini dimatikan agar token reasoning tidak membengkak.
        if ($provider->provider === 'deepseek' && isset($options['thinking']) && is_array($options['thinking'])) {
            $payload['thinking'] = $options['thinking'];
        }

        // OpenRouter menormalkan reasoning lewat objek `reasoning`.
        if ($provider->provider === 'openrouter' && isset($options['reasoning']) && is_array($options['reasoning'])) {
            $payload['reasoning'] = $options['reasoning'];
        }

        try {
            $response = Http::withHeaders($headers)
                ->connectTimeout((int) config('ai.connect_timeout', 10))
                ->timeout((int) ($options['timeout'] ?? config('arabic_names.ai.timeout', 30)))
                ->post($provider->endpoint, $payload);

            if (! $response->successful()) {
                $error = 'HTTP '.$response->status().': '.mb_substr((string) $response->body(), 0, 300);
                Log::warning('AI provider gagal: '.$error);

                return ['ok' => false, 'content' => '', 'status' => $response->status(), 'error' => $error, 'tool_calls' => [], 'finish_reason' => null, 'usage' => [], 'cost' => 0.0];
            }

            $usage = (array) data_get($response->json(), 'usage', []);
            $message = (array) data_get($response->json(), 'choices.0.message', []);
            $content = (string) ($message['content'] ?? '');
            $toolCalls = (array) ($message['tool_calls'] ?? []);

            if (trim($content) === '' && $toolCalls === []) {
                return ['ok' => false, 'content' => '', 'status' => $response->status(), 'error' => 'Respons AI kosong.', 'tool_calls' => [], 'finish_reason' => null, 'usage' => $usage, 'cost' => $this->estimateCost($provider, $usage)];
            }

            return [
                'ok' => true,
                'content' => $content,
                'status' => $response->status(),
                'error' => null,
                'tool_calls' => $toolCalls,
                'finish_reason' => data_get($response->json(), 'choices.0.finish_reason'),
                'usage' => $usage,
                'cost' => $this->estimateCost($provider, $usage),
            ];
        } catch (\Throwable $e) {
            Log::warning('AI provider exception: '.$e->getMessage());

            return ['ok' => false, 'content' => '', 'status' => 0, 'error' => $e->getMessage(), 'tool_calls' => [], 'finish_reason' => null, 'usage' => [], 'cost' => 0.0];
        }
    }

    /**
     * Uji koneksi + simpan status pada provider.
     *
     * @return array{ok: bool, content: string, status: int, error: ?string}
     */
    public function testConnection(AiProviderSetting $provider): array
    {
        $result = $this->chat([
            ['role' => 'system', 'content' => 'Balas hanya dengan kata: OK'],
            ['role' => 'user', 'content' => 'Tes koneksi.'],
        ], ['temperature' => 0, 'max_tokens' => 256, 'timeout' => 30], $provider);

        if ($provider->exists) {
            $provider->forceFill([
                'last_tested_at' => now(),
                'last_test_status' => $result['ok'] ? 'ok' : 'failed',
                'last_test_message' => mb_substr($result['ok'] ? trim($result['content']) : (string) $result['error'], 0, 500),
            ])->save();
        }

        return $result;
    }
}
