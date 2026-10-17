<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);

$rows = collect();

try {
    $rows = DB::table('grade_levels as gl')
        ->leftJoin('study_groups as sg', function ($join) {
            $join->on('sg.grade_level_id', '=', 'gl.id')->where('sg.is_active', 1);
        })
        ->leftJoin('student_class_histories as h', function ($join) {
            $join->on('h.study_group_id', '=', 'sg.id')->where('h.is_active', 1);
        })
        ->where('gl.is_active', 1)
        ->when($schoolId, fn ($q) => $q->where('gl.school_id', $schoolId))
        ->groupBy('gl.id', 'gl.name', 'gl.level')
        ->selectRaw('gl.name, MIN(gl.level) as level, COUNT(DISTINCT h.student_id) as total')
        ->orderBy('level')
        ->get();
} catch (\Throwable $e) {
    Log::warning('Widget chart-santri-per-tingkat: ' . $e->getMessage());
}

return [
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'santri-per-tingkat',
    'label'  => 'Rekap Santri per Tingkat',
    'type'   => 'bar',
    'series' => [['name' => 'Jumlah Santri', 'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all()]],
    'options' => [
        'xaxis'       => ['categories' => $rows->pluck('name')->all()],
        'colors'      => ['#405189'],
        'dataLabels'  => ['enabled' => false],
        'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '45%']],
    ],
];
