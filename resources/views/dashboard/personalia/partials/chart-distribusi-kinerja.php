<?php

use Illuminate\Support\Facades\DB;

$rows = DB::table('kinerja_penilaian')
    ->selectRaw("COALESCE(NULLIF(kategori_hasil, ''), NULLIF(nilai_huruf, ''), 'Belum dinilai') as kategori, COUNT(*) as total")
    ->groupBy('kategori')
    ->orderByDesc('total')
    ->limit(6)
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'distribusi-kinerja',
    'label'  => 'Distribusi Hasil Kinerja',
    'type'   => 'donut',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('kategori')->all(),
    'colors' => ['#0ab39c', '#405189', '#299cdb', '#f7b84b', '#f06548', '#8b5cf6'],
    'options' => [
        'legend'      => ['position' => 'bottom'],
        'plotOptions' => ['pie' => ['donut' => ['size' => '62%']]],
    ],
];
