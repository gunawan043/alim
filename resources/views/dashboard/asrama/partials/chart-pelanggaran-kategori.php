<?php

use Illuminate\Support\Facades\DB;

$rows = $this->scopeAsramaQuery(
    DB::table('dormitory_violations')
        ->whereYear('violation_date', now()->year)
        ->whereMonth('violation_date', now()->month),
    $user
)->selectRaw("COALESCE(NULLIF(violation_category, ''), 'Lainnya') as kategori, COUNT(*) as total")
    ->groupBy('kategori')
    ->orderByDesc('total')
    ->limit(8)
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'pelanggaran-kategori',
    'label'  => 'Pelanggaran per Kategori',
    'type'   => 'donut',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('kategori')->all(),
    'colors' => ['#f06548', '#f7b84b', '#405189', '#299cdb', '#8b5cf6', '#0ab39c', '#6c757d', '#e83e8c'],
    'options' => [
        'legend'      => ['position' => 'bottom'],
        'plotOptions' => ['pie' => ['donut' => ['size' => '62%']]],
    ],
];
