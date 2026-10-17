<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Generator Nama Arab
    |--------------------------------------------------------------------------
    |
    | driver = "rule"  → kaidah transliterasi deterministik (tanpa jaringan).
    | driver = "ai"    → penyedia AI kompatibel OpenAI Chat Completions.
    |
    | AI bersifat OPSIONAL. Bila kunci/endpoint tidak tersedia atau panggilan
    | gagal, sistem otomatis kembali ke kaidah transliterasi (fallback) agar
    | import/generate tetap berjalan.
    |
    */
    'driver' => env('ARABIC_NAME_DRIVER', 'rule'),

    'ai' => [
        'endpoint' => env('ARABIC_NAME_AI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
        'key' => env('ARABIC_NAME_AI_KEY'),
        'model' => env('ARABIC_NAME_AI_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('ARABIC_NAME_AI_TIMEOUT', 20),
        'batch_limit' => (int) env('ARABIC_NAME_AI_BATCH_LIMIT', 20),
    ],
];
