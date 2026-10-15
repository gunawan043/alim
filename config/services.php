<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Recruitment API (untuk Applicant App)
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk koneksi ke Applicant App (recruitment) untuk
    | mengirim notifikasi email ke applicant melalui shared database.
    |
    */
    'recruitment_api' => [
        'url' => env('RECRUITMENT_API_URL', 'http://localhost:8000'),
        'token' => env('RECRUITMENT_API_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | POS / Kasir Integration API
    |--------------------------------------------------------------------------
    |
    | Static API key used by the external POS (kasir) application to pull
    | master data (satuan kerja, kepegawaian, santri, sekolah, dll) from ALIM.
    |
    | - POS_API_KEY          : long random secret (min. 32 chars), empty disables
    |                          the whole /api/pos/v1 integration.
    | - POS_API_HEADER       : custom header name carrying the key. Defaults to
    |                          a non-generic name so automated scanners miss it.
    | - POS_ALLOWED_IPS      : optional comma-separated IP allowlist of the POS
    |                          server(s). Empty = any IP. Supports CIDR.
    |
    | The key is also accepted via `Authorization: Bearer <key>` for tooling.
    |
    */
    'pos' => [
        'api_key' => env('POS_API_KEY', ''),
        'header' => env('POS_API_HEADER', 'X-ALIM-POS-KEY'),
        'allowed_ips' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('POS_ALLOWED_IPS', ''))
        ))),
    ],

];
