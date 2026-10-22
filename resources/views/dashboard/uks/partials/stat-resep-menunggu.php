<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = DB::table('uks_prescriptions')
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->whereIn('status', ['baru', 'disiapkan'])
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Resep Menunggu',
    'icon'  => 'ri-capsule-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Belum diserahkan farmasi',
];
