---
name: alim-core-gtk-work-unit-impact-matrix
description: Matriks pembaca dan penulis gtk_work_unit serta dampak lifecycle Opsi B+
metadata:
  type: analysis
  date: 2026-11-21
  status: draft
  scope: design-only
  depends-on:
    - docs/integration/alim-core-permission-and-placement-lifecycle-design.md
---

# Matriks Dampak `gtk_work_unit` — Opsi B+

> **Analisis saja.** Tidak ada perubahan kode/data. Tujuan: memastikan transisi lifecycle tidak memutus modul existing.

## 1. Ringkasan

- `gtk_work_unit` dibaca oleh **banyak modul**: GTK, sekolah, asrama, sarpras, kurikulum, API POS/Recruitment, ekspor, dan command.
- Ada **9+ jalur penulisan** yang membuat/mengubah/menghapus baris, sebagian memakai query builder dan **melewati model events**.
- Opsi B+ menambahkan `status`/`start_date`/`end_date` dan tabel periode; **pembaca lama tidak boleh diubah sebelum audit dan strategi transisi disetujui**.
- Risiko utama: baris `ended` tetap ada, sehingga pembaca yang tidak memfilter akan menganggapnya aktif.

## 2. Penulis (Writers)

| No | Lokasi | Cara menulis | Dampak Opsi B+ | Tindakan desain |
|---|---|---|---|---|
| W-01 | `GtkWizardController::assignWorkUnit` (`:1542–1545`) | `where(user)->update(is_primary=false)` + `updateOrCreate(user,unit)` | Perlu periode/status; bulk update melewati event | Ganti ke service lifecycle; catat periode |
| W-02 | `GtkWizardController::syncWorkUnit` (`:1752–1760`) | update baris primary, bisa **mengubah work_unit_id** | Melanggar identitas pasangan; periode tidak tercatat | Service: end lama + start/reuse baru |
| W-03 | `GtkWizardController` import (`:3469–3478`) | create/update langsung | Periode tidak tercatat | Service/observer |
| W-04 | `GtkUnitHeadAssignment::assign` (`:42–70`) | `firstOrNew(user,unit)` + save | Tidak mengisi status/start; periode tidak tercatat | Service lifecycle |
| W-05 | `GtkUnitHeadAssignment::release` (`:86–100`) | update jabatan/position, tidak mengakhiri penempatan | Tidak ada end period | Service end/transfer |
| W-06 | `GtkTransferRequest::applyTransferToUser` (`:172–205`) | create/update primary + history | Tidak mengisi periode/status | Service lifecycle + period |
| W-07 | `CandidateConversionService` (`:224–240`) | create + history | Sama | Service lifecycle |
| W-08 | `GtkPositionAssignmentService::assign` (`:36`) | `updateOrCreate(user, is_primary=true)` | Filter bukan unique pair; berpotensi membuat baris kedua untuk unit yang sama | Perbaiki/service; validasi unique pair |
| W-09 | `SatuanKerja\PositionController::update` (`:75–80`) | query builder update position/jabatan | Melewati event; status/periode tidak berubah | Service atau event-aware update |
| W-10 | `SatuanKerja\PositionController::massUpdate` (`:116–125`) | query builder update per user | Sama; massal | Service batch + audit |
| W-11 | `DormitoryTaskAssignmentController::syncWorkUnitPosition` (`:351–360`) | query builder update position/jabatan | Melewati event | Service/event-aware |
| W-12 | `Console\PerbaikiSatkerAsrama` (`:277–286`) | `where(user)->update` + `updateOrCreate` | Command bisa menimpa lifecycle | Wajib melalui service + dry-run |
| W-13 | `Console\ImportGtkDataCommand` (`:302`) | `updateOrCreate` | Tidak mengisi periode | Service + dry-run |
| W-14 | `WorkUnitController::destroy` / `bulkDestroy` | soft delete unit, cek `gtk_work_unit` | Unit nonaktif dapat menyembunyikan penempatan | Cek juga lifecycle/periods; guard |
| W-15 | Seeders/other commands | create/update langsung | Tidak ada lifecycle | Jangan dijalankan di produksi; guard |

**Kesimpulan penulis:** tidak cukup menambah kolom; **semua penulis harus melalui satu service** atau observer yang menangkap create/update/delete **dan** query builder bulk update. Query builder tidak memicu event, jadi service wajib untuk W-01, W-09, W-10, W-11, W-12.

## 3. Pembaca (Readers)

| Modul | Lokasi contoh | Penggunaan | Dampak jika `ended` tidak difilter | Tindakan |
|---|---|---|---|---|
| Identitas/konteks | `User::gtkWorkUnits`, `primaryWorkUnit` (`:138–146`); `SchoolGroupService` | Menentukan unit/sekolah user | Menganggap ended sebagai aktif | Tambah scope current/effective |
| Auth/akses | `Auth\AccessValidatorController` (`:82`) | Primary work unit | Akses sekolah salah | Current-only |
| Sekolah | `SchoolController`, `SchoolUnitController` | Cek unit user | Scope salah | Current-only |
| GTK wizard | `GtkWizardController` (23 referensi) | List/detail/import/update | Menampilkan penempatan lama sebagai aktif | Current-only + admin view |
| Personalia | `Personalia\PersonaliaDashboardController`, `CrossUnitAssignmentController` | Statistik, cross-unit | Hitungan ganda | Definisikan current vs all |
| Satuan kerja | `SatuanKerja\PositionController`, `TaskController` | Daftar GTK per unit | GTK lama tetap muncul | Current-only |
| SK/matriks | `InstitutionDecreeController`, `InstitutionDecreeMatrixService` | Baris SK/assignment | Data SK salah | Current-only + periode |
| Asrama | `DormitoryMasterController`, `DormitoryTaskAssignmentController`, `RoomSupervisorController` | GTK asrama | Staf lama dianggap aktif | Current-only |
| Sarpras | `SarprasBaseController`, `SarprasOfficerController`, `SarprasPertamanan`, `SarprasElectricity`, `SarprasWaste`, `SarprasVehicle`, `SarprasItemManager`, `SarprasCleaning`, `SarprasStockOpname`, `SarprasReport` | Filter petugas/unit/jabatan | Petugas lama tetap berwenang | Current-only |
| Kurikulum | `TeachingAssignmentController`, `Waka\Supervisi*`, `KurikulumImportTemplateExport` | Guru/unit | Data guru salah | Current-only |
| Kaldik | `KaldikController` | Target unit/primary | Notifikasi salah | Current-only |
| Siplah/BumpOrder/InstitutionDebt | `SiplahController`, `BumpOrderController`, `InstitutionDebtController` | Primary unit | Data unit salah | Current-only |
| Profil | `ProfileController` | Menampilkan unit | Menampilkan penempatan lama | Current-only |
| Master data | `MasterDataController` | Jumlah GTK per unit | Hitungan ganda | Current-only |
| Ekspor | `GtkExport`, `InstitutionDecreeTemplateExport`, `KurikulumImportTemplateExport` | Unit utama/daftar | Ekspor salah | Current-only + label |
| API POS | `Api\Pos\V1\KepegawaianController` | `daftar_satuan_kerja` | Unit lama tampil | Current-only + lifecycle field |
| API Recruitment | `Api\Recruitment\V1\PegawaiController` | `gtkWorkUnits` | Sama | Current-only |
| API Connect (desain) | `organization/work-unit-assignments` | Akan mengekspos lifecycle | Belum ada | Status/tanggal |
| Command | `PerbaikiSatkerAsrama` | Audit/repair | Bisa menimpa lifecycle | Read-only/dry-run |

**Catatan:** daftar ini berdasarkan grep `gtkWorkUnits`, `GtkWorkUnit::`, dan `gtk_work_unit`; perlu audit final per file sebelum implementasi.

## 4. Temuan kritis kompatibilitas

1. **`GtkWizardController::syncWorkUnit` mengubah `work_unit_id` pada baris primary.** Ini mengubah identitas pasangan `(user,unit)` dan tidak mencatat periode. Harus diganti service end/start.
2. **Bulk update `is_primary`** melewati event; history/periode tidak tercatat.
3. **`GtkPositionAssignmentService` memakai `updateOrCreate(['user_id', 'is_primary'=>true])`**, bukan unique pair; berpotensi membuat baris kedua untuk unit yang sama.
4. **`SatuanKerja\PositionController` dan `DormitoryTaskAssignmentController`** memakai query builder update; tidak memicu event.
5. **`GtkUnitHeadAssignment::release`** hanya mengubah jabatan/position, tidak mengakhiri penempatan.
6. **`GtkTransferRequest`** create/update tanpa periode.
7. **Banyak pembaca tidak memfilter status**; setelah baris ended ditambahkan, mereka akan salah.
8. **Global scope bukan solusi otomatis** karena ekspor/laporan histori mungkin membutuhkan semua baris.

## 5. Strategi transisi yang disarankan

### 5.1 Service lifecycle

Buat `GtkPlacementLifecycleService` (nama usulan) dengan operasi:

- `start(User, WorkUnit, ?Position, startDate, actor, decree?)`
- `updatePosition(placement, position, actor)`
- `end(placement, endDate, reason, actor)`
- `transfer(User, from, to, ...)`
- `reactivate(placement, startDate, ...)`
- `assertNoOverlap(...)`

Semua penulis W-01..W-14 harus memakai service ini, kecuali command read-only.

### 5.2 Feature flag

- `placement.lifecycle.enabled` (config/env).
- Saat off: perilaku lama (baris dianggap aktif).
- Saat on: tulis status/tanggal/periode; pembaca memakai current.
- Jangan mengubah pembaca sebelum flag on di staging.

### 5.3 Dual-read / audit

- Bandingkan jumlah dan daftar unit per user antara query lama dan baru.
- Log perbedaan.
- Hanya aktifkan filter setelah nol perbedaan yang tidak dijelaskan.

### 5.4 Urutan

1. Audit final semua pembaca/penulis.
2. Staging: add kolom/tabel + backfill.
3. Deploy service + flag off (no behavior change).
4. Migrasikan penulis ke service (flag off; tulis status/periode).
5. Dual-read test.
6. Aktifkan flag pembaca bertahap.
7. Monitor.
8. Baru izinkan API Connect memakai lifecycle.

## 6. Test matrix per modul

| Modul | Test minimum |
|---|---|
| User/SchoolGroup | Sekolah efektif berubah saat placement end/transfer |
| Auth access validator | Tidak memakai ended placement |
| GTK wizard | Create/update/import menghasilkan periode |
| Personalia/transfer | Transfer menutup periode lama, membuka baru |
| Satuan kerja | Daftar GTK per unit tidak menampilkan ended |
| SK/matriks | Baris SK konsisten dengan periode |
| Asrama | Staf asrama ended tidak muncul |
| Sarpras | Petugas ended tidak berwenang |
| Kurikulum | Guru ended tidak dihitung |
| Kaldik/Siplah/BumpOrder | Primary unit benar |
| Export/API | Field lifecycle benar; tidak ada data lama bocor |
| Command | Dry-run tidak menulis; write melalui service |

## 7. Larangan

- Jangan mengubah pembaca sebelum audit/strategi disetujui.
- Jangan memakai global scope tanpa analisis laporan histori.
- Jangan mengubah unique `(user_id, work_unit_id)`.
- Jangan menghapus baris ended.
- Jangan menjalankan backfill di produksi sebelum backup/restore test oleh PIC.
- Jangan menganggap matriks ini final tanpa audit file per file.
