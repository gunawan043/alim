<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);

$total = 0;

try {
    $total = DB::table('guardian_complaints')
        ->where('status', 'diproses')
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-aduan-proses: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $total,
    'label' => 'Sedang Diproses',
    'icon'  => 'ri-loader-4-line',
    'color' => 'warning',
    'sub'   => 'Aduan dalam penanganan',
];
