<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$rows = $this->scopeTahfidzQuery(
    DB::table('tahfidz_setorans as s')
        ->join('tahfidz_groups as g', 'g.id', '=', 's.tahfidz_group_id')
        ->when($schoolId, fn ($q) => $q->where('g.school_id', $schoolId))
        ->whereYear('s.setoran_date', now()->year)
        ->whereMonth('s.setoran_date', now()->month)
        ->whereNotNull('s.nilai_setoran'),
    $user,
    's.tahfidz_group_id'
)->groupBy('g.id', 'g.name')
    ->selectRaw('g.name, ROUND(AVG(s.nilai_setoran), 1) as rata')
    ->orderByDesc('rata')
    ->limit(8)
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'nilai-per-halaqah',
    'label'  => 'Rata-rata Nilai Setoran per Halaqah',
    'type'   => 'bar',
    'series' => [['name' => 'Rata-rata Nilai', 'data' => $rows->pluck('rata')->map(fn ($v) => (float) $v)->all()]],
    'options' => [
        'plotOptions' => ['bar' => ['horizontal' => true, 'borderRadius' => 4, 'columnWidth' => '55%']],
        'xaxis'       => ['categories' => $rows->pluck('name')->all()],
        'colors'      => ['#405189'],
        'dataLabels'  => ['enabled' => true],
    ],
];
