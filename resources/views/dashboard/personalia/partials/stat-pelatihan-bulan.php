<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('gtk_trainings')
    ->whereYear('created_at', now()->year)
    ->whereMonth('created_at', now()->month)
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Pelatihan Bulan Ini',
    'icon'  => 'ri-presentation-line',
    'color' => 'info',
    'sub'   => 'Pelatihan GTK tercatat',
];
