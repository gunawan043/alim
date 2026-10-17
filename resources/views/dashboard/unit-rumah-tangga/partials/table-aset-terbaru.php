<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = DB::table('assets as a')
    ->leftJoin('asset_categories as c', 'c.id', '=', 'a.asset_category_id')
    ->leftJoin('asset_rooms as r', 'r.id', '=', 'a.room_id')
    ->whereNull('a.deleted_at')
    ->when($schoolId, fn ($q) => $q->where('a.school_id', $schoolId))
    ->orderByDesc('a.created_at')
    ->limit(10)
    ->get([
        'a.asset_code', 'a.asset_name', 'c.name as kategori',
        'r.room_name as ruang', 'a.condition', 'a.created_at',
    ])
    ->all();

return [
    "_type" => "table",
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Aset Terbaru',
    'items' => $items,
];
