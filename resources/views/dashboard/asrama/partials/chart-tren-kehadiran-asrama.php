<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

$rows = $this->scopeAsramaQuery(
    DB::table('dormitory_attendances')->whereIn('attendance_date', $days->all()),
    $user
)->selectRaw("attendance_date, SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir, COUNT(*) as total")
    ->groupBy('attendance_date')
    ->get()
    ->keyBy(fn ($r) => Carbon::parse($r->attendance_date)->toDateString());

$categories = [];
$percent = [];

foreach ($days as $d) {
    $row = $rows->get($d);
    $categories[] = Carbon::parse($d)->translatedFormat('d M');
    $percent[] = ($row && $row->total > 0) ? round($row->hadir / $row->total * 100, 1) : 0;
}

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'tren-kehadiran-asrama',
    'label'  => 'Tren Kehadiran Asrama (7 Hari)',
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
