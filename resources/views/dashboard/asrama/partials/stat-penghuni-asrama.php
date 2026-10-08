<?php

use Illuminate\Support\Facades\DB;

$scope = $this->getAsramaScope($user);
$count = $this->scopeAsramaQuery(DB::table('dormitory_residents')->where('is_active', 1), $user)->count();

$sub = ! empty($scope->roomIds)
    ? count($scope->roomIds) . ' kamar binaan'
    : (! empty($scope->dormitoryIds) ? count($scope->dormitoryIds) . ' asrama' : 'Seluruh asrama');

return [
    '_type' => 'stat',
    '_col'  => 'col-xl-3 col-md-6',
    'value' => $count,
    'label' => 'Penghuni Asrama',
    'icon'  => 'ri-user-3-line',
    'color' => 'primary',
    'sub'   => 'Penghuni aktif · ' . $sub,
];
