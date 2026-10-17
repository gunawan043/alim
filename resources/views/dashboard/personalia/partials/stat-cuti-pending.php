<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('cuti_requests')->where('status', 'pending')->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Cuti Menunggu',
    'icon'  => 'ri-calendar-todo-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Perlu persetujuan',
];
