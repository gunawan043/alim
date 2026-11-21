<?php

use Illuminate\Support\Facades\DB;

/*
 * Izin yang sudah disetujui tetapi santrinya belum meninggalkan asrama.
 * Dipisahkan dari "Sedang di Luar" supaya angka pulang tidak membengkak
 * hanya karena persetujuan sudah diberikan.
 */
$count = $this->scopeAsramaQuery(
    DB::table('dormitory_permits')
        ->where('status', 'approved')
        ->whereNull('actual_return_datetime'),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Izin Disetujui',
    'icon'  => 'ri-file-check-line',
    'color' => 'info',
    'sub'   => 'Belum berangkat',
];
