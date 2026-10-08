<?php

use Illuminate\Support\Facades\DB;

$rows = DB::table('roles as r')
    ->leftJoin('model_has_roles as mr', 'mr.role_id', '=', 'r.id')
    ->groupBy('r.id', 'r.name')
    ->selectRaw('r.name, COUNT(mr.model_id) as total')
    ->orderByDesc('total')
    ->limit(10)
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'user-per-role',
    'label'  => 'Distribusi User per Role',
    'type'   => 'bar',
    'series' => [['name' => 'Jumlah User', 'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all()]],
    'options' => [
        'plotOptions' => ['bar' => ['horizontal' => true, 'borderRadius' => 4, 'columnWidth' => '55%']],
        'xaxis'       => ['categories' => $rows->pluck('name')->all()],
        'colors'      => ['#405189'],
        'dataLabels'  => ['enabled' => true],
    ],
];
