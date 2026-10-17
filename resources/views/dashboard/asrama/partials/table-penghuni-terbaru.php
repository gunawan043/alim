<?php

use Illuminate\Support\Facades\DB;

$items = $this->scopeAsramaQuery(
    DB::table('dormitory_residents as res')
        ->join('students as s', 's.id', '=', 'res.student_id')
        ->leftJoin('dormitory_rooms as r', 'r.id', '=', 'res.room_id')
        ->leftJoin('dormitories as d', 'd.id', '=', 'res.dormitory_id'),
    $user,
    'res.room_id',
    'res.dormitory_id'
)
    ->orderByDesc('res.check_in_date')
    ->limit(10)
    ->get([
        's.name as santri', 'r.name as kamar', 'd.name as asrama',
        'res.bed_number', 'res.check_in_date', 'res.is_active',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Penghuni Terbaru',
    'items' => $items,
];
