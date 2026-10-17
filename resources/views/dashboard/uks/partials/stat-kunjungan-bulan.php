<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$base = fn () => $this->applyUksScope(
    DB::table('uks_patients')
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId)),
    $user
);

$bulanIni = $base()->whereYear('admitted_at', now()->year)->whereMonth('admitted_at', now()->month)->count();
$bulanLalu = $base()->whereYear('admitted_at', now()->subMonth()->year)->whereMonth('admitted_at', now()->subMonth()->month)->count();
$trend = $bulanLalu > 0 ? round((($bulanIni - $bulanLalu) / $bulanLalu) * 100, 1) : 0;

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $bulanIni,
    'label' => 'Kunjungan Bulan Ini',
    'icon'  => 'ri-line-chart-line',
    'color' => 'info',
    'trend' => $trend,
    'sub'   => 'Bulan lalu ' . $bulanLalu . ' kunjungan',
];
