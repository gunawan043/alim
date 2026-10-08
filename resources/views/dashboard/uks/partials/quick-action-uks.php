<?php

use Illuminate\Support\Facades\Route;

$userId = $user->id;

$url = fn (string $name) => Route::has($name) ? route($name, ['userId' => $userId]) : '#';

return [
    '_type' => 'quick-action',
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Aksi Cepat UKS',
    'items' => [
        ['label' => 'Pasien UKS',   'icon' => 'ri-user-heart-line',    'color' => 'danger',  'url' => $url('user.uks.patients.index')],
        ['label' => 'Tempat Tidur', 'icon' => 'ri-hotel-bed-line',     'color' => 'primary', 'url' => $url('user.uks.beds.index')],
        ['label' => 'Perizinan Sehat', 'icon' => 'ri-file-shield-2-line', 'color' => 'warning', 'url' => $url('user.uks.health-permits.index')],
        ['label' => 'Rujukan Faskes', 'icon' => 'ri-hospital-line',    'color' => 'info',    'url' => $url('user.uks.facility-referrals.index')],
    ],
];
