<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = $this->applyUksScope(
    DB::table('uks_patients')
        ->whereDate('queue_date', today())
        ->whereIn('queue_status', ['menunggu', 'dipanggil'])
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId)),
    $user
)->count();

$p1 = $this->applyUksScope(
    DB::table('uks_patients')
        ->whereDate('queue_date', today())
        ->where('triage_level', 'P1')
        ->whereIn('queue_status', ['menunggu', 'dipanggil', 'pemeriksaan'])
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId)),
    $user
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Antrean Menunggu',
    'icon'  => 'ri-list-check-2',
    'color' => $p1 > 0 ? 'danger' : 'warning',
    'sub'   => $p1 > 0 ? $p1.' pasien prioritas P1' : 'Antrean klinik hari ini',
];
