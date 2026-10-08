<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('tahfidz_uthq_registrations as r')
    ->join('tahfidz_uthq_events as e', 'e.id', '=', 'r.uthq_event_id')
    ->whereIn('r.status', ['terdaftar', 'lolos_audisi', 'finalis'])
    ->whereIn('e.status', ['pendaftaran', 'audisi', 'final'])
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Peserta UTHQ',
    'icon'  => 'ri-trophy-line',
    'color' => 'info',
    'sub'   => 'Peserta aktif event UTHQ',
];
