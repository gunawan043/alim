<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$row = $this->scopeTahfidzQuery(
    DB::table('tahfidz_group_attendances as a')
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from('tahfidz_groups as g')
                ->whereColumn('g.id', 'a.tahfidz_group_id')
                ->where('g.school_id', $schoolId));
        })
        ->where('a.attendance_date', today()),
    $user,
    'a.tahfidz_group_id'
)->selectRaw("SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as hadir, COUNT(*) as total")
    ->first();

$hadir = (int) ($row->hadir ?? 0);
$total = (int) ($row->total ?? 0);
$percent = $total > 0 ? round($hadir / $total * 100) : 0;

return [
    '_type'  => 'stat',
    '_col'   => 'col-xl-3 col-md-6',
    'value'  => $percent,
    'suffix' => '%',
    'label'  => 'Kehadiran Halaqah',
    'icon'   => 'ri-calendar-check-line',
    'color'  => $percent >= 90 ? 'success' : ($percent >= 75 ? 'warning' : 'danger'),
    'sub'    => $hadir . ' dari ' . $total . ' tercatat hari ini',
];
