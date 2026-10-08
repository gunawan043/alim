<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

$userId = $user->id;
$scope = $this->getAsramaScope($user);
$dormUuid = $scope->dormitoryIds[0] ?? DB::table('dormitories')->where('is_active', 1)->value('id');

$url = fn (string $routeName, array $params = []) => ($routeName && Route::has($routeName))
    ? route($routeName, $params)
    : '#';

return [
    '_type' => 'quick-action',
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Aksi Cepat Piket',
    'items' => [
        [
            'label' => 'Catat Absensi',
            'icon'  => 'ri-calendar-check-line',
            'color' => 'primary',
            'url'   => ($dormUuid && Route::has('user.asrama.attendance.create'))
                ? route('user.asrama.attendance.create', ['userId' => $userId, 'asramaUuid' => $dormUuid])
                : '#',
        ],
        [
            'label' => 'Kunjungan',
            'icon'  => 'ri-user-shared-line',
            'color' => 'info',
            'url'   => $url('user.calendar.visit.index', ['userId' => $userId]),
        ],
        [
            'label' => 'Izin Aktif',
            'icon'  => 'ri-walk-line',
            'color' => 'warning',
            'url'   => ($dormUuid && Route::has('user.asrama.permits.index'))
                ? route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $dormUuid])
                : '#',
        ],
        [
            'label' => 'Data Asrama',
            'icon'  => 'ri-home-smile-2-line',
            'color' => 'success',
            'url'   => $url('user.asrama.index', ['userId' => $userId]),
        ],
    ],
];
