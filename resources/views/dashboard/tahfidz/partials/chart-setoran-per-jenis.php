<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$rows = $this->scopeTahfidzQuery(
    DB::table('tahfidz_setorans as s')
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from('tahfidz_groups as g')
                ->whereColumn('g.id', 's.tahfidz_group_id')
                ->where('g.school_id', $schoolId));
        })
        ->whereYear('s.setoran_date', now()->year)
        ->whereMonth('s.setoran_date', now()->month),
    $user,
    's.tahfidz_group_id'
)->selectRaw('s.setoran_type, COUNT(*) as total')
    ->groupBy('s.setoran_type')
    ->get();

$labels = ['ziyadah' => 'Ziyadah', 'murajaah' => 'Murajaah', 'tikror' => 'Tikror'];

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'setoran-per-jenis',
    'label'  => 'Setoran per Jenis (Bulan Ini)',
    'type'   => 'donut',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('setoran_type')->map(fn ($t) => $labels[$t] ?? ucfirst((string) $t))->all(),
    'colors' => ['#405189', '#0ab39c', '#f7b84b'],
    'options' => [
        'legend'      => ['position' => 'bottom'],
        'plotOptions' => ['pie' => ['donut' => ['size' => '62%']]],
    ],
];
