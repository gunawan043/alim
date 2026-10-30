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

        // Tolak request non-HTTPS saat APP_ENV=production.
        'require_https' => env('POS_REQUIRE_HTTPS', true),

        // Batas percobaan autentikasi gagal per IP per menit.
        'auth_fail_limit' => env('POS_AUTH_FAIL_LIMIT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Recruitment Integration API (inbound)
    |--------------------------------------------------------------------------
    |
    | Digunakan oleh aplikasi recruitment (applicant app) untuk menarik data
    | referensi milik Personalia: pegawai/GTK, satuan kerja, jabatan, jenis
    | GTK, sekolah, dan role. Endpoint: /api/recruitment/v1/*.
    |
    | Catatan: RECRUITMENT_API_URL/TOKEN di bawah adalah arah keluar (ALIM
    | memanggil aplikasi recruitment untuk dokumen/notifikasi) — berbeda
    | dengan kunci masuk di sini.
    |
    */
    'recruitment' => [
        'api_key' => env('RECRUITMENT_INTEGRATION_KEY', ''),
        'header' => env('RECRUITMENT_INTEGRATION_HEADER', 'X-ALIM-RECRUITMENT-KEY'),
        'allowed_ips' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('RECRUITMENT_INTEGRATION_ALLOWED_IPS', ''))
        ))),
        'require_https' => env('RECRUITMENT_INTEGRATION_REQUIRE_HTTPS', true),
        'auth_fail_limit' => env('RECRUITMENT_INTEGRATION_AUTH_FAIL_LIMIT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | POS Procurement (ALIM → POS)
    |--------------------------------------------------------------------------
    |
    | ALIM memanggil API pengadaan POS untuk katalog, pengajuan, dan status.
    | Nilai database di Pengaturan → Integrasi POS menjadi sumber utama;
    | env di bawah hanya fallback.
    |
    */
    'pos_procurement' => [
        'base_url' => env('POS_PROCUREMENT_URL', ''),
        'api_key' => env('POS_PROCUREMENT_KEY', ''),
        'header' => env('POS_PROCUREMENT_HEADER', 'X-POS-PROCUREMENT-KEY'),
        'timeout' => env('POS_PROCUREMENT_TIMEOUT', 15),
        'verify_ssl' => env('POS_PROCUREMENT_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Notification Native — FCM + APNs
    |--------------------------------------------------------------------------
    |
    | FCM memakai service account Firebase (JSON). APNs memakai auth key .p8.
    | Nilai ini adalah fallback environment; pengaturan utama tersedia di
    | Super Admin > Pengaturan Sistem > Push Mobile dan disimpan terenkripsi.
    |
    */
    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID', ''),
        'service_account_json' => env('FCM_SERVICE_ACCOUNT_JSON', ''),
        'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH', ''),
    ],

    'web_push' => [
        'vapid_public_key' => env('WEB_PUSH_VAPID_PUBLIC_KEY', ''),
        'vapid_private_key' => env('WEB_PUSH_VAPID_PRIVATE_KEY', ''),
        'vapid_subject' => env('WEB_PUSH_VAPID_SUBJECT', ''),
    ],

    'apns' => [
        'key_id' => env('APNS_KEY_ID', ''),
        'team_id' => env('APNS_TEAM_ID', ''),
        'bundle_id' => env('APNS_BUNDLE_ID', env('IOS_BUNDLE_ID', 'com.alim.app')),
        'private_key' => env('APNS_PRIVATE_KEY', ''),
        'private_key_path' => env('APNS_PRIVATE_KEY_PATH', ''),
        'environment' => env('APNS_ENVIRONMENT', 'production'),
    ],

];
