<?php

use Illuminate\Support\Facades\DB;

$rows = DB::table('activity_log')
    ->selectRaw("COALESCE(NULLIF(event, ''), 'lainnya') as event, COUNT(*) as total")
    ->groupBy('event')
    ->orderByDesc('total')
    ->limit(8)
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'aktivitas-event',
    'label'  => 'Jenis Aktivitas',
    'type'   => 'donut',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('event')->map(fn ($e) => ucfirst((string) $e))->all(),
    'colors' => ['#405189', '#0ab39c', '#f7b84b', '#f06548', '#299cdb', '#8b5cf6', '#6c757d', '#e83e8c'],
    'options' => [
        'legend'      => ['position' => 'bottom'],
        'plotOptions' => ['pie' => ['donut' => ['size' => '62%']]],
    ],
];
