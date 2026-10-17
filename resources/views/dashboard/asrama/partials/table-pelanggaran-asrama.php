<?php

use Illuminate\Support\Facades\DB;

$items = $this->scopeAsramaQuery(
    DB::table('dormitory_violations as v')
        ->join('students as s', 's.id', '=', 'v.student_id')
        ->leftJoin('dormitory_rooms as r', 'r.id', '=', 'v.room_id'),
    $user,
    'v.room_id',
    'v.dormitory_id'
)
    ->orderByDesc('v.violation_date')
    ->limit(10)
    ->get([
        's.name as santri', 'r.name as kamar', 'v.violation_date',
        'v.violation_type', 'v.points', 'v.follow_up',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Pelanggaran Terbaru',
    'items' => $items,
];
