<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$row = $this->scopeTahfidzQuery(
    DB::table('tahfidz_setorans as s')
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from('tahfidz_groups as g')
                ->whereColumn('g.id', 's.tahfidz_group_id')
                ->where('g.school_id', $schoolId));
        })
        ->whereYear('s.setoran_date', now()->year)
        ->whereMonth('s.setoran_date', now()->month),
    $user,
    's.tahfidz_group_id'
)->selectRaw("SUM(CASE WHEN s.status = 'lulus' THEN 1 ELSE 0 END) as lulus, COUNT(*) as total")
    ->first();

$lulus = (int) ($row->lulus ?? 0);
$total = (int) ($row->total ?? 0);
$percent = $total > 0 ? round($lulus / $total * 100, 1) : 0;

return [
    '_type'  => 'stat',
    '_col'   => 'col-xl-3 col-md-6',
    'value'  => $percent,
    'suffix' => '%',
    'label'  => 'Kelulusan Setoran',
    'icon'   => 'ri-checkbox-circle-line',
    'color'  => $percent >= 80 ? 'success' : ($percent >= 60 ? 'warning' : 'danger'),
    'sub'    => $lulus . ' lulus dari ' . $total . ' setoran bulan ini',
];
