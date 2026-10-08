<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('gtk_recruitments as r')
    ->leftJoin('work_units as w', 'w.id', '=', 'r.work_unit_id')
    ->orderByDesc('r.tanggal_dibutuhkan')
    ->limit(10)
    ->get([
        'r.jabatan', 'w.name as unit', 'r.kebutuhan', 'r.tanggal_dibutuhkan', 'r.status',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Permintaan Rekrutmen',
    'items' => $items,
];
