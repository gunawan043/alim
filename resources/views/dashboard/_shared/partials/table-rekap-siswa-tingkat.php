<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$items = [];

try {
    $items = DB::table('grade_levels as gl')
        ->leftJoin('study_groups as sg', function ($join) {
            $join->on('sg.grade_level_id', '=', 'gl.id')->where('sg.is_active', 1);
        })
        ->leftJoin('student_class_histories as h', function ($join) {
            $join->on('h.study_group_id', '=', 'sg.id')->where('h.is_active', 1);
        })
        ->where('gl.is_active', 1)
        ->when($schoolId, fn ($q) => $q->where('gl.school_id', $schoolId))
        ->groupBy('gl.id', 'gl.name', 'gl.level')
        ->selectRaw('gl.name, MIN(gl.level) as level, COUNT(DISTINCT sg.id) as rombel, COUNT(DISTINCT h.student_id) as santri')
        ->orderBy('level')
        ->get()
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-rekap-siswa-tingkat: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Rekap Rombel & Santri per Tingkat',
    'items' => $items,
];
