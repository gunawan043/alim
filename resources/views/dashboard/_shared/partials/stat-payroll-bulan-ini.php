<?php

use Illuminate\Support\Facades\DB;

$bulan = now()->month;
$tahun = now()->year;

try {
    $query = DB::table('payroll')->where('bulan', $bulan)->where('tahun', $tahun);

    $count = (clone $query)->count();
    $total = (clone $query)->sum('gaji_bersih');
} catch (\Throwable $e) {
    $count = 0;
    $total = 0;
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Payroll Bulan Ini',
    'icon'  => 'ri-money-dollar-circle-line',
    'color' => 'success',
    'sub'   => 'Total Rp ' . number_format((float) $total, 0, ',', '.'),
];
