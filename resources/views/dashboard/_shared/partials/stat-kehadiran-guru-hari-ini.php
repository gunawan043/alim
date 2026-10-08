<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);
$today = today();
$dayOfWeek = (int) $today->format('w') ?: 7;

$totalJadwal = DB::table('jadwal_kbms')
    ->where('is_active', 1)
    ->where('day_of_week', $dayOfWeek)
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->count();

$row = DB::table('teacher_class_attendances')
    ->where('attendance_date', $today)
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->selectRaw("SUM(CASE WHEN actual_time_in IS NOT NULL THEN 1 ELSE 0 END) as hadir,
                 SUM(CASE WHEN status_masuk = 'terlambat' THEN 1 ELSE 0 END) as telat,
                 COUNT(*) as total")
    ->first();

$hadir = (int) ($row->hadir ?? 0);
$telat = (int) ($row->telat ?? 0);
$recorded = (int) ($row->total ?? 0);

$percent = $totalJadwal > 0
    ? round($hadir / $totalJadwal * 100)
    : ($recorded > 0 ? round($hadir / $recorded * 100) : 0);

return [
    '_col'   => 'col-xl-3 col-md-6',
    'value'  => $percent,
    'suffix' => '%',
    'label'  => 'Kehadiran Guru',
    'icon'   => 'ri-user-star-line',
    'color'  => $percent >= 90 ? 'success' : ($percent >= 75 ? 'warning' : 'danger'),
    'sub'    => "{$hadir} check-in · {$telat} terlambat · {$totalJadwal} jadwal hari ini",
];
