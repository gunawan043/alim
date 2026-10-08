<?php

use Illuminate\Support\Facades\DB;

$count = $this->scopeAsramaQuery(
    DB::table('dormitory_rewards')
        ->whereYear('awarded_date', now()->year)
        ->whereMonth('awarded_date', now()->month),
    $user,
    'dormitory_id',
    'dormitory_id'
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Penghargaan Bulan Ini',
    'icon'  => 'ri-medal-line',
    'color' => 'success',
    'sub'   => 'Apresiasi santri asrama',
];
