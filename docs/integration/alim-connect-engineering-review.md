---
name: alim-connect-engineering-review
description: Review fundamental engineering, risiko kritis, keputusan desain, dan kontrak API integrasi ALIM Connect
metadata:
  type: review
  review-date: 2026-11-21
  status: completed
  scope: audit-only
  reviewed:
    - docs/integration/alim-connect-api-design.md
    - docs/README.md (tidak ditemukan)
    - implementasi ALIM Core terkait
---

# Review Fundamental Engineering — Integrasi ALIM Connect

> **Sifat pekerjaan:** audit dan review saja. Tidak ada perubahan source code, migration, endpoint, database, konfigurasi, atau refactor.
>
> **Status sistem:** ALIM Core dianggap **sistem produksi**. Stabilitas dan keselamatan data lebih penting daripada penambahan fitur.
>
> **Batasan klaim:** tidak ada pengujian runtime yang dijalankan pada review ini karena tidak ada implementasi API Connect. Seluruh pernyataan tentang perilaku sistem adalah hasil pembacaan source code, migration, route, model, observer, seeder, dan dokumen desain.

## 1. Executive Summary

### 1.1 Lingkup review

Review mencakup:

1. `docs/integration/alim-connect-api-design.md` — desain kontrak, autentikasi, sinkronisasi, tombstone, audit, versioning, dan test plan.
2. `docs/README.md` — **tidak ditemukan** pada repository.
3. Implementasi aktual ALIM Core yang relevan: `work_units`, `schools`, `structural_positions`, `gtk_work_unit`, `structural_assignments`, `institution_decrees`, `gtk_additional_tasks`, `other_teacher_tasks`, observer permission, audit log, seeder, route integrasi POS/Recruitment, dan middleware API key.

### 1.2 Kesimpulan utama

**Desain layak dilanjutkan dengan prasyarat (conditional go), tetapi belum layak diimplementasikan sebagai satu paket penuh.**

- **Fase 1–2** (endpoint master read-only + snapshot + polling `updated_since`) dapat dimulai **setelah** kontrak diperbaiki dan data diverifikasi.
- **Fase 3** (`changes` + tombstone) **belum layak** sampai `organization_change_log` dan kebijakan delete disetujui serta diimplementasikan.
- **Tidak layak** mengimplementasikan API yang bergantung pada permission snapshot Core, atau mengubah assignment/permission existing, sebelum bug observer dan konsistensi assignment diselesaikan.
- **Tidak layak** menjalankan seeder atau operasi destruktif apa pun terhadap data produksi.

### 1.3 Temuan paling kritis

| Peringkat | Temuan | Dampak | Status terhadap API |
|---|---|---|---|
| 1 | `JenisGtkSeeder` menghapus `structural_positions` dan FK assignment memakai `cascadeOnDelete` | Seluruh assignment struktural dapat hilang; UUID posisi berubah | **Wajib guardrail sebelum deployment** |
| 2 | Delete SK (`InstitutionDecree`) dan delete sekolah memicu cascade ke assignment/akademik | Kehilangan data assignment dan akademik permanen | **Wajib dimitigasi sebelum API dipakai** |
| 3 | `PermissionRebuildObserver` tidak menangani `StructuralAssignment`/`HomeroomAssignment`/`CoordinatorAssignment` | Permission snapshot bisa basi; akses user salah | **Wajib diperbaiki sebelum mengandalkan permission Core** |
| 4 | `gtk_work_unit` tidak punya status/masa berlaku/tombstone | Lifecycle penempatan tidak dapat ditentukan | **Harus diputuskan sebelum Fase 2** |
| 5 | Tidak ada `organization_change_log` | `changes`/tombstone tidak andal | **Menghalangi Fase 3** |
| 6 | Soft delete/hard delete tidak konsisten | Histori dan tombstone hilang | **Menghalangi Fase 3** |
| 7 | `parent_id`/`divisi_id`/`domain_id` tidak konsisten | Hierarki, filter, dan domain permission salah | **Verifikasi data sebelum Fase 1** |

### 1.4 Yang sudah baik

- Pemisahan API Connect dari POS/Recruitment melalui prefix dan key terpisah adalah keputusan yang tepat.
- Desain menggunakan UUID dan ID baris assignment, bukan teks `jabatan`.
- Desain sudah membedakan unit, sekolah, jabatan, penempatan, assignment struktural, dan tugas tambahan.
- Envelope `ApiResponse` dan pola middleware integrasi existing dapat digunakan kembali secara aditif.
- Desain sudah mengakui bahwa `changes`/tombstone memerlukan change ledger.
- Matriks PII sudah membatasi data sensitif secara eksplisit.

### 1.5 Yang belum dapat diklaim

- **Belum ada bukti** bahwa endpoint Connect aman, benar, atau siap produksi; endpoint belum diimplementasikan.
- **Belum ada pengujian** unit, feature, contract, security, performance, atau regresi yang dijalankan untuk desain ini.
- **Belum ada jaminan** bahwa data produksi bersih dari masalah `parent_id`, `divisi_id`, dan `domain_id`.
- **Belum ada jaminan** bahwa permission Core konsisten setelah perubahan assignment.

### 1.6 Catatan dokumentasi

`docs/README.md` **tidak ada**. Repository memiliki banyak dokumen di `docs/`, tetapi tidak ada indeks pusat. Root `README.md` juga menyebut Laravel 10, sedangkan runtime aktual adalah Laravel 12.70.1. Ini bukan blocker API, tetapi merupakan risiko maintainability dan onboarding.

## 2. Hasil Fundamental Engineering Review

### 2.1 Single Source of Truth

**Kriteria:** setiap data master memiliki satu sumber kebenaran; tidak ada duplikasi otoritatif.

**Bukti dari desain:**

- §1.2 dan §1.4 menyatakan Core sebagai SSOT dan Connect hanya read model.
- §2.2 menetapkan UUID sebagai identifier utama.
- §3.6–§3.8 memisahkan penempatan, assignment struktural, dan tugas tambahan.

**Bukti dari kode:**

- `work_units`, `schools`, `structural_positions`, `structural_assignments`, `gtk_work_unit`, `gtk_additional_tasks`, dan `other_teacher_tasks` adalah tabel terpisah dengan peran berbeda.
- Namun “jabatan” memiliki beberapa representasi: `gtk_employments.jabatan`, `gtk_work_unit.jabatan`, `gtk_work_unit.position_id`, `structural_assignments.position_id`, dan teks tugas tambahan.

**Penilaian:** arah SSOT sudah benar, tetapi **precedence jabatan belum didefinisikan**. Desain mengekspos semua sumber tanpa aturan mana yang menang bila berbeda.

**Verdict: Cukup — perlu keputusan precedence.**

### 2.2 Separation of Concerns

**Kriteria:** HTTP, domain logic, persistence, autentikasi, audit, dan sinkronisasi dipisahkan.

**Bukti dari desain:**

- §1.2 memisahkan controller, `OrganizationReadService`, middleware auth, dan ledger.
- §5.1 memisahkan auth integrasi dari auth pengguna.
- §7.3 memisahkan audit internal dari feed integrasi.
- §6 memisahkan snapshot, changes, dan reconciliation.

**Bukti dari kode:**

- Pola existing `IntegrationController` + `ApiResponse` + middleware API key sudah memisahkan HTTP dari domain.
- `VerifyIntegrationApiKey` adalah abstract base; POS dan Recruitment memakai subclass. Ini mendukung penambahan subclass Connect tanpa mengubah yang existing.

**Penilaian:** desain sudah baik, tetapi `OrganizationReadService` belum ada dan belum ada kontrak internal untuk transformasi entity. Risiko: logika query tersebar di controller.

**Verdict: Baik — pastikan service layer benar-benar dibuat, bukan hanya disebut.**

### 2.3 Domain Integrity dan Relational Integrity

**Kriteria:** entitas dan relasi merepresentasikan domain; FK/unique/nullable/cascade sesuai aturan bisnis.

**Bukti dari kode:**

- `schools.work_unit_id` FK wajib, tetapi **tidak unique** → one-to-many.
- `WorkUnit::school()` adalah `hasOne` (`app/Models/WorkUnit.php:248`), sedangkan `WorkUnit::schools()` adalah `hasMany` (`:128`).
- `structural_assignments.position_id`, `school_id`, `academic_year_id`, dan `decree_id` memakai `cascadeOnDelete`.
- `gtk_work_unit.position_id` memakai `nullOnDelete`.
- `work_units.parent_id` tidak memiliki FK.
- `work_units.divisi_id` dan `domain_id`, serta `structural_positions.domain_id`, memakai `nullOnDelete`.

**Penilaian:** domain model sudah dipisahkan, tetapi **integritas relasi destruktif** belum aman: menghapus posisi/SK/sekolah dapat menghapus assignment. Desain API tidak memperbaiki ini; API hanya membaca.

**Verdict: Berisiko — integritas referensial ada, tetapi cascade menghadirkan risiko kehilangan data.**

### 2.4 Data Consistency dan Lifecycle Management

**Kriteria:** status, masa berlaku, dan histori konsisten untuk semua assignment.

**Bukti dari kode:**

- `structural_assignments` memiliki `status`, `start_date`, `end_date`, `deleted_at`, dan scope `active()` (`app/Models/StructuralAssignment.php:69`).
- `gtk_work_unit` hanya memiliki `user_id`, `work_unit_id`, `jabatan`, `position_id`, `is_primary`, dan timestamps — **tanpa status/tanggal/deleted_at** (`2026_02_10_010955_create_gtk_work_unit_table.php:11`).
- `gtk_additional_tasks` memakai `tmt`/`tst`; `other_teacher_tasks` memakai `is_active`.
- `gtk_employments` memakai `status_kepegawaian`, tetapi `STATUS_LABELS` tidak memuat `Berhenti`, `Wafat`, `Pensiun`.

**Penilaian:** lifecycle hanya kuat pada assignment struktural. Penempatan unit dan sebagian tugas tidak memiliki lifecycle formal. Ini membuat desain §3.6 harus memakai “baris hilang = berakhir”, yang lemah untuk audit dan sinkronisasi.

**Verdict: Belum konsisten — perlu keputusan sebelum Fase 2.**

### 2.5 Security by Design dan Least Privilege

**Kriteria:** autentikasi, otorisasi, scope, secret, rate limit, audit, dan minimisasi data.

**Bukti dari kode:**

- `VerifyIntegrationApiKey` memakai `hash_equals`, IP allowlist, HTTPS di produksi, rate limit auth gagal, dan `Cache-Control: no-store` (`app/Http/Middleware/VerifyIntegrationApiKey.php:50–116`).
- `IntegrationApiConfig` mendukung key terenkripsi dan setting per prefix (`app/Support/IntegrationApiConfig.php`).
- POS dan Recruitment hanya mendaftarkan route `GET` (`routes/api.php:449–495`).

**Bukti dari desain:**

- §5 mengusulkan key `connect`, read-only, IP allowlist, rate limit, dan audit akses.
- §8 membatasi PII.

**Penilaian:** fondasi keamanan baik, tetapi:

1. key bersifat all-or-nothing (tidak ada scope per entity);
2. `IntegrationApiConfig` hanya mendukung satu key per prefix;
3. audit akses sukses belum ada (hanya kegagalan auth);
4. `LogConnectAccess` masih usulan, belum ada.

**Verdict: Baik dengan catatan — jangan klaim aman sebelum key lifecycle dan audit akses diuji.**

### 2.6 Backward Compatibility

**Kriteria:** tidak merusak POS/Recruitment, route existing, dan perilaku modul.

**Bukti dari kode:**

- POS/Recruitment route group existing terpisah (`routes/api.php:449`, `:478`).
- Middleware alias existing terdaftar di `app/Http/Kernel.php:165–166`.
- `IntegrationApiConfig` bersifat generik; penambahan prefix `connect` tidak mengubah perilaku prefix lain.

**Bukti dari desain:**

- §5.7 dan §11.5 menyatakan POS/Recruitment tidak diubah.
- §9 menetapkan versioning.

**Penilaian:** penambahan route/config baru bersifat aditif. Risiko utama bukan pada API, tetapi pada perubahan observer/assignment yang diusulkan di fase lain.

**Verdict: Baik — pertahankan larangan mengubah middleware dan route existing.**

### 2.7 Idempotency dan Concurrency

**Kriteria:** sinkronisasi aman terhadap duplikat, retry, dan perubahan bersamaan.

**Bukti dari desain:**

- §6.5 menetapkan upsert berdasarkan `external_id`, `source_updated_at`, dan `payload_hash`.
- §6.4 menetapkan cursor dan peringatan commit order.
- §6.9 menetapkan retry, lock, dan rekonsiliasi.

**Bukti dari kode/implementasi:**

- `ApiResponse::paginated()` hanya mendukung pagination halaman (`page`, `per_page`, `total`, `last_page`), bukan cursor (`app/Support/ApiResponse.php:90`).
- Belum ada implementasi cursor/idempotency.

**Penilaian:** desain sudah memikirkan idempotency, tetapi implementasi cursor memerlukan helper baru. Tanpa ledger, cursor berbasis `updated_at` rentan terhadap event yang commit tidak berurutan.

**Verdict: Cukup — desain benar, implementasi belum ada.**

### 2.8 Scalability dan Performance

**Kriteria:** pagination, index, streaming, query efisien, tidak N+1.

**Bukti dari desain:**

- §3.9 mendukung `ndjson`.
- §6.12 menyarankan index `(updated_at, id)`.
- §10.7 menetapkan target performa.

**Bukti dari kode:**

- `IntegrationController::perPage` membatasi 1–200 (`app/Http/Controllers/Api/Shared/IntegrationController.php`).
- `SatuanKerjaController` memakai `withCount` dan eager loading; pola ini bisa ditiru.
- `people.school_id` union (`gtk_work_unit` + `gtk_employments`) berpotensi mahal dan duplikat.

**Penilaian:** desain scalable secara arah, tetapi belum ada pengukuran. Snapshot JSON penuh berisiko memory besar.

**Verdict: Perlu validasi performa sebelum produksi.**

### 2.9 Observability dan Auditability

**Kriteria:** perubahan dapat dilacak; kegagalan dapat didiagnosis.

**Bukti dari kode:**

- `audit_logs` memiliki `action`, `table_name`, `record_id`, `record_type`, `request_id`, `metadata` (`app/Models/AuditLog.php`).
- Hanya `work_units` yang menulis `AuditLog` eksplisit (`app/Models/WorkUnit.php:200–222`).
- `divisis`, `domains` tidak memiliki audit; `schools`, `structural_positions`, `gtk_work_unit`, `structural_assignments` tidak memiliki audit.
- `PermissionRebuildObserver` tidak menangani assignment (lihat §3).
- `integration_event_log` ada, tetapi dipakai event bus internal, bukan feed perubahan.

**Penilaian:** observability belum memadai untuk integrasi. Tanpa ledger, Connect tidak dapat membedakan “tidak berubah” dan “hilang”.

**Verdict: Belum siap — gap kritis.**

### 2.10 Maintainability dan Extensibility

**Kriteria:** kontrak jelas, versioning, dokumentasi, test, dan mudah dikembangkan.

**Bukti dari desain:**

- §9 versioning, §10 test plan, §11 decisions.
- §3.11 ringkasan endpoint.

**Bukti dari repository:**

- `docs/README.md` **tidak ada**.
- Root `README.md` menyebut Laravel 10, sedangkan runtime Laravel 12.70.1.
- Dokumen integrasi berada di `docs/integration/` tanpa indeks.
- Belum ada JSON Schema.

**Penilaian:** desain cukup rinci, tetapi dokumentasi repository tidak terindeks dan ada drift versi.

**Verdict: Cukup — perlu indeks dokumentasi dan schema contract.**

### 2.11 Failure Handling dan Recovery

**Kriteria:** kegagalan sinkronisasi tidak menghilangkan perubahan; ada retry, cursor recovery, rekonsiliasi, backup.

**Bukti dari desain:**

- §6.9 retry/backoff/lock; §6.8 rekonsiliasi; §7.5 fail-closed audit.
- §10 menguji cursor kedaluwarsa dan tombstone.

**Bukti dari kode:**

- Belum ada implementasi sync/ledger.
- `InstitutionDecreeController::destroy` dan `SchoolController::destroy` melakukan hard delete yang memicu cascade; tidak ada recovery otomatis.

**Penilaian:** desain failure handling benar secara konsep, tetapi bergantung pada ledger yang belum ada. Recovery data saat ini bergantung pada backup, bukan mekanisme aplikasi.

**Verdict: Cukup secara desain; belum terbukti secara implementasi.**

## 3. Critical Findings

### 3.1 Skala keparahan

| Level | Definisi |
|---|---|
| **Critical** | Dapat menyebabkan kehilangan data, downtime, atau eskalasi hak akses secara diam-diam di produksi. Harus dimitigasi sebelum pekerjaan terkait. |
| **High** | Dapat menyebabkan scope/kewenangan salah, sinkronisasi tidak lengkap, atau audit gagal. Harus diselesaikan sebelum fase terkait. |
| **Medium** | Dapat menyebabkan data tidak akurat atau maintainability buruk. Selesaikan sebelum produksi. |
| **Low** | Dokumentasi/cleanup; tidak memblokir. |

### 3.2 F-01 — `JenisGtkSeeder` menghapus `structural_positions` dengan cascade

**Kondisi aktual:**

- `database/seeders/JenisGtkSeeder.php:17` memanggil `StructuralPosition::query()->delete()` sebelum mengisi ulang.
- `structural_assignments.position_id` adalah FK dengan `cascadeOnDelete()` (`database/migrations/2026_02_14_135020_create_structural_assignments_table.php:19–21`).
- `gtk_work_unit.position_id` memakai `nullOnDelete()` (`2026_10_29_180100_add_position_id_to_gtk_work_unit_table.php:22–25`).
- `gtk_employments.jabatan_id` dan `gtk_position_proposals.proposed_position_id` dialihkan ke `structural_positions` dengan `set null` (`2026_09_03_154500_merge_positions_to_structural_positions_table.php`).
- Seeder membuat ulang posisi dengan `updateOrCreate` berdasarkan `code`, tetapi karena semua baris dihapus lebih dulu, **UUID posisi berubah**.

**Risiko teknis:**

- Menjalankan seeder di produksi akan menghapus **seluruh `structural_assignments`**.
- Penempatan GTK kehilangan `position_id` (menjadi null).
- Permission domain yang bergantung pada posisi hilang.
- Snapshot/Connect akan melihat posisi dan assignment “hilang”, lalu membuat tombstone massal.
- Audit perubahan tidak lengkap karena bulk delete melewati model events.

**Dampak bisnis:**

- Kewenangan struktural (kepala asrama, kepala sekolah, wakil, koordinator) hilang.
- Pengguna kehilangan akses atau mendapatkan akses yang salah.
- Pemulihan hanya mungkin dari backup; tidak ada undo otomatis.

**Severity: Critical.**

**Rekomendasi:**

1. **Jangan jalankan seeder di produksi.** Tambahkan guard environment/konfirmasi pada seeder (perubahan terpisah, perlu persetujuan).
2. Backup penuh `structural_positions`, `structural_assignments`, `gtk_work_unit`, dan `gtk_employments` sebelum operasi apa pun.
3. Untuk jangka panjang, ubah seeder menjadi idempoten tanpa delete (updateOrCreate by code) — **tetapi jangan diubah pada tahap review ini**.
4. Verifikasi apakah seeder sudah pernah dijalankan di produksi; jika ya, audit assignment yang hilang.

**Memblokir API?**

- Tidak memblokir penulisan kode endpoint read-only **jika** seeder dijamin tidak dijalankan.
- **Memblokir deployment** sampai guardrail/CI dan backup tersedia.
- **Memblokir Fase 2/3** jika data assignment sudah tidak konsisten.

### 3.3 F-02 — `PermissionRebuildObserver` tidak menangani seluruh assignment

**Kondisi aktual:**

- `AuthorizationRuntimeServiceProvider` mendaftarkan `HomeroomAssignment`, `CoordinatorAssignment`, `StructuralAssignment`, `TeachingAssignment`, `Subject`, `SubjectGroup`, `GtkAdditionalTask` (`app/Authorization/Providers/AuthorizationRuntimeServiceProvider.php:143–146`).
- `PermissionRebuildObserver::dispatchForModel()` hanya memiliki cabang untuk `User`, `Permission`, `GTKEmployment`, `RevokedPermission`, `TeachingAssignment`, `GtkAdditionalTask`, `Subject`, `SubjectGroup`; sisanya `default => []` (`app/Authorization/Support/PermissionRebuildObserver.php:60–70`).
- `GtkWorkUnit` dan `OtherTeacherTask` tidak didaftarkan sebagai model yang diobservasi.

**Risiko teknis:**

- Perubahan `StructuralAssignment` (termasuk berakhirnya masa tugas) **tidak memicu** `BuildSnapshotJob`.
- `HomeroomAssignment` dan `CoordinatorAssignment` juga tidak memicu rebuild.
- Snapshot permission dapat basi sampai ada event lain yang memicu rebuild.
- `GtkEmploymentObserver` hanya bereaksi pada `jabatan_id`; perubahan assignment tanpa perubahan employment tidak memicu sinkronisasi role.

**Dampak bisnis:**

- Pengguna dapat mempertahankan akses setelah jabatan berakhir.
- Pengguna baru dapat tidak mendapatkan akses.
- Risiko pelanggaran least privilege dan audit.

**Severity: High (security-relevant).**

**Rekomendasi:**

1. Tambahkan cabang `StructuralAssignment`, `HomeroomAssignment`, `CoordinatorAssignment` di observer (menggunakan `user_id`/`teacher_id`).
2. Daftarkan `GtkWorkUnit` dan `OtherTeacherTask` bila perubahan tersebut memengaruhi permission.
3. Tambahkan regression test: assignment dibuat/diakhiri → snapshot berubah.
4. Lakukan sebagai perubahan terpisah dengan analisis dampak; **jangan mengubah pada review ini**.

**Memblokir API?**

- **Ya**, jika Connect mengandalkan permission efektif Core.
- **Tidak**, jika Connect hanya memakai assignment mentah untuk scope sendiri (sesuai §6.7 desain).
- Namun bug Core tetap harus diperbaiki untuk keamanan internal.

### 3.4 F-03 — `gtk_work_unit` tidak memiliki lifecycle status dan masa berlaku

**Kondisi aktual:**

- Tabel `gtk_work_unit` hanya memiliki `id`, `user_id`, `work_unit_id`, `jabatan`, `is_primary`, `position_id` (nullable), `timestamps`, dan unique `(user_id, work_unit_id)` (`2026_02_10_010955_create_gtk_work_unit_table.php:11–21`; `2026_10_29_180100...`).
- Tidak ada `status`, `start_date`, `end_date`, `deleted_at`, atau alasan berakhir.
- `gtk_work_unit_histories` hanya ditulis oleh alur tertentu (`PersonaliaController`, `GtkTransferRequest`, `CandidateConversionService`), bukan setiap perubahan.

**Risiko teknis:**

- Tidak dapat membedakan penempatan aktif, akan datang, berakhir, atau dibatalkan.
- Sinkronisasi hanya dapat menyimpulkan “baris hilang = berakhir”, yang tidak dapat membedakan delete, mutasi, atau kesalahan input.
- Snapshot tidak dapat merekonstruksi histori.
- Perubahan `is_primary` tidak memiliki audit.

**Dampak bisnis:**

- Scope unit Connect dapat mencakup orang yang sudah tidak ditempatkan, atau sebaliknya.
- Laporan histori penempatan tidak dapat diandalkan.
- Audit kepegawaian lemah.

**Severity: High.**

**Rekomendasi:**

1. Untuk Fase 1–2, dokumentasikan semantik: baris ada = aktif; baris hilang = berakhir; `created_at`/`updated_at` hanya metadata.
2. Jangan menambah kolom pada tabel existing tanpa persetujuan; opsi additive (`start_date` nullable, `end_date` nullable, `status` nullable) memerlukan migration dan backfill.
3. Sementara, gunakan `gtk_work_unit_histories` bila tersedia dan ledger baru untuk perubahan.
4. Connect harus menyimpan tombstone lokal dan tidak menganggap baris hilang sebagai “belum pernah ada”.

**Memblokir API?**

- **Ya untuk Fase 2** yang membutuhkan lifecycle penempatan.
- **Tidak untuk Fase 1** bila endpoint hanya membaca dan mendokumentasikan keterbatasan.

### 3.5 F-04 — Belum ada `organization_change_log`

**Kondisi aktual:**

- Tidak ada tabel/migration `organization_change_log`; pencarian hanya menemukan referensi di dokumen desain.
- `integration_event_log` ada (`2026_07_05_000001_create_integration_event_log_table.php`), tetapi memiliki semantik event bus: `event_name`, `source_module`, `target_module`, `status` (`processed|failed|retrying`), `attempt`, `payload`, `processed_at`. Ini bukan feed perubahan per-entity yang append-only.
- Tidak ada cursor/occurred_at contract yang dirancang untuk konsumen eksternal.

**Risiko teknis:**

- `changes` tidak dapat mendeteksi hard delete.
- Tidak ada jaminan urutan event.
- Tidak ada before/after yang seragam.
- Rekonsiliasi penuh menjadi satu-satunya fallback, tetapi mahal dan tidak real-time.

**Dampak bisnis:**

- Data Connect dapat basi tanpa terdeteksi.
- Kewenangan user bisa tidak dicabut setelah perubahan.
- Audit dan kepatuhan sulit dibuktikan.

**Severity: High (Critical untuk `changes`).**

**Rekomendasi:**

1. Jangan merilis endpoint `changes` sebelum ledger tersedia.
2. Ajukan migration additive `organization_change_log` (append-only), dengan index `(occurred_at, id)` dan `(entity_type, occurred_at, id)`.
3. Tulis event melalui observer/service, termasuk `deleted` sebelum hard delete.
4. Sediakan backfill tombstone dari snapshot/rekonsiliasi.
5. Untuk sementara, gunakan snapshot + `updated_since` + rekonsiliasi penuh, dan nyatakan `changes` sebagai **belum tersedia**.

**Memblokir API?**

- **Menghalangi Fase 3.**
- Tidak menghalangi Fase 1 (master + snapshot) dan Fase 2 (polling).

### 3.6 F-05 — Soft delete dan hard delete tidak konsisten

**Kondisi aktual:**

- Soft delete: `WorkUnit` (`use HasFactory, LogsDeletion, SoftDeletes`), `Divisi`, `Domain`, `StructuralAssignment`, `User`, `DormitoryStaffAssignment`, `RoomSupervisor`.
- Hard delete (tanpa `SoftDeletes`): `School`, `StructuralPosition`, `GtkWorkUnit`, `GtkEmployment`, `GtkAdditionalTask`, `OtherTeacherTask`, `AdditionalTaskType`, `JenisGtk`.
- `InstitutionDecree` memiliki kolom `deleted_at` (`2026_02_14_135017...:44–45`) tetapi model **tidak** memakai `SoftDeletes`; `delete()` akan hard delete.
- `SchoolController::destroy()` memanggil `$school->delete()` tanpa soft delete (`app/Http/Controllers/SchoolController.php:492–511`).
- `MasterDataController::jabatanDestroy()` memanggil `StructuralPosition::findOrFail($id)->delete()` (`app/Http/Controllers/MasterDataController.php:155–160`).
- `InstitutionDecreeController::destroy()` menghapus `OtherTeacherTask` dan `TeachingAssignment`, lalu `$decree->delete()` (`app/Http/Controllers/InstitutionDecreeController.php:827–837`).

**Risiko teknis:**

- Hard delete tidak menghasilkan tombstone.
- Cascade dapat menghapus assignment/akademik terkait.
- Connect tidak dapat membedakan “dihapus” dan “belum pernah ada”.
- Audit hilang karena baris sudah tidak ada.

**Dampak bisnis:**

- Kehilangan data permanen.
- Scope Connect salah karena record hilang tanpa sinyal.
- Kesulitan pemulihan dan audit.

**Severity: High; beberapa jalur Critical (lihat F-11–F-13).**

**Rekomendasi:**

1. Jangan menambah soft delete secara massal pada tabel existing (mengubah perilaku dan query).
2. Lindungi jalur hard delete dengan guard, konfirmasi, dan larangan untuk data yang memiliki assignment.
3. Tambahkan change ledger yang menangkap `deleting` sebelum hard delete.
4. Untuk jangka panjang, evaluasi soft delete selektif pada tabel kritis dengan migration additive dan backfill.
5. Connect wajib memakai rekonsiliasi sampai ledger tersedia.

**Memblokir API?**

- **Menghalangi Fase 3** (tombstone).
- Fase 1 snapshot dapat berjalan, tetapi tanpa jaminan deteksi delete.

### 3.7 F-06 — Integritas `parent_id`, `divisi_id`, dan `domain_id`

**Kondisi aktual:**

- `work_units.parent_id` adalah `uuid nullable` **tanpa FK** (`2026_02_10_010813_create_work_units_table.php:16`).
- `WorkUnitSeeder` tidak menyetel `parent_id` sama sekali (pencarian tidak menemukan `parent_id` di seeder).
- `DivisiSeeder` memakai kode `PAH-SP`, `PAH-PNG`, dst. (13 baris), sedangkan `WorkUnitSeeder` mencari `SP`, `PNG`, dst. (`database/seeders/WorkUnitSeeder.php:14`).
- `work_units.divisi_id` dan `domain_id` memakai `nullOnDelete` (`2026_04_25_000003...`, `2026_09_18_000001...`).
- `structural_positions.domain_id` memakai `nullOnDelete` (`2026_09_16_000003...`).
- `StructuralPosition::$fillable` tidak memuat `domain_id` (`app/Models/StructuralPosition.php:14–25`), sementara `JenisGtkSeeder` mengirim `'domain_id' => $domainId` dalam `updateOrCreate()` (`database/seeders/JenisGtkSeeder.php:374–386`).

**Risiko teknis:**

- Pohon unit bisa datar karena `parent_id` null.
- `divisi_id` bisa null pada data hasil seeder.
- Domain posisi bisa null karena mass-assignment mengabaikan `domain_id`.
- Permission berbasis domain tidak terbentuk.
- Filter `divisi_id`/`domain_id` di API dapat mengembalikan hasil kosong.

**Dampak bisnis:**

- Scope unit/hierarki tidak sesuai struktur nyata.
- Kewenangan berbasis domain tidak berjalan.
- Laporan dan integrasi menghasilkan data yang menyesatkan.

**Severity: High untuk hierarki dan permission; Medium untuk list dasar.**

**Rekomendasi:**

1. Lakukan **audit data read-only** di produksi: hitung `parent_id` null, `divisi_id` null, `domain_id` null.
2. Jangan menjalankan ulang seeder untuk “memperbaiki”; gunakan backfill terkontrol dengan persetujuan.
3. API harus mengekspos null secara jujur dan tidak menginfer hierarki.
4. Perbaiki `$fillable`/seeder di masa depan sebagai perubahan terpisah (bukan sekarang).
5. Dokumentasikan bahwa domain posisi nullable.

**Memblokir API?**

- **Ya untuk fitur hierarki/domain-dependent** sampai data diverifikasi.
- Fase 1 list dapat berjalan dengan field nullable.

### 3.8 F-07 — `WorkUnit::school()` tidak sesuai kardinalitas database

**Kondisi aktual:**

- `schools.work_unit_id` adalah FK wajib **tanpa unique** (`2026_02_14_094120_create_schools_table.php:13–15`), sehingga one-to-many.
- `WorkUnit::schools()` adalah `hasMany` (`app/Models/WorkUnit.php:128–131`).
- `WorkUnit::school()` adalah `hasOne` (`app/Models/WorkUnit.php:248–251`).
- POS `SatuanKerjaController` memakai `school` tunggal dalam format response.

**Risiko teknis:**

- Satu unit dengan beberapa sekolah hanya menampilkan satu sekolah.
- Connect dapat kehilangan sekolah operasional.
- Relasi scope menjadi tidak lengkap.

**Dampak bisnis:**

- Data sekolah tidak lengkap di Connect.
- Laporan per unit tidak akurat.

**Severity: Medium.**

**Rekomendasi:**

1. Endpoint Connect **wajib** memakai `schools: []`, bukan `school`.
2. Jangan mengubah `WorkUnit::school()` existing (POS bergantung padanya).
3. Tambahkan test unit dengan dua sekolah dalam satu unit.

**Memblokir API?**

- **Tidak**, karena desain §3.2 sudah memakai array `schools`. Pastikan implementasi tidak memakai `hasOne`.

### 3.9 F-08 — Konsistensi assignment dan permission snapshot

**Kondisi aktual:**

- `StructuralAssignment::scopeActive()` memakai `status = active`, `start_date <= today`, dan `end_date` null/≥ today (`app/Models/StructuralAssignment.php:69–76`).
- `GtkEmploymentObserver::syncRoles()` memilih **assignment aktif pertama** tanpa urutan/prioritas (`app/Observers/GtkEmploymentObserver.php:85–110`).
- `PermissionRebuildObserver` tidak menangani assignment (F-02).
- Satu user dapat memiliki banyak assignment pada sekolah/tahun ajaran berbeda; unique key mencakup `position_id`, `school_id`, `academic_year_id`.

**Risiko teknis:**

- Role user dapat ditentukan oleh assignment yang kebetulan pertama, bukan yang seharusnya.
- Assignment berakhir tidak memicu rebuild.
- Permission snapshot per `scope_key` dapat tidak sesuai dengan assignment.
- Multi-school assignment dapat memberi akses lintas sekolah yang tidak diinginkan.

**Dampak bisnis:**

- Privilege escalation atau denial of access.
- Ketidaksesuaian antara jabatan dan akses.
- Audit sulit.

**Severity: High.**

**Rekomendasi:**

1. Connect **tidak** boleh mengandalkan snapshot permission Core; hitung scope dari `structural_assignments` + `gtk_work_unit`.
2. Definisikan precedence assignment (mis. skor domain/posisi/primary) secara eksplisit.
3. Perbaiki observer dan `GtkEmploymentObserver` secara terpisah dengan test multi-assignment.
4. Tambahkan invariant test: assignment aktif ↔ domain/permission yang diharapkan.

**Memblokir API?**

- **Ya**, jika API mengekspos “permission efektif” atau Connect memakainya untuk otorisasi.
- **Tidak**, jika Connect hanya memakai assignment mentah; namun bug Core tetap risiko produksi.

### 3.10 F-09 — Potensi kehilangan histori perubahan

**Kondisi aktual:**

- `audit_logs` hanya ditulis eksplisit oleh `WorkUnit`, `GtkProfile`, `GtkTransferRequest`, `User` (beberapa aksi), dan beberapa modul lain; bukan semua entity organisasi.
- `divisis`, `domains`, `schools`, `structural_positions`, `gtk_work_unit`, `structural_assignments`, `gtk_employments`, `gtk_additional_tasks`, `other_teacher_tasks` tidak memiliki audit organisasi yang lengkap.
- `gtk_work_unit_histories` hanya mencatat `ASSIGN|TRANSFER|REMOVE` pada alur tertentu.
- `structural_assignments` menyimpan status/tanggal, tetapi tidak menyimpan siapa mengubah, alasan, atau nilai sebelum/sesudah.
- `InstitutionDecree` memakai `LogsDeletion` (activity log) tetapi tanpa `SoftDeletes`.

**Risiko teknis:**

- Tidak dapat menjawab “siapa mengubah apa, kapan, dari nilai apa”.
- Perubahan assignment yang salah tidak dapat direkonstruksi.
- Sinkronisasi tidak dapat memverifikasi gap.

**Dampak bisnis:**

- Audit internal/eksternal gagal.
- Investigasi insiden sulit.
- Pemulihan data tidak presisi.

**Severity: High.**

**Rekomendasi:**

1. Tambahkan observer audit untuk entity organisasi yang belum tercakup (fase terpisah).
2. Gunakan `audit_logs.metadata` untuk before/after yang sudah diredaksi.
3. Tambahkan `organization_change_log` untuk feed integrasi.
4. Jangan mengandalkan `activity_log` sebagai satu-satunya sumber.

**Memblokir API?**

- **Ya untuk fitur histori/audit**; tidak untuk sekadar membaca state saat ini.

### 3.11 F-10 — Konsistensi snapshot, incremental sync, dan tombstone

**Kondisi aktual/desain:**

- §3.9 mendefinisikan `snapshot_at`; §3.10 mendefinisikan `changes`; §7.3 mengusulkan ledger.
- Saat ini tidak ada ledger, tidak ada cursor, dan tidak ada tombstone.
- `ApiResponse::paginated()` tidak mendukung cursor (`app/Support/ApiResponse.php:90`).
- Sebagian entity hard delete; sebagian soft delete.
- `updated_at` dapat tidak berubah pada operasi bulk yang melewati Eloquent.

**Risiko teknis:**

- Snapshot dan changes dapat saling tumpang tindih atau kehilangan event.
- Cursor berbasis `updated_at` dapat melewatkan event yang commit tidak berurutan.
- Hard delete tidak terlihat.
- Connect dapat menghapus data yang salah jika tombstone salah diinterpretasikan.

**Dampak bisnis:**

- Scope user salah; akses tidak dicabut/diberikan.
- Data historis hilang.
- Kepercayaan pada integrasi menurun.

**Severity: Critical untuk sinkronisasi.**

**Rekomendasi:**

1. Terapkan ledger dengan `id` monotonic + `occurred_at`; cursor utama memakai `id`.
2. Snapshot memakai `snapshot_at`; changes memakai `since` dengan overlap.
3. Idempotent upsert + payload hash + tombstone lokal.
4. Rekonsiliasi berkala dan alert drift.
5. Jangan merilis `changes` sebelum invariant test lulus.

**Memblokir API?**

- **Ya untuk Fase 3.**
- Fase 1 snapshot + Fase 2 polling dapat berjalan dengan dokumentasi keterbatasan.

### 3.12 F-11 — Hard delete SK memicu cascade ke assignment (temuan tambahan)

**Kondisi aktual:**

- `structural_assignments.decree_id`, `teaching_assignments.decree_id`, `homeroom_assignments.decree_id`, dan `coordinator_assignments.decree_id` semuanya memakai `cascadeOnDelete()` (`2026_02_14_135020...:31–33`, `...135142...:15–17`, `...135143...:15–17`, `...135144...:15–17`).
- `InstitutionDecree` memiliki kolom `deleted_at` tetapi **tidak** memakai `SoftDeletes`; `delete()` adalah hard delete.
- `InstitutionDecreeController::destroy()` juga menghapus `OtherTeacherTask` dan `TeachingAssignment` secara eksplisit sebelum `$decree->delete()` (`app/Http/Controllers/InstitutionDecreeController.php:827–837`).

**Risiko teknis:**

- Menghapus satu SK dapat menghapus seluruh assignment struktural, mengajar, wali kelas, dan koordinator yang terkait.
- `OtherTeacherTask` dan `TeachingAssignment` dihapus eksplisit, lalu cascade menghapus sisanya.
- Tidak ada tombstone/audit yang memadai.

**Dampak bisnis:**

- Kehilangan data assignment massal hanya karena menghapus SK.
- Kewenangan pengguna hilang.
- Pemulihan membutuhkan backup.

**Severity: Critical.**

**Rekomendasi:**

- Jangan mengubah FK existing pada tahap ini.
- Tambahkan guard: SK yang memiliki assignment harus **diarsipkan** (`status=archived`) atau ditolak untuk dihapus.
- Audit jumlah assignment sebelum delete.
- Backup dan prosedur pemulihan.
- Jadikan prioritas sebelum API Connect mengekspos SK/assignment.

**Memblokir API?**

- Tidak memblokir endpoint read-only, tetapi **memblokir operasi delete** dan **memblokir klaim integritas data** sampai guard tersedia.

### 3.13 F-12 — Hard delete sekolah memicu cascade akademik dan assignment

**Kondisi aktual:**

- `schools.work_unit_id` cascade ke `work_units` (`2026_02_14_094120...:13`).
- `academic_years.school_id`, `grade_levels.school_id`, `study_groups.school_id/academic_year_id/grade_level_id`, dan `structural_assignments.school_id` memakai cascade.
- `teaching_assignments.school_id`, `homeroom_assignments.school_id`, `coordinator_assignments.school_id`, dan `gtk_cross_unit_assignments.school_id` memakai cascade.
- `SchoolController::destroy()` memanggil `$school->delete()` tanpa guard dependensi (`app/Http/Controllers/SchoolController.php:492–511`).
- `School` tidak memakai `SoftDeletes`.

**Risiko teknis:**

- Menghapus sekolah dapat menghapus tahun ajaran, rombel, assignment struktural, penugasan mengajar, wali kelas, koordinator, dan cross-unit.
- `gtk_employments.school_id` hanya nullOnDelete, sehingga data kepegawaian kehilangan konteks.

**Dampak bisnis:**

- Kehilangan data akademik dan kewenangan.
- Operasional sekolah lain dapat terdampak jika data guru bersama.
- Audit sulit.

**Severity: Critical.**

**Rekomendasi:**

- Guard: tolak delete bila ada tahun ajaran/rombel/assignment.
- Arsipkan sekolah (`is_active=false`) alih-alih delete.
- Audit dependensi sebelum delete.
- Jangan mengubah FK existing; tambahkan validasi di service/controller (perubahan terpisah, persetujuan).

**Memblokir API?**

- Tidak memblokir read-only; memblokir klaim bahwa data aman dari kehilangan.

### 3.14 F-13 — Hard delete jabatan memicu cascade assignment

**Kondisi aktual:**

- `MasterDataController::jabatanDestroy()` memanggil `StructuralPosition::findOrFail($id)->delete()` (`app/Http/Controllers/MasterDataController.php:155–160`).
- `structural_assignments.position_id` cascadeOnDelete.
- `gtk_work_unit.position_id` nullOnDelete.
- `StructuralPosition` tidak memakai `SoftDeletes`.

**Risiko teknis:**

- Menghapus satu jabatan menghapus assignment struktural yang memakainya.
- Penempatan GTK kehilangan `position_id` tanpa audit.
- Permission domain yang terkait hilang.

**Dampak bisnis:**

- Kewenangan hilang; data historis tidak dapat direkonstruksi.
- Risiko salah hapus dari UI master data.

**Severity: Critical.**

**Rekomendasi:**

- Tolak delete bila `structural_assignments` atau `gtk_work_unit` masih mereferensikan posisi.
- Gunakan `is_active=false` untuk menonaktifkan jabatan.
- Audit dan backup sebelum perubahan.
- Jangan mengubah relasi existing pada tahap ini.

**Memblokir API?**

- Tidak memblokir read-only; memblokir klaim integritas.

### 3.15 F-14 — Hard delete tahun ajaran memicu cascade akademik dan assignment

**Kondisi aktual:**

- `AcademicYearController::destroy()` memanggil `$academicYear->delete()` (`app/Http/Controllers/AcademicYearController.php:126–133`).
- `academic_years` tidak memiliki soft delete.
- `study_groups.academic_year_id`, `structural_assignments.academic_year_id`, `teaching_assignments.academic_year_id`, `homeroom_assignments.academic_year_id`, dan `coordinator_assignments.academic_year_id` memakai cascade.

**Risiko teknis:**

- Menghapus tahun ajaran menghapus rombel dan assignment terkait.
- Data historis akademik hilang.
- Snapshot/Connect melihat penghapusan massal tanpa tombstone.

**Dampak bisnis:**

- Kehilangan data akademik dan kewenangan.
- Audit dan pelaporan historis rusak.

**Severity: Critical.**

**Rekomendasi:**

- Gunakan arsip/lock (mekanisme `isLocked()` sudah ada) alih-alih delete.
- Tolak delete bila ada rombel/assignment.
- Backup dan audit.
- Jangan mengubah skema pada tahap ini.

**Memblokir API?**

- Tidak memblokir read-only; memblokir klaim data integrity dan `changes`.

## 4. Architecture Risks

### 4.1 Register risiko arsitektur

| ID | Risiko | Bukti | Dampak | Kemungkinan | Mitigasi |
|---|---|---|---|---|---|
| AR-01 | API Connect bergantung pada data yang belum bersih | `parent_id` null, `divisi_id` mismatch, `domain_id` null (F-06) | Scope/hierarki salah | Tinggi | Verifikasi data sebelum Fase 1; expose null; jangan infer |
| AR-02 | `changes` dirancang sebelum ledger ada | §3.10 vs §7.3/§11.3 | Klien mengira fitur tersedia | Tinggi | Tandai `changes` sebagai roadmap, bukan fitur v1 |
| AR-03 | Static API key all-or-nothing | `VerifyIntegrationApiKey` tidak punya scope | Blast radius besar | Sedang | Key khusus, IP, rate limit, rotasi, audit |
| AR-04 | `ApiResponse` tidak mendukung cursor/`server_time` | `app/Support/ApiResponse.php:90–115` | Implementasi response menyimpang | Tinggi | Helper baru khusus Connect tanpa mengubah existing |
| AR-05 | `people.school_id` union menimbulkan duplikat/ambigu | §3.5 | Data ganda; scope salah | Sedang | Dedupe by user; sertakan `sources`; definisi prioritas |
| AR-06 | `position_scope=any` menimbulkan duplikat/ambigu | §3.5 | Assignment ganda; urutan tidak jelas | Sedang | Definisikan precedence; sertakan `source` per item |
| AR-07 | `additional_tasks` dua sumber | §3.8; `gtk_additional_tasks` vs `other_teacher_tasks` | Kunci ID tabrakan | Sedang | Wajib `(source, id)`; validasi di kontrak |
| AR-08 | Snapshot penuh membebani server | §3.9 | Timeout/OOM | Sedang | `ndjson`, `types`, limit, off-peak, queue |
| AR-09 | Cursor `updated_at` dapat melewatkan event | §6.4; commit order | Data drift | Sedang | Ledger `id` monotonic; overlap window; rekonsiliasi |
| AR-10 | Hard delete tanpa tombstone | F-05, F-11–F-14 | Kehilangan data di Connect | Tinggi | Ledger `deleting`; rekonsiliasi; guard delete |
| AR-11 | `updated_at` tidak berubah pada bulk update | §6.2 | Perubahan tidak tersinkron | Sedang | Wajib set `updated_at`; audit query builder |
| AR-12 | Dokumentasi tidak terindeks | `docs/README.md` tidak ada; root README menyebut Laravel 10 | Onboarding/maintainability | Tinggi | Tambahkan indeks dokumentasi (terpisah) |
| AR-13 | Timezone/`today()` tidak konsisten | `active()` memakai `today()`; API memakai ISO-8601 | Assignment efektif berbeda 1 hari | Sedang | Definisikan timezone dan boundary di kontrak |
| AR-14 | Static key tidak bisa dual-key | `IntegrationApiConfig` satu key | Rotasi berisiko downtime | Sedang | Dukung previous key atau rotasi maintenance |
| AR-15 | Endpoint Connect dapat membebani DB produksi | Query kompleks, snapshot | Gangguan Core | Sedang | Read replica (jika ada), cache, rate limit, off-peak |
| AR-16 | Perubahan observer/audit dapat memengaruhi Core | F-02, §7 | Permission/downtime | Sedang | Perubahan terpisah, feature flag, regression test |

### 4.2 Risiko yang paling perlu perhatian arsitek

1. **Ketergantungan pada data historis yang tidak lengkap.** Desain mengasumsikan assignment dapat direkonstruksi; kenyataannya `gtk_work_unit` tidak punya lifecycle dan banyak entity hard delete. Solusi: ledger + rekonsiliasi, bukan asumsi.
2. **Dua sumber “kebenaran” untuk jabatan.** `employment.jabatan`, `gtk_work_unit.position_id`, dan `structural_assignments.position_id` dapat berbeda. Tanpa precedence, Connect akan menampilkan jabatan yang tidak konsisten.
3. **API dan Core berbagi tabel produksi.** Endpoint read-only tetap membebani DB. Perlu index, limit, dan pemisahan beban.
4. **Kegagalan Connect tidak boleh mengganggu Core.** Desain sudah read-only; pastikan tidak ada callback/write ke Core dan tidak ada shared session.
5. **Perubahan pada Core untuk kebutuhan Connect.** Larangan sudah jelas: jangan ubah struktur existing; jika tidak cukup, ajukan alternatif.

### 4.3 Risiko dokumentasi

- `docs/README.md` tidak ditemukan. Tidak ada indeks yang menjelaskan dokumen mana yang otoritatif.
- Root `README.md` menyebut Laravel 10, sedangkan runtime Laravel 12.70.1. Ini indikasi drift dokumentasi.
- Dokumen audit dan desain berada di `docs/integration/`, tetapi belum ada `CHANGELOG.md` atau schema index.
- Risiko: developer Connect membaca dokumen lama atau salah versi.

**Rekomendasi:** tambahkan indeks dokumentasi dan status (draft/approved/deprecated) pada fase dokumentasi, bukan pada implementasi API.

## 5. Data Integrity Review

### 5.1 Identifier

| Entitas | Primary key | Natural key | Stabilitas | Catatan |
|---|---|---|---|---|
| `work_units` | `id` UUID | `code` unique | UUID stabil; `code` dapat diubah | `code` dibuat dari prefix divisi |
| `schools` | `id` UUID | `npsn` unique | UUID stabil; NPSN eksternal | Tidak ada soft delete |
| `divisis` | `id` UUID | `kode` unique | Stabil | Soft delete |
| `structural_positions` | `id` UUID | `code` unique | **ID tidak stabil bila seeder dijalankan ulang** | Seeder delete + recreate |
| `users` | `id` UUID | `email` unique | Stabil | Soft delete |
| `gtk_work_unit` | `id` UUID baris | `(user_id, work_unit_id)` | Baris dapat hilang/dibuat ulang | Tidak ada lifecycle |
| `structural_assignments` | `id` UUID | `(user_id, position_id, school_id, academic_year_id)` | Stabil; soft delete | Sumber utama kewenangan |
| `gtk_additional_tasks` | `id` UUID | — | Stabil | Legacy; tanpa school/year |
| `other_teacher_tasks` | `id` UUID | `(teacher_id, academic_year_id, task_code, study_group_id)` | Stabil | Per sekolah/tahun |
| `institution_decrees` | `id` UUID | `decree_number` unique | Stabil | `deleted_at` tidak aktif di model |

**Kesimpulan:** UUID layak menjadi identifier kanonik, tetapi `structural_positions.id` **tidak boleh dianggap abadi** selama seeder destruktif masih ada.

### 5.2 Foreign key dan cascade

| FK | Perilaku | Dampak |
|---|---|---|
| `schools.work_unit_id` | cascade | Hapus unit (force) → hapus sekolah → cascade akademik/assignment |
| `academic_years.school_id` | cascade | Hapus sekolah → hapus tahun ajaran |
| `study_groups.school_id` | cascade | Hapus sekolah → hapus rombel |
| `study_groups.academic_year_id` | cascade | Hapus tahun ajaran → hapus rombel |
| `structural_assignments.school_id` | cascade | Hapus sekolah → hapus assignment |
| `structural_assignments.position_id` | cascade | Hapus posisi → hapus assignment |
| `structural_assignments.academic_year_id` | cascade | Hapus tahun ajaran → hapus assignment |
| `structural_assignments.decree_id` | cascade | Hapus SK → hapus assignment |
| `teaching_assignments.decree_id` | cascade | Hapus SK → hapus penugasan mengajar |
| `homeroom_assignments.decree_id` | cascade | Hapus SK → hapus wali kelas |
| `coordinator_assignments.decree_id` | cascade | Hapus SK → hapus koordinator |
| `gtk_work_unit.position_id` | nullOnDelete | Hapus posisi → penempatan kehilangan posisi |
| `gtk_employments.school_id` | nullOnDelete | Hapus sekolah → employment kehilangan konteks |
| `work_units.divisi_id` | nullOnDelete | Hapus divisi → unit tanpa divisi |
| `work_units.domain_id` | nullOnDelete | Hapus domain → unit tanpa domain |
| `structural_positions.domain_id` | nullOnDelete | Hapus domain → posisi tanpa domain |
| `work_units.parent_id` | **tanpa FK** | Orphan possible |

**Kesimpulan:** terdapat **cascade berantai** dari unit/sekolah/SK/posisi/tahun ajaran ke assignment. Ini adalah risiko data loss terbesar dan tidak boleh diabaikan oleh desain integrasi.

### 5.3 Soft delete vs hard delete

| Entity | Soft delete | Konsekuensi |
|---|---|---|
| `work_units` | Ya | Normal delete aman; force delete memicu cascade |
| `divisis` | Ya | Relatif aman |
| `domains` | Ya | Relatif aman |
| `structural_assignments` | Ya | Tombstone alami |
| `users` | Ya | Tombstone alami |
| `schools` | **Tidak** | Hard delete + cascade |
| `structural_positions` | **Tidak** | Hard delete + cascade |
| `gtk_work_unit` | **Tidak** | Hilang tanpa jejak |
| `gtk_employments` | **Tidak** | Hilang tanpa jejak |
| `gtk_additional_tasks` | **Tidak** | Hilang tanpa jejak |
| `other_teacher_tasks` | **Tidak** | Hilang tanpa jejak |
| `institution_decrees` | Kolom ada, trait tidak | Perilaku delete tidak konsisten |

### 5.4 Lifecycle assignment

| Sumber | Aktif? | Mulai | Berakhir | Histori |
|---|---|---|---|---|
| `structural_assignments` | `status=active` | `start_date` | `end_date` | Status + soft delete |
| `gtk_work_unit` | Baris ada | `created_at` (implisit) | Baris hilang | Tidak |
| `gtk_additional_tasks` | `tst` null/≥ today | `tmt` | `tst` | Tidak |
| `other_teacher_tasks` | `is_active` | Tidak ada | Tidak ada | Tidak |
| `gtk_employments` | `status_kepegawaian` | `tmt` | Tidak ada | Banyak baris |
| `users` | `is_active` | — | — | Soft delete |

**Kesimpulan:** hanya assignment struktural yang memiliki lifecycle eksplisit. Desain harus menyatakan ini dan tidak menggeneralisasi “assignment” seolah semua memiliki status/tanggal.

### 5.5 Data quality yang harus diverifikasi sebelum integrasi

1. Jumlah `work_units` dengan `parent_id` null.
2. Jumlah `work_units` dengan `divisi_id` null.
3. Jumlah `structural_positions` dengan `domain_id` null.
4. Jumlah `structural_assignments` yang `position_id`/`school_id`/`academic_year_id` tidak konsisten.
5. Jumlah `gtk_work_unit` dengan `position_id` null.
6. Jumlah `gtk_work_unit` dengan lebih dari satu `is_primary=true` per user.
7. Jumlah `users` dengan banyak `gtk_employments` dan status terminal.
8. Jumlah `other_teacher_tasks` yang `is_active=true` tetapi `decree_id` null.
9. Jumlah SK dengan assignment terkait (risiko delete).
10. Jumlah sekolah dengan lebih dari satu `work_unit`? (tidak mungkin; arahnya unit → banyak sekolah) — hitung unit dengan >1 sekolah.

**Metode:** query read-only di staging/salinan produksi. **Jangan** menjalankan query berat di produksi tanpa persetujuan.

### 5.6 Verdict data integrity

**Belum memenuhi syarat untuk integrasi penuh.** Data dapat dibaca, tetapi belum dapat dijamin konsisten untuk scope, hierarchy, domain, dan tombstone. Fase 1 dapat berjalan dengan field nullable dan dokumentasi keterbatasan; Fase 2/3 memerlukan perbaikan data dan ledger.

## 6. Security Review

### 6.1 Autentikasi dan reuse komponen existing

**Bukti:**

- `VerifyIntegrationApiKey` adalah abstract base; memakai `hash_equals`, IP allowlist, HTTPS di produksi, rate limit auth gagal, dan `Cache-Control: no-store` (`app/Http/Middleware/VerifyIntegrationApiKey.php:50–116`).
- `IntegrationApiConfig` mendukung key terenkripsi `enc:v1:` dan setting per prefix (`app/Support/IntegrationApiConfig.php`).
- POS dan Recruitment memakai subclass dengan prefix berbeda (`app/Http/Middleware/VerifyPosApiKey.php`, `VerifyRecruitmentApiKey.php`).
- Route POS/Recruitment hanya `GET` (`routes/api.php:449–495`).

**Penilaian:** reuse desain §5.1 tepat. Penambahan subclass `VerifyConnectApiKey` dan prefix `connect` bersifat aditif. **Tidak ada perubahan pada POS/Recruitment yang diperlukan.**

### 6.2 Least privilege

| Aspek | Status desain | Catatan |
|---|---|---|
| Read-only | Ya | Semua route Connect harus `GET` |
| Scope per entity | **Tidak** | Key memberi akses ke seluruh endpoint Connect |
| Scope per unit/sekolah | **Tidak** | Scope dihitung di Connect, bukan pada key |
| Pemisahan key POS/Recruitment/Connect | Ya | Prefix berbeda |
| Write ke master Core | Tidak | Tidak ada route tulis |

**Gap:** key Connect bersifat all-or-nothing. Jika bocor, seluruh data yang diizinkan dapat dibaca. Mitigasi: IP allowlist, rate limit, rotasi, audit, dan minimisasi data. Untuk jangka panjang, pertimbangkan scope per endpoint atau key per lingkungan.

### 6.3 Key lifecycle

- `IntegrationApiConfig` hanya mendukung satu key per prefix (`key()` membaca `{prefix}_api_key`).
- Desain §5.4 mengakui dual-key belum didukung.
- Rotasi saat ini memerlukan update key di Core dan Connect secara koordinasi; risiko downtime jika tidak sinkron.
- **Rekomendasi:** dukung `connect_api_key_previous` dengan masa berlaku, atau rotasi pada maintenance window. Ini perubahan kode, bukan migration.

### 6.4 Rate limiting

- Base middleware hanya membatasi **auth gagal** per IP (default 10/menit).
- Desain §5.3 mengusulkan `throttle:connect-api` per key/IP dan limit snapshot terpisah.
- Belum ada implementasi.
- **Risiko:** tanpa limiter sukses, snapshot besar dapat dibebani berulang.
- **Rekomendasi:** implementasikan limiter di `RouteServiceProvider` secara aditif; uji 429 dan `Retry-After`.

### 6.5 Audit akses

- Auth gagal sudah dicatat base middleware (IP, path, alasan; tanpa token).
- Akses sukses **belum** dicatat.
- Desain §5.5 mengusulkan `LogConnectAccess`; belum ada.
- **Risiko:** tidak ada jejak siapa mengambil snapshot/changes.
- **Rekomendasi:** catat route, status, response count, durasi, key fingerprint, `request_id`; jangan catat query values yang mengandung PII.

### 6.6 PII

Desain §8 sudah membatasi:

- Dilarang: NIK, KK, NPWP, NUPY, nomor SK, rekening, kesehatan, keluarga.
- Diizinkan: nama, username, email (opsional), status kepegawaian, unit/position.
- `schools` bank fields dan dokumen paths dilarang.

**Gap:** kebijakan email/nomor telepon belum diputuskan (§11.3 poin 7). `LogConnectAccess` harus memastikan query PII tidak tercatat. API response harus memilih kolom eksplisit, bukan `SELECT *`.

### 6.7 Threat model

| Ancaman | Dampak | Mitigasi desain | Status |
|---|---|---|---|
| Key bocor | Baca seluruh data | Rotasi, IP, rate limit, audit | Sebagian |
| Enumerasi UUID | Mengambil entity lain | UUID non-sequential; filter | Baik |
| Query injection | Eksekusi berbahaya | Eloquent/parameter binding | Perlu implementasi |
| Snapshot DoS | Beban server | Limit, ndjson, off-peak | Desain |
| PII leakage | Kepatuhan | Matriks §8 | Desain |
| Replay | Tidak ada efek samping | Read-only | Baik |
| Cross-app key | Gangguan POS/Recruitment | Prefix terpisah | Baik |
| Insider misuse | Baca data sensitif | Audit, least privilege | Sebagian |

### 6.8 Temuan keamanan

| ID | Temuan | Severity | Rekomendasi |
|---|---|---|---|
| SEC-01 | Key all-or-nothing | Medium | Scope/endpoint key atau terima risiko dengan IP+audit |
| SEC-02 | Rotasi single-key | Medium | Dual-key/grace period |
| SEC-03 | Audit akses sukses belum ada | High | Implementasi `LogConnectAccess` |
| SEC-04 | Rate limit sukses belum ada | High | Implementasi limiter |
| SEC-05 | PII policy belum final | Medium | Keputusan pemilik sistem |
| SEC-06 | `request_id` dari header klien dapat dipalsukan | Low | Validasi/generate server-side |
| SEC-07 | `LogConnectAccess` berpotensi mencatat query PII | Medium | Whitelist parameter, redaksi |

### 6.9 Verdict keamanan

**Desain keamanan berada pada jalur yang benar, tetapi belum aman secara operasional sampai SEC-03, SEC-04, dan key lifecycle diimplementasikan dan diuji.** Jangan menyatakan API “aman” hanya berdasarkan desain.

## 7. API Contract Review

### 7.1 Checklist kontrak

| Kriteria | Status | Bukti/catatan |
|---|---|---|
| Hanya read-only | **Lulus desain** | §3.1 dan §5.2; semua endpoint `GET`; belum ada implementasi |
| UUID identifier kanonik | **Lulus dengan catatan** | §2.2; `structural_positions.id` tidak stabil saat reseed |
| Tidak menduplikasi master | **Sebagian** | `people.units` dan `work-unit-assignments` tumpang tindih; `position_scope=any` ambigu |
| Struktur response konsisten | **Sebagian** | Envelope `ApiResponse` konsisten, tetapi `next_cursor`/`server_time` tidak didukung helper existing |
| One-to-many benar | **Lulus desain** | §3.2 memakai `schools: []`; implementasi harus tidak memakai `WorkUnit::school()` |
| Assignment lifecycle jelas | **Sebagian** | Struktural jelas; penempatan unit tidak |
| Tombstone tidak salah hapus | **Belum** | Hard delete tanpa ledger; aturan §7.3.1 benar, tetapi belum ada sumber |
| Pagination/cursor aman | **Sebagian** | Cursor dirancang; `ApiResponse::paginated` page-based; ordering perlu ledger |
| Kegagalan sync tidak kehilangan perubahan | **Sebagian** | §6.9 retry/reconcile; belum ada implementasi/ledger |
| PII dibatasi | **Lulus desain** | §8; kebijakan email/telepon belum final |

### 7.2 Temuan kontrak

| ID | Temuan | Severity | Dampak | Rekomendasi |
|---|---|---|---|---|
| API-01 | `changes` didefinisikan sebagai endpoint v1 meski ledger belum ada | High | Klien mengira fitur tersedia | Tandai `changes` sebagai roadmap/fase 3; jangan daftarkan route sebelum siap |
| API-02 | `ApiResponse::paginated()` tidak mendukung `next_cursor`/`server_time` | Medium | Implementasi menyimpang atau envelope tidak konsisten | Buat `CursorResponse`/meta helper tanpa mengubah existing |
| API-03 | `position_scope=any` tidak mendefinisikan precedence | High | Duplikat/assignment salah | Definisikan urutan (`structural` > `placement`), dedupe, sertakan `source` |
| API-04 | `people.school_id` union berpotensi duplikat | High | Data ganda/scope salah | Dedupe by `user_id`; sertakan `match_sources`; definisi prioritas |
| API-05 | `work-unit-assignments` tanpa status/tanggal | High | Tidak bisa tentukan aktif/berakhir | Dokumentasikan “baris ada = aktif”; ledger/tombstone; fase 2 |
| API-06 | `additional_tasks` dua tabel dengan `id` terpisah | Medium | Tabrakan kunci | Wajib `(source, id)`; validasi di Connect |
| API-07 | `tree=true` mengubah bentuk `data` dan mengabaikan pagination | Low | Kontrak tidak seragam | Definisikan `meta` khusus tree; atau endpoint terpisah |
| API-08 | `include_deleted` tidak konsisten dengan hard delete | High | Tombstone tidak lengkap | Aktifkan hanya untuk entity soft delete; ledger untuk hard delete |
| API-09 | `since`/`active_on` timezone tidak eksplisit | Medium | Assignment efektif berbeda 1 hari | Tetapkan timezone dan boundary di kontrak |
| API-10 | `snapshot` JSON penuh berpotensi besar | Medium | Timeout/OOM | `ndjson`, `types`, limit, off-peak |
| API-11 | `changes` cursor tidak menyebut HMAC/signature di versi awal | Medium | Cursor dapat dimanipulasi | Tandatangani cursor; validasi |
| API-12 | `principal_name`/`email` dapat dianggap PII | Low–Medium | Kebijakan belum final | Keputusan pemilik sistem; minimisasi |

### 7.3 Analisis per kriteria

#### Read-only

Desain §3.1 dan §5.2 hanya mengizinkan `GET`. Ini konsisten dengan POS/Recruitment existing. **Tidak ada route Connect yang terdaftar saat ini**, sehingga klaim “read-only” baru berlaku pada desain, bukan implementasi.

#### UUID

UUID dipakai di semua entity utama. Namun:

- `structural_positions.id` berubah bila `JenisGtkSeeder` dijalankan (delete + recreate).
- `gtk_work_unit.id` berubah bila baris dihapus dan dibuat ulang.
- `schools.id` stabil, tetapi tidak ada soft delete.

**Rekomendasi:** simpan `natural_key` untuk rekonsiliasi; jangan gunakan natural key sebagai pengganti UUID.

#### Duplikasi sumber master

- `people.units` dan `work-unit-assignments` mengekspos data yang sama dari `gtk_work_unit`.
- `people.primary_unit` juga berasal dari sana.
- `position_scope=any` menggabungkan `gtk_work_unit.position_id` dan `structural_assignments.position_id`.

Ini bukan duplikasi tabel, tetapi **duplikasi representasi**. Connect harus tahu mana yang otoritatif untuk scope. Desain belum menetapkan precedence.

#### Struktur response

Envelope `success/message/data/meta/errors` konsisten dengan `ApiResponse`. Namun:

- `meta.server_time` tidak ada di `defaultMeta()` (`app/Support/ApiResponse.php:108–115`).
- `meta.next_cursor` tidak dihasilkan `paginated()`.
- `meta.pagination` selalu page-based.

**Rekomendasi:** helper response khusus Connect, tanpa mengubah `ApiResponse` existing (backward compatibility).

#### One-to-many

Desain §3.2 sudah benar memakai `schools: []`. Ini menghindari bug `WorkUnit::school()` hasOne. Implementasi wajib memakai relasi `schools()`, bukan `school()`.

#### Lifecycle assignment

- Struktural: status + tanggal + soft delete → jelas.
- Penempatan unit: tidak ada status/tanggal → tidak jelas.
- Tugas tambahan: `gtk_additional_tasks` `tmt/tst`, `other_teacher_tasks` `is_active` → parsial.

**Rekomendasi:** bedakan `source` dan lifecycle di response; jangan memakai satu istilah “assignment” untuk semua.

#### Tombstone

Aturan §7.3.1 benar: delete → `deleted_at` lokal, cabut scope, jangan hard delete. Namun sumber tombstone belum ada untuk hard delete. Tanpa ledger, aturan ini tidak dapat dijalankan untuk `schools`, `structural_positions`, `gtk_work_unit`, `gtk_employments`, `gtk_additional_tasks`, `other_teacher_tasks`.

#### Pagination, cursor, dan concurrency

- Desain memakai page untuk list dan cursor untuk changes.
- Cursor berbasis `occurred_at,id` atau `id` perlu ledger.
- Tanpa ledger, cursor berbasis `updated_at` dapat melewatkan event yang commit tidak berurutan.
- Idempotent upsert dan payload hash sudah dirancang.

#### Kegagalan sinkronisasi

- Retry/backoff, cursor expiry, reconciliation sudah dirancang.
- Tidak ada outbox/dead-letter di Connect.
- Core tidak boleh terganggu jika Connect down; read-only membantu.

#### PII

Matriks §8 membatasi field. Namun:

- `principal_name` dan `email` masih bisa dianggap PII.
- `notes`/`additional_info` pada assignment dapat memuat informasi sensitif; desain membolehkan opsional.
- `LogConnectAccess` harus menghindari query values.

### 7.4 Verdict kontrak API

**Kontrak belum siap dibekukan (contract freeze) sampai API-01, API-03, API-04, API-05, API-08, dan API-09 diselesaikan.** Setelah itu, Fase 1 dapat memakai kontrak dengan `changes` ditandai belum tersedia.

## 8. Evaluasi 14 Keputusan

### 8.1 Matriks klasifikasi

| No | Keputusan | Kategori | Rekomendasi review | Pemilik keputusan | Blocker |
|---|---|---|---|---|---|
| 1 | Change ledger | Arsitektur | Setuju membuat `organization_change_log` append-only; jangan memakai `audit_logs` sebagai feed | Arsitek + pemilik sistem | Fase 3 |
| 2 | Cakupan assignment | Arsitektur/produk | Mulai dari unit + structural + additional tasks; teaching/homeroom/asrama menyusul | Pemilik produk Connect | Fase 1–2 |
| 3 | `position_scope` | Teknis + produk | Default `any`, tetapi wajib precedence dan `source`; jangan dedupe tanpa aturan | Arsitek + produk | Fase 1 |
| 4 | Assignment berakhir | Kebijakan institusi | Cabut akses pada evaluasi berikutnya; timing final milik pemilik sistem | Pemilik sistem | Fase 2 |
| 5 | Hard delete policy | Kebijakan + arsitektur | Jangan tambah soft delete massal; guard delete + ledger | Pemilik sistem + arsitek | Fase 3 |
| 6 | Rotasi key | Teknis/operasional | Dukung dual-key/grace period; rotasi maintenance sebagai fallback | Tim platform/security | Fase 1 |
| 7 | PII | Kebijakan institusi | Email opsional; telepon tidak; keputusan final pemilik | Pemilik sistem/legal | Fase 1 |
| 8 | Audit retention | Kebijakan institusi | Usulan 1–2 tahun; final sesuai kebijakan | Pemilik sistem/legal | Fase 3 |
| 9 | Rate limit | Teknis (butuh data) | Tetapkan setelah load test; mulai konservatif | Platform/security | Fase 1 |
| 10 | Snapshot scale | Teknis (butuh data) | Wajib `ndjson` di atas ambang; ambang setelah ukur data | Platform | Fase 1 |
| 11 | Observer fix | Teknis | Perbaiki di perubahan terpisah dengan regression test | Tim Core | Sebelum authority |
| 12 | Backfill ownership | Institusi/operasional | Tetapkan PIC data; review tidak boleh mengubah data | Pemilik sistem | Sebelum Fase 2 |
| 13 | Audit failure | Arsitektur/risiko | Fail-closed untuk assignment; fail-open untuk akses baca (queue) | Arsitek + pemilik sistem | Fase 3 |
| 14 | Tombstone hard delete | Arsitektur | Jangan rilis `changes`; gunakan rekonsiliasi sampai ledger | Arsitek | Fase 3 |

### 8.2 Keputusan teknis yang dapat direkomendasikan

#### K-03 — `position_scope`

**Rekomendasi teknis:** definisikan tiga nilai:

- `placement`: hanya `gtk_work_unit.position_id`.
- `structural`: hanya `structural_assignments.position_id` yang efektif.
- `any`: gabungan, tetapi **wajib** mengembalikan `source` per item dan dedupe berdasarkan `(user_id, position_id, source, assignment_id)`.

Tanpa aturan ini, user dapat muncul dua kali untuk posisi yang sama. Ini bukan keputusan bisnis, melainkan kejelasan kontrak.

#### K-06 — Rotasi key

**Rekomendasi teknis:** dual-key dengan `connect_api_key_previous` dan masa berlaku. Jika tidak, rotasi harus maintenance window. Ini dapat diimplementasikan di subclass `VerifyConnectApiKey` tanpa mengubah POS/Recruitment.

#### K-09 — Rate limit

**Rekomendasi teknis:** mulai dari batas konservatif:

- list: 120/menit per key+IP
- changes: 60/menit
- snapshot: 6/menit

Angka final harus divalidasi dengan load test. Ini bukan keputusan institusi.

#### K-10 — Snapshot scale

**Rekomendasi teknis:** aktifkan `ndjson` bila salah satu ambang terlampaui:

- `people > 10.000`
- `structural_assignments > 50.000`
- response JSON > 50 MB

Ambang final setelah pengukuran data aktual.

#### K-11 — Observer fix

**Rekomendasi teknis:** tambahkan cabang `StructuralAssignment`, `HomeroomAssignment`, `CoordinatorAssignment`, dan daftarkan `GtkWorkUnit`/`OtherTeacherTask` bila relevan. Lakukan sebagai perubahan terpisah dengan test regresi permission. **Jangan dikerjakan bersamaan dengan API Connect.**

### 8.3 Keputusan arsitektur yang membutuhkan persetujuan

#### K-01 — Change ledger

**Rekomendasi:** setuju membuat `organization_change_log` append-only. Alasan: `integration_event_log` bukan feed per-entity (`status`, `target_module`, `attempt`) dan tidak memiliki before/after/cursor contract.

**Yang membutuhkan persetujuan:** nama tabel, skema, retensi, siapa yang menulis, dan apakah event ditulis dalam transaksi bisnis.

#### K-13 — Audit failure policy

**Rekomendasi:** pisahkan:

- Perubahan assignment/unit/sekolah/posisi: **fail-closed** (audit gagal → rollback) untuk integritas.
- Akses baca API: **fail-open** (audit ke queue; jangan gagalkan request) untuk ketersediaan.

Ini trade-off arsitektur, bukan keputusan bisnis murni.

#### K-14 — Tombstone hard delete

**Rekomendasi:** jangan merilis `changes` sebelum ledger. Sementara gunakan rekonsiliasi penuh; dokumentasikan bahwa delete tidak real-time. Jika pemilik sistem ingin `changes` segera, ledger harus disetujui lebih dulu.

### 8.4 Keputusan kebijakan institusi

Berikut **tidak boleh diputuskan oleh reviewer**:

#### K-04 — Kapan akses dicabut saat assignment berakhir

Opsi:

- **A. Ketat:** tepat pada `end_date` (evaluasi setiap request).
- **B. Siklus sync:** setelah job berikutnya (mis. 5–15 menit).
- **C. Manual:** menunggu admin.

Dampak: keamanan vs beban komputasi vs operasional. **Pemilik sistem harus memilih.**

#### K-05 — Soft delete vs hard delete

Opsi:

- **A. Tambah soft delete selektif** pada tabel kritis (migration additive, backfill, ubah query).
- **B. Pertahankan hard delete + ledger + guard.**
- **C. Arsipkan melalui `is_active`/`status` tanpa mengubah delete.**

Risiko A: perubahan perilaku produksi. Risiko B: ledger wajib. **Pemilik sistem harus memilih.**

#### K-07 — PII

Opsi:

- Email: perlu/tidak.
- Nomor telepon: perlu/tidak.
- `principal_name`: perlu/tidak.
- `notes` assignment: perlu/tidak.

**Keputusan legal/institusi.**

#### K-08 — Retensi audit

Opsi 1 tahun, 2 tahun, atau lebih. **Keputusan legal/institusi.**

#### K-12 — Backfill ownership

Siapa yang bertanggung jawab:

- verifikasi `parent_id`,
- memperbaiki `divisi_id`,
- backfill `domain_id`,
- memverifikasi assignment.

**Keputusan organisasi.**

### 8.5 Keputusan yang belum dapat ditentukan

#### K-02 — Cakupan assignment

Belum ada informasi kebutuhan Connect yang cukup untuk memastikan apakah `teaching_assignments`, `homeroom_assignments`, dan `dormitory_staff_assignments` diperlukan pada fase awal. **Rekomendasi:** mulai dari unit + structural + additional tasks; tambah setelah ada use case konkret.

#### K-09 dan K-10

Membutuhkan data volume dan hasil load test. Belum dapat ditentukan dari source code.

#### K-12

Membutuhkan struktur organisasi/operasional yang berwenang atas data. Belum tersedia.

### 8.6 Ringkasan keputusan

- **Dapat direkomendasikan sekarang:** K-03, K-06, K-09 (angka awal), K-10 (ambang awal), K-11.
- **Perlu persetujuan arsitektur:** K-01, K-13, K-14.
- **Perlu keputusan institusi:** K-04, K-05, K-07, K-08, K-12.
- **Belum dapat ditentukan:** K-02, angka final K-09/K-10, pemilik K-12.

## 9. Rekomendasi Urutan Penyelesaian

### 9.1 Prinsip urutan

1. **Lindungi produksi dulu**, baru tambah fitur.
2. **Perbaiki data/kontrak** sebelum mengekspos API.
3. **Jangan menambah skema** sampai benar-benar diperlukan dan disetujui.
4. **Setiap fase harus punya gate persetujuan, backup, dan rollback.**
5. **Persetujuan desain bukan persetujuan implementasi atau deployment.**

### 9.2 Fase 0 — Perlindungan dan verifikasi (tanpa perubahan)

**Tujuan:** memastikan tidak ada operasi destruktif dan data dipahami.

Aksi:

1. Bekukan eksekusi `JenisGtkSeeder` dan seeder destruktif lain di produksi; tambahkan larangan di CI/runbook (perubahan terpisah).
2. Backup penuh tabel: `work_units`, `schools`, `structural_positions`, `structural_assignments`, `gtk_work_unit`, `gtk_employments`, `institution_decrees`, `academic_years`, `study_groups`, `users`.
3. Audit data read-only: `parent_id`, `divisi_id`, `domain_id`, `position_id`, `is_primary`, assignment effectivity.
4. Hitung dampak delete SK/sekolah/posisi/tahun ajaran (jumlah assignment terkait).
5. Tinjau dan setujui kontrak API (termasuk API-01..API-12).
6. Tambahkan indeks dokumentasi `docs/README.md` (dokumen, bukan kode).

**Gate:** data report + kontrak disetujui + backup tersedia.

### 9.3 Fase 1 — API read-only master + snapshot

**Cakupan:**

- Auth Connect (`VerifyConnectApiKey`, config, rate limiter, audit akses).
- `GET /organization/units`, `/schools`, `/positions`, `/people`.
- `GET /organization/snapshot` (json/ndjson).
- Tanpa `changes`; tanpa skema baru.

**Prasyarat:**

- Fase 0 selesai.
- Kontrak `schools: []`, `meta` cursor/`server_time`, `position_scope`, `school_id` union disepakati.
- Data nullable didokumentasikan.
- Load test awal pada salinan produksi.

**Test:**

- Auth/security, filter/pagination, one-to-many schools, PII, performance.
- Regression POS/Recruitment.

**Rollback:** hapus route/feature flag; tidak ada migration.

**Gate:** security review + staging acceptance + persetujuan deployment terpisah.

### 9.4 Fase 2 — Assignment dan polling

**Cakupan:**

- `GET /organization/work-unit-assignments`
- `GET /organization/structural-assignments`
- `GET /organization/additional-tasks`
- `updated_since` pada entity yang belum punya.
- Scope engine di Connect.

**Prasyarat:**

- Keputusan lifecycle `gtk_work_unit` (K-04/K-05/K-14).
- Precedence `position_scope`.
- Data assignment terverifikasi.
- `PermissionRebuildObserver` tidak dijadikan dependensi.

**Test:**

- `is_effective`, multi-assignment, end date, deleted/inactive, duplicate prevention.
- Rekonsiliasi terhadap snapshot.
- Regression.

**Rollback:** feature flag per endpoint.

### 9.5 Fase 3 — Change ledger dan tombstone

**Cakupan:**

- Migration additive `organization_change_log`.
- Observer penulisan event (termasuk `deleting`).
- `GET /organization/changes`.
- Tombstone handling di Connect.
- Audit observer untuk entity organisasi.

**Prasyarat:**

- Persetujuan arsitektur K-01, K-05, K-13, K-14.
- Backup + staging representatif.
- Ukuran tabel dan locking dianalisis.
- Rencana rollback yang **tidak** menghapus data baru.

**Test:**

- Idempotensi, cursor, hard/soft delete, out-of-order, duplicate, expiry, reconciliation.
- Contract test.
- Performance dengan volume produksi.

**Rollback:** hentikan penulisan ledger/feature flag; **jangan drop tabel** yang sudah berisi data tanpa persetujuan eksplisit.

### 9.6 Fase 4 — Hardening dan operasional

- Monitoring drift, alert, dashboard sync.
- Key rotation drill.
- Rate limit tuning.
- Versioning/deprecation.
- Dokumentasi runbook dan on-call.

### 9.7 Pekerjaan terpisah yang tidak boleh dicampur

| Pekerjaan | Alasan | Gate |
|---|---|---|
| Perbaikan `PermissionRebuildObserver` | Memengaruhi otorisasi Core | Persetujuan + regression test |
| Guard delete SK/sekolah/posisi/tahun ajaran | Mengubah perilaku UI | Persetujuan produk/operasional |
| Backfill `parent_id`/`divisi_id`/`domain_id` | Menulis data produksi | Persetujuan data owner + backup |
| Perubahan seeder | Berpotensi menghapus data | Persetujuan + staging |
| Soft delete baru | Mengubah query/perilaku | Persetujuan arsitektur |
| Perbaikan `GtkEmploymentObserver` | Memengaruhi role | Persetujuan + test |

### 9.8 Larangan selama fase ini

- Jangan menjalankan `JenisGtkSeeder` atau seeder destruktif.
- Jangan `DROP`/`TRUNCATE`/delete massal.
- Jangan mengubah FK/cascade existing.
- Jangan mengubah tipe/nullable/default/constraint existing.
- Jangan mengubah assignment/permission existing.
- Jangan mengubah API POS/Recruitment.
- Jangan deploy ke produksi tanpa persetujuan terpisah.

## 10. Go/No-Go Assessment

### 10.1 Definisi

- **Go:** boleh masuk tahap implementasi untuk ruang lingkup yang disetujui, dengan gate berikutnya.
- **Conditional Go:** boleh masuk tahap berikutnya **hanya setelah** prasyarat terpenuhi dan disetujui.
- **No-Go:** belum boleh diimplementasikan/dirilis sampai kondisi tertentu terpenuhi.

### 10.2 Penilaian per komponen

| Komponen | Verdict | Alasan | Prasyarat utama |
|---|---|---|---|
| Desain keseluruhan | **Conditional Go** | Arah arsitektur benar, tetapi risiko data/otorisasi belum dimitigasi | Fase 0 + kontrak disetujui |
| Fase 1 — master read-only + snapshot | **Conditional Go** | Tidak perlu skema baru; data nullable dapat diekspos | Data audit, auth, rate limit, audit akses, staging |
| Fase 2 — assignment + polling | **Conditional Go** | Struktural siap; penempatan belum punya lifecycle | K-03/K-04/K-05/K-12 |
| Fase 3 — `changes` + tombstone | **No-Go** | Tidak ada ledger, hard delete tidak terdeteksi | K-01/K-05/K-13/K-14 + migration |
| Perbaikan Core (observer/delete guard) | **No-Go** | Berpotensi mengubah perilaku produksi | Persetujuan terpisah + regression test |
| Perubahan seeder | **No-Go** | Risiko kehilangan assignment massal | Persetujuan + staging + backup |
| Deployment produksi | **No-Go** | Belum ada implementasi, test, staging, approval | Semua gate + persetujuan deployment |
| Dokumentasi | **Conditional Go** | `docs/README.md` tidak ada; root README drift | Indeks + status dokumen |

### 10.3 Kriteria Go untuk Fase 1

Semua harus terpenuhi:

1. **Backup terverifikasi** untuk tabel organisasi/assignment/akademik.
2. **Guardrail seeder** aktif; `JenisGtkSeeder` tidak dapat dijalankan tanpa konfirmasi eksplisit.
3. **Data audit** `parent_id`, `divisi_id`, `domain_id`, `position_id`, `is_primary` selesai; hasilnya diterima pemilik data.
4. **Kontrak API** API-01..API-12 ditinjau dan diterima; `changes` ditandai belum tersedia.
5. **Auth Connect** diimplementasikan dengan key khusus, IP allowlist, HTTPS, rate limit, audit akses.
6. **PII policy** minimal untuk Fase 1 disetujui.
7. **Regression test POS/Recruitment** lulus.
8. **Staging** dengan salinan data representatif tersedia.
9. **Load test** Fase 1 lulus target yang disepakati.
10. **Persetujuan implementasi** eksplisit.

Jika salah satu tidak terpenuhi → **No-Go** untuk Fase 1.

### 10.4 Kriteria No-Go

**No-Go** jika:

- Seeder destruktif masih dapat dijalankan tanpa guard.
- `structural_positions`/`schools`/SK/tahun ajaran masih dapat dihapus tanpa guard dan tanpa backup.
- `PermissionRebuildObserver` masih dijadikan dasar otorisasi Connect.
- `changes`/tombstone dijanjikan tanpa ledger.
- API key POS/Recruitment akan diubah.
- Akan ada migration destruktif atau perubahan FK/cascade.
- Tidak ada staging/backup.
- Belum ada persetujuan pemilik sistem untuk kebijakan K-04/K-05/K-07/K-08/K-12.

### 10.5 Rekomendasi keputusan

**Rekomendasi review:**

1. **Setujui desain sebagai arah, bukan sebagai izin implementasi penuh.**
2. **Setujui Fase 0** segera (read-only audit + guard + backup).
3. **Setujui Fase 1 secara bersyarat** setelah 10 kriteria §10.3.
4. **Tunda Fase 3** sampai ledger dan kebijakan delete disetujui.
5. **Pisahkan** perbaikan Core (observer, guard delete, backfill) dari pekerjaan API Connect.
6. **Jangan deploy** sebelum tahap verifikasi dan persetujuan deployment terpisah.

### 10.6 Kesimpulan akhir

**Status desain: LAYAK DILANJUTKAN DENGAN PRASYARAT (conditional go).**

Alasan:

- Desain menghormati SSOT, read-only, UUID, pemisahan entity, dan reuse komponen existing.
- Namun integritas data belum terjamin: cascade delete, hard delete, `gtk_work_unit` tanpa lifecycle, `parent_id`/`divisi_id`/`domain_id` tidak konsisten, dan permission snapshot tidak selalu diperbarui.
- `changes`/tombstone belum dapat diimplementasikan secara andal tanpa ledger.
- Keamanan desain baik secara arah, tetapi audit akses dan rate limit sukses belum ada.

**Status implementasi: BELUM LAYAK DIIMPLEMENTASIKAN SEBAGAI SATU PAKET PENUH.**

- Fase 1–2: layak setelah prasyarat.
- Fase 3: belum layak sampai ledger dan kebijakan delete disetujui.
- Perubahan Core: belum layak tanpa persetujuan dan pengujian terpisah.
- Deployment produksi: belum layak; belum ada implementasi, test, staging, atau approval.

**Reviewer tidak menyatakan sistem aman atau siap produksi.** Tidak ada pengujian runtime yang dijalankan pada review ini; seluruh klaim didasarkan pada pembacaan source code dan dokumen desain.

---

## Lampiran A. Indeks Bukti

### A.1 Dokumen yang direview

| Dokumen | Status | Catatan |
|---|---|---|
| `docs/integration/alim-connect-api-design.md` | Ada | 11 bagian + ringkasan + lampiran; status proposal |
| `docs/README.md` | **Tidak ada** | Tidak ada indeks dokumentasi |
| `docs/integration/alim-core-organization-data-audit.md` | Ada | Dasar audit; digunakan sebagai pembanding |
| `README.md` (root) | Ada | Menyebut Laravel 10; runtime Laravel 12.70.1 |

### A.2 Source code utama

| Komponen | Lokasi | Relevansi |
|---|---|---|
| Seeder posisi | `database/seeders/JenisGtkSeeder.php:17` | F-01 |
| FK assignment | `database/migrations/2026_02_14_135020_create_structural_assignments_table.php:19–33` | F-01, F-11 |
| Permission observer | `app/Authorization/Support/PermissionRebuildObserver.php:60–70` | F-02 |
| Registrasi observer | `app/Authorization/Providers/AuthorizationRuntimeServiceProvider.php:143–146` | F-02 |
| Penempatan unit | `database/migrations/2026_02_10_010955_create_gtk_work_unit_table.php:11–21` | F-03 |
| Event log | `database/migrations/2026_07_05_000001_create_integration_event_log_table.php` | F-04 |
| Soft delete | `app/Models/*.php`; migration terkait | F-05 |
| Parent/divisi/domain | `2026_02_10_010813...`, `2026_04_25_000003...`, `2026_09_18_000001...`, `2026_09_16_000003...` | F-06 |
| Relasi unit-sekolah | `app/Models/WorkUnit.php:128–131, 248–251`; `2026_02_14_094120...:13–15` | F-07 |
| Active assignment | `app/Models/StructuralAssignment.php:69–76` | F-08 |
| Audit log | `app/Models/AuditLog.php`; `app/Models/WorkUnit.php:200–222` | F-09 |
| API response | `app/Support/ApiResponse.php:90–115` | F-10, API-02 |
| Auth integrasi | `app/Http/Middleware/VerifyIntegrationApiKey.php:50–116` | Security |
| Config integrasi | `app/Support/IntegrationApiConfig.php` | Security |
| Route POS/Recruitment | `routes/api.php:449–495` | Backward compatibility |
| Delete SK | `app/Http/Controllers/InstitutionDecreeController.php:827–837` | F-11 |
| Delete sekolah | `app/Http/Controllers/SchoolController.php:492–511` | F-12 |
| Delete jabatan | `app/Http/Controllers/MasterDataController.php:155–160` | F-13 |
| Delete tahun ajaran | `app/Http/Controllers/AcademicYearController.php:126–133` | F-14 |

### A.3 Bagian desain yang dirujuk

| Bagian | Topik |
|---|---|
| §2.2 | Aturan identifier |
| §2.3–§2.4 | Kesiapan data dan verdict |
| §3.5–§3.8 | People, penempatan, assignment, tugas tambahan |
| §3.9–§3.10 | Snapshot dan changes |
| §5 | Autentikasi dan keamanan |
| §6 | Sinkronisasi |
| §7 | Tombstone dan audit |
| §8 | Matriks data |
| §9 | Versioning |
| §10 | Test plan |
| §11.3 | 14 keputusan |

## Lampiran B. Batasan Review

1. **Tidak ada pengujian runtime.** Tidak ada endpoint Connect, sehingga unit/feature/contract/performance test tidak dapat dijalankan.
2. **Tidak ada akses database produksi.** Temuan data (mis. `divisi_id` null) adalah kesimpulan dari kode seeder/migration, bukan sensus data.
3. **Tidak ada perubahan kode/skema.** Review ini tidak mengubah apa pun.
4. **Cakupan terbatas pada integrasi organisasi/GTK/assignment.** Modul akademik, keuangan, sarpras, dan asrama detail tidak diaudit mendalam.
5. **Dokumen desain dianggap draft.** Temuan dapat berubah jika desain direvisi.
6. **Test plan desain bukan hasil pengujian.** Tidak ada klaim lulus.

## Lampiran C. Checklist Serah Terima

| Item | Status |
|---|---|
| Audit source code selesai | Ya |
| Audit dokumen desain selesai | Ya |
| `docs/README.md` tersedia | **Tidak** |
| Semua 14 keputusan diklasifikasikan | Ya |
| Semua 10 risiko yang diminta dibahas | Ya |
| Temuan tambahan cascade delete | Ya (F-11–F-14) |
| Rekomendasi urutan fase | Ya |
| Go/No-Go | Ya (Conditional Go) |
| Perubahan kode | Tidak ada |
| Migration | Tidak ada |
| Endpoint | Tidak ada |
| Konfigurasi | Tidak ada |
| Pengujian runtime | Tidak dijalankan |
| Persetujuan implementasi | **Belum** |

---

> **Pernyataan penutup:** review ini tidak menyatakan ALIM Core atau integrasi ALIM Connect aman, siap produksi, atau bebas risiko. Kesimpulan bersifat kondisional dan bergantung pada pemenuhan prasyarat serta persetujuan eksplisit pemilik sistem.
