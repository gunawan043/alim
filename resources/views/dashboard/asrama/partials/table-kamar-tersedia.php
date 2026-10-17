<?php

use Illuminate\Support\Facades\DB;

$scope = $this->getAsramaScope($user);

$roomsQuery = DB::table('dormitory_rooms as r')
    ->leftJoin('dormitories as d', 'd.id', '=', 'r.dormitory_id')
    ->where('r.is_active', 1);

if (! empty($scope->roomIds)) {
    $roomsQuery->whereIn('r.id', $scope->roomIds);
} elseif (! empty($scope->dormitoryIds)) {
    $roomsQuery->whereIn('r.dormitory_id', $scope->dormitoryIds);
}

$rooms = $roomsQuery->get(['r.id', 'r.name', 'r.capacity', 'd.name as asrama']);

$occupied = DB::table('dormitory_residents')
    ->whereIn('room_id', $rooms->pluck('id')->all() ?: ['-'])
    ->where('is_active', 1)
    ->selectRaw('room_id, COUNT(*) as total')
    ->groupBy('room_id')
    ->pluck('total', 'room_id');

$items = $rooms
    ->map(fn ($room) => (object) [
        'kamar'    => $room->name,
        'asrama'   => $room->asrama,
        'kapasitas' => (int) $room->capacity,
        'terisi'   => (int) ($occupied[$room->id] ?? 0),
        'sisa'     => max(0, (int) $room->capacity - (int) ($occupied[$room->id] ?? 0)),
    ])
    ->filter(fn ($item) => $item->sisa > 0)
    ->sortByDesc('sisa')
    ->take(10)
    ->values()
    ->all();

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Kamar Masih Tersedia',
    'items' => $items,
];
