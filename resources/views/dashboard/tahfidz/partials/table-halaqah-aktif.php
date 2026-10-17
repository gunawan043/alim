<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$groups = $this->scopeTahfidzQuery(
    DB::table('tahfidz_groups as g')
        ->leftJoin('users as t', 't.id', '=', 'g.teacher_id')
        ->leftJoin('users as c', 'c.id', '=', 'g.coordinator_id')
        ->where('g.is_active', 1)
        ->when($schoolId, fn ($q) => $q->where('g.school_id', $schoolId)),
    $user,
    'g.id'
)
    ->orderBy('g.name')
    ->limit(10)
    ->get([
        'g.id', 'g.name', 'g.gender', 'g.room', 't.name as musyrif',
        'c.name as koordinator', 'g.max_members',
    ]);

$counts = DB::table('tahfidz_group_members')
    ->whereIn('tahfidz_group_id', $groups->pluck('id')->all() ?: ['-'])
    ->where('status', 'aktif')
    ->selectRaw('tahfidz_group_id, COUNT(*) as total')
    ->groupBy('tahfidz_group_id')
    ->pluck('total', 'tahfidz_group_id');

$items = $groups->map(function ($row) use ($counts) {
    $row->anggota = (int) ($counts[$row->id] ?? 0);

    return $row;
})->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Halaqah Aktif',
    'items' => $items,
];
