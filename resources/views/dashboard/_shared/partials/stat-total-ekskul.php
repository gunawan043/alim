<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$total = 0;

try {
    $total = DB::table('extracurriculars')
        ->where('is_active', 1)
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-total-ekskul: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $total,
    'label' => 'Ekstrakurikuler Aktif',
    'icon'  => 'ri-trophy-line',
    'color' => 'warning',
    'sub'   => 'Kegiatan aktif di unit ini',
];
