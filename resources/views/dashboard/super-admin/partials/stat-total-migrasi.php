<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('migrations')->count();
$batch = DB::table('migrations')->max('batch');

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Migrasi Database',
    'icon'  => 'ri-database-2-line',
    'color' => 'primary',
    'sub'   => 'Batch terakhir #' . ($batch ?? '-'),
];
