<?php

/**
 * Dashboard config — Role: UKS
 *
 * Jabatan: Kepala UKS, Staf UKS Putra, Staf UKS Putri.
 * Tugas tambahan: Dokter Jaga, Perawat Jaga, Petugas Apoteker.
 *
 * Scope gender otomatis: Staf UKS Putra → santri putra, Staf UKS Putri → santri putri.
 */

$welcome = ['welcome-banner'];

$kpiLayanan = [
    'stat-pasien-aktif',
    'stat-rawat-inap',
    'stat-kunjungan-hari-ini',
    'stat-kunjungan-bulan',
    'stat-tindakan-hari-ini',
];

$kpiFarmasi = [
    'stat-obat-menipis',
    'stat-obat-kadaluarsa',
];

$blokLayanan = [
    'chart-kunjungan-7-hari',
    'chart-pasien-per-status',
    'chart-pasien-per-jenis',
    'table-pasien-aktif',
    'table-kunjungan-terbaru',
    'table-tindakan-terbaru',
    'table-bed-okupansi',
];

$blokFarmasi = [
    'chart-obat-terbanyak',
    'table-stok-obat',
    'table-obat-terbaru',
];

$blokRujukan = [
    'stat-rujukan-bulan',
    'stat-bed-tersedia',
    'stat-istirahat-aktif',
    'chart-kunjungan-per-dormitory',
    'table-rujukan-terbaru',
    'table-istirahat-aktif',
    'table-kunjungan-klinik',
];

return [

    'jabatan' => [

        // ── Kepala UKS ───────────────────────────────────────────
        'tenaga_kesehatan_uks_kepala_uks' => [
            'label'   => 'Kepala UKS',
            'widgets' => [
                ...$welcome,
                ...$kpiLayanan,
                ...$kpiFarmasi,
                ...$blokRujukan,
                ...$blokLayanan,
                ...$blokFarmasi,
                'quick-action-uks',
            ],
        ],

        // ── Staf UKS Putra / Putri ───────────────────────────────
        'tenaga_kesehatan_uks_staf_uks_putra' => [
            'label'   => 'Staf UKS Putra',
            'widgets' => [
                ...$welcome,
                ...$kpiLayanan,
                'stat-bed-tersedia',
                'chart-kunjungan-7-hari',
                'chart-pasien-per-status',
                'table-pasien-aktif',
                'table-kunjungan-terbaru',
                'table-tindakan-terbaru',
                'table-bed-okupansi',
                'table-obat-terbaru',
                'quick-action-uks',
            ],
        ],
        'tenaga_kesehatan_uks_staf_uks_putri' => [
            'label'   => 'Staf UKS Putri',
            'widgets' => [
                ...$welcome,
                ...$kpiLayanan,
                'stat-bed-tersedia',
                'chart-kunjungan-7-hari',
                'chart-pasien-per-status',
                'table-pasien-aktif',
                'table-kunjungan-terbaru',
                'table-tindakan-terbaru',
                'table-bed-okupansi',
                'table-obat-terbaru',
                'quick-action-uks',
            ],
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // TUGAS TAMBAHAN → WIDGET
    // ══════════════════════════════════════════════════════════════
    'tugas_tambahan' => [

        'Dokter Jaga' => [
            'label'   => 'Dokter Jaga',
            'widgets' => [
                'stat-pasien-aktif',
                'stat-rawat-inap',
                'stat-kunjungan-hari-ini',
                'stat-rujukan-bulan',
                'chart-kunjungan-7-hari',
                'table-pasien-aktif',
                'table-kunjungan-terbaru',
                'table-rujukan-terbaru',
                'quick-action-uks',
            ],
        ],

        'Perawat Jaga' => [
            'label'   => 'Perawat Jaga',
            'widgets' => [
                'stat-pasien-aktif',
                'stat-rawat-inap',
                'stat-tindakan-hari-ini',
                'stat-bed-tersedia',
                'table-pasien-aktif',
                'table-tindakan-terbaru',
                'table-bed-okupansi',
                'table-obat-terbaru',
                'quick-action-uks',
            ],
        ],

        'Petugas Apoteker' => [
            'label'   => 'Petugas Apoteker',
            'widgets' => [
                'stat-obat-menipis',
                'stat-obat-kadaluarsa',
                'chart-obat-terbanyak',
                'table-stok-obat',
                'table-obat-terbaru',
                'quick-action-farmasi',
            ],
        ],
    ],

    // Fallback jabatan tidak dikenal
    'default' => [
        'label'   => 'UKS',
        'widgets' => [
            ...$welcome,
            'stat-pasien-aktif',
            'stat-kunjungan-hari-ini',
            'table-pasien-aktif',
            'table-kunjungan-terbaru',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION
    // ══════════════════════════════════════════════════════════════
    'sections' => [
        'ringkasan' => ['title' => 'Ringkasan Layanan',   'icon' => 'ri-heart-pulse-line'],
        'layanan'   => ['title' => 'Layanan Medis',       'icon' => 'ri-stethoscope-line'],
        'farmasi'   => ['title' => 'Farmasi & Obat',      'icon' => 'ri-capsule-line'],
        'rujukan'   => ['title' => 'Rujukan & Perizinan', 'icon' => 'ri-hospital-line'],
        'lainnya'   => ['title' => 'Tindak Lanjut',       'icon' => 'ri-checkbox-multiple-line'],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION MAP
    // ══════════════════════════════════════════════════════════════
    'section_map' => [
        'welcome-banner'               => 'ringkasan',
        'stat-pasien-aktif'            => 'ringkasan',
        'stat-rawat-inap'              => 'ringkasan',
        'stat-kunjungan-hari-ini'      => 'ringkasan',
        'stat-kunjungan-bulan'         => 'ringkasan',
        'stat-tindakan-hari-ini'       => 'ringkasan',
        'stat-obat-menipis'            => 'ringkasan',
        'stat-obat-kadaluarsa'         => 'ringkasan',

        'chart-kunjungan-7-hari'       => 'layanan',
        'chart-pasien-per-status'      => 'layanan',
        'chart-pasien-per-jenis'       => 'layanan',
        'table-pasien-aktif'           => 'layanan',
        'table-kunjungan-terbaru'      => 'layanan',
        'table-tindakan-terbaru'       => 'layanan',
        'table-bed-okupansi'           => 'layanan',

        'chart-obat-terbanyak'         => 'farmasi',
        'table-stok-obat'              => 'farmasi',
        'table-obat-terbaru'           => 'farmasi',

        'stat-rujukan-bulan'           => 'rujukan',
        'stat-bed-tersedia'            => 'rujukan',
        'stat-istirahat-aktif'         => 'rujukan',
        'chart-kunjungan-per-dormitory' => 'rujukan',
        'table-rujukan-terbaru'        => 'rujukan',
        'table-istirahat-aktif'        => 'rujukan',
        'table-kunjungan-klinik'       => 'rujukan',

        'quick-action-uks'             => 'lainnya',
        'quick-action-farmasi'         => 'lainnya',
    ],
];
