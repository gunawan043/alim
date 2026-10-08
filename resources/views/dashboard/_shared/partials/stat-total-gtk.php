<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$total = DB::table('gtk_employments')
    ->join('users', 'users.id', '=', 'gtk_employments.user_id')
    ->where('users.is_active', 1)
    ->whereNull('users.deleted_at')
    ->when($schoolId, fn ($q) => $q->where('gtk_employments.school_id', $schoolId))
    ->count();

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $total,
    'label' => 'Total GTK',
    'icon'  => 'ri-team-line',
    'color' => 'success',
    'sub'   => 'GTK aktif di unit ini',
];
