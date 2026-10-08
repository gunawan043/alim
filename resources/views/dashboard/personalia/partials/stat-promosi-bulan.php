<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('promosi_demosi')
    ->whereYear('created_at', now()->year)
    ->whereMonth('created_at', now()->month)
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Promosi / Demosi',
    'icon'  => 'ri-arrow-up-circle-line',
    'color' => 'primary',
    'sub'   => 'Keputusan bulan ini',
];
