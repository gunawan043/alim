<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$count = 0;
$soal = 0;

try {
    $query = DB::table('bank_soal')
        ->whereNull('deleted_at')
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

    $count = (clone $query)->count();
    $soal = (int) (clone $query)->sum('total_soal');
} catch (\Throwable $e) {
    Log::warning('Widget stat-bank-soal-rumpun: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Bank Soal Rumpun',
    'icon'  => 'ri-folder-open-line',
    'color' => 'info',
    'sub'   => number_format($soal, 0, ',', '.') . ' total soal',
];
