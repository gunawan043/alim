<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$awal = now()->startOfMonth()->toDateString();
$akhir = now()->endOfMonth()->toDateString();
$count = 0;
$sub = 'Mutasi keluar disetujui bulan ini';

try {
    $query = DB::table('student_mutations_out')
        ->where('status', 'approved')
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
        ->whereBetween('approved_at', [$awal . ' 00:00:00', $akhir . ' 23:59:59']);

    $count = (clone $query)->count();

    $labels = [
        'mutation'   => 'Mutasi',
        'dropout'    => 'Dropout',
        'graduation' => 'Lulus',
    ];

    $breakdown = (clone $query)
        ->selectRaw('out_type, COUNT(*) as total')
        ->groupBy('out_type')
        ->pluck('total', 'out_type')
        ->map(fn ($total, $type) => ($labels[$type] ?? ucfirst($type)) . ' ' . $total)
        ->values()
        ->all();

    if ($breakdown) {
        $sub = implode(' · ', $breakdown);
    }
} catch (\Throwable $e) {
    Log::warning('Widget stat-santri-keluar: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Santri Keluar',
    'icon'  => 'ri-logout-box-line',
    'color' => 'danger',
    'sub'   => $sub,
];
