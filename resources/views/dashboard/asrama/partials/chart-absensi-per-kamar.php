<?php

use Illuminate\Support\Facades\DB;

$scope = $this->getAsramaScope($user);

$query = DB::table('dormitory_attendances as a')
    ->join('dormitory_rooms as r', 'r.id', '=', 'a.room_id')
    ->where('a.attendance_date', today());

if (! empty($scope->roomIds)) {
    $query->whereIn('a.room_id', $scope->roomIds);
} elseif (! empty($scope->dormitoryIds)) {
    $query->whereIn('r.dormitory_id', $scope->dormitoryIds);
}

$rows = $query
    ->groupBy('r.id', 'r.name')
    ->selectRaw("r.name, SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as hadir, COUNT(*) as total")
    ->orderBy('r.name')
    ->limit(10)
    ->get();

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'absensi-per-kamar',
    'label'  => 'Kehadiran per Kamar Hari Ini',
    'type'   => 'bar',
    'series' => [[
        'name' => 'Kehadiran (%)',
        'data' => $rows->map(fn ($row) => $row->total > 0 ? round($row->hadir / $row->total * 100, 1) : 0)->all(),
    ]],
    'options' => [
        'xaxis'       => ['categories' => $rows->pluck('name')->all()],
        'yaxis'       => ['max' => 100],
        'colors'      => ['#299cdb'],
        'dataLabels'  => ['enabled' => false],
        'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '45%']],
    ],
];
