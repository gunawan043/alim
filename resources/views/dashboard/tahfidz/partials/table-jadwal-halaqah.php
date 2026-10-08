<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Ahad'];

$items = $this->scopeTahfidzQuery(
    DB::table('tahfidz_schedules as sc')
        ->join('tahfidz_groups as g', 'g.id', '=', 'sc.tahfidz_group_id')
        ->where('sc.is_active', 1)
        ->when($schoolId, fn ($q) => $q->where('g.school_id', $schoolId)),
    $user,
    'sc.tahfidz_group_id'
)
    ->orderBy('sc.day_of_week')
    ->orderBy('sc.time_start')
    ->limit(10)
    ->get(['g.name as halaqah', 'sc.day_of_week', 'sc.time_start', 'sc.time_end', 'sc.room', 'sc.schedule_type'])
    ->map(function ($row) use ($days) {
        $row->hari = $days[$row->day_of_week] ?? '—';

        return $row;
    })
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Jadwal Halaqah',
    'items' => $items,
];
