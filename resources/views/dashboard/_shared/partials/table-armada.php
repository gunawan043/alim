<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);

$items = [];

try {
    $items = DB::table('vehicles as v')
        ->leftJoin('users as d', 'd.id', '=', 'v.driver_id')
        ->where('v.is_active', 1)
        ->when($schoolId, fn ($q) => $q->where('v.school_id', $schoolId))
        ->orderBy('v.name')
        ->limit(10)
        ->get([
            'v.plate_number as plat',
            'v.name as nama',
            'v.type as jenis',
            'd.name as pengemudi',
            'v.next_service_date',
            'v.status',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-armada: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Daftar Armada',
    'items' => $items,
];
