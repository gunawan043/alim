<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);
$dayOfWeek = (int) today()->format('w') ?: 7;
$academicYearId = $this->getActiveAcademicYearId();

$items = DB::table('jadwal_kbms as j')
    ->leftJoin('study_groups as sg', 'sg.id', '=', 'j.study_group_id')
    ->leftJoin('subjects as s', 's.id', '=', 'j.subject_id')
    ->leftJoin('users as u', 'u.id', '=', 'j.teacher_id')
    ->where('j.is_active', 1)
    ->where('j.day_of_week', $dayOfWeek)
    ->when($academicYearId, fn ($q) => $q->where('j.academic_year_id', $academicYearId))
    ->when($schoolId, fn ($q) => $q->where('j.school_id', $schoolId))
    ->orderBy('j.start_time')
    ->limit(10)
    ->get([
        'j.start_time', 'j.end_time', 'j.room',
        'sg.name as kelas', 's.name as mapel', 'u.name as guru',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Jadwal Pelajaran Aktif Hari Ini',
    'items' => $items,
];
