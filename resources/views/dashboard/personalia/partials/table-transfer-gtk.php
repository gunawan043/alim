<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('gtk_transfer_requests as t')
    ->leftJoin('users as u', 'u.id', '=', 't.user_id')
    ->leftJoin('work_units as wf', 'wf.id', '=', 't.from_work_unit_id')
    ->leftJoin('work_units as wt', 'wt.id', '=', 't.to_work_unit_id')
    ->orderByDesc('t.created_at')
    ->limit(10)
    ->get([
        'u.name', 'wf.name as dari', 'wt.name as ke', 't.jabatan', 't.status', 't.created_at',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Permintaan Mutasi GTK',
    'items' => $items,
];
