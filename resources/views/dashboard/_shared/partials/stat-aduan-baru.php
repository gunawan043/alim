<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);

$total = 0;

try {
    $total = DB::table('guardian_complaints')
        ->where('status', 'baru')
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-aduan-baru: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $total,
    'label' => 'Aduan Baru',
    'icon'  => 'ri-customer-service-2-line',
    'color' => $total > 0 ? 'danger' : 'success',
    'sub'   => 'Menunggu tindak lanjut',
];
