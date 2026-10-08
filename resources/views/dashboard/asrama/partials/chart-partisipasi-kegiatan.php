<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

$rows = $this->scopeAsramaQuery(
    DB::table('dormitory_activity_logs')->whereIn('activity_date', $days->all()),
    $user,
    'dormitory_id',
    'dormitory_id'
)->selectRaw('activity_date, COUNT(*) as total')
    ->groupBy('activity_date')
    ->get()
    ->keyBy(fn ($r) => Carbon::parse($r->activity_date)->toDateString());

$categories = [];
$data = [];

foreach ($days as $d) {
    $categories[] = Carbon::parse($d)->translatedFormat('d M');
    $data[] = (int) ($rows->get($d)->total ?? 0);
}

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'partisipasi-kegiatan',
    'label'  => 'Pencatatan Kegiatan (7 Hari)',
    'type'   => 'bar',
    'series' => [['name' => 'Catatan Kegiatan', 'data' => $data]],
    'options' => [
        'xaxis'       => ['categories' => $categories],
        'colors'      => ['#8b5cf6'],
        'dataLabels'  => ['enabled' => false],
        'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '45%']],
    ],
];
