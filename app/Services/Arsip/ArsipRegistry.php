<?php

namespace App\Services\Arsip;

/**
 * Registri modul arsip per tahun ajaran.
 *
 * Setiap entri didefinisikan secara deklaratif (tanpa closure) agar aman
 * untuk `config:cache` dan mudah diaudit. Sumber kebenaran inventaris:
 * kolom `academic_year_id` pada tabel nyata + rantai join untuk memfilter
 * satuan pendidikan.
 */
class ArsipRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function modules(): array
    {
        return [
            [
                'key' => 'rombel', 'label' => 'Rombel', 'icon' => 'ri-layout-grid-line',
                'table' => 'study_groups', 'school_column' => 'study_groups.school_id',
                'link_route' => 'user.study-groups.index',
            ],
            [
                'key' => 'penempatan_santri', 'label' => 'Penempatan Santri (Riwayat Kelas)', 'icon' => 'ri-community-line',
                'table' => 'student_class_histories',
                'joins' => [['study_groups', 'student_class_histories.study_group_id', 'study_groups.id']],
                'school_column' => 'study_groups.school_id',
                'link_route' => 'user.students.index',
            ],
            [
                'key' => 'buku_nilai', 'label' => 'Buku Nilai Guru', 'icon' => 'ri-book-2-line',
                'table' => 'teacher_admin_books', 'school_column' => 'teacher_admin_books.school_id',
                'link_route' => 'user.schools.nilai.index',
            ],
            [
                'key' => 'nilai_sumatif', 'label' => 'Nilai Sumatif', 'icon' => 'ri-file-edit-line',
                'table' => 'admin_nilai_sumatif',
                'joins' => [['teacher_admin_books', 'admin_nilai_sumatif.admin_book_id', 'teacher_admin_books.id']],
                'school_column' => 'teacher_admin_books.school_id',
            ],
            [
                'key' => 'rapor', 'label' => 'Registrasi Rapor', 'icon' => 'ri-file-text-line',
                'table' => 'raport_registrations',
                'joins' => [['study_groups', 'raport_registrations.study_group_id', 'study_groups.id']],
                'school_column' => 'study_groups.school_id',
            ],
            [
                'key' => 'presensi_harian', 'label' => 'Presensi Harian Santri', 'icon' => 'ri-calendar-check-line',
                'table' => 'admin_presensi_harian',
                'joins' => [['study_groups', 'admin_presensi_harian.study_group_id', 'study_groups.id']],
                'school_column' => 'study_groups.school_id',
            ],
            [
                'key' => 'presensi_mapel', 'label' => 'Presensi per Mapel', 'icon' => 'ri-calendar-2-line',
                'table' => 'admin_presensi_mapel',
                'joins' => [['teacher_admin_books', 'admin_presensi_mapel.admin_book_id', 'teacher_admin_books.id']],
                'school_column' => 'teacher_admin_books.school_id',
            ],
            [
                'key' => 'jurnal_pembelajaran', 'label' => 'Jurnal Pembelajaran', 'icon' => 'ri-booklet-line',
                'table' => 'admin_jurnal_pembelajaran',
                'joins' => [['teacher_admin_books', 'admin_jurnal_pembelajaran.admin_book_id', 'teacher_admin_books.id']],
                'school_column' => 'teacher_admin_books.school_id',
            ],
            [
                'key' => 'penugasan_mengajar', 'label' => 'Penugasan Mengajar', 'icon' => 'ri-user-star-line',
                'table' => 'teaching_assignments', 'school_column' => 'teaching_assignments.school_id',
            ],
            [
                'key' => 'presensi_gtk', 'label' => 'Presensi GTK', 'icon' => 'ri-user-follow-line',
                'table' => 'teacher_attendances', 'school_column' => 'teacher_attendances.school_id',
            ],
            [
                'key' => 'tahfidz_setoran', 'label' => 'Setoran Tahfidz', 'icon' => 'ri-book-read-line',
                'table' => 'tahfidz_setorans',
                'joins' => [['students', 'tahfidz_setorans.student_id', 'students.id']],
                'school_column' => 'students.school_id',
            ],
            [
                'key' => 'tahfidz_capaian', 'label' => 'Capaian Juz', 'icon' => 'ri-award-line',
                'table' => 'tahfidz_juz_progress',
                'joins' => [['students', 'tahfidz_juz_progress.student_id', 'students.id']],
                'school_column' => 'students.school_id',
            ],
            [
                'key' => 'asrama_penghuni', 'label' => 'Penghuni Asrama', 'icon' => 'ri-hotel-bed-line',
                'table' => 'dormitory_residents',
                'joins' => [['students', 'dormitory_residents.student_id', 'students.id']],
                'school_column' => 'students.school_id',
            ],
            [
                'key' => 'kedisiplinan', 'label' => 'Kedisiplinan Santri', 'icon' => 'ri-error-warning-line',
                'table' => 'violation_points',
                'joins' => [['students', 'violation_points.student_id', 'students.id']],
                'school_column' => 'students.school_id',
            ],
            [
                'key' => 'uks_kunjungan', 'label' => 'Layanan UKS', 'icon' => 'ri-first-aid-kit-line',
                'table' => 'student_clinic_visits', 'school_column' => 'student_clinic_visits.school_id',
            ],
            [
                'key' => 'prestasi_santri', 'label' => 'Prestasi Santri', 'icon' => 'ri-medal-line',
                'table' => 'student_achievements', 'school_column' => 'student_achievements.school_id',
            ],
            [
                'key' => 'ekstrakurikuler', 'label' => 'Anggota Ekstrakurikuler', 'icon' => 'ri-flag-line',
                'table' => 'extracurricular_members', 'school_column' => 'extracurricular_members.school_id',
            ],
            [
                'key' => 'kalender_kaldik', 'label' => 'Kalender Pendidikan', 'icon' => 'ri-calendar-event-line',
                'table' => 'kaldik',
            ],
            [
                'key' => 'pekan_efektif', 'label' => 'Pekan Efektif', 'icon' => 'ri-calendar-check-2-line',
                'table' => 'pekan_efektif', 'school_column' => 'pekan_efektif.school_id',
            ],
            [
                'key' => 'kurikulum_atp', 'label' => 'ATP (Alur Tujuan Pembelajaran)', 'icon' => 'ri-route-line',
                'table' => 'alur_tujuan_pembelajaran', 'school_column' => 'alur_tujuan_pembelajaran.school_id',
                'link_route' => 'user.kurikulum.index',
            ],
            [
                'key' => 'kurikulum_prota', 'label' => 'PROTA', 'icon' => 'ri-calendar-schedule-line',
                'table' => 'prota', 'school_column' => 'prota.school_id',
            ],
            [
                'key' => 'kurikulum_prosem', 'label' => 'PROSEM', 'icon' => 'ri-calendar-2-fill',
                'table' => 'prosem', 'school_column' => 'prosem.school_id',
            ],
            [
                'key' => 'perangkat_rpm', 'label' => 'Perangkat / RPM', 'icon' => 'ri-booklet-fill',
                'table' => 'perangkat_pembelajaran', 'school_column' => 'perangkat_pembelajaran.school_id',
            ],
            [
                'key' => 'kktp', 'label' => 'KKTP / KKM', 'icon' => 'ri-focus-2-line',
                'table' => 'subject_kktp', 'school_column' => 'subject_kktp.school_id',
            ],
            [
                'key' => 'ujian_ljk', 'label' => 'Ujian LJK', 'icon' => 'ri-scan-2-line',
                'table' => 'ljk_exams', 'school_column' => 'ljk_exams.school_id',
            ],
            [
                'key' => 'supervisi', 'label' => 'Supervisi Guru', 'icon' => 'ri-file-excel-2-line',
                'table' => 'supervisi', 'school_column' => 'supervisi.school_id',
            ],
        ];
    }

    /**
     * Modul yang belum dibangun dengan dimensi periode — jujur apa adanya,
     * agar tidak diklaim didukung tanpa bukti.
     *
     * @return array<int, array{label: string, note: string}>
     */
    public function unsupportedModules(): array
    {
        return [
            ['label' => 'Perpustakaan (transaksi peminjaman)', 'note' => 'Modul/tabel transaksi belum tersedia — belum ada data untuk diarsipkan.'],
            ['label' => 'Surat Masuk & Surat Keluar', 'note' => 'Arsip administratif kronologis (tanggal), tidak terikat tahun ajaran — tepat disimpan sebagai arsip dokumen, bukan arsip periode.'],
            ['label' => 'Aset Sarana Prasarana', 'note' => 'Berbasis siklus hidup aset (event log, mutasi, pemeliharaan) lintas tahun ajaran — tidak dibekukan per TA, riwayatnya tetap melekat pada aset.'],
            ['label' => 'Kasir SPP / Keuangan', 'note' => 'Tabel spp_bills sudah menyimpan academic_year_id, namun modul kasirnya belum berjalan.'],
            ['label' => 'Absensi GTK (Personalia)', 'note' => 'Tabel absensi_gtk berbasis tanggal tanpa academic_year_id — disarankan menautkan ke periode saat modul disempurnakan.'],
            ['label' => 'Capaian Pembelajaran (CP)', 'note' => 'CP bersifat standar per fase (global), belum berversi per TA — perubahan standar dapat memengaruhi interpretasi TP/ATP lama.'],
            ['label' => 'Struktur Organisasi & Jabatan (master)', 'note' => 'Master organisasi tidak berversi per TA; penugasan operasionalnya sudah berperiode (structural_assignments).'],
            ['label' => 'Aturan Akademik (kenaikan, kelulusan, format rapor)', 'note' => 'Masih hidup di kode/konfigurasi — belum menjadi data berversi yang dapat diarsipkan per periode.'],
        ];
    }
}
