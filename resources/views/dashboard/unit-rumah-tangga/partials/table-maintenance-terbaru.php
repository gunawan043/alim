<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = DB::table('asset_maintenance_logs as l')
    ->leftJoin('assets as a', 'a.id', '=', 'l.asset_id')
    ->when($schoolId, fn ($q) => $q->where('l.school_id', $schoolId))
    ->orderByDesc('l.maintenance_date')
    ->limit(10)
    ->get([
        'a.asset_name', 'l.log_type', 'l.maintenance_date', 'l.status',
        'l.cost', 'l.vendor_name',
    ])
    ->all();

return [
    "_type" => "table",
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Log Pemeliharaan Terbaru',
    'items' => $items,
];
