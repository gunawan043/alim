---
name: alim-core-production-safety-plan
description: Rencana perlindungan data dan urutan pekerjaan aman untuk ALIM Core produksi
metadata:
  type: plan
  plan-date: 2026-11-21
  status: proposal
  scope: audit-and-planning
  production-assumption: true
  depends-on:
    - docs/integration/alim-core-organization-data-audit.md
    - docs/integration/alim-connect-api-design.md
    - docs/integration/alim-connect-engineering-review.md
---

# Production Safety Plan — ALIM Core

> **ALIM Core dianggap sistem produksi.** Stabilitas, integritas data, dan kelangsungan operasional lebih penting daripada penambahan fitur atau penyesuaian untuk ALIM Connect.
>
> **Dokumen ini bukan izin implementasi.** Tidak ada perubahan source code, database, migration, endpoint, konfigurasi, atau data pada tahap ini.
>
> **Tidak ada pengujian runtime atau inspeksi database produksi** yang dilakukan. Seluruh temuan adalah hasil pembacaan source code, migration, route, model, observer, seeder, dan controller.

## 1. Ringkasan Eksekutif

### 1.1 Tujuan

Memastikan:

1. Tidak ada jalur operasional yang dapat menghapus atau merusak data produksi tanpa perlindungan.
2. Seluruh data organisasi, GTK, assignment, dan akademik memiliki backup serta prosedur pemulihan.
3. Perbaikan dilakukan bertahap, aditif, dan tidak mengganggu modul existing.
4. ALIM Connect hanya mengonsumsi API read-only setelah Core stabil dan data terverifikasi.

### 1.2 Kesimpulan utama

**Status: BELUM AMAN untuk dilakukan perubahan apa pun tanpa prasyarat perlindungan data.**

Temuan paling berbahaya bukan pada desain API Connect, melainkan pada **jalur destruktif yang sudah ada di ALIM Core**:

- `JenisGtkSeeder` menghapus seluruh `structural_positions`, dan FK assignment memakai cascade.
- Hard delete SK, sekolah, jabatan, tahun ajaran, tingkat kelas, mapel, dan rombel memicu cascade ke assignment dan data akademik.
- `PermissionRebuildObserver` tidak menangani assignment, sehingga perubahan kewenangan dapat tidak tercermin pada permission snapshot.
- Banyak seeder lain yang bersifat destruktif (role/permission/domain, kalender, sidebar), dan `DatabaseSeeder` memanggilnya.

**Rekomendasi:** jalankan **Fase 0 — Freeze, Backup, dan Verifikasi** sebelum menyentuh kode. Perbaikan kode hanya dilakukan setelah persetujuan eksplisit dan diuji di staging.

### 1.3 Skala prioritas

| Prioritas | Fokus | Contoh |
|---|---|---|
| **P0 — Immediate** | Mencegah kehilangan data | Freeze seeder destruktif, backup, guard delete |
| **P1 — High** | Menutup jalur cascade berbahaya | SK, sekolah, jabatan, tahun ajaran, mapel, rombel |
| **P2 — Medium** | Konsistensi data dan otorisasi | Observer assignment, `parent_id`/`divisi_id`/`domain_id`, `gtk_work_unit` |
| **P3 — Planned** | Kesiapan integrasi | API read-only, change ledger, snapshot/sync, audit |

### 1.4 Batasan penting

- Tidak ada data produksi yang dibaca atau diubah pada penyusunan dokumen ini.
- Tidak ada bukti insiden produksi yang diklaim; yang ada adalah **bukti jalur kode**.
- Tidak ada migration, seeder, rollback, atau deployment yang dijalankan.
- Semua perbaikan yang diusulkan memerlukan persetujuan terpisah.

## 2. Metode, Definisi, dan Batasan

### 2.1 Metode

1. Membaca migration untuk memetakan FK, unique constraint, nullable, dan cascade.
2. Membaca model untuk memetakan soft delete, observer, relasi, dan scope.
3. Membaca controller/route untuk memetakan jalur delete yang dapat diakses pengguna.
4. Membaca seeder dan `DatabaseSeeder` untuk memetakan jalur destruktif saat seeding.
5. Membaca service/observer/job untuk memetakan penghapusan tidak langsung.
6. Membandingkan dengan dokumen audit, desain, dan review sebelumnya.

### 2.2 Definisi status bukti

| Status | Arti |
|---|---|
| **Terverifikasi di kode** | Perilaku dipastikan dari source code/migration. |
| **Terverifikasi di data produksi** | Dikonfirmasi dari database/log produksi. **Belum dilakukan.** |
| **Terverifikasi sebagai insiden** | Ada bukti data hilang/rusak aktual. **Belum ada.** |
| **Potensial** | Jalur ada, tetapi belum diketahui apakah pernah dieksekusi. |

### 2.3 Definisi dampak

| Dampak | Arti |
|---|---|
| **Data loss** | Baris terhapus permanen atau tidak dapat direkonstruksi. |
| **Data corruption** | Relasi/status menjadi tidak konsisten. |
| **Access corruption** | Role/permission/scope berubah tanpa diinginkan. |
| **Operational disruption** | Fitur/modul tidak dapat digunakan. |
| **Audit failure** | Tidak ada jejak perubahan yang cukup. |

### 2.4 Batasan cakupan

Fokus audit: organisasi, GTK, penempatan, assignment struktural, SK, tahun ajaran, rombel, mapel, dan permission. Modul lain (sarpras, keuangan, tahfidz, UKS, asrama detail) hanya diperiksa jika jalurnya bersinggungan langsung dengan data tersebut. **Daftar ini tidak boleh dianggap sebagai audit menyeluruh seluruh modul ALIM.**

## 3. Daftar Temuan dan Jalur Kode

### 3.1 Matriks temuan

| ID | Temuan | Jalur utama | Dampak | Dapat terjadi operasional normal? | Severity | Prioritas |
|---|---|---|---|---|---|---|
| PS-01 | `DatabaseSeeder` memanggil seeder destruktif | `database/seeders/DatabaseSeeder.php:17–62` | Data loss + access corruption | Tidak harian, tetapi mungkin saat deploy/setup | Critical | P0 |
| PS-02 | `JenisGtkSeeder` menghapus posisi | `JenisGtkSeeder.php:17`; FK cascade | Assignment struktural hilang | Tidak harian; hanya jika seeder dijalankan | Critical | P0/P1 |
| PS-03 | Hard delete SK | `InstitutionDecreeController.php:827–837`; FK cascade | Assignment, mengajar, wali kelas, koordinator hilang | Ya, admin dokumen | Critical | P1 |
| PS-04 | Hard delete sekolah | `SchoolController.php:492–511`; `AdminSchoolController.php:188–195` | Tahun ajaran, rombel, assignment, akademik hilang | Ya, admin sekolah | Critical | P1 |
| PS-05 | Hard delete jabatan | `MasterDataController.php:155–160`; FK cascade | Assignment struktural hilang | Ya, admin master data | Critical | P1 |
| PS-06 | Hard delete tahun ajaran | `AcademicYearController.php:126–133`; FK cascade | Rombel, assignment, absensi/asrama hilang | Ya, admin akademik | Critical | P1 |
| PS-07 | Hard delete tingkat kelas | `GradeLevelController.php:449–462`; FK cascade | Rombel dan assignment hilang | Ya, admin akademik | Critical | P1 |
| PS-08 | Hard delete mapel | `SubjectController.php:394–407`; FK cascade | Penugasan mengajar hilang | Ya, admin kurikulum | Critical | P1 |
| PS-09 | Hapus rombel | `StudyGroupService.php:15–36`; `StudyGroupController.php:298–315` | Jadwal, riwayat, absensi, assignment hilang | Ya, admin akademik | Critical | P1 |
| PS-10 | Bulk delete mapel struktur | `SuperAdmin/CurriculumStructureController.php:212–221` | Penugasan mengajar lintas sekolah hilang | Ya, super admin | Critical | P1 |
| PS-11 | `RoleSeeder` menghapus role | `RoleSeeder.php:74,82`; FK cascade | User kehilangan role/akses | Tidak harian; deploy/setup | Critical | P0 |
| PS-12 | `PermissionRoleSeeder` menghapus semua role-permission | `PermissionRoleSeeder.php:16` | Hak akses berubah massal | Tidak harian; deploy/setup | Critical | P0 |
| PS-13 | `DomainPermissionSeeder` menghapus matriks domain | `DomainPermissionSeeder.php:28` | Permission domain hilang/berubah | Tidak harian; deploy/setup | High | P0 |
| PS-14 | `KaldikSeeder` force delete kalender | `KaldikSeeder.php:65–70` | Kalender akademik hilang | Tidak harian; deploy/setup | High | P0 |
| PS-15 | `SidebarAccessSeeder` menghapus menu | `SidebarAccessSeeder.php:153` | Menu/akses UI hilang | Tidak harian; manual | Medium | P2 |
| PS-16 | Hapus unit tidak memeriksa sekolah | `WorkUnitController.php:171–187, 232` | Unit soft-deleted; sekolah orphan | Ya, admin unit | Medium | P2 |
| PS-17 | Observer tidak menangani assignment | `PermissionRebuildObserver.php:60–70` | Permission snapshot basi | Ya, setiap perubahan assignment | High | P2 |
| PS-18 | Sinkronisasi role/employment ambigu | `GtkEmploymentObserver.php:85–110,126–146` | Role/akses tidak konsisten | Ya, perubahan jabatan | High | P2 |
| PS-19 | `gtk_work_unit` tanpa lifecycle | `gtk_work_unit` migration | Scope/sync salah | Ya, mutasi/penempatan | High | P2 |
| PS-20 | `parent_id`/`divisi_id`/`domain_id` tidak konsisten | Seeder + model fillable | Hierarki/domain salah | Ya, data existing | High | P2 |
| PS-21 | `WorkUnit::school()` hasOne vs one-to-many | `WorkUnit.php:248`; `schools` FK | Sekolah hilang dari response | Ya, unit multi-sekolah | Medium | P3 |
| PS-22 | Soft/hard delete tidak konsisten | Berbagai model | Tombstone/histori hilang | Ya, delete UI | High | P2/P3 |
| PS-23 | Tidak ada change ledger/audit lengkap | Tidak ada tabel; audit parsial | Tidak bisa deteksi perubahan | Ya, semua perubahan | High | P3 |
| PS-24 | Hapus jenis GTK (tipe tugas sudah guarded) | `MasterDataController.php:72–78`; `additional_task_types.jenis_gtk_id` cascade | Klasifikasi posisi + tipe tugas hilang | Ya, admin master | High | P2 |
| PS-25 | Force delete asrama (di luar fokus) | `Dormitory` soft delete; FK cascade | Kamar, penghuni, izin, absensi hilang | Jarang (force/purge) | High | P2 (audit terpisah) |

### 3.2 Jalur seeder dan bootstrap

#### PS-01 — `DatabaseSeeder` memanggil seeder destruktif

**Bukti:**

- `database/seeders/DatabaseSeeder.php:17–62` memanggil `RoleSeeder`, `PermissionRoleSeeder`, `DomainPermissionSeeder`, `SuperAdminDomainPermissionSeeder`, `JenisGtkSeeder`, `KaldikSeeder`, dan seeder lain.
- Komentar file menyebut “Hanya berisi seeders struktural/ketenagakerjaan untuk production.”

**Risiko:**

- Satu perintah `php artisan db:seed` dapat:
  - menghapus posisi + assignment (PS-02),
  - menghapus role legacy dan relasi user-role (PS-11),
  - menghapus seluruh role-permission lalu menyusun ulang (PS-12),
  - menghapus matriks domain (PS-13),
  - menghapus kalender kategori KALDIK (PS-14),
  - menghapus item checklist Sarpras (seeder terkait).
- Tidak ada idempotensi yang aman untuk data produksi.

**Apakah operasional normal?** Tidak setiap hari, tetapi sangat mungkin pada:
- deployment pertama/ulang,
- setup staging yang mengarah ke database produksi,
- operator menjalankan `db:seed` untuk “memperbaiki master data”.

**Severity: Critical.**

**Mitigasi minimal (belum dieksekusi):**

1. Bekukan seluruh `db:seed` di produksi.
2. Hapus/guard pemanggilan seeder destruktif dari pipeline deployment (perubahan terpisah).
3. Tambahkan konfirmasi eksplisit dan environment check.
4. Backup sebelum operasi apa pun.

#### PS-02 — `JenisGtkSeeder` menghapus `structural_positions`

**Bukti:** `JenisGtkSeeder.php:17`; FK `structural_assignments.position_id` cascade.

**Dampak:** assignment struktural hilang; `gtk_work_unit.position_id` null; `gtk_employments.jabatan_id` null; permission domain hilang; UUID posisi berubah. Selain itu, `JenisGtk::query()->delete()` (baris 21) memicu cascade `additional_task_types.jenis_gtk_id`, sehingga seluruh master tipe tugas tambahan ikut terhapus; `additional_task_type_id` pada tugas existing menjadi null saat `AdditionalTaskTypeSeeder` membuat ulang tipe dengan UUID baru.

**Mitigasi:** lihat §6 dan §7.

#### PS-11 — `RoleSeeder` menghapus role

**Bukti:** `RoleSeeder.php:74` memindahkan `model_has_roles` lalu `$oldRole->delete()`; baris 82 menghapus role legacy dengan `whereIn(...)->delete()`.

**Dampak:** role delete cascade ke `model_has_roles` dan `role_has_permissions` (FK `onDelete('cascade')` di migration permission). User dapat kehilangan akses.

#### PS-12 — `PermissionRoleSeeder` menghapus semua `role_has_permissions`

**Bukti:** `PermissionRoleSeeder.php:16` `DB::table('role_has_permissions')->delete();` lalu menyusun ulang mapping.

**Dampak:** semua custom mapping yang tidak ada di seeder hilang; akses berubah massal. Seeder tidak dibungkus transaksi, sehingga kegagalan di tengah proses dapat meninggalkan permission parsial. `permission_snapshots` yang sudah ada dapat tetap memakai permission lama sampai TTL/rebuild, sehingga cache dan database bisa tidak sinkron.

#### PS-13 — `DomainPermissionSeeder` menghapus matriks domain

**Bukti:** `DomainPermissionSeeder.php:28` `DB::table('domain_role_permissions')->delete();`; `SuperAdminDomainPermissionSeeder.php:44` menghapus baris super admin lalu insert ulang.

**Dampak:** permission domain hilang/berubah; `activeDomainIds()`/`effectivePermissionNames()` tidak sesuai. Seeder tidak transaksional, sehingga kegagalan dapat meninggalkan matriks parsial. `permission_snapshots` dapat tetap memakai permission lama sampai TTL/rebuild.

#### PS-14 — `KaldikSeeder` force delete kalender

**Bukti:** `KaldikSeeder.php:65–70` `Kaldik::withTrashed()->where('category', KALDIK)->forceDelete();`.

**Dampak:** seluruh kalender kategori KALDIK (termasuk data buatan pengguna) hilang permanen.

#### PS-15 — `SidebarAccessSeeder` menghapus menu

**Bukti:** `SidebarAccessSeeder.php:153` `whereNotIn('menu_key', ...)->delete();`. Seeder ini **tidak dipanggil** oleh `DatabaseSeeder`, tetapi dapat dijalankan manual.

**Dampak:** menu yang tidak ada di definisi seeder hilang dari akses UI (bukan data akademik, tetapi operasional).

### 3.3 Jalur hard delete di controller

#### PS-03 — SK (`institution_decrees`)

**Bukti:**

- `InstitutionDecreeController.php:827–837`: menghapus `OtherTeacherTask`, `TeachingAssignment`, lalu `$decree->delete()`.
- FK cascade: `structural_assignments.decree_id`, `teaching_assignments.decree_id`, `homeroom_assignments.decree_id`, `coordinator_assignments.decree_id`.
- `InstitutionDecree` punya `deleted_at` di migration tetapi **tidak** memakai `SoftDeletes`.

**Dampak:** menghapus satu SK dapat menghapus assignment struktural, penugasan mengajar, wali kelas, dan koordinator.

#### PS-04 — Sekolah (`schools`)

**Bukti:**

- `SchoolController.php:492–511`: `$school->delete()`.
- `SuperAdmin/AdminSchoolController.php:188–195`: `$school->delete()`.
- `School` tidak memakai `SoftDeletes`.
- FK cascade: `academic_years`, `grade_levels`, `study_groups`, `structural_assignments`, `teaching_assignments`, `homeroom_assignments`, `coordinator_assignments`, `gtk_cross_unit_assignments`.
- `gtk_employments.school_id` nullOnDelete.

**Dampak:** menghapus sekolah dapat menghapus tahun ajaran, rombel, assignment, penugasan mengajar, wali kelas, koordinator, dan cross-unit.

#### PS-05 — Jabatan (`structural_positions`)

**Bukti:**

- `MasterDataController.php:155–160`: `StructuralPosition::findOrFail($id)->delete()`.
- `StructuralPosition` tanpa `SoftDeletes`.
- FK `structural_assignments.position_id` cascade; `gtk_work_unit.position_id` nullOnDelete.

**Dampak:** assignment struktural pengguna hilang; penempatan kehilangan posisi.

#### PS-06 — Tahun ajaran (`academic_years`)

**Bukti:**

- `AcademicYearController.php:126–133`: `$academicYear->delete()`.
- `AcademicYear` tanpa `SoftDeletes`.
- FK cascade: `study_groups`, `structural_assignments`, `teaching_assignments`, `homeroom_assignments`, `coordinator_assignments`, serta banyak tabel asrama (izin, absensi, penghuni, pelanggaran, inventaris).

**Dampak:** data akademik dan asrama untuk tahun ajaran tersebut hilang.

#### PS-07 — Tingkat kelas (`grade_levels`)

**Bukti:** `GradeLevelController.php:449–462`; `study_groups.grade_level_id` cascade.

**Dampak:** rombel dan seluruh assignment/absensi yang bergantung padanya hilang.

#### PS-08 — Mapel (`subjects`)

**Bukti:** `SubjectController.php:394–407`; `teaching_assignments.subject_id` cascade.

**Dampak:** penugasan mengajar untuk mapel tersebut hilang. Jika mapel dipakai lintas sekolah, dampaknya lebih luas.

#### PS-09 — Rombel (`study_groups`)

**Bukti:** `StudyGroupService.php:15–36` secara eksplisit menghapus `jadwal_kbms`, `student_grade_promotions`, `agenda_attendees`, lalu `$studyGroup->delete()`. `StudyGroupController.php:298–315` memanggil service ini.

**Dampak:** jadwal, riwayat kenaikan, agenda, assignment, absensi, dan data terkait rombel hilang.

#### PS-10 — Bulk delete mapel struktur kurikulum

**Bukti:** `SuperAdmin/CurriculumStructureController.php:212–221` menghapus `GradeLevelSubject` dan `Subject` berdasarkan `code` untuk seluruh sekolah pada jenjang.

**Dampak:** penugasan mengajar lintas sekolah untuk mapel tersebut hilang; perubahan besar dalam satu aksi.

#### PS-24 — Hapus jenis GTK menyebabkan cascade tipe tugas

**Bukti:**

- `MasterDataController::jenisGtkDestroy()` menghapus `JenisGtk` (`app/Http/Controllers/MasterDataController.php:72–78`).
- `additional_task_types.jenis_gtk_id` memakai `cascadeOnDelete()` (`2026_04_04_000005_create_additional_task_types_table.php:18`).
- `gtk_additional_tasks.additional_task_type_id` dan `other_teacher_tasks.additional_task_type_id` memakai `nullOnDelete()`.
- `structural_positions.jenis_gtk_id` memakai `set null`.

**Dampak:** menghapus satu `jenis_gtk` menghapus seluruh `additional_task_types` kategori tersebut; tugas existing kehilangan tipe (null), posisi kehilangan kategori, dan pivot `dormitory_staff_assignment_tasks` untuk tipe tersebut ikut terhapus (cascade). Seeder `JenisGtkSeeder` melakukan ini untuk **semua** kategori.

**Mitigasi:** nonaktifkan (`is_active=false`), jangan hapus; guard dependency; backup.

### 3.4 Cascade matrix ringkas

| Sumber delete | Cascade langsung | Cascade lanjutan |
|---|---|---|
| `structural_positions` | `structural_assignments` | permission snapshot/domain, akses |
| `institution_decrees` | `structural_assignments`, `teaching_assignments`, `homeroom_assignments`, `coordinator_assignments` | assignment/akademik |
| `schools` | `academic_years`, `grade_levels`, `study_groups`, `structural_assignments`, `teaching_assignments`, `homeroom_assignments`, `coordinator_assignments`, `gtk_cross_unit_assignments` | absensi, asrama, nilai, jadwal |
| `academic_years` | `study_groups`, `structural_assignments`, `teaching_assignments`, `homeroom_assignments`, `coordinator_assignments`, banyak tabel asrama | absensi, izin, penghuni |
| `grade_levels` | `study_groups` | assignment/absensi |
| `subjects` | `teaching_assignments` | penugasan mengajar |
| `study_groups` | `teaching_assignments`, `homeroom_assignments`, absensi, jadwal | data akademik |
| `work_units` (force) | `schools`, `gtk_work_unit`, `gtk_cross_unit_assignments` | cascade sekolah |
| `users` (force) | banyak FK `cascadeOnDelete` | assignment, employment, dsb. |
| `roles` | `model_has_roles`, `role_has_permissions` | akses user |

### 3.5 Jalur tidak langsung

| Jalur | Bukti | Dampak |
|---|---|---|
| `PermissionRebuildObserver` | `PermissionRebuildObserver.php:60–70` | Snapshot permission tidak diperbarui untuk assignment |
| `GtkEmploymentObserver` | `GtkEmploymentObserver.php:85–110,126–146` | Role dipilih dari assignment pertama; penghapusan role tidak efektif |
| `StudyGroupObserver` | `app/Observers/StudyGroupObserver.php:53` | Menghapus `AssetRoom` saat study group dihapus |
| `DormitoryRoomObserver` | `app/Observers/DormitoryRoomObserver.php:41` | Menghapus `AssetRoom` saat kamar dihapus |
| `InstitutionDecreeMatrixService` | `app/Services/InstitutionDecreeMatrixService.php:416,624` | Menghapus baris matriks/assignment tertentu |
| `JadwalGeneratorService` | `app/Services/JadwalGeneratorService.php:254,515,530` | Menghapus jadwal lama sebelum regenerate |
| `StudyGroupService` | `app/Services/StudyGroupService.php:15–36` | Menghapus data terkait rombel |
| `GtkFamilyService` | `app/Services/GtkFamilyService.php:102,161,182,287,301` | Menghapus anggota keluarga saat sync (PII) |

Jalur tidak langsung ini harus diuji sebelum menyentuh modul terkait.

## 4. Analisis Operasional dan Status Bukti

### 4.1 Skenario operasional normal yang dapat memicu risiko

| Skenario | Jalur | Kemungkinan | Dampak |
|---|---|---|---|
| Admin sekolah menghapus sekolah | `SchoolController::destroy` / `AdminSchoolController::destroy` | Sedang | Critical |
| Admin akademik menghapus tahun ajaran | `AcademicYearController::destroy` | Sedang | Critical |
| Admin kurikulum menghapus mapel | `SubjectController::destroy` / bulk struktur | Sedang | Critical |
| Admin akademik menghapus tingkat/rombel | `GradeLevelController` / `StudyGroupController` | Sedang | Critical |
| Admin dokumen menghapus SK | `InstitutionDecreeController::destroy` | Sedang | Critical |
| Admin master data menghapus jabatan | `MasterDataController::jabatanDestroy` | Sedang | Critical |
| Super Admin/TU menjalankan `db:seed` | `DatabaseSeeder` | Rendah–Sedang | Critical |
| Deployment pipeline menjalankan seeder | CI/CD/runbook | Rendah–Sedang | Critical |
| Operator force delete/cleanup | Tidak ada route eksplisit, tetapi mungkin via tinker/command | Rendah | Critical |
| Perubahan assignment tanpa rebuild | Observer gap | Tinggi | High |
| Mutasi GTK menghapus baris `gtk_work_unit` | UI/service | Sedang | High |

**Kesimpulan:** risiko tidak hanya teoretis. Beberapa jalur adalah **aktivitas administratif normal** yang tersedia di UI dan hanya dilindungi permission, bukan dependency check.

### 4.2 Pemisahan risiko potensial vs kejadian terbukti

| Kategori | Status | Contoh |
|---|---|---|
| **Terverifikasi di kode (bukan insiden)** | Ya | Delete controller, FK cascade, seeder delete |
| **Terverifikasi di data produksi** | **Belum** | Tidak ada query DB produksi |
| **Terverifikasi sebagai insiden** | **Belum** | Tidak ada bukti data hilang yang diberikan |
| **Potensial terjadi** | Ya | Semua jalur di §3 |
| **Mungkin sudah terjadi tanpa terdeteksi** | Tidak diketahui | Tidak ada audit trail lengkap untuk membuktikan |

**Pernyataan penting:** kita **tidak boleh** menyimpulkan data sudah hilang. Yang dapat disimpulkan adalah **jalur untuk menghilangkan data benar-benar ada**. Verifikasi harus dilakukan read-only di salinan/staging sebelum klaim apa pun.

### 4.3 Faktor yang memperbesar risiko

1. **Cascade berlapis** — satu delete dapat menghapus banyak tabel.
2. **Hard delete pada master data** — tidak ada tombstone.
3. **Seeder destruktif** — tidak ada guard produksi.
4. **Audit tidak lengkap** — sulit mendeteksi kehilangan.
5. **Permission snapshot** — perubahan assignment tidak selalu memicu rebuild.
6. **Data existing tidak bersih** — `parent_id`/`divisi_id`/`domain_id`.
7. **Tidak ada staging representatif** — perubahan berisiko diuji di produksi.
8. **Tidak ada backup/restore drill yang terdokumentasi** — recovery belum terbukti.

## 5. Prioritas dan Urgensi

### 5.1 Priority 1 — Risiko kehilangan data (Immediate/High)

| ID | Temuan | Urgensi | Alasan |
|---|---|---|---|
| PS-01 | Seeder destruktif di `DatabaseSeeder` | **Immediate** | Satu perintah dapat menghapus data/akses |
| PS-02 | `JenisGtkSeeder` + cascade assignment | **Immediate** | Assignment struktural hilang massal |
| PS-03 | Hard delete SK | **High** | Cascade ke 4 tabel assignment |
| PS-04 | Hard delete sekolah | **High** | Cascade ke akademik + assignment |
| PS-05 | Hard delete jabatan | **High** | Cascade ke assignment |
| PS-06 | Hard delete tahun ajaran | **High** | Cascade ke rombel + assignment + asrama |
| PS-07 | Hard delete tingkat kelas | **High** | Cascade ke rombel |
| PS-08 | Hard delete mapel | **High** | Cascade ke penugasan mengajar |
| PS-09 | Hapus rombel | **High** | Menghapus jadwal/riwayat/agenda/assignment |
| PS-10 | Bulk delete mapel | **High** | Dampak lintas sekolah |
| PS-11 | `RoleSeeder` menghapus role | **Immediate** | Akses user hilang |
| PS-12 | `PermissionRoleSeeder` reset permission | **Immediate** | Akses berubah massal |
| PS-13 | `DomainPermissionSeeder` reset domain | **Immediate** | Permission domain hilang |
| PS-14 | `KaldikSeeder` force delete | **High** | Kalender hilang permanen |

### 5.2 Priority 2 — Konsistensi sistem (Planned/High)

| ID | Temuan | Urgensi | Alasan |
|---|---|---|---|
| PS-17 | Observer assignment tidak lengkap | High | Permission basi |
| PS-18 | Sinkronisasi role/employment | High | Akses tidak konsisten |
| PS-19 | `gtk_work_unit` tanpa lifecycle | High | Scope/sync salah |
| PS-20 | `parent_id`/`divisi_id`/`domain_id` | High | Hierarki/domain salah |
| PS-22 | Soft/hard delete tidak konsisten | High | Histori/tombstone |
| PS-15 | `SidebarAccessSeeder` menghapus menu | Medium | Menu/akses UI hilang |
| PS-24 | Hapus jenis GTK (tipe tugas guarded) | High | Klasifikasi posisi + tipe tugas hilang |
| PS-25 | Force delete asrama | High (di luar fokus) | Data asrama hilang |

### 5.3 Priority 3 — Kesiapan integrasi (Planned)

| ID | Temuan | Urgensi | Alasan |
|---|---|---|---|
| PS-21 | `WorkUnit::school()` hasOne | Medium | Response sekolah tidak lengkap |
| PS-23 | Tidak ada change ledger/audit lengkap | High | `changes`/tombstone tidak andal |
| — | API read-only | Planned | Dapat dimulai setelah P1/P2 |
| — | Snapshot/sync | Planned | Butuh data bersih |

### 5.4 Prinsip urutan

- **P0 (hari ini):** freeze, backup, verifikasi read-only. Tidak ada perubahan kode.
- **P1 (segera, setelah approval):** guard delete + archive policy + dependency check.
- **P2 (setelah P1):** observer, data cleanup, lifecycle.
- **P3 (setelah P2):** API/ledger/sync.

**Jangan mengejar P3 sebelum P1/P2 selesai.** Integrasi tidak boleh menjadi alasan untuk mengubah Core.

## 6. Mitigasi Minimal per Temuan

> Semua mitigasi di bawah adalah **usulan**. Tidak ada yang diimplementasikan pada tahap ini.

### 6.1 Prinsip mitigasi

1. **Jangan menghapus atau mengubah FK/cascade existing.**
2. **Jangan menambah soft delete massal** tanpa analisis dampak.
3. **Utamakan guard dan archive** sebelum delete.
4. **Perubahan kode minimal, terisolasi, dan dapat di-rollback.**
5. **Data existing tidak ditulis ulang** kecuali backfill yang disetujui.

### 6.2 Mitigasi per temuan

| ID | Mitigasi operasional (segera, tanpa kode) | Mitigasi kode (future, perlu approval) | Jika ditunda |
|---|---|---|---|
| PS-01 | Larang `db:seed` di produksi; audit pipeline; backup | Guard environment + konfirmasi; pisahkan seeder destruktif dari `DatabaseSeeder` | Risiko data loss tinggi |
| PS-02 | Bekukan `JenisGtkSeeder`; backup posisi/assignment | Ubah seeder menjadi updateOrCreate tanpa delete; atau jadikan command terpisah dengan dry-run | Assignment hilang |
| PS-03 | Larang delete SK; arsipkan via `status`; backup | Guard dependency check; archive action; audit | Assignment/akademik hilang |
| PS-04 | Larang delete sekolah; nonaktifkan `is_active=false` | Guard: tolak delete bila ada tahun ajaran/rombel/assignment; archive | Akademik hilang |
| PS-05 | Larang delete jabatan; nonaktifkan `is_active=false` | Guard: tolak delete bila ada assignment/penempatan | Assignment hilang |
| PS-06 | Larang delete tahun ajaran; gunakan arsip/lock | Guard: tolak delete bila ada rombel/assignment | Akademik/asrama hilang |
| PS-07 | Larang delete tingkat; cek rombel | Guard dependency; archive | Rombel hilang |
| PS-08 | Larang delete mapel; cek teaching assignment | Guard dependency; nonaktifkan mapel | Penugasan hilang |
| PS-09 | Larang delete rombel; cek jadwal/riwayat | Guard dependency; soft delete/arsip | Akademik hilang |
| PS-10 | Batasi bulk delete ke Super Admin; wajib konfirmasi | Guard + audit + dry-run | Dampak lintas sekolah |
| PS-11 | Jangan jalankan `RoleSeeder` di produksi | Pisahkan role sync dari delete; dry-run | Akses user hilang |
| PS-12 | Jangan jalankan `PermissionRoleSeeder` di produksi | Pisahkan mapping awal vs update; dry-run; backup pivot | Akses berubah massal |
| PS-13 | Jangan jalankan `DomainPermissionSeeder` di produksi | Sama; backup `domain_role_permissions` | Permission domain hilang |
| PS-14 | Jangan jalankan `KaldikSeeder` di produksi | Upsert, bukan forceDelete | Kalender hilang |
| PS-15 | Jangan jalankan `SidebarAccessSeeder` di produksi | Upsert tanpa delete | Menu hilang |
| PS-16 | Cek sekolah sebelum hapus unit; gunakan nonaktif | Guard `schools`, `gtk_work_unit`, `dormitories` | Unit orphan |
| PS-17 | Jangan mengandalkan permission Core untuk Connect | Perbaiki observer + test | Permission basi |
| PS-18 | Jangan andalkan `syncRoles` otomatis | Perbaiki pemilihan assignment/role + test | Akses salah |
| PS-19 | Dokumentasikan “baris ada = aktif” | Additive lifecycle/ledger (perlu approval) | Scope salah |
| PS-20 | Audit read-only; jangan backfill tanpa approval | Perbaiki fillable/seeder; backfill terkontrol | Hierarki/domain salah |
| PS-21 | Gunakan array `schools` di API | Jangan ubah `WorkUnit::school()` existing | Data sekolah hilang |
| PS-22 | Jangan hard delete; gunakan arsip | Ledger/soft delete selektif | Histori hilang |
| PS-23 | Sementara rekonsiliasi penuh | `organization_change_log` + audit | Tidak ada tombstone |
| PS-24 | Jangan hapus tipe/jenis; nonaktifkan | Guard/arsip | Klasifikasi hilang |
| PS-25 | Jangan force delete asrama; audit terpisah | Guard dependency + archive | Data asrama hilang |

### 6.3 Mitigasi minimal yang direkomendasikan segera

**Tanpa perubahan kode:**

1. **Freeze operasi destruktif** melalui runbook dan komunikasi ke admin:
   - jangan hapus sekolah, SK, jabatan, tahun ajaran, tingkat, mapel, rombel;
   - jangan jalankan `db:seed`;
   - jangan force delete.
2. **Backup penuh + verifikasi restore** sebelum perubahan apa pun.
3. **Batasi permission delete** ke Super Admin/supervisor, bukan admin harian.
4. **Catat setiap delete manual** (ticket/change log) untuk audit sementara.
5. **Pantau log** untuk `destroy`/delete error.

**Perubahan kode minimal (setelah approval):**

1. Tambahkan guard dependency pada method `destroy` sebelum `delete()`.
2. Ubah delete menjadi archive/nonaktif jika ada dependensi.
3. Tambahkan audit event sebelum delete.
4. Tambahkan konfirmasi eksplisit untuk bulk delete.

**Jangan:**

- Mengubah FK/cascade existing.
- Menambah soft delete ke tabel existing tanpa migration/backfill yang aman.
- Menjalankan seeder untuk “memperbaiki” data.

## 7. Strategi Perlindungan Data, Backup, Pemulihan, dan Verifikasi

### 7.1 Prinsip

- **Backup belum sah sampai restore diuji.**
- **Satu backup bukan backup.** Minimal dua salinan, satu offsite.
- **Jangan mengandalkan migration rollback** untuk memulihkan data.
- **Jangan menjalankan operasi destruktif tanpa backup terverifikasi.**
- **RPO/RTO harus disetujui pemilik sistem**, bukan ditentukan sepihak.

### 7.2 Cakupan backup minimum

| Kategori | Tabel/objek |
|---|---|
| Organisasi | `work_units`, `divisis`, `domains`, `domain_role_permissions` |
| Sekolah/akademik | `schools`, `academic_years`, `grade_levels`, `study_groups`, `subjects`, `subject_groups` |
| GTK | `users`, `gtk_profiles`, `gtk_contacts`, `gtk_employments`, `gtk_work_unit`, `gtk_work_unit_histories` |
| Assignment | `structural_assignments`, `teaching_assignments`, `homeroom_assignments`, `coordinator_assignments`, `other_teacher_tasks`, `gtk_additional_tasks`, `gtk_cross_unit_assignments` |
| SK | `institution_decrees` |
| Permission | `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `permission_snapshots` |
| Audit | `audit_logs`, `activity_log` |
| Master pendukung | `jenis_gtk`, `additional_task_types`, `institution_decrees` |
| File | dokumen SK, logo/kop sekolah, tanda tangan/stempel, foto (sesuai kebijakan retensi) |

### 7.3 Jenis backup

| Jenis | Frekuensi usulan | Retensi | Catatan |
|---|---|---|---|
| Logical full backup (`mysqldump --single-transaction`) | Harian | 30 hari | Konsisten untuk InnoDB; uji restore |
| Physical backup (XtraBackup/snapshot) | Harian/mingguan | 30–90 hari | Untuk database besar; butuh prosedur |
| Binary log / PITR | Berkelanjutan | 7–30 hari | Memungkinkan recovery titik waktu |
| Pre-change backup | Sebelum setiap perubahan berisiko | Sampai perubahan stabil | Wajib |
| Offsite copy | Harian | 30–90 hari | Terenkripsi, akses terbatas |
| File storage backup | Harian/mingguan | Sesuai kebijakan | SK, dokumen, gambar |

### 7.4 Prosedur backup sebelum perubahan

1. Identifikasi tabel/objek terdampak.
2. Pastikan tidak ada operasi tulis besar berjalan.
3. Jalankan backup penuh + backup tabel terdampak.
4. Hitung checksum file backup.
5. Simpan metadata: waktu, ukuran, versi aplikasi, commit hash, operator.
6. Restore ke lingkungan terisolasi dan jalankan smoke test.
7. Baru boleh meminta persetujuan perubahan.

### 7.5 Verifikasi restore

Minimal:

- Jumlah baris tabel kunci sama dengan sumber.
- Checksum tabel/backup sama.
- FK tidak rusak.
- Login Super Admin berhasil.
- Halaman unit/sekolah/GTK/assignment dapat dibuka.
- Permission snapshot dapat dibangun ulang.
- File SK/logo dapat diakses.

**Jangan menguji restore di database produksi.** Gunakan staging/isolated instance.

### 7.6 Verifikasi read-only sebelum perbaikan

Query berikut hanya contoh; jalankan di salinan/staging, bukan produksi tanpa persetujuan.

```sql
-- Unit tanpa induk
SELECT COUNT(*) FROM work_units WHERE parent_id IS NULL AND deleted_at IS NULL;

-- Unit tanpa divisi
SELECT COUNT(*) FROM work_units WHERE divisi_id IS NULL AND deleted_at IS NULL;

-- Posisi tanpa domain
SELECT COUNT(*) FROM structural_positions WHERE domain_id IS NULL AND is_active = 1;

-- Assignment tanpa posisi valid
SELECT COUNT(*) FROM structural_assignments sa
LEFT JOIN structural_positions sp ON sp.id = sa.position_id
WHERE sp.id IS NULL AND sa.deleted_at IS NULL;

-- Penempatan tanpa posisi
SELECT COUNT(*) FROM gtk_work_unit WHERE position_id IS NULL;

-- User dengan lebih dari satu is_primary
SELECT user_id, COUNT(*) FROM gtk_work_unit
WHERE is_primary = 1 GROUP BY user_id HAVING COUNT(*) > 1;

-- Unit dengan lebih dari satu sekolah
SELECT work_unit_id, COUNT(*) FROM schools
GROUP BY work_unit_id HAVING COUNT(*) > 1;

-- SK dengan assignment terkait (risiko delete)
SELECT d.id, d.decree_number, COUNT(sa.id) AS assignments
FROM institution_decrees d
LEFT JOIN structural_assignments sa ON sa.decree_id = d.id AND sa.deleted_at IS NULL
GROUP BY d.id, d.decree_number
HAVING assignments > 0
ORDER BY assignments DESC;

-- Assignment efektif tetapi posisi nonaktif
SELECT COUNT(*) FROM structural_assignments sa
JOIN structural_positions sp ON sp.id = sa.position_id
WHERE sa.status = 'active' AND sp.is_active = 0 AND sa.deleted_at IS NULL;
```

Catatan:

- Jangan menjalankan query agregat berat pada jam sibuk.
- Jika tabel besar, gunakan sampling/read replica.
- Hasil query adalah bukti awal, bukan keputusan otomatis untuk backfill.

### 7.7 Pemulihan (recovery)

| Skenario | Tindakan |
|---|---|
| Baris terhapus karena hard delete | Restore tabel/tabel terkait dari backup; jangan re-run seeder |
| FK/cascade menghapus assignment | Restore `structural_assignments` dan tabel turunannya; rekonsiliasi ke snapshot |
| Role/permission berubah | Restore tabel permission dari backup; rebuild cache/snapshot |
| Kalender/menu hilang | Restore dari backup; jangan re-run seeder |
| Data tidak konsisten tanpa kehilangan | Perbaiki via backfill terkontrol di staging, lalu produksi dengan approval |

**Prinsip:** restore harus selektif jika memungkinkan; jangan menimpa seluruh database produksi tanpa persetujuan.

### 7.8 RPO/RTO

Usulan awal (perlu persetujuan):

- **RPO:** ≤ 15 menit untuk data transaksional; ≤ 24 jam untuk master data.
- **RTO:** ≤ 4 jam untuk operasional kritis; ≤ 24 jam untuk pemulihan penuh.

Angka final harus ditetapkan pemilik sistem berdasarkan kapasitas dan risiko.

### 7.9 Insiden data

Jika dicurigai kehilangan data:

1. **Hentikan** operasi yang berpotensi menghapus.
2. Catat waktu, operator, aksi, dan tabel yang terdampak.
3. Jangan menjalankan seeder/migration/rollback.
4. Amankan log (`laravel.log`, web server, audit).
5. Identifikasi backup terakhir sebelum insiden.
6. Restore ke staging, verifikasi, lalu produksi dengan persetujuan.
7. Lakukan post-mortem dan perbaiki guard.

## 8. Urutan Pekerjaan yang Aman

### 8.1 Fase 0 — Freeze, Backup, Verifikasi (sekarang, tanpa perubahan kode)

**Tujuan:** menghentikan jalur destruktif dan memastikan recovery tersedia.

| Langkah | Detail | Output |
|---|---|---|
| 0.1 | Umumkan freeze: larang `db:seed`, delete master, force delete | Bukti komunikasi |
| 0.2 | Backup penuh + tabel kunci + file | File backup + checksum |
| 0.3 | Uji restore di staging terisolasi | Laporan restore |
| 0.4 | Jalankan query verifikasi read-only | Laporan data |
| 0.5 | Inventarisasi permission admin delete | Daftar user/role berisiko |
| 0.6 | Siapkan runbook incident | Dokumen |

**Gate:** backup terverifikasi + data report + persetujuan untuk lanjut.

### 8.2 Fase 1 — Guard jalur delete (P1)

**Temuan:** PS-01..PS-15, PS-07..PS-10.

**Urutan:**

1. **Seeder** (PS-01, PS-02, PS-11..PS-15)
   - Pisahkan seeder destruktif dari `DatabaseSeeder` (perubahan kode, approval).
   - Tambahkan guard environment/konfirmasi.
   - Jangan mengubah data.
2. **SK** (PS-03)
   - Guard: hitung assignment terkait; tolak delete; arahkan archive.
3. **Sekolah** (PS-04)
   - Guard: tahun ajaran/rombel/assignment; tolak delete; arahkan nonaktif.
4. **Jabatan** (PS-05)
   - Guard: assignment/penempatan; tolak delete; arahkan nonaktif.
5. **Tahun ajaran** (PS-06)
   - Guard: rombel/assignment; gunakan lock/arsip.
6. **Tingkat/mapel/rombel** (PS-07..PS-10)
   - Guard dependency + konfirmasi + audit.
7. **Bulk delete** (PS-10)
   - Dry-run, jumlah terdampak, konfirmasi, audit.

**Prinsip:** guard **sebelum** `delete()`, bukan sesudah. Tidak ada perubahan FK.

**Test:** feature test delete guard, role/permission, audit, regression modul terkait.

**Rollback:** feature flag per guard; perubahan kode dapat di-revert tanpa data loss.

### 8.3 Fase 2 — Konsistensi assignment dan data (P2)

**Temuan:** PS-16..PS-20, PS-24, PS-25.

1. **Observer assignment** (PS-17)
   - Tambah cabang `StructuralAssignment`/`HomeroomAssignment`/`CoordinatorAssignment`.
   - Daftarkan `GtkWorkUnit`/`OtherTeacherTask` bila relevan.
   - Test: assignment dibuat/diakhiri → snapshot berubah.
2. **Role/employment** (PS-18)
   - Perbaiki pemilihan assignment dan pencabutan role.
   - Test multi-assignment, end date, inactive.
3. **`gtk_work_unit` lifecycle** (PS-19)
   - Putuskan: dokumentasi semantik atau additive lifecycle.
   - Jangan ubah tabel tanpa approval.
4. **Data quality** (PS-20)
   - Backfill `parent_id`/`divisi_id`/`domain_id` di staging, lalu produksi dengan approval.
   - Jangan re-run seeder.
5. **Tipe tugas/jenis GTK** (PS-24)
   - Nonaktifkan, bukan hapus.
6. **Asrama** (PS-25)
   - Audit terpisah; guard force delete.

### 8.4 Fase 3 — Integrasi read-only (P3)

1. API master + snapshot.
2. Assignment + polling.
3. Change ledger + `changes` + tombstone.
4. Audit/history.

**Prasyarat:** Fase 1–2 selesai, data terverifikasi, kontrak disetujui, staging/load test.

### 8.5 Fase 4 — Audit dan observability

- Audit event untuk entity organisasi.
- Dashboard drift/rekonsiliasi.
- Runbook on-call.

### 8.6 Matriks fase vs temuan

| Fase | Temuan |
|---|---|
| 0 | Semua (freeze/backup/verifikasi) |
| 1 | PS-01..PS-15 |
| 2 | PS-16..PS-20, PS-24, PS-25 |
| 3 | PS-21, PS-23, API/ledger |
| 4 | Audit/monitoring |

### 8.7 Gate persetujuan

| Gate | Yang disetujui | Bukan yang disetujui |
|---|---|---|
| G0 | Freeze/backup/verifikasi | Perubahan kode |
| G1 | Guard delete | Schema/FK |
| G2 | Perbaikan observer/data | API |
| G3 | API read-only | Write/Connect |
| G4 | Ledger + changes | Deployment produksi |

**Persetujuan desain tidak otomatis menjadi persetujuan implementasi atau deployment.**

## 9. Guardrails dan Larangan

### 9.1 Larangan mutlak

1. Jangan menghapus, mengubah, atau menulis ulang data existing tanpa persetujuan.
2. Jangan menjalankan seeder yang berpotensi destruktif.
3. Jangan menjalankan migration/rollback di produksi.
4. Jangan mengubah/menghapus FK atau cascade existing.
5. Jangan mengubah tipe, nullable, default, atau constraint kolom existing.
6. Jangan mengganti primary key/identifier existing.
7. Jangan refactor modul existing.
8. Jangan mengubah assignment, RBAC, permission, atau autentikasi existing.
9. Jangan deploy ke produksi tanpa persetujuan terpisah.
10. Jangan menjadikan kebutuhan ALIM Connect sebagai alasan mengubah struktur Core.

### 9.2 Guardrail operasional

| Guardrail | Detail |
|---|---|
| Freeze seeder | Larang `db:seed`, `migrate:fresh`, `migrate:refresh`, `db:wipe` |
| Freeze delete | Larang delete master/akademik sampai guard tersedia |
| Backup first | Setiap perubahan berisiko wajib backup + restore test |
| Least privilege | Cabut permission delete dari admin harian |
| Change ticket | Setiap delete manual dicatat |
| Audit | Log akses dan perubahan |
| Staging | Uji di salinan produksi |
| Canary | Perubahan kecil, bertahap, monitor |
| Rollback | Rencana rollback yang tidak menghapus data baru |

### 9.3 Guardrail untuk ALIM Connect

- Hanya read-only.
- API key terpisah.
- Tidak ada akses DB langsung.
- Tidak ada write ke master Core.
- Kegagalan Connect tidak boleh memblokir Core.
- Jangan mengubah API POS/Recruitment.
- Jangan memakai seeder/migration untuk “menyesuaikan” data Connect.

### 9.4 Definisi “selesai” yang salah

Pekerjaan **tidak** dianggap selesai hanya karena:

- kode berhasil dibuat;
- test plan ditulis;
- migration berhasil di staging;
- dokumen disetujui.

Pekerjaan dianggap selesai hanya setelah:

- test dijalankan dan lulus;
- backup/restore terbukti;
- dampak produksi dianalisis;
- persetujuan deployment diberikan;
- monitoring pasca-deploy menunjukkan hasil stabil.

## 10. Daftar Pekerjaan

### 10.1 Harus diprioritaskan untuk melindungi data produksi

1. Freeze `db:seed`/seeder destruktif dan operasi delete master.
2. Backup penuh + uji restore.
3. Guard delete SK, sekolah, jabatan, tahun ajaran, tingkat, mapel, rombel.
4. Pisahkan/guard seeder destruktif dari `DatabaseSeeder`.
5. Audit permission delete (siapa yang bisa menghapus).
6. Verifikasi data `parent_id`/`divisi_id`/`domain_id`/`position_id`.
7. Perbaiki `PermissionRebuildObserver` agar assignment memicu rebuild.
8. Tambahkan audit event sebelum delete.
9. Uji regresi modul terkait.

### 10.2 Dapat ditunda tanpa mengganggu operasional

1. Perbaikan kosmetik/penamaan.
2. Refactor service/controller.
3. Index tambahan yang belum terbukti dibutuhkan.
4. Perluasan API ke modul akademik detail.
5. Perubahan UI non-kritis.
6. Dokumentasi lanjutan (setelah indeks dasar).
7. Optimasi performa yang belum menjadi bottleneck.
8. Soft delete massal (jangan dilakukan tanpa analisis).

### 10.3 Dibutuhkan untuk integrasi ALIM Connect

1. API read-only master + snapshot.
2. Endpoint assignment + tugas tambahan.
3. Change ledger + `changes` + tombstone.
4. Autentikasi API key Connect + rate limit + audit.
5. Data bersih dan konsisten.
6. Kontrak/versioning/schema test.
7. Staging dan load test.

### 10.4 Membutuhkan keputusan dan persetujuan

1. Apakah `db:seed` dilarang permanen di produksi?
2. Apakah delete master diganti archive/nonaktif?
3. Apakah soft delete selektif ditambahkan?
4. Apakah change ledger dibuat?
5. Kapan akses dicabut saat assignment berakhir?
6. PII mana yang boleh diekspos ke Connect?
7. Retensi audit/backup.
8. RPO/RTO.
9. Siapa PIC backfill data.
10. Jadwal perbaikan observer/guard.
11. Apakah API Connect boleh masuk setelah P1/P2.
12. Apakah deployment dilakukan setelah staging/load test.

## 11. Kesimpulan dan Rekomendasi

### 11.1 Status

**ALIM Core belum dapat dinyatakan aman dari risiko kehilangan data** hanya berdasarkan kode saat ini. Jalur destruktif terverifikasi ada pada seeder, controller, dan cascade FK. Namun **tidak ada klaim** bahwa data produksi sudah rusak; verifikasi data belum dilakukan.

### 11.2 Rekomendasi

**Go** untuk:

- Fase 0: freeze, backup, verifikasi read-only.
- Penyusunan guard/desain perbaikan.

**No-Go** untuk:

- Menjalankan seeder/migration/rollback di produksi.
- Implementasi API Connect sebelum P1/P2.
- Perubahan FK/cascade/assignment/permission existing.
- Deployment tanpa staging, backup, dan persetujuan.

### 11.3 Pernyataan penutup

Prioritas utama bukan menyelesaikan seluruh temuan sekaligus, melainkan:

1. menghentikan jalur destruktif;
2. memastikan backup dapat dipulihkan;
3. menutup cascade delete berbahaya;
4. memperbaiki konsistensi assignment/permission;
5. baru membangun integrasi read-only.

**Stabilitas ALIM Core dan keselamatan data produksi lebih penting daripada kecepatan pengembangan ALIM Connect.**

---

## Lampiran A. Indeks Bukti

| Topik | File/lokasi |
|---|---|
| Seeder destruktif | `database/seeders/JenisGtkSeeder.php:17`; `DatabaseSeeder.php:17–62` |
| Role/permission seed | `RoleSeeder.php:74,82`; `PermissionRoleSeeder.php:16`; `DomainPermissionSeeder.php:28`; `SuperAdminDomainPermissionSeeder.php:44` |
| Kalender | `KaldikSeeder.php:65–70` |
| SK delete | `InstitutionDecreeController.php:827–837` |
| Sekolah delete | `SchoolController.php:492–511`; `SuperAdmin/AdminSchoolController.php:188–195` |
| Jabatan delete | `MasterDataController.php:155–160` |
| Tahun ajaran delete | `AcademicYearController.php:126–133` |
| Tingkat delete | `GradeLevelController.php:449–462` |
| Mapel delete | `SubjectController.php:394–407`; `SuperAdmin/CurriculumStructureController.php:212–221` |
| Rombel delete | `StudyGroupService.php:15–36`; `StudyGroupController.php:298–315` |
| Cascade assignment | `2026_02_14_135020_create_structural_assignments_table.php:19–33` |
| Cascade SK | `2026_02_14_135142...:15–17`; `...135143...:15–17`; `...135144...:15–17` |
| Cascade sekolah/akademik | `2026_02_14_094120...:13`; `2026_02_14_094121...:13`; `2026_02_14_094123...:13–15` |
| Soft delete | Model `WorkUnit`, `Divisi`, `Domain`, `StructuralAssignment`, `User`; model `School`, `StructuralPosition`, `GtkWorkUnit`, `GtkEmployment`, `GtkAdditionalTask`, `OtherTeacherTask` |
| Observer gap | `PermissionRebuildObserver.php:60–70` |
| Role/employment | `GtkEmploymentObserver.php:85–110,126–146` |
| `gtk_work_unit` | `2026_02_10_010955_create_gtk_work_unit_table.php:11–21` |
| Parent/divisi/domain | `2026_02_10_010813...:16`; `2026_04_25_000003...`; `2026_09_18_000001...`; `2026_09_16_000003...`; `StructuralPosition.php:14–25`; `JenisGtkSeeder.php:374–386` |
| Unit-sekolah | `WorkUnit.php:128–131,248–251`; `2026_02_14_094120...:13–15` |
| Permission tables | `2026_02_10_010757_create_permission_tables.php:48,67,83,88` |

## Lampiran B. Checklist Backup Sebelum Perubahan

- [ ] Identifikasi tabel/objek terdampak.
- [ ] Backup penuh database.
- [ ] Backup tabel kunci.
- [ ] Backup file storage terkait.
- [ ] Checksum backup.
- [ ] Simpan metadata (waktu, operator, versi, commit).
- [ ] Restore ke staging terisolasi.
- [ ] Smoke test login/unit/sekolah/GTK/assignment.
- [ ] Catat RPO/RTO.
- [ ] Dapatkan persetujuan perubahan.

## Lampiran C. Checklist Verifikasi Read-Only

- [ ] `work_units.parent_id` null.
- [ ] `work_units.divisi_id` null.
- [ ] `structural_positions.domain_id` null.
- [ ] `structural_assignments` tanpa posisi valid.
- [ ] `gtk_work_unit.position_id` null.
- [ ] `is_primary` ganda per user.
- [ ] Unit dengan lebih dari satu sekolah.
- [ ] SK dengan assignment terkait.
- [ ] Assignment aktif dengan posisi nonaktif.
- [ ] User dengan banyak employment/status terminal.
- [ ] Role/permission yang dipakai user.
- [ ] Backup terakhir dan status restore.

## Lampiran D. Template Permintaan Persetujuan

```text
Ruang lingkup:
Tabel/modul terdampak:
Jenis perubahan (kode/migration/data/config):
Risiko data:
Backup:
Uji yang dilakukan:
Rollback:
Dampak ke POS/Recruitment/Connect:
Rencana monitoring:
PIC:
Tanggal target:
Persetujuan:
```
