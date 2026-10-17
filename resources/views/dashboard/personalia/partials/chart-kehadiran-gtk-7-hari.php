<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

$rows = DB::table('absensi_gtk')
    ->whereIn('tanggal', $days->all())
    ->selectRaw("tanggal, SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir, COUNT(*) as total")
    ->groupBy('tanggal')
    ->get()
    ->keyBy(fn ($r) => Carbon::parse($r->tanggal)->toDateString());

$categories = [];
$percent = [];

foreach ($days as $d) {
    $r = $rows->get($d);
    $categories[] = Carbon::parse($d)->translatedFormat('d M');
    $percent[] = ($r && $r->total > 0) ? round($r->hadir / $r->total * 100, 1) : 0;
}

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-8 col-md-12',
    'key'    => 'kehadiran-gtk-7-hari',
    'label'  => 'Kehadiran GTK (7 Hari)',
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
