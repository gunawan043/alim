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
        $item('Data Santri', 'ri-group-line', 'primary', 'user.students.index'),
        $item('Tambah Santri', 'ri-user-add-line', 'success', 'user.students.create'),
        $item('Import Santri', 'ri-upload-2-line', 'info', 'user.students.import-form'),
        $item('Data GTK', 'ri-team-line', 'warning', 'user.gtk.index'),
    ],
];
