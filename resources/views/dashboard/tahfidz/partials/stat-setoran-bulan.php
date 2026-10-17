<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$base = fn () => $this->scopeTahfidzQuery(
    DB::table('tahfidz_setorans as s')
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from('tahfidz_groups as g')
                ->whereColumn('g.id', 's.tahfidz_group_id')
                ->where('g.school_id', $schoolId));
        }),
    $user,
    's.tahfidz_group_id'
);

$bulanIni = $base()->whereYear('s.setoran_date', now()->year)->whereMonth('s.setoran_date', now()->month)->count();
$bulanLalu = $base()->whereYear('s.setoran_date', now()->subMonth()->year)->whereMonth('s.setoran_date', now()->subMonth()->month)->count();
$trend = $bulanLalu > 0 ? round((($bulanIni - $bulanLalu) / $bulanLalu) * 100, 1) : 0;

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $bulanIni,
    'label' => 'Setoran Bulan Ini',
    'icon'  => 'ri-calendar-check-line',
    'color' => 'success',
    'trend' => $trend,
    'sub'   => 'Bulan lalu ' . $bulanLalu . ' setoran',
];
