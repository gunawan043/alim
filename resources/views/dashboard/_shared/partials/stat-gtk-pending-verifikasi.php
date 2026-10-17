<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

try {
    $proposals = DB::table('gtk_position_proposals')
        ->whereIn('status', ['submitted', 'pending'])
        ->when($schoolId, fn ($q) => $q->where('proposed_school_id', $schoolId))
        ->count();

    $requests = DB::table('gtk_requests')
        ->whereIn('status', ['submitted', 'pending'])
        ->count();
} catch (\Throwable $e) {
    $proposals = 0;
    $requests = 0;
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $proposals + $requests,
    'label' => 'GTK Pending Verifikasi',
    'icon'  => 'ri-user-follow-line',
    'color' => 'warning',
    'sub'   => "{$proposals} usulan jabatan · {$requests} pengajuan GTK",
];
