<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

$rows = DB::table('activity_log')
    ->whereIn(DB::raw('DATE(created_at)'), $days->all())
    ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
    ->groupBy('tanggal')
    ->get()
    ->keyBy(fn ($r) => Carbon::parse($r->tanggal)->toDateString());

$categories = [];
$data = [];

foreach ($days as $d) {
    $categories[] = Carbon::parse($d)->translatedFormat('d M');
    $data[] = (int) ($rows->get($d)->total ?? 0);
}

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-8 col-md-12',
    'key'    => 'aktivitas-7-hari',
    'label'  => 'Aktivitas Sistem (7 Hari)',
    'type'   => 'area',
    'series' => [['name' => 'Aktivitas', 'data' => $data]],
    'options' => [
        'xaxis'      => ['categories' => $categories],
        'stroke'     => ['curve' => 'smooth', 'width' => 3],
        'fill'       => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.35, 'opacityTo' => 0.05]],
        'colors'     => ['#405189'],
        'dataLabels' => ['enabled' => false],
    ],
];
