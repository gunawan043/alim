<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$row = DB::table('student_attendances')
    ->where('attendance_date', today())
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->selectRaw("SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir, COUNT(*) as total")
    ->first();

$hadir = (int) ($row->hadir ?? 0);
$total = (int) ($row->total ?? 0);
$percent = $total > 0 ? round($hadir / $total * 100) : 0;

return [
    '_col'   => 'col-xl-3 col-md-6',
    'value'  => $percent,
    'suffix' => '%',
    'label'  => 'Absensi Santri Hari Ini',
    'icon'   => 'ri-checkbox-circle-line',
    'color'  => $percent >= 90 ? 'success' : ($percent >= 75 ? 'warning' : 'danger'),
    'sub'    => "{$hadir} dari {$total} santri hadir",
];
