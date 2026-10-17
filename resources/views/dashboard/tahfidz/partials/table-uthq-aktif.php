<?php

use Illuminate\Support\Facades\DB;

$events = DB::table('tahfidz_uthq_events as e')
    ->whereIn('e.status', ['pendaftaran', 'audisi', 'final'])
    ->orderBy('e.event_date_start')
    ->limit(10)
    ->get([
        'e.id', 'e.name', 'e.event_date_start', 'e.event_date_end', 'e.location', 'e.status',
    ]);

$counts = DB::table('tahfidz_uthq_registrations')
    ->whereIn('uthq_event_id', $events->pluck('id')->all() ?: ['-'])
    ->selectRaw('uthq_event_id, COUNT(*) as total')
    ->groupBy('uthq_event_id')
    ->pluck('total', 'uthq_event_id');

$items = $events->map(function ($row) use ($counts) {
    $row->peserta = (int) ($counts[$row->id] ?? 0);

    return $row;
})->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Event UTHQ Aktif',
    'items' => $items,
];
