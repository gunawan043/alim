<?php

/**
 * Dashboard config — Role: Super Admin / System Engineer
 *
 * Fungsi: akses penuh modul, manajemen database, role-permission, sistem log.
 * Cakupan GLOBAL.
 */

$welcome = ['welcome-banner'];

$kpiUser = [
    'stat-user-aktif',
    'stat-user-nonaktif',
    'stat-user-tanpa-role',
];

$kpiSistem = [
    'stat-total-role',
    'stat-total-permission',
    'stat-total-migrasi',
    'stat-aktivitas-total',
    'stat-aktivitas-hari',
];

$blokAktivitas = [
    'chart-aktivitas-7-hari',
    'chart-aktivitas-event',
    'table-aktivitas-terbaru',
];

$blokPengguna = [
    'chart-user-per-role',
    'table-user-terbaru',
    'table-user-tanpa-role',
];

$blokSistem = [
    'table-role-permission',
    'table-migrasi-terbaru',
];

return [

    'jabatan' => [

        // ── Super Admin ──────────────────────────────────────────
        'super_admin_system_engineer_super_admin' => [
            'label'   => 'Super Admin',
            'widgets' => [
                ...$welcome,
                ...$kpiUser,
                ...$kpiSistem,
                ...$blokAktivitas,
                ...$blokPengguna,
                ...$blokSistem,
                'quick-action-super-admin',
            ],
        ],

        // ── System Engineer ──────────────────────────────────────
        'super_admin_system_engineer_system_engineer' => [
            'label'   => 'System Engineer',
            'widgets' => [
                ...$welcome,
                'stat-user-aktif',
                ...$kpiSistem,
                ...$blokAktivitas,
                'table-role-permission',
                'table-migrasi-terbaru',
                'quick-action-super-admin',
            ],
        ],
    ],

    'tugas_tambahan' => [],

    'default' => [
        'label'   => 'Super Admin',
        'widgets' => [
            ...$welcome,
            ...$kpiUser,
            ...$kpiSistem,
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION
    // ══════════════════════════════════════════════════════════════
    'sections' => [
        'ringkasan' => ['title' => 'Ringkasan Sistem',   'icon' => 'ri-shield-user-line'],
        'pengguna'  => ['title' => 'Pengguna & Akses',   'icon' => 'ri-group-line'],
        'aktivitas' => ['title' => 'Log Aktivitas',      'icon' => 'ri-history-line'],
        'sistem'    => ['title' => 'Kesehatan Sistem',   'icon' => 'ri-server-line'],
        'lainnya'   => ['title' => 'Tindak Lanjut',      'icon' => 'ri-checkbox-multiple-line'],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION MAP
    // ══════════════════════════════════════════════════════════════
    'section_map' => [
        'welcome-banner'         => 'ringkasan',
        'stat-user-aktif'        => 'ringkasan',
        'stat-user-nonaktif'     => 'ringkasan',
        'stat-user-tanpa-role'   => 'ringkasan',
        'stat-total-role'        => 'ringkasan',
        'stat-total-permission'  => 'ringkasan',
        'stat-total-migrasi'     => 'ringkasan',
        'stat-aktivitas-total'   => 'ringkasan',
        'stat-aktivitas-hari'    => 'ringkasan',

        'chart-user-per-role'    => 'pengguna',
        'table-user-terbaru'     => 'pengguna',
        'table-user-tanpa-role'  => 'pengguna',

        'chart-aktivitas-7-hari' => 'aktivitas',
        'chart-aktivitas-event'  => 'aktivitas',
        'table-aktivitas-terbaru' => 'aktivitas',

        'table-role-permission'  => 'sistem',
        'table-migrasi-terbaru'  => 'sistem',

        'quick-action-super-admin' => 'lainnya',
    ],
];
