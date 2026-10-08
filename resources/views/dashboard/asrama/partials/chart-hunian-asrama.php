<?php

use Illuminate\Support\Facades\DB;

$scope = $this->getAsramaScope($user);

$dormitories = DB::table('dormitories')->where('is_active', 1)
    ->when(! empty($scope->dormitoryIds), fn ($q) => $q->whereIn('id', $scope->dormitoryIds))
    ->get(['id', 'name']);

$categories = [];
$percent = [];

foreach ($dormitories as $dormitory) {
    $capacity = DB::table('dormitory_rooms')->where('dormitory_id', $dormitory->id)->where('is_active', 1)->sum('capacity');
    $occupied = DB::table('dormitory_residents')->where('dormitory_id', $dormitory->id)->where('is_active', 1)->count();

    $categories[] = $dormitory->name;
    $percent[] = $capacity > 0 ? round($occupied / $capacity * 100, 1) : 0;
}

return [
    '_type'  => 'chart',
    '_col'   => 'col-xl-6 col-md-12',
    'key'    => 'hunian-asrama',
    'label'  => 'Tingkat Hunian per Asrama',
    'type'   => 'bar',
    'series' => [['name' => 'Hunian (%)', 'data' => $percent]],
    'options' => [
        'xaxis'       => ['categories' => $categories],
        'yaxis'       => ['max' => 100],
        'colors'      => ['#405189'],
        'dataLabels'  => ['enabled' => true],
        'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '45%']],
    ],
];
