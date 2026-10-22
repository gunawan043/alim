<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);
$activeStatuses = [
    'menunggu_pemeriksaan',
    'sedang_ditangani',
    'observasi',
    'rawat_uks',
    'istirahat_di_uks',
];

$base = fn () => $this->applyUksScope(
    DB::table('uks_patients')->whereIn('status', $activeStatuses)
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId)),
    $user
);

$count = $base()->count();
$rawat = $base()->where('in_bed', 1)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Pasien Aktif',
    'icon'  => 'ri-user-heart-line',
    'color' => $count > 0 ? 'danger' : 'success',
    'sub'   => $rawat . ' sedang rawat inap',
];
