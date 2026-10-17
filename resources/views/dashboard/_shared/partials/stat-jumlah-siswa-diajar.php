<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$total = 0;

try {
    $total = DB::table('student_class_histories as h')
        ->join('jadwal_kbms as j', 'j.study_group_id', '=', 'h.study_group_id')
        ->join('students as st', 'st.id', '=', 'h.student_id')
        ->where('j.teacher_id', $user->id)
        ->where('j.is_active', 1)
        ->where('h.is_active', 1)
        ->where('st.status', 'active')
        ->distinct()
        ->count('h.student_id');
} catch (\Throwable $e) {
    Log::warning('Widget stat-jumlah-siswa-diajar: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $total,
    'label' => 'Santri Diajar',
    'icon'  => 'ri-group-2-line',
    'color' => 'primary',
    'sub'   => 'Dari seluruh kelas yang Anda ampu',
];
