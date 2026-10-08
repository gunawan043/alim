<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = DB::table('gtk_employments')
    ->join('users', 'users.id', '=', 'gtk_employments.user_id')
    ->where('users.is_active', 1)
    ->whereNull('users.deleted_at')
    ->when($schoolId, fn ($q) => $q->where('gtk_employments.school_id', $schoolId))
    ->whereNotNull('gtk_employments.decree_date')
    ->orderByDesc('gtk_employments.decree_date')
    ->limit(10)
    ->get(['users.name', 'gtk_employments.status_kepegawaian', 'gtk_employments.decree_date']);

return [
    '_col'  => 'col-xl-6 col-12',
    'items' => $items,
    'label' => 'SK / Kontrak Terbaru',
];
