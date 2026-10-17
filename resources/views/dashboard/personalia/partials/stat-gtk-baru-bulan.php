<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('gtk_profiles')
    ->whereYear('created_at', now()->year)
    ->whereMonth('created_at', now()->month)
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'GTK Baru',
    'icon'  => 'ri-user-add-line',
    'color' => 'success',
    'sub'   => 'Profil terdaftar bulan ini',
];
