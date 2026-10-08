<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('cuti_balances as b')
    ->leftJoin('users as u', 'u.id', '=', 'b.user_id')
    ->leftJoin('cuti_templates as t', 't.id', '=', 'b.cuti_template_id')
    ->orderBy('b.tersisa')
    ->limit(10)
    ->get([
        'u.name', 't.nama as jenis', 'b.jumlah_hari', 'b.digunakan', 'b.tersisa',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Saldo Cuti GTK',
    'items' => $items,
];
