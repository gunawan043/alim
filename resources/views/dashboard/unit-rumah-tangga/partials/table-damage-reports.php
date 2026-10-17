<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = DB::table('asset_damage_reports as d')
    ->leftJoin('assets as a', 'a.id', '=', 'd.asset_id')
    ->leftJoin('users as u', 'u.id', '=', 'd.reported_by')
    ->when($schoolId, fn ($q) => $q->where('d.school_id', $schoolId))
    ->orderByDesc('d.created_at')
    ->limit(10)
    ->get([
        'd.report_number', 'a.asset_name', 'd.damage_level', 'd.status',
        'u.name as pelapor', 'd.created_at',
    ])
    ->all();

return [
    "_type" => "table",
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Laporan Kerusakan Terbaru',
    'items' => $items,
];
