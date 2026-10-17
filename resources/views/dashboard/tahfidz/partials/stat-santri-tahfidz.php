<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = $this->scopeTahfidzQuery(
    DB::table('tahfidz_group_members as m')
        ->join('tahfidz_groups as g', 'g.id', '=', 'm.tahfidz_group_id')
        ->where('m.status', 'aktif')
        ->when($schoolId, fn ($q) => $q->where('g.school_id', $schoolId)),
    $user,
    'm.tahfidz_group_id'
)->distinct()->count('m.student_id');

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Santri Tahfidz',
    'icon'  => 'ri-user-3-line',
    'color' => 'primary',
    'sub'   => 'Anggota halaqah aktif',
];
