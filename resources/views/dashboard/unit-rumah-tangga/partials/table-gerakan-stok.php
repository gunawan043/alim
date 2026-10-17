<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('sparepart_stock_movements as m')
    ->leftJoin('spareparts as s', 's.id', '=', 'm.sparepart_id')
    ->leftJoin('users as u', 'u.id', '=', 'm.performed_by')
    ->orderByDesc('m.occurred_at')
    ->limit(10)
    ->get([
        'm.movement_code', 's.name as sparepart', 'm.movement_type', 'm.quantity',
        'm.balance_after', 'u.name as petugas', 'm.occurred_at',
    ])
    ->all();

return [
    "_type" => "table",
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Pergerakan Stok Terbaru',
    'items' => $items,
];
