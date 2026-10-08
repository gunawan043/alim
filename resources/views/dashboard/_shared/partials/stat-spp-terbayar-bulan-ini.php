<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);

$period = now()->format('Y-m');
$count = 0;
$total = 0;

try {
    $query = DB::table('spp_bills')
        ->where('period', $period)
        ->whereIn('status', ['sebagian', 'lunas'])
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

    $count = (clone $query)->where('paid_amount', '>', 0)->count();
    $total = (clone $query)->sum('paid_amount');
} catch (\Throwable $e) {
    Log::warning('Widget stat-spp-terbayar-bulan-ini: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Pembayaran Bulan Ini',
    'icon'  => 'ri-hand-coin-line',
    'color' => 'success',
    'sub'   => 'Terbayar Rp ' . number_format((float) $total, 0, ',', '.'),
];
