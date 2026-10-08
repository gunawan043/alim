<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

$rows = DB::table('student_attendances')
    ->whereIn('attendance_date', $days->all())
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->selectRaw("attendance_date, SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir, COUNT(*) as total")
    ->groupBy('attendance_date')
    ->get()
    ->keyBy(fn ($r) => Carbon::parse($r->attendance_date)->toDateString());

$categories = [];
$percent = [];
$totalHadir = 0;
$totalAll = 0;

foreach ($days as $d) {
    $r = $rows->get($d);
    $categories[] = Carbon::parse($d)->translatedFormat('d M');
    $percent[] = ($r && $r->total > 0) ? round($r->hadir / $r->total * 100, 1) : 0;
    $totalHadir += (int) ($r->hadir ?? 0);
    $totalAll += (int) ($r->total ?? 0);
}

return [
    '_col'   => 'col-xl-8 col-md-12',
    'key'    => 'tren-kehadiran-santri',
    'label'  => 'Tren Kehadiran Santri (7 Hari)',
    'badge'  => $totalAll > 0 ? round($totalHadir / $totalAll * 100, 1) . '% hadir' : '7 hari',
    'type'   => 'area',
    'series' => [['name' => 'Kehadiran (%)', 'data' => $percent]],
    'options' => [
        'xaxis'      => ['categories' => $categories],
        'yaxis'      => ['max' => 100],
        'stroke'     => ['curve' => 'smooth', 'width' => 3],
        'fill'       => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.35, 'opacityTo' => 0.05]],
        'colors'     => ['#405189'],
        'dataLabels' => ['enabled' => false],
    ],
];
