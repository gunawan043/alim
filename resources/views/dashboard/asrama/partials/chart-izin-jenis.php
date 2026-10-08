<?php

use Illuminate\Support\Facades\DB;

$labels = DB::table('permit_types')->pluck('label', 'code');

$rows = $this->scopeAsramaQuery(
    DB::table('dormitory_permits')
        ->whereYear('created_at', now()->year)
        ->whereMonth('created_at', now()->month),
    $user
)->selectRaw('permit_type, COUNT(*) as total')
    ->groupBy('permit_type')
    ->orderByDesc('total')
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'izin-jenis',
    'label'  => 'Izin per Jenis (Bulan Ini)',
    'type'   => 'donut',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('permit_type')->map(fn ($t) => $labels[$t] ?? ucfirst((string) $t))->all(),
    'colors' => ['#405189', '#0ab39c', '#f7b84b', '#f06548', '#299cdb', '#8b5cf6', '#6c757d'],
    'options' => [
        'legend'      => ['position' => 'bottom'],
        'plotOptions' => ['pie' => ['donut' => ['size' => '62%']]],
    ],
];
