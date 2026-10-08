<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$items = [];

try {
    $items = DB::table('kesejahteraan_klaim as k')
        ->join('users as u', 'u.id', '=', 'k.user_id')
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(function ($sub) use ($schoolId) {
                $sub->select(DB::raw(1))
                    ->from('gtk_employments as g')
                    ->whereColumn('g.user_id', 'k.user_id')
                    ->where('g.school_id', $schoolId);
            });
        })
        ->orderByDesc('k.created_at')
        ->limit(10)
        ->get([
            'k.nomor_klaim', 'u.name', 'k.nilai_diminta', 'k.status', 'k.created_at',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-kesejahteraan-terbaru: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Klaim Kesejahteraan Terbaru',
    'items' => $items,
];
