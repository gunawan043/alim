<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('promosi_demosi as pd')
    ->leftJoin('users as u', 'u.id', '=', 'pd.user_id')
    ->orderByDesc('pd.tanggal_sk')
    ->limit(10)
    ->get([
        'u.name', 'pd.jenis', 'pd.jabatan_lama', 'pd.jabatan_baru',
        'pd.nomor_sk', 'pd.tanggal_sk', 'pd.status',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Promosi & Demosi Terbaru',
    'items' => $items,
];
