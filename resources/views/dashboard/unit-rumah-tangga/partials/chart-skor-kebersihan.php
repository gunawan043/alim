<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$rows = DB::table('sanitation_inspections')
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->whereYear('inspection_date', now()->year)
    ->whereMonth('inspection_date', now()->month)
    ->whereNotNull('score')
    ->selectRaw("COALESCE(NULLIF(location_type, ''), 'lainnya') as lokasi, ROUND(AVG(score), 1) as rata")
    ->groupBy('lokasi')
    ->orderBy('lokasi')
    ->limit(8)
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'skor-kebersihan',
    'label'  => 'Rata-rata Skor Kebersihan per Area',
    'type'   => 'bar',
    'series' => [['name' => 'Skor', 'data' => $rows->pluck('rata')->map(fn ($v) => (float) $v)->all()]],
    'options' => [
        'xaxis'       => ['categories' => $rows->pluck('lokasi')->map(fn ($l) => ucwords(str_replace('_', ' ', (string) $l)))->all()],
        'colors'      => ['#0ab39c'],
        'dataLabels'  => ['enabled' => true],
        'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '45%']],
    ],
];
