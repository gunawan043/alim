<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$items = [];

try {
    $items = DB::table('sarpras_sla_trackers as t')
        ->orderByDesc('t.started_at')
        ->limit(10)
        ->get([
            't.workflow_type', 't.entity_type', 't.priority', 't.deadline_at',
            't.completed_at', 't.status', 't.escalation_level',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-sla-tracking: ' . $e->getMessage());
}

return [
    "_type" => "table",
    '_col'  => 'col-xl-6 col-12',
    'label' => 'SLA Tracking',
    'items' => $items,
];
