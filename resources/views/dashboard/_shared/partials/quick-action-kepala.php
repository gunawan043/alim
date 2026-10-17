<?php

use Illuminate\Support\Facades\Route;

$userId = $user->id;

$item = function (string $label, string $icon, string $color, ?string $routeName) use ($userId) {
    return [
        'label' => $label,
        'icon'  => $icon,
        'color' => $color,
        'url'   => ($routeName && Route::has($routeName))
            ? route($routeName, ['userId' => $userId])
            : '#',
    ];
};

return [
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Aksi Cepat',
    'items' => [
        $item('Kelola Data GTK', 'ri-team-line', 'primary', 'user.gtk.index'),
        $item('Tinjau SK GTK', 'ri-file-text-line', 'success', 'user.gtk-positions.index'),
        $item('Approve Pengajuan GTK', 'ri-mail-unread-line', 'warning', 'user.gtk-requests.index'),
        $item('Panel Disiplin Santri', 'ri-alert-line', 'danger', 'user.violation-points.dashboard'),
    ],
];
