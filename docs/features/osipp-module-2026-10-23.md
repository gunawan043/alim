# Modul OSIPP — Ruang Kerja Guru Koordinator

## Tujuan

Menyediakan workspace terpadu untuk Guru Koordinator OSIPP dalam:

- mengelola periode dan kepengurusan OSIPP;
- memantau program kerja dan progresnya;
- mencatat serta memverifikasi kegiatan;
- mengawasi kelengkapan administrasi (proposal, surat, notulen, daftar hadir, LPJ);
- mencatat pembinaan pengurus beserta peserta;
- mengevaluasi kepengurusan.

Modul ini **tidak** mengelola RAB, transaksi, saldo, atau verifikasi keuangan.

## Sumber Kewenangan

Urutan sumber akses:

1. sistem administrator / Super Admin;
2. permission eksplisit `osipp_view`, `osipp_manage`, `osipp_verify`, atau `osipp_evaluate` dengan cakupan sekolah yang sama;
3. penugasan resmi aktif pada `other_teacher_tasks` dengan `task_name`/`task_code` OSIPP, dibatasi `school_id` dan `academic_year_id`;
4. fallback penugasan lama `coordinator_assignments` (area/deskripsi OSIPP);
5. fallback SK `gtk_additional_tasks` yang masih aktif dan sesuai sekolah.

Nama yang dikenali: `Guru Koordinator OSIPP`, `Koordinator OSIPP`, `Pembina OSIPP`.

## Data

Tabel baru:

- `osipp_periods`
- `osipp_members`
- `osipp_programs`
- `osipp_activities`
- `osipp_documents`
- `osipp_coachings`
- `osipp_coaching_attendances`
- `osipp_evaluations`

`school_id` menjadi sumber cakupan pada periode; seluruh data turunannya mengikuti periode. Anggota memakai `students`, bukan tabel anggota baru. Penugasan memakai `other_teacher_tasks`/SK yang sudah ada.

## Alur Verifikasi

Kegiatan:

`draft → submitted → verified → completed`

atau `submitted → revision/rejected`.

Administrasi:

`draft → submitted → verified`

atau `submitted → revision/rejected`.

Verifikasi menyimpan `verified_by`, `verified_at`, dan `verification_notes`.

## Evaluasi

Lima aspek dengan bobot total 100:

- Perencanaan Program (20)
- Pelaksanaan Kegiatan (25)
- Administrasi & Dokumentasi (20)
- Kepemimpinan & Koordinasi (20)
- Pelaporan & Evaluasi (15)

Skor 1–5 dikonversi ke nilai 0–100; grade A/B/C/D dihitung otomatis.

## Deployment

```bash
php artisan migrate
php artisan db:seed --class=PermissionSeeder --force
```

Penugasan Guru Koordinator OSIPP dapat dibuat melalui menu Tugas Tambahan/Other Teacher Tasks dengan nama `Guru Koordinator OSIPP` pada sekolah dan tahun ajaran yang benar.

## Pengujian

- `tests/Feature/OsippAccessTest.php`
- `tests/Feature/OsippModuleSmokeTest.php`
