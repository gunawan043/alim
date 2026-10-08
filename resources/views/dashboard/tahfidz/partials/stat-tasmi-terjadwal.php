<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);
$mustamiId = $this->getTasmianMustamiId($user);

$query = DB::table('tahfidz_tasmian_sessions')
    ->whereIn('status', ['terjadwal', 'berlangsung'])
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->when($mustamiId, function ($q) use ($mustamiId) {
        $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))
            ->from('tahfidz_tasmian_participants as p')
            ->whereColumn('p.tasmian_session_id', 'tahfidz_tasmian_sessions.id')
            ->where('p.mustami_id', $mustamiId));
    });

$count = $query->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => "Tasmi' Terjadwal",
    'icon'  => 'ri-award-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Menunggu pelaksanaan',
];
