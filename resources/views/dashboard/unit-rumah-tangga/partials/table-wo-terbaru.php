<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = DB::table('work_orders as w')
    ->leftJoin('assets as a', 'a.id', '=', 'w.asset_id')
    ->leftJoin('users as u', 'u.id', '=', 'w.assignee_id')
    ->when($schoolId, fn ($q) => $q->where('a.school_id', $schoolId))
    ->orderByDesc('w.created_at')
    ->limit(10)
    ->get([
        'w.order_number', 'a.asset_name', 'w.type', 'w.status',
        'w.scheduled_date', 'w.actual_end', 'u.name as teknisi',
    ])
    ->all();

return [
    "_type" => "table",
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Work Order Terbaru',
    'items' => $items,
];
