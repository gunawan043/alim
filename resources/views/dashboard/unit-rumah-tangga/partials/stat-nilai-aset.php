<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$total = DB::table('assets')
    ->whereNull('deleted_at')
    ->where('is_active', 1)
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->sum(DB::raw('COALESCE(current_value, acquisition_price, 0)'));

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => null,
    'label' => 'Nilai Aset',
    'icon'  => 'ri-money-cny-circle-line',
    'color' => 'success',
    'sub'   => 'Rp ' . number_format((float) $total, 0, ',', '.'),
];
