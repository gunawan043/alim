<?php

use Illuminate\Support\Facades\DB;

$row = $this->scopeAsramaQuery(
    DB::table('dormitory_violations')
        ->whereYear('violation_date', now()->year)
        ->whereMonth('violation_date', now()->month),
    $user
)->selectRaw('COUNT(*) as total, COALESCE(SUM(points), 0) as poin')
    ->first();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => (int) ($row->total ?? 0),
    'label' => 'Pelanggaran Bulan Ini',
    'icon'  => 'ri-alert-line',
    'color' => (int) ($row->total ?? 0) > 0 ? 'danger' : 'success',
    'sub'   => number_format((float) ($row->poin ?? 0), 0, ',', '.') . ' total poin',
];
