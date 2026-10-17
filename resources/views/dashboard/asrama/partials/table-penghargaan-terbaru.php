<?php

use Illuminate\Support\Facades\DB;

$items = $this->scopeAsramaQuery(
    DB::table('dormitory_rewards as rw')
        ->join('students as s', 's.id', '=', 'rw.student_id'),
    $user,
    'rw.dormitory_id',
    'rw.dormitory_id'
)
    ->orderByDesc('rw.awarded_date')
    ->limit(10)
    ->get([
        's.name as santri', 'rw.title', 'rw.category', 'rw.level', 'rw.awarded_date',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Penghargaan Terbaru',
    'items' => $items,
];
