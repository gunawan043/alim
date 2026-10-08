<?php

use Illuminate\Support\Facades\DB;

$roles = DB::table('roles')->count();
$permissions = DB::table('permissions')->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $roles,
    'label' => 'Role & Permission',
    'icon'  => 'ri-shield-keyhole-line',
    'color' => 'primary',
    'sub'   => $roles . ' role · ' . number_format($permissions, 0, ',', '.') . ' permission',
];
