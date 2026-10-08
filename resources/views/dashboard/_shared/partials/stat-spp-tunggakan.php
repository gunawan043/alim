<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);

$count = 0;
$total = 0;

try {
    $query = DB::table('spp_bills')
        ->whereIn('status', ['belum_bayar', 'sebagian'])
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

    $count = (clone $query)->count();
    $total = (clone $query)->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as sisa')->value('sisa');
} catch (\Throwable $e) {
    Log::warning('Widget stat-spp-tunggakan: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Tagihan Belum Lunas',
    'icon'  => 'ri-money-dollar-circle-line',
    'color' => $count > 0 ? 'danger' : 'success',
    'sub'   => 'Tunggakan Rp ' . number_format((float) $total, 0, ',', '.'),
];
