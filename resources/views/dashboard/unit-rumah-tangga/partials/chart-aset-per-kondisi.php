<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$rows = DB::table('assets')
    ->whereNull('deleted_at')
    ->where('is_active', 1)
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->selectRaw("COALESCE(NULLIF(`condition`, ''), 'baik') as kondisi, COUNT(*) as total")
    ->groupBy('kondisi')
    ->get();

$labels = [
    'baik' => 'Baik', 'rusak_ringan' => 'Rusak Ringan', 'rusak_sedang' => 'Rusak Sedang',
    'rusak_berat' => 'Rusak Berat', 'hilang' => 'Hilang', 'dihapus' => 'Dihapus',
];

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'aset-per-kondisi',
    'label'  => 'Kondisi Aset',
    'type'   => 'pie',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('kondisi')->map(fn ($k) => $labels[$k] ?? ucfirst((string) $k))->all(),
    'colors' => ['#0ab39c', '#f7b84b', '#f06548', '#d63384', '#6c757d', '#495057'],
    'options' => [
        'legend' => ['position' => 'bottom'],
    ],
];
