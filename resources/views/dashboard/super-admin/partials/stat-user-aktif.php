<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('users')->where('is_active', 1)->whereNull('deleted_at')->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'User Aktif',
    'icon'  => 'ri-user-follow-line',
    'color' => 'success',
    'sub'   => 'Akun aktif sistem',
];
