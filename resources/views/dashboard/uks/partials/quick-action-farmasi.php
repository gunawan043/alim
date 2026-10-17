<?php

use Illuminate\Support\Facades\Route;

$userId = $user->id;

$url = fn (string $name) => Route::has($name) ? route($name, ['userId' => $userId]) : '#';

return [
    '_type' => 'quick-action',
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Aksi Cepat Farmasi',
    'items' => [
        ['label' => 'Stok Obat',      'icon' => 'ri-capsule-line',        'color' => 'primary', 'url' => $url('user.uks.medicine-inventory.index')],
        ['label' => 'Tambah Obat',    'icon' => 'ri-add-circle-line',     'color' => 'success', 'url' => $url('user.uks.medicine-inventory.create')],
        ['label' => 'Riwayat Obat',   'icon' => 'ri-history-line',        'color' => 'info',    'url' => $url('user.uks.medicine-logs.index')],
        ['label' => 'Kunjungan',      'icon' => 'ri-stethoscope-line',    'color' => 'warning', 'url' => $url('user.uks.visits.index')],
    ],
];
