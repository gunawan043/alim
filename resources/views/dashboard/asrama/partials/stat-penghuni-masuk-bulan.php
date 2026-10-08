<?php

use Illuminate\Support\Facades\DB;

$masuk = $this->scopeAsramaQuery(
    DB::table('dormitory_residents')
        ->whereYear('check_in_date', now()->year)
        ->whereMonth('check_in_date', now()->month),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $masuk,
    'label' => 'Penghuni Masuk',
    'icon'  => 'ri-login-box-line',
    'color' => 'success',
    'sub'   => 'Check-in bulan ini',
];
