<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('users')
    ->whereNull('deleted_at')
    ->whereNotExists(function ($q) {
        $q->select(DB::raw(1))->from('model_has_roles')->whereColumn('model_has_roles.model_id', 'users.id');
    })
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'User Tanpa Role',
    'icon'  => 'ri-user-warning-line',
    'color' => $count > 0 ? 'danger' : 'success',
    'sub'   => 'Perlu penetapan role',
];
