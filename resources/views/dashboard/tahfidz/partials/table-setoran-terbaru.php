<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = $this->scopeTahfidzQuery(
    DB::table('tahfidz_setorans as s')
        ->join('students as st', 'st.id', '=', 's.student_id')
        ->leftJoin('tahfidz_groups as g', 'g.id', '=', 's.tahfidz_group_id')
        ->leftJoin('tahfidz_surah_master as sr', 'sr.id', '=', 's.surah_start_id')
        ->when($schoolId, fn ($q) => $q->where('g.school_id', $schoolId)),
    $user,
    's.tahfidz_group_id'
)
    ->orderByDesc('s.setoran_date')
    ->limit(10)
    ->get([
        'st.name as santri', 'g.name as halaqah', 's.setoran_date', 's.setoran_type',
        'sr.name_latin as surah', 's.ayat_start', 's.ayat_end', 's.juz',
        's.nilai_setoran', 's.status',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Setoran Terbaru',
    'items' => $items,
];
