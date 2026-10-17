<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = DB::table('work_order_progress as p')
    ->join('work_orders as w', 'w.id', '=', 'p.work_order_id')
    ->leftJoin('assets as a', 'a.id', '=', 'w.asset_id')
    ->leftJoin('users as u', 'u.id', '=', 'p.performed_by')
    ->when($schoolId, fn ($q) => $q->where('a.school_id', $schoolId))
    ->orderByDesc('p.created_at')
    ->limit(10)
    ->get([
        'w.order_number', 'p.step_number', 'p.status', 'p.description',
        'u.name as petugas', 'p.completed_at',
    ])
    ->all();

return [
    "_type" => "table",
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Progress Pengerjaan Terbaru',
    'items' => $items,
];
