<?php

use Illuminate\Support\Facades\DB;

$roles = DB::table('roles')->orderBy('name')->get(['id', 'name', 'level']);

$userCounts = DB::table('model_has_roles')
    ->selectRaw('role_id, COUNT(*) as total')
    ->groupBy('role_id')
    ->pluck('total', 'role_id');

$permCounts = DB::table('role_has_permissions')
    ->selectRaw('role_id, COUNT(*) as total')
    ->groupBy('role_id')
    ->pluck('total', 'role_id');

$items = $roles->map(fn ($role) => (object) [
    'role'        => $role->name,
    'level'       => $role->level,
    'users'       => (int) ($userCounts[$role->id] ?? 0),
    'permissions' => (int) ($permCounts[$role->id] ?? 0),
])->all();

return [
    '_type' => 'table',
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Role & Hak Akses',
    'items' => $items,
];
