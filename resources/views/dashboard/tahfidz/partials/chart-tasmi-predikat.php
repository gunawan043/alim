<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$rows = DB::table('tahfidz_tasmian_scores')
    ->when($schoolId, function ($q) use ($schoolId) {
        $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))
            ->from('tahfidz_tasmian_sessions as ts')
            ->whereColumn('ts.id', 'tahfidz_tasmian_scores.tasmian_session_id')
            ->where('ts.school_id', $schoolId));
    })
    ->whereNotNull('predikat')
    ->selectRaw('predikat, COUNT(*) as total')
    ->groupBy('predikat')
    ->get();

$labels = [
    'mumtaz'         => 'Mumtaz',
    'jayyid_jiddan'  => 'Jayyid Jiddan',
    'jayyid'         => 'Jayyid',
    'maqbul'         => 'Maqbul',
    'rasib'          => 'Rasib',
];

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'tasmi-predikat',
    'label'  => "Predikat Tasmi'",
    'type'   => 'donut',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('predikat')->map(fn ($p) => $labels[$p] ?? ucfirst((string) $p))->all(),
    'colors' => ['#0ab39c', '#405189', '#299cdb', '#f7b84b', '#f06548'],
    'options' => [
        'legend'      => ['position' => 'bottom'],
        'plotOptions' => ['pie' => ['donut' => ['size' => '62%']]],
    ],
];
