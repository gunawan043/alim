<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = $this->scopeTahfidzQuery(
    DB::table('tahfidz_setorans as s')
        ->when($schoolId, function ($q) use ($schoolId) {
            $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from('tahfidz_groups as g')
                ->whereColumn('g.id', 's.tahfidz_group_id')
                ->where('g.school_id', $schoolId));
        })
        ->whereDate('s.setoran_date', today()),
    $user,
    's.tahfidz_group_id'
)->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Setoran Hari Ini',
    'icon'  => 'ri-book-mark-line',
    'color' => 'primary',
    'sub'   => 'Setoran tercatat hari ini',
];
