<?php

use Illuminate\Support\Facades\DB;

$rombel = $this->getHomeroomStudyGroup($user);

if (! $rombel) {
    return [
        '_col'  => 'col-xl-3 col-md-6',
        'value' => null,
        'label' => 'Hadir Hari Ini',
        'icon'  => 'ri-checkbox-circle-line',
        'color' => 'primary',
        'sub'   => 'Anda belum ditetapkan sebagai wali kelas.',
    ];
}

$row = DB::table('student_attendances')
    ->where('study_group_id', $rombel->id)
    ->where('attendance_date', today())
    ->selectRaw("SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir, COUNT(*) as total")
    ->first();

$hadir = (int) ($row->hadir ?? 0);
$total = (int) ($row->total ?? 0);
$percent = $total > 0 ? round($hadir / $total * 100) : 0;

return [
    '_col'   => 'col-xl-3 col-md-6',
    'value'  => $percent,
    'suffix' => '%',
    'label'  => 'Hadir Hari Ini',
    'icon'   => 'ri-checkbox-circle-line',
    'color'  => $percent >= 90 ? 'success' : ($percent >= 75 ? 'warning' : 'danger'),
    'sub'    => "{$hadir} dari {$total} santri kelas {$rombel->name}",
];
