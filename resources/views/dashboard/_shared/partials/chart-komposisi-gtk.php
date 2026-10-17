<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$rows = DB::table('gtk_employments as g')
    ->join('users as u', 'u.id', '=', 'g.user_id')
    ->where('u.is_active', 1)
    ->whereNull('u.deleted_at')
    ->when($schoolId, fn ($q) => $q->where('g.school_id', $schoolId))
    ->selectRaw("COALESCE(NULLIF(g.jenis_gtk, ''), NULLIF(g.position_type, ''), 'Lainnya') as kelompok, COUNT(*) as total")
    ->groupBy('kelompok')
    ->orderByDesc('total')
    ->limit(6)
    ->get();

return [
    '_col'   => 'col-xl-4 col-md-6',
    'key'    => 'komposisi-gtk',
    'label'  => 'Komposisi GTK',
    'type'   => 'pie',
    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
    'labels' => $rows->pluck('kelompok')->all(),
    'colors' => ['#405189', '#0ab39c', '#f7b84b', '#f06548', '#299cdb', '#8b5cf6'],
    'options' => [
        'legend' => ['position' => 'bottom'],
    ],
];
