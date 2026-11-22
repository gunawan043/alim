---
name: alim-core-permission-and-placement-lifecycle-design
description: Desain invalidasi permission snapshot dan lifecycle penempatan GTK di ALIM Core
metadata:
  type: design
  design-date: 2026-11-21
  status: revised-proposal
  revision: 2
  scope: design-only
  depends-on:
    - docs/integration/alim-core-production-safety-plan.md
    - docs/integration/alim-connect-engineering-review.md
---

# Desain Permission Rebuild & Lifecycle Penempatan GTK

> **Desain saja.** Tidak ada perubahan kode, migration, database, seeder, endpoint, konfigurasi, atau deployment pada tahap ini.
>
> **Ruang lingkup disetujui:** analisis perilaku existing, inventaris assignment, desain invalidasi/rebuild, desain lifecycle `gtk_work_unit`, kompatibilitas, strategi migrasi aman, test/rollback.
>
> **Di luar ruang lingkup:** implementasi, migration, backfill produksi, deployment.

## 1. Ringkasan dan Rekomendasi

### 1.1 Masalah utama

1. **Permission snapshot bisa basi.** `PermissionRebuildObserver` mendaftarkan `StructuralAssignment`, `HomeroomAssignment`, dan `CoordinatorAssignment`, tetapi `match(true)` tidak memiliki cabang untuk ketiganya. `GtkWorkUnit`, `OtherTeacherTask`, `Role`, dan pivot permission juga tidak ditangani.
2. **Rebuild tidak selalu dapat merekonstruksi scope.** `BuildSnapshotJob` hanya memiliki `scope_school_id` dan menebak academic year dari `$user->active_academic_year_id`/config; `scope_key` adalah hash yang tidak dapat dibalik.
3. **Invalidasi cache tidak cukup.** Menghapus cache tanpa menandai snapshot DB dapat membuat resolver memuat snapshot lama sampai TTL (3600 detik).
4. **`gtk_work_unit` tidak memiliki status/masa berlaku.** Baris hilang adalah satu-satunya sinyal berakhir; tidak ada lifecycle formal.

### 1.2 Rekomendasi

- **Permission:** pusatkan invalidasi pada satu service, tandai snapshot `stale_at`, hapus cache, rebuild per user/scope dengan metadata scope yang tersimpan. Tambahkan kolom nullable pada `permission_snapshots` (`scope_academic_year_id`, `scope_role_dimension`, `scope_tenant`) dan `stale_at`.
- **Assignment inventory:** daftarkan seluruh model yang memengaruhi provider, termasuk `DormitoryStaffAssignment`, `OtherTeacherTask`, `Role`, `Permission`, `Subject`, `SubjectGroup`, `StructuralPosition`, `Domain`, dan `GtkWorkUnit`.
- **Penempatan GTK:** pertahankan `gtk_work_unit` sebagai **current projection**; tambahkan kolom lifecycle (`status`, `start_date`, `end_date`) dan tabel baru `gtk_work_unit_periods` untuk histori periode. Jangan mengubah unique `(user_id, work_unit_id)`.
- **Migrasi:** additive, nullable, backfill bertahap, tanpa delete/DROP/FK change; rollback = nonaktifkan fitur, bukan drop data.

### 1.3 Keputusan pemilik sistem yang sudah disetujui

| ID | Keputusan | Status |
|---|---|---|
| D-006 | Opsi B+: baris utama + unique `(user_id, work_unit_id)` dipertahankan; histori di `gtk_work_unit_periods`; baris `ended` tetap disimpan | **Disetujui** |
| D-007 | FK `gtk_work_unit_periods` ke GTK/WorkUnit memakai **RESTRICT** | **Disetujui** |
| D-008 | Metadata snapshot `stale_at`, `scope_academic_year_id`, `scope_role_dimension`, `scope_tenant` | **Disetujui secara prinsip** |
| D-009 | Invalidasi berbasis cakupan dampak; global hanya untuk perubahan global | **Disetujui** |
| D-010 | PIC backup/restore tetap TBD; tidak diisi berdasarkan asumsi | **Disetujui** |
| D-011 | Jadwal staging/backfill belum disetujui | **Belum** |
| D-012 | Persetujuan desain bukan persetujuan implementasi | **Ditegaskan** |

**Batasan:**

- `scope_key` existing **tidak boleh berubah format/identitas**.
- Snapshot existing **tidak boleh dihapus/diubah identitasnya**.
- Perubahan pembaca `gtk_work_unit` belum boleh dilakukan sebelum audit final dan strategi transisi disetujui.
- Metadata snapshot tidak boleh menjadi ketergantungan keras untuk data lama.
- Rebuild gagal tidak boleh memberikan kewenangan dari snapshot stale.

**Matriks dampak:** lihat `docs/integration/alim-core-gtk-work-unit-impact-matrix.md`.
**Audit final file-per-file:** lihat `docs/integration/alim-core-gtk-work-unit-final-audit.md` (status: belum menjadi izin implementasi).

## 2. Arsitektur Permission Saat Ini

### 2.1 Alur resolusi

```text
Request
  → OrganizationContext (schoolId, academicYearId, roleDimension, tenant)
    → SnapshotResolver::resolve(user, context)
      → cache.get(userId, scopeKey)
        → jika miss: repository.findByScopeKey(scopeKey, userId)
          → jika expired/tidak ada: SnapshotRebuildService::rebuild()
            → EffectivePermissionBuilder::build()
              → semua PermissionProvider
            → simpan PermissionSnapshot + cache
      → AuthorizationManager::allows() membaca PermissionBag
```

Bukti:

- `SnapshotResolver::resolve()` — `app/Authorization/Services/SnapshotResolver.php:32`.
- `EffectivePermissionBuilder::build()` — `app/Authorization/Support/EffectivePermissionBuilder.php:28`.
- `SnapshotRebuildService::rebuild()` — `app/Authorization/Services/SnapshotRebuildService.php`.
- `permission_snapshots` — `database/migrations/2026_06_29_000021_create_authorization_permission_snapshots_table.php`.

### 2.2 Provider dan sumber assignment

Provider didaftarkan otomatis dari direktori `app/Authorization/Providers` (`AuthorizationServiceProvider:77–100`). Yang membaca assignment/employment/role:

| Provider | Sumber data | Assignment yang memengaruhi |
|---|---|---|
| `GtkPermissionProvider` | `gtk_employments`, `gtk_additional_tasks`, `gtk_transfer_requests`, role level | Employment, tugas tambahan, transfer |
| `StudentPermissionProvider` | `teaching_assignments` | Guru mapel/rombel |
| `SubjectGroupPermissionProvider` | `SubjectGroupResolver` → `teaching_assignments`, `subjects`, `other_teacher_tasks`, `gtk_additional_tasks` | Guru mapel, rumpun, koordinator |
| `WorkspacePermissionProvider` | `WorkspaceActivationService` → `homeroom_assignments`, `coordinator_assignments`, `structural_assignments` | Wali kelas, koordinator, struktural |
| `StafPerizinanPermissionProvider` | `dormitory_staff_assignments` | Staf perizinan asrama |
| `RolePermissionProvider` | `roles`, `permissions`, pivot | Role/permission langsung |
| `DormitoryPermissionProvider` | role level | Role asrama |
| Provider lain (Academic, Finance, dll.) | role level, employment | Tidak langsung assignment spesifik |

### 2.3 Perilaku observer saat ini

**Bukti:** `app/Authorization/Support/PermissionRebuildObserver.php:58–78`; registrasi `AuthorizationRuntimeServiceProvider.php:143–146`.

| Model | Terdaftar? | Ditangani `match`? | Akibat |
|---|---|---|---|
| `User` | Ya | Ya | Rebuild user |
| `Permission` | Ya | Ya (tetapi `extractUserIdsFromModel` return `[]`) | **Tidak ada user yang di-dispatch** |
| `GTKEmployment` | Ya | Ya | Rebuild via `user_id` |
| `RevokedPermission` | Ya | Ya | Rebuild via `user_id` |
| `TeachingAssignment` | Ya | Ya | Rebuild teacher lama/baru |
| `GtkAdditionalTask` | Ya | Ya | Rebuild user lama/baru |
| `Subject` | Ya | Ya | Rebuild guru mapel terkait |
| `SubjectGroup` | Ya | Ya | Rebuild guru/koordinator rumpun |
| `StructuralAssignment` | Ya | **Tidak** (`default => []`) | **Tidak rebuild** |
| `HomeroomAssignment` | Ya | **Tidak** | **Tidak rebuild** |
| `CoordinatorAssignment` | Ya | **Tidak** | **Tidak rebuild** |
| `OtherTeacherTask` | **Tidak** | Tidak | **Tidak rebuild** |
| `DormitoryStaffAssignment` | Tidak di observer ini | Ditangani observer terpisah | Rebuild user |
| `GtkWorkUnit` | Tidak | Tidak | Tidak rebuild/invalidasi |
| `Role` | Tidak | Tidak | Tidak rebuild |
| `StructuralPosition` / `Domain` | Tidak | Tidak | Tidak rebuild |

### 2.4 Keterbatasan `BuildSnapshotJob`

**Bukti:** `app/Authorization/Jobs/BuildSnapshotJob.php`.

1. Hanya mengambil snapshot `is_current = true` yang sudah ada; jika user belum punya snapshot untuk scope baru, tidak ada rebuild.
2. Hanya memproses `scope_school_id` non-null; scope tanpa sekolah dilewati.
3. Academic year ditebak dari `$user->active_academic_year_id` (bukan atribut model yang jelas) atau config default — **bukan** dari scope asli.
4. `scope_key` adalah hash; tidak dapat dipakai untuk merekonstruksi school/year/role/tenant.
5. Tidak memanggil `cache->forgetUser()` sebelum rebuild; cache untuk scope yang tidak direbuild tetap ada.
6. Tidak ada deduplikasi job per user/scope; beberapa event dapat mengirim job berulang.
7. Kegagalan job hanya log; tidak menandai snapshot stale, sehingga request berikutnya dapat memuat snapshot lama.

### 2.5 Konsekuensi

- Perubahan assignment struktural/wali/koordinator dapat tidak tercermin sampai TTL 3600 detik atau event lain.
- Perubahan role/permission via seeder/pivot tidak memicu rebuild.
- Perubahan `gtk_work_unit` dapat mengubah sekolah efektif user, tetapi snapshot lama untuk sekolah lama tetap dipakai sampai TTL.
- `Permission` berubah tidak me-rebuild user mana pun.

## 3. Inventaris Assignment yang Memengaruhi Permission

### 3.1 Matriks lengkap

| Model/tabel | Perubahan yang relevan | Provider/efek | User ID yang terdampak | Scope yang mungkin terpengaruh | Penanganan saat ini | Usulan |
|---|---|---|---|---|---|---|
| `gtk_employments` | create/update/delete, `status_kepegawaian`, `jabatan_id`, `school_id`, `academic_year_id` | `GtkPermissionProvider`, provider role/employment lain | `user_id` (lama & baru) | Semua scope user | Observer terdaftar | Pertahankan; tambah old/new user |
| `gtk_additional_tasks` | create/update/delete, `tst` | `GtkPermissionProvider`, `SubjectGroupResolver` | `user_id` (lama & baru) | Semua scope user | Observer terdaftar | Pertahankan |
| `gtk_transfer_requests` | status approved, target unit | `GtkPermissionProvider` | `user_id` | Scope unit/sekolah tujuan | **Tidak** | Tambah observer/service |
| `teaching_assignments` | create/update/delete, `teacher_id`, `subject_id`, `study_group_id`, `status` | `StudentPermissionProvider`, `SubjectGroupPermissionProvider` | `teacher_id` lama/baru | Scope sekolah/TA | Observer terdaftar | Pertahankan; sertakan subject lama/baru |
| `subjects` | `subject_group_id`, `name`, `is_active` | `SubjectGroupPermissionProvider` via resolver | Guru yang mengampu mapel | Scope sekolah/TA | Observer terdaftar | Pertahankan |
| `subject_groups` | perubahan definisi/koordinator | `SubjectGroupPermissionProvider` | Guru/koordinator rumpun | Scope sekolah/TA | Observer terdaftar | Pertahankan |
| `homeroom_assignments` | create/update/delete, status/tanggal | `WorkspaceActivationService` | `teacher_id` lama/baru | Scope sekolah/TA | Terdaftar tetapi **tidak ditangani** | Tambah cabang |
| `coordinator_assignments` | create/update/delete, status/tanggal | `WorkspaceActivationService` | `teacher_id` lama/baru | Scope sekolah/TA | Terdaftar tetapi **tidak ditangani** | Tambah cabang |
| `structural_assignments` | create/update/delete/end, `user_id`, `position_id`, `school_id`, `academic_year_id`, `status` | `WorkspaceActivationService`, domain/structural | `user_id` lama/baru | Scope sekolah/TA | Terdaftar tetapi **tidak ditangani** | Tambah cabang |
| `dormitory_staff_assignments` | create/update/delete, status/tanggal | `StafPerizinanPermissionProvider` | `user_id` | Scope asrama/sekolah | Observer terpisah | Satukan/pertahankan, hindari dobel |
| `other_teacher_tasks` | create/update/delete, `teacher_id`, `task_name`, `is_active` | `SubjectGroupResolver` | `teacher_id` lama/baru | Scope sekolah/TA | **Tidak** | Tambah observer/service |
| `gtk_work_unit` | create/update/delete, `is_primary`, `position_id`, `work_unit_id` | Tidak langsung provider; memengaruhi sekolah efektif/scope | `user_id` | Scope sekolah lama/baru | **Tidak** | Invalidate cache/scope user |
| `structural_positions` | `domain_id`, `role_id`, `is_active`, `hierarchy_level` | `WorkspaceActivationService`, domain | User dengan assignment/penempatan posisi | Scope terkait | **Tidak** | Invalidate user terkait |
| `domains` | `is_active`, `code`, mapping permission | Domain permission/role | User dengan assignment domain | Scope terkait | **Tidak** | Invalidate user terkait |
| `roles` | create/update/delete, permission mapping | `RolePermissionProvider` | User dengan role | Semua scope user | **Tidak** | Global/user invalidation |
| `permissions` | create/update/delete | `RolePermissionProvider` | Semua user/role terkait | Semua scope | Observer terdaftar tetapi **tidak dispatch** | Global invalidation |
| `role_has_permissions` / `model_has_roles` | perubahan pivot | Role/permission | User terkait (`model_has_roles`) | Semua scope | **Tidak** | Observer/command |
| `users` | role attach/detach, `is_active` | Semua provider | user | Semua scope | Observer terdaftar | Pertahankan |
| `revoked_permissions` | create/update/delete | `RevocationProvider` | `user_id` | Semua scope | Observer terdaftar | Pertahankan |
| `schools` | `is_active`, `work_unit_id` | Scope/konteks | User dengan assignment/penempatan sekolah | Scope sekolah | **Tidak** | Invalidate scope |
| `work_units` | `is_active`, `domain_id`, `parent_id` | Scope/domain | User dengan penempatan/assignment | Scope unit | **Tidak** | Invalidate scope |

### 3.2 Catatan penting

1. **`StructuralAssignment` adalah sumber kewenangan utama**, tetapi justru tidak ditangani observer.
2. **`HomeroomAssignment` dan `CoordinatorAssignment`** memengaruhi `WorkspaceActivationService` (workspace/sidebar/permission), tetapi tidak memicu rebuild.
3. **`OtherTeacherTask`** adalah sumber koordinator rumpun resmi per sekolah; tidak diobservasi.
4. **`Role`/`Permission`** memengaruhi hampir semua provider, tetapi Role tidak diobservasi dan Permission tidak menghasilkan user ID.
5. **`GtkWorkUnit`** tidak menghasilkan permission langsung, tetapi mengubah sekolah efektif (`SchoolGroupService::getUserSchool`) dan scope cache.
6. **Pivot Spatie** (`model_has_roles`, `role_has_permissions`) diubah via query builder pada seeder; event Eloquent tidak selalu berjalan.
7. **Bulk update** (`GtkWizardController::where(...)->update(['is_primary'=>false])`) melewati model events; observer tidak akan menangkap.

## 4. Desain Invalidasi dan Rebuild Permission

### 4.1 Invariant

1. Setiap perubahan assignment/role/permission yang memengaruhi snapshot harus **menandai snapshot terkait sebagai tidak valid**.
2. Cache permission untuk user/scope harus dihapus.
3. Rebuild harus memakai **scope asli** (school, academic year, role dimension, tenant), bukan tebakan.
4. Jika rebuild gagal, sistem harus **fail-closed** (tidak memakai snapshot lama), tetapi tidak boleh membuat Core tidak dapat diakses tanpa alasan yang jelas.
5. Perubahan harus **idempoten** dan aman terhadap event ganda.
6. Tidak boleh ada assignment yang “hilang” dari invalidation hanya karena bulk update/seeder.

### 4.2 Service pusat

Usulan komponen baru (belum diimplementasikan):

```text
PermissionInvalidationService
  invalidateUsers(array $userIds, string $reason, array $scopeHints = []): void
  invalidateAll(string $reason): void
  invalidateByRole(array $roleIds, string $reason): void
  invalidateByPermission(array $permissionIds, string $reason): void
```

Alur:

```text
Model event (created/updated/deleted)
  → kumpulkan user IDs (baru + lama)
  → afterCommit
    → PermissionInvalidationService::invalidateUsers()
      → tandai permission_snapshots.stale_at = now() untuk user
      → cache->forgetUser(userId)
      → dispatch BuildSnapshotJob(userId, reason) [dedupe]
      → catat audit/event
```

### 4.3 Ekstraksi user ID per model

| Model | Aturan |
|---|---|
| `StructuralAssignment` | `user_id` baru dan `getOriginal('user_id')` |
| `HomeroomAssignment` | `teacher_id` baru dan lama |
| `CoordinatorAssignment` | `teacher_id` baru dan lama |
| `TeachingAssignment` | `teacher_id` baru dan lama |
| `GtkAdditionalTask` | `user_id` baru dan lama |
| `OtherTeacherTask` | `teacher_id` baru dan lama |
| `DormitoryStaffAssignment` | `user_id` baru dan lama |
| `GtkEmployment` | `user_id` baru dan lama |
| `GtkWorkUnit` | `user_id` baru dan lama |
| `GtkTransferRequest` | `user_id` + pelaku/approver terkait |
| `Subject` | guru dari `TeachingAssignment` untuk subject tersebut |
| `SubjectGroup` | guru + koordinator dari resolver |
| `Role` | user dari `model_has_roles` |
| `Permission` | role/user terdampak; global hanya jika perubahan benar-benar sistemik |
| `StructuralPosition` | user dari `StructuralAssignment` + `GtkWorkUnit` untuk posisi tersebut |
| `Domain` | user dari assignment posisi dalam domain |
| `School`/`WorkUnit` | user dengan penempatan/assignment pada sekolah/unit |

### 4.4 Perubahan skema snapshot (additive)

`permission_snapshots` saat ini tidak menyimpan komponen scope. Usulan kolom **nullable**:

| Kolom | Tipe | Fungsi |
|---|---|---|
| `scope_academic_year_id` | uuid/string nullable | Rekonstruksi academic year |
| `scope_role_dimension` | string nullable | Rekonstruksi role dimension |
| `scope_tenant` | string nullable | Rekonstruksi tenant |
| `stale_at` | timestamp nullable | Penanda invalidasi |
| `invalidated_reason` | string nullable | Audit/debug |

**Batasan kompatibilitas yang disetujui:**

1. **Format `scope_key` tidak berubah.** `ScopeKey::fromComponents()` tetap menghasilkan hash 64 karakter yang sama; kolom metadata hanya pelengkap, bukan pengganti.
2. **Identitas snapshot tidak berubah.** Unique existing `(user_id, scope_key, is_current)` dan primary key `id` dipertahankan.
3. **Kolom baru nullable.** Tidak ada default yang mengubah perilaku snapshot lama.
4. **Snapshot lama tidak dihapus/diubah paksa.** Baris lama tanpa metadata tetap dapat dipakai sampai TTL; rebuild berikutnya mengisi metadata.
5. **Tidak ada ketergantungan keras pada metadata lama.** Bila `scope_academic_year_id`/`scope_role_dimension`/`scope_tenant` null, resolver memakai fallback dan mencatat warning; jangan mengasumsikan nilainya ada.
6. **Index sesuai pola pencarian:**
   - existing `(user_id, is_current)` tetap;
   - tambah `(user_id, stale_at)`;
   - tambah `(scope_key)` bila belum ada (unique existing sudah mencakup `scope_key`, tetapi query rebuild per scope dapat memanfaatkannya);
   - jangan menambah index berlebihan pada tabel panas.

Tambahan:

- `invalidated_reason` membantu audit/debug, bukan dasar keputusan.
- `stale_at` diisi oleh invalidation; `null` berarti snapshot dianggap segar (selama belum expired).
- Backfill metadata **tidak** dilakukan dari `scope_key` (hash tidak dapat dibalik); lakukan rebuild bertahap dari daftar sekolah/tahun ajaran yang diketahui atau biarkan lazy.
- Snapshot baru menulis seluruh metadata.

**Alternatif tanpa kolom:** rebuild seluruh kombinasi school×year×role yang diketahui; lebih mahal dan rawan. Kolom additive lebih tepat.

### 4.5 Perubahan `SnapshotResolver`

Logika baru:

```text
if cache hit → return
if stored && (expired || stale_at != null):
    rebuild with context from stored metadata
if stored && !expired && stale_at == null → return
if no stored → rebuild on demand
```

Jika metadata lama null:

- gunakan `scope_school_id` + tahun ajaran aktif **hanya sebagai fallback**;
- catat warning;
- setelah rebuild, isi metadata baru.

### 4.6 Perubahan `BuildSnapshotJob`

1. Ambil snapshot `is_current` untuk user.
2. Untuk setiap row:
   - gunakan `scope_school_id`, `scope_academic_year_id`, `scope_role_dimension`, `scope_tenant`;
   - jika metadata kosong, fallback + log;
   - rebuild context yang sama.
3. Sebelum rebuild: `cache->forget(userId, scopeKey)`.
4. Setelah rebuild: `stale_at = null`, `invalidated_reason = null`.
5. Dedupe job per user; gunakan `ShouldBeUnique` atau cache lock.
6. Jika gagal: biarkan `stale_at` terisi; resolver akan fail-closed/rebuild berikutnya.

### 4.7 Role dan permission

- **Role berubah:** user IDs dari `model_has_roles`; invalidate **hanya user yang terdampak role tersebut**.
- **Permission berubah:** cari role yang memakai permission lalu user pada role tersebut; invalidate user terkait. **Global invalidation hanya** jika permission tersebut memang dipakai lintas seluruh sistem atau perubahan bersifat sistemik.
- **Pivot berubah:** observer Eloquent pada pivot tidak selalu tersedia; sediakan command `permission:invalidate --role=...` dan jalankan setelah seeder/perubahan manual.
- **Seeder:** jangan mengandalkan event; setelah seeder, jalankan invalidation terbatas pada role/user yang berubah.
- **Larangan:** jangan memicu rebuild seluruh snapshot untuk setiap perubahan kecil.

### 4.8 Scope dan konteks

- `GtkWorkUnit` berubah → invalidate user; scope sekolah lama/baru.
- `School.is_active`/`work_unit_id` berubah → invalidate user yang terkait.
- `WorkUnit.domain_id`/`is_active` berubah → invalidate user dengan penempatan/assignment.
- `AcademicYear.is_active`/`locked_at` berubah → invalidate scope tahun ajaran.

### 4.9 Idempotency, concurrency, failure

**Cakupan invalidasi:**

| Perubahan | Cakupan |
|---|---|
| Assignment (`structural`, `homeroom`, `coordinator`, `teaching`, `additional task`, `dormitory staff`) | User + scope terkait |
| `gtk_work_unit` / status / tanggal | User + scope sekolah lama/baru |
| Role/permission yang dipakai role tertentu | User yang memiliki role tersebut |
| Permission global / perubahan sistemik | Seluruh user (hanya jika terbukti global) |
| Subject/SubjectGroup | Guru/koordinator yang terkait |

**Mekanisme:**

1. Gunakan `afterCommit` agar event tidak dikirim sebelum transaksi commit.
2. Dedupe dengan `ShouldBeUnique` per user + window waktu (mis. 30–60 detik) atau lock per user; jangan membuat job untuk setiap baris dalam batch besar.
3. Job retry terbatas (mis. 3×) dengan backoff; jangan retry tanpa batas.
4. Bila rebuild gagal:
   - snapshot tetap `stale_at` terisi;
   - cache dihapus;
   - resolver **tidak** boleh memakai snapshot stale untuk allow (fail-closed);
   - catat error dan kirim alert;
   - sediakan command rebuild manual.
5. Bila queue mati, gunakan command `permission:rebuild --stale` dengan rate limit dan batch.
6. Jangan menulis audit PII.
7. Hindari loop: kegagalan rebuild tidak boleh memicu event baru yang menyebabkan rebuild lagi.

### 4.10 Observability

- Log: user_id, trigger, model, event, scope count, durasi, hasil.
- Metrics: jumlah invalidation, rebuild sukses/gagal, snapshot stale, umur snapshot.
- Command drift: bandingkan fingerprint snapshot dengan build ulang sampling.
- Audit perubahan assignment harus tercatat terpisah (lihat Production Safety Plan).

### 4.11 Migrasi dan kompatibilitas

- Kolom baru nullable; tidak ada perubahan tipe/constraint existing.
- Kode lama tetap berjalan; kolom baru diisi oleh kode baru.
- Baris snapshot lama tanpa metadata tetap dapat dipakai sampai TTL; setelah itu rebuild.
- Tidak ada delete/arsip massal snapshot.
- Rollback: nonaktifkan penggunaan `stale_at` (feature flag), biarkan kolom; jangan drop.
- Backfill metadata tidak dapat dilakukan dari `scope_key` hash; lakukan rebuild bertahap dari daftar sekolah/tahun ajaran yang diketahui, atau biarkan lazy.

### 4.12 Yang tidak diubah

- Tidak mengubah `permission_snapshots` primary key/unique existing.
- Tidak mengubah provider atau aturan bisnis permission.
- Tidak mengubah role/permission existing.
- Tidak menjalankan rebuild massal pada desain ini.

## 5. Lifecycle Penempatan GTK (`gtk_work_unit`)

### 5.1 Kondisi aktual

**Skema:** `id`, `user_id`, `work_unit_id`, `jabatan`, `is_primary`, `position_id` (nullable), `timestamps`, unique `(user_id, work_unit_id)` (`2026_02_10_010955_create_gtk_work_unit_table.php:11–21`; `2026_10_29_180100...`).

**Tidak ada:** `status`, `start_date`, `end_date`, `deleted_at`, alasan berakhir.

**Pembaca utama (harus kompatibel):**

- `User::gtkWorkUnits()`, `primaryWorkUnit()` (`app/Models/User.php:138–146`).
- `SchoolGroupService::getUserSchool()` dan `userIdsForSchool()`.
- `GtkWizardController` (create/update/import, `assignWorkUnit`, bulk update).
- `GtkUnitHeadAssignment` (kepala/wakil).
- `PersonaliaController`, `GtkTransferRequest`, `CandidateConversionService`.
- `SatuanKerja\PositionController`, `SatuanKerja\TaskController`.
- `DormitoryTaskAssignmentController`, `DormitoryMasterController`, `RoomSupervisorController`.
- `SchoolController`, `SchoolUnitController`, `AccessValidatorController`.
- Sarpras controllers, Kaldik, Siplah, BumpOrder, exports, `UserJabatan`.
- POS `KepegawaianController` (via `gtkWorkUnits`).
- API Connect (desain baru).

**Implikasi:** perubahan pada `gtk_work_unit` berpotensi memengaruhi banyak modul. Tidak boleh ada perubahan perilaku tanpa inventaris dan test.

### 5.2 Opsi desain

| Opsi | Deskripsi | Kelebihan | Kekurangan | Rekomendasi |
|---|---|---|---|---|
| A | Tidak berubah; dokumentasikan “baris ada = aktif” | Nol risiko | Tidak ada lifecycle/history; API tidak bisa akurat | Tidak cukup |
| B+ | Tambah `status`/`start_date`/`end_date` ke `gtk_work_unit` + tabel `gtk_work_unit_periods` | Data tetap di tabel; lifecycle jelas; histori periode | Perubahan perilaku pembaca; perlu audit menyeluruh | **Disetujui pemilik sistem** |
| C | Tabel baru `gtk_work_unit_periods`; `gtk_work_unit` tetap current projection | Additive penuh; pembaca lama aman | Perlu sinkronisasi proyeksi; akhir periode harus menghapus/menandai current row | Alternatif |
| D | Tabel baru penuh, `gtk_work_unit` dihapus/deprecated | Model bersih | Perubahan besar; tidak backward-compatible | Tidak sekarang |

**Keputusan: Opsi B+ disetujui.**

- Tambah kolom lifecycle ke `gtk_work_unit`.
- Tambah tabel `gtk_work_unit_periods` untuk histori periode.
- Pertahankan unique `(user_id, work_unit_id)`; re-placement memakai baris yang sama (reactivate) dan menambah period baru.
- **Baris `ended` tetap disimpan** di `gtk_work_unit`; tidak dipindahkan/dihapus.
- Histori periode tidak boleh hilang saat penempatan berakhir.
- **Belum ada perubahan perilaku pembaca** sebelum audit seluruh pembaca dan strategi transisi disetujui (lihat matriks dampak).
- Semua pembaca wajib memakai scope current/active **setelah** transisi disetujui dan diuji.

### 5.3 Status dan aturan efektif

Usulan status:

| Status | Arti |
|---|---|
| `planned` | Akan dimulai (`start_date` > hari ini) |
| `active` | Sedang berlaku |
| `ended` | Berakhir normal (`end_date` terisi) |
| `cancelled` | Dibatalkan sebelum/selama berlaku |

Aturan:

```text
is_effective =
  status = 'active'
  AND start_date <= active_on
  AND (end_date IS NULL OR end_date >= active_on)
```

- `planned` tidak dianggap aktif untuk scope, tetapi tampil di admin.
- `ended`/`cancelled` tidak aktif.
- `is_primary` terpisah; hanya satu primary aktif per user (validasi aplikasi).
- `position_id` tetap opsional; `jabatan` tetap legacy.

### 5.4 Tabel periode

Usulan `gtk_work_unit_periods` (baru, additive):

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | uuid PK | |
| `placement_id` | uuid nullable | Referensi `gtk_work_unit.id`; `nullOnDelete` agar histori tetap |
| `user_id` | uuid | Tanpa cascade delete; histori harus tetap |
| `work_unit_id` | uuid | Tanpa cascade delete; histori harus tetap |
| `position_id` | uuid nullable | `nullOnDelete` |
| `decree_id` | uuid nullable | `nullOnDelete` |
| `status` | string(16) | `planned/active/ended/cancelled` |
| `start_date` | date | Wajib |
| `end_date` | date nullable | |
| `ended_reason` | string nullable | |
| `ended_by` | uuid nullable | User |
| `created_by` | uuid nullable | User |
| `created_at`, `updated_at` | timestamp | |

Index: `(user_id, start_date)`, `(work_unit_id, start_date)`, `(status, start_date)`.

**Keputusan FK:** `user_id` dan `work_unit_id` memakai **FK RESTRICT** (disetujui). Tujuannya menjaga histori: penghapusan user/work unit tidak boleh menghilangkan periode. Hard delete induk harus melalui pemeriksaan dependensi dan persetujuan khusus; archive/nonaktif tetap default.

**Catatan:** `position_id`, `decree_id`, `ended_by`, `created_by` dapat memakai `nullOnDelete` agar histori tetap ada meski referensi opsional dihapus; keputusan final per kolom dicatat saat implementasi.

### 5.5 Sinkronisasi current projection dan periods

Alur tulis yang disarankan (service, bukan controller langsung):

```text
startPlacement(user, unit, position, start_date, ...):
  - validasi unik/tidak ada active overlap
  - upsert gtk_work_unit (status active/planned, start_date, end_date null)
  - append gtk_work_unit_periods (status, start_date)
  - invalidate permission/scope user

endPlacement(placement, end_date, reason, actor):
  - update gtk_work_unit: status ended, end_date, is_primary=false
  - update period aktif: status ended, end_date, reason, actor
  - invalidate permission/scope user

replacePlacement(...):
  - end periode lama
  - reactivate baris yang sama (unique pair) dengan start_date baru
  - append period baru
```

**Penting:** semua penulisan `gtk_work_unit` (termasuk import, transfer, kandidat, wizard, unit head) harus melalui service ini atau observer yang setara. Bulk update `where(...)->update()` melewati event; harus diubah ke service/loop dengan event.

### 5.6 Kompatibilitas pembaca

**Batasan disetujui:** jangan mengubah perilaku pembaca existing sebelum seluruh pembaca diaudit dan strategi transisi disetujui. Matriks awal ada di `docs/integration/alim-core-gtk-work-unit-impact-matrix.md`; matriks itu **belum final** sampai audit file per file selesai.

Sebelum mengaktifkan lifecycle:

1. Tambah kolom nullable + tabel periods; backfill.
2. Tambah scope `current()`/`effectiveOn()` pada model.
3. Audit semua pembaca; klasifikasikan:
   - **Current-only** (kebanyakan): wajib filter current/active.
   - **History/admin**: boleh melihat ended.
   - **Count/statistik**: perlu definisi (current vs all).
4. Update pembaca bertahap dengan feature flag.
5. Bandingkan hasil query lama vs baru di staging.
6. Aktifkan filter hanya setelah semua pembaca diuji.

**Jangan** memakai global scope diam-diam tanpa audit, karena dapat menyembunyikan data dari laporan histori.

### 5.7 Backfill

- `status = 'active'` untuk semua baris existing.
- `start_date = DATE(created_at)`.
- `end_date = NULL`.
- Buat satu `gtk_work_unit_periods` per baris existing dengan `placement_id`, `status active`, `start_date`, `end_date NULL`.
- Lakukan batch (mis. 500–1000 baris) untuk menghindari lock panjang.
- Verifikasi jumlah: `gtk_work_unit` = periode aktif awal.

### 5.8 Migrasi aman

**Prasyarat sebelum staging/backfill (belum ada jadwal):**

1. Backup penuh dan **restore test terverifikasi oleh PIC internal**.
2. Inventaris final pembaca/penulis `gtk_work_unit` (bukan hanya grep awal).
3. Analisis kompatibilitas data existing: jumlah baris, duplikasi, `is_primary`, unit multi-sekolah.
4. Strategi backfill + query validasi hasil (counts, checksum, sampling).
5. Estimasi dampak database/performa: ukuran tabel, index, lock, waktu backfill, beban query baru.
6. Rencana pengujian staging per modul.
7. Kriteria keberhasilan dan kegagalan yang eksplisit.
8. Rollback yang tidak menghapus data.
9. Feature flag dan rencana penghentian fitur.
10. Persetujuan terpisah untuk implementasi.

**Estimasi dampak yang wajib diukur di staging:**

- jumlah baris `gtk_work_unit` dan `gtk_work_unit_periods`;
- ukuran tabel/index sebelum/sesudah;
- waktu `ALTER TABLE`/backfill per batch;
- lock duration dan dampak ke query harian;
- `EXPLAIN` untuk query pembaca utama;
- p95 latency halaman GTK/sekolah/asrama/sarpras sebelum/sesudah;
- memori/CPU worker rebuild permission.

**Kriteria keberhasilan:**

1. Backfill selesai tanpa error; jumlah periode awal = jumlah baris existing.
2. Tidak ada duplikasi/overlap aktif yang tidak dijelaskan.
3. Pembaca current-only menghasilkan daftar yang sama dengan baseline (kecuali data yang memang ended).
4. Semua test modul di §7 lulus.
5. Permission invalidation/rebuild lulus; stale tidak memberi akses.
6. Performa dalam ambang yang disetujui.
7. Rollback flag terbukti mengembalikan perilaku lama tanpa kehilangan data.

**Kriteria kegagalan (hentikan dan rollback flag):**

- Ada perbedaan data yang tidak dapat dijelaskan.
- Ada modul yang menampilkan ended sebagai aktif.
- Ada user kehilangan/mendapat akses yang salah.
- Backfill melebihi jendela waktu/lock.
- Rebuild permission gagal berulang.
- Performa melewati ambang.

| Langkah | Perubahan | Risiko | Mitigasi |
|---|---|---|---|
| 1 | Add nullable columns `status`, `start_date`, `end_date`, `ended_reason`, `ended_by` | Lock tabel | Online DDL/staged; backup; maintenance window |
| 2 | Create `gtk_work_unit_periods` | Rendah | Additive |
| 3 | Backfill batch | Beban DB | Batch, off-peak, monitoring |
| 4 | Deploy kode dual-read (flag off) | Rendah | Feature flag |
| 5 | Aktifkan filter current | Perilaku berubah | Staging test, canary |
| 6 | Aktifkan write lifecycle | Risiko data | Service + test |

**Larangan:** drop unique existing, drop column, cascade change, delete data, `migrate:fresh`.

### 5.9 Rollback

- Sebelum fitur aktif: drop/keep tabel baru? **Jangan drop** jika sudah berisi data; nonaktifkan flag.
- Sesudah fitur aktif: rollback kode ke pembaca lama; kolom `status` diabaikan; data tetap.
- Jangan menghapus `gtk_work_unit_periods`.
- Jangan mengubah data existing untuk rollback.

## 6. Dampak ke Permission dan Integrasi

### 6.1 Permission

- Setiap perubahan `gtk_work_unit` harus memanggil `PermissionInvalidationService`.
- Perubahan status/end date harus invalidate scope user.
- `gtk_work_unit_periods` tidak perlu memicu permission sendiri jika current projection sudah memicu; hindari double.

### 6.2 ALIM Connect

- API harus mengekspos `status`, `start_date`, `end_date`, `is_effective`.
- `work-unit-assignments` saat ini tidak memiliki lifecycle; setelah desain ini, API dapat menyediakan.
- Sampai implementasi, API harus menyatakan “baris ada = aktif” dan tidak mengklaim lifecycle.

### 6.3 Audit

- Setiap transisi status harus menulis audit (actor, reason, before/after).
- Jangan hanya mengandalkan `updated_at`.
- Histori periode menjadi sumber audit penempatan.

## 7. Rencana Pengujian

| ID | Skenario | Ekspektasi |
|---|---|---|
| PL-01 | Backfill existing | Semua baris status active, start=created_at, periode awal |
| PL-02 | Start placement | Current + period dibuat; scope user invalidated |
| PL-03 | End placement | Status ended, end_date, period ditutup, akses dicabut |
| PL-04 | Re-placement pair sama | Baris yang sama reactivated; period baru |
| PL-05 | Future start | Status planned; tidak efektif |
| PL-06 | Cancel | Status cancelled; tidak efektif |
| PL-07 | Multi-unit | Scope union benar |
| PL-08 | Primary ganda | Validasi menolak |
| PL-09 | Bulk import | Semua melalui service; period lengkap |
| PL-10 | Transfer/kandidat | Period dan history benar |
| PL-11 | Pembaca lama | Tidak menampilkan ended sebagai aktif (setelah flag) |
| PL-12 | Rollback flag | Perilaku lama kembali; data tetap |
| PR-01 | Structural assignment create/end | Snapshot invalidated/rebuild |
| PR-02 | Homeroom create/end | Snapshot invalidated/rebuild |
| PR-03 | Coordinator create/end | Snapshot invalidated/rebuild |
| PR-04 | Role/permission change | Invalidasi user/role terdampak; global hanya jika sistemik |
| PR-05 | OtherTeacherTask | Snapshot invalidated |
| PR-06 | DormitoryStaffAssignment | Snapshot invalidated once (no duplicate) |
| PR-07 | Subject/SubjectGroup | Guru/koordinator invalidated |
| PR-08 | GtkWorkUnit change | Scope/cache invalidated |
| PR-09 | Queue failure | Snapshot stale, fail-closed, retry |
| PR-10 | Concurrent events | Dedupe, no lost update |
| PR-11 | Old snapshot without metadata | Fallback + log + rebuild |
| PR-12 | Bulk update bypass events | Command/service catches or documented gap |

## 8. Risiko dan Keputusan

| ID | Risiko | Mitigasi |
|---|---|---|
| R-01 | Perubahan reader `gtk_work_unit` terlewat | Inventaris + grep + test per modul |
| R-02 | Backfill mengunci tabel | Batch/off-peak/online DDL |
| R-03 | Status ended tetap terbaca | Scope current + audit pembaca |
| R-04 | Unique pair menghalangi riwayat | Reuse row + periods table |
| R-05 | Snapshot lama tanpa metadata | Fallback + lazy rebuild |
| R-06 | Role/pivot change via query builder | Command invalidasi + seeder guard |
| R-07 | Queue failure | Stale flag + fail-closed + manual rebuild |
| R-08 | Double invalidation | Dedupe/central service |
| R-09 | Perubahan perilaku produksi | Feature flag, staging, canary |
| R-10 | Data existing tidak konsisten | Read-only audit sebelum backfill |

### 8.1 Keputusan yang sudah disetujui

| No | Keputusan | Status |
|---|---|---|
| 1 | Opsi B+ (kolom lifecycle + periods) | Disetujui |
| 2 | Baris `ended` tetap di `gtk_work_unit` | Disetujui |
| 3 | FK periods ke user/work unit = RESTRICT | Disetujui |
| 4 | `stale_at` + metadata scope pada snapshot | Disetujui secara prinsip |
| 5 | Invalidasi berbasis cakupan dampak | Disetujui |
| 6 | `scope_key` dan identitas snapshot tidak berubah | Disetujui |
| 7 | PIC backup/restore TBD, tidak diisi asumsi | Disetujui |
| 8 | Jadwal staging/backfill belum ditetapkan | Belum |

### 8.2 Yang masih diperlukan sebelum implementasi

1. Nama PIC backup/restore dan reviewer (diisi pemilik sistem).
2. Backup + restore test terverifikasi.
3. Inventaris final pembaca/penulis `gtk_work_unit`.
4. Analisis kompatibilitas data existing dan estimasi performa.
5. Strategi backfill + validasi hasil.
6. Rencana pengujian staging dan kriteria lulus/gagal.
7. Rollback dan feature flag final.
8. Persetujuan implementasi terpisah.

## 9. Larangan

- Jangan mengimplementasikan desain ini sebelum disetujui.
- Jangan menjalankan migration/backfill sekarang.
- Jangan mengubah FK/cascade existing.
- Jangan menghapus/merename kolom existing.
- Jangan mengubah assignment/permission existing.
- Jangan melakukan rebuild massal di produksi tanpa backup/approval.
- Jangan menganggap dokumen ini sebagai izin deployment.

## Lampiran A. Bukti Kode

| Topik | Lokasi |
|---|---|
| Observer gap | `app/Authorization/Support/PermissionRebuildObserver.php:58–78` |
| Registrasi model | `app/Authorization/Providers/AuthorizationRuntimeServiceProvider.php:143–146` |
| Build job scope | `app/Authorization/Jobs/BuildSnapshotJob.php` |
| Resolver/TTL | `app/Authorization/Services/SnapshotResolver.php:32–127` |
| Rebuild service | `app/Authorization/Services/SnapshotRebuildService.php` |
| Snapshot schema | `2026_06_29_000021_create_authorization_permission_snapshots_table.php` |
| Provider auto-tag | `app/Authorization/Providers/AuthorizationServiceProvider.php:77–100` |
| Workspace activation | `app/Services/WorkspaceActivationService.php` |
| SubjectGroup resolver | `app/Services/SubjectGroupResolver.php` |
| Dormitory observer | `app/Observers/DormitoryStaffAssignmentObserver.php` |
| GtkWorkUnit schema | `2026_02_10_010955_create_gtk_work_unit_table.php:11–21` |
| GtkWorkUnit model | `app/Models/GtkWorkUnit.php` |
| Reader examples | `app/Services/SchoolGroupService.php`; `app/Http/Controllers/GtkWizardController.php`; `app/Models/User.php:138–146` |
