<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);
$mustamiId = $this->getTasmianMustamiId($user);

$items = DB::table('tahfidz_tasmian_sessions as ts')
    ->when($schoolId, fn ($q) => $q->where('ts.school_id', $schoolId))
    ->when($mustamiId, function ($q) use ($mustamiId) {
        $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))
            ->from('tahfidz_tasmian_participants as p')
            ->whereColumn('p.tasmian_session_id', 'ts.id')
            ->where('p.mustami_id', $mustamiId));
    })
    ->whereIn('ts.status', ['terjadwal', 'berlangsung'])
    ->orderBy('ts.session_date')
    ->limit(10)
    ->get([
        'ts.session_name', 'ts.session_date', 'ts.session_time_start',
        'ts.location', 'ts.session_type', 'ts.status',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => "Tasmi' Terjadwal",
    'items' => $items,
];
