<?php

use Illuminate\Support\Facades\DB;

/*
 * Hanya santri yang benar-benar sudah meninggalkan asrama: izin berstatus
 * picked_up (sudah dijemput) atau overdue (melewati batas kembali).
 * Izin yang baru disetujui tetapi belum berangkat dihitung terpisah.
 */
$count = $this->scopeAsramaQuery(
    DB::table('dormitory_permits')
        ->whereIn('status', ['picked_up', 'overdue'])
        ->whereNull('actual_return_datetime'),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Sedang di Luar',
    'icon'  => 'ri-walk-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Sudah keluar asrama',
];
