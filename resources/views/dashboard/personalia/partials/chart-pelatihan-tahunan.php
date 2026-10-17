<?php

use Illuminate\Support\Facades\DB;

$rows = DB::table('gtk_trainings')
    ->whereNotNull('tahun')
    ->selectRaw('tahun, COUNT(*) as total')
    ->groupBy('tahun')
    ->orderBy('tahun')
    ->limit(6)
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'pelatihan-tahunan',
    'label'  => 'Pelatihan GTK per Tahun',
    'type'   => 'bar',
    'series' => [['name' => 'Pelatihan', 'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all()]],
    'options' => [
        'xaxis'       => ['categories' => $rows->pluck('tahun')->map(fn ($v) => (string) $v)->all()],
        'colors'      => ['#0ab39c'],
        'dataLabels'  => ['enabled' => true],
        'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '45%']],
    ],
];
