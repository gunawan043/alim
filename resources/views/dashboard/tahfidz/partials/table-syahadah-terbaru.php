<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = DB::table('tahfidz_certificates as c')
    ->join('students as st', 'st.id', '=', 'c.student_id')
    ->when($schoolId, fn ($q) => $q->where('c.school_id', $schoolId))
    ->orderByDesc('c.issued_date')
    ->limit(10)
    ->get([
        'st.name as santri', 'c.certificate_type', 'c.certificate_number',
        'c.total_juz_completed', 'c.predikat', 'c.issued_date',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Syahadah Terbaru',
    'items' => $items,
];
