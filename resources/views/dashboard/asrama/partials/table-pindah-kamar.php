<?php

use Illuminate\Support\Facades\DB;

$items = $this->scopeAsramaQuery(
    DB::table('dormitory_room_moves as m')
        ->join('students as s', 's.id', '=', 'm.student_id')
        ->leftJoin('dormitory_rooms as fr', 'fr.id', '=', 'm.from_room_id')
        ->leftJoin('dormitory_rooms as tr', 'tr.id', '=', 'm.to_room_id')
        ->where('m.approval_status', 'pending'),
    $user,
    'm.from_room_id',
    'm.dormitory_id'
)
    ->orderByDesc('m.move_date')
    ->limit(10)
    ->get([
        's.name as santri', 'fr.name as dari', 'tr.name as ke',
        'm.move_date', 'm.move_type', 'm.reason',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Pindah Kamar Menunggu',
    'items' => $items,
];
