<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Arsip — Backup
    |--------------------------------------------------------------------------
    |
    | Pengingat backup mingguan. Arahkan `path` ke direktori hasil backup
    | (dump database + folder storage/app/arsip). Bila tidak ada berkas baru
    | dalam `max_age_days`, sistem mengirim notifikasi ke Super Admin.
    |
    */
    'backup' => [
        'path' => env('ARSIP_BACKUP_PATH', storage_path('app/backups')),
        'max_age_days' => (int) env('ARSIP_BACKUP_MAX_AGE_DAYS', 7),
    ],
];
