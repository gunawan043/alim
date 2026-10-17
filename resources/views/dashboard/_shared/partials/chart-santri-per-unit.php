<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$rows = collect();

try {
    $rows = DB::table('schools as s')
        ->leftJoin('students as st', function ($join) {
            $join->on('st.school_id', '=', 's.id')->where('st.status', 'active');
        })
        ->where('s.is_active', 1)
        ->groupBy('s.id', 's.name')
        ->selectRaw('s.name, COUNT(st.id) as total')
        ->orderByDesc('total')
        ->limit(10)
        ->get();
} catch (\Throwable $e) {
    Log::warning('Widget chart-santri-per-unit: ' . $e->getMessage());
}

return [
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'santri-per-unit',
    'label'  => 'Sebaran Santri per Unit',
    'type'   => 'bar',
    'series' => [['name' => 'Jumlah Santri', 'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all()]],
    'options' => [
        'plotOptions' => ['bar' => ['horizontal' => true, 'borderRadius' => 4, 'columnWidth' => '55%']],
        'xaxis'       => ['categories' => $rows->pluck('name')->all()],
        'colors'      => ['#405189'],
        'dataLabels'  => ['enabled' => true],
    ],
];
