<?php

use Illuminate\Support\Facades\Route;

$userId = $user->id;

$url = fn (string $name, array $params = []) => Route::has($name)
    ? route($name, array_merge(['userId' => $userId], $params))
    : '#';

return [
    '_type' => 'quick-action',
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Aksi Cepat Personalia',
    'items' => [
        ['label' => 'Data GTK',    'icon' => 'ri-team-line',            'color' => 'primary', 'url' => $url('user.gtk.index')],
        ['label' => 'Approval Cuti', 'icon' => 'ri-calendar-check-line', 'color' => 'warning', 'url' => $url('user.cuti.approval')],
        ['label' => 'Absensi GTK', 'icon' => 'ri-fingerprint-line',     'color' => 'success', 'url' => $url('user.absensi-gtk.index')],
        ['label' => 'Kinerja',     'icon' => 'ri-medal-line',           'color' => 'info',    'url' => $url('user.kinerja.index')],
        ['label' => 'Rekrutmen',   'icon' => 'ri-user-search-line',     'color' => 'danger',  'url' => $url('user.recruitment.index')],
        ['label' => 'Cuti & Izin', 'icon' => 'ri-calendar-todo-line',   'color' => 'secondary', 'url' => $url('user.cuti.index')],
    ],
];
