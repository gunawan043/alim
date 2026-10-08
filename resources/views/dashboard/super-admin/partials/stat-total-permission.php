<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('permissions')->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Permission',
    'icon'  => 'ri-lock-password-line',
    'color' => 'info',
    'sub'   => 'Hak akses terdaftar',
];
