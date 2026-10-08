<?php

use Illuminate\Support\Facades\DB;

$items = $this->scopeAsramaQuery(
    DB::table('dormitory_inventories as inv')
        ->leftJoin('dormitory_rooms as r', 'r.id', '=', 'inv.room_id')
        ->whereIn('inv.condition', ['rusak', 'hilang']),
    $user,
    'inv.room_id',
    'inv.dormitory_id'
)
    ->orderBy('inv.room_id')
    ->limit(10)
    ->get([
        'r.name as kamar', 'inv.item_name', 'inv.quantity',
        'inv.condition', 'inv.last_checked_at',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Inventaris Rusak / Hilang',
    'items' => $items,
];
