<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = DB::table('assets')
    ->whereNull('deleted_at')
    ->where('is_active', 1)
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Total Aset',
    'icon'  => 'ri-archive-2-line',
    'color' => 'primary',
    'sub'   => 'Aset aktif terdata',
];
