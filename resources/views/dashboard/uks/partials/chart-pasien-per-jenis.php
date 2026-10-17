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
    ->selectRaw("COALESCE(NULLIF(patient_type, ''), 'lainnya') as jenis, COUNT(*) as total")
    ->groupBy('jenis')
    ->get();

$labels = ['rawat' => 'Rawat', 'pulang' => 'Pulang', 'balik' => 'Balik'];

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'pasien-per-jenis',
    'label'  => 'Pasien per Jenis (Bulan Ini)',
    'type'   => 'pie',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('jenis')->map(fn ($s) => $labels[$s] ?? ucfirst((string) $s))->all(),
    'colors' => ['#405189', '#299cdb', '#f7b84b'],
    'options' => [
        'legend' => ['position' => 'bottom'],
    ],
];
