<?php

namespace Database\Seeders;

use App\Models\AiProviderSetting;
use App\Services\Ai\AiProviderPresets;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Katalog provider/model AI.
 *
 * Aman dijalankan berulang:
 * - tidak pernah menimpa API key, status aktif, atau default milik user;
 * - baris baru selalu nonaktif dan tanpa key;
 * - model dari platform yang sama berbagi API key melalui extra.key_group.
 */
class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        // Lengkapi key_group untuk provider lama (termasuk baris OpenRouter
        // yang sudah berisi API key) tanpa menyentuh key/status/default.
        AiProviderSetting::query()->each(function (AiProviderSetting $provider) {
            $group = $this->defaultKeyGroup($provider->provider);

            if ($group === null) {
                return;
            }

            $extra = is_array($provider->extra) ? $provider->extra : [];
            if (empty($extra['key_group'])) {
                $extra['key_group'] = $group;
                $provider->forceFill(['extra' => $extra])->saveQuietly();
            }
        });

        foreach (config('ai_provider_catalog', []) as $entry) {
            $preset = AiProviderPresets::get($entry['provider']);
            if (! $preset) {
                continue;
            }

            $name = (string) $entry['name'];
            $existing = AiProviderSetting::where('name', $name)->first();

            $extra = [
                'tier' => $entry['tier'] ?? 'paid',
                'pricing' => $entry['pricing'] ?? null,
                'notes' => $entry['notes'] ?? null,
                'key_group' => $entry['key_group'] ?? null,
                'sort_order' => $entry['sort_order'] ?? 999,
                'catalog' => 'v1',
            ];

            if ($existing) {
                // Hanya tambahkan metadata yang belum ada. Jangan ubah
                // api_key, is_active, is_default, endpoint, atau model.
                $current = is_array($existing->extra) ? $existing->extra : [];
                $merged = array_merge($extra, $current);
                if ($merged !== $current) {
                    $existing->forceFill(['extra' => $merged])->saveQuietly();
                }

                continue;
            }

            AiProviderSetting::create([
                'id' => (string) Str::uuid(),
                'name' => $name,
                'provider' => $entry['provider'],
                'endpoint' => $entry['endpoint'] ?? $preset['endpoint'],
                'model' => $entry['model'] ?? $preset['model'],
                'api_key' => null,
                'is_active' => false,
                'is_default' => false,
                'extra' => $extra,
            ]);
        }
    }

    private function defaultKeyGroup(string $provider): ?string
    {
        return match ($provider) {
            'openrouter', 'groq', 'gemini', 'openai', 'anthropic' => $provider,
            default => null,
        };
    }
}
