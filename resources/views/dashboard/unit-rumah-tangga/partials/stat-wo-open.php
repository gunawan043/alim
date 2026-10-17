<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = DB::table('work_orders as w')
    ->leftJoin('assets as a', 'a.id', '=', 'w.asset_id')
    ->whereIn('w.status', ['pending', 'assigned', 'in_progress'])
    ->when($schoolId, fn ($q) => $q->where('a.school_id', $schoolId))
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Work Order Aktif',
    'icon'  => 'ri-customer-service-2-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Pending / dikerjakan',
];
