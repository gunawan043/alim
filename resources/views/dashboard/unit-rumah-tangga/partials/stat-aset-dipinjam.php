<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = DB::table('assets')
    ->whereNull('deleted_at')
    ->where('status', 'dipinjam')
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Aset Dipinjam',
    'icon'  => 'ri-hand-coin-line',
    'color' => 'info',
    'sub'   => 'Sedang dipinjam pengguna',
];
