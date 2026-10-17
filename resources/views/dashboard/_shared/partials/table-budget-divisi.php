<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$items = [];

try {
    $items = DB::table('division_budgets as db')
        ->leftJoin('divisis as d', 'd.id', '=', 'db.division_id')
        ->where('db.fiscal_year', now()->year)
        ->groupBy('db.division_id', 'd.nama')
        ->selectRaw('COALESCE(d.nama, "Divisi") as nama,
                     SUM(db.allocated_amount) as alokasi,
                     SUM(db.used_amount) as terpakai')
        ->orderByDesc('alokasi')
        ->limit(10)
        ->get()
        ->map(fn ($row) => (object) [
            'nama'     => $row->nama,
            'alokasi'  => (float) $row->alokasi,
            'terpakai' => (float) $row->terpakai,
            'sisa'     => (float) $row->alokasi - (float) $row->terpakai,
            'persen'   => (float) $row->alokasi > 0 ? round((float) $row->terpakai / (float) $row->alokasi * 100, 1) : 0,
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-budget-divisi: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Realisasi Anggaran per Divisi',
    'items' => $items,
];
