<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$today = now()->toDateString();
$count = 0;

try {
    $count = DB::table('cuti_requests')
        ->where('status', 'approved')
        ->where('tanggal_mulai', '<=', $today)
        ->where('tanggal_selesai', '>=', $today)
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(function ($sub) use ($schoolId) {
                $sub->select(DB::raw(1))
                    ->from('gtk_employments as g')
                    ->whereColumn('g.user_id', 'cuti_requests.user_id')
                    ->where('g.school_id', $schoolId);
            });
        })
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-cuti-aktif-hari-ini: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'GTK Cuti / Izin',
    'icon'  => 'ri-calendar-check-line',
    'color' => 'warning',
    'sub'   => 'Cuti disetujui aktif hari ini',
];
