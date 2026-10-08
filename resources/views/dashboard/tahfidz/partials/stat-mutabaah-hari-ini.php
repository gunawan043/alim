<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = $this->scopeTahfidzQuery(
    DB::table('tahfidz_mutabaah as m')
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from('tahfidz_groups as g')
                ->whereColumn('g.id', 'm.tahfidz_group_id')
                ->where('g.school_id', $schoolId));
        })
        ->whereDate('m.record_date', today()),
    $user,
    'm.tahfidz_group_id'
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => "Mutaba'ah Hari Ini",
    'icon'  => 'ri-clipboard-line',
    'color' => 'info',
    'sub'   => 'Catatan ibadah harian masuk',
];
