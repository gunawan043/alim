<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('tahfidz_uthq_registrations as r')
    ->join('students as st', 'st.id', '=', 'r.student_id')
    ->leftJoin('tahfidz_uthq_events as e', 'e.id', '=', 'r.uthq_event_id')
    ->leftJoin('tahfidz_uthq_categories as c', 'c.id', '=', 'r.uthq_category_id')
    ->leftJoin('tahfidz_uthq_assessments as a', 'a.registration_id', '=', 'r.id')
    ->whereIn('r.status', ['terdaftar', 'lolos_audisi', 'finalis'])
    ->orderBy('r.registration_date')
    ->limit(10)
    ->get([
        'st.name as santri', 'e.name as event', 'c.name as kategori',
        'r.nomor_peserta', 'r.status', 'a.nilai_akhir', 'a.ranking',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Peserta UTHQ',
    'items' => $items,
];
