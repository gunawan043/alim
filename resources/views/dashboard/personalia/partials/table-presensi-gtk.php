<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('absensi_gtk as a')
    ->leftJoin('users as u', 'u.id', '=', 'a.gtk_id')
    ->orderByDesc('a.tanggal')
    ->orderByDesc('a.created_at')
    ->limit(10)
    ->get([
        'u.name', 'a.tanggal', 'a.status', 'a.jam_masuk',
        'a.terlambat_menit', 'a.keterangan',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Presensi GTK Terbaru',
    'items' => $items,
];
