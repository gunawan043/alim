<?php

use Illuminate\Support\Facades\DB;

$items = $this->scopeAsramaQuery(
    DB::table('dormitory_visit_logs as vl')
        ->leftJoin('students as s', 's.id', '=', 'vl.student_id'),
    $user,
    'vl.room_id',
    'vl.dormitory_id'
)
    ->orderByDesc('vl.created_at')
    ->limit(10)
    ->get([
        's.name as santri', 'vl.visitor_name', 'vl.visitor_relationship',
        'vl.purpose', 'vl.expected_arrival_datetime', 'vl.status',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Kunjungan Terbaru',
    'items' => $items,
];
