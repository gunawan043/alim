<?php

use Illuminate\Support\Facades\DB;

$rows = DB::table('cuti_requests')
    ->whereYear('created_at', now()->year)
    ->selectRaw('status, COUNT(*) as total')
    ->groupBy('status')
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'cuti-per-status',
    'label'  => 'Cuti per Status (Tahun Ini)',
    'type'   => 'donut',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('status')->map(fn ($s) => ucfirst((string) $s))->all(),
    'colors' => ['#f7b84b', '#0ab39c', '#f06548'],
    'options' => [
        'legend'      => ['position' => 'bottom'],
        'plotOptions' => ['pie' => ['donut' => ['size' => '62%']]],
    ],
];
