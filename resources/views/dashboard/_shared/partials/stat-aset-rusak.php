<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$total = 0;

try {
    $total = DB::table('assets')
        ->whereNull('deleted_at')
        ->where('is_active', 1)
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->where(function ($q) {
            $q->where('condition', 'like', '%rusak%')
                ->orWhere('status', 'like', '%rusak%');
        })
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-aset-rusak: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $total,
    'label' => 'Aset Rusak',
    'icon'  => 'ri-tools-line',
    'color' => $total > 0 ? 'danger' : 'success',
    'sub'   => 'Perlu perbaikan / tindak lanjut',
];
