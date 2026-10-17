<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);

$total = 0;

try {
    $total = DB::table('vehicles')
        ->where('is_active', 1)
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->where(function ($q) {
            $q->where('status', 'perawatan')
                ->orWhere(function ($q2) {
                    $q2->whereNotNull('next_service_date')
                        ->whereDate('next_service_date', '<=', now()->addDays(30));
                });
        })
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-armada-servis: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $total,
    'label' => 'Servis / Perawatan',
    'icon'  => 'ri-tools-line',
    'color' => $total > 0 ? 'warning' : 'success',
    'sub'   => 'Perawatan atau servis ≤ 30 hari',
];
