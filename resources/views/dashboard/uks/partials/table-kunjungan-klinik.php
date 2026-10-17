<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = $this->applyUksScope(
    DB::table('student_clinic_visits as v')
        ->join('students as s', 's.id', '=', 'v.student_id')
        ->when($schoolId, fn ($q) => $q->where('v.school_id', $schoolId)),
    $user,
    'v.student_id'
)
    ->orderByDesc('v.visit_date')
    ->limit(10)
    ->get([
        's.name as santri', 'v.complaint', 'v.diagnosis', 'v.is_referred',
        'v.referral_hospital', 'v.visit_date',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Kunjungan Klinik Terbaru',
    'items' => $items,
];
