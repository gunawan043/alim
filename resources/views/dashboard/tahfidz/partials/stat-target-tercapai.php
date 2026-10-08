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
)->selectRaw("SUM(CASE WHEN s.capaian_target IN ('tercapai','melampaui') THEN 1 ELSE 0 END) as capai, COUNT(*) as total")
    ->first();

$capai = (int) ($row->capai ?? 0);
$total = (int) ($row->total ?? 0);
$percent = $total > 0 ? round($capai / $total * 100, 1) : 0;

return [
    '_type'  => 'stat',
    '_col'   => 'col-xl-3 col-md-6',
    'value'  => $percent,
    'suffix' => '%',
    'label'  => 'Target Tercapai',
    'icon'   => 'ri-focus-3-line',
    'color'  => $percent >= 70 ? 'success' : ($percent >= 40 ? 'warning' : 'danger'),
    'sub'    => $capai . ' dari ' . $total . ' setoran mencapai target',
];
