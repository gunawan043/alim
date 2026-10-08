<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = DB::table('tahfidz_tasmian_scores as sc')
    ->join('students as st', 'st.id', '=', 'sc.student_id')
    ->leftJoin('tahfidz_tasmian_sessions as ts', 'ts.id', '=', 'sc.tasmian_session_id')
    ->leftJoin('users as ev', 'ev.id', '=', 'sc.evaluator_id')
    ->when($schoolId, fn ($q) => $q->where('ts.school_id', $schoolId))
    ->orderByDesc('sc.created_at')
    ->limit(10)
    ->get([
        'st.name as santri', 'ts.session_name', 'ts.session_date',
        'sc.nilai_akhir', 'sc.predikat', 'ev.name as penguji',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => "Hasil Tasmi' Terbaru",
    'items' => $items,
];
