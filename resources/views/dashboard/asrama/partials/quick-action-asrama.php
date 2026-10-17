<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

$userId = $user->id;
$scope = $this->getAsramaScope($user);
$dormUuid = $scope->dormitoryIds[0] ?? DB::table('dormitories')->where('is_active', 1)->value('id');

return [
    '_type' => 'quick-action',
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Aksi Cepat Asrama',
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
            'label' => 'Ajukan Izin',
            'icon'  => 'ri-walk-line',
            'color' => 'warning',
            'url'   => ($dormUuid && Route::has('user.asrama.permits.create'))
                ? route('user.asrama.permits.create', ['userId' => $userId, 'asramaUuid' => $dormUuid])
                : '#',
        ],
        [
            'label' => 'Approval Center',
            'icon'  => 'ri-checkbox-multiple-line',
            'color' => 'success',
            'url'   => ($dormUuid && Route::has('user.asrama.approval-center'))
                ? route('user.asrama.approval-center', ['userId' => $userId, 'asramaUuid' => $dormUuid])
                : '#',
        ],
        [
            'label' => 'Informasi Asrama',
            'icon'  => 'ri-megaphone-line',
            'color' => 'info',
            'url'   => ($dormUuid && Route::has('user.asrama.posts.index'))
                ? route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $dormUuid])
                : '#',
        ],
    ],
];
