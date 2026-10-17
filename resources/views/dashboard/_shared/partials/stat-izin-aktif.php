<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$count = 0;

try {
    $count = DB::table('dormitory_permits as p')
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(function ($sub) use ($schoolId) {
                $sub->select(DB::raw(1))
                    ->from('students as s')
                    ->whereColumn('s.id', 'p.student_id')
                    ->where('s.school_id', $schoolId);
            });
        })
        ->whereIn('status', ['approved', 'overdue'])
        ->whereNull('actual_return_datetime')
        ->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-izin-aktif: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Izin Belum Kembali',
    'icon'  => 'ri-walk-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Santri izin keluar / pulang aktif',
];
