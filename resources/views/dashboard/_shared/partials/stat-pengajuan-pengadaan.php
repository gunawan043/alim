<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

try {
    $pending = DB::table('procurement_requests')
        ->whereNull('deleted_at')
        ->whereNotIn('status', ['approved', 'rejected', 'completed', 'done', 'cancelled'])
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->count();

    $totalNilai = DB::table('procurement_requests')
        ->whereNull('deleted_at')
        ->whereNotIn('status', ['approved', 'rejected', 'completed', 'done', 'cancelled'])
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->sum('total_estimated_price');
} catch (\Throwable $e) {
    $pending = 0;
    $totalNilai = 0;
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $pending,
    'label' => 'Pengajuan Pengadaan',
    'icon'  => 'ri-shopping-cart-2-line',
    'color' => 'warning',
    'sub'   => 'Estimasi Rp ' . number_format((float) $totalNilai, 0, ',', '.'),
];
