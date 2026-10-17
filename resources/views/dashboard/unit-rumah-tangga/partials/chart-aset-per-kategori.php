<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$rows = DB::table('assets as a')
    ->leftJoin('asset_categories as c', 'c.id', '=', 'a.asset_category_id')
    ->whereNull('a.deleted_at')
    ->where('a.is_active', 1)
    ->when($schoolId, fn ($q) => $q->where('a.school_id', $schoolId))
    ->selectRaw("COALESCE(c.name, 'Tanpa Kategori') as kategori, COUNT(*) as total")
    ->groupBy('kategori')
    ->orderByDesc('total')
    ->limit(8)
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'aset-per-kategori',
    'label'  => 'Aset per Kategori',
    'type'   => 'donut',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('kategori')->all(),
    'colors' => ['#405189', '#0ab39c', '#f7b84b', '#f06548', '#299cdb', '#8b5cf6', '#6c757d', '#e83e8c'],
    'options' => [
        'legend'      => ['position' => 'bottom'],
        'plotOptions' => ['pie' => ['donut' => ['size' => '62%']]],
    ],
];
