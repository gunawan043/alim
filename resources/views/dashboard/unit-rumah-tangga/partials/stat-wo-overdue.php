<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$count = 0;

try {
    $count = DB::table('sarpras_sla_trackers')
        ->whereNull('completed_at')
        ->whereIn('status', ['overdue', 'escalated'])
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-wo-overdue: ' . $e->getMessage());
}

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'SLA Terlampaui',
    'icon'  => 'ri-alarm-warning-line',
    'color' => $count > 0 ? 'danger' : 'success',
    'sub'   => 'Melewati batas layanan',
];
