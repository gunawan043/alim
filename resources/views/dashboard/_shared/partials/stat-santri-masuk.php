<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$awal = now()->startOfMonth()->toDateString();
$akhir = now()->endOfMonth()->toDateString();
$count = 0;

try {
    $count = DB::table('student_mutations_in')
        ->where('status', 'approved')
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->where(function ($q) use ($awal, $akhir) {
            $q->whereBetween('entry_date', [$awal, $akhir])
                ->orWhere(function ($sub) use ($awal, $akhir) {
                    $sub->whereNull('entry_date')
                        ->whereBetween('approved_at', [$awal . ' 00:00:00', $akhir . ' 23:59:59']);
                });
        })
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-santri-masuk: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Santri Masuk',
    'icon'  => 'ri-login-box-line',
    'color' => 'success',
    'sub'   => 'Mutasi masuk disetujui bulan ini',
];
