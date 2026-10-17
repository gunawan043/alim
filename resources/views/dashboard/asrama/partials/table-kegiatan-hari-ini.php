<?php

use Illuminate\Support\Facades\DB;

$activities = $this->scopeAsramaQuery(
    DB::table('dormitory_activities')->whereDate('activity_date', today()),
    $user,
    'dormitory_id',
    'dormitory_id'
)->orderBy('start_time')->get(['id', 'name', 'start_time', 'status']);

$items = $activities->map(function ($activity) {
    $recorded = DB::table('dormitory_activity_attendances')
        ->where('activity_id', $activity->id)
        ->count();

    return (object) [
        'session'  => $activity->start_time ? substr($activity->start_time, 0, 5) : 'Kegiatan',
        'kegiatan' => $activity->name,
        'tercatat' => $recorded,
    ];
})->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Kegiatan Hari Ini',
    'items' => $items,
];
