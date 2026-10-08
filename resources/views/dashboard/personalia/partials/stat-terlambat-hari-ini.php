<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('absensi_gtk')
    ->where('tanggal', today())
    ->where('terlambat_menit', '>', 0)
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Terlambat Hari Ini',
    'icon'  => 'ri-time-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'GTK datang terlambat',
];
