<?php

use Illuminate\Support\Facades\DB;

$count = $this->scopeAsramaQuery(
    DB::table('dormitory_permits')->where('status', 'pending'),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Izin Menunggu',
    'icon'  => 'ri-time-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Perlu persetujuan',
];
