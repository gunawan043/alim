<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$rows = $this->applyUksScope(
    DB::table('uks_patients')
        ->whereYear('admitted_at', now()->year)
        ->whereMonth('admitted_at', now()->month)
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId)),
    $user
)
    ->selectRaw('status, COUNT(*) as total')
    ->groupBy('status')
    ->get();

$labels = ['aktif' => 'Aktif', 'selesai' => 'Selesai', 'dirujuk' => 'Dirujuk'];

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'pasien-per-status',
    'label'  => 'Pasien per Status (Bulan Ini)',
    'type'   => 'donut',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('status')->map(fn ($s) => $labels[$s] ?? ucfirst((string) $s))->all(),
    'colors' => ['#f06548', '#0ab39c', '#f7b84b'],
    'options' => [
        'legend'      => ['position' => 'bottom'],
        'plotOptions' => ['pie' => ['donut' => ['size' => '62%']]],
    ],
];
