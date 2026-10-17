<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);
$days = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->toDateString());

$rows = $this->scopeTahfidzQuery(
    DB::table('tahfidz_setorans as s')
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from('tahfidz_groups as g')
                ->whereColumn('g.id', 's.tahfidz_group_id')
                ->where('g.school_id', $schoolId));
        })
        ->whereIn('s.setoran_date', $days->all()),
    $user,
    's.tahfidz_group_id'
)->selectRaw('s.setoran_date, COUNT(*) as total')
    ->groupBy('s.setoran_date')
    ->get()
    ->keyBy(fn ($r) => Carbon::parse($r->setoran_date)->toDateString());

$categories = [];
$data = [];

foreach ($days as $d) {
    $categories[] = Carbon::parse($d)->translatedFormat('d M');
    $data[] = (int) ($rows->get($d)->total ?? 0);
}

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-8 col-md-12',
    'key'    => 'setoran-7-hari',
    'label'  => 'Setoran 7 Hari Terakhir',
    'type'   => 'area',
    'series' => [['name' => 'Setoran', 'data' => $data]],
    'options' => [
        'xaxis'      => ['categories' => $categories],
        'stroke'     => ['curve' => 'smooth', 'width' => 3],
        'fill'       => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.35, 'opacityTo' => 0.05]],
        'colors'     => ['#405189'],
        'dataLabels' => ['enabled' => false],
    ],
];
