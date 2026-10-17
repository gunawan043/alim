<?php

use Illuminate\Support\Facades\DB;

$labels = DB::table('permit_types')->pluck('label', 'code');

$items = $this->scopeAsramaQuery(
    DB::table('dormitory_permits as p')
        ->join('students as s', 's.id', '=', 'p.student_id')
        ->leftJoin('dormitory_rooms as r', 'r.id', '=', 'p.room_id')
        ->where('p.status', 'pending'),
    $user,
    'p.room_id',
    'p.dormitory_id'
)
    ->orderBy('p.departure_datetime')
    ->limit(10)
    ->get([
        's.name as santri', 'p.permit_type', 'p.departure_datetime',
        'p.expected_return_datetime', 'p.purpose', 'r.name as kamar',
    ])
    ->map(function ($row) use ($labels) {
        $row->jenis = $labels[$row->permit_type] ?? ucfirst((string) $row->permit_type);

        return $row;
    })
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Izin Menunggu Persetujuan',
    'items' => $items,
];
