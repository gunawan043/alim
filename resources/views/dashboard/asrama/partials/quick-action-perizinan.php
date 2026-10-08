<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

$userId = $user->id;
$scope = $this->getAsramaScope($user);
$dormUuid = $scope->dormitoryIds[0] ?? DB::table('dormitories')->where('is_active', 1)->value('id');

$url = fn (string $routeName) => ($dormUuid && Route::has($routeName))
    ? route($routeName, ['userId' => $userId, 'asramaUuid' => $dormUuid])
    : '#';

return [
    '_type' => 'quick-action',
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Aksi Cepat Perizinan',
    'items' => [
        ['label' => 'Daftar Izin',        'icon' => 'ri-file-list-3-line',     'color' => 'primary', 'url' => $url('user.asrama.permits.index')],
        ['label' => 'Verifikasi Izin',    'icon' => 'ri-shield-check-line',    'color' => 'success', 'url' => Route::has('user.asrama.permits.verify') ? route('user.asrama.permits.verify', ['userId' => $userId]) : '#'],
        ['label' => 'Cetak Kartu Izin',   'icon' => 'ri-printer-line',         'color' => 'info',    'url' => $url('user.asrama.permits.bulk-card')],
        ['label' => 'Pengaturan Izin',    'icon' => 'ri-settings-4-line',      'color' => 'warning', 'url' => $url('user.asrama.leave-policies.index')],
    ],
];
