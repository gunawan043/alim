<?php

use Illuminate\Support\Facades\DB;

$sessions = ['subuh', 'pagi', 'siang', 'sore', 'isya', 'malam'];

$recorded = $this->scopeAsramaQuery(
    DB::table('dormitory_attendances')->where('attendance_date', today()),
    $user
)->distinct()->pluck('session')->all();

$belum = count(array_diff($sessions, $recorded));

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $belum,
    'label' => 'Sesi Belum Diisi',
    'icon'  => 'ri-calendar-todo-line',
    'color' => $belum > 0 ? 'warning' : 'success',
    'sub'   => 'Dari 6 sesi harian',
];
