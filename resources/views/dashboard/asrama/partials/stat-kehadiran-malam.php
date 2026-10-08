<?php

use Illuminate\Support\Facades\DB;

$row = $this->scopeAsramaQuery(
    DB::table('dormitory_attendances')
        ->where('attendance_date', today())
        ->where('session', 'malam'),
    $user
)->selectRaw("SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir, COUNT(*) as total")
    ->first();

$hadir = (int) ($row->hadir ?? 0);
$total = (int) ($row->total ?? 0);
$percent = $total > 0 ? round($hadir / $total * 100) : 0;

return [
    '_type'  => 'stat',
    '_col'   => 'col-xl-3 col-md-6',
    'value'  => $percent,
    'suffix' => '%',
    'label'  => 'Kehadiran Malam',
    'icon'   => 'ri-moon-line',
    'color'  => $percent >= 90 ? 'success' : ($percent >= 75 ? 'warning' : 'danger'),
    'sub'    => "{$hadir} dari {$total} tercatat · sesi malam",
];
