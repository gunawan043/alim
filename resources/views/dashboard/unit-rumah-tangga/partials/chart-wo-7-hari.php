<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);
$days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

$query = fn () => DB::table('work_orders as w')
    ->leftJoin('assets as a', 'a.id', '=', 'w.asset_id')
    ->when($schoolId, fn ($q) => $q->where('a.school_id', $schoolId));

$created = $query()->whereIn(DB::raw('DATE(w.created_at)'), $days->all())
    ->selectRaw('DATE(w.created_at) as tanggal, COUNT(*) as total')->groupBy('tanggal')->get()
    ->keyBy(fn ($r) => Carbon::parse($r->tanggal)->toDateString());

$selesai = $query()->whereIn(DB::raw('DATE(w.actual_end)'), $days->all())
    ->selectRaw('DATE(w.actual_end) as tanggal, COUNT(*) as total')->groupBy('tanggal')->get()
    ->keyBy(fn ($r) => Carbon::parse($r->tanggal)->toDateString());

$categories = [];
$dataCreated = [];
$dataDone = [];

foreach ($days as $d) {
    $categories[] = Carbon::parse($d)->translatedFormat('d M');
    $dataCreated[] = (int) ($created->get($d)->total ?? 0);
    $dataDone[] = (int) ($selesai->get($d)->total ?? 0);
}

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-8 col-md-12',
    'key'    => 'wo-7-hari',
    'label'  => 'Work Order 7 Hari',
    'type'   => 'bar',
    'series' => [
        ['name' => 'Dibuat', 'data' => $dataCreated],
        ['name' => 'Selesai', 'data' => $dataDone],
    ],
    'options' => [
        'xaxis'       => ['categories' => $categories],
        'colors'      => ['#405189', '#0ab39c'],
        'legend'      => ['position' => 'top'],
        'dataLabels'  => ['enabled' => false],
        'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%']],
    ],
];
