<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = $this->applyUksScope(
    DB::table('uks_patients as p')
        ->join('students as s', 's.id', '=', 'p.student_id')
        ->when($schoolId, fn ($q) => $q->where('p.school_id', $schoolId)),
    $user,
    'p.student_id'
)
    ->orderByDesc('p.admitted_at')
    ->limit(10)
    ->get([
        's.name as santri', 'p.patient_type', 'p.chief_complaint',
        'p.admitted_at', 'p.status',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Kunjungan Terbaru',
    'items' => $items,
];
