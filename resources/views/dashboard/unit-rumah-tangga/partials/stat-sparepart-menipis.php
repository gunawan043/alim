<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('spareparts')
    ->where('is_active', 1)
    ->whereColumn('stock', '<=', 'min_stock')
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Stok Menipis',
    'icon'  => 'ri-error-warning-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Di bawah stok minimum',
];
