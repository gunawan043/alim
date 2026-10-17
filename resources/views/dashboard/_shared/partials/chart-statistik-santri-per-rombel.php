<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);
$academicYearId = $this->getActiveAcademicYearId();

$rows = DB::table('study_groups as sg')
    ->leftJoin('student_class_histories as h', function ($join) {
        $join->on('h.study_group_id', '=', 'sg.id')->where('h.is_active', 1);
    })
    ->where('sg.is_active', 1)
    ->when($schoolId, fn ($q) => $q->where('sg.school_id', $schoolId))
    ->when($academicYearId, fn ($q) => $q->where('sg.academic_year_id', $academicYearId))
    ->groupBy('sg.id', 'sg.name')
    ->selectRaw('sg.name, COUNT(h.id) as total')
    ->orderBy('sg.name')
    ->limit(12)
    ->get();

return [
    '_col'   => 'col-xl-8 col-md-12',
    'key'    => 'santri-per-rombel',
    'label'  => 'Statistik Santri per Rombel',
    'type'   => 'bar',
    'series' => [['name' => 'Jumlah Santri', 'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all()]],
    'options' => [
        'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '45%']],
        'xaxis'       => ['categories' => $rows->pluck('name')->all()],
        'colors'      => ['#299cdb'],
        'dataLabels'  => ['enabled' => false],
    ],
];
