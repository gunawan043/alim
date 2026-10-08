<?php

use Illuminate\Support\Facades\DB;

$sessions = ['subuh', 'pagi', 'siang', 'sore', 'isya', 'malam'];

$recorded = $this->scopeAsramaQuery(
    DB::table('dormitory_attendances')->where('attendance_date', today()),
    $user
)->distinct()->pluck('session')->all();

$items = collect(array_diff($sessions, $recorded))
    ->map(fn ($session) => [
        'session' => ucfirst($session),
        'tanggal' => now()->translatedFormat('d M Y'),
    ])
    ->values()
    ->all();

return [
    '_col'  => 'col-xl-4 col-md-12',
    'label' => 'Sesi Belum Diisi Hari Ini',
    'items' => $items,
];
