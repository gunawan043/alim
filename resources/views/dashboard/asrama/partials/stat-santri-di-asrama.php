<?php

use Illuminate\Support\Facades\DB;

/*
 * Santri yang benar-benar masih berada di asrama.
 *
 * Tabel student_boarding_statuses menandai santri ON_LEAVE sejak izin
 * disetujui, padahal santri baru benar-benar keluar saat izin berstatus
 * picked_up/overdue. Karena itu basis "di luar" memakai status izin, bukan
 * status ON_LEAVE, supaya angkanya konsisten dengan kartu "Sedang di Luar".
 */
$residentIds = $this->scopeAsramaQuery(
    DB::table('dormitory_residents')->where('is_active', 1),
    $user
)->distinct()->pluck('student_id');

$residentList = $residentIds->all() ?: ['-'];

$permitOutIds = DB::table('dormitory_permits')
    ->whereIn('status', ['picked_up', 'overdue'])
    ->whereNull('actual_return_datetime')
    ->whereIn('student_id', $residentList)
    ->distinct()
    ->pluck('student_id');

$statusOutIds = DB::table('student_boarding_statuses')
    ->whereIn('status', ['AT_HOSPITAL', 'OFFICIAL_ACTIVITY'])
    ->whereIn('student_id', $residentList)
    ->distinct()
    ->pluck('student_id');

$outIds = $permitOutIds->merge($statusOutIds)->unique();

$count = max(0, $residentIds->count() - $outIds->count());

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Di Asrama',
    'icon'  => 'ri-home-4-line',
    'color' => 'success',
    'sub'   => number_format($outIds->count()) . ' santri sedang di luar',
];
