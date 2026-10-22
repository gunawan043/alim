<?php

use Illuminate\Support\Facades\Route;

$userId = $user->id;

$url = fn (string $name) => Route::has($name) ? route($name, ['userId' => $userId]) : '#';

$items = [
    ['label' => 'Pasien UKS',     'icon' => 'ri-user-heart-line',    'color' => 'danger',  'url' => $url('user.uks.patients.index')],
    ['label' => 'Farmasi',        'icon' => 'ri-capsule-line',       'color' => 'success', 'url' => $url('user.uks.pharmacy.index')],
    ['label' => 'Tempat Tidur',   'icon' => 'ri-hotel-bed-line',     'color' => 'primary', 'url' => $url('user.uks.beds.index')],
    ['label' => 'Papan Lokasi',   'icon' => 'ri-map-pin-user-line',  'color' => 'info',    'url' => $url('user.uks.care-locations.index')],
    ['label' => 'Rujukan Pasien', 'icon' => 'ri-hospital-line',      'color' => 'info',    'url' => $url('user.uks.referrals.index')],
    ['label' => 'Izin Sakit',     'icon' => 'ri-file-shield-2-line', 'color' => 'secondary', 'url' => $url('user.uks.health-permits.index')],
];

if ((string) setting('uks_mode', 'simple') === 'clinic') {
    array_unshift($items, [
        'label' => 'Antrean Klinik',
        'icon' => 'ri-list-check-2',
        'color' => 'warning',
        'url' => $url('user.uks.clinic.queue'),
    ]);
}

return [
    '_type' => 'quick-action',
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Aksi Cepat UKS',
    'items' => $items,
];
