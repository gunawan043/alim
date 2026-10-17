<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('gtk_pensions as p')
    ->join('users as u', 'u.id', '=', 'p.user_id')
    ->orderBy('p.planned_pension_date')
    ->limit(10)
    ->get([
        'u.name', 'p.planned_pension_date', 'p.pension_type',
        'p.pension_letter_no', 'p.pension_status',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Rencana Pensiun',
    'items' => $items,
];
