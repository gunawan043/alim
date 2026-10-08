<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('spareparts')->where('is_active', 1)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Item Sparepart',
    'icon'  => 'ri-stack-line',
    'color' => 'primary',
    'sub'   => 'Aktif di gudang',
];
