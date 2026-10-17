<?php

use Illuminate\Support\Facades\DB;

$items = $this->scopeAsramaQuery(
    DB::table('dormitory_attendance_recaps as rc')
        ->leftJoin('dormitory_rooms as r', 'r.id', '=', 'rc.room_id')
        ->where('rc.recap_month', now()->month)
        ->where('rc.recap_year', now()->year),
    $user,
    'rc.room_id',
    'rc.dormitory_id'
)
    ->groupBy('rc.room_id', 'r.name')
    ->selectRaw('COALESCE(r.name, "Tanpa Kamar") as kamar,
                 SUM(rc.total_hadir) as hadir,
                 SUM(rc.total_izin) as izin,
                 SUM(rc.total_sakit) as sakit,
                 SUM(rc.total_alpa) as alpa,
                 SUM(rc.total_pulang) as pulang')
    ->orderBy('kamar')
    ->limit(10)
    ->get()
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Rekap Absensi Bulanan per Kamar',
    'items' => $items,
];
