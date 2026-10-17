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
    '_col'  => 'col-xl-4 col-md-12',
    'label' => 'Aksi Cepat Wali Kelas',
    'items' => [
        $item('Ambil Absensi', 'ri-user-follow-line', 'primary', 'user.absensi.harian.create'),
        $item('Input Nilai', 'ri-edit-circle-line', 'success', 'user.schools.nilai-kelas.index'),
        $item('Catat Pelanggaran', 'ri-alert-line', 'warning', 'user.peraturan.violation'),
        $item('Data Santri', 'ri-group-line', 'info', 'user.students.index'),
    ],
];
