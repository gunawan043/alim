<?php

use Illuminate\Support\Facades\DB;

$count = $this->scopeAsramaQuery(
    DB::table('dormitory_permits')->where('status', 'overdue'),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Izin Telat Kembali',
    'icon'  => 'ri-alarm-warning-line',
    'color' => $count > 0 ? 'danger' : 'success',
    'sub'   => 'Melewati batas waktu kembali',
];
