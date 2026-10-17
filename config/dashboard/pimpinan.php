<?php

/**
 * Dashboard config — Role: Pimpinan
 *
 * Jabatan: Mudir/Pengasuh, Wadir 1 (Akademik), Wadir 2 (Non-Akademik).
 * Cakupan data GLOBAL (seluruh unit), bukan per sekolah.
 * Tugas tambahan: tidak ada (sesuai struktur).
 */

$welcome = ['welcome-banner'];

// Kartu eksekutif (ringkasan yayasan)
$execStats = [
    'stat-total-unit',
    'stat-total-santri',
    'stat-total-gtk',
];

// Antrean persetujuan
$approvalStats = [
    'stat-pengajuan-pengadaan',
    'stat-kesejahteraan-klaim',
    'stat-gtk-pending-verifikasi',
    'stat-cuti-aktif-hari-ini',
];

return [

    'jabatan' => [

        // ── Mudir / Pengasuh Pesantren ───────────────────────────
        'pimpinan_pesantren_mudir' => [
            'label'   => 'Mudir',
            'widgets' => [
                ...$welcome,
                ...$execStats,
                ...$approvalStats,
                'chart-santri-per-unit',
                'chart-keuangan-bulanan',
                'table-pengajuan-pengadaan',
                'table-kesejahteraan-terbaru',
                'table-cuti-terbaru',
                'table-agenda-mendatang',
                'quick-action-pimpinan',
            ],
        ],
        'pimpinan_pesantren_pengasuh_pesantren' => [
            'label'   => 'Pengasuh Pesantren',
            'widgets' => [
                ...$welcome,
                ...$execStats,
                ...$approvalStats,
                'chart-santri-per-unit',
                'chart-keuangan-bulanan',
                'table-pengajuan-pengadaan',
                'table-kesejahteraan-terbaru',
                'table-cuti-terbaru',
                'table-agenda-mendatang',
                'quick-action-pimpinan',
            ],
        ],

        // ── Wadir 1 — Akademik ───────────────────────────────────
        'pimpinan_pesantren_wadir_1' => [
            'label'   => 'Wadir 1 (Akademik)',
            'widgets' => [
                ...$welcome,
                'stat-total-santri',
                'stat-total-gtk',
                'stat-total-rombel',
                'stat-kehadiran-guru-hari-ini',
                'stat-kelas-kosong-hari-ini',
                'stat-supervisi-mendatang',
                'chart-tren-kehadiran-santri',
                'chart-santri-per-tingkat',
                'chart-komposisi-gtk',
                'table-kinerja-terbaru',
                'table-validasi-perangkat-ajar',
                'table-agenda-mendatang',
            ],
        ],

        // ── Wadir 2 — Non-Akademik ───────────────────────────────
        'pimpinan_pesantren_wadir_2' => [
            'label'   => 'Wadir 2 (Non-Akademik)',
            'widgets' => [
                ...$welcome,
                'stat-santri-di-uks',
                'stat-izin-aktif',
                'stat-total-ekskul',
                'stat-aset-rusak',
                'stat-cuti-aktif-hari-ini',
                'chart-komposisi-gtk',
                'table-izin-terbaru',
                'table-uks-terbaru',
                'table-jadwal-ekskul-minggu-ini',
                'table-agenda-mendatang',
                'quick-action-pimpinan',
            ],
        ],
    ],

    // Pimpinan tidak punya tugas tambahan pada struktur saat ini
    'tugas_tambahan' => [],

    // Fallback jabatan tidak dikenal
    'default' => [
        'label'   => 'Pimpinan',
        'widgets' => [
            ...$welcome,
            ...$execStats,
            'table-agenda-mendatang',
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION
    // ══════════════════════════════════════════════════════════════
    'sections' => [
        'ringkasan'   => ['title' => 'Ringkasan Eksekutif',   'icon' => 'ri-dashboard-3-line'],
        'akademik'    => ['title' => 'Akademik & KBM',         'icon' => 'ri-book-open-line'],
        'kesiswaan'   => ['title' => 'Kesiswaan & Kesehatan',  'icon' => 'ri-heart-pulse-line'],
        'kepegawaian' => ['title' => 'Kepegawaian',            'icon' => 'ri-team-line'],
        'keuangan'    => ['title' => 'Keuangan & Persetujuan', 'icon' => 'ri-wallet-3-line'],
        'agenda'      => ['title' => 'Agenda & Kegiatan',      'icon' => 'ri-calendar-event-line'],
        'lainnya'     => ['title' => 'Tindak Lanjut',          'icon' => 'ri-checkbox-multiple-line'],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION MAP
    // ══════════════════════════════════════════════════════════════
    'section_map' => [
        // Ringkasan eksekutif
        'welcome-banner'                   => 'ringkasan',
        'stat-total-unit'                  => 'ringkasan',
        'stat-total-santri'                => 'ringkasan',
        'stat-total-gtk'                   => 'ringkasan',
        'stat-total-rombel'                => 'ringkasan',
        'stat-santri-baru'                 => 'ringkasan',
        'stat-santri-masuk'                => 'ringkasan',
        'stat-santri-keluar'               => 'ringkasan',
        'stat-santri-kelas-saya'           => 'ringkasan',
        'stat-jumlah-siswa-diajar'         => 'ringkasan',
        'stat-absensi-santri-hari-ini'     => 'ringkasan',
        'stat-kehadiran-guru-hari-ini'     => 'ringkasan',
        'stat-kelas-kosong-hari-ini'       => 'ringkasan',
        'stat-hadir-hari-ini'              => 'ringkasan',

        // Akademik
        'chart-tren-kehadiran-santri'      => 'akademik',
        'chart-gender-santri'              => 'akademik',
        'chart-top-rombel'                 => 'akademik',
        'chart-statistik-santri-per-rombel' => 'akademik',
        'chart-santri-per-tingkat'         => 'akademik',
        'chart-santri-per-unit'            => 'akademik',
        'chart-absensi-kelas'              => 'akademik',
        'timeline-jadwal-mengajar-hari-ini' => 'akademik',
        'list-kelas-perlu-absen'           => 'akademik',
        'table-jadwal-pelajaran-aktif'     => 'akademik',
        'table-validasi-perangkat-ajar'    => 'akademik',
        'table-top-5-santri-berprestasi'   => 'akademik',
        'table-rekap-siswa-tingkat'        => 'akademik',
        'stat-tugas-belum-dinilai'         => 'akademik',
        'stat-nilai-belum-lengkap'         => 'akademik',
        'stat-rata-nilai-kelas'            => 'akademik',
        'stat-bank-soal-saya'              => 'akademik',
        'stat-bank-soal-rumpun'            => 'akademik',

        // Kesiswaan & kesehatan
        'stat-santri-di-uks'               => 'kesiswaan',
        'stat-izin-aktif'                  => 'kesiswaan',
        'table-izin-terbaru'               => 'kesiswaan',
        'table-uks-terbaru'                => 'kesiswaan',
        'table-pelanggaran-terbaru'        => 'kesiswaan',
        'table-pelanggaran-kelas'          => 'kesiswaan',
        'stat-total-ekskul'                => 'kesiswaan',
        'table-jadwal-ekskul-minggu-ini'   => 'kesiswaan',

        // Kepegawaian
        'stat-gtk-pending-verifikasi'      => 'kepegawaian',
        'stat-cuti-aktif-hari-ini'         => 'kepegawaian',
        'stat-supervisi-mendatang'         => 'kepegawaian',
        'stat-total-guru-rumpun'           => 'kepegawaian',
        'chart-komposisi-gtk'              => 'kepegawaian',
        'chart-tren-kehadiran-guru'        => 'kepegawaian',
        'chart-kehadiran-rumpun'           => 'kepegawaian',
        'table-gtk-kontrak-habis'          => 'kepegawaian',
        'table-jadwal-supervisi'           => 'kepegawaian',
        'table-cuti-terbaru'               => 'kepegawaian',
        'table-kinerja-terbaru'            => 'kepegawaian',

        // Keuangan & persetujuan
        'stat-payroll-bulan-ini'           => 'keuangan',
        'stat-pengajuan-pengadaan'         => 'keuangan',
        'stat-kesejahteraan-klaim'         => 'keuangan',
        'stat-total-alat-lab'              => 'keuangan',
        'stat-aset-rusak'                  => 'keuangan',
        'chart-keuangan-bulanan'           => 'keuangan',
        'table-pengajuan-pengadaan'        => 'keuangan',
        'table-kesejahteraan-terbaru'      => 'keuangan',

        // Agenda
        'stat-agenda-hari-ini'             => 'agenda',
        'table-agenda-mendatang'           => 'agenda',

        // Tindak lanjut
        'quick-action-pimpinan'            => 'lainnya',
        'quick-action-kepala'              => 'lainnya',
        'quick-action-kepala-tu'           => 'lainnya',
        'quick-action-wali-kelas'          => 'lainnya',
    ],
];
