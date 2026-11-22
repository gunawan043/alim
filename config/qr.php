<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Logo pada QR Code
    |--------------------------------------------------------------------------
    |
    | Dipakai oleh App\Services\Ljk\LjkQrRenderer. Nilai di sini hanya
    | default; Pengaturan Sistem (tab QR) menimpanya bila sudah diisi.
    |
    | ratio = lebar logo dibanding lebar QR (0.22 berarti 22%).
    | Saat logo aktif, error correction otomatis dinaikkan ke level H.
    |
    */

    'logo' => [
        'enabled' => env('QR_LOGO_ENABLED', true),
        'path' => env('QR_LOGO_PATH', public_path('build/images/alim-sm-light.png')),
        'ratio' => (float) env('QR_LOGO_RATIO', 0.22),
        'min_ratio' => 0.12,
        'max_ratio' => 0.28,
    ],

];
