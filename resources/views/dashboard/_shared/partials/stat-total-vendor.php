<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$count = 0;

try {
    $count = DB::table('vendors')->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-total-vendor: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Vendor Terdaftar',
    'icon'  => 'ri-store-2-line',
    'color' => 'info',
    'sub'   => 'Terdaftar di sistem pengadaan',
];
