<?php

use Illuminate\Support\Facades\Route;

$userId = $user->id;

$item = function (string $label, string $icon, string $color, ?string $routeName, array $params = []) {
    return [
        'label' => $label,
        'icon'  => $icon,
        'color' => $color,
        'url'   => ($routeName && Route::has($routeName)) ? route($routeName, $params) : '#',
    ];
};

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Pusat Persetujuan',
    'items' => [
        $item('Persetujuan Pengadaan', 'ri-shopping-cart-2-line', 'warning', 'sarpras.pengadaan.index'),
        $item('Persetujuan Cuti / Izin', 'ri-calendar-check-line', 'primary', 'user.cuti.approval', ['userId' => $userId]),
        $item('Verifikasi Usulan Jabatan', 'ri-user-follow-line', 'info', 'user.gtk-position-proposals.index', ['userId' => $userId]),
        $item('Klaim Kesejahteraan', 'ri-hand-heart-line', 'success', 'user.kesejahteraan.klaim', ['userId' => $userId]),
    ],
];
