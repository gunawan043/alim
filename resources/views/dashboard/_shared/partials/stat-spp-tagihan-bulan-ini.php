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
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

    $count = (clone $query)->count();
    $total = (clone $query)->sum('amount');
} catch (\Throwable $e) {
    Log::warning('Widget stat-spp-tagihan-bulan-ini: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Tagihan Bulan Ini',
    'icon'  => 'ri-file-list-3-line',
    'color' => 'primary',
    'sub'   => 'Total Rp ' . number_format((float) $total, 0, ',', '.'),
];
