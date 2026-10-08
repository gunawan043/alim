<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = DB::table('asset_maintenance_schedules')
    ->where('is_active', 1)
    ->whereNotNull('next_maintenance_date')
    ->whereDate('next_maintenance_date', '<=', now()->addDays(7))
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Maintenance Jatuh Tempo',
    'icon'  => 'ri-calendar-todo-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Jadwal ≤ 7 hari lagi',
];
