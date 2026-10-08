<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$items = [];

try {
    $items = DB::table('dormitory_permits as p')
        ->join('students as s', 's.id', '=', 'p.student_id')
        ->when($schoolId, fn ($q) => $q->where('s.school_id', $schoolId))
        ->orderByDesc('p.departure_datetime')
        ->limit(10)
        ->get([
            's.name', 'p.permit_type', 'p.departure_datetime',
            'p.expected_return_datetime', 'p.actual_return_datetime', 'p.status',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-izin-terbaru: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Perizinan Santri Terbaru',
    'items' => $items,
];
