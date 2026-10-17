<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$count = 0;

try {
    $count = DB::table('schools')->where('is_active', 1)->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-total-unit: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Unit Sekolah',
    'icon'  => 'ri-building-4-line',
    'color' => 'primary',
    'sub'   => 'Unit aktif di lingkungan yayasan',
];
