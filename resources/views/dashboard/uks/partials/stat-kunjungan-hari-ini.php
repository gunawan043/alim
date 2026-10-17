<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = $this->applyUksScope(
    DB::table('uks_patients')->whereDate('admitted_at', today())
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId)),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Kunjungan Hari Ini',
    'icon'  => 'ri-calendar-check-line',
    'color' => 'primary',
    'sub'   => 'Pasien masuk UKS hari ini',
];
