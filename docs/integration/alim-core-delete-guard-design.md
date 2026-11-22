---
name: alim-core-delete-guard-design
description: Desain guard delete dan archive untuk mencegah kehilangan data ALIM Core
metadata:
  type: design
  design-date: 2026-11-21
  status: proposal
  scope: p1-remediation
  depends-on:
    - docs/integration/alim-core-production-safety-plan.md
---

# Desain Guard Delete dan Archive — ALIM Core

> **Desain saja.** Tidak ada perubahan kode, route, controller, model, migration, atau database pada tahap ini.
>
> Tujuan: menghentikan hard delete berantai tanpa mengubah FK/cascade existing dan tanpa mengubah data existing.

## 1. Prinsip

1. **Archive/nonaktif lebih diutamakan daripada delete.**
2. **Guard sebelum delete**, bukan sesudah.
3. **Jangan mengubah FK/cascade existing.**
4. **Jangan menambah kolom** kecuali benar-benar diperlukan.
5. **Data existing tidak ditulis ulang.**
6. **Perubahan controller dapat di-rollback tanpa kehilangan data.**
7. **Semua keputusan bisnis tetap milik pemilik sistem.**

## 2. Rekomendasi kebijakan

**Rekomendasi terbaik: gunakan kolom status/aktif yang sudah ada.**

| Entity | Kolom archive yang sudah ada | Tindakan yang disarankan |
|---|---|---|
| `schools` | `is_active` | Nonaktifkan, jangan hapus |
| `structural_positions` | `is_active` | Nonaktifkan, jangan hapus |
| `academic_years` | `is_active`, `locked_at` | Kunci/arsipkan, jangan hapus |
| `grade_levels` | `is_active` | Nonaktifkan |
| `subjects` | `is_active` | Nonaktifkan |
| `study_groups` | `is_active` | Nonaktifkan |
| `work_units` | `is_active`, `deleted_at` | Nonaktifkan/soft delete |
| `institution_decrees` | `status` (`draft|active|archived`) | Arsipkan |
| `gtk_work_unit` | **Tidak ada** | Belum diubah; perlu keputusan terpisah |

**Kesimpulan:** untuk P1, **tidak perlu menambah kolom baru**. Kolom yang ada sudah cukup untuk archive/nonaktif.

## 3. Matriks guard per controller

### 3.1 SK — `InstitutionDecreeController@destroy`

**Dependensi yang harus dicek:**

- `structural_assignments.decree_id`
- `teaching_assignments.decree_id`
- `homeroom_assignments.decree_id`
- `coordinator_assignments.decree_id`
- `other_teacher_tasks.decree_id`
- `gtk_additional_tasks.decree_id`

**Aturan:**

- Jika ada dependensi → **tolak delete**, arahkan `status = archived`.
- Jangan menghapus `OtherTeacherTask`/`TeachingAssignment` secara eksplisit seperti saat ini.
- Jika tidak ada dependensi → boleh delete dengan audit, atau tetap archive untuk konsistensi.

**Catatan:** `InstitutionDecree` memiliki `deleted_at` tetapi model tidak memakai `SoftDeletes`; perubahan trait adalah perubahan perilaku dan harus disetujui terpisah.

### 3.2 Sekolah — `SchoolController@destroy`, `SuperAdmin\AdminSchoolController@destroy`

**Dependensi:**

- `academic_years.school_id`
- `grade_levels.school_id`
- `study_groups.school_id`
- `structural_assignments.school_id`
- `teaching_assignments.school_id`
- `homeroom_assignments.school_id`
- `coordinator_assignments.school_id`
- `gtk_cross_unit_assignments.school_id`
- `dormitories.school_id`

**Aturan:**

- Jika ada dependensi → **tolak delete**, arahkan `is_active = false`.
- Jika tidak ada dependensi → delete dengan audit.
- Jangan mengubah FK cascade.

### 3.3 Jabatan — `MasterDataController@jabatanDestroy`

**Dependensi:**

- `structural_assignments.position_id`
- `gtk_work_unit.position_id`
- `dormitory_staff_assignments.position_id`

**Aturan:**

- Jika ada dependensi → tolak delete, arahkan `is_active = false`.
- Jika tidak ada → delete dengan audit.
- Jangan mengubah `cascadeOnDelete`/`nullOnDelete`.

### 3.4 Tahun ajaran — `AcademicYearController@destroy`

**Dependensi:**

- `study_groups.academic_year_id`
- `structural_assignments.academic_year_id`
- `teaching_assignments.academic_year_id`
- `homeroom_assignments.academic_year_id`
- `coordinator_assignments.academic_year_id`
- tabel asrama yang memakai `academic_year_id`

**Aturan:**

- Jika ada dependensi → tolak delete; gunakan `locked_at`/`is_active=false`.
- Jika tidak ada → delete dengan audit.
- Hormati `isLocked()` yang sudah ada.

### 3.5 Tingkat kelas — `GradeLevelController@destroy`

**Dependensi:** `study_groups.grade_level_id`.

**Aturan:** jika ada rombel → tolak delete, arahkan `is_active=false`.

### 3.6 Mapel — `SubjectController@destroy`

**Dependensi:**

- `teaching_assignments.subject_id`
- `study_group_subjects.subject_id`
- `grade_level_subjects.subject_id`
- tabel lain yang memakai `subject_id`

**Aturan:** jika ada dependensi → tolak delete, arahkan `is_active=false`.

### 3.7 Rombel — `StudyGroupController@destroy`

**Dependensi:**

- `teaching_assignments.study_group_id`
- `homeroom_assignments.study_group_id`
- absensi/jadwal/riwayat
- `study_group_subjects`
- `teacher_admin_books`

**Aturan:**

- **Hentikan pemakaian `StudyGroupService::deleteSafely()`** yang menghapus jadwal/riwayat/agenda.
- Jika ada dependensi → tolak delete, arahkan `is_active=false`.
- Jika tidak ada → delete dengan audit.

### 3.8 Unit kerja — `WorkUnitController@destroy` / `bulkDestroy`

**Dependensi:**

- `schools.work_unit_id`
- `gtk_work_unit.work_unit_id`
- `dormitories.work_unit_id`
- `gtk_cross_unit_assignments.home_work_unit_id`

**Aturan:**

- Cek seluruh dependensi, bukan hanya `gtk_work_unit`.
- Jika ada sekolah/GTK/asrama → tolak delete; arahkan `is_active=false`.
- `bulkDestroy` harus memakai guard yang sama; jangan soft-delete massal tanpa cek.

## 4. Pseudocode guard (bukan implementasi)

```text
function destroy(entity):
    dependencies = countDependencies(entity)
    if dependencies > 0:
        audit("DELETE_BLOCKED", entity, dependencies)
        return redirect with error + saran nonaktif/arsip

    DB::transaction:
        audit("DELETE_ALLOWED", entity)
        entity.delete()

    return success
```

**Catatan:**

- Audit harus ditulis **sebelum** delete.
- Jangan menelan exception tanpa log.
- Jangan memakai `forceDelete` di route pengguna.

## 5. Audit event

Minimal metadata:

```json
{
  "action": "DELETE_BLOCKED",
  "table_name": "schools",
  "record_id": "uuid",
  "actor_id": "uuid",
  "dependencies": {
    "academic_years": 3,
    "study_groups": 12,
    "structural_assignments": 8
  },
  "request_id": "..."
}
```

- Jangan menyimpan PII.
- Untuk delete yang diizinkan, catat snapshot field kunci sebelum delete.

## 6. UI/UX

- Tampilkan jumlah dependensi sebelum tombol delete.
- Jika dependensi > 0:
  - nonaktifkan tombol delete;
  - tampilkan tombol **Nonaktifkan/Arsipkan**;
  - jelaskan dampak.
- Untuk bulk delete:
  - tampilkan ringkasan per item;
  - wajib konfirmasi teks;
  - tolak seluruh batch jika ada satu item dengan dependensi (fail-closed).

## 7. Kompatibilitas dan rollback

- Perubahan hanya di controller/request/UI; tidak ada migration.
- Perilaku delete berubah (lebih ketat), tetapi data existing tidak berubah.
- Rollback: revert controller; tidak ada data yang perlu dipulihkan.
- Perlu regression test untuk memastikan archive/nonaktif bekerja dan modul lain tetap membaca data.

## 8. Test plan

| ID | Skenario | Ekspektasi |
|---|---|---|
| DG-01 | Delete SK dengan assignment | Ditolak + saran archive |
| DG-02 | Delete SK tanpa assignment | Diizinkan/archived |
| DG-03 | Delete sekolah dengan rombel | Ditolak |
| DG-04 | Delete sekolah tanpa dependensi | Diizinkan/archived |
| DG-05 | Delete jabatan dengan assignment | Ditolak |
| DG-06 | Delete tahun ajaran dengan rombel | Ditolak |
| DG-07 | Delete tingkat dengan rombel | Ditolak |
| DG-08 | Delete mapel dengan teaching assignment | Ditolak |
| DG-09 | Delete rombel dengan assignment | Ditolak |
| DG-10 | Bulk delete dengan satu item dependensi | Seluruh batch ditolak |
| DG-11 | Audit event tercatat | Ya, tanpa PII |
| DG-12 | Regresi POS/Recruitment | Tidak berubah |

## 9. Yang belum diputuskan

1. Apakah delete tanpa dependensi tetap diizinkan atau selalu archive.
2. Apakah `InstitutionDecree` memakai `SoftDeletes` (perubahan perilaku).
3. Apakah `gtk_work_unit` mendapat lifecycle (butuh kolom baru).
4. Apakah `WorkUnit` bulk delete dihapus total atau tetap dengan guard.
5. Siapa yang berwenang melakukan archive/nonaktif.

## 10. Larangan

- Jangan mengubah FK/cascade.
- Jangan menambah kolom pada desain ini.
- Jangan menghapus data existing.
- Jangan menjalankan migration.
- Jangan mengubah permission existing.
