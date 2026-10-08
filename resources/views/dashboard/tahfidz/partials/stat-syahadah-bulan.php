<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = DB::table('tahfidz_certificates')
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->whereYear('issued_date', now()->year)
    ->whereMonth('issued_date', now()->month)
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Syahadah Bulan Ini',
    'icon'  => 'ri-medal-line',
    'color' => 'success',
    'sub'   => 'Sertifikat tahfidz diterbitkan',
];
