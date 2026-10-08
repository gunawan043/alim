<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = $this->applyUksScope(
    DB::table('uks_treatments as t')
        ->join('uks_patients as p', 'p.id', '=', 't.patient_id')
        ->join('students as s', 's.id', '=', 'p.student_id')
        ->leftJoin('users as u', 'u.id', '=', 't.performed_by')
        ->when($schoolId, fn ($q) => $q->where('p.school_id', $schoolId)),
    $user,
    'p.student_id'
)
    ->orderByDesc('t.created_at')
    ->limit(10)
    ->get([
        's.name as santri', 't.diagnosis', 't.treatment', 'u.name as petugas', 't.created_at',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Tindakan Terbaru',
    'items' => $items,
];
