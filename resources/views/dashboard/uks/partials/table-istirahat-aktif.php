<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = $this->applyUksScope(
    DB::table('student_health_permits as hp')
        ->join('students as s', 's.id', '=', 'hp.student_id')
        ->whereIn('hp.status', ['approved', 'extended'])
        ->whereDate('hp.start_date', '<=', today())
        ->whereDate('hp.end_date', '>=', today())
        ->when($schoolId, fn ($q) => $q->where('hp.school_id', $schoolId)),
    $user,
    'hp.student_id'
)
    ->orderBy('hp.end_date')
    ->limit(10)
    ->get([
        's.name as santri', 'hp.permit_type', 'hp.start_date', 'hp.end_date',
        'hp.rest_days', 'hp.status',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Izin Istirahat Aktif',
    'items' => $items,
];
