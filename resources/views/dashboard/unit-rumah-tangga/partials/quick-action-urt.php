<?php

use Illuminate\Support\Facades\Route;

$url = fn (string $name) => Route::has($name) ? route($name) : '#';

return [
    '_type' => 'quick-action',
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Aksi Cepat URT',
    'items' => [
        ['label' => 'Dashboard Sarpras', 'icon' => 'ri-dashboard-3-line',    'color' => 'primary', 'url' => $url('sarpras.dashboard')],
        ['label' => 'Data Aset',         'icon' => 'ri-archive-2-line',      'color' => 'success', 'url' => $url('sarpras.aset.index')],
        ['label' => 'Jadwal Pemeliharaan', 'icon' => 'ri-calendar-todo-line', 'color' => 'warning', 'url' => $url('sarpras.pemeliharaan.schedule.index')],
        ['label' => 'Log Pemeliharaan',  'icon' => 'ri-history-line',        'color' => 'info',    'url' => $url('sarpras.pemeliharaan.log.index')],
        ['label' => 'Pergerakan Stok',   'icon' => 'ri-stack-line',          'color' => 'secondary', 'url' => $url('sarpras.movements.index')],
        ['label' => 'Peminjaman Aset',   'icon' => 'ri-hand-coin-line',      'color' => 'danger',  'url' => $url('sarpras.peminjaman.index')],
    ],
];
