<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('spareparts as s')
    ->leftJoin('warehouses as w', 'w.id', '=', 's.warehouse_id')
    ->where('s.is_active', 1)
    ->orderByRaw('CASE WHEN s.stock <= s.min_stock THEN 0 ELSE 1 END')
    ->orderBy('s.stock')
    ->limit(12)
    ->get([
        's.part_number', 's.name', 'w.name as gudang', 's.stock',
        's.min_stock', 's.reorder_point', 's.unit_price',
    ])
    ->all();

return [
    "_type" => "table",
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Stok Sparepart',
    'items' => $items,
];
