<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

$schoolId = $this->getUserSchoolId($user);
$total = 0;

try {
    if (Schema::hasTable('lab_item_profiles') && Schema::hasTable('lab_profiles')) {
        $total = DB::table('lab_item_profiles as li')
            ->join('lab_profiles as lp', 'lp.id', '=', 'li.lab_id')
            ->join('assets as a', 'a.id', '=', 'li.asset_id')
            ->whereNull('lp.deleted_at')
            ->where('lp.is_active', 1)
            ->whereNull('a.deleted_at')
            ->where('a.is_active', 1)
            ->when($schoolId, fn ($q) => $q->where('lp.school_id', $schoolId))
            ->count();
    } else {
        $total = DB::table('assets as a')
            ->join('asset_categories as c', 'c.id', '=', 'a.asset_category_id')
            ->whereNull('a.deleted_at')
            ->where('a.is_active', 1)
            ->when($schoolId, fn ($q) => $q->where('a.school_id', $schoolId))
            ->where(function ($q) {
                $q->where('c.name', 'like', '%lab%')
                    ->orWhere('c.code', 'like', '%LAB%')
                    ->orWhere('a.asset_name', 'like', '%lab%');
            })
            ->count();
    }
} catch (\Throwable $e) {
    Log::warning('Widget stat-total-alat-lab: ' . $e->getMessage());
}

return [
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $total,
    'label' => 'Total Alat Laboratorium',
    'icon'  => 'ri-flask-line',
    'color' => 'info',
    'sub'   => 'Aset kategori laboratorium',
];
