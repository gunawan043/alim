<?php

use Illuminate\Support\Facades\DB;

$rombel = $this->getHomeroomStudyGroup($user);

if (! $rombel) {
    return [
        '_col'  => 'col-xl-3 col-md-6',
        'value' => null,
        'label' => 'Nilai Belum Lengkap',
        'icon'  => 'ri-error-warning-line',
        'color' => 'primary',
        'sub'   => 'Anda belum ditetapkan sebagai wali kelas.',
    ];
}

$belum = DB::table('student_class_histories as h')
    ->where('h.study_group_id', $rombel->id)
    ->where('h.is_active', 1)
    ->whereNotExists(function ($q) use ($rombel) {
        $q->select(DB::raw(1))
            ->from('admin_nilai_sumatif as ns')
            ->join('teacher_admin_books as ab', 'ab.id', '=', 'ns.admin_book_id')
            ->whereColumn('ns.student_id', 'h.student_id')
            ->where('ab.study_group_id', $rombel->id)
            ->whereNotNull('ns.nr_final');
    })
    ->count();

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $belum,
    'label' => 'Nilai Belum Lengkap',
    'icon'  => 'ri-error-warning-line',
    'color' => $belum > 0 ? 'warning' : 'success',
    'sub'   => 'Santri tanpa nilai sumatif final',
];
