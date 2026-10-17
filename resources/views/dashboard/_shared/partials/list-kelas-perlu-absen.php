<?php

use Illuminate\Support\Facades\DB;

$today = today();
$dayOfWeek = (int) $today->format('w') ?: 7;
$academicYearId = $this->getActiveAcademicYearId();

$items = DB::table('jadwal_kbms as j')
    ->leftJoin('study_groups as sg', 'sg.id', '=', 'j.study_group_id')
    ->leftJoin('subjects as s', 's.id', '=', 'j.subject_id')
    ->leftJoin('teacher_class_attendances as t', function ($join) use ($today) {
        $join->on('t.jadwal_kbm_id', '=', 'j.id')
            ->where('t.attendance_date', '=', $today);
    })
    ->where('j.teacher_id', $user->id)
    ->where('j.is_active', 1)
    ->where('j.day_of_week', $dayOfWeek)
    ->when($academicYearId, fn ($q) => $q->where('j.academic_year_id', $academicYearId))
    ->whereNull('t.id')
    ->orderBy('j.slot_index')
    ->limit(10)
    ->get([
        'j.start_time', 'j.end_time', 'j.room',
        'sg.name as kelas', 's.name as mapel',
    ])
    ->map(fn ($row) => [
        'kelas' => $row->kelas ?? '—',
        'mapel' => $row->mapel ?? '—',
        'jam'   => substr((string) $row->start_time, 0, 5) . ' – ' . substr((string) $row->end_time, 0, 5),
        'ruang' => $row->room,
    ])
    ->all();

return [
    '_col'  => 'col-xl-4 col-md-12',
    'label' => 'Kelas Perlu Diabsen',
    'items' => $items,
];
