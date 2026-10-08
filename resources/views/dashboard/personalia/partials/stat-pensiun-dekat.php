<?php

use Illuminate\Support\Facades\DB;

$count = DB::table('gtk_pensions')
    ->whereNotNull('planned_pension_date')
    ->whereDate('planned_pension_date', '<=', now()->addDays(90))
    ->whereNotIn('pension_status', ['selesai', 'diproses_selesai', 'done', 'cancelled'])
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Pensiun Dekat',
    'icon'  => 'ri-logout-circle-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Rencana pensiun ≤ 90 hari',
];
