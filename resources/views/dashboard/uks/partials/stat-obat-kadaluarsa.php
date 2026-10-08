<?php

use Illuminate\Support\Facades\DB;

$schoolId = $this->getUserSchoolId($user);

$count = DB::table('student_medicine_inventory')
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->whereNotNull('expiry_date')
    ->whereDate('expiry_date', '<=', now()->addDays(60))
    ->count();

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Obat Kadaluarsa',
    'icon'  => 'ri-error-warning-line',
    'color' => $count > 0 ? 'danger' : 'success',
    'sub'   => 'Kadaluarsa / ≤ 60 hari lagi',
];
