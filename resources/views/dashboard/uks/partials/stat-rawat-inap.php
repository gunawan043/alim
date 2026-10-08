<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = $this->applyUksScope(
    DB::table('uks_patients')->where('in_bed', 1)->whereNull('discharged_at')
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId)),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Rawat Inap',
    'icon'  => 'ri-hotel-bed-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Pasien menempati bed UKS',
];
