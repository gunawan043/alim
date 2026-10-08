<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = DB::table('student_medicine_inventory')
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->whereColumn('current_stock', '<=', 'min_stock_alert')
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Obat Menipis',
    'icon'  => 'ri-capsule-line',
    'color' => $count > 0 ? 'warning' : 'success',
    'sub'   => 'Stok di bawah batas minimum',
];
