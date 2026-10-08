<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('gtk_profiles')
    ->join('users', 'users.id', '=', 'gtk_profiles.user_id')
    ->where('users.is_active', 1)
    ->whereNull('users.deleted_at')
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'GTK Aktif',
    'icon'  => 'ri-team-line',
    'color' => 'primary',
    'sub'   => 'Seluruh unit',
];
