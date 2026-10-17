<?php

/**
 * Dashboard config — Role: Unit Rumah Tangga (URT)
 *
 * Fungsi: ticketing perbaikan, master inventaris aset, logistik & gudang,
 * armada/transport (tabel `vehicles`), kebersihan lingkungan.
 *
 * Jabatan: Kepala/Koordinator/TU URT, Teknisi Maintenance, Petugas Kebersihan,
 * Driver/Pengemudi. Tugas: Petugas Gudang URT, Petugas Armada Kendaraan,
 * Petugas Ticketing Work Order.
 *
 * Catatan: modul Armada (kendaraan/BBM/servis) belum punya tabel → tanpa widget.
 */

$welcome = ['welcome-banner'];

$blokTicketing = [
    'stat-wo-open',
    'stat-wo-hari-ini',
    'stat-wo-selesai-bulan',
    'stat-wo-overdue',
    'chart-wo-7-hari',
    'chart-wo-per-type',
    'table-wo-terbaru',
    'table-wo-progress',
];

$blokAset = [
    'stat-total-aset',
    'stat-nilai-aset',
    'stat-aset-dipinjam',
    'stat-aset-rusak',
    'chart-aset-per-kategori',
    'chart-aset-per-kondisi',
    'table-aset-terbaru',
    'table-damage-reports',
];

$blokGudang = [
    'stat-sparepart-item',
    'stat-sparepart-menipis',
    'chart-gerakan-stok-7-hari',
    'table-stok-menipis',
    'table-gerakan-stok',
];

$blokKebersihan = [
    'stat-inspeksi-bulan',
    'stat-inspeksi-lulus',
    'chart-skor-kebersihan',
    'table-inspeksi-terbaru',
];

$blokPemeliharaan = [
    'stat-maintenance-jatuh-tempo',
    'table-maintenance-terbaru',
    'table-maintenance-jadwal',
    'table-sla-tracking',
];

return [

    'jabatan' => [

        // ── Kepala URT ───────────────────────────────────────────
        'tenaga_rumah_tangga_sarpras_kepala_unit_ruma_79636' => [
            'label'   => 'Kepala Unit Rumah Tangga',
            'widgets' => [
                ...$welcome, ...$blokTicketing, ...$blokAset, ...$blokGudang, ...$blokKebersihan, ...$blokPemeliharaan, 'quick-action-urt',
            ],
        ],

        // ── Koordinator Sarpras ──────────────────────────────────
        'tenaga_rumah_tangga_sarpras_koordinator_sarpras' => [
            'label'   => 'Koordinator Sarpras',
            'widgets' => [
                ...$welcome, ...$blokTicketing, ...$blokAset, ...$blokGudang, ...$blokPemeliharaan, 'quick-action-urt',
            ],
        ],
        'tenaga_rumah_tangga_sarpras_koordinator_sara_16855' => [
            'label'   => 'Koordinator Sarana Prasarana',
            'widgets' => [
                ...$welcome, ...$blokTicketing, ...$blokAset, ...$blokGudang, ...$blokPemeliharaan, 'quick-action-urt',
            ],
        ],

        // ── TU / Staf URT ────────────────────────────────────────
        'tenaga_rumah_tangga_sarpras_tata_usaha_urt' => [
            'label'   => 'Tata Usaha URT',
            'widgets' => [
                ...$welcome, ...$blokTicketing, ...$blokAset, ...$blokGudang, ...$blokKebersihan, ...$blokPemeliharaan, 'quick-action-urt',
            ],
        ],
        'tenaga_rumah_tangga_sarpras_tu_urt' => [
            'label'   => 'TU URT',
            'widgets' => [
                ...$welcome, ...$blokTicketing, ...$blokAset, ...$blokGudang, ...$blokKebersihan, ...$blokPemeliharaan, 'quick-action-urt',
            ],
        ],
        'tenaga_rumah_tangga_sarpras_staf_tu_urt' => [
            'label'   => 'Staf TU URT',
            'widgets' => [
                ...$welcome, ...$blokTicketing, ...$blokAset, ...$blokGudang, ...$blokPemeliharaan,
            ],
        ],
        'tenaga_rumah_tangga_sarpras_staf_urt' => [
            'label'   => 'Staf URT',
            'widgets' => [
                ...$welcome, ...$blokTicketing, ...$blokAset, ...$blokGudang, ...$blokPemeliharaan,
            ],
        ],

        // ── Teknisi Maintenance ──────────────────────────────────
        'tenaga_rumah_tangga_sarpras_teknisi_maintenance' => [
            'label'   => 'Teknisi Maintenance',
            'widgets' => [
                ...$welcome,
                'stat-wo-open', 'stat-wo-hari-ini', 'stat-wo-overdue',
                'chart-wo-7-hari',
                'table-wo-terbaru', 'table-wo-progress',
                'table-stok-menipis', 'table-gerakan-stok',
                'table-maintenance-terbaru', 'table-maintenance-jadwal',
                'quick-action-urt',
            ],
        ],
        'tenaga_rumah_tangga_sarpras_teknisi' => [
            'label'   => 'Teknisi',
            'widgets' => [
                ...$welcome,
                'stat-wo-open', 'stat-wo-hari-ini', 'stat-wo-overdue',
                'chart-wo-7-hari',
                'table-wo-terbaru', 'table-wo-progress',
                'table-stok-menipis', 'table-gerakan-stok',
                'table-maintenance-terbaru', 'table-maintenance-jadwal',
                'quick-action-urt',
            ],
        ],

        // ── Petugas Kebersihan / Janitor ─────────────────────────
        'tenaga_rumah_tangga_sarpras_petugas_kebersihan' => [
            'label'   => 'Petugas Kebersihan',
            'widgets' => [
                ...$welcome, ...$blokKebersihan,
                'stat-wo-open', 'table-wo-terbaru',
                'quick-action-urt',
            ],
        ],
        'tenaga_rumah_tangga_sarpras_janitor' => [
            'label'   => 'Janitor',
            'widgets' => [
                ...$welcome, ...$blokKebersihan,
                'stat-wo-open', 'table-wo-terbaru',
                'quick-action-urt',
            ],
        ],

        // ── Driver / Pengemudi (armada/transport) ────────────────
        'tenaga_rumah_tangga_sarpras_driver' => [
            'label'   => 'Driver',
            'widgets' => [
                ...$welcome,
        ['stat-armada-total', 'stat-armada-tersedia', 'stat-armada-servis', 'table-armada'],
                'stat-wo-open',
                'table-wo-terbaru',
            ],
        ],
        'tenaga_rumah_tangga_sarpras_pengemudi' => [
            'label'   => 'Pengemudi',
            'widgets' => [
                ...$welcome,
        ['stat-armada-total', 'stat-armada-tersedia', 'stat-armada-servis', 'table-armada'],
                'stat-wo-open',
                'table-wo-terbaru',
            ],
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // TUGAS TAMBAHAN → WIDGET
    // ══════════════════════════════════════════════════════════════
    'tugas_tambahan' => [

        'Petugas Ticketing Work Order' => [
            'label'   => 'Petugas Ticketing Work Order',
            'widgets' => [
                'stat-wo-open',
                'stat-wo-hari-ini',
                'stat-wo-selesai-bulan',
                'chart-wo-7-hari',
                'table-wo-terbaru',
                'table-wo-progress',
            ],
        ],

        'Petugas Gudang URT' => [
            'label'   => 'Petugas Gudang URT',
            'widgets' => [
                'stat-sparepart-item',
                'stat-sparepart-menipis',
                'chart-gerakan-stok-7-hari',
                'table-stok-menipis',
                'table-gerakan-stok',
            ],
        ],

        'Petugas Armada Kendaraan' => [
            'label'   => 'Petugas Armada Kendaraan',
            'widgets' => [
                'stat-armada-total',
                'stat-armada-tersedia',
                'stat-armada-servis',
                'table-armada',
                'quick-action-urt',
            ],
        ],
    ],

    // Fallback jabatan tidak dikenal
    'default' => [
        'label'   => 'Unit Rumah Tangga',
        'widgets' => [
            ...$welcome,
            'stat-wo-open',
            'stat-total-aset',
            'table-wo-terbaru',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION
    // ══════════════════════════════════════════════════════════════
    'sections' => [
        'ringkasan'    => ['title' => 'Ringkasan URT',            'icon' => 'ri-home-gear-line'],
        'ticketing'    => ['title' => 'Ticketing Perbaikan',      'icon' => 'ri-customer-service-2-line'],
        'aset'         => ['title' => 'Inventaris Aset',          'icon' => 'ri-archive-2-line'],
        'gudang'       => ['title' => 'Logistik & Gudang',        'icon' => 'ri-stack-line'],
        'kebersihan'   => ['title' => 'Kebersihan Lingkungan',    'icon' => 'ri-brush-line'],
        'pemeliharaan' => ['title' => 'Pemeliharaan & SLA',       'icon' => 'ri-tools-line'],
        'lainnya'      => ['title' => 'Tindak Lanjut',            'icon' => 'ri-checkbox-multiple-line'],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION MAP
    // ══════════════════════════════════════════════════════════════
    'section_map' => [
        'welcome-banner'            => 'ringkasan',
        'stat-wo-open'              => 'ringkasan',
        'stat-wo-hari-ini'          => 'ringkasan',
        'stat-wo-selesai-bulan'     => 'ringkasan',
        'stat-wo-overdue'           => 'ringkasan',
        'stat-total-aset'           => 'ringkasan',
        'stat-nilai-aset'           => 'ringkasan',
        'stat-aset-dipinjam'        => 'ringkasan',
        'stat-aset-rusak'           => 'ringkasan',
        'stat-sparepart-item'       => 'ringkasan',
        'stat-sparepart-menipis'    => 'ringkasan',
        'stat-inspeksi-bulan'       => 'ringkasan',
        'stat-inspeksi-lulus'       => 'ringkasan',
        'stat-maintenance-jatuh-tempo' => 'ringkasan',

        'chart-wo-7-hari'           => 'ticketing',
        'chart-wo-per-type'         => 'ticketing',
        'table-wo-terbaru'          => 'ticketing',
        'table-wo-progress'         => 'ticketing',

        'chart-aset-per-kategori'   => 'aset',
        'chart-aset-per-kondisi'    => 'aset',
        'table-aset-terbaru'        => 'aset',
        'table-damage-reports'      => 'aset',

        'chart-gerakan-stok-7-hari' => 'gudang',
        'table-stok-menipis'        => 'gudang',
        'table-gerakan-stok'        => 'gudang',

        'chart-skor-kebersihan'     => 'kebersihan',
        'table-inspeksi-terbaru'    => 'kebersihan',

        'table-maintenance-terbaru' => 'pemeliharaan',
        'table-maintenance-jadwal'  => 'pemeliharaan',
        'table-sla-tracking'        => 'pemeliharaan',

        'quick-action-urt'          => 'lainnya',
    ],
];
