<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('activity_log')->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Total Log Aktivitas',
    'icon'  => 'ri-history-line',
    'color' => 'info',
    'sub'   => 'Seluruh catatan sistem',
];
