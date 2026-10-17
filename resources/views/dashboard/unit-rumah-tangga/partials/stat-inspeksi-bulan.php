<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = DB::table('sanitation_inspections')
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->whereYear('inspection_date', now()->year)
    ->whereMonth('inspection_date', now()->month)
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Inspeksi Kebersihan',
    'icon'  => 'ri-brush-line',
    'color' => 'info',
    'sub'   => 'Inspeksi bulan ini',
];
