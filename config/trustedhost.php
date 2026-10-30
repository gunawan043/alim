<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Hosts (Host Header Allowlist)
    |--------------------------------------------------------------------------
    |
    | Daftar host yang boleh mengakses aplikasi, dipisah koma. Nilai berupa
    | host persis atau pola regex (tanpa delimiter), contoh:
    |
    |   alim.sekolah.sch.id,^(.+\.)?alim\.sekolah\.sch\.id$
    |
    | Kosong = gunakan host dari APP_URL beserta seluruh subdomainnya,
    | ditambah localhost/127.0.0.1 untuk pengembangan.
    |
    */

    'hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_HOSTS', ''))
    ))),

];
