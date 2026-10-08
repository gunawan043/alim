<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$rows = collect();

try {
    $rows = DB::table('invoice_approvals')
        ->selectRaw('status, COUNT(*) as total')
        ->groupBy('status')
        ->orderByDesc('total')
        ->get();
} catch (\Throwable $e) {
    Log::warning('Widget chart-invoice-status: ' . $e->getMessage());
}

return [
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'invoice-status',
    'label'  => 'Status Invoice Masuk',
    'type'   => 'donut',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('status')->map(fn ($s) => ucfirst((string) $s))->all(),
    'colors' => ['#f7b84b', '#405189', '#0ab39c', '#f06548', '#299cdb', '#8b5cf6'],
    'options' => [
        'legend'      => ['position' => 'bottom'],
        'plotOptions' => ['pie' => ['donut' => ['size' => '62%']]],
    ],
];
