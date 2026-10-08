<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = $this->applyUksScope(
    DB::table('uks_patients as p')
        ->join('students as s', 's.id', '=', 'p.student_id')
        ->where('p.status', 'aktif')
        ->when($schoolId, fn ($q) => $q->where('p.school_id', $schoolId)),
    $user,
    'p.student_id'
)
    ->orderByDesc('p.in_bed')
    ->orderByDesc('p.admitted_at')
    ->limit(10)
    ->get([
        's.name as santri', 'p.chief_complaint', 'p.diagnosis', 'p.bed_number',
        'p.in_bed', 'p.admitted_at', 'p.status',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Pasien Aktif',
    'items' => $items,
];
