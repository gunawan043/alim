<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = $this->scopeTahfidzQuery(
    DB::table('tahfidz_groups')
        ->where('is_active', 1)
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId)),
    $user,
    'id'
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Halaqah Aktif',
    'icon'  => 'ri-group-line',
    'color' => 'success',
    'sub'   => 'Kelompok tahfidz berjalan',
];
