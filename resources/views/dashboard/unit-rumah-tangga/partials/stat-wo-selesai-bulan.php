<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = DB::table('work_orders as w')
    ->leftJoin('assets as a', 'a.id', '=', 'w.asset_id')
    ->whereIn('w.status', ['completed', 'closed'])
    ->whereYear('w.actual_end', now()->year)
    ->whereMonth('w.actual_end', now()->month)
    ->when($schoolId, fn ($q) => $q->where('a.school_id', $schoolId))
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Selesai Bulan Ini',
    'icon'  => 'ri-check-double-line',
    'color' => 'success',
    'sub'   => 'Work order tuntas',
];
