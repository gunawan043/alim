<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Penyimpanan Foto Bukti
    |--------------------------------------------------------------------------
    | Upload lokal memakai disk ini. Untuk menghemat memori, pengguna juga
    | dapat menempelkan tautan Google Drive pada kolom URL foto.
    */
    'photo_disk' => env('SARPRAS_PHOTO_DISK', 'public'),
    'google_drive_folder_url' => env('SARPRAS_GOOGLE_DRIVE_FOLDER_URL'),
];
