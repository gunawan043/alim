<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$items = [];

try {
    $items = DB::table('violation_points as v')
        ->join('students as s', 's.id', '=', 'v.student_id')
        ->leftJoin('study_groups as sg', 'sg.id', '=', 'v.study_group_id')
        ->when($schoolId, fn ($q) => $q->where('s.school_id', $schoolId))
        ->orderByDesc('v.violation_date')
        ->limit(10)
        ->get([
            's.name as santri', 'sg.name as kelas',
            'v.violation_type', 'v.points', 'v.violation_date', 'v.action_taken',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-pelanggaran-terbaru: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Pelanggaran Terbaru',
    'items' => $items,
];
