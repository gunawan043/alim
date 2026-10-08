<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$total = DB::table('gtk_employments as g')
    ->join('users as u', 'u.id', '=', 'g.user_id')
    ->where('u.is_active', 1)
    ->whereNull('u.deleted_at')
    ->when($schoolId, fn ($q) => $q->where('g.school_id', $schoolId))
    ->where(function ($q) {
        $q->where('g.jenis_gtk', 'like', '%guru%')
            ->orWhere('g.position_type', 'like', '%guru%')
            ->orWhere('g.jabatan', 'like', '%guru%');
    })
    ->count();

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $total,
    'label' => 'Total Guru Rumpun',
    'icon'  => 'ri-team-line',
    'color' => 'primary',
    'sub'   => 'Guru aktif di unit ini',
];
