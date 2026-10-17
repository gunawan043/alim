<?php

use Illuminate\Support\Facades\DB;

$rombel = $this->getHomeroomStudyGroup($user);

if (! $rombel) {
    return [
        '_col'  => 'col-xl-3 col-md-6',
        'value' => null,
        'label' => 'Rata-rata Nilai Kelas',
        'icon'  => 'ri-bar-chart-fill',
        'color' => 'primary',
        'sub'   => 'Anda belum ditetapkan sebagai wali kelas.',
    ];
}

$avg = DB::table('admin_nilai_sumatif as ns')
    ->join('teacher_admin_books as ab', 'ab.id', '=', 'ns.admin_book_id')
    ->where('ab.study_group_id', $rombel->id)
    ->whereNotNull('ns.nr_final')
    ->avg('ns.nr_final');

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $avg !== null ? round((float) $avg, 1) : null,
    'label' => 'Rata-rata Nilai Kelas',
    'icon'  => 'ri-bar-chart-fill',
    'color' => 'info',
    'sub'   => 'Nilai sumatif final kelas ' . $rombel->name,
];
