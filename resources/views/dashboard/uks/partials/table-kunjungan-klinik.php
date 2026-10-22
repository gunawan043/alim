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
        's.name as santri', 'p.queue_number', 'p.triage_level', 'p.chief_complaint',
        'p.diagnosis', 'p.queue_status', 'p.admitted_at',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Kunjungan Klinik Terbaru',
    'items' => $items,
];
