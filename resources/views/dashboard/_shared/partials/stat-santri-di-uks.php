<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$count = 0;

try {
    $count = DB::table('uks_patients')
        ->whereNull('discharged_at')
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-santri-di-uks: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Santri di UKS',
    'icon'  => 'ri-heart-pulse-line',
    'color' => $count > 0 ? 'danger' : 'success',
    'sub'   => 'Pasien rawat/observasi aktif',
];
