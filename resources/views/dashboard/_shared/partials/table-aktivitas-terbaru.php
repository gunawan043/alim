<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$logs = [];

try {
    $logs = DB::table('activity_log')
        ->select('id', 'log_name', 'description', 'event', 'created_at')
        ->orderByDesc('created_at')
        ->limit(10)
        ->get()
        ->map(fn ($log) => [
            'description' => $log->description ?: ($log->event ? ucfirst($log->event) : '—'),
            'log_name'    => $log->log_name ?: 'default',
            'created_at'  => $log->created_at,
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-aktivitas-terbaru: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Aktivitas Terbaru',
    'logs'  => $logs,
];
