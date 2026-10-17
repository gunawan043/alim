<?php

use Illuminate\Support\Facades\DB;

$requests = DB::table('gtk_requests')->whereIn('status', ['pending', 'submitted'])->count();
$proposals = DB::table('gtk_position_proposals')->where('status', 'submitted')->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $requests + $proposals,
    'label' => 'Pending Verifikasi',
    'icon'  => 'ri-user-follow-line',
    'color' => ($requests + $proposals) > 0 ? 'warning' : 'success',
    'sub'   => "{$proposals} usulan jabatan · {$requests} pengajuan GTK",
];
