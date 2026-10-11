<?php

/**
 * Registry pencarian global (topbar): menu & fitur utama.
 * `route` di-resolve dengan parameter userId pada saat pencarian.
 */
return [
    ['label' => 'Dasbor', 'route' => 'user.dashboard', 'group' => 'Utama', 'keywords' => 'dashboard home beranda'],
    ['label' => 'Data Santri', 'route' => 'user.students.index', 'group' => 'Peserta Didik', 'keywords' => 'santri siswa peserta didik murid'],
    ['label' => 'Data GTK', 'route' => 'user.gtk.index', 'group' => 'Kepegawaian', 'keywords' => 'guru karyawan gtk pegawai'],
    ['label' => 'Alumni', 'route' => 'user.alumni.index', 'group' => 'Peserta Didik', 'keywords' => 'alumni lulusan'],
    ['label' => 'Asrama', 'route' => 'user.asrama.index', 'group' => 'Asrama', 'keywords' => 'asrama dormitory kamar'],
    ['label' => 'Perizinan Asrama', 'route' => 'user.asrama.index', 'group' => 'Asrama', 'keywords' => 'izin pulang perizinan permit'],
    ['label' => 'Kunjungan Asrama', 'route' => 'user.asrama.index', 'group' => 'Asrama', 'keywords' => 'kunjungan visit'],
    ['label' => 'UKS', 'route' => 'user.uks.dashboard', 'group' => 'Kesehatan', 'keywords' => 'uks klinik sakit pasien'],
    ['label' => 'Presensi Harian', 'route' => 'user.absensi.harian.index', 'group' => 'Kehadiran', 'keywords' => 'absensi kehadiran harian presensi'],
    ['label' => 'Nilai — Buku Guru', 'route' => 'user.nilai.index', 'group' => 'Penilaian', 'keywords' => 'nilai buku nilai sumatif formatif'],
    ['label' => 'Nilai — Kelas/Leger/Rapor', 'route' => 'user.nilai-kelas.index', 'group' => 'Penilaian', 'keywords' => 'leger rapor nilai kelas'],
    ['label' => 'Bank Soal', 'route' => 'user.bank-soal.index', 'group' => 'Evaluasi', 'keywords' => 'bank soal evaluasi'],
    ['label' => 'Paket Soal', 'route' => 'user.paket-soal.index', 'group' => 'Evaluasi', 'keywords' => 'paket soal ujian sumatif'],
    ['label' => 'Review Soal', 'route' => 'user.review-soal.index', 'group' => 'Evaluasi', 'keywords' => 'review telaah soal'],
    ['label' => 'Kurikulum', 'route' => 'user.kurikulum.index', 'group' => 'Kurikulum', 'keywords' => 'kurikulum peta'],
    ['label' => 'Master Rumpun', 'route' => 'user.kurikulum.rumpun.index', 'group' => 'Kurikulum', 'keywords' => 'rumpun mapel serumpun'],
    ['label' => 'Perangkat / RPM', 'route' => 'user.kurikulum.perangkat.index', 'group' => 'Kurikulum', 'keywords' => 'rpm perangkat modul ajar'],
    ['label' => 'Kalender Pendidikan (Kaldik)', 'route' => 'user.kaldik.index', 'group' => 'Kurikulum', 'keywords' => 'kaldik kalender libur tahunan'],
    ['label' => 'Jadwal Pelajaran', 'route' => 'user.jadwal.index', 'group' => 'Kurikulum', 'keywords' => 'jadwal schedule pelajaran'],
    ['label' => 'Keuangan / Tagihan', 'route' => 'user.keuangan.index', 'group' => 'Keuangan', 'keywords' => 'keuangan tagihan spp invoice pembayaran'],
    ['label' => 'Pengaturan Sistem', 'route' => 'user.sa.system-settings.index', 'group' => 'Sistem', 'keywords' => 'pengaturan settings sistem'],
    ['label' => 'WA Gateway', 'route' => 'user.sa.system-settings.index', 'group' => 'Sistem', 'params' => ['tab' => 'notifications'], 'keywords' => 'whatsapp wa gateway notifikasi fonnte'],
];
