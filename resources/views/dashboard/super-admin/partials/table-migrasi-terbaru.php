<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('migrations')
    ->orderByDesc('id')
    ->limit(10)
    ->get(['migration', 'batch'])
    ->all();

return [
    '_type' => 'table',
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Migrasi Terbaru',
    'items' => $items,
];
