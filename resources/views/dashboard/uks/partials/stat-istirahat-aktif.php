<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = $this->applyUksScope(
    DB::table('student_health_permits')
        ->where('status', 'approved')
        ->whereDate('start_date', '<=', today())
        ->whereDate('end_date', '>=', today())
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId)),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Izin Istirahat Aktif',
    'icon'  => 'ri-zzz-line',
    'color' => 'info',
    'sub'   => 'Santri istirahat/isolasi hari ini',
];
