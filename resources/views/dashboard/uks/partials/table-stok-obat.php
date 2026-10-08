<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$items = DB::table('student_medicine_inventory')
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->orderByRaw('CASE WHEN current_stock <= min_stock_alert THEN 0 ELSE 1 END')
    ->orderBy('expiry_date')
    ->limit(12)
    ->get([
        'medicine_name', 'category', 'unit', 'current_stock',
        'min_stock_alert', 'expiry_date', 'storage_location',
    ])
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Stok Obat',
    'items' => $items,
];
