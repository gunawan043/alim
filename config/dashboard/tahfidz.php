<?php

/**
 * Dashboard config — Role: Departemen Tahfidz
 *
 * Jabatan: Kepala/Wakil Kepala Departemen Tahfidz, TU/Staf TU Departemen Tahfidz.
 * Tugas tambahan: Penguji Tasmi', Penguji UTHQ, Musyrif Tahfidz, Koordinator Halaqah.
 *
 * Scope halaqah otomatis untuk musyrif/koordinator (lihat TahfidzDashboardController).
 * Catatan: modul web Tahfidz belum ada (baru API mobile), sehingga tidak ada quick action.
 */

$welcome = ['welcome-banner'];

$kpiRingkasan = [
    'stat-santri-tahfidz',
    'stat-halaqah-aktif',
    'stat-musyrif-tahfidz',
    'stat-setoran-hari-ini',
    'stat-setoran-bulan',
    'stat-kelulusan-setoran',
    'stat-target-tercapai',
    'stat-mutabaah-hari-ini',
    'stat-kehadiran-halaqah',
];

$kpiUjian = [
    'stat-tasmi-terjadwal',
    'stat-peserta-uthq',
    'stat-syahadah-bulan',
];

$blokSetoran = [
    'chart-setoran-7-hari',
    'chart-setoran-per-jenis',
    'chart-mutabaah-ibadah',
    'table-setoran-terbaru',
    'table-mutabaah-terbaru',
    'table-progres-santri',
];

$blokHalaqah = [
    'chart-kehadiran-halaqah-7-hari',
    'chart-nilai-per-halaqah',
    'table-halaqah-aktif',
    'table-kehadiran-terbaru',
    'table-jadwal-halaqah',
    'table-target-hafalan',
];

$blokUjian = [
    'chart-tasmi-predikat',
    'table-tasmi-terjadwal',
    'table-tasmi-hasil',
    'table-uthq-aktif',
    'table-peserta-uthq',
];

$blokAdministrasi = [
    'table-syahadah-terbaru',
    'table-muqorrar-aktif',
];

return [

    'jabatan' => [

        // ── Kepala Departemen Tahfidz ────────────────────────────
        'departemen_tahfidz_kepala_departemen_tahfidz' => [
            'label'   => 'Kepala Departemen Tahfidz',
            'widgets' => [
                ...$welcome,
                ...$kpiRingkasan,
                ...$kpiUjian,
                ...$blokSetoran,
                ...$blokHalaqah,
                ...$blokUjian,
                ...$blokAdministrasi,
            ],
        ],

        // ── Wakil Kepala Departemen Tahfidz ──────────────────────
        'departemen_tahfidz_wakil_kepala_departemen_tahfidz' => [
            'label'   => 'Wakil Kepala Departemen Tahfidz',
            'widgets' => [
                ...$welcome,
                ...$kpiRingkasan,
                ...$kpiUjian,
                ...$blokSetoran,
                ...$blokHalaqah,
                ...$blokUjian,
                ...$blokAdministrasi,
            ],
        ],

        // ── TU / Staf TU Departemen Tahfidz ──────────────────────
        'departemen_tahfidz_tata_usaha_departemen_tahfidz' => [
            'label'   => 'Tata Usaha Departemen Tahfidz',
            'widgets' => [
                ...$welcome,
                ...$kpiRingkasan,
                ...$kpiUjian,
                'chart-setoran-7-hari',
                'chart-setoran-per-jenis',
                'table-halaqah-aktif',
                'table-jadwal-halaqah',
                'table-target-hafalan',
                'table-muqorrar-aktif',
                'table-syahadah-terbaru',
                'table-uthq-aktif',
                'table-progres-santri',
            ],
        ],
        'departemen_tahfidz_tu_departemen_tahfidz' => [
            'label'   => 'TU Departemen Tahfidz',
            'widgets' => [
                ...$welcome,
                ...$kpiRingkasan,
                ...$kpiUjian,
                'chart-setoran-7-hari',
                'chart-setoran-per-jenis',
                'table-halaqah-aktif',
                'table-jadwal-halaqah',
                'table-target-hafalan',
                'table-muqorrar-aktif',
                'table-syahadah-terbaru',
                'table-uthq-aktif',
                'table-progres-santri',
            ],
        ],
        'departemen_tahfidz_staf_tu_departemen_tahfidz' => [
            'label'   => 'Staf TU Departemen Tahfidz',
            'widgets' => [
                ...$welcome,
                'stat-santri-tahfidz',
                'stat-halaqah-aktif',
                'stat-setoran-bulan',
                'stat-syahadah-bulan',
                'table-halaqah-aktif',
                'table-jadwal-halaqah',
                'table-target-hafalan',
                'table-syahadah-terbaru',
                'table-muqorrar-aktif',
            ],
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // TUGAS TAMBAHAN → WIDGET
    // ══════════════════════════════════════════════════════════════
    'tugas_tambahan' => [

        'Musyrif Tahfidz' => [
            'label'   => 'Musyrif Tahfidz',
            'widgets' => [
                'stat-santri-tahfidz',
                'stat-setoran-hari-ini',
                'stat-setoran-bulan',
                'stat-kelulusan-setoran',
                'stat-target-tercapai',
                'stat-mutabaah-hari-ini',
                'stat-kehadiran-halaqah',
                'chart-setoran-7-hari',
                'chart-setoran-per-jenis',
                'chart-mutabaah-ibadah',
                'chart-nilai-per-halaqah',
                'table-setoran-terbaru',
                'table-mutabaah-terbaru',
                'table-kehadiran-terbaru',
                'table-target-hafalan',
                'table-progres-santri',
            ],
        ],

        'Koordinator Halaqah' => [
            'label'   => 'Koordinator Halaqah',
            'widgets' => [
                'stat-santri-tahfidz',
                'stat-halaqah-aktif',
                'stat-musyrif-tahfidz',
                'stat-kehadiran-halaqah',
                'chart-kehadiran-halaqah-7-hari',
                'chart-nilai-per-halaqah',
                'chart-setoran-7-hari',
                'table-halaqah-aktif',
                'table-jadwal-halaqah',
                'table-kehadiran-terbaru',
                'table-setoran-terbaru',
            ],
        ],

        'Penguji Tasmi\'' => [
            'label'   => "Penguji Tasmi'",
            'widgets' => [
                'stat-tasmi-terjadwal',
                'chart-tasmi-predikat',
                'table-tasmi-terjadwal',
                'table-tasmi-hasil',
                'table-setoran-terbaru',
            ],
        ],

        'Penguji UTHQ' => [
            'label'   => 'Penguji UTHQ',
            'widgets' => [
                'stat-peserta-uthq',
                'table-uthq-aktif',
                'table-peserta-uthq',
                'table-tasmi-hasil',
            ],
        ],
    ],

    // Fallback jabatan tidak dikenal
    'default' => [
        'label'   => 'Departemen Tahfidz',
        'widgets' => [
            ...$welcome,
            'stat-santri-tahfidz',
            'stat-halaqah-aktif',
            'stat-setoran-bulan',
            'table-setoran-terbaru',
            'table-halaqah-aktif',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION
    // ══════════════════════════════════════════════════════════════
    'sections' => [
        'ringkasan'    => ['title' => 'Ringkasan Tahfidz',        'icon' => 'ri-book-2-line'],
        'setoran'      => ['title' => "Setoran & Mutaba'ah",       'icon' => 'ri-clipboard-line'],
        'halaqah'      => ['title' => 'Halaqah & Kehadiran',       'icon' => 'ri-group-line'],
        'ujian'        => ['title' => "Tasmi' & UTHQ",             'icon' => 'ri-award-line'],
        'administrasi' => ['title' => 'Administrasi & Syahadah',   'icon' => 'ri-file-text-line'],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION MAP
    // ══════════════════════════════════════════════════════════════
    'section_map' => [
        'welcome-banner'              => 'ringkasan',
        'stat-santri-tahfidz'         => 'ringkasan',
        'stat-halaqah-aktif'          => 'ringkasan',
        'stat-musyrif-tahfidz'        => 'ringkasan',
        'stat-setoran-hari-ini'       => 'ringkasan',
        'stat-setoran-bulan'          => 'ringkasan',
        'stat-kelulusan-setoran'      => 'ringkasan',
        'stat-target-tercapai'        => 'ringkasan',
        'stat-mutabaah-hari-ini'      => 'ringkasan',
        'stat-kehadiran-halaqah'      => 'ringkasan',
        'stat-tasmi-terjadwal'        => 'ringkasan',
        'stat-peserta-uthq'           => 'ringkasan',
        'stat-syahadah-bulan'         => 'ringkasan',

        'chart-setoran-7-hari'        => 'setoran',
        'chart-setoran-per-jenis'     => 'setoran',
        'chart-mutabaah-ibadah'       => 'setoran',
        'table-setoran-terbaru'       => 'setoran',
        'table-mutabaah-terbaru'      => 'setoran',
        'table-progres-santri'        => 'setoran',

        'chart-kehadiran-halaqah-7-hari' => 'halaqah',
        'chart-nilai-per-halaqah'     => 'halaqah',
        'table-halaqah-aktif'         => 'halaqah',
        'table-kehadiran-terbaru'     => 'halaqah',
        'table-jadwal-halaqah'        => 'halaqah',
        'table-target-hafalan'        => 'halaqah',

        'chart-tasmi-predikat'        => 'ujian',
        'table-tasmi-terjadwal'       => 'ujian',
        'table-tasmi-hasil'           => 'ujian',
        'table-uthq-aktif'            => 'ujian',
        'table-peserta-uthq'          => 'ujian',

        'table-syahadah-terbaru'      => 'administrasi',
        'table-muqorrar-aktif'        => 'administrasi',
    ],
];
