<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$schoolId = $this->getUserSchoolId($user);
$items = [];

try {
    $items = DB::table('kinerja_penilaian as kp')
        ->join('users as u', 'u.id', '=', 'kp.user_id')
        ->leftJoin('kinerja_periode as p', 'p.id', '=', 'kp.kinerja_periode_id')
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(function ($sub) use ($schoolId) {
                $sub->select(DB::raw(1))
                    ->from('gtk_employments as g')
                    ->whereColumn('g.user_id', 'kp.user_id')
                    ->where('g.school_id', $schoolId);
            });
        })
        ->orderByDesc('kp.tanggal_penilaian')
        ->orderByDesc('kp.created_at')
        ->limit(10)
        ->get([
            'u.name', 'p.nama as periode', 'kp.total_skor', 'kp.nilai_huruf',
            'kp.kategori_hasil', 'kp.status', 'kp.tanggal_penilaian',
        ])
        ->all();
} catch (\Throwable $e) {
    Log::warning('Widget table-kinerja-terbaru: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Penilaian Kinerja Terbaru',
    'items' => $items,
];
