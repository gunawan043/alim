<?php

use Illuminate\Support\Facades\DB;

$scope = $this->getAsramaScope($user);

$roomsQuery = DB::table('dormitory_rooms')->where('is_active', 1);
if (! empty($scope->roomIds)) {
    $roomsQuery->whereIn('id', $scope->roomIds);
} elseif (! empty($scope->dormitoryIds)) {
    $roomsQuery->whereIn('dormitory_id', $scope->dormitoryIds);
}

$rooms = $roomsQuery->get(['id', 'capacity']);

$occupied = DB::table('dormitory_residents')
    ->whereIn('room_id', $rooms->pluck('id')->all() ?: ['-'])
    ->where('is_active', 1)
    ->selectRaw('room_id, COUNT(*) as total')
    ->groupBy('room_id')
    ->pluck('total', 'room_id');

$tersedia = 0;
$sisaBed = 0;

foreach ($rooms as $room) {
    $sisa = max(0, (int) $room->capacity - (int) ($occupied[$room->id] ?? 0));
    $sisaBed += $sisa;
    if ($sisa > 0) {
        $tersedia++;
    }
}

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $tersedia,
    'label' => 'Kamar Tersedia',
    'icon'  => 'ri-door-open-line',
    'color' => 'info',
    'sub'   => number_format($sisaBed, 0, ',', '.') . ' bed masih kosong',
];
