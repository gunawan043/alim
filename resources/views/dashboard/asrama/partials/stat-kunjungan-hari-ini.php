<?php

use Illuminate\Support\Facades\DB;

$count = $this->scopeAsramaQuery(
    DB::table('dormitory_visit_logs')->whereDate('expected_arrival_datetime', today()),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Kunjungan Hari Ini',
    'icon'  => 'ri-user-shared-line',
    'color' => 'info',
    'sub'   => 'Tamu terjadwal hari ini',
];
