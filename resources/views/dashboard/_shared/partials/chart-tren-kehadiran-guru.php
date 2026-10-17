<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

$rows = DB::table('teacher_class_attendances')
    ->whereIn('attendance_date', $days->all())
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->selectRaw("attendance_date, SUM(CASE WHEN actual_time_in IS NOT NULL THEN 1 ELSE 0 END) as hadir, COUNT(*) as total")
    ->groupBy('attendance_date')
    ->get()
    ->keyBy(fn ($r) => Carbon::parse($r->attendance_date)->toDateString());

$categories = [];
$percent = [];

foreach ($days as $d) {
    $r = $rows->get($d);
    $categories[] = Carbon::parse($d)->translatedFormat('d M');
    $percent[] = ($r && $r->total > 0) ? round($r->hadir / $r->total * 100, 1) : 0;
}

return [
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'tren-kehadiran-guru',
    'label'  => 'Tren Kehadiran Guru (7 Hari)',
    'type'   => 'area',
    'series' => [['name' => 'Kehadiran (%)', 'data' => $percent]],
    'options' => [
        'xaxis'      => ['categories' => $categories],
        'yaxis'      => ['max' => 100],
        'stroke'     => ['curve' => 'smooth', 'width' => 3],
        'fill'       => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.35, 'opacityTo' => 0.05]],
        'colors'     => ['#0ab39c'],
        'dataLabels' => ['enabled' => false],
    ],
];
