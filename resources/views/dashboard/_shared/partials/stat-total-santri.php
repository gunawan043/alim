<?php

use Illuminate\Support\Facades\DB;

/** @var \App\Http\Controllers\Dashboard\DashboardController $this */
$schoolId = $this->getUserSchoolId($user);

$total = DB::table('students')
    ->where('status', 'active')
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->count();

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $total,
    'label' => 'Santri Aktif',
    'icon'  => 'ri-user-3-line',
    'color' => 'primary',
    'sub'   => 'Santri aktif di unit ini',
];
