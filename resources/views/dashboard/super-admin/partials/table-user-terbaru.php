<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('users')
    ->whereNull('deleted_at')
    ->orderByDesc('created_at')
    ->limit(10)
    ->get(['name', 'email', 'is_active', 'created_at'])
    ->all();

return [
    '_type' => 'table',
    '_col'  => 'col-xl-6 col-12',
    'label' => 'User Terbaru',
    'items' => $items,
];
