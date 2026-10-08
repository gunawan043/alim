<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = DB::table('asset_maintenance_schedules as s')
    ->leftJoin('assets as a', 'a.id', '=', 's.asset_id')
    ->when($schoolId, fn ($q) => $q->where('s.school_id', $schoolId))
    ->where('s.is_active', 1)
    ->whereNotNull('s.next_maintenance_date')
    ->orderBy('s.next_maintenance_date')
    ->limit(10)
    ->get([
        'a.asset_name', 's.maintenance_type', 's.frequency', 's.next_maintenance_date',
        's.estimated_cost', 's.status',
    ])
    ->all();

return [
    "_type" => "table",
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Jadwal Pemeliharaan',
    'items' => $items,
];
