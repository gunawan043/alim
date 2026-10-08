<?php

use Illuminate\Support\Facades\DB;

$templates = $this->scopeAsramaQuery(
    DB::table('dormitory_activity_templates')
        ->where('is_active', 1)
        ->whereNull('deleted_at'),
    $user,
    'dormitory_id',
    'dormitory_id'
)->get(['id', 'session', 'activity_items']);

$logs = $this->scopeAsramaQuery(
    DB::table('dormitory_activity_logs')->where('activity_date', today()),
    $user,
    'dormitory_id',
    'dormitory_id'
)->selectRaw('session, COUNT(*) as total')
    ->groupBy('session')
    ->pluck('total', 'session');

$items = $templates->map(function ($template) use ($logs) {
    $items = json_decode($template->activity_items ?? '[]', true);

    return (object) [
        'session'  => ucfirst((string) $template->session),
        'kegiatan' => is_array($items) ? count($items) : 0,
        'tercatat' => (int) ($logs[$template->session] ?? 0),
    ];
})->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Kegiatan Hari Ini',
    'items' => $items,
];
