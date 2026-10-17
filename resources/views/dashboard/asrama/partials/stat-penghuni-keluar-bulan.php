<?php

use Illuminate\Support\Facades\DB;

$keluar = $this->scopeAsramaQuery(
    DB::table('dormitory_residents')
        ->whereYear('check_out_date', now()->year)
        ->whereMonth('check_out_date', now()->month),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $keluar,
    'label' => 'Penghuni Keluar',
    'icon'  => 'ri-logout-box-line',
    'color' => 'danger',
    'sub'   => 'Check-out bulan ini',
];
