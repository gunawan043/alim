<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = $this->scopeTahfidzQuery(
    DB::table('tahfidz_mutabaah as m')
        ->join('students as st', 'st.id', '=', 'm.student_id')
        ->leftJoin('tahfidz_groups as g', 'g.id', '=', 'm.tahfidz_group_id')
        ->when($schoolId, fn ($q) => $q->where('g.school_id', $schoolId)),
    $user,
    'm.tahfidz_group_id'
)
    ->orderByDesc('m.record_date')
    ->limit(10)
    ->get([
        'st.name as santri', 'g.name as halaqah', 'm.record_date',
        'm.tilawah_halaman', 'm.tikror_mandiri_halaman', 'm.catatan_musyrif',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => "Mutaba'ah Terbaru",
    'items' => $items,
];
