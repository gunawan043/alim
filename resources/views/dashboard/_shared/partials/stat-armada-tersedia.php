<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);

$total = 0;

try {
    $total = DB::table('vehicles')
        ->where('is_active', 1)
        ->where('status', 'tersedia')
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-armada-tersedia: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $total,
    'label' => 'Armada Tersedia',
    'icon'  => 'ri-checkbox-circle-line',
    'color' => $total > 0 ? 'success' : 'secondary',
    'sub'   => 'Siap digunakan hari ini',
];
