<?php

use Illuminate\Support\Facades\Route;

$url = fn (string $name) => Route::has($name) ? route($name) : '#';

return [
    '_type' => 'quick-action',
    '_col'  => 'col-xl-6 col-12',
    'label' => 'Akses Cepat Sistem',
    'items' => [
        ['label' => 'System Dashboard', 'icon' => 'ri-dashboard-3-line',  'color' => 'primary', 'url' => $url('system.dashboard')],
        ['label' => 'Monitoring',       'icon' => 'ri-pulse-line',        'color' => 'success', 'url' => $url('system.monitoring')],
        ['label' => 'Maintenance',      'icon' => 'ri-tools-line',        'color' => 'warning', 'url' => $url('system.maintenance')],
        ['label' => 'Features',         'icon' => 'ri-apps-2-line',       'color' => 'info',    'url' => $url('system.features')],
        ['label' => 'Config',           'icon' => 'ri-settings-4-line',   'color' => 'secondary', 'url' => $url('system.config')],
        ['label' => 'Dev Tools',        'icon' => 'ri-code-s-slash-line', 'color' => 'danger',  'url' => $url('system.devtools')],
    ],
];
