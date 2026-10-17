<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$items = [];

try {
    $items = DB::table('uks_patients as p')
        ->join('students as s', 's.id', '=', 'p.student_id')
        ->when($schoolId, fn ($q) => $q->where('p.school_id', $schoolId))
        ->orderByDesc('p.admitted_at')
        ->limit(10)
        ->get([
            's.name', 'p.chief_complaint', 'p.diagnosis', 'p.bed_number',
            'p.status', 'p.in_bed', 'p.admitted_at', 'p.discharged_at',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-uks-terbaru: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Kunjungan UKS Terbaru',
    'items' => $items,
];
