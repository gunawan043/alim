<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = $this->scopeTahfidzQuery(
    DB::table('tahfidz_student_targets as t')
        ->join('students as st', 'st.id', '=', 't.student_id')
        ->leftJoin('tahfidz_groups as g', 'g.id', '=', 't.tahfidz_group_id')
        ->when($schoolId, fn ($q) => $q->where('g.school_id', $schoolId)),
    $user,
    't.tahfidz_group_id'
)
    ->orderByDesc('t.created_at')
    ->limit(10)
    ->get([
        'st.name as santri', 'g.name as halaqah', 't.semester',
        't.target_bulan', 't.target_halaman', 't.target_hadits',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Target Hafalan',
    'items' => $items,
];
