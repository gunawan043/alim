<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Isi dengan IP/CIDR reverse proxy (Nginx lokal, load balancer, atau
    | Cloudflare) bila aplikasi berada di belakang proxy. Jangan gunakan "*"
    | pada server yang dapat diakses langsung dari internet.
    |
    */
    'proxies' => env('TRUSTED_PROXIES'),
];
