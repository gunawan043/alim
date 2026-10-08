<?php

use Illuminate\Support\Facades\DB;

$items = DB::table('cuti_requests as c')
    ->join('users as u', 'u.id', '=', 'c.user_id')
    ->leftJoin('cuti_templates as t', 't.id', '=', 'c.cuti_template_id')
    ->where('c.status', 'pending')
    ->orderBy('c.tanggal_mulai')
    ->limit(10)
    ->get([
        'u.name', 't.nama as jenis', 'c.tanggal_mulai', 'c.tanggal_selesai',
        'c.jumlah_hari', 'c.alasan',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Cuti Menunggu Persetujuan',
    'items' => $items,
];
