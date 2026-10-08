<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);
$days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

$rows = $this->applyUksScope(
    DB::table('uks_patients')
        ->whereIn(DB::raw('DATE(admitted_at)'), $days->all())
        ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId)),
    $user
)
    ->selectRaw('DATE(admitted_at) as tanggal, COUNT(*) as total')
    ->groupBy('tanggal')
    ->get()
    ->keyBy('tanggal');

$categories = [];
$data = [];

foreach ($days as $d) {
    $categories[] = Carbon::parse($d)->translatedFormat('d M');
    $data[] = (int) ($rows->get($d)->total ?? 0);
}

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-8 col-md-12',
    'key'    => 'kunjungan-7-hari',
    'label'  => 'Kunjungan UKS (7 Hari)',
    'type'   => 'area',
    'series' => [['name' => 'Kunjungan', 'data' => $data]],
    'options' => [
        'xaxis'      => ['categories' => $categories],
        'stroke'     => ['curve' => 'smooth', 'width' => 3],
        'fill'       => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.35, 'opacityTo' => 0.05]],
        'colors'     => ['#f06548'],
        'dataLabels' => ['enabled' => false],
    ],
];
