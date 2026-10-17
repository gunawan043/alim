<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$rows = $this->applyUksScope(
    DB::table('uks_patients as p')
        ->leftJoin('dormitories as d', 'd.id', '=', 'p.dormitory_id')
        ->when($schoolId, fn ($q) => $q->where('p.school_id', $schoolId)),
    $user,
    'p.student_id'
)
    ->groupBy('p.dormitory_id', 'd.name')
    ->selectRaw('COALESCE(d.name, "Tanpa Asrama") as asrama, COUNT(*) as total')
    ->orderByDesc('total')
    ->limit(8)
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'kunjungan-per-dormitory',
    'label'  => 'Kunjungan per Asrama',
    'type'   => 'bar',
    'series' => [['name' => 'Kunjungan', 'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all()]],
    'options' => [
        'plotOptions' => ['bar' => ['horizontal' => true, 'borderRadius' => 4, 'columnWidth' => '55%']],
        'xaxis'       => ['categories' => $rows->pluck('asrama')->all()],
        'colors'      => ['#299cdb'],
        'dataLabels'  => ['enabled' => true],
    ],
];
