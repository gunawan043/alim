<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('gtk_position_proposals as p')
    ->join('users as u', 'u.id', '=', 'p.user_id')
    ->leftJoin('structural_positions as sp', 'sp.id', '=', 'p.proposed_position_id')
    ->orderByDesc('p.created_at')
    ->limit(10)
    ->get([
        'u.name', 'sp.name as jabatan_usulan', 'p.proposed_jabatan_text',
        'p.proposal_type', 'p.status', 'p.created_at',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Usulan Jabatan',
    'items' => $items,
];
