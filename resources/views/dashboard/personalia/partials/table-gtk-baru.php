<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('gtk_profiles as gp')
    ->join('users as u', 'u.id', '=', 'gp.user_id')
    ->orderByDesc('gp.created_at')
    ->limit(10)
    ->get(['u.name', 'gp.nik', 'gp.jabatan', 'gp.tmt_kerja', 'gp.created_at'])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'GTK Terbaru',
    'items' => $items,
];
