<?php

use Illuminate\Support\Facades\DB;

$scope = $this->getAsramaScope($user);

$rooms = DB::table('dormitory_rooms')->where('is_active', 1);
if (! empty($scope->roomIds)) {
    $rooms->whereIn('id', $scope->roomIds);
} elseif (! empty($scope->dormitoryIds)) {
    $rooms->whereIn('dormitory_id', $scope->dormitoryIds);
}

$capacity = (clone $rooms)->sum('capacity');
$occupied = $this->scopeAsramaQuery(DB::table('dormitory_residents')->where('is_active', 1), $user)->count();
$percent = $capacity > 0 ? round($occupied / $capacity * 100, 1) : 0;

return [
    '_type'  => 'stat',
    '_col'   => 'col-xl-3 col-md-6',
    'value'  => $percent,
    'suffix' => '%',
    'label'  => 'Tingkat Hunian',
    'icon'   => 'ri-hotel-bed-line',
    'color'  => $percent > 95 ? 'danger' : ($percent > 80 ? 'warning' : 'success'),
    'sub'    => number_format($occupied, 0, ',', '.') . ' dari ' . number_format($capacity, 0, ',', '.') . ' bed terisi',
];
