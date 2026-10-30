<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mode validasi default
    |--------------------------------------------------------------------------
    | qr    : registry + token QR (dapat diverifikasi publik)
    | pades : signature kriptografis PDF + registry
    |
    | PAdES hanya aktif bila kredensial PSrE tersedia. Mode per jenis dokumen
    | dapat diubah tanpa mengubah kode.
    */
    'default_mode' => env('DOCUMENTS_DEFAULT_MODE', 'qr'),

    'modes' => [
        'rapor' => env('DOCUMENTS_MODE_RAPOR', 'qr'),
        'mutasi' => env('DOCUMENTS_MODE_MUTASI', 'qr'),
        'sk' => env('DOCUMENTS_MODE_SK', 'qr'),
        'skl' => env('DOCUMENTS_MODE_SKL', 'qr'),
        'leger' => env('DOCUMENTS_MODE_LEGER', 'qr'),
        'kaldik' => env('DOCUMENTS_MODE_KALDIK', 'qr'),
        'jadwal' => env('DOCUMENTS_MODE_JADWAL', 'qr'),
        'cp' => env('DOCUMENTS_MODE_CP', 'qr'),
        'tp' => env('DOCUMENTS_MODE_TP', 'qr'),
        'pekan_efektif' => env('DOCUMENTS_MODE_PEKAN_EFEKTIF', 'qr'),
        'perangkat' => env('DOCUMENTS_MODE_PERANGKAT', 'qr'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Kunci tanda tangan registry QR
    |--------------------------------------------------------------------------
    | Gunakan kunci khusus, bukan APP_KEY, agar rotasi kunci aplikasi tidak
    | memutus verifikasi dokumen lama. Isi DOCUMENTS_SIGNING_KEY di .env.
    */
    'signing_key' => env('DOCUMENTS_SIGNING_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | PAdES / PSrE
    |--------------------------------------------------------------------------
    | Jangan mengisi private key di repo. Produksi memakai remote signing
    | API/HSM. `enabled` baru boleh true setelah provider PSrE disepakati.
    */
    'pades' => [
        'enabled' => (bool) env('DOCUMENTS_PADES_ENABLED', false),
        'endpoint' => env('DOCUMENTS_PADES_ENDPOINT'),
        'client_id' => env('DOCUMENTS_PADES_CLIENT_ID'),
        'client_secret' => env('DOCUMENTS_PADES_CLIENT_SECRET'),
        'profile' => env('DOCUMENTS_PADES_PROFILE', 'PAdES-B-T'),
        'tsa_url' => env('DOCUMENTS_PADES_TSA_URL'),
        'country' => env('DOCUMENTS_PADES_COUNTRY', 'ID'),
        'reason' => env('DOCUMENTS_PADES_REASON', 'Pengesahan dokumen resmi ALIM'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Halaman publik
    |--------------------------------------------------------------------------
    */
    'public' => [
        'route_prefix' => 'verifikasi-dokumen',
        'rate_limit_per_minute' => (int) env('DOCUMENTS_VERIFY_RATE_LIMIT', 30),
        'show_signer_names' => true,
        'show_document_number' => true,
    ],
];
