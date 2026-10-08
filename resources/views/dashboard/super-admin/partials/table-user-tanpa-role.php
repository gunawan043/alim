<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('users')
    ->whereNull('deleted_at')
    ->whereNotExists(function ($q) {
        $q->select(DB::raw(1))->from('model_has_roles')->whereColumn('model_has_roles.model_id', 'users.id');
    })
    ->orderBy('name')
    ->limit(10)
    ->get(['name', 'email', 'is_active', 'created_at'])
    ->all();

return [
    '_type' => 'table',
    '_col'  => 'col-xl-6 col-12',
    'label' => 'User Belum Memiliki Role',
    'items' => $items,
];
