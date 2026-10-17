<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$rows = collect();

try {
    $rows = DB::table('division_budgets as db')
        ->leftJoin('divisis as d', 'd.id', '=', 'db.division_id')
        ->where('db.fiscal_year', now()->year)
        ->groupBy('db.division_id', 'd.nama')
        ->selectRaw('COALESCE(d.nama, "Divisi") as nama,
                     SUM(db.allocated_amount) as alokasi,
                     SUM(db.used_amount) as terpakai')
        ->orderByDesc('alokasi')
        ->limit(6)
        ->get();
} catch (\Throwable $e) {
    Log::warning('Widget chart-budget-divisi: ' . $e->getMessage());
}

return [
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'budget-divisi',
    'label'  => 'Anggaran per Divisi (Tahun Ini)',
    'badge'  => 'Alokasi vs Terpakai',
    'type'   => 'bar',
    'series' => [
        ['name' => 'Alokasi', 'data' => $rows->pluck('alokasi')->map(fn ($v) => (float) $v)->all()],
        ['name' => 'Terpakai', 'data' => $rows->pluck('terpakai')->map(fn ($v) => (float) $v)->all()],
    ],
    'options' => [
        'xaxis'       => ['categories' => $rows->pluck('nama')->all()],
        'colors'      => ['#405189', '#0ab39c'],
        'legend'      => ['position' => 'top'],
        'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%']],
        'dataLabels'  => ['enabled' => false],
    ],
];
