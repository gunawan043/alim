<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$items = [];

try {
    $items = DB::table('cuti_requests as c')
        ->join('users as u', 'u.id', '=', 'c.user_id')
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(function ($sub) use ($schoolId) {
                $sub->select(DB::raw(1))
                    ->from('gtk_employments as g')
                    ->whereColumn('g.user_id', 'c.user_id')
                    ->where('g.school_id', $schoolId);
            });
        })
        ->orderByDesc('c.created_at')
        ->limit(10)
        ->get([
            'u.name', 'c.tanggal_mulai', 'c.tanggal_selesai',
            'c.jumlah_hari', 'c.alasan', 'c.status',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-cuti-terbaru: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Pengajuan Cuti / Izin GTK',
    'items' => $items,
];
