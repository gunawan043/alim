<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);

$total = 0;

try {
    $total = DB::table('guardian_complaints')
        ->where('status', 'selesai')
        ->whereYear('created_at', now()->year)
        ->whereMonth('created_at', now()->month)
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-aduan-selesai-bulan: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $total,
    'label' => 'Selesai Bulan Ini',
    'icon'  => 'ri-checkbox-circle-line',
    'color' => 'success',
    'sub'   => 'Aduan tuntas ditangani',
];
