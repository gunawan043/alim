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
                return null;
            }

            return AiProviderSetting::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->first();
        } catch (\Throwable) {
            return null;
        }
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
     * @return array{ok: bool, content: string, status: int, error: ?string, tool_calls: array, finish_reason: ?string}
     */
    public function chat(array $messages, array $options = [], ?AiProviderSetting $provider = null): array
    {
        $provider = $provider ?: $this->resolveProvider();

        if (! $provider || ! filled($provider->endpoint)) {
            return ['ok' => false, 'content' => '', 'status' => 0, 'error' => 'Belum ada provider AI yang dikonfigurasi.', 'tool_calls' => [], 'finish_reason' => null];
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

        try {
            $response = Http::withHeaders($headers)
                ->timeout((int) ($options['timeout'] ?? config('arabic_names.ai.timeout', 30)))
                ->post($provider->endpoint, $payload);

            if (! $response->successful()) {
                $error = 'HTTP '.$response->status().': '.mb_substr((string) $response->body(), 0, 300);
                Log::warning('AI provider gagal: '.$error);

                return ['ok' => false, 'content' => '', 'status' => $response->status(), 'error' => $error, 'tool_calls' => [], 'finish_reason' => null];
            }

            $message = (array) data_get($response->json(), 'choices.0.message', []);
            $content = (string) ($message['content'] ?? '');
            $toolCalls = (array) ($message['tool_calls'] ?? []);

            if (trim($content) === '' && $toolCalls === []) {
                return ['ok' => false, 'content' => '', 'status' => $response->status(), 'error' => 'Respons AI kosong.', 'tool_calls' => [], 'finish_reason' => null];
            }

            return [
                'ok' => true,
                'content' => $content,
                'status' => $response->status(),
                'error' => null,
                'tool_calls' => $toolCalls,
                'finish_reason' => data_get($response->json(), 'choices.0.finish_reason'),
            ];
        } catch (\Throwable $e) {
            Log::warning('AI provider exception: '.$e->getMessage());

            return ['ok' => false, 'content' => '', 'status' => 0, 'error' => $e->getMessage(), 'tool_calls' => [], 'finish_reason' => null];
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
