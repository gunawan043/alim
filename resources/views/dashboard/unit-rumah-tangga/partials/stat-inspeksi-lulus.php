<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$row = DB::table('sanitation_inspections')
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->whereYear('inspection_date', now()->year)
    ->whereMonth('inspection_date', now()->month)
    ->selectRaw('SUM(CASE WHEN is_passed = 1 THEN 1 ELSE 0 END) as lulus, COUNT(*) as total')
    ->first();

$lulus = (int) ($row->lulus ?? 0);
$total = (int) ($row->total ?? 0);
$percent = $total > 0 ? round($lulus / $total * 100) : 0;

return [
    '_type'  => 'stat',
    '_col'   => 'col-xl-3 col-md-6',
    'value'  => $percent,
    'suffix' => '%',
    'label'  => 'Inspeksi Lulus',
    'icon'   => 'ri-checkbox-circle-line',
    'color'  => $percent >= 80 ? 'success' : ($percent >= 60 ? 'warning' : 'danger'),
    'sub'    => $lulus . ' dari ' . $total . ' inspeksi bulan ini',
];
