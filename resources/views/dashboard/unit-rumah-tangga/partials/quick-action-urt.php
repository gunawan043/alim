<?php

use Illuminate\Support\Facades\Route;

$url = fn (string $name) => Route::has($name) ? route($name) : '#';

return [
    '_type' => 'quick-action',
    '_col' => 'col-xl-6 col-12',
    'label' => 'Aksi Cepat URT',
    'items' => [
        ['label' => 'Scan QR Aset',      'icon' => 'ri-qr-scan-2-line',      'color' => 'danger',  'url' => $url('sarpras.qr.scanner')],
        ['label' => 'Alur Kerja URT',    'icon' => 'ri-flow-chart',          'color' => 'primary', 'url' => $url('sarpras.workflow.index')],
        ['label' => 'Penugasan & Area',  'icon' => 'ri-user-settings-line',  'color' => 'success', 'url' => $url('sarpras.workflow.assignment')],
        ['label' => 'Laporan Penugasan', 'icon' => 'ri-file-list-3-line',    'color' => 'warning', 'url' => $url('sarpras.assignment-report.index')],
        ['label' => 'Dashboard Sarpras', 'icon' => 'ri-dashboard-3-line',    'color' => 'primary', 'url' => $url('sarpras.dashboard')],
        ['label' => 'Data Aset',         'icon' => 'ri-archive-2-line',      'color' => 'success', 'url' => $url('sarpras.aset.index')],
        ['label' => 'Jadwal Pemeliharaan', 'icon' => 'ri-calendar-todo-line', 'color' => 'warning', 'url' => $url('sarpras.pemeliharaan.schedule.index')],
        ['label' => 'Log Pemeliharaan',  'icon' => 'ri-history-line',        'color' => 'info',    'url' => $url('sarpras.pemeliharaan.log.index')],
        ['label' => 'Pergerakan Stok',   'icon' => 'ri-stack-line',          'color' => 'secondary', 'url' => $url('sarpras.movements.index')],
        ['label' => 'Peminjaman Aset',   'icon' => 'ri-hand-coin-line',      'color' => 'danger',  'url' => $url('sarpras.peminjaman.index')],
    ],
];
