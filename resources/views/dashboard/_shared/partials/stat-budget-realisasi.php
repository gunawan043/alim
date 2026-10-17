<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$percent = 0;
$allocated = 0;
$used = 0;

try {
    $row = DB::table('division_budgets')
        ->where('fiscal_year', now()->year)
        ->selectRaw('SUM(allocated_amount) as alokasi, SUM(used_amount) as terpakai')
        ->first();

    $allocated = (float) ($row->alokasi ?? 0);
    $used = (float) ($row->terpakai ?? 0);
    $percent = $allocated > 0 ? round($used / $allocated * 100, 1) : 0;
} catch (\Throwable $e) {
    Log::warning('Widget stat-budget-realisasi: ' . $e->getMessage());
}

return [
    '_col'   => 'col-xl-3 col-md-6',
    'value'  => $percent,
    'suffix' => '%',
    'label'  => 'Realisasi Anggaran',
    'icon'   => 'ri-pie-chart-2-line',
    'color'  => $percent > 90 ? 'danger' : ($percent > 70 ? 'warning' : 'success'),
    'sub'    => 'Rp ' . number_format($used, 0, ',', '.') . ' dari Rp ' . number_format($allocated, 0, ',', '.'),
];
