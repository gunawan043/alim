<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = $this->scopeTahfidzQuery(
    DB::table('tahfidz_group_attendances as a')
        ->join('students as st', 'st.id', '=', 'a.student_id')
        ->leftJoin('tahfidz_groups as g', 'g.id', '=', 'a.tahfidz_group_id')
        ->when($schoolId, fn ($q) => $q->where('g.school_id', $schoolId)),
    $user,
    'a.tahfidz_group_id'
)
    ->orderByDesc('a.attendance_date')
    ->limit(10)
    ->get([
        'st.name as santri', 'g.name as halaqah', 'a.attendance_date',
        'a.session_type', 'a.status',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Kehadiran Halaqah Terbaru',
    'items' => $items,
];
