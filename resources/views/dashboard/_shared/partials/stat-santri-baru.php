<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$bulanIni = DB::table('students')
    ->where('status', 'active')
    ->whereYear('created_at', now()->year)
    ->whereMonth('created_at', now()->month)
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->count();

$bulanLalu = DB::table('students')
    ->where('status', 'active')
    ->whereYear('created_at', now()->subMonth()->year)
    ->whereMonth('created_at', now()->subMonth()->month)
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->count();

$trend = $bulanLalu > 0 ? round((($bulanIni - $bulanLalu) / $bulanLalu) * 100, 1) : 0;

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $bulanIni,
    'label' => 'Santri Baru',
    'icon'  => 'ri-user-add-line',
    'color' => 'warning',
    'trend' => $trend,
    'sub'   => 'Input data bulan ini',
];
