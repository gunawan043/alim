<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto-Deploy Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi webhook auto-deploy. Set di .env:
    |
    |   DEPLOY_SECRET=...           # Shared secret (sama dengan di GitHub webhook)
    |   DEPLOY_BRANCH=main          # Branch yang akan di-deploy otomatis
    |   DEPLOY_SCRIPT=/path/...     # Path absolut ke deploy.sh di server
    |
    */

    'secret' => env('DEPLOY_SECRET', ''),

    'branch' => env('DEPLOY_BRANCH', 'main'),

    'script_path' => env('DEPLOY_SCRIPT', base_path('deploy.sh')),

];
