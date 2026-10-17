<?php

/**
 * Dashboard config — Role: Humas Personalia (SDM)
 *
 * Jabatan: Kepala Humas & Personalia, Kepala Humas, Staf Humas,
 * Kepala Personalia, Staf Personalia.
 * Tugas tambahan: Petugas Rekrutmen, Petugas Arsip SDM, Petugas Layanan Aduan.
 *
 * Cakupan GLOBAL (seluruh unit).
 * Catatan: modul "Layanan Aduan Wali Santri" belum punya tabel di skema,
 * sehingga Petugas Layanan Aduan belum punya widget (tidak dibuat dummy).
 */

$welcome = ['welcome-banner'];

// Data GTK & SK
$blokGtk = [
    'stat-gtk-aktif',
    'stat-gtk-baru-bulan',
    'stat-gtk-pending',
    'stat-sk-akan-habis',
    'stat-pensiun-dekat',
    'chart-komposisi-gtk',
    'table-gtk-baru',
    'table-sk-terbaru',
    'table-usulan-jabatan',
    'table-kelengkapan-arsip',
    'table-pensiun',
];

// Presensi & cuti
$blokPresensi = [
    'stat-kehadiran-gtk-hari-ini',
    'stat-terlambat-hari-ini',
    'stat-cuti-pending',
    'stat-cuti-aktif-hari-ini',
    'chart-kehadiran-gtk-7-hari',
    'chart-cuti-per-status',
    'table-presensi-gtk',
    'table-cuti-pending',
    'table-cuti-terbaru',
    'table-saldo-cuti',
];

// Kinerja & karier
$blokKinerja = [
    'stat-kinerja-aktif',
    'stat-penilaian-pending',
    'stat-transfer-pending',
    'stat-promosi-bulan',
    'chart-distribusi-kinerja',
    'table-kinerja-terbaru',
    'table-reward-punishment',
    'table-transfer-gtk',
    'table-promosi-demosi',
];

// Pelatihan
$blokPelatihan = [
    'stat-pelatihan-bulan',
    'chart-pelatihan-tahunan',
    'table-pelatihan-gtk',
];

// Rekrutmen
$blokRekrutmen = [
    'stat-rekrutmen-aktif',
    'table-rekrutmen',
    'table-lamaran-terbaru',
];

// Humas
$blokHumas = [
    'table-agenda-mendatang',
];

return [

    'jabatan' => [

        // ── Kepala Humas & Personalia ────────────────────────────
        'humas_personalia_kepala_humas_personalia' => [
            'label'   => 'Kepala Humas & Personalia',
            'widgets' => [
                ...$welcome,
                ...$blokGtk,
                ...$blokPresensi,
                ...$blokKinerja,
                ...$blokPelatihan,
                ...$blokRekrutmen,
                ...$blokHumas,
                'quick-action-personalia',
            ],
        ],

        // ── Kepala / Staf Personalia ─────────────────────────────
        'humas_personalia_kepala_personalia' => [
            'label'   => 'Kepala Personalia',
            'widgets' => [
                ...$welcome,
                ...$blokGtk,
                ...$blokPresensi,
                ...$blokKinerja,
                ...$blokPelatihan,
                ...$blokRekrutmen,
                ...$blokHumas,
                'quick-action-personalia',
            ],
        ],
        'humas_personalia_staf_personalia' => [
            'label'   => 'Staf Personalia',
            'widgets' => [
                ...$welcome,
                'stat-gtk-aktif',
                'stat-gtk-baru-bulan',
                'stat-gtk-pending',
                'stat-kehadiran-gtk-hari-ini',
                'stat-cuti-pending',
                'stat-cuti-aktif-hari-ini',
                'table-presensi-gtk',
                'table-cuti-pending',
                'table-cuti-terbaru',
                'table-saldo-cuti',
                'table-gtk-baru',
                'table-kinerja-terbaru',
                'table-agenda-mendatang',
                'quick-action-personalia',
            ],
        ],

        // ── Kepala / Staf Humas ──────────────────────────────────
        'humas_personalia_kepala_humas' => [
            'label'   => 'Kepala Humas',
            'widgets' => [
                ...$welcome,
                'stat-gtk-aktif',
                'stat-gtk-baru-bulan',
                'stat-pelatihan-bulan',
                'stat-rekrutmen-aktif',
                'chart-komposisi-gtk',
                'chart-pelatihan-tahunan',
                'table-pelatihan-gtk',
                'table-rekrutmen',
                'table-lamaran-terbaru',
                'table-agenda-mendatang',
                'quick-action-personalia',
            ],
        ],
        'humas_personalia_staf_humas' => [
            'label'   => 'Staf Humas',
            'widgets' => [
                ...$welcome,
                'stat-gtk-aktif',
                'stat-pelatihan-bulan',
                'stat-rekrutmen-aktif',
                'table-pelatihan-gtk',
                'table-rekrutmen',
                'table-agenda-mendatang',
            ],
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // TUGAS TAMBAHAN → WIDGET
    // ══════════════════════════════════════════════════════════════
    'tugas_tambahan' => [

        'Petugas Rekrutmen' => [
            'label'   => 'Petugas Rekrutmen',
            'widgets' => [
                'stat-rekrutmen-aktif',
                'table-rekrutmen',
                'table-lamaran-terbaru',
            ],
        ],

        'Petugas Arsip SDM' => [
            'label'   => 'Petugas Arsip SDM',
            'widgets' => [
                'stat-gtk-aktif',
                'stat-sk-akan-habis',
                'table-gtk-baru',
                'table-sk-terbaru',
                'table-kelengkapan-arsip',
            ],
        ],

        'Petugas Layanan Aduan' => [
            'label'   => 'Petugas Layanan Aduan',
            'widgets' => [
                'stat-aduan-baru',
                'stat-aduan-proses',
                'stat-aduan-selesai-bulan',
                'table-aduan-terbaru',
                'quick-action-personalia',
            ],
        ],
    ],

    // Fallback jabatan tidak dikenal
    'default' => [
        'label'   => 'Humas & Personalia',
        'widgets' => [
            ...$welcome,
            'stat-gtk-aktif',
            'stat-kehadiran-gtk-hari-ini',
            'table-presensi-gtk',
            'table-cuti-terbaru',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION
    // ══════════════════════════════════════════════════════════════
    'sections' => [
        'ringkasan'  => ['title' => 'Ringkasan SDM',        'icon' => 'ri-team-line'],
        'gtk'        => ['title' => 'Data GTK & SK',        'icon' => 'ri-id-card-line'],
        'presensi'   => ['title' => 'Presensi & Cuti',      'icon' => 'ri-fingerprint-line'],
        'kinerja'    => ['title' => 'Kinerja & Karier',     'icon' => 'ri-line-chart-line'],
        'rekrutmen'  => ['title' => 'Rekrutmen & Pelatihan', 'icon' => 'ri-user-add-line'],
        'humas'      => ['title' => 'Humas & Agenda',       'icon' => 'ri-megaphone-line'],
        'lainnya'    => ['title' => 'Tindak Lanjut',        'icon' => 'ri-checkbox-multiple-line'],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION MAP
    // ══════════════════════════════════════════════════════════════
    'section_map' => [
        'welcome-banner'            => 'ringkasan',
        'stat-gtk-aktif'            => 'ringkasan',
        'stat-gtk-baru-bulan'       => 'ringkasan',
        'stat-gtk-pending'          => 'ringkasan',
        'stat-sk-akan-habis'        => 'ringkasan',
        'stat-pensiun-dekat'        => 'ringkasan',
        'stat-kehadiran-gtk-hari-ini' => 'ringkasan',
        'stat-terlambat-hari-ini'   => 'ringkasan',
        'stat-cuti-pending'         => 'ringkasan',
        'stat-cuti-aktif-hari-ini'  => 'ringkasan',

        'chart-komposisi-gtk'       => 'gtk',
        'table-gtk-baru'            => 'gtk',
        'table-sk-terbaru'          => 'gtk',
        'table-usulan-jabatan'      => 'gtk',
        'table-kelengkapan-arsip'   => 'gtk',
        'table-pensiun'             => 'gtk',

        'chart-kehadiran-gtk-7-hari' => 'presensi',
        'chart-cuti-per-status'     => 'presensi',
        'table-presensi-gtk'        => 'presensi',
        'table-cuti-pending'        => 'presensi',
        'table-cuti-terbaru'        => 'presensi',
        'table-saldo-cuti'          => 'presensi',

        'stat-kinerja-aktif'        => 'kinerja',
        'stat-penilaian-pending'    => 'kinerja',
        'stat-transfer-pending'     => 'kinerja',
        'stat-promosi-bulan'        => 'kinerja',
        'chart-distribusi-kinerja'  => 'kinerja',
        'table-kinerja-terbaru'     => 'kinerja',
        'table-reward-punishment'   => 'kinerja',
        'table-transfer-gtk'        => 'kinerja',
        'table-promosi-demosi'      => 'kinerja',

        'stat-pelatihan-bulan'      => 'rekrutmen',
        'stat-rekrutmen-aktif'      => 'rekrutmen',
        'chart-pelatihan-tahunan'   => 'rekrutmen',
        'table-pelatihan-gtk'       => 'rekrutmen',
        'table-rekrutmen'           => 'rekrutmen',
        'table-lamaran-terbaru'     => 'rekrutmen',

        'table-agenda-mendatang'    => 'humas',

        'quick-action-personalia'   => 'lainnya',
    ],
];
