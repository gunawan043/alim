<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = $this->applyUksScope(
    DB::table('uks_patients')
        ->where(function ($q) {
            $q->where('status', 'dirujuk')->orWhere('referred_to_faskes', 1);
        })
        ->whereYear('admitted_at', now()->year)
        ->whereMonth('admitted_at', now()->month)
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId)),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Rujukan Bulan Ini',
    'icon'  => 'ri-hospital-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Dirujuk ke fasilitas kesehatan',
];
