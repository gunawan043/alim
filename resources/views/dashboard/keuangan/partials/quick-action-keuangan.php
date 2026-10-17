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
    'label' => 'Aksi Cepat Keuangan',
    'items' => [
        $item('Kelola Payroll', 'ri-money-dollar-circle-line', 'primary', 'user.payroll.index', ['userId' => $userId]),
        $item('Slip Gaji', 'ri-file-text-line', 'success', 'user.payroll-slip.index', ['userId' => $userId]),
        $item('Pengadaan', 'ri-shopping-cart-2-line', 'warning', 'sarpras.user.pengadaan.index'),
        $item('Klaim Kesejahteraan', 'ri-hand-heart-line', 'info', 'user.kesejahteraan.klaim', ['userId' => $userId]),
    ],
];
