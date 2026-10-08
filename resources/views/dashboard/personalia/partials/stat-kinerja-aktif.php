<?php

use Illuminate\Support\Facades\DB;

$periode = DB::table('kinerja_periode')->whereIn('status', ['draft', 'aktif'])->orderByDesc('tanggal_mulai')->first();
$count = DB::table('kinerja_periode')->whereIn('status', ['draft', 'aktif'])->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Periode Kinerja Aktif',
    'icon'  => 'ri-medal-line',
    'color' => 'info',
    'sub'   => $periode ? $periode->nama : 'Belum ada periode berjalan',
];
