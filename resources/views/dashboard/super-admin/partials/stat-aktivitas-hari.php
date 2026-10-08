<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('activity_log')->whereDate('created_at', today())->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Aktivitas Hari Ini',
    'icon'  => 'ri-pulse-line',
    'color' => 'success',
    'sub'   => 'Log tercatat hari ini',
];
