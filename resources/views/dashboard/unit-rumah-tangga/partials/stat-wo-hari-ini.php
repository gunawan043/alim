<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = DB::table('work_orders as w')
    ->leftJoin('assets as a', 'a.id', '=', 'w.asset_id')
    ->whereDate('w.created_at', today())
    ->when($schoolId, fn ($q) => $q->where('a.school_id', $schoolId))
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Laporan Hari Ini',
    'icon'  => 'ri-add-box-line',
    'color' => 'primary',
    'sub'   => 'Work order dibuat hari ini',
];
