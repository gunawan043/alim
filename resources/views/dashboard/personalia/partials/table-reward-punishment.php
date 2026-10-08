<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('kinerja_reward_punishment as rp')
    ->leftJoin('users as u', 'u.id', '=', 'rp.user_id')
    ->leftJoin('kinerja_periode as p', 'p.id', '=', 'rp.kinerja_periode_id')
    ->orderByDesc('rp.tanggal')
    ->limit(10)
    ->get([
        'u.name', 'rp.jenis', 'rp.kategori', 'rp.nama', 'p.nama as periode', 'rp.tanggal',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Reward & Punishment Terbaru',
    'items' => $items,
];
