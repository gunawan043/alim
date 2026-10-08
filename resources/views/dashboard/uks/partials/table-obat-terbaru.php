<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = $this->applyUksScope(
    DB::table('uks_medication_logs as m')
        ->join('uks_patients as p', 'p.id', '=', 'm.patient_id')
        ->join('students as s', 's.id', '=', 'p.student_id')
        ->when($schoolId, fn ($q) => $q->where('p.school_id', $schoolId)),
    $user,
    'p.student_id'
)
    ->orderByDesc('m.given_at')
    ->limit(10)
    ->get([
        's.name as santri', 'm.medicine_name', 'm.dosage', 'm.route', 'm.given_at',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Pemberian Obat Terbaru',
    'items' => $items,
];
