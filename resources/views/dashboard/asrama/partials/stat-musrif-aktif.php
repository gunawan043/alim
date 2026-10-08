<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$scope = $this->getAsramaScope($user);
$count = 0;

try {
    $today = now()->toDateString();

    $staff = DB::table('dormitory_staff_assignments')
        ->where('status', 'active')
        ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
        ->when(! empty($scope->dormitoryIds), fn ($q) => $q->whereIn('dormitory_id', $scope->dormitoryIds))
        ->pluck('user_id');

    $supervisors = DB::table('room_supervisors')
        ->where('status', 'active')
        ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
        ->when(! empty($scope->roomIds), fn ($q) => $q->whereIn('room_id', $scope->roomIds))
        ->when(empty($scope->roomIds) && ! empty($scope->dormitoryIds), fn ($q) => $q->whereIn('dormitory_id', $scope->dormitoryIds))
        ->pluck('user_id');

    $count = $staff->merge($supervisors)->unique()->filter()->count();
} catch (\Throwable $e) {
    Log::warning('Widget stat-musrif-aktif: ' . $e->getMessage());
}

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Musrif Aktif',
    'icon'  => 'ri-user-star-line',
    'color' => 'primary',
    'sub'   => 'Staf pengasuhan & wali kamar aktif',
];
