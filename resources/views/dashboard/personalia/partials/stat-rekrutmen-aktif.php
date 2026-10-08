<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('gtk_recruitments')
    ->whereNotIn('status', ['closed', 'selesai', 'ditolak', 'rejected', 'cancelled', 'done'])
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Rekrutmen Aktif',
    'icon'  => 'ri-user-search-line',
    'color' => 'info',
    'sub'   => 'Permintaan rekrutmen berjalan',
];
