<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('users')->where('is_active', 0)->whereNull('deleted_at')->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'User Nonaktif',
    'icon'  => 'ri-user-unfollow-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Akun dinonaktifkan',
];
