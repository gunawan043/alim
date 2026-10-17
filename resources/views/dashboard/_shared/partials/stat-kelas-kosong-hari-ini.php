<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);
$today = today();
$dayOfWeek = (int) $today->format('w') ?: 7;
$academicYearId = $this->getActiveAcademicYearId();

$kelasKosong = DB::table('jadwal_kbms as j')
    ->where('j.is_active', 1)
    ->where('j.day_of_week', $dayOfWeek)
    ->when($academicYearId, fn ($q) => $q->where('j.academic_year_id', $academicYearId))
    ->when($schoolId, fn ($q) => $q->where('j.school_id', $schoolId))
    ->whereNotExists(function ($q) use ($today) {
        $q->select(DB::raw(1))
            ->from('teacher_class_attendances as t')
            ->whereColumn('t.jadwal_kbm_id', 'j.id')
            ->where('t.attendance_date', $today)
            ->whereNotNull('t.actual_time_in');
    })
    ->count();

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $kelasKosong,
    'label' => 'Kelas Belum Diabsen',
    'icon'  => 'ri-door-closed-line',
    'color' => $kelasKosong > 0 ? 'warning' : 'success',
    'sub'   => 'Jadwal hari ini tanpa check-in guru',
];
