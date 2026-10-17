<?php

namespace App\Services\Ai;

/**
 * Preset platform AI (semuanya memakai protokol OpenAI Chat Completions).
 * Endpoint dapat diubah manual untuk platform lain.
 */
class AiProviderPresets
{
    public const PRESETS = [
        'openai' => [
            'label' => 'OpenAI',
            'endpoint' => 'https://api.openai.com/v1/chat/completions',
            'model' => 'gpt-4o-mini',
            'needs_key' => true,
            'note' => 'Berbayar (sesuai pemakaian).',
        ],
        'gemini' => [
            'label' => 'Google Gemini (OpenAI-compatible)',
            'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
            'model' => 'gemini-2.0-flash',
            'needs_key' => true,
            'note' => 'Tersedia free tier.',
        ],
        'groq' => [
            'label' => 'Groq',
            'endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
            'model' => 'llama-3.3-70b-versatile',
            'needs_key' => true,
            'note' => 'Tersedia free tier.',
        ],
        'openrouter' => [
            'label' => 'OpenRouter',
            'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
            'model' => 'meta-llama/llama-3.3-70b-instruct:free',
            'needs_key' => true,
            'note' => 'Banyak model gratis (akhiran :free).',
        ],
        'anthropic' => [
            'label' => 'Anthropic Claude (OpenAI-compatible)',
            'endpoint' => 'https://api.anthropic.com/v1/chat/completions',
            'model' => 'claude-3-5-haiku-latest',
            'needs_key' => true,
            'note' => 'Berbayar. Bila endpoint kompatibilitas berbeda, gunakan Custom.',
        ],
        'ollama' => [
            'label' => 'Ollama (lokal — gratis)',
            'endpoint' => 'http://127.0.0.1:11434/v1/chat/completions',
            'model' => 'qwen2.5',
            'needs_key' => false,
            'note' => 'Jalankan Ollama di server; tanpa biaya API.',
        ],
        'lmstudio' => [
            'label' => 'LM Studio (lokal — gratis)',
            'endpoint' => 'http://127.0.0.1:1234/v1/chat/completions',
            'model' => 'local-model',
            'needs_key' => false,
            'note' => 'Jalankan LM Studio (server lokal); tanpa biaya API.',
        ],
        'custom' => [
            'label' => 'Custom (OpenAI-compatible)',
            'endpoint' => '',
            'model' => '',
            'needs_key' => false,
            'note' => 'Untuk platform lain yang mendukung /chat/completions.',
        ],
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::PRESETS;
    }

    public static function get(string $key): ?array
    {
        return self::PRESETS[$key] ?? null;
    }

    public static function keys(): array
    {
        return array_keys(self::PRESETS);
    }
}
