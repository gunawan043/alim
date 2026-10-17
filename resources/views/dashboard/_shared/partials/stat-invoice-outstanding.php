<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$count = 0;
$total = 0;

try {
    $query = DB::table('invoice_approvals')
        ->whereNotIn('status', ['paid', 'rejected', 'cancelled']);

    $count = (clone $query)->count();
    $total = (clone $query)->sum('total_amount');
} catch (\Throwable $e) {
    Log::warning('Widget stat-invoice-outstanding: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Invoice Belum Lunas',
    'icon'  => 'ri-file-list-2-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Nilai Rp ' . number_format((float) $total, 0, ',', '.'),
];
