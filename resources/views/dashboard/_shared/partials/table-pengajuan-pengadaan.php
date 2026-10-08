<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$items = [];

try {
    $items = DB::table('procurement_requests as p')
        ->leftJoin('users as u', 'u.id', '=', 'p.requested_by')
        ->whereNull('p.deleted_at')
        ->when($schoolId, fn ($q) => $q->where('p.school_id', $schoolId))
        ->whereNotIn('p.status', ['approved', 'rejected', 'completed', 'done', 'cancelled'])
        ->orderByDesc('p.request_date')
        ->limit(10)
        ->get([
            'p.request_number', 'p.request_date', 'p.urgency',
            'p.total_estimated_price', 'p.status', 'u.name as pemohon',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-pengajuan-pengadaan: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Pengajuan Pengadaan Menunggu',
    'items' => $items,
];
