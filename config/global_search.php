<?php

/**
 * Registry pencarian global (topbar): menu & fitur utama.
 * `route` di-resolve dengan parameter userId pada saat pencarian.
 * Hanya gunakan nama route yang benar-benar ada (verifikasi via Route::has).
 */
return [
    // ── Utama ──
    ['label' => 'Dasbor', 'route' => 'root', 'group' => 'Utama', 'keywords' => 'dashboard home beranda'],
    ['label' => 'Data Santri', 'route' => 'user.students.index', 'group' => 'Kesiswaan', 'keywords' => 'santri siswa peserta didik murid'],
    ['label' => 'Data Mahrom', 'route' => 'user.students.mahroms.global', 'group' => 'Kesiswaan', 'keywords' => 'mahrom wali orang tua'],
    ['label' => 'Import Data Santri', 'route' => 'user.students.import-form', 'group' => 'Kesiswaan', 'keywords' => 'import unggah excel santri'],
    ['label' => 'Data Alumni', 'route' => 'user.alumni.index', 'group' => 'Kesiswaan', 'keywords' => 'alumni lulusan'],
    ['label' => 'Pengaturan Rombel', 'route' => 'user.study-groups.index', 'group' => 'Kesiswaan', 'keywords' => 'rombel kelas pengaturan'],
    ['label' => 'Data Kelas & Mapel', 'route' => 'user.grade-levels.index', 'group' => 'Akademik', 'keywords' => 'tingkat kelas mapel struktur'],
    ['label' => 'Mutasi Masuk', 'route' => 'user.mutations-in.index', 'group' => 'Kesiswaan', 'keywords' => 'mutasi masuk pindahan'],
    ['label' => 'Mutasi Keluar', 'route' => 'user.mutations-out.index', 'group' => 'Kesiswaan', 'keywords' => 'mutasi keluar pindah'],
    ['label' => 'Pindahkan Santri', 'route' => 'user.student-move.index', 'group' => 'Kesiswaan', 'keywords' => 'pindah rombel kelas'],
    ['label' => 'Poin Pelanggaran', 'route' => 'user.violation-points.index', 'group' => 'Kesiswaan', 'keywords' => 'pelanggaran disiplin poin'],
    ['label' => 'Dashboard Kedisiplinan', 'route' => 'user.violation-points.dashboard', 'group' => 'Kesiswaan', 'keywords' => 'pelanggaran dashboard kedisiplinan'],
    ['label' => 'Rekap Pelanggaran', 'route' => 'user.violation-points.recap', 'group' => 'Kesiswaan', 'keywords' => 'rekap pelanggaran poin'],
    ['label' => 'Prestasi Santri', 'route' => 'user.student-achievement.index', 'group' => 'Kesiswaan', 'keywords' => 'prestasi penghargaan'],

    // ── GTK & Tugas ──
    ['label' => 'Data GTK', 'route' => 'user.gtk.index', 'group' => 'Kepegawaian', 'keywords' => 'guru karyawan gtk pegawai tendik'],
    ['label' => 'Data Guru', 'route' => 'user.gtk.indexguru', 'group' => 'Kepegawaian', 'keywords' => 'guru pengajar'],
    ['label' => 'Data Tendik', 'route' => 'user.gtk.indextendik', 'group' => 'Kepegawaian', 'keywords' => 'tendik administrasi'],
    ['label' => 'Pengajuan GTK', 'route' => 'user.gtk-requests.index', 'group' => 'Kepegawaian', 'keywords' => 'pengajuan gtk rekrutmen'],
    ['label' => 'Tugas Tambahan', 'route' => 'user.other-teacher-tasks.index', 'group' => 'GTK & Tugas', 'keywords' => 'tugas tambahan wali kelas koordinator'],
    ['label' => 'Supervisi & Kinerja GTK', 'route' => 'user.rapor-gtk.tahunan', 'group' => 'GTK & Tugas', 'keywords' => 'kinerja supervisi rapor gtk'],
    ['label' => 'Evaluasi Guru Mapel', 'route' => 'user.kinerja.index', 'group' => 'GTK & Tugas', 'keywords' => 'evaluasi kinerja guru mapel'],

    // ── Akademik & Kurikulum ──
    ['label' => 'Struktur Kurikulum (per Jenjang)', 'route' => 'user.sa.curriculum-structure.matrix', 'group' => 'Kurikulum', 'keywords' => 'struktur kurikulum matriks jenjang pondok'],
    ['label' => 'Peta Kurikulum', 'route' => 'user.kurikulum.index', 'group' => 'Kurikulum', 'keywords' => 'kurikulum peta kktp'],
    ['label' => 'Capaian Pembelajaran (CP)', 'route' => 'user.kurikulum.cp.index', 'group' => 'Kurikulum', 'keywords' => 'cp capaian pembelajaran'],
    ['label' => 'Tujuan Pembelajaran (TP)', 'route' => 'user.kurikulum.tp.index', 'group' => 'Kurikulum', 'keywords' => 'tp tujuan pembelajaran'],
    ['label' => 'ATP', 'route' => 'user.kurikulum.atp.index', 'group' => 'Kurikulum', 'keywords' => 'atp alur tujuan pembelajaran'],
    ['label' => 'PROTA', 'route' => 'user.kurikulum.prota.index', 'group' => 'Kurikulum', 'keywords' => 'prota program tahunan'],
    ['label' => 'PROSEM', 'route' => 'user.kurikulum.prosem.index', 'group' => 'Kurikulum', 'keywords' => 'prosem program semester'],
    ['label' => 'Jurnal Pembelajaran (Realisasi)', 'route' => 'user.kurikulum.realisasi.index', 'group' => 'Kurikulum', 'keywords' => 'jurnal realisasi pembelajaran'],
    ['label' => 'Modul Ajar (RPM)', 'route' => 'user.kurikulum.perangkat.index', 'group' => 'Kurikulum', 'keywords' => 'rpm perangkat modul ajar'],
    ['label' => 'Permintaan Akses Edit', 'route' => 'user.kurikulum.akses-edit.index', 'group' => 'Kurikulum', 'keywords' => 'izin edit akses dokumen'],
    ['label' => 'Master Rumpun', 'route' => 'user.kurikulum.rumpun.index', 'group' => 'Kurikulum', 'keywords' => 'rumpun mapel serumpun koordinator'],
    ['label' => 'Tahun Ajaran', 'route' => 'user.academic-years.index', 'group' => 'Akademik', 'keywords' => 'tahun ajaran semester'],
    ['label' => 'Mata Pelajaran', 'route' => 'user.subjects.index', 'group' => 'Akademik', 'keywords' => 'mapel mata pelajaran'],
    ['label' => 'Plotting Guru Mengajar', 'route' => 'user.teaching-assignments.index', 'group' => 'Akademik', 'keywords' => 'plotting penugasan guru mengajar sk'],
    ['label' => 'Input Massal Penugasan', 'route' => 'user.teaching-assignments.bulk-create', 'group' => 'Akademik', 'keywords' => 'input massal penugasan guru tabel'],
    ['label' => 'SK & Keputusan', 'route' => 'user.institution-decrees.index', 'group' => 'Akademik', 'keywords' => 'sk surat keputusan pembagian tugas'],
    ['label' => 'Jadwal Pelajaran', 'route' => 'user.jadwal-kbm.index', 'group' => 'Akademik', 'keywords' => 'jadwal pelajaran kbm generate'],
    ['label' => 'Jam Pelajaran', 'route' => 'user.jam-pelajaran.index', 'group' => 'Akademik', 'keywords' => 'jam pelajaran slot istirahat'],
    ['label' => 'Pekan Efektif', 'route' => 'user.pekan-efektif.index', 'group' => 'Akademik', 'keywords' => 'pekan efektif minggu'],
    ['label' => 'Kalender Pendidikan (Kaldik)', 'route' => 'user.kaldik.index', 'group' => 'Akademik', 'keywords' => 'kaldik kalender libur tahunan'],
    ['label' => 'Agenda Kegiatan', 'route' => 'user.kalender-kegiatan.akademik', 'group' => 'Akademik', 'keywords' => 'agenda kegiatan kalender'],

    // ── Penilaian ──
    ['label' => 'Buku Nilai Saya', 'route' => 'user.schools.guru-mapel.index', 'group' => 'Penilaian', 'keywords' => 'nilai buku guru sumatif formatif jurnal'],
    ['label' => 'Data Nilai', 'route' => 'user.schools.nilai.index', 'group' => 'Penilaian', 'keywords' => 'data nilai'],
    ['label' => 'Leger & Cetak Rapor', 'route' => 'user.schools.nilai-kelas.index', 'group' => 'Penilaian', 'keywords' => 'leger cetak rapor nilai kelas'],
    ['label' => 'Cetak Rapor', 'route' => 'user.schools.nilai-kelas.rapor', 'group' => 'Penilaian', 'keywords' => 'cetak rapor print'],

    // ── Kehadiran ──
    ['label' => 'Presensi Harian', 'route' => 'user.absensi.harian.index', 'group' => 'Kehadiran', 'keywords' => 'absensi kehadiran harian presensi'],
    ['label' => 'Rekap Absensi Kelas', 'route' => 'user.absensi.harian.recap', 'group' => 'Kehadiran', 'keywords' => 'rekap absensi kelas'],
    ['label' => 'Absensi GTK', 'route' => 'user.absensi-gtk.index', 'group' => 'Kehadiran', 'keywords' => 'absensi gtk guru'],
    ['label' => 'Scan QR Kehadiran', 'route' => 'user.teacher-qr.scan', 'group' => 'Kehadiran', 'keywords' => 'scan qr kehadiran guru'],
    ['label' => 'Riwayat Kehadiran', 'route' => 'user.teacher-qr.history', 'group' => 'Kehadiran', 'keywords' => 'riwayat kehadiran guru'],
    ['label' => 'Kehadiran Guru Per Jam', 'route' => 'user.teacher-qr.waka-dashboard', 'group' => 'Kehadiran', 'keywords' => 'monitoring kehadiran guru per jam'],

    // ── Evaluasi / Asesmen ──
    ['label' => 'Kisi-Kisi Soal', 'route' => 'user.kisi-kisi-soal.index', 'group' => 'Evaluasi', 'keywords' => 'kisi kisi soal'],
    ['label' => 'Bank Soal & Input Soal', 'route' => 'user.bank-soal.index', 'group' => 'Evaluasi', 'keywords' => 'bank soal input soal'],
    ['label' => 'Repository Soal', 'route' => 'user.bank-soal-terpusat.index', 'group' => 'Evaluasi', 'keywords' => 'repository soal terpusat'],
    ['label' => 'Review Soal Serumpun', 'route' => 'user.review-soal.index', 'group' => 'Evaluasi', 'keywords' => 'review telaah soal serumpun'],
    ['label' => 'Paket Soal Sumatif', 'route' => 'user.paket-soal.index', 'group' => 'Evaluasi', 'keywords' => 'paket soal ujian sumatif'],
    ['label' => 'TU — Cetak Paket Final', 'route' => 'user.tu-paket-soal.index', 'group' => 'Evaluasi', 'keywords' => 'cetak paket final tu'],

    // ── Unit lain ──
    ['label' => 'Ekstrakurikuler', 'route' => 'waka.ekstrakurikuler.index', 'group' => 'Ekstrakurikuler', 'keywords' => 'ekskul ekstrakurikuler'],
    ['label' => 'Sarpras — Dashboard', 'route' => 'sarpras.user.dashboard', 'group' => 'Sarpras', 'keywords' => 'sarpras sarana prasarana'],
    ['label' => 'Sarpras — Inventaris Aset', 'route' => 'sarpras.user.aset.index', 'group' => 'Sarpras', 'keywords' => 'aset inventaris sarpras'],
    ['label' => 'UKS', 'route' => 'user.uks.dashboard', 'group' => 'Kesehatan', 'keywords' => 'uks klinik sakit pasien'],
    ['label' => 'Surat Masuk', 'route' => 'waka.surat-masuk.index', 'group' => 'Administrasi', 'keywords' => 'surat masuk'],
    ['label' => 'Surat Keluar', 'route' => 'waka.surat-keluar.index', 'group' => 'Administrasi', 'keywords' => 'surat keluar'],
    ['label' => 'Dokumen Mutu (ISO)', 'route' => 'user.dokumen-iso.index', 'group' => 'Administrasi', 'keywords' => 'iso dokumen mutu'],
    ['label' => 'Laporan', 'route' => 'user.laporan.index', 'group' => 'Laporan', 'keywords' => 'laporan presensi santri gtk'],
    ['label' => 'Pengaturan Sistem & WA Gateway', 'route' => 'user.sa.system-settings.index', 'group' => 'Sistem', 'keywords' => 'pengaturan settings whatsapp wa gateway notifikasi fonnte'],
];
