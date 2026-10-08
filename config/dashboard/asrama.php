<?php

/**
 * Dashboard config — Role: Asrama
 *
 * Jabatan: Kepala/Wakil Kepala Asrama, Musrif/Musrifah/Musyrif/Musyrifah,
 * TU Asrama, Staf Perizinan.
 * Tugas tambahan: Wali Kamar, Pembina Kegiatan Asrama, Petugas Piket Asrama.
 *
 * Semua data mengikuti scope user (lihat AsramaDashboardController::getAsramaScope).
 */

$welcome = ['welcome-banner'];

// Penghuni & hunian
$kpiPenghuni = [
    'stat-penghuni-asrama',
    'stat-occupancy-asrama',
    'stat-kamar-tersedia',
    'stat-penghuni-masuk-bulan',
    'stat-penghuni-keluar-bulan',
];

// Status keberadaan santri (boarding)
$kpiKeberadaan = [
    'stat-santri-di-asrama',
    'stat-boarding-izin',
    'stat-boarding-sakit',
    'stat-boarding-kegiatan',
];

// Absensi & kegiatan
$blokAbsensi = [
    'stat-kehadiran-malam',
    'stat-sesi-belum-diisi',
    'chart-kehadiran-sesi-hari-ini',
    'chart-tren-kehadiran-asrama',
    'chart-absensi-per-kamar',
    'table-sesi-belum-diisi',
    'table-rekap-absensi-bulanan',
    'table-kegiatan-hari-ini',
    'chart-partisipasi-kegiatan',
];

// Perizinan
$blokPerizinan = [
    'chart-izin-jenis',
    'chart-izin-bulanan',
    'table-izin-pending',
    'table-izin-aktif',
    'table-izin-overdue',
];

// Tata tertib & prestasi
$blokTataTertib = [
    'stat-pelanggaran-bulan',
    'stat-penghargaan-bulan',
    'chart-pelanggaran-kategori',
    'table-pelanggaran-asrama',
    'table-poin-tertinggi',
    'table-penghargaan-terbaru',
];

// Operasional & komunikasi
$blokOperasional = [
    'stat-kunjungan-hari-ini',
    'table-kunjungan-terbaru',
    'stat-inventaris-rusak',
    'table-inventaris-rusak',
    'table-pengumuman-asrama',
];

// Kamar
$blokKamar = [
    'chart-hunian-asrama',
    'table-kamar-tersedia',
    'table-pindah-kamar',
    'table-penghuni-terbaru',
];

return [

    'jabatan' => [

        // ── Kepala Asrama ────────────────────────────────────────
        'tenaga_pengasuhan_keasramaan_kepala_asrama' => [
            'label'   => 'Kepala Asrama',
            'widgets' => [
                ...$welcome,
                ...$kpiPenghuni,
                ...$kpiKeberadaan,
                'stat-musrif-aktif',
                'stat-izin-pending',
                'stat-izin-overdue',
                ...$blokAbsensi,
                ...$blokPerizinan,
                ...$blokKamar,
                ...$blokTataTertib,
                ...$blokOperasional,
                'quick-action-asrama',
            ],
        ],

        // ── Wakil Kepala Asrama ──────────────────────────────────
        'tenaga_pengasuhan_keasramaan_wakil_kepala_asrama' => [
            'label'   => 'Wakil Kepala Asrama',
            'widgets' => [
                ...$welcome,
                ...$kpiPenghuni,
                ...$kpiKeberadaan,
                'stat-musrif-aktif',
                'stat-izin-pending',
                'stat-izin-overdue',
                ...$blokAbsensi,
                ...$blokPerizinan,
                ...$blokKamar,
                ...$blokTataTertib,
                ...$blokOperasional,
                'quick-action-asrama',
            ],
        ],

        // ── Musrif / Musrifah / Musyrif / Musyrifah ──────────────
        'tenaga_pengasuhan_keasramaan_musrif' => [
            'label'   => 'Musrif',
            'widgets' => [
                ...$welcome,
                'stat-penghuni-asrama',
                'stat-santri-di-asrama',
                'stat-boarding-izin',
                'stat-boarding-sakit',
                'stat-izin-pending',
                'stat-kehadiran-malam',
                'stat-sesi-belum-diisi',
                'chart-kehadiran-sesi-hari-ini',
                'chart-tren-kehadiran-asrama',
                'chart-absensi-per-kamar',
                'table-sesi-belum-diisi',
                'table-kegiatan-hari-ini',
                'table-izin-pending',
                'table-izin-aktif',
                'table-izin-overdue',
                'table-penghuni-terbaru',
                'table-pindah-kamar',
                'table-pelanggaran-asrama',
                'table-poin-tertinggi',
                'table-penghargaan-terbaru',
                'table-kunjungan-terbaru',
                'table-pengumuman-asrama',
                'quick-action-asrama',
            ],
        ],
        'tenaga_pengasuhan_keasramaan_musrifah' => [
            'label'   => 'Musrifah',
            'widgets' => [
                ...$welcome,
                'stat-penghuni-asrama',
                'stat-santri-di-asrama',
                'stat-boarding-izin',
                'stat-boarding-sakit',
                'stat-izin-pending',
                'stat-kehadiran-malam',
                'stat-sesi-belum-diisi',
                'chart-kehadiran-sesi-hari-ini',
                'chart-tren-kehadiran-asrama',
                'chart-absensi-per-kamar',
                'table-sesi-belum-diisi',
                'table-kegiatan-hari-ini',
                'table-izin-pending',
                'table-izin-aktif',
                'table-izin-overdue',
                'table-penghuni-terbaru',
                'table-pindah-kamar',
                'table-pelanggaran-asrama',
                'table-poin-tertinggi',
                'table-penghargaan-terbaru',
                'table-kunjungan-terbaru',
                'table-pengumuman-asrama',
                'quick-action-asrama',
            ],
        ],
        'tenaga_pengasuhan_keasramaan_musyrif' => [
            'label'   => 'Musyrif',
            'widgets' => [
                ...$welcome,
                'stat-penghuni-asrama',
                'stat-santri-di-asrama',
                'stat-boarding-izin',
                'stat-boarding-sakit',
                'stat-izin-pending',
                'stat-kehadiran-malam',
                'stat-sesi-belum-diisi',
                'chart-kehadiran-sesi-hari-ini',
                'chart-tren-kehadiran-asrama',
                'chart-absensi-per-kamar',
                'table-sesi-belum-diisi',
                'table-kegiatan-hari-ini',
                'table-izin-pending',
                'table-izin-aktif',
                'table-izin-overdue',
                'table-penghuni-terbaru',
                'table-pindah-kamar',
                'table-pelanggaran-asrama',
                'table-poin-tertinggi',
                'table-penghargaan-terbaru',
                'table-kunjungan-terbaru',
                'table-pengumuman-asrama',
                'quick-action-asrama',
            ],
        ],
        'tenaga_pengasuhan_keasramaan_musyrifah' => [
            'label'   => 'Musyrifah',
            'widgets' => [
                ...$welcome,
                'stat-penghuni-asrama',
                'stat-santri-di-asrama',
                'stat-boarding-izin',
                'stat-boarding-sakit',
                'stat-izin-pending',
                'stat-kehadiran-malam',
                'stat-sesi-belum-diisi',
                'chart-kehadiran-sesi-hari-ini',
                'chart-tren-kehadiran-asrama',
                'chart-absensi-per-kamar',
                'table-sesi-belum-diisi',
                'table-kegiatan-hari-ini',
                'table-izin-pending',
                'table-izin-aktif',
                'table-izin-overdue',
                'table-penghuni-terbaru',
                'table-pindah-kamar',
                'table-pelanggaran-asrama',
                'table-poin-tertinggi',
                'table-penghargaan-terbaru',
                'table-kunjungan-terbaru',
                'table-pengumuman-asrama',
                'quick-action-asrama',
            ],
        ],

        // ── TU Asrama ────────────────────────────────────────────
        'tenaga_pengasuhan_keasramaan_tata_usaha_asrama' => [
            'label'   => 'Tata Usaha Asrama',
            'widgets' => [
                ...$welcome,
                ...$kpiPenghuni,
                'stat-musrif-aktif',
                ...$blokKamar,
                'chart-tren-kehadiran-asrama',
                'table-rekap-absensi-bulanan',
                'table-kegiatan-hari-ini',
                'table-izin-pending',
                'table-izin-aktif',
                ...$blokOperasional,
                'quick-action-asrama',
            ],
        ],
        'tenaga_pengasuhan_keasramaan_tu_asrama' => [
            'label'   => 'TU Asrama',
            'widgets' => [
                ...$welcome,
                ...$kpiPenghuni,
                'stat-musrif-aktif',
                ...$blokKamar,
                'chart-tren-kehadiran-asrama',
                'table-rekap-absensi-bulanan',
                'table-kegiatan-hari-ini',
                'table-izin-pending',
                'table-izin-aktif',
                ...$blokOperasional,
                'quick-action-asrama',
            ],
        ],
        'tenaga_pengasuhan_keasramaan_staf_tu_asrama' => [
            'label'   => 'Staf TU Asrama',
            'widgets' => [
                ...$welcome,
                ...$kpiPenghuni,
                ...$blokKamar,
                'table-rekap-absensi-bulanan',
                'table-izin-pending',
                'table-kunjungan-terbaru',
                'table-inventaris-rusak',
                'table-pengumuman-asrama',
                'quick-action-asrama',
            ],
        ],

        // ── Staf Perizinan ───────────────────────────────────────
        'tenaga_pengasuhan_keasramaan_staf_perizinan' => [
            'label'   => 'Staf Perizinan',
            'widgets' => [
                ...$welcome,
                'stat-izin-pending',
                'stat-izin-overdue',
                'stat-santri-di-asrama',
                'stat-boarding-izin',
                'stat-kunjungan-hari-ini',
                ...$blokPerizinan,
                'table-kunjungan-terbaru',
                'table-pengumuman-asrama',
                'quick-action-perizinan',
            ],
        ],
        'tenaga_pengasuhan_keasramaan_perizinan' => [
            'label'   => 'Perizinan',
            'widgets' => [
                ...$welcome,
                'stat-izin-pending',
                'stat-izin-overdue',
                'stat-santri-di-asrama',
                'stat-boarding-izin',
                'stat-kunjungan-hari-ini',
                ...$blokPerizinan,
                'table-kunjungan-terbaru',
                'table-pengumuman-asrama',
                'quick-action-perizinan',
            ],
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // TUGAS TAMBAHAN → WIDGET
    // ══════════════════════════════════════════════════════════════
    'tugas_tambahan' => [

        'Wali Kamar' => [
            'label'   => 'Wali Kamar',
            'widgets' => [
                'stat-penghuni-asrama',
                'stat-boarding-izin',
                'stat-boarding-sakit',
                'stat-kehadiran-malam',
                'chart-absensi-per-kamar',
                'chart-tren-kehadiran-asrama',
                'table-sesi-belum-diisi',
                'table-penghuni-terbaru',
                'table-pelanggaran-asrama',
                'table-penghargaan-terbaru',
                'table-poin-tertinggi',
                'table-kunjungan-terbaru',
                'table-pengumuman-asrama',
                'quick-action-asrama',
            ],
        ],

        'Pembina Kegiatan Asrama' => [
            'label'   => 'Pembina Kegiatan Asrama',
            'widgets' => [
                'table-kegiatan-hari-ini',
                'chart-partisipasi-kegiatan',
                'stat-sesi-belum-diisi',
                'table-sesi-belum-diisi',
                'chart-absensi-per-kamar',
                'quick-action-piket',
            ],
        ],

        'Petugas Piket Asrama' => [
            'label'   => 'Petugas Piket Asrama',
            'widgets' => [
                'stat-kehadiran-malam',
                'stat-sesi-belum-diisi',
                'chart-kehadiran-sesi-hari-ini',
                'table-sesi-belum-diisi',
                'stat-kunjungan-hari-ini',
                'table-kunjungan-terbaru',
                'table-izin-aktif',
                'quick-action-piket',
            ],
        ],
    ],

    // Fallback jabatan tidak dikenal
    'default' => [
        'label'   => 'Asrama',
        'widgets' => [
            ...$welcome,
            ...$kpiPenghuni,
            'table-izin-pending',
            'table-penghuni-terbaru',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION
    // ══════════════════════════════════════════════════════════════
    'sections' => [
        'ringkasan'    => ['title' => 'Ringkasan Asrama',        'icon' => 'ri-home-smile-2-line'],
        'kamar'        => ['title' => 'Kamar & Hunian',          'icon' => 'ri-door-open-line'],
        'absensi'      => ['title' => 'Absensi & Kegiatan',      'icon' => 'ri-calendar-check-line'],
        'perizinan'    => ['title' => 'Perizinan',               'icon' => 'ri-walk-line'],
        'tata-tertib'  => ['title' => 'Tata Tertib & Prestasi',  'icon' => 'ri-shield-star-line'],
        'operasional'  => ['title' => 'Operasional & Komunikasi', 'icon' => 'ri-briefcase-4-line'],
        'lainnya'      => ['title' => 'Tindak Lanjut',           'icon' => 'ri-checkbox-multiple-line'],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION MAP
    // ══════════════════════════════════════════════════════════════
    'section_map' => [
        // Ringkasan
        'welcome-banner'               => 'ringkasan',
        'stat-penghuni-asrama'         => 'ringkasan',
        'stat-santri-di-asrama'        => 'ringkasan',
        'stat-boarding-izin'           => 'ringkasan',
        'stat-boarding-sakit'          => 'ringkasan',
        'stat-boarding-kegiatan'       => 'ringkasan',
        'stat-musrif-aktif'            => 'ringkasan',
        'stat-izin-pending'            => 'ringkasan',
        'stat-izin-overdue'            => 'ringkasan',

        // Kamar & hunian
        'stat-occupancy-asrama'        => 'kamar',
        'stat-kamar-tersedia'          => 'kamar',
        'stat-penghuni-masuk-bulan'    => 'kamar',
        'stat-penghuni-keluar-bulan'   => 'kamar',
        'chart-hunian-asrama'          => 'kamar',
        'table-kamar-tersedia'         => 'kamar',
        'table-pindah-kamar'           => 'kamar',
        'table-penghuni-terbaru'       => 'kamar',

        // Absensi & kegiatan
        'stat-kehadiran-malam'         => 'absensi',
        'stat-sesi-belum-diisi'        => 'absensi',
        'chart-kehadiran-sesi-hari-ini' => 'absensi',
        'chart-tren-kehadiran-asrama'  => 'absensi',
        'chart-absensi-per-kamar'      => 'absensi',
        'chart-partisipasi-kegiatan'   => 'absensi',
        'table-sesi-belum-diisi'       => 'absensi',
        'table-rekap-absensi-bulanan'  => 'absensi',
        'table-kegiatan-hari-ini'      => 'absensi',

        // Perizinan
        'chart-izin-jenis'             => 'perizinan',
        'chart-izin-bulanan'           => 'perizinan',
        'table-izin-pending'           => 'perizinan',
        'table-izin-aktif'             => 'perizinan',
        'table-izin-overdue'           => 'perizinan',

        // Tata tertib
        'stat-pelanggaran-bulan'       => 'tata-tertib',
        'stat-penghargaan-bulan'       => 'tata-tertib',
        'chart-pelanggaran-kategori'   => 'tata-tertib',
        'table-pelanggaran-asrama'     => 'tata-tertib',
        'table-poin-tertinggi'         => 'tata-tertib',
        'table-penghargaan-terbaru'    => 'tata-tertib',

        // Operasional
        'stat-kunjungan-hari-ini'      => 'operasional',
        'table-kunjungan-terbaru'      => 'operasional',
        'stat-inventaris-rusak'        => 'operasional',
        'table-inventaris-rusak'       => 'operasional',
        'table-pengumuman-asrama'      => 'operasional',

        // Tindak lanjut
        'quick-action-asrama'          => 'lainnya',
        'quick-action-perizinan'       => 'lainnya',
        'quick-action-piket'           => 'lainnya',
    ],
];
