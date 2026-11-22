---
name: alim-core-gtk-work-unit-final-audit
description: Audit final file-per-file pembaca dan penulis gtk_work_unit untuk lifecycle Opsi B+
metadata:
  type: audit
  audit-date: 2026-11-22
  status: completed
  scope: read-only
  depends-on:
    - docs/integration/alim-core-permission-and-placement-lifecycle-design.md
    - docs/integration/alim-core-gtk-work-unit-impact-matrix.md
---

# Audit Final `gtk_work_unit` — Pembaca, Penulis, dan Kesiapan Opsi B+

> **Audit read-only.** Tidak ada perubahan kode, database, migration, seeder, observer, job, service, atau konfigurasi.
>
> **Tidak ada pengujian runtime/backfill** yang dijalankan. Seluruh temuan berasal dari pembacaan source code dan migration.

## 1. Executive Summary

### 1.1 Kesimpulan

**Opsi B+ layak dilanjutkan ke desain implementasi, tetapi belum layak diimplementasikan sebelum 10 perbaikan wajib diselesaikan.**

Alasannya:

1. B+ **mampu** mempertahankan identitas baris utama **jika invarian berikut dijaga**:
   - satu baris per `(user_id, work_unit_id)`;
   - `work_unit_id` pada baris existing **tidak pernah diubah**;
   - transfer = end baris lama + start/reactivate baris tujuan;
   - re-placement = reactivate baris yang sama + periode baru.
2. Namun ada jalur existing yang **melanggar invarian**:
   - `GtkWizardController::syncWorkUnit` mengubah `work_unit_id` pada baris primary.
   - `GtkPositionAssignmentService::assign` memakai filter `is_primary` dan dapat mengubah `work_unit_id`/membuat baris ganda.
3. Banyak pembaca **belum memfilter status**; jika baris `ended` dipertahankan, pembaca akan menganggapnya aktif.
4. Beberapa **penulis memakai query builder** dan melewati model event; lifecycle/history tidak akan tercatat tanpa service.
5. Risiko tertinggi ada pada pembaca otorisasi (Sarpras, Asrama, Sekolah, Kaldik) yang memakai `jabatan`/keanggotaan unit; user yang sudah berakhir dapat tetap memiliki akses.

### 1.2 Angka audit

- Referensi `GtkWorkUnit`/`gtk_work_unit`/`gtkWorkUnits`: **229 baris** di **±70 file**.
- Penulis/reader-writer yang memengaruhi lifecycle: **16 jalur**.
- Pembaca yang perlu ditinjau: **±55 file** (dikelompokkan per modul).
- Jalur yang wajib memakai service terpusat: **12 jalur**.
- Jalur yang wajib diperbaiki sebelum feature flag: **10 item** (§9).

### 1.3 Verdict

| Aspek | Verdict |
|---|---|
| B+ secara model | **Layak** |
| Identitas baris saat transfer | **Layak hanya jika W-02/W-09 diperbaiki** |
| Identitas baris saat re-placement | **Layak** (reuse baris + periode) |
| Kesiapan pembaca | **Belum** — perlu filter current |
| Kesiapan penulis | **Belum** — banyak bypass service |
| Kesiapan implementasi | **No-Go** sampai §9 selesai |

## 2. Metode dan Batasan

### 2.1 Metode

1. Inventaris seluruh referensi `GtkWorkUnit`, `gtk_work_unit`, `gtkWorkUnits`.
2. Klasifikasi reader/writer/reader-writer.
3. Pembacaan method dan query aktual.
4. Penelusuran relasi, pivot, dan event.
5. Analisis identitas baris untuk transfer/re-placement.
6. Penyusunan transisi per modul dan kriteria go/no-go.

### 2.2 Batasan

- Line reference dapat berubah jika file diedit.
- Audit tidak menjalankan aplikasi atau query database.
- Tidak semua modul non-organisasi diaudit hingga detail bisnis; fokus pada penggunaan `gtk_work_unit`.
- Kesimpulan data existing belum diverifikasi; hanya kode.

## 3. Klasifikasi

| Kode | Arti |
|---|---|
| **R** | Reader: hanya membaca |
| **W** | Writer: membuat/mengubah/menghapus |
| **RW** | Reader-writer |
| **Store** | Menyimpan/menyalin data untuk modul lain |

## 4. Audit Penulis dan Reader-Writer

### 4.1 Ringkasan penulis

| ID | File | Method | Tipe | Query/perilaku | Risiko B+ |
|---|---|---|---|---|---|
| W-01 | `GtkWizardController` | `assignWorkUnit` (`:1540–1555`) | W | `where(user)->update(is_primary=false)` + `updateOrCreate(user,unit)` | Primary lama tidak di-end; periode tidak dibuat; bulk update bypass event |
| W-02 | `GtkWizardController` | `syncWorkUnit` (`:1749–1765`) | RW | update baris primary dan **mengubah `work_unit_id`** | **Identitas baris hilang**; periode tidak tercatat |
| W-03 | `GtkWizardController` | import/update (`:3468–3485`) | W | update `existing` atau create | Tidak membuat periode; tidak menutup periode lama |
| W-04 | `GtkUnitHeadAssignment` | `assign` (`:42–70`) | RW | `firstOrNew(user,unit)` + save | Identitas aman; status/start tidak diisi; tidak menutup periode |
| W-05 | `GtkUnitHeadAssignment` | `release` (`:86–110`) | RW | update jabatan/position | Tidak mengakhiri penempatan (memang bukan end placement); perlu dipastikan tidak salah dianggap end |
| W-06 | `GtkTransferRequest` | `applyTransferToUser` (`:172–205`) | RW | create/reactivate destination, demote primary | Identitas tujuan aman; primary lama tidak di-end; periode tidak dibuat |
| W-07 | `PersonaliaController` | `transferGtk` / `approveTransfer` (`:245–290`, `:345–370`) | RW | `workUnits()->syncWithoutDetaching` + `updateExistingPivot` | Identitas aman; tidak end/start periode; bypass model event |
| W-08 | `CandidateConversionService` | konversi kandidat (`:224–240`) | W | `where(user)->update(is_primary=false)` + create | Identitas aman; periode tidak dibuat |
| W-09 | `GtkPositionAssignmentService` | `assign` (`:36–40`) | W | `updateOrCreate(['user_id','is_primary'=>true])` | **Filter salah**; dapat mengubah `work_unit_id`/membuat baris ganda; identitas berisiko |
| W-10 | `SatuanKerja\PositionController` | `update` (`:75–82`) | RW | query builder update `position_id`/`jabatan` | Identitas aman; bypass event; tidak mencatat perubahan periode/audit |
| W-11 | `SatuanKerja\PositionController` | `massUpdate` (`:116–126`) | RW | query builder update massal | Sama; batch tanpa audit |
| W-12 | `DormitoryTaskAssignmentController` | `syncWorkUnitPosition` (`:351–360`) | RW | query builder update `position_id`/`jabatan` | Identitas aman; bypass event |
| W-13 | `Console\PerbaikiSatkerAsrama` | command (`:277–288`) | RW | update primary + `updateOrCreate` | Bisa menimpa lifecycle; wajib dry-run/service |
| W-14 | `Console\ImportGtkDataCommand` | command (`:302`) | W | `updateOrCreate` | Tidak membuat periode; import massal |
| W-15 | `WorkUnitController` | `destroy`/`bulkDestroy` (`:171–187`, `:232`) | RW | cek `gtk_work_unit`, soft delete unit | Tidak menghapus placement, tetapi unit nonaktif menyembunyikan konteks; guard belum memeriksa schools/dormitories |
| W-16 | `GtkUnitHeadController` | `update` | RW | memanggil `GtkUnitHeadAssignment` | Tergantung W-04/W-05; tidak boleh mengakhiri placement saat release |

### 4.2 Detail jalur kritis

#### W-02 — `GtkWizardController::syncWorkUnit` (paling berbahaya)

**Kode aktual:**

```php
$workUnit = GtkWorkUnit::where('user_id', $userId)->where('is_primary', true)->first();
$workUnit
    ? $workUnit->update([
        'work_unit_id' => $workUnitId,
        'jabatan' => $jabatan,
        'position_id' => ...,
    ])
    : GtkWorkUnit::create([...]);
```

**Dampak:**

- Baris dengan `id` lama berubah dari unit A menjadi unit B.
- Identitas periode A hilang; histori A tidak tercatat.
- Jika sudah ada baris untuk B (secondary), unique `(user, B)` dapat bentrok atau menciptakan ambigu.
- B+ **tidak dapat** mempertahankan identitas jika jalur ini tidak diperbaiki.

**Wajib:** ganti dengan service `end(A)` + `start/reactivate(B)`.

#### W-09 — `GtkPositionAssignmentService::assign`

**Kode aktual:**

```php
GtkWorkUnit::updateOrCreate(
    ['user_id' => $user->id, 'is_primary' => true],
    ['work_unit_id' => $workUnit->id, 'jabatan' => $positionName]
);
```

**Dampak:**

- Filter `is_primary` bukan kunci unik; dapat membuat baris kedua untuk unit yang sama.
- Dapat mengubah `work_unit_id` pada baris primary (identitas hilang).
- Tidak memakai posisi/period/end.

**Wajib:** gunakan kunci `(user_id, work_unit_id)` + service.

#### W-07 — Transfer via `PersonaliaController`

**Kode aktual:** `workUnits()->syncWithoutDetaching` dan `updateExistingPivot(['is_primary'=>false])`.

**Dampak:**

- Identitas baris aman, tetapi tidak mengakhiri periode lama/membuka periode baru.
- Bypass model events; audit lifecycle tidak tercatat.
- Dua jalur transfer (PersonaliaController dan GtkTransferRequest) harus disatukan.

#### W-10/W-11/W-12 — Query builder update

**Dampak:**

- `position_id`/`jabatan` berubah tanpa event, audit, atau update periode.
- Identitas baris aman, tetapi histori posisi dalam periode tidak tercatat.
- Wajib service `updatePosition()` atau event-aware update.

### 4.3 Invarian identitas untuk penulis

Agar B+ valid, semua penulis harus mematuhi:

```text
1. Baris diidentifikasi oleh (id) dan pasangan (user_id, work_unit_id).
2. work_unit_id tidak boleh diubah pada baris existing.
3. Transfer = end baris lama + start/reactivate baris tujuan.
4. Re-placement = reactivate baris yang sama + periode baru.
5. Satu user hanya boleh punya satu active primary.
6. Setiap start/end/update-position menulis periode + audit.
7. Bulk update tidak boleh melewati service.
```

## 5. Audit Pembaca

### 5.1 Ringkasan pembaca per modul

| Modul/file | Class/method utama | Tipe | Query/perilaku | Dampak jika `ended` dipertahankan | Transisi |
|---|---|---|---|---|---|
| `Models/User.php` | `gtkWorkUnits`, `primaryWorkUnit`, `headedWorkUnitIds`, akses asrama | R | hasMany, primary, `jabatan LIKE KEPALA%`, semua baris | Ended dianggap aktif; kepala lama tetap berwenang | Filter current; primary hanya active |
| `Models/WorkUnit.php` | `gtkWorkUnits()` | R | hasMany | Hitungan/daftar GTK termasuk ended | Current-only untuk daftar |
| `Models/GtkProfile.php` | komentar/model | R | menegaskan data unit dikelola `GtkWorkUnit` | Tidak langsung | Dokumentasi |
| `Policies/KaldikPolicy.php` | `getUserWorkUnitId` | R | `primaryWorkUnit` | Ended primary bisa dipakai | Current-only |
| `Services/SchoolGroupService.php` | `getUserSchool`, `userIdsForSchool`, `gtkUserIdsForSchool` | R | semua baris unit/sekolah | Scope sekolah salah; GTK lama masuk | Filter current/effective |
| `Support/UserJabatan.php` | resolusi jabatan | R | placements + structural | Jabatan lama tampil | Current-only |
| `Services/TeacherAvailabilityService.php` | ketersediaan guru | R | `where(work_unit)->pluck(user_id)` | Guru ended dianggap tersedia | Current-only |
| `Services/InstitutionDecreeMatrixService.php` | matriks SK | R | semua `GtkWorkUnit` per user | Baris SK memakai penempatan lama | Current-only/period-aware |
| `Services/InstitutionDecreePayloadService.php` | payload SK | R | `whereIn(user)->get()` | Payload salah | Current-only |
| `Services/NotificationUniversalService.php` | target notifikasi | R | `gtkWorkUnits` | Notifikasi ke user/unit lama | Current-only |
| `Services/SidebarIndicatorService.php` | indikator sidebar | R | placement | Indikator salah | Current-only |
| `Services/Dormitory/DormitoryRewardRequestService.php` | reward asrama | R | placement | Staf lama dianggap aktif | Current-only |
| `Http/Controllers/GtkWizardController.php` | list/detail/update/import | RW | 24 referensi | Menampilkan ended sebagai aktif; writer risk | Service + current-only |
| `Http/Controllers/GtkUnitHeadController.php` | index/update | RW | fallback placement kepala/wakil | Kepala lama tetap tampil | Current-only + service |
| `Http/Controllers/GtkUnitHeadAssignment.php` (Support) | assign/release | RW | firstOrNew/update | Tidak end/start period | Service |
| `Http/Controllers/GtkPositionController.php` | posisi | R | placement | Data lama tampil | Current-only |
| `Http/Controllers/MasterDataController.php` | index/destroy | R/guard | count/guard `gtk_work_unit` | Hitungan termasuk ended; guard unit | Current-only; guard period |
| `Http/Controllers/WorkUnitController.php` | destroy/bulkDestroy | RW | cek placement, soft delete unit | Unit nonaktif menyembunyikan placement | Guard schools/unit lifecycle |
| `Http/Controllers/SchoolController.php` | akses sekolah | R | `where(user)->pluck(work_unit)` | User ended tetap akses sekolah | Current-only |
| `Http/Controllers/SchoolUnitController.php` | daftar unit | R | `whereHas(gtkWorkUnits)` | User ended tetap tampil | Current-only |
| `Http/Controllers/Auth/AccessValidatorController.php` | validasi akses | R | `primaryWorkUnit` | Akses salah | Current-only |
| `Http/Controllers/ProfileController.php` | profil | R | `gtkWorkUnits.workUnit` | Menampilkan unit lama | Current-only |
| `Http/Controllers/PersonaliaController.php` | transfer/approve/history | RW | pivot sync/update | Tidak end/start period | Service |
| `Http/Controllers/Personalia/PersonaliaDashboardController.php` | dashboard | R | `DB::table('gtk_work_unit')` join | Statistik termasuk ended | Current-only |
| `Http/Controllers/Personalia/CrossUnitAssignmentController.php` | cross-unit | R | `gtkWorkUnits` | User ended ditampilkan | Current-only |
| `Http/Controllers/KaldikController.php` | target agenda/notifikasi | R | `where(work_unit)->pluck(user)` | Admin TU lama dapat notifikasi | Current-only |
| `Http/Controllers/SiplahController.php` | unit primary | R | `primaryWorkUnit` | Unit lama | Current-only |
| `Http/Controllers/BumpOrderController.php` | unit/jabatan | R | `primaryWorkUnit`, jabatan | Data lama | Current-only |
| `Http/Controllers/InstitutionDebtController.php` | unit primary | R | `primaryWorkUnit` | Unit lama | Current-only |
| `Http/Controllers/AppFeedbackController.php` | unit/jabatan | R | `orderByDesc(is_primary)->first()` | Bisa memilih ended | Current-only |
| `Http/Controllers/SuperAdmin/NotificationUniversalController.php` | target unit | R | `primaryWorkUnit`/first | Unit lama | Current-only |
| `Http/Controllers/Waka/SupervisiController.php` | validasi GTK sekolah | R | `whereHas(workUnit.school)` | GTK ended dianggap anggota | Current-only |
| `Http/Controllers/Waka/SupervisiSkController.php` | validasi GTK sekolah | R | `whereHas(workUnit.school)` | Sama | Current-only |
| `Http/Controllers/Waka/SupervisiPengaturanController.php` | validasi supervisor | R | `whereHas(workUnit.school)` | Sama | Current-only |
| `Http/Controllers/Waka/EkstrakurikulerController.php` | daftar GTK sekolah | R | `whereHas(workUnit.school)` | Sama | Current-only |
| `Http/Controllers/TeachingAssignmentController.php` | scoping guru | R | `pluck(user_id)`/`pluck(work_unit)` | Guru ended dianggap anggota | Current-only |
| `Http/Controllers/InstitutionDecreeController.php` | SK/matriks | R | `whereIn(user)->get()` | Data lama | Current-only |
| `Http/Controllers/DormitoryMasterController.php` | GTK asrama | R | `gtkWorkUnits.workUnit` | Staf lama tampil | Current-only |
| `Http/Controllers/DormitoryTaskAssignmentController.php` | staf/penugasan | RW | `whereHas(gtkWorkUnits)`, update position | Staf lama dianggap aktif; bypass event | Current-only + service |
| `Http/Controllers/RoomSupervisorController.php` | wali kamar | R | `whereHas(workUnits)` | Wali lama tampil | Current-only |
| `Http/Controllers/DormitoryRoomController.php` | kamar | R | placement | Data lama | Current-only |
| `Http/Controllers/SatuanKerja/PositionController.php` | daftar/update posisi | RW | `whereHas(gtkWorkUnits)`, query builder update | GTK ended tampil; bypass event | Current-only + service |
| `Http/Controllers/SatuanKerja/TaskController.php` | tugas unit | R | `where(work_unit)->pluck(user)` | User ended dianggap anggota | Current-only |
| `Http/Controllers/Sarpras/SarprasBaseController.php` | scope unit | R | `DB::table(gtk_work_unit)` | Petugas lama berwenang | Current-only |
| `Http/Controllers/Sarpras/SarprasOfficerController.php` | petugas | R | `whereIn(work_unit)`/`whereHas` | Petugas lama berwenang | Current-only |
| `Http/Controllers/Sarpras/SarprasPertamananController.php` | otorisasi jabatan | R | `pluck(jabatan)` | Jabatan lama memberi akses | Current-only |
| `Http/Controllers/Sarpras/SarprasCleaningController.php` | otorisasi kebersihan | R | `pluck(jabatan)` | Sama | Current-only |
| `Http/Controllers/Sarpras/SarprasElectricityController.php` | otorisasi listrik | R | `pluck(jabatan)` | Sama | Current-only |
| `Http/Controllers/Sarpras/SarprasWasteController.php` | otorisasi sampah | R | `pluck(jabatan)` | Sama | Current-only |
| `Http/Controllers/Sarpras/SarprasVehicleController.php` | otorisasi pengemudi | R | `pluck(jabatan)` | Sama | Current-only |
| `Http/Controllers/Sarpras/SarprasItemManagerController.php` | otorisasi unit | R | `whereHas(gtkWorkUnits)` | Sama | Current-only |
| `Http/Controllers/Sarpras/SarprasReportController.php` | laporan | R | eager load `gtkWorkUnits` | Data lama | Current-only |
| `Http/Controllers/Sarpras/SarprasStockOpnameController.php` | stock opname | R | `whereHas(gtkWorkUnits)` | Sama | Current-only |
| `Http/Controllers/SarprasController.php` | akses sarpras | R | `where(user)->pluck(work_unit)` | Sama | Current-only |
| `Http/Controllers/Api/Pos/V1/KepegawaianController.php` | API POS | R | `gtkWorkUnits` | Unit lama terkirim | Current-only + field lifecycle |
| `Http/Controllers/Api/Pos/V1/SatuanKerjaController.php` | API POS | R | `withCount(gtkWorkUnits)` | Hitungan termasuk ended | Current-only |
| `Http/Controllers/Api/Pos/V1/KoneksiController.php` | ping | R | count | Hitungan termasuk ended | Current-only |
| `Http/Controllers/Api/Recruitment/V1/PegawaiController.php` | API recruitment | R | `gtkWorkUnits` | Unit lama terkirim | Current-only |
| `Http/Controllers/Api/Recruitment/V1/KoneksiController.php` | ping | R | count | Sama | Current-only |
| `Exports/GtkExport.php` | ekspor GTK | R | `firstWhere(is_primary)` | Unit utama lama | Current-only |
| `Exports/InstitutionDecreeTemplateExport.php` | template SK | R | `GtkWorkUnit::with(position)` | Data lama | Current-only |
| `Exports/KurikulumImportTemplateExport.php` | template kurikulum | R | `where(user)->...` | Data lama | Current-only |
| `Console/Commands/SyncGtkRolesCommand.php` | sinkron role | R/W | placement | Bisa memakai data lama | Dry-run/service |
| `Console/Commands/FixGtkDomainPermissions.php` | audit domain | R | placement | Data lama | Read-only/report |
| `Console/Commands/PerbaikiSatkerAsrama.php` | repair | RW | update/create | Menimpa lifecycle | Dry-run/service |
| `Console/Commands/ImportGtkDataCommand.php` | import | W | updateOrCreate | Tidak ada periode | Service |
| `Models/GtkWorkUnitHistory.php` | histori lama | R | from/to unit | Bukan pengganti periode | Migrasi ke periods |

### 5.2 Temuan pembaca paling berisiko

1. **Otorisasi Sarpras berbasis `jabatan`** (`pluck('jabatan')->implode`) — jika baris ended tetap ada, user lama tetap dianggap petugas/koordinator. **High.**
2. **Keanggotaan unit/sekolah** (`whereHas('gtkWorkUnits')`, `userIdsForSchool`) — user ended tetap dianggap anggota. **High.**
3. **Kepala/wakil unit** (`headedWorkUnitIds`, `GtkUnitHeadController`) — kepala lama tetap dianggap kepala. **High.**
4. **Primary unit** (`primaryWorkUnit`) — jika `is_primary` tidak dibersihkan saat end, unit lama tetap utama. **High.**
5. **Statistik/ekspor** — angka dan data historis tercampur. **Medium.**
6. **API POS/Recruitment** — data unit lama terkirim ke aplikasi eksternal. **Medium–High.**

**Kesimpulan:** tidak ada satu pun pembaca yang secara eksplisit memfilter `status` karena kolom belum ada. Setelah B+, **semua pembaca harus diklasifikasikan dan diubah**; tidak boleh ada feature flag aktif sebelum audit ini selesai.

## 6. Apakah Opsi B+ Mampu Mempertahankan Identitas Baris Utama?

### 6.1 Invarian yang harus dijaga

Opsi B+ hanya valid jika:

1. Baris existing **tidak pernah** mengubah `work_unit_id`.
2. Kunci alami adalah `(user_id, work_unit_id)`; unique dipertahankan.
3. Transfer = **end** baris lama + **start/reactivate** baris tujuan.
4. Re-placement = **reactivate baris yang sama** + periode baru.
5. Setiap perubahan status/tanggal/posisi dicatat di `gtk_work_unit_periods`.
6. `ended` tetap ada; `is_primary` menjadi false saat end.
7. Pembaca memakai current/active, bukan semua baris.

### 6.2 Skenario identitas

| Skenario | Baris A | Baris B | Identitas | Periode | Verdict |
|---|---|---|---|---|---|
| Start di A | Baru, aktif | — | Stabil | 1 periode | **Aman** |
| Transfer A→B (B belum ada) | A di-end | B dibuat | A & B stabil | 2 periode | **Aman jika service** |
| Transfer A→B (B sudah ada secondary) | A di-end | B reactivated | A & B stabil | 2 periode | **Aman jika service** |
| Re-placement A→B→A | A di-end lalu reactivated | B di-end | A & B stabil | 3 periode | **Aman jika service** |
| Secondary B saat A primary | A tetap | B tetap | Stabil | Sesuai periode | **Aman** |
| `syncWorkUnit` A→B | `id` A berubah menjadi B | — | **Rusak** | A hilang | **Tidak aman** |
| `GtkPositionAssignmentService` primary | Bisa berubah unit/duplikat | — | **Berisiko** | Tidak tercatat | **Tidak aman** |
| Release kepala unit | Baris tetap di unit | — | Stabil | Tidak berakhir | **Aman** (release jabatan ≠ end placement) |
| End placement | Status ended, end_date | — | Stabil | Periode ditutup | **Aman** |
| Re-placement unit sama | Baris sama reactivated | — | Stabil | Periode baru | **Aman** |

### 6.3 Jawaban langsung

**Ya, B+ mampu mempertahankan identitas baris utama — dengan syarat W-02 dan W-09 diperbaiki, dan semua transfer/re-placement memakai service.**

Tanpa perbaikan:

- W-02 memindahkan identitas baris A ke B (bukan membuat periode A berakhir).
- W-09 dapat mengubah unit baris primary atau membuat baris kedua.
- Transfer via Personalia/TransferRequest tidak menutup periode lama.
- Pembaca akan melihat A/B yang salah.

**Kesimpulan:** B+ **secara desain layak**, tetapi **belum aman dengan jalur penulisan saat ini**.

## 7. Service Lifecycle Terpusat

### 7.1 Tanggung jawab

Usulan `GtkPlacementLifecycleService`:

| Method | Tanggung jawab |
|---|---|
| `start(User, WorkUnit, ?Position, startDate, actor, ?decree)` | Buat/reactivate baris; buat periode; validasi unik/overlap; set primary |
| `end(Placement, endDate, reason, actor)` | Set ended/end_date; tutup periode; `is_primary=false`; invalidate permission/scope |
| `transfer(User, from, to, ...)` | `end(from)` + `start(to)`; periode baru; audit |
| `reactivate(Placement, startDate, ...)` | Reuse baris; periode baru; status active |
| `updatePosition(Placement, ?Position, actor)` | Update position/jabatan; catat perubahan periode/audit |
| `setPrimary(User, Placement)` | Pastikan hanya satu active primary; end/clear primary lama |
| `assertNoOverlap(User, WorkUnit, start, end)` | Cegah periode tumpang tindih |
| `assertIdentity(Placement)` | Pastikan `work_unit_id` tidak berubah |

### 7.2 Transaksi dan audit

- Semua method dalam `DB::transaction`.
- Tulis `gtk_work_unit_periods` sebelum/sesudah perubahan current dalam transaksi yang sama.
- Tulis audit event (`placement.started/ended/transferred/position_changed`) dengan actor, reason, before/after.
- Panggil `PermissionInvalidationService` setelah commit.
- Jangan memanggil service dari dalam observer yang dapat menyebabkan rekursi.

### 7.3 Penulis yang wajib memakai service

| Wajib | Alasan |
|---|---|
| W-01 `GtkWizardController::assignWorkUnit` | Primary/end/start |
| W-02 `GtkWizardController::syncWorkUnit` | Identitas baris |
| W-03 `GtkWizardController` import | Periode massal |
| W-04/W-05 `GtkUnitHeadAssignment` | Start/position; release bukan end |
| W-06 `GtkTransferRequest` | Transfer end/start |
| W-07 `PersonaliaController` transfer/approve | Transfer end/start |
| W-08 `CandidateConversionService` | Start |
| W-09 `GtkPositionAssignmentService` | Identitas/primary |
| W-10/W-11 `SatuanKerja\PositionController` | Position update + audit |
| W-12 `DormitoryTaskAssignmentController` | Position update + audit |
| W-13/W-14 Console commands | Dry-run + service |

## 8. Pembaca yang Wajib Diubah Sebelum Feature Flag

| Prioritas | Modul | Alasan |
|---|---|---|
| P0 | Auth/AccessValidator, SchoolController, SchoolUnitController | Akses sekolah/unit |
| P0 | SarprasBase/Officer/Pertamanan/Cleaning/Electricity/Waste/Vehicle/ItemManager | Otorisasi berbasis jabatan/keanggotaan |
| P0 | DormitoryMaster/TaskAssignment/RoomSupervisor | Staf asrama/wali kamar |
| P0 | User::headedWorkUnitIds, GtkUnitHeadController | Kepala/wakil lama |
| P1 | GtkWizard, SatuanKerja Position/Task, InstitutionDecree | Daftar/penugasan |
| P1 | Kaldik, Siplah, BumpOrder, InstitutionDebt, AppFeedback | Unit utama/target |
| P1 | API POS/Recruitment, Exports | Data keluar |
| P2 | Statistik/dashboard | Definisi current vs all |
| P2 | Waka supervisi/ekstrakurikuler | Keanggotaan sekolah |

**Aturan:** jangan mengaktifkan filter current di salah satu modul sebelum seluruh modul P0 selesai; jangan memakai global scope diam-diam karena dapat menyembunyikan data histori.

## 9. Must-Fix Sebelum Feature Flag

| No | Item | Bukti | Kriteria selesai |
|---|---|---|---|
| MF-01 | Hentikan perubahan `work_unit_id` pada baris existing | W-02, W-09 | Semua transfer memakai end/start; test identitas |
| MF-02 | Perbaiki `GtkPositionAssignmentService` | filter `is_primary` | Kunci `(user,unit)`; tidak ada duplikat |
| MF-03 | Tutup periode lama pada semua transfer | W-01, W-06, W-07, W-08, W-14 | Periode A ended; periode B started |
| MF-04 | Perbaiki `GtkUnitHeadAssignment::release` | W-05 | Release hanya mengubah posisi/role; tidak salah end |
| MF-05 | Ganti query builder update ke service | W-10/W-11/W-12 | Event/audit/periode tercatat |
| MF-06 | Guard command repair/import | W-13/W-14 | Dry-run default; service; backup |
| MF-07 | Filter current pada pembaca P0 | §8 | Test akses Sarpras/Asrama/Sekolah |
| MF-08 | Pastikan satu active primary per user | W-01/W-09 | Invariant test |
| MF-09 | Backfill periode konsisten | §6/§7 | Counts/cocok; no orphan |
| MF-10 | Feature flag + rollback + observability | Desain | Flag off/on; alert; runbook |
| MF-11 | API/exports memakai lifecycle | §5 | Field status/end; tidak ada ended sebagai current |

## 10. Skenario Pengujian Regresi

| ID | Skenario | Ekspektasi |
|---|---|---|
| RG-01 | Start A | Baris A + periode; current readers melihat A |
| RG-02 | Transfer A→B baru | A ended; B active; 2 periode; identitas A/B tetap |
| RG-03 | Transfer A→B existing | A ended; B reactivated; tidak ada duplikat |
| RG-04 | Re-placement A→B→A | A/B identity stabil; 3 periode; current A |
| RG-05 | End placement | Status ended; is_primary false; akses dicabut |
| RG-06 | Release kepala | Position/role berubah; placement tetap active |
| RG-07 | Position change | Position update; periode/audit tercatat |
| RG-08 | Bulk import | Semua periode; tidak ada bypass |
| RG-09 | Command repair/import | Dry-run; service; backup |
| RG-10 | Auth sekolah | Ended tidak mengakses sekolah |
| RG-11 | Sarpras otorisasi | Ended tidak dianggap petugas |
| RG-12 | Asrama | Ended tidak dianggap staf/wali |
| RG-13 | GtkUnitHead | Ended tidak dianggap kepala/wakil |
| RG-14 | API POS/Recruitment | Hanya current; field lifecycle benar |
| RG-15 | Export | Hanya current atau label history |
| RG-16 | Permission invalidation | Assignment/placement end mencabut akses |
| RG-17 | Rollback flag | Perilaku lama; data tetap |
| RG-18 | Concurrency | Dua transfer bersamaan tidak menghasilkan dua primary |
| RG-19 | Data backfill | Jumlah periode = baris existing; no overlap |
| RG-20 | Performance | Tidak ada regresi di halaman kritis |

## 11. Go/No-Go Implementasi

### 11.1 Go

Implementasi boleh dimulai hanya jika:

1. **Backup + restore test terverifikasi PIC internal.**
2. Inventaris file ini disetujui dan **tidak ada writer yang belum terdaftar**.
3. MF-01 s.d. MF-11 selesai atau diisolasi dengan feature flag yang aman.
4. Semua pembaca P0 memakai current/effective.
5. Service lifecycle + periode + audit + invalidation tersedia.
6. Backfill diuji di staging dengan hasil valid.
7. Performa/lock dalam ambang yang disetujui.
8. Rollback flag terbukti.
9. Persetujuan implementasi terpisah.

### 11.2 No-Go

**No-Go** jika:

- Masih ada jalur yang mengubah `work_unit_id` pada baris existing.
- Masih ada bulk update `is_primary` yang melewati service.
- Masih ada pembaca P0 tanpa filter current.
- Belum ada periode/backfill yang konsisten.
- Belum ada backup/restore verified.
- Belum ada staging/feature flag/rollback.
- Ada perubahan FK/cascade existing.

### 11.3 Verdict final

| Pertanyaan | Jawaban |
|---|---|
| Apakah B+ mampu mempertahankan identitas baris? | **Ya, dengan syarat MF-01/MF-02 dan service** |
| Apakah implementasi siap? | **Belum** |
| Apakah desain layak dilanjutkan? | **Ya, conditional go** |
| Apakah ada persetujuan implementasi? | **Belum** |

## 12. Batasan dan Pertanyaan Terbuka

1. Line reference dapat berubah setelah edit.
2. Tidak ada query database produksi; jumlah/duplikasi data belum diketahui.
3. Tidak ada pengujian runtime.
4. Nama PIC backup/restore tetap TBD.
5. Perlu keputusan apakah `ended` tetap terlihat di admin/history atau hanya di periode.
6. Perlu keputusan apakah `is_primary` harus unik per user di level DB atau aplikasi.
7. Perlu keputusan performa index/backfill.
8. Perlu audit final file-per-file setelah implementasi service (bukan hanya sebelum).

## Lampiran A. Inventaris File

**Models/Support/Services:** `User`, `WorkUnit`, `GtkWorkUnit`, `GtkWorkUnitHistory`, `GtkProfile`, `GtkTransferRequest`, `UserJabatan`, `GtkUnitHeadAssignment`, `SchoolGroupService`, `TeacherAvailabilityService`, `InstitutionDecreeMatrixService`, `InstitutionDecreePayloadService`, `NotificationUniversalService`, `SidebarIndicatorService`, `DormitoryRewardRequestService`, `KaldikPolicy`.

**Controllers:** `GtkWizardController`, `GtkUnitHeadController`, `GtkPositionController`, `MasterDataController`, `WorkUnitController`, `SchoolController`, `SchoolUnitController`, `AccessValidatorController`, `ProfileController`, `PersonaliaController`, `PersonaliaDashboardController`, `CrossUnitAssignmentController`, `KaldikController`, `SiplahController`, `BumpOrderController`, `InstitutionDebtController`, `AppFeedbackController`, `SuperAdmin/NotificationUniversalController`, `Waka/SupervisiController`, `Waka/SupervisiSkController`, `Waka/SupervisiPengaturanController`, `Waka/EkstrakurikulerController`, `TeachingAssignmentController`, `InstitutionDecreeController`, `DormitoryMasterController`, `DormitoryTaskAssignmentController`, `RoomSupervisorController`, `DormitoryRoomController`, `SatuanKerja/PositionController`, `SatuanKerja/TaskController`, `SarprasBaseController`, `SarprasOfficerController`, `SarprasPertamananController`, `SarprasCleaningController`, `SarprasElectricityController`, `SarprasWasteController`, `SarprasVehicleController`, `SarprasItemManagerController`, `SarprasReportController`, `SarprasStockOpnameController`, `SarprasController`.

**API/Exports/Commands:** `Api/Pos/V1/KepegawaianController`, `Api/Pos/V1/SatuanKerjaController`, `Api/Pos/V1/KoneksiController`, `Api/Recruitment/V1/PegawaiController`, `Api/Recruitment/V1/KoneksiController`, `Exports/GtkExport`, `Exports/InstitutionDecreeTemplateExport`, `Exports/KurikulumImportTemplateExport`, `Console/Commands/SyncGtkRolesCommand`, `Console/Commands/FixGtkDomainPermissions`, `Console/Commands/PerbaikiSatkerAsrama`, `Console/Commands/ImportGtkDataCommand`.

## 13. Verifikasi Kegagalan View (2026-11-22)

Dua kelompok test view gagal saat regresi Batch 1/GTK follow-up:

- `tests/Feature/DashboardJabatanTest.php`: 2 gagal (`Tahun Ajaran`, `welcome-banner`/salam).
- `tests/Feature/SchoolUnitTest.php`: 1 gagal (`Mapping Rombel Dapodik`).

Pemeriksaan singkat:

- Test file dan view dashboard/dapodik **tidak diubah** oleh commit GTK Batch 1 (`ba4876a9`) maupun follow-up (`4f291b43`).
- Kegagalan berupa assertion teks pada HTML, bukan error pada service, migration, atau lifecycle GTK.
- Baseline `cfc86817` (sebelum GTK Batch 1) dijalankan di worktree terpisah dan menghasilkan **3 kegagalan yang sama** pada `DashboardJabatanTest` (2) dan `SchoolUnitTest` (1).
- Working tree saat ini memiliki banyak perubahan pre-existing di luar Batch 1/approval.

**Kesimpulan:** tiga kegagalan tersebut **pre-existing**, bukan regresi GTK Batch 1, GTK follow-up, atau Approval Command API. Perbaikan view/assertion tetap perlu dilakukan pada pekerjaan terpisah, tetapi tidak menghalangi review implementasi ini. Tidak ada data produksi yang diubah.
