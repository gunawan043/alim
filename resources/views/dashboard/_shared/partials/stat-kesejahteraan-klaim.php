<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$count = 0;
$total = 0;

try {
    $query = DB::table('kesejahteraan_klaim')
        ->whereIn('status', ['pending', 'submitted', 'diproses', 'menunggu'])
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(function ($sub) use ($schoolId) {
                $sub->select(DB::raw(1))
                    ->from('gtk_employments as g')
                    ->whereColumn('g.user_id', 'kesejahteraan_klaim.user_id')
                    ->where('g.school_id', $schoolId);
            });
        });

    $count = (clone $query)->count();
    $total = (clone $query)->sum('nilai_diminta');
} catch (\Throwable $e) {
    Log::warning('Widget stat-kesejahteraan-klaim: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Klaim Kesejahteraan',
    'icon'  => 'ri-hand-heart-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Menunggu proses · Rp ' . number_format((float) $total, 0, ',', '.'),
];
