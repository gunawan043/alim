<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('institution_decrees')
    ->whereNull('deleted_at')
    ->orderByDesc('issued_date')
    ->limit(10)
    ->get(['decree_number', 'decree_type', 'title', 'issued_date', 'effective_date', 'end_date', 'status'])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'SK Terbaru',
    'items' => $items,
];
