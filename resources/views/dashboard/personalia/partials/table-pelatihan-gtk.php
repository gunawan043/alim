<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('gtk_trainings as tr')
    ->leftJoin('users as u', 'u.id', '=', 'tr.user_id')
    ->orderByDesc('tr.created_at')
    ->limit(10)
    ->get([
        'u.name', 'tr.nama_pelatihan', 'tr.bidang_pelatihan', 'tr.penyelenggara', 'tr.tahun',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Pelatihan GTK Terbaru',
    'items' => $items,
];
