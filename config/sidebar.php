<?php

/**
 * Sidebar Menu Configuration — ALL menus defined in code
 *
 * Access control per menu per role di database (sidebar_accesses table).
 * Edit config ini untuk mengatur struktur, route, icon, urutan menu.
 * Edit sidebar_accesses untuk mengatur siapa yang bisa akses.
 *
 * Format:
 * 'key' => [
 *     'label'      => 'Label Menu',
 *     'icon'       => 'remix-icon',
 *     'route'      => 'route.name',   // null = placeholder (belum ada route)
 *     'params'     => [],             // route params (optional)
 *     'query'      => '?foo=bar',     // query string (optional)
 *     'children'   => [...],          // sub-menu (optional)
 *     'roles'      => ['Role Name'],  // role restriction di config (optional, DB override)
 * ]
 */

return [

    // ═══════════════════════════════════════════════════════════
    //  SUPER ADMIN
    // ═══════════════════════════════════════════════════════════
    'sa' => [
        'label'   => 'Super Admin',
        'icon'    => 'ri-shield-star-line',
        'is_group' => true,
        'children' => [
            'sa.users' => [
                'label' => 'Manajemen User',
                'route' => 'user.sa.users.index',
                'params' => ['userId' => '__userId__'],
            ],
            'sa.roles' => [
                'label' => 'Roles & Permissions',
                'route' => 'user.sa.roles.index',
                'params' => ['userId' => '__userId__'],
            ],
            'sa.permissions' => [
                'label' => 'Permissions',
                'route' => 'user.sa.permissions.index',
                'params' => ['userId' => '__userId__'],
            ],
            'sa.audit_logs' => [
                'label' => 'Audit Log',
                'route' => 'user.sa.audit-logs.index',
                'params' => ['userId' => '__userId__'],
            ],
            'sa.tokens' => [
                'label' => 'Token & Sesi',
                'route' => 'user.sa.tokens.index',
                'params' => ['userId' => '__userId__'],
            ],
            'sa.failed_jobs' => [
                'label' => 'Failed Jobs',
                'route' => 'user.sa.failed-jobs.index',
                'params' => ['userId' => '__userId__'],
            ],
            'sa.notifications' => [
                'label' => 'Notifikasi Universal',
                'route' => 'user.sa.notifications.index',
                'params' => ['userId' => '__userId__'],
            ],
            'sa.sidebar_menus' => [
                'label' => 'Kelola Menu Sidebar',
                'route' => 'user.sa.sidebar-menus.index',
                'params' => ['userId' => '__userId__'],
            ],
            'sa.password_reset_logs' => [
                'label' => 'Password Reset Logs',
                'route' => 'user.sa.password-reset-logs.index',
                'params' => ['userId' => '__userId__'],
            ],
            'sa.system_settings' => [
                'label' => 'Pengaturan Sistem',
                'route' => 'user.sa.system-settings.index',
                'params' => ['userId' => '__userId__'],
            ],
        ],
    ],

    // ═══════════════════════════════════════════════════════════
    //  UMUM
    // ═══════════════════════════════════════════════════════════
    'notifications' => [
        'label' => 'Notifikasi',
        'icon'  => 'bx bx-bell',
        'route' => 'user.notifications.index',
        'params' => ['userId' => '__userId__'],
    ],

    // ═══════════════════════════════════════════════════════════
    //  ADMIN TATA USAHA / WAKA KEPALA SEKOLAH
    // ═══════════════════════════════════════════════════════════

    // Menu Utama
    'dashboard' => [
        'label' => 'Dashboard',
        'icon'  => 'ri-home-6-line',
        'route' => 'root',
    ],

    'sekolah' => [
        'label'  => 'Satuan Pendidikan',
        'icon'   => 'ri-government-line',
        'route'  => 'user.schools.show',
        'params' => ['userId' => '__userId__'],
    ],

    // GTK & Peserta Didik
    'gtk' => [
        'label'   => 'Data GTK',
        'icon'    => 'ri-contacts-book-2-line',
        'is_group' => true,
        'children' => [
            'gtk.guru' => [
                'label' => 'Guru',
                'route' => 'user.gtk.indexguru',
                'params' => ['userId' => '__userId__'],
            ],
            'gtk.tendik' => [
                'label' => 'Tendik',
                'route' => 'user.gtk.indextendik',
                'params' => ['userId' => '__userId__'],
            ],
        ],
    ],

    'peserta_didik' => [
        'label'   => 'Peserta Didik',
        'icon'    => 'ri-team-line',
        'is_group' => true,
        'children' => [
            'grade_levels' => [
                'label' => 'Data Kelas',
                'route' => 'user.grade-levels.index',
                'params' => ['userId' => '__userId__'],
            ],
            'study_groups' => [
                'label' => 'Pengaturan Rombel',
                'route' => 'user.study-groups.index',
                'params' => ['userId' => '__userId__'],
            ],
            'rombel' => [
                'label' => 'Rombel',
                'route' => 'user.students.index',
                'params' => ['userId' => '__userId__'],
            ],
            'mutasi_in' => [
                'label' => 'Mutasi PD',
                'route' => 'user.mutations-in.index',
                'params' => ['userId' => '__userId__'],
            ],
            'mutasi_out' => [
                'label' => 'PD Keluar',
                'route' => 'user.mutations-out.index',
                'params' => ['userId' => '__userId__'],
            ],
        ],
    ],

    'pelanggaran' => [
        'label'   => 'Poin Pelanggaran',
        'icon'    => 'ri-spam-line',
        'route'   => 'user.violation-points.index',
        'params'  => ['userId' => '__userId__'],
    ],

    // ═══════════════════════════════════════════════════════════
    //  UKS — KESEHATAN
    // ═══════════════════════════════════════════════════════════
    'uks' => [
        'label'   => 'UKS',
        'icon'    => 'ri-heart-pulse-line',
        'is_group' => true,
        'children' => [
            'uks.immunizations' => [
                'label' => 'Imunisasi',
                'route' => 'user.uks.immunizations.index',
                'params' => ['userId' => '__userId__'],
            ],
            'uks.health_checkups' => [
                'label' => 'Medical Check-up',
                'route' => 'user.uks.health-checkups.index',
                'params' => ['userId' => '__userId__'],
            ],
            'uks.health_permits' => [
                'label' => 'Izin Sakit',
                'route' => 'user.uks.health-permits.index',
                'params' => ['userId' => '__userId__'],
            ],
            'uks.medicine_inventory' => [
                'label' => 'Inventori Obat',
                'route' => 'user.uks.medicine-inventory.index',
                'params' => ['userId' => '__userId__'],
            ],
            'uks.medicine_logs' => [
                'label' => 'Pemberian Obat',
                'route' => 'user.uks.medicine-logs.index',
                'params' => ['userId' => '__userId__'],
            ],
            'uks.health_metrics' => [
                'label' => 'Antropometri',
                'route' => 'user.uks.health-metrics.index',
                'params' => ['userId' => '__userId__'],
            ],
            'uks.counseling_records' => [
                'label' => 'Konseling',
                'route' => 'user.uks.counseling-records.index',
                'params' => ['userId' => '__userId__'],
            ],
            'uks.facility_referrals' => [
                'label' => 'Faskes Rujukan',
                'route' => 'user.uks.facility-referrals.index',
                'params' => ['userId' => '__userId__'],
            ],
            'uks.sanitation_inspections' => [
                'label' => 'Inspeksi Sanitasi',
                'route' => 'user.uks.sanitation-inspections.index',
                'params' => ['userId' => '__userId__'],
            ],
        ],
    ],

    'alumni' => [
        'label' => 'Data Alumni',
        'icon'  => 'ri-group-2-line',
        'route' => 'user.alumni.index',
        'params' => ['userId' => '__userId__'],
    ],

    // Akademik
    'subjects' => [
        'label' => 'Mata Pelajaran',
        'icon'  => 'ri-book-open-line',
        'route' => 'user.subjects.index',
        'params' => ['userId' => '__userId__'],
    ],

    'sumatif' => [
        'label'   => 'Pelaksanaan Sumatif',
        'icon'    => 'ri-file-edit-line',
        'is_group' => true,
        'children' => [
            'kisi_kisi' => [
                'label' => 'Kisi-Kisi Soal',
                'route' => null,
            ],
            'soal_sumatif' => [
                'label' => 'Soal Sumatif',
                'route' => null,
            ],
        ],
    ],

    'nilai' => [
        'label'   => 'Data Nilai',
        'icon'    => 'ri-survey-line',
        'is_group' => true,
        'children' => [
            'nilai_sts' => [
                'label' => 'Nilai STS',
                'route' => 'user.schools.nilai.index',
                'params' => ['userId' => '__userId__'],
            ],
            'nilai_sas' => [
                'label' => 'Nilai SAS',
                'route' => 'user.schools.nilai.index',
                'params' => ['userId' => '__userId__'],
            ],
            'buku_admin_guru' => [
                'label' => 'Buku Admin Guru',
                'route' => 'user.schools.guru-mapel.index',
                'params' => ['userId' => '__userId__'],
            ],
        ],
    ],

    'absensi' => [
        'label'   => 'Absensi',
        'icon'    => 'ri-contacts-book-line',
        'is_group' => true,
        'children' => [
            'absensi_gtk' => [
                'label' => 'Absensi GTK',
                'route' => null,
            ],
            'absensi_pd' => [
                'label' => 'Absensi Peserta Didik',
                'route' => null,
            ],
        ],
    ],

    'prestasi' => [
        'label'   => 'Data Prestasi',
        'icon'    => 'ri-trophy-line',
        'is_group' => true,
        'children' => [
            'prestasi_akademik' => [
                'label' => 'Prestasi Akademik',
                'route' => null,
            ],
            'hafalan_quran' => [
                'label' => "Hafalan Qur'an",
                'route' => null,
            ],
            'hafalan_hadits' => [
                'label' => 'Hafalan Hadits',
                'route' => null,
            ],
        ],
    ],

    'ekskul' => [
        'label' => 'Ekstrakurikuler',
        'icon'  => 'ri-basketball-line',
        'route' => null,
    ],

    'supervisi' => [
        'label'  => 'Supervisi',
        'icon'   => 'ri-file-excel-2-line',
        'route'  => null,
        'roles'  => ['Wakil Kepala Sekolah'],
    ],

    // Administrasi
    'jadwal_kbm' => [
        'label'   => 'Jadwal KBM',
        'icon'    => 'ri-file-text-line',
        'is_group' => true,
        'children' => [
            'sk_guru' => [
                'label' => 'SK Guru',
                'route' => 'user.institution-decrees.index',
                'params' => ['userId' => '__userId__'],
            ],
            'jadwal_pelajaran' => [
                'label' => 'Jadwal Pelajaran',
                'route' => null,
            ],
            'jam_mengajar' => [
                'label' => 'Jam Mengajar',
                'route' => null,
            ],
            'rekap_pergantian' => [
                'label' => 'Rekap Pergantian Jam',
                'route' => null,
            ],
        ],
    ],

    'surat_menyurat' => [
        'label'   => 'Surat Menyurat',
        'icon'    => 'ri-mail-send-line',
        'is_group' => true,
        'children' => [
            'surat_keluar' => [
                'label' => 'Surat Keluar',
                'route' => null,
            ],
            'surat_masuk' => [
                'label' => 'Surat Masuk',
                'route' => null,
            ],
        ],
    ],

    'dokumen_iso' => [
        'label' => 'Dokumen ISO',
        'icon'  => 'ri-dashboard-2-line',
        'route' => null,
    ],

    // Pendukung
    'agenda_kegiatan' => [
        'label'   => 'Agenda Kegiatan',
        'icon'    => 'ri-calendar-todo-line',
        'is_group' => true,
        'children' => [
            'kaldik' => [
                'label' => 'Kaldik',
                'route' => null,
            ],
            'pekan_efektif' => [
                'label' => 'Pekan Efektif',
                'route' => null,
            ],
        ],
    ],

    'sarpras' => [
        'label'   => 'Sarana Prasarana',
        'icon'    => 'ri-community-line',
        'is_group' => true,
        'children' => [
            'sarpras.dashboard' => [
                'label' => 'Dashboard',
                'route' => 'sarpras.dashboard',
            ],
            'sarpras.gedung' => [
                'label' => 'Gedung',
                'route' => 'sarpras.gedung.index',
            ],
            'sarpras.ruang' => [
                'label' => 'Ruangan',
                'route' => 'sarpras.ruang.index',
            ],
            'sarpras.aset' => [
                'label' => 'Aset / Inventaris',
                'route' => 'sarpras.aset.index',
            ],
            'sarpras.peminjaman' => [
                'label' => 'Peminjaman Aset',
                'route' => 'sarpras.peminjaman.index',
            ],
            'sarpras.pemeliharaan' => [
                'label' => 'Pemeliharaan',
                'route' => 'sarpras.pemeliharaan.index',
            ],
            'sarpras.booking' => [
                'label' => 'Booking Ruangan',
                'route' => 'sarpras.booking.index',
            ],
            'sarpras.perpindahan' => [
                'label' => 'Riwayat Perpindahan',
                'route' => 'sarpras.perpindahan.index',
            ],
            'sarpras.pengadaan' => [
                'label' => 'Pengadaan Barang',
                'route' => 'sarpras.pengadaan.index',
            ],
            'sarpras.qr' => [
                'label' => 'QR Code & Audit',
                'route' => 'sarpras.qr.index',
            ],
            'sarpras.laporan' => [
                'label' => 'Laporan',
                'route' => 'sarpras.laporan.index',
            ],
        ],
    ],

    // ═══════════════════════════════════════════════════════════
    //  ASRAMA / DORMITORY
    // ═══════════════════════════════════════════════════════════
    'asrama' => [
        'label'   => 'Asrama',
        'icon'    => 'ri-hotel-line',
        'is_group' => true,
        'children' => [
            'asrama.daftar' => [
                'label' => 'Daftar Asrama',
                'route' => 'user.asrama.index',
                'params' => ['userId' => '__userId__'],
            ],
            'asrama.penghuni' => [
                'label' => 'Penghuni Asrama',
                'route' => null, // dynamic per asrama
            ],
            'asrama.absensi' => [
                'label' => 'Absensi Asrama',
                'route' => null,
            ],
            'asrama.izin' => [
                'label' => 'Perizinan',
                'route' => null,
            ],
            'asrama.pelanggaran' => [
                'label' => 'Pelanggaran',
                'route' => null,
            ],
            'asrama.informasi' => [
                'label' => 'Informasi & Broadcast',
                'route' => null,
            ],
            'asrama.kunjungan' => [
                'label' => 'Kunjungan',
                'route' => null,
            ],
        ],
    ],

    // ═══════════════════════════════════════════════════════════
    //  GTK (Guru Mapel)
    // ═══════════════════════════════════════════════════════════
    'gtk_buku_admin' => [
        'label'  => 'Buku Admin Guru',
        'icon'   => 'ri-book-2-line',
        'route'  => 'user.schools.guru-mapel.index',
        'params' => ['userId' => '__userId__'],
        'roles'  => ['GTK'],
    ],

    'gtk_study_groups' => [
        'label' => 'Rombongan Belajar',
        'icon'  => 'ri-file-list-3-line',
        'route' => 'user.study-groups.index',
        'params' => ['userId' => '__userId__'],
        'roles'  => ['GTK'],
    ],

    'gtk_nilai' => [
        'label'  => 'Leger Nilai',
        'icon'   => 'ri-survey-line',
        'route'  => 'user.schools.nilai.index',
        'params' => ['userId' => '__userId__'],
        'roles'  => ['GTK'],
    ],

    // ═══════════════════════════════════════════════════════════
    //  MASTER DATA
    // ═══════════════════════════════════════════════════════════
    'master_data' => [
        'label'   => 'Master Data',
        'icon'    => 'bx bx-slider',
        'is_group' => true,
        'children' => [
            'master_data.jenis_gtk' => [
                'label' => 'Jenis GTK',
                'route' => 'user.master-data.jenis-gtk.index',
                'params' => ['userId' => '__userId__'],
            ],
            'master_data.jabatan' => [
                'label' => 'Jabatan',
                'route' => 'user.master-data.jabatan.index',
                'params' => ['userId' => '__userId__'],
            ],
            'master_data.satuan_kerja' => [
                'label' => 'Satuan Kerja',
                'route' => 'user.master-data.satuan-kerja.index',
                'params' => ['userId' => '__userId__'],
            ],
        ],
    ],

    // ═══════════════════════════════════════════════════════════
    //  GTK REQUESTS & APPROVALS
    // ═══════════════════════════════════════════════════════════
    'gtk_requests' => [
        'label'   => 'GTK Requests',
        'icon'    => 'ri-git-pull-request-line',
        'is_group' => true,
        'children' => [
            'gtk_requests.index' => [
                'label' => 'Daftar Request GTK',
                'route' => 'user.gtk-requests.index',
                'params' => ['userId' => '__userId__'],
            ],
            'gtk_requests.pengadaan' => [
                'label' => 'Pengadaan GTK',
                'route' => 'user.gtk-requests.create',
                'params' => ['userId' => '__userId__'],
                'query' => '?type=procurement',
            ],
            'gtk_requests.percobaan' => [
                'label' => 'Pengangkatan Percobaan',
                'route' => 'user.gtk-requests.create',
                'params' => ['userId' => '__userId__'],
                'query' => '?type=trial',
            ],
            'gtk_requests.status' => [
                'label' => 'Kenaikan Status GTK',
                'route' => 'user.gtk-requests.create',
                'params' => ['userId' => '__userId__'],
                'query' => '?type=status_increase',
            ],
            'approvals' => [
                'label' => 'Approval',
                'route' => 'user.approvals.index',
                'params' => ['userId' => '__userId__'],
            ],
        ],
    ],

    // ═══════════════════════════════════════════════════════════
    //  RECRUITMENT
    // ═══════════════════════════════════════════════════════════
    'recruitment' => [
        'label'   => 'Recruitment',
        'icon'    => 'ri-team-line',
        'is_group' => true,
        'children' => [
            'recruitment.jobs' => [
                'label' => 'Lowongan',
                'route' => 'user.ats.jobs.index',
                'params' => ['userId' => '__userId__'],
            ],
            'recruitment.candidates' => [
                'label' => 'Kandidat',
                'route' => 'user.ats.candidates.index',
                'params' => ['userId' => '__userId__'],
            ],
            'recruitment.applications' => [
                'label' => 'Lamaran',
                'route' => 'user.ats.applications.index',
                'params' => ['userId' => '__userId__'],
            ],
            'recruitment.interviews' => [
                'label' => 'Jadwal Interview',
                'route' => 'user.ats.interviews.index',
                'params' => ['userId' => '__userId__'],
            ],
            'recruitment.reports' => [
                'label' => 'Reports',
                'route' => 'user.ats.reports.index',
                'params' => ['userId' => '__userId__'],
            ],
            'recruitment.settings' => [
                'label' => 'Settings',
                'route' => 'user.ats.settings.index',
                'params' => ['userId' => '__userId__'],
            ],
        ],
    ],

    // ═══════════════════════════════════════════════════════════
    //  JENJANG KARIR
    // ═══════════════════════════════════════════════════════════
    'jenjang_karir' => [
        'label'   => 'Jenjang Karir',
        'icon'    => 'bx bx-rocket',
        'is_group' => true,
        'children' => [
            'jenjang_karir.career_path' => [
                'label' => 'Career Path',
                'route' => 'user.jenjang-karir.career-path.index',
                'params' => ['userId' => '__userId__'],
            ],
            'jenjang_karir.mutasi' => [
                'label' => 'Mutasi & Rotasi',
                'route' => 'user.jenjang-karir.mutasi.index',
                'params' => ['userId' => '__userId__'],
            ],
            'jenjang_karir.promosi' => [
                'label' => 'Promosi & Demosi',
                'route' => 'user.jenjang-karir.promosi.index',
                'params' => ['userId' => '__userId__'],
            ],
            'jenjang_karir.talent' => [
                'label' => 'Talent Pool',
                'route' => 'user.jenjang-karir.talent.index',
                'params' => ['userId' => '__userId__'],
            ],
            'jenjang_karir.succession' => [
                'label' => 'Succession Plan',
                'route' => 'user.jenjang-karir.succession.index',
                'params' => ['userId' => '__userId__'],
            ],
        ],
    ],

];