<?php

/**
 * Dashboard config — Role: Keuangan
 *
 * Jabatan: Kepala Departemen Keuangan, Kepala Keuangan, Bendahara Penerimaan,
 * Kasir SPP, Bendahara Pengeluaran, Staf Payroll/Gaji, Akuntan, Staf Pembukuan/Akuntansi/Keuangan.
 * Tugas tambahan: Petugas Kasir SPP, Petugas Payroll, Petugas Pembukuan, Petugas Verifikasi Kas.
 *
 * Data SPP bersumber dari tabel `spp_bills` (tagihan & pembayaran per periode).
 */

$welcome = ['welcome-banner'];

$kpiKeuangan = [
    'stat-payroll-bulan-ini',
    'stat-invoice-outstanding',
    'stat-budget-realisasi',
];

$tabelKeuangan = [
    'table-invoice-terbaru',
    'table-payroll-terbaru',
    'table-budget-divisi',
];

return [

    'jabatan' => [

        // ── Kepala Departemen Keuangan / Kepala Keuangan ─────────
        'tenaga_keuangan_akuntansi_kepala_departemen__02f9b' => [
            'label'   => 'Kepala Departemen Keuangan',
            'widgets' => [
                ...$welcome,
                ...$kpiKeuangan,
                'stat-pengajuan-pengadaan',
                'stat-kesejahteraan-klaim',
                'stat-total-vendor',
                'chart-keuangan-bulanan',
                'chart-budget-divisi',
                'chart-invoice-status',
                ...$tabelKeuangan,
                'table-pengajuan-pengadaan',
                'table-kesejahteraan-terbaru',
                'quick-action-keuangan',
            ],
        ],
        'tenaga_keuangan_akuntansi_kepala_keuangan' => [
            'label'   => 'Kepala Keuangan',
            'widgets' => [
                ...$welcome,
                ...$kpiKeuangan,
                'stat-pengajuan-pengadaan',
                'stat-kesejahteraan-klaim',
                'stat-total-vendor',
                'chart-keuangan-bulanan',
                'chart-budget-divisi',
                'chart-invoice-status',
                ...$tabelKeuangan,
                'table-pengajuan-pengadaan',
                'table-kesejahteraan-terbaru',
                'quick-action-keuangan',
            ],
        ],

        // ── Bendahara Penerimaan / Kasir SPP ─────────────────────
        'tenaga_keuangan_akuntansi_bendahara_penerimaan' => [
            'label'   => 'Bendahara Penerimaan',
            'widgets' => [
                ...$welcome,
                'stat-invoice-outstanding',
                'stat-pengajuan-pengadaan',
                'stat-kesejahteraan-klaim',
                'chart-invoice-status',
                'table-invoice-terbaru',
                'table-pengajuan-pengadaan',
                'table-kesejahteraan-terbaru',
                'quick-action-keuangan',
            ],
        ],
        'tenaga_keuangan_akuntansi_kasir_spp' => [
            'label'   => 'Kasir SPP',
            'widgets' => [
                ...$welcome,
                'stat-spp-tunggakan',
                'stat-spp-tagihan-bulan-ini',
                'stat-spp-terbayar-bulan-ini',
                'table-spp-terbaru',
                'quick-action-keuangan',
            ],
        ],

        // ── Bendahara Pengeluaran ────────────────────────────────
        'tenaga_keuangan_akuntansi_bendahara_pengeluaran' => [
            'label'   => 'Bendahara Pengeluaran',
            'widgets' => [
                ...$welcome,
                'stat-pengajuan-pengadaan',
                'stat-invoice-outstanding',
                'chart-keuangan-bulanan',
                'chart-invoice-status',
                'table-pengajuan-pengadaan',
                'table-invoice-terbaru',
                'quick-action-keuangan',
            ],
        ],

        // ── Staf Payroll / Staf Gaji ─────────────────────────────
        'tenaga_keuangan_akuntansi_staf_payroll' => [
            'label'   => 'Staf Payroll',
            'widgets' => [
                ...$welcome,
                'stat-payroll-bulan-ini',
                'stat-kesejahteraan-klaim',
                'chart-keuangan-bulanan',
                'table-payroll-terbaru',
                'table-kesejahteraan-terbaru',
                'quick-action-keuangan',
            ],
        ],
        'tenaga_keuangan_akuntansi_staf_gaji' => [
            'label'   => 'Staf Gaji',
            'widgets' => [
                ...$welcome,
                'stat-payroll-bulan-ini',
                'stat-kesejahteraan-klaim',
                'chart-keuangan-bulanan',
                'table-payroll-terbaru',
                'table-kesejahteraan-terbaru',
                'quick-action-keuangan',
            ],
        ],

        // ── Akuntan / Staf Pembukuan / Akuntansi ─────────────────
        'tenaga_keuangan_akuntansi_akuntan' => [
            'label'   => 'Akuntan',
            'widgets' => [
                ...$welcome,
                ...$kpiKeuangan,
                'chart-budget-divisi',
                'chart-invoice-status',
                ...$tabelKeuangan,
                'quick-action-keuangan',
            ],
        ],
        'tenaga_keuangan_akuntansi_staf_pembukuan' => [
            'label'   => 'Staf Pembukuan',
            'widgets' => [
                ...$welcome,
                ...$kpiKeuangan,
                'chart-budget-divisi',
                'chart-invoice-status',
                ...$tabelKeuangan,
                'quick-action-keuangan',
            ],
        ],
        'tenaga_keuangan_akuntansi_staf_akuntansi' => [
            'label'   => 'Staf Akuntansi',
            'widgets' => [
                ...$welcome,
                ...$kpiKeuangan,
                'chart-budget-divisi',
                'chart-invoice-status',
                ...$tabelKeuangan,
                'quick-action-keuangan',
            ],
        ],

        // ── Staf Keuangan (umum) ─────────────────────────────────
        'tenaga_keuangan_akuntansi_staf_keuangan' => [
            'label'   => 'Staf Keuangan',
            'widgets' => [
                ...$welcome,
                ...$kpiKeuangan,
                'stat-pengajuan-pengadaan',
                'chart-keuangan-bulanan',
                'chart-invoice-status',
                ...$tabelKeuangan,
                'table-pengajuan-pengadaan',
                'quick-action-keuangan',
            ],
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // TUGAS TAMBAHAN → WIDGET
    // ══════════════════════════════════════════════════════════════
    'tugas_tambahan' => [

        'Petugas Kasir SPP' => [
            'label'   => 'Petugas Kasir SPP',
            'widgets' => [
                'stat-spp-tunggakan',
                'stat-spp-tagihan-bulan-ini',
                'stat-spp-terbayar-bulan-ini',
                'table-spp-terbaru',
            ],
        ],

        'Petugas Payroll' => [
            'label'   => 'Petugas Payroll',
            'widgets' => [
                'stat-payroll-bulan-ini',
                'table-payroll-terbaru',
                'chart-keuangan-bulanan',
            ],
        ],

        'Petugas Pembukuan' => [
            'label'   => 'Petugas Pembukuan',
            'widgets' => [
                'stat-budget-realisasi',
                'chart-budget-divisi',
                'table-budget-divisi',
                'table-invoice-terbaru',
            ],
        ],

        'Petugas Verifikasi Kas' => [
            'label'   => 'Petugas Verifikasi Kas',
            'widgets' => [
                'stat-invoice-outstanding',
                'table-invoice-terbaru',
                'stat-kesejahteraan-klaim',
                'table-kesejahteraan-terbaru',
            ],
        ],
    ],

    // Fallback jabatan tidak dikenal
    'default' => [
        'label'   => 'Keuangan',
        'widgets' => [
            ...$welcome,
            ...$kpiKeuangan,
            'table-invoice-terbaru',
            'table-payroll-terbaru',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION
    // ══════════════════════════════════════════════════════════════
    'sections' => [
        'ringkasan' => ['title' => 'Ringkasan Keuangan',  'icon' => 'ri-dashboard-3-line'],
        'keuangan'  => ['title' => 'Keuangan & Pembukuan', 'icon' => 'ri-wallet-3-line'],
        'lainnya'   => ['title' => 'Tindak Lanjut',       'icon' => 'ri-checkbox-multiple-line'],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION MAP
    // ══════════════════════════════════════════════════════════════
    'section_map' => [
        'welcome-banner'               => 'ringkasan',
        'stat-payroll-bulan-ini'       => 'ringkasan',
        'stat-invoice-outstanding'     => 'ringkasan',
        'stat-budget-realisasi'        => 'ringkasan',
        'stat-pengajuan-pengadaan'     => 'ringkasan',
        'stat-kesejahteraan-klaim'     => 'ringkasan',
        'stat-total-vendor'            => 'ringkasan',

        'chart-keuangan-bulanan'       => 'keuangan',
        'chart-budget-divisi'          => 'keuangan',
        'chart-invoice-status'         => 'keuangan',
        'table-invoice-terbaru'        => 'keuangan',
        'table-payroll-terbaru'        => 'keuangan',
        'table-budget-divisi'          => 'keuangan',
        'table-pengajuan-pengadaan'    => 'keuangan',
        'table-kesejahteraan-terbaru'  => 'keuangan',

        'quick-action-keuangan'        => 'lainnya',
    ],
];
