<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$rows = DB::table('work_orders as w')
    ->leftJoin('assets as a', 'a.id', '=', 'w.asset_id')
    ->when($schoolId, fn ($q) => $q->where('a.school_id', $schoolId))
    ->selectRaw("COALESCE(NULLIF(w.type, ''), 'lainnya') as tipe, COUNT(*) as total")
    ->groupBy('tipe')
    ->orderByDesc('total')
    ->limit(8)
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'wo-per-type',
    'label'  => 'Work Order per Jenis',
    'type'   => 'donut',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('tipe')->map(fn ($t) => ucwords(str_replace('_', ' ', (string) $t)))->all(),
    'colors' => ['#405189', '#0ab39c', '#f7b84b', '#f06548', '#299cdb', '#8b5cf6', '#6c757d', '#e83e8c'],
    'options' => [
        'legend'      => ['position' => 'bottom'],
        'plotOptions' => ['pie' => ['donut' => ['size' => '62%']]],
    ],
];
