<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$count = 0;

try {
    $count = DB::table('supervisi')
        ->whereNull('deleted_at')
        ->whereIn('status', ['terjadwal', 'berlangsung'])
        ->where('tanggal_supervisi', '>=', today()->toDateString())
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-supervisi-mendatang: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Supervisi Mendatang',
    'icon'  => 'ri-presentation-line',
    'color' => $count > 0 ? 'info' : 'success',
    'sub'   => 'Jadwal supervisi belum terlaksana',
];
