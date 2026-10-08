<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('gtk_transfer_requests')->where('status', 'pending')->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Mutasi Menunggu',
    'icon'  => 'ri-exchange-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Permintaan pindah unit',
];
