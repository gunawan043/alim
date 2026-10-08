<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = $this->scopeTahfidzQuery(
    DB::table('tahfidz_progress_recaps as r')
        ->join('students as st', 'st.id', '=', 'r.student_id')
        ->leftJoin('tahfidz_groups as g', 'g.id', '=', 'r.tahfidz_group_id')
        ->when($schoolId, fn ($q) => $q->where('g.school_id', $schoolId)),
    $user,
    'r.tahfidz_group_id'
)
    ->orderByDesc('r.total_juz_completed')
    ->limit(10)
    ->get([
        'st.name as santri', 'g.name as halaqah', 'r.total_juz_completed',
        'r.total_halaman_ziyadah', 'r.total_setoran', 'r.rata_rata_nilai',
        'r.pencapaian_target_persen',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Progres Santri',
    'items' => $items,
];
