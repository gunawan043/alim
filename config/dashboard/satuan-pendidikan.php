<?php

/**
 * Dashboard config — Role: Satuan Pendidikan
 *
 * Widget final user = widgets jabatan struktural + widgets SEMUA tugas tambahan (deduplicated).
 * Key 'jabatan'         → structural_positions.code
 * Key 'tugas_tambahan'  → gtk_additional_tasks.nama_tugas (case-sensitive, persis seperti di DB)
 * Key 'sections'        → urutan + judul kelompok widget di halaman
 * Key 'section_map'     → penempatan tiap widget ke section
 */

// Kelompok widget
$welcome = ['welcome-banner'];

$statTotals = ['stat-total-santri', 'stat-total-gtk', 'stat-total-rombel'];

return [

    // ══════════════════════════════════════════════════════════════
    // JABATAN STRUKTURAL → WIDGET
    // ══════════════════════════════════════════════════════════════
    'jabatan' => [

        // ── Kepala Satuan Pendidikan ─────────────────────────────
        'tenaga_kependidikan_satuan_pendidikan_kepala_fc314' => [
            'label'   => 'Kepala Satuan Pendidikan',
            'widgets' => [
                ...$welcome,
                ...$statTotals,
                'stat-santri-baru',
                'stat-santri-masuk',
                'stat-santri-keluar',
                'stat-absensi-santri-hari-ini',
                'stat-kehadiran-guru-hari-ini',
                'stat-santri-di-uks',
                'stat-izin-aktif',
                'stat-agenda-hari-ini',
                'stat-cuti-aktif-hari-ini',
                'stat-supervisi-mendatang',
                'chart-tren-kehadiran-santri',
                'chart-gender-santri',
                'chart-top-rombel',
                'chart-santri-per-tingkat',
                'table-jadwal-pelajaran-aktif',
                'table-gtk-kontrak-habis',
                'table-rekap-siswa-tingkat',
                'table-izin-terbaru',
                'table-uks-terbaru',
                'table-cuti-terbaru',
                'table-kinerja-terbaru',
                'table-agenda-mendatang',
                'quick-action-kepala',
            ],
        ],

        // ── Wakil Kepala / Wakasek Satuan Pendidikan ─────────────
        'tenaga_kependidikan_satuan_pendidikan_wakil__576be' => [
            'label'   => 'Wakil Kepala Satuan Pendidikan',
            'widgets' => [
                ...$welcome,
                'stat-kehadiran-guru-hari-ini',
                'stat-kelas-kosong-hari-ini',
                'stat-absensi-santri-hari-ini',
                'stat-cuti-aktif-hari-ini',
                'stat-supervisi-mendatang',
                'stat-izin-aktif',
                'table-jadwal-supervisi',
                'chart-tren-kehadiran-guru',
                'table-kinerja-terbaru',
                ...$statTotals,
            ],
        ],
        'tenaga_kependidikan_satuan_pendidikan_wakase_0f240' => [
            'label'   => 'Wakasek Satuan Pendidikan',
            'widgets' => [
                ...$welcome,
                'stat-kehadiran-guru-hari-ini',
                'stat-kelas-kosong-hari-ini',
                'stat-absensi-santri-hari-ini',
                'stat-cuti-aktif-hari-ini',
                'stat-supervisi-mendatang',
                'stat-izin-aktif',
                'table-jadwal-supervisi',
                'chart-tren-kehadiran-guru',
                'table-kinerja-terbaru',
                ...$statTotals,
            ],
        ],

        // ── Guru (6 jenis) ───────────────────────────────────────
        'tenaga_kependidikan_satuan_pendidikan_guru_umum' => [
            'label'   => 'Guru',
            'widgets' => [
                ...$welcome,
                'timeline-jadwal-mengajar-hari-ini',
                'stat-tugas-belum-dinilai',
                'stat-bank-soal-saya',
                'stat-jumlah-siswa-diajar',
                'list-kelas-perlu-absen',
                'table-agenda-mendatang',
            ],
        ],
        'tenaga_kependidikan_satuan_pendidikan_guru_agama' => [
            'label'   => 'Guru',
            'widgets' => [
                ...$welcome,
                'timeline-jadwal-mengajar-hari-ini',
                'stat-tugas-belum-dinilai',
                'stat-bank-soal-saya',
                'stat-jumlah-siswa-diajar',
                'list-kelas-perlu-absen',
                'table-agenda-mendatang',
            ],
        ],
        'tenaga_kependidikan_satuan_pendidikan_guru_hadits' => [
            'label'   => 'Guru',
            'widgets' => [
                ...$welcome,
                'timeline-jadwal-mengajar-hari-ini',
                'stat-tugas-belum-dinilai',
                'stat-bank-soal-saya',
                'stat-jumlah-siswa-diajar',
                'list-kelas-perlu-absen',
                'table-agenda-mendatang',
            ],
        ],
        'tenaga_kependidikan_satuan_pendidikan_guru_b_a7705' => [
            'label'   => 'Guru Bahasa Arab',
            'widgets' => [
                ...$welcome,
                'timeline-jadwal-mengajar-hari-ini',
                'stat-tugas-belum-dinilai',
                'stat-bank-soal-saya',
                'stat-jumlah-siswa-diajar',
                'list-kelas-perlu-absen',
                'table-agenda-mendatang',
            ],
        ],
        'tenaga_kependidikan_satuan_pendidikan_guru_tahfidz' => [
            'label'   => 'Guru Tahfidz',
            'widgets' => [
                ...$welcome,
                'timeline-jadwal-mengajar-hari-ini',
                'stat-tugas-belum-dinilai',
                'stat-bank-soal-saya',
                'stat-jumlah-siswa-diajar',
                'list-kelas-perlu-absen',
                'table-agenda-mendatang',
            ],
        ],
        'tenaga_kependidikan_satuan_pendidikan_guru_kelas' => [
            'label'   => 'Guru Kelas',
            'widgets' => [
                ...$welcome,
                'timeline-jadwal-mengajar-hari-ini',
                'stat-tugas-belum-dinilai',
                'stat-bank-soal-saya',
                'stat-jumlah-siswa-diajar',
                'list-kelas-perlu-absen',
                'table-agenda-mendatang',
            ],
        ],

        // ── Kepala Tata Usaha ────────────────────────────────────
        'tenaga_kependidikan_satuan_pendidikan_kepala_2722e' => [
            'label'   => 'Kepala Tata Usaha',
            'widgets' => [
                ...$welcome,
                ...$statTotals,
                'stat-santri-masuk',
                'stat-santri-keluar',
                'stat-gtk-pending-verifikasi',
                'stat-santri-di-uks',
                'stat-izin-aktif',
                'stat-agenda-hari-ini',
                'stat-cuti-aktif-hari-ini',
                'chart-statistik-santri-per-rombel',
                'chart-komposisi-gtk',
                'chart-santri-per-tingkat',
                'table-santri-data-belum-lengkap',
                'table-rekap-siswa-tingkat',
                'table-izin-terbaru',
                'table-uks-terbaru',
                'table-cuti-terbaru',
                'table-agenda-mendatang',
                'quick-action-kepala-tu',
            ],
        ],
        'tenaga_kependidikan_satuan_pendidikan_kepala_78c89' => [
            'label'   => 'Kepala TU Sekolah',
            'widgets' => [
                ...$welcome,
                ...$statTotals,
                'stat-santri-masuk',
                'stat-santri-keluar',
                'stat-gtk-pending-verifikasi',
                'stat-santri-di-uks',
                'stat-izin-aktif',
                'stat-agenda-hari-ini',
                'stat-cuti-aktif-hari-ini',
                'chart-statistik-santri-per-rombel',
                'chart-komposisi-gtk',
                'chart-santri-per-tingkat',
                'table-santri-data-belum-lengkap',
                'table-rekap-siswa-tingkat',
                'table-izin-terbaru',
                'table-uks-terbaru',
                'table-cuti-terbaru',
                'table-agenda-mendatang',
                'quick-action-kepala-tu',
            ],
        ],

        // ── Tata Usaha / Staf TU ─────────────────────────────────
        'tenaga_kependidikan_satuan_pendidikan_tata_usaha' => [
            'label'   => 'Tata Usaha',
            'widgets' => [
                ...$welcome,
                ...$statTotals,
                'stat-santri-masuk',
                'stat-santri-keluar',
                'stat-izin-aktif',
                'stat-agenda-hari-ini',
                'chart-statistik-santri-per-rombel',
                'chart-santri-per-tingkat',
                'table-santri-data-belum-lengkap',
                'table-rekap-siswa-tingkat',
                'table-izin-terbaru',
                'table-agenda-mendatang',
                'quick-action-kepala-tu',
            ],
        ],
        'tenaga_kependidikan_satuan_pendidikan_tu_sekolah' => [
            'label'   => 'TU Sekolah',
            'widgets' => [
                ...$welcome,
                ...$statTotals,
                'stat-santri-masuk',
                'stat-santri-keluar',
                'stat-izin-aktif',
                'stat-agenda-hari-ini',
                'chart-statistik-santri-per-rombel',
                'chart-santri-per-tingkat',
                'table-santri-data-belum-lengkap',
                'table-rekap-siswa-tingkat',
                'table-izin-terbaru',
                'table-agenda-mendatang',
                'quick-action-kepala-tu',
            ],
        ],
        'tenaga_kependidikan_satuan_pendidikan_staf_t_5554d' => [
            'label'   => 'Staf Tata Usaha',
            'widgets' => [
                ...$welcome,
                ...$statTotals,
                'stat-agenda-hari-ini',
                'table-santri-data-belum-lengkap',
                'table-agenda-mendatang',
                'quick-action-kepala-tu',
            ],
        ],
        'tenaga_kependidikan_satuan_pendidikan_staf_t_f292f' => [
            'label'   => 'Staf TU Sekolah',
            'widgets' => [
                ...$welcome,
                ...$statTotals,
                'stat-agenda-hari-ini',
                'table-santri-data-belum-lengkap',
                'table-agenda-mendatang',
                'quick-action-kepala-tu',
            ],
        ],

        // ── Bendahara / Kasir / Operator Sekolah ─────────────────
        'tenaga_kependidikan_satuan_pendidikan_bendah_05e6e' => [
            'label'   => 'Bendahara Sekolah',
            'widgets' => [
                ...$welcome,
                ...$statTotals,
                'stat-payroll-bulan-ini',
                'stat-pengajuan-pengadaan',
                'stat-agenda-hari-ini',
                'table-agenda-mendatang',
                'quick-action-kepala-tu',
            ],
        ],
        'tenaga_kependidikan_satuan_pendidikan_kasir__050f1' => [
            'label'   => 'Kasir Sekolah',
            'widgets' => [
                ...$welcome,
                ...$statTotals,
                'stat-payroll-bulan-ini',
                'stat-pengajuan-pengadaan',
                'stat-agenda-hari-ini',
                'table-agenda-mendatang',
                'quick-action-kepala-tu',
            ],
        ],
        'tenaga_kependidikan_satuan_pendidikan_operat_1830f' => [
            'label'   => 'Operator Sekolah',
            'widgets' => [
                ...$welcome,
                'stat-gtk-pending-verifikasi',
                'stat-agenda-hari-ini',
                'table-santri-data-belum-lengkap',
                'table-agenda-mendatang',
            ],
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // TUGAS TAMBAHAN → WIDGET  ⭐
    // nama_tugas = persis nilai di gtk_additional_tasks.nama_tugas
    // ══════════════════════════════════════════════════════════════
    'tugas_tambahan' => [

        'Wali Kelas' => [
            'label'   => 'Wali Kelas',
            'widgets' => [
                'stat-santri-kelas-saya',
                'stat-hadir-hari-ini',
                'stat-nilai-belum-lengkap',
                'stat-rata-nilai-kelas',
                'chart-absensi-kelas',
                'table-top-5-santri-berprestasi',
                'table-pelanggaran-kelas',
                'quick-action-wali-kelas',
            ],
        ],

        'Tim Kurikulum' => [
            'label'   => 'Tim Kurikulum',
            'widgets' => [
                'table-validasi-perangkat-ajar',
                'table-jadwal-pelajaran-aktif',
            ],
        ],

        'Tim Kesiswaan' => [
            'label'   => 'Tim Kesiswaan',
            'widgets' => [
                'table-pelanggaran-terbaru',
            ],
        ],

        'Koordinator Guru Umum' => [
            'label'   => 'Koordinator Guru Umum',
            'widgets' => [
                'stat-total-guru-rumpun',
                'stat-bank-soal-rumpun',
                'chart-kehadiran-rumpun',
            ],
        ],
        'Koordinator Guru Agama' => [
            'label'   => 'Koordinator Guru Agama',
            'widgets' => [
                'stat-total-guru-rumpun',
                'stat-bank-soal-rumpun',
                'chart-kehadiran-rumpun',
            ],
        ],
        'Koordinator Guru Hadits' => [
            'label'   => 'Koordinator Guru Hadits',
            'widgets' => [
                'stat-total-guru-rumpun',
                'stat-bank-soal-rumpun',
                'chart-kehadiran-rumpun',
            ],
        ],
        'Koordinator Guru Bahasa Arab' => [
            'label'   => 'Koordinator Guru Bahasa Arab',
            'widgets' => [
                'stat-total-guru-rumpun',
                'stat-bank-soal-rumpun',
                'chart-kehadiran-rumpun',
            ],
        ],
        'Koordinator Guru Tahfidz' => [
            'label'   => 'Koordinator Guru Tahfidz',
            'widgets' => [
                'stat-total-guru-rumpun',
                'stat-bank-soal-rumpun',
                'chart-kehadiran-rumpun',
            ],
        ],

        'Koordinator Ekstrakurikuler' => [
            'label'   => 'Koordinator Ekstrakurikuler',
            'widgets' => [
                'stat-total-ekskul',
                'table-jadwal-ekskul-minggu-ini',
            ],
        ],
        'Pembina Ekstrakurikuler' => [
            'label'   => 'Pembina Ekstrakurikuler',
            'widgets' => [
                'stat-total-ekskul',
                'table-jadwal-ekskul-minggu-ini',
            ],
        ],

        'Koordinator Laboratorium' => [
            'label'   => 'Koordinator Laboratorium',
            'widgets' => [
                'stat-total-alat-lab',
            ],
        ],

        'Koordinator Sarpras Sekolah' => [
            'label'   => 'Koordinator Sarpras Sekolah',
            'widgets' => [
                'stat-aset-rusak',
            ],
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // DEFAULT (fallback jabatan tidak dikenal)
    // ══════════════════════════════════════════════════════════════
    'default' => [
        'label'   => 'Satuan Pendidikan',
        'widgets' => [
            ...$welcome,
            ...$statTotals,
        ],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION (urutan tampilan kelompok widget)
    // ══════════════════════════════════════════════════════════════
    'sections' => [
        'ringkasan'   => ['title' => 'Ringkasan',            'icon' => 'ri-dashboard-3-line'],
        'akademik'    => ['title' => 'Akademik & KBM',       'icon' => 'ri-book-open-line'],
        'kesiswaan'   => ['title' => 'Kesiswaan & Kesehatan', 'icon' => 'ri-heart-pulse-line'],
        'kepegawaian' => ['title' => 'Kepegawaian',          'icon' => 'ri-team-line'],
        'keuangan'    => ['title' => 'Keuangan & Sarana',    'icon' => 'ri-wallet-3-line'],
        'agenda'      => ['title' => 'Agenda & Kegiatan',    'icon' => 'ri-calendar-event-line'],
        'lainnya'     => ['title' => 'Tindak Lanjut',        'icon' => 'ri-checkbox-multiple-line'],
    ],

    // ══════════════════════════════════════════════════════════════
    // SECTION MAP (widget → section)
    // ══════════════════════════════════════════════════════════════
    'section_map' => [
        // Ringkasan
        'welcome-banner'                   => 'ringkasan',
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
        'stat-gtk-pending-verifikasi'      => 'ringkasan',
        'stat-payroll-bulan-ini'           => 'ringkasan',
        'stat-pengajuan-pengadaan'         => 'ringkasan',

        // Akademik & KBM
        'chart-tren-kehadiran-santri'      => 'akademik',
        'chart-gender-santri'              => 'akademik',
        'chart-top-rombel'                 => 'akademik',
        'chart-statistik-santri-per-rombel' => 'akademik',
        'chart-santri-per-tingkat'         => 'akademik',
        'chart-absensi-kelas'              => 'akademik',
        'timeline-jadwal-mengajar-hari-ini' => 'akademik',
        'list-kelas-perlu-absen'           => 'akademik',
        'table-jadwal-pelajaran-aktif'     => 'akademik',
        'table-validasi-perangkat-ajar'    => 'akademik',
        'table-top-5-santri-berprestasi'   => 'akademik',
        'table-rekap-siswa-tingkat'        => 'akademik',
        'table-pelanggaran-kelas'          => 'kesiswaan',
        'stat-tugas-belum-dinilai'         => 'akademik',
        'stat-nilai-belum-lengkap'         => 'akademik',
        'stat-rata-nilai-kelas'            => 'akademik',
        'stat-bank-soal-saya'              => 'akademik',
        'stat-bank-soal-rumpun'            => 'akademik',

        // Kepegawaian
        'chart-komposisi-gtk'              => 'kepegawaian',
        'chart-tren-kehadiran-guru'        => 'kepegawaian',
        'chart-kehadiran-rumpun'           => 'kepegawaian',
        'table-gtk-kontrak-habis'          => 'kepegawaian',
        'table-jadwal-supervisi'           => 'kepegawaian',
        'stat-total-guru-rumpun'           => 'kepegawaian',
        'stat-cuti-aktif-hari-ini'         => 'kepegawaian',
        'stat-supervisi-mendatang'         => 'kepegawaian',
        'table-cuti-terbaru'               => 'kepegawaian',
        'table-kinerja-terbaru'            => 'kepegawaian',

        // Keuangan & Sarana
        'stat-total-alat-lab'              => 'keuangan',
        'stat-aset-rusak'                  => 'keuangan',

        // Tindak lanjut
        'quick-action-kepala'              => 'lainnya',
        'quick-action-kepala-tu'           => 'lainnya',
        'quick-action-wali-kelas'          => 'lainnya',

        // Kesiswaan & Kesehatan
        'stat-santri-di-uks'               => 'kesiswaan',
        'stat-izin-aktif'                  => 'kesiswaan',
        'table-izin-terbaru'               => 'kesiswaan',
        'table-uks-terbaru'                => 'kesiswaan',
        'table-pelanggaran-terbaru'        => 'kesiswaan',
        'stat-total-ekskul'                => 'kesiswaan',
        'table-jadwal-ekskul-minggu-ini'   => 'kesiswaan',

        // Agenda & Kegiatan
        'stat-agenda-hari-ini'             => 'agenda',
        'table-agenda-mendatang'           => 'agenda',
    ],
];
