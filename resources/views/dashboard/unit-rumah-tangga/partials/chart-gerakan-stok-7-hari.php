<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

$rows = DB::table('sparepart_stock_movements')
    ->whereIn(DB::raw('DATE(occurred_at)'), $days->all())
    ->selectRaw('DATE(occurred_at) as tanggal, COUNT(*) as total')
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
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'gerakan-stok-7-hari',
    'label'  => 'Pergerakan Stok (7 Hari)',
    'type'   => 'area',
    'series' => [['name' => 'Pergerakan', 'data' => $data]],
    'options' => [
        'xaxis'      => ['categories' => $categories],
        'stroke'     => ['curve' => 'smooth', 'width' => 3],
        'fill'       => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.35, 'opacityTo' => 0.05]],
        'colors'     => ['#8b5cf6'],
        'dataLabels' => ['enabled' => false],
    ],
];
