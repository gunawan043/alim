<?php

use Illuminate\Support\Facades\DB;

$items = $this->scopeAsramaQuery(
    DB::table('dormitory_violations as v')
        ->join('students as s', 's.id', '=', 'v.student_id')
        ->whereYear('v.violation_date', now()->year)
        ->whereMonth('v.violation_date', now()->month),
    $user,
    'v.room_id',
    'v.dormitory_id'
)
    ->groupBy('v.student_id', 's.name')
    ->selectRaw('s.name as santri, SUM(v.points) as poin, COUNT(*) as jumlah')
    ->orderByDesc('poin')
    ->limit(5)
    ->get()
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Poin Pelanggaran Tertinggi (Bulan Ini)',
    'items' => $items,
];
