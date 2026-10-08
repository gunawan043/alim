<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$items = [];

try {
    $items = DB::table('payroll as p')
        ->leftJoin('gtk_profiles as gp', 'gp.id', '=', 'p.gtk_id')
        ->leftJoin('users as u', 'u.id', '=', 'gp.user_id')
        ->orderByDesc('p.tahun')
        ->orderByDesc('p.bulan')
        ->orderByDesc('p.created_at')
        ->limit(10)
        ->get([
            'u.name', 'p.bulan', 'p.tahun', 'p.gaji_bersih',
            'p.status', 'p.tanggal_bayar',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-payroll-terbaru: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Payroll Terbaru',
    'items' => $items,
];
