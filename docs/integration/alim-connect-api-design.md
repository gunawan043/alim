---
name: alim-connect-api-design
description: Desain teknis API read-only, autentikasi, sinkronisasi, tombstone, dan audit trail integrasi ALIM Core dengan ALIM Connect
metadata:
  type: design
  design-date: 2026-11-21
  status: proposal
  scope: read-only
  depends-on: docs/integration/alim-core-organization-data-audit.md
---

# Desain Teknis Integrasi ALIM Core — ALIM Connect

> **Status:** desain dan audit lanjutan. **Tidak ada perubahan kode, database, migration, endpoint, maupun konfigurasi** pada tahap ini.
>
> **Dasar:** `docs/integration/alim-core-organization-data-audit.md` dan verifikasi langsung pada implementasi repository.
>
> **Prinsip utama:** ALIM Core adalah Single Source of Truth. ALIM Connect memiliki database sendiri dan hanya boleh mengonsumsi data melalui API read-only. Tidak ada akses langsung ke database Core.

## Ringkasan Eksekutif

- **Dapat dimulai tanpa perubahan skema:** endpoint unit, sekolah, jabatan, people, penempatan unit, assignment struktural, tugas tambahan, dan snapshot dapat dibangun dari tabel yang sudah ada.
- **Belum dapat diandalkan:** `changes` dan tombstone karena belum ada change ledger dan banyak entity tidak memiliki soft delete.
- **Rekomendasi utama:** pisahkan API Connect (`/api/connect/v1`), reuse `VerifyIntegrationApiKey` + `IntegrationApiConfig` dengan prefix `connect`, dan tambahkan change ledger pada fase berikutnya.
- **Identifier:** UUID adalah kunci utama; natural key hanya untuk rekonsiliasi. Assignment memakai ID baris (`gtk_work_unit.id`, `structural_assignments.id`).
- **Keamanan:** read-only, API key khusus, HTTPS, IP allowlist, rate limit, audit akses, dan minimisasi PII.
- **Keputusan kritis:** persetujuan change ledger, cakupan assignment, definisi `position_scope`, kebijakan tombstone, dan penanganan audit gagal.

## 1. Arsitektur Integrasi

### 1.1 Gambaran komponen

```text
┌──────────────────────────────┐        HTTPS + API key        ┌──────────────────────────────┐
│          ALIM Core           │  ───────────────────────────► │         ALIM Connect         │
│                              │                               │                              │
│  Web/UI Admin                │   GET /api/connect/v1/...     │  Sync Worker                 │
│  ├─ WorkUnitController       │ ◄───────────────────────────  │  ├─ Snapshot bootstrap       │
│  ├─ SchoolController         │                               │  ├─ Incremental changes      │
│  ├─ GtkWizardController      │                               │  └─ Reconciliation           │
│  ├─ GtkUnitHeadController    │                               │                              │
│  └─ PersonaliaController     │                               │  Read Model (PostgreSQL/MySQL)│
│                              │                               │  ├─ org_units                │
│  Domain/Service              │                               │  ├─ org_schools              │
│  ├─ AuthorizationManager     │                               │  ├─ org_positions            │
│  ├─ SnapshotResolver         │                               │  ├─ org_people               │
│  └─ SchoolContextMiddleware  │                               │  ├─ org_work_unit_assignments│
│                              │                               │  ├─ org_structural_assignments│
│  Read API (baru)             │                               │  ├─ org_additional_tasks     │
│  ├─ OrganizationReadService  │                               │  └─ sync_state / tombstones  │
│  ├─ VerifyConnectApiKey      │                               │                              │
│  └─ LogConnectAccess         │                               │  RBAC/Teams (scope unit)     │
└──────────────────────────────┘                               └──────────────────────────────┘
```

### 1.2 Prinsip arsitektur

1. **Read-only by construction.** Seluruh route Connect hanya `GET`. Tidak ada `POST/PUT/PATCH/DELETE` ke master organisasi.
2. **Reuse sebelum abstraksi baru.** Autentikasi memakai `VerifyIntegrationApiKey` + `IntegrationApiConfig`; response memakai `ApiResponse`; route memakai pola POS/Recruitment.
3. **Pemisahan tanggung jawab.**
   - Controller hanya menangani HTTP (validasi query, bentuk response).
   - `OrganizationReadService` menangani query dan transformasi domain.
   - Middleware menangani autentikasi, rate limit, dan audit akses.
   - Sync/ledger (bila diimplementasikan) menjadi tanggung jawab terpisah dari API baca.
4. **Least privilege.** API key Connect hanya dapat membaca endpoint Connect. Tidak dapat memakai endpoint POS/Recruitment dan sebaliknya.
5. **Backward compatibility.** POS dan Recruitment tidak diubah. Penambahan `connect` bersifat aditif.
6. **Identitas kanonik.** UUID dari Core dipakai apa adanya. Natural key (`code`, `npsn`, `email`) hanya untuk rekonsiliasi, bukan pengganti UUID.

### 1.3 Batas sistem

| Dalam cakupan | Di luar cakupan |
|---|---|
| Organisasi: unit, sekolah, divisi, domain | Presensi, nilai, keuangan, sarpras |
| Jabatan struktural | Perhitungan permission internal Core |
| GTK/people dan penempatan | Data sensitif (NIK, KK, NPWP, rekening) |
| Assignment struktural + masa berlaku | Mutasi/transfer workflow |
| Tugas tambahan (dua sumber) | Modul akademik detail (rombel, mapel, jadwal) |
| Snapshot, changes, tombstone, audit akses | Penulisan balik dari Connect ke Core |

Data akademik detail (rombel, mapel, wali kelas) **tidak** termasuk kontrak awal. Bila Connect membutuhkannya, gunakan endpoint terpisah pada fase berikutnya dengan scope yang disetujui.

### 1.4 Alur data end-to-end

1. Admin Core mengubah unit/jabatan/penempatan/assignment melalui UI yang sudah ada.
2. Perubahan tersimpan di tabel aktual (`work_units`, `schools`, `structural_positions`, `gtk_work_unit`, `structural_assignments`, `gtk_additional_tasks`, `other_teacher_tasks`).
3. API Connect membaca tabel tersebut secara langsung (read-only) dengan filter dan pagination.
4. Sync worker Connect mengambil snapshot/changes, lalu melakukan idempotent upsert ke read model.
5. Connect mengevaluasi assignment efektif untuk menentukan scope unit/sekolah.
6. Perubahan assignment/kewenangan memicu evaluasi ulang akses dan keanggotaan Teams di Connect.

## 2. Entitas dan Identifier Kanonik

### 2.1 Tabel identifier

| Entitas | Tabel | Identifier kanonik | Natural key | Status/validity | Soft delete | Siap sync? |
|---|---|---|---|---|---|---|
| Unit organisasi | `work_units` | `id` UUID | `code` unique | `is_active` | Ya (`deleted_at`) | Sebagian |
| Divisi/bagian | `divisis` | `id` UUID | `kode` unique | `is_active` | Ya | Sebagian |
| Sekolah | `schools` | `id` UUID | `npsn` unique | `is_active` | **Tidak** | Sebagian |
| Jabatan struktural | `structural_positions` | `id` UUID | `code` unique | `is_active` | **Tidak** | Sebagian |
| Jenis GTK | `jenis_gtk` | `id` UUID | — | `is_active` | Tidak | Ya |
| Domain | `domains` | `id` UUID | `code` unique | `is_active` | Ya | Ya |
| Tahun ajaran | `academic_years` | `id` UUID | `(school_id,name,semester)` | `is_active` | Tidak | Ya |
| Orang/GTK | `users` | `id` UUID | `email` unique | `is_active` | Ya | Ya |
| Kepegawaian | `gtk_employments` | `id` UUID | — | `status_kepegawaian` | **Tidak** | Sebagian |
| Penempatan unit | `gtk_work_unit` | `id` UUID (baris) | `(user_id,work_unit_id)` | **Tidak ada status/tanggal** | **Tidak** | Sebagian |
| Assignment struktural | `structural_assignments` | `id` UUID | `(user_id,position_id,school_id,academic_year_id)` | `status` + `start_date`/`end_date` | Ya | **Ya** |
| Tugas tambahan (legacy) | `gtk_additional_tasks` | `id` UUID | — | `tmt`/`tst` (dihitung) | Tidak | Sebagian |
| Tugas tambahan (per sekolah) | `other_teacher_tasks` | `id` UUID | `(teacher_id,academic_year_id,task_code,study_group_id)` | `is_active` | Tidak | Sebagian |
| SK/decree | `institution_decrees` | `id` UUID | `decree_number` unique | `status` | Kolom ada, trait tidak | Sebagian |
| Riwayat mutasi | `gtk_work_unit_histories` | `id` UUID | — | append-only | Tidak | Tidak (audit) |

### 2.2 Aturan identifier

1. **UUID adalah identifier utama** untuk semua entity yang diekspos. Jangan membuat ID baru.
2. **Natural key** hanya untuk rekonsiliasi dan troubleshooting:
   - unit: `code`
   - sekolah: `npsn`
   - jabatan: `code`
   - orang: `email`
   - assignment: kombinasi unique yang sudah ada
3. **Assignment baris** memakai `id` baris, bukan ID entitas relasi. Contoh: `gtk_work_unit.id`, bukan `work_unit.id`; `structural_assignments.id`, bukan `position_id`.
4. **Tugas tambahan** berasal dari dua tabel. `id` unik di masing-masing tabel, tetapi belum tentu unik lintas tabel. Connect **wajib** menyimpan `(source, id)` sebagai kunci unik. Ini bukan identifier baru, melainkan komposit teknis yang diperlukan.
5. **Jangan memakai teks `jabatan`** sebagai identifier. Teks hanya atribut transisi.
6. **Jangan memakai `code` unit sebagai primary key integrasi**, karena admin dapat mengubahnya. UUID tetap sumber utama.

### 2.3 Hasil audit kesiapan data

#### Konsistensi identifier

| Temuan | Bukti | Dampak | Rekomendasi |
|---|---|---|---|
| Sebagian besar entity memakai UUID, tetapi ada natural key yang dapat berubah | `work_units.code` unik; `structural_positions.code` unik | Jika `code` berubah, pemetaan berbasis kode rusak | Gunakan UUID; simpan `code` sebagai atribut |
| `structural_positions.id` tidak stabil bila `JenisGtkSeeder` dijalankan | Seeder menghapus seluruh posisi lalu `updateOrCreate` | Assignment dapat menunjuk posisi yang hilang; FK `cascadeOnDelete` menghapus assignment | Jangan jalankan seeder di produksi; snapshot harus menangani posisi hilang |
| `gtk_work_unit` tidak memiliki soft delete/status | Migration hanya `timestamps` | Penghapusan baris tidak terlihat sebagai tombstone | Perlu change ledger atau rekonsiliasi penuh |
| `schools` tidak memiliki soft delete | Model `School` tanpa `SoftDeletes` | Penghapusan sekolah bersifat hard delete dan menghilang dari API | Perlu tombstone via change ledger/rekonsiliasi |
| `institution_decrees` memiliki kolom `deleted_at` tetapi model tanpa `SoftDeletes` | Migration `softDeletes()`, model `InstitutionDecree` hanya `LogsDeletion` | `deleted_at` tidak otomatis dipakai; perilaku delete tidak konsisten | Jangan andalkan `deleted_at` SK; gunakan `status` |

#### Relasi `work_units` dan `schools`

- `schools.work_unit_id` adalah FK wajib, `cascadeOnDelete`.
- **Tidak ada unique constraint** pada `schools.work_unit_id`, sehingga satu unit dapat memiliki banyak sekolah.
- Model `WorkUnit::school()` adalah `hasOne`, sedangkan `WorkUnit::schools()` adalah `hasMany`. API POS memakai `school` tunggal.
- **Rekomendasi:** endpoint Connect mengembalikan `schools: []` (array), bukan `school` tunggal. Jangan mengikuti asumsi `hasOne`.

#### Relasi GTK dengan `gtk_work_unit`

- `gtk_work_unit.user_id` → `users.id`, `work_unit_id` → `work_units.id`, `position_id` → `structural_positions.id` (nullable).
- Unique `(user_id, work_unit_id)`; satu user dapat memiliki banyak unit.
- `is_primary` tidak dijamin tunggal per user (tidak ada unique parsial).
- Tidak ada `status`, `start_date`, `end_date`, atau `deleted_at`.
- **Rekomendasi:** ekspos `id` baris, `is_primary`, `position_id`, dan `jabatan` (legacy). Connect harus memperlakukan baris yang hilang sebagai penempatan berakhir.

#### Relasi GTK dengan `structural_assignments`

- `user_id`, `position_id`, `school_id`, `academic_year_id` wajib; `decree_id` nullable.
- Unique `(user_id, position_id, academic_year_id, school_id)`.
- `status` (`active|inactive|ended`), `start_date` wajib, `end_date` nullable, soft delete.
- **Ini adalah sumber utama kewenangan struktural.**
- **Rekomendasi:** ekspos assignment beserta `is_effective` yang dihitung; jangan mengandalkan `status` saja.

#### Status aktif dan masa berlaku

| Sumber | Status | Masa berlaku | Catatan |
|---|---|---|---|
| `users` | `is_active`, `deleted_at` | — | Diperiksa saat login |
| `gtk_employments` | `status_kepegawaian` | `tmt` | Tidak ada `end_date`; status terminal menandakan nonaktif |
| `gtk_work_unit` | Tidak ada | Tidak ada | Aktif selama baris ada |
| `structural_assignments` | `status` | `start_date`/`end_date` | Sumber utama |
| `gtk_additional_tasks` | Dihitung dari `tst` | `tmt`/`tst` | Legacy |
| `other_teacher_tasks` | `is_active` | — | Per sekolah/tahun ajaran |
| `schools`, `work_units` | `is_active` | — | Unit tidak beroperasi |

#### Domain dan posisi struktural

- `structural_positions.domain_id` nullable, FK `domains`.
- `structural_positions.role_id` masih ada (legacy cutover).
- `StructuralPosition::$fillable` **tidak memuat `domain_id`**, sementara `JenisGtkSeeder` mengirim `domain_id` via `updateOrCreate()`.
- **Konsekuensi:** domain posisi hasil seeder berpotensi null; permission berbasis domain tidak terbentuk.
- **Rekomendasi:** verifikasi/backfill data sebelum integrasi; API harus mengekspos `domain` nullable dan Connect tidak boleh mengasumsikan selalu ada.

#### Histori dan soft delete

| Entity | Histori | Soft delete | Audit |
|---|---|---|---|
| `work_units` | `audit_logs` + activity log | Ya | Ya (create/update/delete) |
| `divisis` | Tidak ada | Ya | Tidak |
| `domains` | Tidak ada | Ya | Tidak |
| `schools` | Tidak ada | Tidak | Tidak |
| `structural_positions` | Tidak ada | Tidak | Tidak |
| `gtk_work_unit` | Hanya `gtk_work_unit_histories` untuk mutasi tertentu | Tidak | Tidak |
| `structural_assignments` | Status + tanggal, tanpa audit per-field | Ya | Tidak |
| `gtk_employments` | Banyak baris (de facto riwayat) | Tidak | Tidak |
| `gtk_additional_tasks` | `tmt`/`tst` | Tidak | Tidak |
| `other_teacher_tasks` | `is_active` | Tidak | Tidak |
| `institution_decrees` | activity log (`LogsDeletion`) | Kolom ada, trait tidak | Sebagian |

#### Konsistensi untuk sinkronisasi eksternal

| Kebutuhan | Status | Keterangan |
|---|---|---|
| `updated_at` per record | Tersedia di hampir semua tabel | Kecuali tabel tanpa timestamps (tidak ditemukan pada entity utama) |
| Penanda terhapus | Sebagian | Soft delete hanya pada unit/divisi/domain/assignment/user |
| Feed perubahan | **Belum ada** | `integration_event_log` bukan feed eksternal |
| Cursor stabil | **Belum ada** | Tidak ada endpoint cursor |
| Snapshot konsisten | **Belum ada** | Tidak ada endpoint snapshot |
| Audit perubahan assignment | **Belum ada** | `PermissionRebuildObserver` tidak menangani assignment (lihat §7) |

### 2.4 Verdict kesiapan

| Area | Verdict | Alasan |
|---|---|---|
| Identifier entity | **Siap dengan catatan** | UUID konsisten; `structural_positions.id` tidak stabil bila seeder dijalankan ulang |
| Relasi `work_units`–`schools` | **Siap dengan catatan** | Relasi ada, tetapi one-to-many dan `schools` tanpa soft delete |
| GTK–`gtk_work_unit` | **Siap baca, belum siap sync penuh** | Tidak ada status/masa berlaku/tombstone |
| GTK–`structural_assignments` | **Siap** | Status, tanggal, soft delete, unique key tersedia |
| Status aktif & masa berlaku | **Sebagian** | Kuat di assignment; lemah di penempatan/employment |
| Domain & posisi | **Sebagian** | `domain_id` nullable dan berpotensi tidak tersimpan saat seeding |
| Histori & soft delete | **Belum siap** | Tidak seragam; banyak entity hard delete |
| Konsistensi sinkronisasi | **Belum siap untuk changes** | Tidak ada ledger/tombstone; snapshot + polling dapat dijalankan |

**Kesimpulan:** Fase 1–2 (snapshot + list `updated_since`) dapat dimulai tanpa perubahan skema. Fase 3 (`changes` + tombstone) memerlukan change ledger dan persetujuan.

## 3. Kontrak Endpoint

### 3.1 Konvensi umum

**Base path:** `/api/connect/v1`

**Headers wajib:**

| Header | Nilai | Keterangan |
|---|---|---|
| `X-ALIM-CONNECT-KEY` | API key | Alternatif: `Authorization: Bearer <key>` |
| `Accept` | `application/json` | |
| `X-ALIM-API-Version` | `1` | Opsional pada v1, disarankan |

**Headers respons:**

| Header | Keterangan |
|---|---|
| `X-ALIM-API-Version` | Versi kontrak yang melayani request |
| `X-Request-Id` | ID request untuk korelasi log |
| `Cache-Control` | `no-store, private` |
| `Deprecation` / `Sunset` | Hanya bila endpoint akan dihapus |

**Envelope:** memakai `App\Support\ApiResponse`:

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "meta": {
    "request_id": "…",
    "timestamp": "2026-11-21T10:00:00+08:00",
    "server_time": "2026-11-21T10:00:00+08:00",
    "pagination": {
      "page": 1,
      "per_page": 50,
      "total": 120,
      "last_page": 3
    }
  },
  "errors": null
}
```

**Parameter umum:**

| Parameter | Tipe | Default | Batas | Keterangan |
|---|---|---|---|---|
| `page` | int | 1 | ≥1 | Pagination halaman |
| `per_page` | int | 50 | 1–200 | Ukuran halaman |
| `updated_since` | ISO-8601 | — | — | Perubahan sejak waktu tertentu (inklusif) |
| `is_active` | bool/`all` | `true` | — | Status aktif; `all` menonaktifkan filter |
| `include` | csv | — | — | Relasi tambahan (`divisi`, `schools`, `domain`, `position`, `decree`, `work_unit`, `academic_year`) |
| `include_deleted` | bool | `false` | — | Hanya untuk snapshot/changes; bukan untuk list biasa |

**Aturan response:**

- Semua tanggal ISO-8601 dengan timezone aplikasi.
- Field `deleted_at` hanya muncul bila `include_deleted=true`.
- `updated_at` wajib ada pada setiap item.
- `id` selalu UUID string.
- Jangan mengembalikan field yang tidak ada di §8.

**Error codes:**

| HTTP | Kondisi | Body |
|---|---|---|
| 401 | API key tidak ada/salah | `success=false`, `errors=null` |
| 403 | IP tidak diizinkan / non-HTTPS | `success=false` |
| 404 | Entity tidak ditemukan | `success=false` |
| 422 | Query tidak valid | `success=false`, `errors` berisi detail |
| 429 | Rate limit | `success=false`, header `Retry-After` |
| 503 | Integrasi dinonaktifkan / belum dikonfigurasi | `success=false` |

### 3.2 Organization Units

**`GET /organization/units`**

Query:

| Parameter | Tipe | Keterangan |
|---|---|---|
| `parent_id` | uuid | Anak langsung dari unit tertentu |
| `type` | string | `Unsur Pimpinan`, `Unit Akademik`, `Unit Penunjang Akademik`, `Unit Administrasi`, `Unit Pelayanan`, `Unit Humas Publikasi`, `Pelayanan Kesehatan` |
| `divisi_id` | uuid | Filter divisi |
| `q` | string | Cari `name`/`code` (max 100 karakter) |
| `is_active` | bool/`all` | Default `true` |
| `updated_since` | ISO-8601 | |
| `include` | csv | `divisi,schools,domain,parent` |
| `tree` | bool | `true` → `data` berupa pohon; pagination diabaikan |
| `page`, `per_page` | int | |

Item:

```json
{
  "id": "0f7f0f3e-…",
  "code": "UAK-001",
  "name": "SD IT Putra Abu Hurairah Mataram",
  "type": "Unit Akademik",
  "parent_id": "…",
  "parent": {"id": "…", "code": "…", "name": "…"},
  "divisi": {"id": "…", "kode": "PAH-SP", "nama": "Satuan Pendidikan"},
  "domain": {"id": "…", "code": "satuan_pendidikan", "name": "Satuan Pendidikan"},
  "schools": [
    {"id": "…", "npsn": "52010203", "name": "…", "school_level": "sd", "school_gender": "putra", "is_active": true}
  ],
  "is_active": true,
  "created_at": "2026-02-10T…",
  "updated_at": "2026-11-20T…"
}
```

Catatan:

- `parent_id` dapat null dan **tidak memiliki FK**; jangan mengasumsikan pohon valid.
- `divisi` dapat null (lihat risiko R2 pada dokumen audit).
- `domain` dapat null.
- `schools` selalu array, meskipun saat ini banyak unit hanya punya satu sekolah.

**`GET /organization/units/{id}`**

Mengembalikan satu unit dengan `include` default `parent,divisi,domain,schools`. 404 bila tidak ada atau sudah dihapus (kecuali `include_deleted=true`).

### 3.3 Schools

**`GET /organization/schools`**

Query: `work_unit_id`, `school_level`, `school_gender`, `q`, `is_active`, `updated_since`, `include` (`work_unit`), `page`, `per_page`.

Item:

```json
{
  "id": "…",
  "work_unit_id": "…",
  "work_unit": {"id": "…", "code": "UAK-001", "name": "…"},
  "npsn": "52010203",
  "nss": "…",
  "name": "SD IT Putra Abu Hurairah Mataram",
  "school_level": "sd",
  "school_level_text": "SD IT",
  "school_gender": "putra",
  "school_status": "swasta",
  "boarding_type": "non_boarding",
  "principal_user_id": "…",
  "principal_name": "…",
  "is_active": true,
  "created_at": "…",
  "updated_at": "…"
}
```

Catatan:

- `principal_user_id` boleh null.
- `school_status` hanya `negeri|swasta` (nilai aktual).
- `boarding_type` berasal dari migration `2026_10_11`; nilai aktual perlu diverifikasi.
- **Tidak ada `deleted_at`.** Penghapusan sekolah tidak dapat dideteksi dari record; gunakan `changes`/rekonsiliasi.

**`GET /organization/schools/{id}`** — detail dengan `work_unit`.

### 3.4 Structural Positions

**`GET /organization/positions`**

Query: `jenis_gtk_id`, `domain_id`, `level`, `kategori`, `q`, `is_active`, `updated_since`, `include` (`jenis_gtk,domain,role`), `page`, `per_page`.

Item:

```json
{
  "id": "…",
  "code": "tenaga_pengasuhan_keasramaan_kepala_asrama",
  "name": "Kepala Asrama",
  "kategori": "Jabatan",
  "level": "pondok",
  "hierarchy_level": 5,
  "urutan": 1,
  "description": null,
  "jenis_gtk": {"id": "…", "nama": "Tenaga Pengasuhan/Keasramaan"},
  "domain": {"id": "…", "code": "asrama", "name": "Asrama"},
  "is_active": true,
  "created_at": "…",
  "updated_at": "…"
}
```

Catatan:

- `domain` dapat null.
- `role` tidak diekspos secara default; `role_id` adalah mekanisme legacy cutover. Bila benar-benar dibutuhkan untuk debugging, gunakan `include=role` dan dokumentasikan sebagai legacy.
- `hierarchy_level` tidak konsisten; jangan dipakai sebagai otoritas.

**`GET /organization/positions/{id}`** — detail.

### 3.5 GTK / People

**`GET /organization/people`**

Query:

| Parameter | Keterangan |
|---|---|
| `work_unit_id` | User yang punya `gtk_work_unit` pada unit tersebut |
| `school_id` | Union: `gtk_work_unit.work_unit_id = schools.work_unit_id` **atau** `gtk_employments.school_id = school_id` |
| `position_id` | Default `any`: cocok pada `gtk_work_unit.position_id` atau `structural_assignments.position_id` aktif |
| `position_scope` | `any` (default), `placement`, `structural` |
| `status_kepegawaian` | Salah satu status enum |
| `is_active` | Default `true` (dari `users.is_active`) |
| `q` | Cari `name`, `username`, `email` |
| `updated_since` | Termasuk perubahan profil, kontak, employment, `gtk_work_unit`, assignment |
| `page`, `per_page` | |

Item:

```json
{
  "id": "…",
  "name": "Ahmad Fauzi",
  "username": "ahmad.fauzi",
  "email": "ahmad@example.test",
  "is_active": true,
  "status_kepegawaian": "GTY",
  "status_kepegawaian_text": "Guru Tetap Yayasan",
  "jenis_gtk": {"id": "…", "nama": "Tenaga Kependidikan Satuan Pendidikan"},
  "primary_unit": {"id": "…", "code": "UAK-001", "name": "…"},
  "units": [
    {
      "assignment_id": "…",
      "unit": {"id": "…", "code": "UAK-001", "name": "…"},
      "position_id": "…",
      "position_name": "Guru",
      "jabatan": "Guru",
      "is_primary": true
    }
  ],
  "active_structural_assignment_count": 2,
  "created_at": "…",
  "updated_at": "…"
}
```

**`GET /organization/people/{id}`** — detail, termasuk `units` dan `structural_assignments` ringkas (opsional `include=assignments`).

**`GET /organization/people/{id}/assignments`** — semua assignment user dari ketiga sumber (`work_unit`, `structural`, `additional_task`) dengan bentuk seragam.

### 3.6 GTK Work Unit Assignments

**`GET /organization/work-unit-assignments`**

Sumber: `gtk_work_unit` (baris penempatan). Query: `user_id`, `work_unit_id`, `position_id`, `is_primary`, `updated_since`, `include` (`user,unit,position`), `page`, `per_page`.

Item:

```json
{
  "id": "…",
  "user_id": "…",
  "work_unit_id": "…",
  "unit": {"id": "…", "code": "UAK-001", "name": "…", "type": "Unit Akademik"},
  "position_id": "…",
  "position": {"id": "…", "code": "…", "name": "Guru", "domain_id": "…"},
  "jabatan": "Guru",
  "is_primary": true,
  "created_at": "…",
  "updated_at": "…"
}
```

Catatan penting:

- **Tidak ada `status`, `start_date`, `end_date`, atau `deleted_at`.** Baris yang hilang berarti penempatan berakhir.
- `position_id` dapat null (data lama); `jabatan` hanya teks legacy.
- `is_primary` tidak dijamin hanya satu per user.
- Untuk sinkronisasi, gunakan `id` baris dan natural key `(user_id, work_unit_id)`. Bila baris dihapus dan dibuat ulang, `id` berubah; rekonsiliasi harus menangani ini.

### 3.7 Structural Assignments

**`GET /organization/structural-assignments`**

Query:

| Parameter | Keterangan |
|---|---|
| `user_id` | Filter GTK |
| `position_id` | Filter jabatan |
| `school_id` | Filter sekolah |
| `work_unit_id` | Turunan dari `schools.work_unit_id` |
| `academic_year_id` | Filter tahun ajaran |
| `status` | `active`, `inactive`, `ended`, `all` |
| `active_on` | Tanggal evaluasi; default hari ini |
| `include` | `user,position,school,work_unit,decree,academic_year` |
| `updated_since` | |
| `include_deleted` | `true` untuk sinkronisasi |
| `page`, `per_page` | |

Item:

```json
{
  "id": "…",
  "user_id": "…",
  "user": {"id": "…", "name": "Ahmad Fauzi", "is_active": true},
  "position_id": "…",
  "position": {
    "id": "…",
    "code": "tenaga_pengasuhan_keasramaan_kepala_asrama",
    "name": "Kepala Asrama",
    "domain_id": "…"
  },
  "school_id": "…",
  "school": {"id": "…", "npsn": "52010204", "name": "SMP IT Putra…"},
  "work_unit_id": "…",
  "academic_year_id": "…",
  "academic_year": {"id": "…", "name": "2026/2027", "semester": "ganjil"},
  "decree_id": "…",
  "decree": {
    "id": "…",
    "decree_number": "001/SK/ALIM/2026",
    "decree_type": "SK Kepala Sekolah",
    "effective_date": "2026-07-01",
    "end_date": "2027-06-30"
  },
  "start_date": "2026-07-01",
  "end_date": null,
  "status": "active",
  "is_effective": true,
  "additional_info": null,
  "created_at": "…",
  "updated_at": "…",
  "deleted_at": null
}
```

Aturan `is_effective`:

```
status == 'active'
AND start_date <= active_on
AND (end_date IS NULL OR end_date >= active_on)
AND deleted_at IS NULL
```

Catatan:

- `decree_id`/`decree` dapat null.
- `work_unit_id` diturunkan dari `school.work_unit_id`; bukan kolom tersimpan di assignment.
- Unique key `(user_id, position_id, school_id, academic_year_id)`.
- Ini adalah endpoint **utama** untuk kewenangan; jangan menurunkan assignment dari teks `jabatan`.

**`GET /organization/structural-assignments/{id}`** — detail.

### 3.8 Additional Tasks

**`GET /organization/additional-tasks`**

Query: `user_id`, `work_unit_id`, `school_id`, `academic_year_id`, `source` (`gtk_additional_tasks|other_teacher_tasks|all`), `active_on`, `updated_since`, `page`, `per_page`.

Item seragam:

```json
{
  "id": "…",
  "source": "other_teacher_tasks",
  "user_id": "…",
  "unit_id": "…",
  "school_id": "…",
  "academic_year_id": "…",
  "task_type_id": "…",
  "task_type": {"id": "…", "nama": "Koordinator Kurikulum"},
  "task_name": "Koordinator Kurikulum",
  "task_code": "koordinator-kurikulum",
  "start_date": null,
  "end_date": null,
  "is_active": true,
  "decree_id": "…",
  "created_at": "…",
  "updated_at": "…"
}
```

Pemetaan sumber:

| Field seragam | `gtk_additional_tasks` | `other_teacher_tasks` |
|---|---|---|
| `id` | `id` | `id` |
| `user_id` | `user_id` | `teacher_id` |
| `unit_id` | `work_unit_id` | `work_unit_id` |
| `school_id` | null (tidak ada kolom) | `school_id` |
| `academic_year_id` | null | `academic_year_id` |
| `task_type_id` | `additional_task_type_id` | `additional_task_type_id` |
| `task_name` | `nama_tugas` | `task_name` |
| `task_code` | null | `task_code` |
| `start_date` | `tmt` | null |
| `end_date` | `tst` | null |
| `is_active` | `tst` null/≥ hari ini | `is_active` |
| `decree_id` | `decree_id` | `decree_id` |

Catatan:

- `gtk_additional_tasks` adalah legacy; `other_teacher_tasks` adalah sumber utama per sekolah/tahun ajaran (lihat changelog rumpun).
- Karena dua tabel, Connect wajib memakai kunci komposit `(source, id)`.
- `gtk_additional_tasks` tidak memiliki `school_id`/`academic_year_id`; jangan menebak dari `decree`.
- `nomor_sk` tidak diekspos.

### 3.9 Organization Snapshot

**`GET /organization/snapshot`**

Query: `types` (csv: `units,schools,positions,people,work_unit_assignments,structural_assignments,additional_tasks`), `active_only` (default true), `include_deleted` (default false), `format` (`json|ndjson`), `schema_version` (default 1).

Response `json`:

```json
{
  "success": true,
  "message": "Snapshot organisasi berhasil diambil.",
  "data": {
    "generated_at": "2026-11-21T10:00:00+08:00",
    "snapshot_at": "2026-11-21T10:00:00+08:00",
    "schema_version": 1,
    "counts": {
      "units": 24,
      "schools": 9,
      "positions": 60,
      "people": 180,
      "work_unit_assignments": 240,
      "structural_assignments": 90,
      "additional_tasks": 75
    },
    "checksums": {
      "units": "sha256:…",
      "people": "sha256:…"
    },
    "units": [],
    "schools": [],
    "positions": [],
    "people": [],
    "work_unit_assignments": [],
    "structural_assignments": [],
    "additional_tasks": []
  },
  "meta": {
    "request_id": "…",
    "timestamp": "…",
    "server_time": "…"
  },
  "errors": null
}
```

Ketentuan konsistensi:

- `snapshot_at` adalah batas atas `updated_at` yang disertakan.
- Semua record dengan `updated_at <= snapshot_at` dan memenuhi filter dimasukkan.
- Setelah snapshot, Connect memanggil `changes?since=snapshot_at` untuk menangkap perubahan yang terjadi selama snapshot.
- Pastikan semua penulisan menyentuh `updated_at`. Update massal via query builder yang melewati Eloquent wajib menyetel `updated_at` secara eksplisit; jika tidak, snapshot dapat melewatkan perubahan.
- `checksums` dihitung dari daftar `id` (sorted) per entity; opsional tetapi sangat disarankan.
- Untuk data besar, `format=ndjson` mengalirkan satu objek per baris dengan header meta di baris pertama.

Batasan skala:

- Snapshot JSON sinkron cocok untuk ratusan hingga beberapa ribu record per entity.
- Bila `people > 10.000` atau `structural_assignments > 50.000`, gunakan `ndjson` dan/atau snapshot per `types`.
- Jangan memuat seluruh relasi nested secara default; gunakan `include` minimal.

### 3.10 Organization Changes

**`GET /organization/changes`**

Query: `since` (wajib, ISO-8601), `cursor` (opsional), `types` (csv), `limit` (default 200, max 500), `include` (`payload` default, `minimal`).

Response:

```json
{
  "success": true,
  "message": "Perubahan berhasil diambil.",
  "data": [
    {
      "entity": "structural_assignment",
      "action": "upsert",
      "id": "…",
      "source": null,
      "updated_at": "2026-11-21T09:15:00+08:00",
      "deleted_at": null,
      "payload": { "…": "objek seperti §3.7" }
    },
    {
      "entity": "work_unit_assignment",
      "action": "delete",
      "id": "…",
      "source": null,
      "updated_at": "2026-11-21T09:20:00+08:00",
      "deleted_at": "2026-11-21T09:20:00+08:00",
      "payload": null
    }
  ],
  "meta": {
    "request_id": "…",
    "timestamp": "…",
    "server_time": "…",
    "next_cursor": "eyJ1IjoiMjAyNi0xMS0yMVQwOToyMDowMCswODowMCIsImlkIjoiLi4uIn0",
    "has_more": false
  },
  "errors": null
}
```

Aturan:

- `since` wajib; tanpa `since`, kembalikan 422 (arahkan klien melakukan snapshot).
- `action`: `upsert` atau `delete`.
- `delete` wajib dikirim untuk soft delete/hard delete agar Connect menonaktifkan scope.
- `updated_at` adalah waktu event, bukan waktu request.
- Cursor opaque; klien tidak boleh mem-parse.
- Urutan deterministik: `(occurred_at ASC, id ASC, source ASC)`.
- Pengiriman bersifat **at-least-once**; Connect wajib idempotent.

**Catatan implementasi:** endpoint ini **belum dapat diimplementasikan secara andal** dengan skema saat ini karena tidak ada change ledger dan sebagian entity tidak memiliki soft delete. Lihat §7 untuk desain ledger dan §11 untuk dependensi.

### 3.11 Ringkasan endpoint

| Method | Path | Tujuan | Butuh change ledger? |
|---|---|---|---|
| GET | `/api/connect/v1/organization/units` | Daftar/pohon unit | Tidak |
| GET | `/api/connect/v1/organization/units/{id}` | Detail unit | Tidak |
| GET | `/api/connect/v1/organization/schools` | Daftar sekolah | Tidak |
| GET | `/api/connect/v1/organization/schools/{id}` | Detail sekolah | Tidak |
| GET | `/api/connect/v1/organization/positions` | Daftar jabatan | Tidak |
| GET | `/api/connect/v1/organization/positions/{id}` | Detail jabatan | Tidak |
| GET | `/api/connect/v1/organization/people` | Daftar GTK | Tidak |
| GET | `/api/connect/v1/organization/people/{id}` | Detail GTK | Tidak |
| GET | `/api/connect/v1/organization/people/{id}/assignments` | Assignment per orang | Tidak |
| GET | `/api/connect/v1/organization/work-unit-assignments` | Penempatan unit | Tidak |
| GET | `/api/connect/v1/organization/structural-assignments` | Assignment struktural | Tidak |
| GET | `/api/connect/v1/organization/additional-tasks` | Tugas tambahan | Tidak |
| GET | `/api/connect/v1/organization/snapshot` | Bootstrap/rekonsiliasi | Tidak |
| GET | `/api/connect/v1/organization/changes` | Incremental + tombstone | **Ya** (fase 3) |

## 4. Contoh Request dan Response JSON

### 4.1 Daftar unit dengan filter

Request:

```http
GET /api/connect/v1/organization/units?type=Unit%20Akademik&is_active=true&include=schools,divisi&page=1&per_page=50 HTTP/1.1
Host: alim.example.test
Accept: application/json
X-ALIM-CONNECT-KEY: <redacted>
X-ALIM-API-Version: 1
```

Response:

```json
{
  "success": true,
  "message": "Daftar unit berhasil diambil.",
  "data": [
    {
      "id": "0f7f0f3e-1111-4a2b-9c3d-000000000001",
      "code": "UAK-001",
      "name": "SD IT Putra Abu Hurairah Mataram",
      "type": "Unit Akademik",
      "parent_id": "0f7f0f3e-1111-4a2b-9c3d-000000000000",
      "divisi": {
        "id": "d1v1s1-1111-4a2b-9c3d-000000000001",
        "kode": "PAH-SP",
        "nama": "Satuan Pendidikan"
      },
      "schools": [
        {
          "id": "5c400000-1111-4a2b-9c3d-000000000001",
          "npsn": "52010203",
          "name": "SD IT Putra Abu Hurairah Mataram",
          "school_level": "sd",
          "school_gender": "putra",
          "is_active": true
        }
      ],
      "is_active": true,
      "created_at": "2026-02-10T08:00:00+08:00",
      "updated_at": "2026-11-20T08:12:00+08:00"
    }
  ],
  "meta": {
    "request_id": "req-0001",
    "timestamp": "2026-11-21T10:00:00+08:00",
    "server_time": "2026-11-21T10:00:00+08:00",
    "pagination": {"page": 1, "per_page": 50, "total": 1, "last_page": 1}
  },
  "errors": null
}
```

### 4.2 Structural assignments aktif

Request:

```http
GET /api/connect/v1/organization/structural-assignments?user_id=usr-ahmad&active_on=2026-11-21&status=active&include=position,school,work_unit,decree
```

Response:

```json
{
  "success": true,
  "message": "Daftar assignment struktural berhasil diambil.",
  "data": [
    {
      "id": "sa-0001",
      "user_id": "usr-ahmad",
      "position_id": "sp-kepala-asrama",
      "position": {
        "id": "sp-kepala-asrama",
        "code": "tenaga_pengasuhan_keasramaan_kepala_asrama",
        "name": "Kepala Asrama",
        "domain_id": "dom-asrama"
      },
      "school_id": "sch-smp-putra",
      "school": {"id": "sch-smp-putra", "npsn": "52010204", "name": "SMP IT Putra Abu Hurairah Mataram"},
      "work_unit_id": "wu-asrama-putra",
      "academic_year_id": "ay-2026-ganjil",
      "academic_year": {"id": "ay-2026-ganjil", "name": "2026/2027", "semester": "ganjil"},
      "decree_id": "sk-2026-001",
      "decree": {
        "id": "sk-2026-001",
        "decree_number": "001/SK/ALIM/2026",
        "decree_type": "SK Kepala Sekolah",
        "effective_date": "2026-07-01",
        "end_date": "2027-06-30"
      },
      "start_date": "2026-07-01",
      "end_date": null,
      "status": "active",
      "is_effective": true,
      "additional_info": null,
      "created_at": "2026-07-01T08:00:00+08:00",
      "updated_at": "2026-11-20T08:12:00+08:00"
    }
  ],
  "meta": {
    "request_id": "req-0002",
    "timestamp": "2026-11-21T10:00:00+08:00",
    "pagination": {"page": 1, "per_page": 50, "total": 1, "last_page": 1}
  },
  "errors": null
}
```

### 4.3 Additional tasks (dua sumber)

Request:

```http
GET /api/connect/v1/organization/additional-tasks?user_id=usr-ahmad&active_on=2026-11-21
```

Response:

```json
{
  "success": true,
  "message": "Daftar tugas tambahan berhasil diambil.",
  "data": [
    {
      "id": "ott-0001",
      "source": "other_teacher_tasks",
      "user_id": "usr-ahmad",
      "unit_id": "wu-sd-putra",
      "school_id": "sch-sd-putra",
      "academic_year_id": "ay-2026-ganjil",
      "task_type_id": "att-koord-kurikulum",
      "task_type": {"id": "att-koord-kurikulum", "nama": "Koordinator Kurikulum"},
      "task_name": "Koordinator Kurikulum",
      "task_code": "koordinator-kurikulum",
      "start_date": null,
      "end_date": null,
      "is_active": true,
      "decree_id": "sk-2026-002",
      "created_at": "2026-07-01T08:00:00+08:00",
      "updated_at": "2026-11-20T08:12:00+08:00"
    },
    {
      "id": "gat-0001",
      "source": "gtk_additional_tasks",
      "user_id": "usr-ahmad",
      "unit_id": "wu-asrama-putra",
      "school_id": null,
      "academic_year_id": null,
      "task_type_id": "att-wali-kamar",
      "task_type": {"id": "att-wali-kamar", "nama": "Wali Kamar"},
      "task_name": "Wali Kamar",
      "task_code": null,
      "start_date": "2026-07-01",
      "end_date": "2027-06-30",
      "is_active": true,
      "decree_id": null,
      "created_at": "2026-07-01T08:00:00+08:00",
      "updated_at": "2026-07-01T08:00:00+08:00"
    }
  ],
  "meta": {
    "request_id": "req-0003",
    "timestamp": "2026-11-21T10:00:00+08:00",
    "pagination": {"page": 1, "per_page": 50, "total": 2, "last_page": 1}
  },
  "errors": null
}
```

### 4.4 Snapshot (meta)

Request:

```http
GET /api/connect/v1/organization/snapshot?types=units,schools,positions,people,structural_assignments&active_only=true&format=ndjson
```

Response header:

```http
HTTP/1.1 200 OK
Content-Type: application/x-ndjson
X-ALIM-API-Version: 1
X-Request-Id: req-0004
Cache-Control: no-store, private
```

Baris pertama:

```json
{"type":"meta","generated_at":"2026-11-21T10:00:00+08:00","snapshot_at":"2026-11-21T10:00:00+08:00","schema_version":1,"counts":{"units":24,"schools":9,"positions":60,"people":180,"structural_assignments":90}}
```

Baris berikutnya (contoh):

```json
{"type":"unit","data":{"id":"…","code":"UAK-001","name":"…","type":"Unit Akademik","is_active":true,"updated_at":"…"}}
{"type":"school","data":{"id":"…","work_unit_id":"…","npsn":"52010203","name":"…","is_active":true,"updated_at":"…"}}
{"type":"structural_assignment","data":{"id":"…","user_id":"…","position_id":"…","school_id":"…","academic_year_id":"…","start_date":"2026-07-01","end_date":null,"status":"active","is_effective":true,"updated_at":"…"}}
```

### 4.5 Changes dengan tombstone

Request:

```http
GET /api/connect/v1/organization/changes?since=2026-11-21T09:00:00%2B08:00&limit=200&types=units,structural_assignments,work_unit_assignments
```

Response:

```json
{
  "success": true,
  "message": "Perubahan berhasil diambil.",
  "data": [
    {
      "entity": "unit",
      "action": "upsert",
      "id": "…",
      "source": null,
      "updated_at": "2026-11-21T09:15:00+08:00",
      "deleted_at": null,
      "payload": {"id": "…", "code": "UAK-001", "name": "…", "is_active": true, "updated_at": "2026-11-21T09:15:00+08:00"}
    },
    {
      "entity": "work_unit_assignment",
      "action": "delete",
      "id": "…",
      "source": null,
      "updated_at": "2026-11-21T09:20:00+08:00",
      "deleted_at": "2026-11-21T09:20:00+08:00",
      "payload": null
    }
  ],
  "meta": {
    "request_id": "req-0005",
    "timestamp": "2026-11-21T10:00:00+08:00",
    "server_time": "2026-11-21T10:00:00+08:00",
    "next_cursor": "eyJvIjoiMjAyNi0xMS0yMVQwOToyMDowMCswODowMCIsImlkIjoiLi4uIiwicyI6bnVsbH0",
    "has_more": false
  },
  "errors": null
}
```

### 4.6 Error autentikasi

```json
{
  "success": false,
  "message": "API key tidak valid atau tidak dikirim.",
  "data": null,
  "meta": {"request_id": "req-0006", "timestamp": "2026-11-21T10:00:00+08:00"},
  "errors": null
}
```

## 5. Autentikasi dan Keamanan

### 5.1 Evaluasi reuse `VerifyIntegrationApiKey` dan `IntegrationApiConfig`

**Hasil evaluasi: layak dipakai ulang tanpa mengubah POS/Recruitment.**

| Komponen existing | Perilaku | Dapat dipakai untuk Connect? |
|---|---|---|
| `App\Http\Middleware\VerifyIntegrationApiKey` | abstract; membaca `configPrefix()`, `integrationName()` | Ya, buat subclass |
| `VerifyPosApiKey` / `VerifyRecruitmentApiKey` | subclass dengan prefix `pos`/`recruitment` | Tidak diubah |
| `App\Support\IntegrationApiConfig` | generik: `enabled`, `key`, `header`, `allowedIps`, `storeKey`, `generateKey` | Ya, prefix `connect` otomatis |
| `config/services.php` | blok `pos`, `recruitment` | Tambah blok `connect` (aditif) |
| `system_settings` | menyimpan `{prefix}_api_key`, `_api_header`, `_api_allowed_ips`, `_api_enabled` | Ya, key `connect_*` |
| `ApiResponse` | envelope standar | Ya |
| `IntegrationController` | helper `perPage`, `searchTerm`, `dateFilter` | Ya |

**Komponen baru yang diusulkan (belum diimplementasikan):**

1. `App\Http\Middleware\VerifyConnectApiKey` — subclass `VerifyIntegrationApiKey`:
   - `configPrefix(): 'connect'`
   - `integrationName(): 'ALIM Connect'`
2. Alias middleware di `app/Http/Kernel.php`:
   - `'connect.api-key' => VerifyConnectApiKey::class`
3. Blok `connect` di `config/services.php`:
   - `api_key` ← `ALIM_CONNECT_API_KEY`
   - `header` ← `ALIM_CONNECT_API_HEADER` (default `X-ALIM-CONNECT-KEY`)
   - `allowed_ips` ← `ALIM_CONNECT_ALLOWED_IPS`
   - `require_https` ← `ALIM_CONNECT_REQUIRE_HTTPS` (default true)
   - `auth_fail_limit` ← `ALIM_CONNECT_AUTH_FAIL_LIMIT` (default 10)
4. Route group read-only:
   - `Route::middleware(['connect.api-key', 'throttle:connect-api'])->prefix('connect/v1')->group(...)`
   - Semua route hanya `GET`.
5. Rate limiter `connect-api` di `RouteServiceProvider` (aditif; limiter existing tidak diubah).
6. Middleware/terminable `LogConnectAccess` untuk audit akses sukses (lihat §7).

### 5.2 Persyaratan keamanan dan pemenuhannya

| Persyaratan | Desain |
|---|---|
| API key khusus aplikasi | Prefix `connect`, key terpisah dari POS/Recruitment; disimpan di `system_settings` terenkripsi (`enc:v1:`) atau env |
| Read-only access | Hanya route `GET`; tidak ada route tulis di group Connect |
| HTTPS | `VerifyIntegrationApiKey::shouldForceHttps()` menolak non-HTTPS di produksi |
| Rate limiting | `throttle:connect-api` per key/IP; ditambah limit percobaan auth gagal existing |
| IP restriction | `allowed_ips` di `connect` config/system setting |
| Audit log | Auth gagal sudah dicatat base middleware; akses sukses dicatat `LogConnectAccess` |
| Pemisahan kredensial | Key Connect tidak valid untuk POS/Recruitment, dan sebaliknya (prefix berbeda) |
| Least privilege | Tidak ada scope tulis; key hanya untuk endpoint Connect |
| Secret handling | Jangan pernah mengembalikan key di response/log; bandingkan dengan `hash_equals` |
| Response caching | `Cache-Control: no-store, private` |

### 5.3 Rate limiting

Base middleware hanya membatasi **percobaan autentikasi gagal** per IP (default 10/menit). Untuk trafik sah, tambahkan named limiter:

```php
// Usulan, bukan implementasi
RateLimiter::for('connect-api', function (Request $request) {
    $key = hash('sha256', (string) $request->header('X-ALIM-CONNECT-KEY'));
    return [
        Limit::perMinute(120)->by('connect:'.$key.':'.$request->ip()),
        Limit::perMinute(6)->by('connect-snapshot:'.$key)->response(
            fn () => response()->json([
                'success' => false,
                'message' => 'Terlalu banyak permintaan snapshot.',
                'data' => null,
            ], 429)
        ),
    ];
});
```

Catatan:

- Jangan memakai API key mentah sebagai cache key; hash terlebih dahulu.
- Snapshot lebih berat, sehingga limit terpisah lebih rendah.
- `changes` sebaiknya memiliki limit per menit yang lebih tinggi daripada snapshot, tetapi lebih rendah daripada list biasa.
- Jika key tidak ada, limiter tetap berjalan berdasarkan IP untuk mencegah enumerasi.

### 5.4 Siklus hidup API key

1. **Pembuatan:** `IntegrationApiConfig::generateKey()` (32 byte hex) atau secret manager.
2. **Penyimpanan:** `system_settings` dengan prefix `enc:v1:`; jangan di env produksi jika memungkinkan.
3. **Rotasi:**
   - Idealnya dua key aktif (current + previous) selama masa transisi.
   - `IntegrationApiConfig` saat ini hanya mendukung satu key per prefix. Usulan: `VerifyConnectApiKey` memeriksa `connect_api_key` dan opsional `connect_api_key_previous` dengan masa berlaku.
   - Alternatif tanpa perubahan kode: rotasi pada jendela maintenance, update Connect lebih dulu, lalu Core.
4. **Revocation:** hapus/disable key (`connect_api_enabled=false`) atau ganti key.
5. **Audit:** catat pembuatan/rotasi/penonaktifan key di `audit_logs` (tanpa nilai key).

### 5.5 Audit akses

- **Auth gagal:** sudah dicatat `VerifyIntegrationApiKey` (IP, path, alasan; tanpa token).
- **Auth sukses:** tambahkan `LogConnectAccess` (terminable middleware) yang mencatat:
  - `action`: `CONNECT_API_READ`
  - `table_name`: null
  - `record_id`: null
  - `metadata`: `{route, method, status, query_keys, response_count, duration_ms, key_fingerprint}`
  - `ip_address`, `user_agent`, `request_id`
- **Jangan** mencatat query values yang mengandung data pribadi; cukup nama parameter.
- **Volume:** tulis setelah response (`terminate`) dan/atau queue. Untuk endpoint list berfrekuensi tinggi, sampling 10–25% dapat dipertimbangkan, tetapi snapshot/changes/auth failure harus 100%.
- **Retensi:** 90–180 hari untuk akses; audit perubahan organisasi lebih lama (lihat §7).

### 5.6 Threat model ringkas

| Ancaman | Dampak | Mitigasi |
|---|---|---|
| Key bocor | Akses baca seluruh data | Rotasi cepat, IP allowlist, rate limit, audit, read-only |
| Enumerasi ID | Mengambil data di luar scope | UUID non-sequential; tidak ada endpoint pencarian bebas tanpa filter |
| Replay request | Pengambilan ulang data | Idempotent read; rate limit; tidak ada efek samping |
| Query injection | Eksekusi query berbahaya | Eloquent/parameter binding; validasi enum/boolean/UUID |
| Mass assignment | Tidak berlaku | Read-only, tidak ada input model |
| PII leakage | Pelanggaran privasi | Matriks akses §8; select kolom eksplisit |
| DoS snapshot | Beban server | Limit snapshot, ndjson, `types`, queue/off-peak |
| Cross-app key misuse | POS/Recruitment terganggu | Prefix key terpisah; tidak mengubah middleware existing |

### 5.7 Dampak terhadap integrasi existing

- Tidak ada perubahan pada `VerifyPosApiKey`, `VerifyRecruitmentApiKey`, `config/services.php` blok existing, atau route existing.
- Penambahan alias middleware dan blok config bersifat aditif.
- Key POS/Recruitment tidak dapat dipakai di Connect karena prefix berbeda.
- Regression test wajib membuktikan endpoint POS/Recruitment tetap berfungsi (lihat §10).

## 6. Strategi Snapshot dan Incremental Sync

### 6.1 Model sinkronisasi

ALIM Connect memakai pola **snapshot + change feed + reconciliation**:

```text
Bootstrap:
  snapshot(snapshot_at) ──► upsert read model ──► simpan last_snapshot_at

Rutin:
  changes(since=last_cursor) ──► upsert/delete ──► simpan next_cursor

Berkala:
  full snapshot / checksum compare ──► deteksi drift ──► repair
```

Sampai change ledger tersedia (§7), pola yang dapat dijalankan:

```text
snapshot penuh berkala
  + list endpoint dengan updated_since
  + rekonsiliasi ID (deteksi record hilang)
```

### 6.2 Full snapshot

**Tujuan:** bootstrap dan rekonsiliasi penuh.

**Langkah:**

1. Panggil `GET /organization/snapshot` dengan `types` yang dibutuhkan.
2. Simpan `snapshot_at` dan `schema_version`.
3. Upsert setiap record berdasarkan `id` (dan `source` untuk additional tasks).
4. Setelah snapshot selesai, panggil `changes?since=snapshot_at` untuk menangkap perubahan selama snapshot berlangsung.
5. Simpan `last_snapshot_at` dan `last_cursor`.

**Konsistensi:**

- `snapshot_at` harus menjadi batas atas `updated_at` yang disertakan.
- Record yang berubah setelah `snapshot_at` akan muncul di `changes`.
- Bila `changes` belum tersedia, jalankan snapshot kedua setelah jeda singkat dan bandingkan checksum; ini bukan solusi ideal, hanya fallback.

**Skala:**

- JSON sinkron untuk data kecil/menengah.
- `ndjson` untuk data besar.
- Snapshot per `types` untuk mengurangi memori.
- Jangan memuat relasi nested secara default.

### 6.3 Incremental changes

**Sumber perubahan yang tersedia saat ini:**

- `updated_at` pada sebagian besar tabel.
- Soft delete (`deleted_at`) pada unit/divisi/domain/assignment/user.
- Tidak ada feed event eksternal.
- `integration_event_log` bukan feed perubahan organisasi.

**Desain endpoint `changes`:**

- Berbasis tabel ledger `organization_change_log` (diusulkan §7) atau, sementara, query union dari tabel dengan `updated_at > since`.
- Query union harus menyertakan **hard delete**? Tidak bisa; karena itu ledger diperlukan.
- Bila memakai query union sementara:
  - Kumpulkan kandidat per entity dengan `updated_at > since`.
  - Urutkan `(updated_at, id)`.
  - Tidak dapat mendeteksi hard delete.
  - Gunakan rekonsiliasi untuk menutup gap.

**Parameter:**

- `since` wajib.
- `cursor` opaque untuk melanjutkan batch.
- `limit` 1–500.
- `types` untuk membatasi entity.
- `include=payload|minimal`.

### 6.4 Cursor pagination

**Tujuan:** menghindari skip/duplikat saat data berubah selama iterasi.

**Format usulan:**

- Cursor = base64url dari JSON `{"o":"<occurred_at ISO>","id":"<uuid>","s":"<source|null>"}`.
- **Urutan utama disarankan memakai `id` auto-increment ledger**, bukan `occurred_at`, karena commit transaksi dapat terjadi tidak berurutan. `occurred_at` dipakai untuk filter `since` dengan toleransi overlap (mis. 5 menit).
- Query: `WHERE id > cursor.id AND occurred_at >= since - overlap ORDER BY id ASC`.
- Untuk query union sementara tanpa ledger, gunakan `(updated_at, id)` dengan pemahaman duplikat mungkin terjadi.
- Untuk MySQL, gunakan perbandingan tuple atau `OR` bertingkat yang di-index.
- Cursor tidak boleh memuat offset.
- Cursor harus ditandatangani (HMAC) atau divalidasi agar tidak dapat dimanipulasi klien.
- Cursor kedaluwarsa (mis. > 24 jam) → 410/422, klien harus snapshot ulang.

**Index yang dibutuhkan (jika ledger dibuat):**

- `(occurred_at, id)`
- `(entity_type, occurred_at, id)`
- `(aggregate_id, occurred_at)`

### 6.5 Idempotent upsert

**Aturan di Connect:**

1. Kunci unik: `(entity_type, external_id)`; khusus additional tasks `(entity_type, source, external_id)`.
2. Simpan `source_updated_at` dan `payload_hash`.
3. Upsert hanya bila:
   - record belum ada, atau
   - `incoming.source_updated_at > existing.source_updated_at`, atau
   - sama tetapi `incoming.id` lebih besar (tie-breaker).
4. Bila `payload_hash` sama, lewati update.
5. Terapkan dalam transaksi per batch.
6. Batch 200–500 record; commit per batch.
7. Jangan menghapus hard record; tandai `deleted_at` lokal.

**Contoh pseudo-SQL:**

```sql
INSERT INTO org_units (external_id, code, name, ..., source_updated_at, payload_hash)
VALUES (...)
ON CONFLICT (external_id) DO UPDATE SET
  code = EXCLUDED.code,
  name = EXCLUDED.name,
  ...,
  source_updated_at = EXCLUDED.source_updated_at,
  payload_hash = EXCLUDED.payload_hash,
  synced_at = now()
WHERE org_units.source_updated_at < EXCLUDED.source_updated_at
   OR (org_units.source_updated_at = EXCLUDED.source_updated_at
       AND org_units.external_id < EXCLUDED.external_id);
```

### 6.6 Penanganan data nonaktif

| Kondisi Core | Tindakan Connect |
|---|---|
| `users.is_active = false` | Nonaktifkan user, cabut scope, suspend Teams membership |
| `status_kepegawaian` = `Berhenti`/`Wafat`/`Pensiun` | Sama; assignment tidak efektif |
| `work_units.is_active = false` | Nonaktifkan unit; jangan hapus assignment historis |
| `schools.is_active = false` | Nonaktifkan sekolah; tutup scope akademik |
| `structural_assignments.status` bukan `active` | Assignment tidak efektif |
| `end_date` lewat | Assignment tidak efektif meskipun status masih `active` |
| `gtk_work_unit` hilang | Penempatan berakhir; evaluasi ulang scope |
| `additional_task.is_active = false` | Tugas tidak efektif |

**Prinsip:** nonaktif ≠ hapus. Connect menyimpan histori dan alasan nonaktif untuk audit.

### 6.7 Penanganan perubahan assignment dan kewenangan

1. Setiap batch `changes` yang memuat assignment memicu:
   - update read model,
   - tandai user terdampak,
   - enqueue job `ReevaluateUserScope`.
2. Job menghitung ulang:
   - unit_ids efektif,
   - school_ids efektif,
   - position/domain (jika dipakai),
   - keanggotaan Teams.
3. Perubahan yang mencabut akses harus diproses lebih dulu daripada pemberian akses baru (deny-first).
4. Bila user dinonaktifkan, cabut semua scope sebelum memproses assignment baru.
5. Simpan `scope_version` per user; naikkan saat assignment berubah; gunakan untuk invalidasi cache.

### 6.8 Rekonsiliasi berkala

**Frekuensi usulan:**

- Incremental: setiap 5–15 menit.
- Rekonsiliasi checksum: harian.
- Full snapshot: mingguan atau setelah perubahan besar.

**Metode:**

1. Ambil `counts` dan `checksums` snapshot.
2. Bandingkan dengan read model:
   - jumlah per entity,
   - hash daftar ID,
   - `max(updated_at)`.
3. Bila berbeda:
   - cari ID yang hilang/berlebih,
   - tandai record hilang sebagai tombstone,
   - perbaiki record yang berbeda hash.
4. Catat hasil rekonsiliasi di `sync_state` dan alert bila drift > ambang.

### 6.9 Kegagalan, retry, dan concurrency

- Retry dengan exponential backoff + jitter.
- Retry hanya untuk 429/5xx/timeout; 4xx lain → log dan hentikan.
- Simpan `last_error`, `last_success_at`, `attempt`.
- Gunakan advisory lock/DB lock agar hanya satu worker sync per entity berjalan.
- Jangan menjalankan snapshot dan changes bersamaan tanpa koordinasi; gunakan `snapshot_at` sebagai batas.
- Bila cursor hilang/kedaluwarsa, lakukan snapshot ulang.

### 6.10 Model read model di Connect

```text
org_units
  external_id PK, code, name, type, parent_external_id, divisi_external_id,
  domain_external_id, is_active, source_updated_at, payload_hash, synced_at, deleted_at

org_schools
  external_id PK, work_unit_external_id, npsn, name, school_level, school_gender,
  school_status, boarding_type, principal_user_id, is_active,
  source_updated_at, payload_hash, synced_at, deleted_at

org_positions
  external_id PK, code, name, kategori, level, hierarchy_level, urutan,
  jenis_gtk_external_id, domain_external_id, is_active,
  source_updated_at, payload_hash, synced_at, deleted_at

org_people
  external_id PK, name, username, email, is_active, status_kepegawaian,
  jenis_gtk_external_id, primary_unit_external_id,
  source_updated_at, payload_hash, synced_at, deleted_at

org_work_unit_assignments
  external_id PK, user_external_id, work_unit_external_id, position_external_id,
  jabatan, is_primary, natural_key_hash,
  source_updated_at, payload_hash, synced_at, deleted_at

org_structural_assignments
  external_id PK, user_external_id, position_external_id, school_external_id,
  work_unit_external_id, academic_year_external_id, decree_external_id,
  start_date, end_date, status, is_effective,
  source_updated_at, payload_hash, synced_at, deleted_at

org_additional_tasks
  source, external_id, user_external_id, unit_external_id, school_external_id,
  academic_year_external_id, task_type_external_id, task_name, task_code,
  start_date, end_date, is_active,
  source_updated_at, payload_hash, synced_at, deleted_at,
  PRIMARY KEY (source, external_id)

sync_state
  entity, last_cursor, last_snapshot_at, last_success_at, last_error, attempt
```

### 6.11 Urutan bootstrap yang disarankan

1. `units` (fondasi hierarki)
2. `schools` (butuh unit)
3. `positions` + `jenis_gtk` + `domains` (referensi)
4. `people` (identitas)
5. `work_unit_assignments`
6. `structural_assignments`
7. `additional_tasks`
8. Hitung ulang scope dan keanggotaan Teams
9. Simpan cursor dan mulai incremental

### 6.12 Index dan query

Index yang sudah tersedia (dari migration):

- `work_units.code` unique.
- `schools.npsn` unique; `schools.work_unit_id` index.
- `gtk_work_unit` unique `(user_id, work_unit_id)`.
- `structural_assignments` unique `(user_id, position_id, academic_year_id, school_id)`; index `user_id`, `position_id`, `school_id`, `academic_year_id`, `status`.
- `other_teacher_tasks` unique `(teacher_id, academic_year_id, task_code, study_group_id)`; index `(school_id, academic_year_id)`.
- `users.email` unique.

Index tambahan yang disarankan (evaluasi dengan `EXPLAIN`, jangan optimasi prematur):

| Tabel | Index usulan | Alasan |
|---|---|---|
| `work_units` | `(parent_id)` | Traversal pohon/filter anak |
| `work_units` | `(updated_at, id)` | `updated_since` + cursor |
| `schools` | `(updated_at, id)` | `updated_since` |
| `structural_positions` | `(updated_at, id)` | `updated_since` |
| `structural_assignments` | `(status, start_date, end_date)` | Evaluasi `is_effective` |
| `structural_assignments` | `(updated_at, id)` | Incremental |
| `gtk_work_unit` | `(work_unit_id, user_id)` | Filter per unit ( jika FK belum membuat index komposit) |
| `other_teacher_tasks` | `(teacher_id, is_active)` | Filter orang/tugas aktif |
| `gtk_additional_tasks` | `(user_id, tst)` | Filter tugas aktif |
| `users` | `(is_active, updated_at)` | Filter people aktif |

Catatan:

- Jangan menambah index pada tabel besar tanpa mengukur write amplification.
- Untuk cursor, index komposit `(updated_at, id)` lebih efektif daripada index terpisah.
- Hindari `LIKE '%...%'` pada kolom besar; gunakan prefix search bila memungkinkan.
- Batasi `include` agar tidak memicu N+1; gunakan eager loading eksplisit di service.

## 7. Mekanisme Tombstone dan Audit Trail

### 7.1 Kondisi audit saat ini

| Entity | `audit_logs` eksplisit | Spatie `activity_log` | Observer permission | Soft delete |
|---|---|---|---|---|
| `work_units` | Ya (create/update/delete) | Ya (`LogsDeletion`) | Tidak | Ya |
| `divisis` | Tidak | Tidak | Tidak | Ya |
| `domains` | Tidak | Tidak | Tidak | Ya |
| `schools` | Tidak | Tidak | Tidak | Tidak |
| `structural_positions` | Tidak | Tidak | Tidak | Tidak |
| `gtk_work_unit` | Tidak | Tidak | Tidak | Tidak |
| `structural_assignments` | Tidak | Tidak | Terdaftar, **tidak ter-handle** | Ya |
| `gtk_employments` | Tidak | Tidak | Ya (`GtkEmploymentObserver`) | Tidak |
| `gtk_additional_tasks` | Tidak | Tidak | Ya + observer notifikasi | Tidak |
| `other_teacher_tasks` | Tidak | Tidak | Tidak | Tidak |
| `additional_task_types` | Tidak | Tidak | Tidak | Tidak |
| `institution_decrees` | Tidak | Ya (`LogsDeletion`) | Tidak | Kolom ada, trait tidak |
| `users` | Ya (beberapa aksi) | Tidak | Ya | Ya |

Kesimpulan: **tidak ada audit trail organisasi yang lengkap dan konsisten.** `audit_logs` hanya mencakup sebagian entity; `activity_log` juga tidak lengkap; dan tidak ada before/after diff yang seragam.

### 7.2 Gap utama audit dan tombstone

| Gap | Bukti | Dampak |
|---|---|---|
| Tidak ada change ledger | Tidak ada tabel event organisasi; `integration_event_log` dipakai modul lain | `changes` tidak dapat mendeteksi hard delete |
| Banyak entity tanpa soft delete | `schools`, `structural_positions`, `gtk_work_unit`, `gtk_employments`, `gtk_additional_tasks`, `other_teacher_tasks` | Penghapusan hilang tanpa jejak |
| Audit tidak seragam | Hanya `work_units` yang eksplisit menulis `audit_logs` | Tidak dapat menjawab "siapa mengubah apa, kapan" |
| Observer permission tidak menangani assignment | `PermissionRebuildObserver` mendaftarkan `StructuralAssignment`/`HomeroomAssignment`/`CoordinatorAssignment`, tetapi `match(true)` tidak memiliki cabang untuk ketiganya (default `[]`) | Perubahan assignment dapat tidak memicu rebuild snapshot |
| `JenisGtkSeeder` menghapus posisi | Bulk delete melewati model events | Ledger/observer tidak menangkap; assignment cascade terhapus |
| `InstitutionDecree` `deleted_at` tidak aktif | Kolom ada, model tanpa `SoftDeletes` | Status penghapusan tidak konsisten |

### 7.3 Desain change ledger (diusulkan)

**Nama usulan:** `organization_change_log` (append-only).

**Alasan:** memisahkan audit internal dari feed integrasi, menghindari mengekspos metadata audit sensitif, dan menyediakan tombstone untuk hard delete.

**Skema usulan (bukan implementasi):**

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint unsigned PK | Urutan global |
| `entity_type` | string(64) | `unit`, `school`, `position`, `person`, `work_unit_assignment`, `structural_assignment`, `additional_task`, `decree` |
| `entity_id` | uuid | ID kanonik |
| `entity_source` | string(32) nullable | `gtk_additional_tasks` / `other_teacher_tasks` |
| `action` | string(16) | `created`, `updated`, `deleted`, `restored`, `deactivated` |
| `changed_fields` | json nullable | Daftar field yang berubah |
| `before` | json nullable | Snapshot field terpilih sebelum (redacted) |
| `after` | json nullable | Snapshot field terpilih sesudah (redacted) |
| `actor_id` | uuid nullable | User pelaku (null untuk sistem/integrasi) |
| `source` | string(32) | `web`, `api`, `seeder`, `system` |
| `request_id` | string(64) nullable | Korelasi |
| `occurred_at` | timestamp(6) | Waktu event |
| `schema_version` | smallint | Versi payload |

**Index:** `(occurred_at, id)`, `(entity_type, occurred_at, id)`, `(entity_id, occurred_at)`.

**Penulisan:**

- Melalui model observer/service yang sudah ada, bukan di controller.
- Untuk operasi bulk, gunakan service yang menulis ledger per record atau satu event dengan metadata `affected_ids`.
- `deleted` harus ditulis **sebelum** hard delete (observer `deleting`) dan untuk soft delete (observer `deleted`/`updated`).
- `created`/`updated` menulis diff field yang relevan; jangan menyimpan PII mentah.
- Idempotensi: `(entity_type, entity_id, occurred_at, action)` unik opsional untuk mencegah duplikat.

**Alternatif yang dipertimbangkan:**

| Alternatif | Kelebihan | Kekurangan | Keputusan |
|---|---|---|---|
| Memakai `audit_logs` sebagai feed | Tidak perlu tabel baru | Tidak ada before/after seragam; mencampur audit internal & integrasi; tidak append-only | Tidak disarankan untuk feed |
| Soft delete semua tabel | Tombstone alami | Perubahan perilaku besar; query existing terdampak | Tidak disarankan |
| Rekonsiliasi penuh periodik | Tidak perlu skema baru | Tidak real-time; mahal; tidak mendeteksi perubahan cepat | Fallback sementara |
| `organization_change_log` | Lengkap, terpisah, scalable | Perlu migration + observer | **Direkomendasikan** (fase 3) |

### 7.3.1 Konsumsi tombstone di Connect

Saat menerima `action=delete`:

1. Set `deleted_at` lokal dan `is_active=false` (jangan hard delete).
2. Hapus dari scope efektif (unit/sekolah/assignment).
3. Cabut keanggotaan Teams secara asinkron dengan audit alasan.
4. Simpan `source_updated_at` terakhir untuk mencegah record lama muncul kembali.
5. Bila record yang sama muncul lagi dengan `action=upsert` dan `updated_at` lebih baru, pulihkan (`deleted_at=null`) lalu evaluasi ulang scope.
6. Jangan menghapus tombstone sampai minimal satu siklus rekonsiliasi penuh berhasil.
7. Untuk hard delete tanpa ledger (kondisi saat ini), deteksi lewat rekonsiliasi: record yang ada di Connect tetapi tidak ada di snapshot baru → tombstone.

Tombstone juga harus diterapkan pada assignment:

- `work_unit_assignment` dihapus → hapus penempatan dari scope.
- `structural_assignment` dihapus/ended → cabut kewenangan.
- `additional_task` nonaktif/dihapus → cabut konteks tugas.
- `person` nonaktif → cabut seluruh scope, terlepas dari assignment lain.

### 7.4 Taksonomi event audit

| Event | Entity | Data minimum |
|---|---|---|
| `unit.created` / `unit.updated` / `unit.deleted` | `work_units` | id, code, name, parent_id, divisi_id, domain_id, is_active |
| `school.created` / `school.updated` / `school.deleted` | `schools` | id, npsn, name, work_unit_id, school_level, is_active |
| `position.created` / `position.updated` / `position.deleted` | `structural_positions` | id, code, name, domain_id, is_active |
| `person.status_changed` | `users` | id, is_active, status_kepegawaian |
| `work_unit_assignment.created` / `updated` / `deleted` | `gtk_work_unit` | id, user_id, work_unit_id, position_id, is_primary |
| `structural_assignment.created` / `updated` / `ended` / `deleted` | `structural_assignments` | id, user_id, position_id, school_id, academic_year_id, start_date, end_date, status |
| `additional_task.created` / `updated` / `deleted` | dua tabel | source, id, user_id, unit_id, is_active |
| `decree.created` / `updated` / `deleted` | `institution_decrees` | id, decree_number, status |

### 7.5 Transaksionalitas dan kegagalan audit

- Tulis audit/ledger dalam **transaksi yang sama** dengan perubahan entity untuk event kritis (assignment, unit, sekolah, posisi).
- Bila penulisan ledger gagal, perubahan bisnis harus rollback (fail-closed) untuk menjaga integritas.
- Untuk event non-kritis (mis. akses baca), gunakan queue setelah response.
- Jangan menulis audit di dalam loop N+1; gunakan bulk insert.
- Pastikan observer tidak menyebabkan rekursi (audit model tidak meng-observe dirinya sendiri).

### 7.6 Dampak ke `PermissionRebuildObserver`

- Provider mendaftarkan `HomeroomAssignment`, `CoordinatorAssignment`, `StructuralAssignment`, `TeachingAssignment`, `Subject`, `SubjectGroup`, `GtkAdditionalTask`.
- Namun `dispatchForModel()` hanya menangani `User`, `Permission`, `GTKEmployment`, `RevokedPermission`, `TeachingAssignment`, `GtkAdditionalTask`, `Subject`, `SubjectGroup`.
- **Akibat:** perubahan `StructuralAssignment`, `HomeroomAssignment`, dan `CoordinatorAssignment` tidak memicu `BuildSnapshotJob`. `GtkWorkUnit` dan `OtherTeacherTask` juga tidak diobservasi oleh observer ini, sehingga perubahan penempatan/tugas tidak memicu rebuild snapshot.
- **Dampak ke integrasi:** Connect tidak boleh mengandalkan snapshot permission Core untuk assignment struktural; Connect harus mengevaluasi akses dari assignment mentah (sesuai desain §6.7).
- **Rekomendasi:** perbaiki observer pada fase terpisah setelah analisis dampak, dengan test regresi permission. **Jangan diubah pada tahap desain ini.**

### 7.7 Retensi dan immutability

| Data | Retensi usulan | Catatan |
|---|---|---|
| `organization_change_log` | 2 tahun aktif, arsip setelahnya | Append-only; tidak boleh update/delete |
| `audit_logs` | 1–2 tahun | Redaksi metadata PII |
| Akses API | 90–180 hari | Volume tinggi; sampling untuk list |
| Tombstone Connect | Selama data induk ada | Jangan hapus tombstone sebelum rekonsiliasi stabil |

- Batasi hak `DELETE/UPDATE` pada tabel audit di level database.
- Backup audit terpisah dari backup operasional.
- Jangan menyimpan API key, token, NIK, KK, NPWP, atau isi dokumen di audit.

### 7.8 Rencana implementasi audit (bertahap)

| Fase | Aksi | Perubahan skema? |
|---|---|---|
| 0 | Verifikasi coverage dan data existing | Tidak |
| 1 | Tambah observer audit read-only untuk entity yang belum tercakup | Tidak (kode saja) |
| 2 | Standarkan `audit_logs` (event, diff, request_id) | Tidak (kolom sudah ada) |
| 3 | Tambah `organization_change_log` + endpoint `changes` | **Ya** (perlu persetujuan) |
| 4 | Backfill tombstone awal dari snapshot | Tidak |
| 5 | Monitoring drift + alert | Tidak |

**Tidak ada perubahan mekanisme assignment** sebelum fase 1–2 selesai dan dampaknya diuji.

## 8. Matriks Data yang Boleh Diakses ALIM Connect

### 8.1 Prinsip

- **Need-to-know:** hanya field yang dibutuhkan untuk scope unit, keanggotaan, dan kewenangan.
- **Minimisasi PII:** data pribadi sensitif tidak diekspos.
- **Read-only:** tidak ada field yang dapat diubah Connect.
- **Klasifikasi:** Publik internal / Terbatas / Sensitif / Rahasia.

### 8.2 Matriks per entity

| Entity/field | Klasifikasi | Akses Connect | Catatan |
|---|---|---|---|
| **Unit** | | | |
| `id`, `code`, `name`, `type`, `parent_id` | Publik internal | Ya | Fondasi scope |
| `divisi_id`, `domain_id` | Publik internal | Ya | Boleh null |
| `is_active`, `created_at`, `updated_at` | Publik internal | Ya | |
| `metadata` | Terbatas | **Tidak** | Dapat memuat `head_user_id` dan data internal |
| **Sekolah** | | | |
| `id`, `work_unit_id`, `npsn`, `nss`, `name` | Publik internal | Ya | |
| `school_level`, `school_gender`, `school_status`, `boarding_type` | Publik internal | Ya | |
| `is_active`, timestamps | Publik internal | Ya | |
| `principal_user_id`, `principal_name` | Terbatas | Ya | Untuk penanggung jawab |
| `principal_nip`, `principal_nupy` | Terbatas | **Tidak** | Data kepegawaian |
| `address`, `phone`, `email`, `website` | Terbatas | Ya (opsional) | Hanya bila dibutuhkan |
| `bank_name`, `bank_cabang`, `bank_rekening`, `bank_an`, `npwp` | Rahasia | **Tidak** | Tidak relevan |
| `kop_path`, `ttd_ksp_path`, `stamp_path`, `logo_path` | Terbatas | **Tidak** | File internal |
| **Jabatan** | | | |
| `id`, `code`, `name`, `kategori`, `level`, `hierarchy_level`, `urutan` | Publik internal | Ya | `hierarchy_level` informatif |
| `jenis_gtk_id`, `domain_id` | Publik internal | Ya | Boleh null |
| `role_id` | Terbatas | Tidak (default) | Legacy; hanya debugging |
| `is_active`, timestamps | Publik internal | Ya | |
| **Orang/GTK** | | | |
| `id`, `name`, `username`, `email` | Terbatas | Ya | Email sesuai kebutuhan |
| `is_active` | Terbatas | Ya | Wajib untuk pencabutan akses |
| `status_kepegawaian`, `jenis_gtk` | Terbatas | Ya | |
| Unit/position aktif | Terbatas | Ya | |
| `nik`, `no_kk`, `npwp`, `nupy` | Rahasia | **Tidak** | |
| `nomor_sk`, `tanggal_sk` | Rahasia | **Tidak** | Nomor SK tidak diperlukan |
| Alamat, keluarga, kesehatan, gaji | Rahasia | **Tidak** | |
| **Penempatan unit** | | | |
| `id`, `user_id`, `work_unit_id`, `position_id`, `is_primary` | Terbatas | Ya | |
| `jabatan` (teks) | Terbatas | Ya (transisi) | Jangan dijadikan identifier |
| **Assignment struktural** | | | |
| `id`, `user_id`, `position_id`, `school_id`, `academic_year_id` | Terbatas | Ya | |
| `start_date`, `end_date`, `status`, `is_effective` | Terbatas | Ya | Wajib |
| `decree_id`, `decree_number`, `decree_type` | Terbatas | Ya | |
| `additional_info`, `notes` | Terbatas | Opsional | Bisa memuat catatan internal |
| `deleted_at` | Terbatas | Ya (sync) | Tombstone |
| **Tugas tambahan** | | | |
| `id`, `source`, `user_id`, `unit_id`, `school_id`, `academic_year_id` | Terbatas | Ya | |
| `task_type_id`, `task_name`, `task_code` | Terbatas | Ya | |
| `start_date`, `end_date`, `is_active` | Terbatas | Ya | |
| `decree_id` | Terbatas | Ya | |
| `nomor_sk` | Rahasia | **Tidak** | |
| **SK/decree** | | | |
| `id`, `decree_number`, `decree_type`, `title`, `effective_date`, `end_date`, `status` | Terbatas | Ya (inline) | |
| `document_path`, `document_filename` | Rahasia | **Tidak** | File internal |
| `signed_by`, `signed_position` | Terbatas | Opsional | Bila dibutuhkan |
| **Domain** | | | |
| `id`, `code`, `name`, `is_active` | Publik internal | Ya | |
| Permission detail | Terbatas | **Tidak** | Connect tidak butuh permission mentah |
| **Audit** | | | |
| `audit_logs`, `activity_log` | Rahasia | **Tidak** | Hanya change event, bukan audit internal |

### 8.3 Field yang dilarang

- NIK, nomor KK, NPWP, NUPY, nomor rekening, gaji, data kesehatan, data keluarga.
- Nomor SK dan dokumen SK.
- Password, token, OTP, 2FA secret, API key.
- Path file internal (kop, tanda tangan, stempel, dokumen).
- Metadata internal `work_units.metadata` dan `permission_snapshots`.

### 8.4 Data yang cukup diambil on-demand

| Data | Alasan |
|---|---|
| Detail SK (dokumen, penandatangan) | Jarang dibutuhkan; sensitif |
| Riwayat mutasi lengkap | Audit, bukan operasional |
| Daftar permission efektif | Dihitung Connect dari assignment/domain |
| Data akademik detail (rombel, mapel, jadwal) | Fase berikutnya |
| Data asrama detail (kamar, wing) | Fase berikutnya |
| Profil lengkap GTK | Tidak perlu untuk scope |

### 8.5 Hak akses berdasarkan peran Connect

API key Connect bersifat aplikasi, bukan per pengguna. Setelah data masuk ke Connect:

- **Sync worker** boleh membaca seluruh data yang diizinkan.
- **Aplikasi Connect** hanya menampilkan data sesuai scope user yang login di Connect.
- **Jangan** meneruskan API key Core ke frontend/user.
- **Jangan** memakai API key Core untuk mewakili identitas user akhir.

## 9. Strategi Versioning API

### 9.1 Dua level versi

| Level | Mekanisme | Contoh | Perubahan yang diizinkan |
|---|---|---|---|
| Kontrak API | Path `/v1` + header `X-ALIM-API-Version` | `/api/connect/v1` | Aditif dalam v1; breaking → v2 |
| Skema data | `schema_version` pada snapshot/changes | `1` | Penambahan field/enum; perubahan makna → naikkan versi |

### 9.2 Aturan kompatibilitas

**Diizinkan tanpa naik versi:**

- Menambah field baru pada response.
- Menambah nilai enum baru (klien harus mengabaikan nilai tak dikenal).
- Menambah query parameter opsional.
- Menambah endpoint baru.
- Memperbaiki bug yang tidak mengubah bentuk data.

**Memerlukan versi baru:**

- Menghapus/rename field.
- Mengubah tipe data field.
- Mengubah makna field (mis. `is_effective`).
- Mengubah format identifier.
- Mengubah autentikasi secara fundamental.
- Mengubah perilaku pagination/cursor.

### 9.3 Deprecation

- Tandai endpoint/field dengan header `Deprecation: true` dan `Sunset: <RFC 8594 date>`.
- Beri masa transisi minimal **6 bulan** untuk integrasi internal.
- Umumkan melalui changelog `docs/integration/CHANGELOG.md`.
- Pantau penggunaan endpoint lama via audit akses sebelum menghapus.
- Jangan menghapus endpoint yang masih dipakai tanpa konfirmasi.

### 9.4 Kontrak dan pengujian

- Simpan JSON Schema per endpoint di `docs/integration/schemas/v1/`.
- Contract test memvalidasi response aktual terhadap schema.
- Snapshot schema version dinaikkan bila bentuk item berubah.
- Sertakan contoh request/response di dokumen ini sebagai baseline.
- Client Connect harus mengabaikan field yang tidak dikenal (forward compatibility).

### 9.5 Matriks kompatibilitas

| Perubahan | v1 | v2 |
|---|---|---|
| Tambah `domain_id` pada position | Ya | Ya |
| Hapus `jabatan` teks | Tidak | Ya |
| Ubah `is_effective` menjadi string | Tidak | Ya |
| Tambah entity `academic_year` | Ya | Ya |
| Ubah cursor format | Tidak | Ya |


## 10. Test Plan

### 10.1 Cakupan pengujian

| Level | Fokus |
|---|---|
| Unit | Transformasi entity, evaluasi `is_effective`, cursor encode/decode, redaksi field |
| Feature/API | Auth, filter, pagination, snapshot, changes, error handling |
| Contract | JSON Schema, field presence, tipe data, identifier |
| Security | Least privilege, PII, rate limit, IP, HTTPS, header |
| Performance | Query count, p95 latency, memori snapshot |
| Regression | POS/Recruitment tidak berubah |
| Migration | Hanya untuk fase change ledger (belum sekarang) |

### 10.2 Test autentikasi dan keamanan

| ID | Skenario | Ekspektasi |
|---|---|---|
| AUTH-01 | Tanpa header API key | 401, `success=false` |
| AUTH-02 | API key salah | 401, constant-time, tidak membocorkan detail |
| AUTH-03 | API key POS dipakai di Connect | 401 |
| AUTH-04 | API key Connect dipakai di POS | 401 |
| AUTH-05 | Integrasi Connect dinonaktifkan | 503 |
| AUTH-06 | Key belum dikonfigurasi | 503 |
| AUTH-07 | Non-HTTPS di produksi | 403 |
| AUTH-08 | IP di luar allowlist | 403 |
| AUTH-09 | Rate limit auth gagal | 429 + `Retry-After` |
| AUTH-10 | Rate limit list/snapshot | 429 sesuai limiter |
| AUTH-11 | Respons memiliki `Cache-Control: no-store` | Ya |
| AUTH-12 | Log tidak memuat API key | Ya |
| AUTH-13 | Akses sukses tercatat di audit | Ya, tanpa PII |
| AUTH-14 | Rotasi key (current+previous) | Keduanya valid selama grace period |

### 10.3 Test data dan filter

| ID | Skenario | Ekspektasi |
|---|---|---|
| UNIT-01 | List unit aktif | Hanya `is_active=true` |
| UNIT-02 | `tree=true` | Anak bersarang, total benar |
| UNIT-03 | `parent_id` tidak ada | 200, data kosong |
| UNIT-04 | `include=schools` pada unit multi-sekolah | Array berisi semua sekolah |
| UNIT-05 | `updated_since` | Hanya record yang berubah |
| SCH-01 | Filter `work_unit_id` | Sekolah yang tepat |
| SCH-02 | Sekolah tanpa `principal_user_id` | Field null, tidak error |
| POS-01 | Filter `domain_id` | Hanya posisi domain tersebut |
| POS-02 | Posisi `domain_id=null` | Tetap tampil, domain null |
| PEOPLE-01 | Filter `school_id` | Union `gtk_work_unit` + `gtk_employments` |
| PEOPLE-02 | Filter `position_scope=structural` | Hanya assignment struktural |
| PEOPLE-03 | User nonaktif | Tidak muncul default; muncul dengan `is_active=all` |
| WUA-01 | User multi-unit | Semua baris, `is_primary` benar |
| WUA-02 | `position_id=null` | Tetap tampil, position null |
| SA-01 | `active_on` di antara start/end | `is_effective=true` |
| SA-02 | `end_date` lewat | `is_effective=false` |
| SA-03 | `status=inactive` | Tidak efektif |
| SA-04 | `decree_id=null` | Tidak error |
| TASK-01 | `source=all` | Dua sumber, `source` benar |
| TASK-02 | `gtk_additional_tasks` tanpa school/year | Null, tidak menebak |
| TASK-03 | `other_teacher_tasks` nonaktif | `is_active=false` |

### 10.4 Test pagination dan snapshot

| ID | Skenario | Ekspektasi |
|---|---|---|
| PAG-01 | `per_page=0` / `>200` | Diklamp ke 1/200 |
| PAG-02 | Halaman terakhir | `last_page` benar |
| PAG-03 | Data berubah saat iterasi halaman | Dapat duplikat; tidak boleh hilang (gunakan cursor untuk sync) |
| SNAP-01 | Snapshot semua entity | Counts dan checksums konsisten |
| SNAP-02 | Mutasi selama snapshot | `changes?since=snapshot_at` menangkap perubahan |
| SNAP-03 | `format=ndjson` | Baris pertama meta, sisanya valid JSON |
| SNAP-04 | `types` terbatas | Hanya entity yang diminta |
| SNAP-05 | Dataset besar | Memori terkendali (streaming) |
| SNAP-06 | `include_deleted=true` | Tombstone disertakan |

### 10.5 Test changes dan tombstone

| ID | Skenario | Ekspektasi |
|---|---|---|
| CHG-01 | Tanpa `since` | 422 + arahan snapshot |
| CHG-02 | `since` valid | Hanya event setelah since |
| CHG-03 | Cursor dilanjutkan | Tidak ada duplikat/hilang |
| CHG-04 | Cursor kedaluwarsa | 410/422, snapshot ulang |
| CHG-05 | Soft delete assignment | `action=delete`, payload null |
| CHG-06 | Hard delete `gtk_work_unit` | `action=delete` (via ledger) |
| CHG-07 | Perubahan `end_date` | `upsert` dengan `is_effective=false` |
| CHG-08 | Event duplikat | Upsert idempotent, tidak menggandakan |
| CHG-09 | `limit` maksimum | Diklamp ke 500 |
| CHG-10 | Ordering | `occurred_at, id, source` deterministik |

### 10.6 Test audit trail

| ID | Skenario | Ekspektasi |
|---|---|---|
| AUD-01 | Create unit | `audit_logs`/ledger tercatat dengan actor |
| AUD-02 | Update assignment | Diff field + before/after (redacted) |
| AUD-03 | Delete `gtk_work_unit` | Event `deleted` sebelum baris hilang |
| AUD-04 | Bulk delete seeder | Tidak boleh dipakai di produksi; event tercatat bila melalui service |
| AUD-05 | Audit gagal | Transaksi bisnis rollback (fail-closed) |
| AUD-06 | PII di audit | Tidak ada NIK/KK/NPWP/SK |
| AUD-07 | Immutability | Tidak ada update/delete pada ledger |

### 10.7 Test performa

| ID | Skenario | Target |
|---|---|---|
| PERF-01 | 10.000 people, 50.000 assignment | p95 list < 1 detik |
| PERF-02 | Snapshot 50.000 assignment (ndjson) | Memori < 256 MB |
| PERF-03 | Query count list people | Tidak ada N+1 (≤ 10 query) |
| PERF-04 | Changes 500 record | p95 < 1 detik |
| PERF-05 | Index usage | `EXPLAIN` menunjukkan index untuk filter utama |

### 10.8 Test regresi POS/Recruitment

| ID | Skenario | Ekspektasi |
|---|---|---|
| REG-01 | `/api/pos/v1/ping` | Response sama |
| REG-02 | `/api/pos/v1/kepegawaian` | Bentuk response sama |
| REG-03 | `/api/recruitment/v1/pegawai` | Bentuk response sama |
| REG-04 | Key POS/Recruitment | Tetap valid untuk endpoint masing-masing |
| REG-05 | Middleware alias existing | Tidak berubah |
| REG-06 | Route list | Tidak ada route existing yang hilang |

### 10.9 Kriteria penerimaan

1. Semua endpoint Connect hanya `GET`.
2. API key Connect tidak dapat mengakses POS/Recruitment dan sebaliknya.
3. Snapshot + changes dapat membangun read model tanpa kehilangan data.
4. Tidak ada PII terlarang pada response.
5. POS/Recruitment regression test lulus.
6. Perubahan assignment tercermin pada evaluasi scope ≤ 1 siklus sync.
7. Tidak ada perubahan skema pada fase 1–2.
8. Dokumen kontrak disetujui developer Connect sebelum implementasi.

## 11. Risiko dan Dependensi Implementasi

### 11.1 Register risiko

| ID | Risiko | Dampak | Kemungkinan | Mitigasi | Keputusan? |
|---|---|---|---|---|---|
| R-01 | Belum ada change ledger | `changes`/tombstone tidak andal | Tinggi | Fase 3 migration `organization_change_log`; sementara snapshot + rekonsiliasi | Ya |
| R-02 | Banyak entity tanpa soft delete | Hard delete tidak terdeteksi | Tinggi | Ledger observer `deleting`; rekonsiliasi ID | Ya |
| R-03 | Audit coverage tidak merata | Tidak ada jejak perubahan | Tinggi | Observer audit fase 1–2 | Ya |
| R-04 | `PermissionRebuildObserver` tidak menangani assignment | Snapshot permission bisa basi | Sedang | Connect mengevaluasi assignment sendiri; perbaiki observer terpisah | Ya |
| R-05 | `JenisGtkSeeder` menghapus posisi + cascade assignment | Kehilangan data assignment | Sedang | Larang seeder di produksi; backup; freeze posisi | Ya |
| R-06 | `StructuralPosition::$fillable` tanpa `domain_id` | Domain posisi null | Sedang | Verifikasi/backfill; API nullable | Tidak (data cleanup) |
| R-07 | `work_units.parent_id` tidak terisi | Pohon unit datar | Tinggi | Rekonsiliasi/bangun dari data aktual; jangan asumsikan | Ya (data) |
| R-08 | `divisi_id` mismatch seeder | Filter divisi kosong | Sedang | Verifikasi DB; perbaiki seeder di fase terpisah | Ya |
| R-09 | `WorkUnit::school()` hasOne vs many | Sekolah hilang dari response | Sedang | API mengembalikan array `schools` | Tidak |
| R-10 | `gtk_work_unit` tanpa validity | Tidak bisa tentukan masa berlaku | Tinggi | Perlakukan baris sebagai aktif sampai hilang; ledger | Ya |
| R-11 | `gtk_employments` tidak unik per user | Ambiguitas status utama | Sedang | Gunakan `employment()` + `is_active`; dokumentasikan | Ya |
| R-12 | Snapshot besar membebani server | Timeout/OOM | Sedang | `ndjson`, `types`, limit, off-peak | Tidak |
| R-13 | API key all-or-nothing | Blast radius besar | Sedang | Key khusus, IP allowlist, rate limit, rotasi | Tidak |
| R-14 | PII berlebihan | Kepatuhan privasi | Sedang | Matriks §8; select kolom eksplisit | Tidak |
| R-15 | Perubahan kontrak merusak Connect | Integrasi putus | Sedang | Versioning §9; contract test | Ya |
| R-16 | `InstitutionDecree` `deleted_at` tidak aktif | Status SK tidak konsisten | Rendah | Gunakan `status`, jangan `deleted_at` | Tidak |
| R-17 | Tahun ajaran assignment global | Assignment salah tahun | Sedang | Validasi `academic_years.school_id` saat sync | Ya |
| R-18 | `structural_positions.id` berubah saat reseed | Assignment menunjuk posisi hilang | Sedang | Freeze posisi; natural key `code` untuk rekonsiliasi | Ya |

### 11.2 Dependensi implementasi

| Dependensi | Dibutuhkan untuk | Status |
|---|---|---|
| Migration `organization_change_log` | `changes` + tombstone | Belum ada; perlu persetujuan |
| Observer audit untuk unit/sekolah/posisi/penempatan/assignment | Audit trail | Belum ada |
| Perbaikan `PermissionRebuildObserver` | Snapshot permission akurat | Belum; terpisah |
| Backfill `structural_positions.domain_id` | Kewenangan domain | Belum |
| Perbaikan data `parent_id`/`divisi_id` | Hierarki & filter | Belum |
| Key management `connect` | Autentikasi | Belum |
| Rate limiter `connect-api` | Perlindungan API | Belum |
| JSON Schema v1 | Contract test | Belum |
| Staging environment dengan data mirip produksi | Performance/acceptance | Perlu disiapkan |

### 11.3 Keputusan yang memerlukan persetujuan

1. **Change ledger:** menyetujui pembuatan tabel `organization_change_log` (migration baru) atau memakai `audit_logs` sebagai feed (tidak disarankan).
2. **Cakupan assignment:** apakah Connect membutuhkan `teaching_assignments`, `homeroom_assignments`, dan `dormitory_staff_assignments` pada fase 1, atau cukup unit/structural/additional tasks.
3. **Definisi `position_scope`:** apakah `position_id` pada people harus mencakup assignment struktural, penempatan, atau keduanya (default `any`).
4. **Kebijakan assignment berakhir:** cabut akses tepat saat `end_date` atau setelah siklus sync berikutnya.
5. **Kebijakan hard delete:** apakah Core akan menambah soft delete untuk sekolah/posisi/penempatan/employment, atau mengandalkan ledger.
6. **Rotasi key:** apakah perlu dual-key (current+previous) atau rotasi maintenance window.
7. **Data pribadi:** apakah `email` dan nomor telepon GTK boleh diekspos; rekomendasi: email opsional, telepon tidak.
8. **Audit retention:** 1 tahun, 2 tahun, atau lebih.
9. **Rate limit:** angka final per menit untuk list/changes/snapshot.
10. **Snapshot scale:** batas jumlah record untuk JSON vs wajib `ndjson`.
11. **Perbaikan `PermissionRebuildObserver`:** jadwal dan pemilik perubahan.
12. **Backfill data:** siapa yang bertanggung jawab membersihkan `parent_id`, `divisi_id`, `domain_id`.
13. **Kegagalan audit:** apakah penulisan audit/ledger yang gagal harus membatalkan transaksi bisnis (fail-closed) atau dicatat ke queue (fail-open).
14. **Tombstone hard delete:** apakah integrasi dihentikan sementara sampai ledger tersedia, atau memakai rekonsiliasi penuh sebagai fallback resmi.

### 11.4 Roadmap bertahap

| Fase | Deliverable | Perubahan skema | Risiko |
|---|---|---|---|
| 0 | Verifikasi data + kontrak disetujui | Tidak | Rendah |
| 1 | Auth Connect + unit/school/position/people + snapshot | Tidak | Rendah |
| 2 | Work-unit assignment + structural assignment + additional tasks + `updated_since` | Tidak | Sedang |
| 3 | `organization_change_log` + `changes` + tombstone + audit observer | **Ya** | Sedang–tinggi |
| 4 | Rekonsiliasi otomatis + monitoring drift | Tidak | Rendah |
| 5 | Hardening performa + versioning + deprecation | Tidak | Rendah |

### 11.5 Mapping API existing ke API Connect

| Kebutuhan | Existing | Connect |
|---|---|---|
| Unit | POS `satuan-kerja`, Recruitment `satuan-kerja` | `/organization/units` (array schools, domain, divisi) |
| Sekolah | POS `sekolah` | `/organization/schools` (principal_user_id, boarding_type) |
| Jabatan | POS `jabatan` | `/organization/positions` (domain, jenis) |
| GTK | POS `kepegawaian`, Recruitment `pegawai` | `/organization/people` (units, active structural count) |
| Penempatan unit | Hanya inline `daftar_satuan_kerja` | `/organization/work-unit-assignments` (id baris, position_id) |
| Assignment struktural | Tidak ada | `/organization/structural-assignments` |
| Tugas tambahan | Tidak ada | `/organization/additional-tasks` (dua sumber) |
| Snapshot | Tidak ada | `/organization/snapshot` |
| Changes | Tidak ada | `/organization/changes` |

---

## Lampiran A. Referensi Implementasi

### A.1 Route dan middleware

| Komponen | Lokasi |
|---|---|
| Route API existing | `routes/api.php:449` (POS), `routes/api.php:478` (Recruitment) |
| Middleware alias | `app/Http/Kernel.php:165–166` |
| Base auth integrasi | `app/Http/Middleware/VerifyIntegrationApiKey.php` |
| Subclass POS/Recruitment | `app/Http/Middleware/VerifyPosApiKey.php`, `VerifyRecruitmentApiKey.php` |
| Konfigurasi generik | `app/Support/IntegrationApiConfig.php` |
| Config service | `config/services.php` blok `pos`, `recruitment` |
| Rate limiter existing | `app/Providers/RouteServiceProvider.php:68–95` |

### A.2 Model dan tabel

| Entity | Model | Tabel |
|---|---|---|
| Unit | `App\Models\WorkUnit` | `work_units` |
| Divisi | `App\Models\Divisi` | `divisis` |
| Sekolah | `App\Models\School` | `schools` |
| Jabatan | `App\Models\StructuralPosition` | `structural_positions` |
| Jenis GTK | `App\Models\JenisGtk` | `jenis_gtk` |
| Domain | `App\Models\Domain` | `domains` |
| Tahun ajaran | `App\Models\AcademicYear` | `academic_years` |
| Orang | `App\Models\User` | `users` |
| Kepegawaian | `App\Models\GtkEmployment` | `gtk_employments` |
| Penempatan | `App\Models\GtkWorkUnit` | `gtk_work_unit` |
| Assignment struktural | `App\Models\StructuralAssignment` | `structural_assignments` |
| Tugas legacy | `App\Models\GtkAdditionalTask` | `gtk_additional_tasks` |
| Tugas per sekolah | `App\Models\OtherTeacherTask` | `other_teacher_tasks` |
| SK | `App\Models\InstitutionDecree` | `institution_decrees` |
| Master tugas | `App\Models\AdditionalTaskType` | `additional_task_types` |

### A.3 Migration kunci

| Migration | Isi |
|---|---|
| `2026_02_10_010813_create_work_units_table.php` | `work_units` |
| `2026_02_10_010955_create_gtk_work_unit_table.php` | `gtk_work_unit` |
| `2026_02_10_011049_create_gtk_employments_table.php` | `gtk_employments` |
| `2026_02_14_094120_create_schools_table.php` | `schools` |
| `2026_02_14_135018_create_structural_positions_table.php` | `structural_positions` |
| `2026_02_14_135020_create_structural_assignments_table.php` | `structural_assignments` |
| `2026_02_14_135017_create_institution_decrees_table.php` | `institution_decrees` |
| `2026_09_22_051202_make_decree_id_nullable_in_structural_assignments_table.php` | `decree_id` nullable |
| `2026_10_23_000011_create_gtk_cross_unit_assignments_table.php` | lintas unit |
| `2026_10_29_180100_add_position_id_to_gtk_work_unit_table.php` | `gtk_work_unit.position_id` |
| `2026_04_29_004344_create_other_teacher_tasks_table.php` | `other_teacher_tasks` |
| `2026_10_23_140000_add_request_id_to_audit_logs.php` | `audit_logs.request_id` |
| `2026_11_20_000002_add_metadata_to_audit_logs.php` | `audit_logs.metadata` |

### A.4 Audit dan otorisasi

| Komponen | Lokasi |
|---|---|
| Audit log model | `app/Models/AuditLog.php` |
| WorkUnit audit | `app/Models/WorkUnit.php:190–228` |
| LogsDeletion trait | `app/Models/Traits/LogsDeletion.php` |
| Permission rebuild observer | `app/Authorization/Support/PermissionRebuildObserver.php` |
| Observer registration | `app/Authorization/Providers/AuthorizationRuntimeServiceProvider.php:120–155` |
| GtkEmployment observer | `app/Observers/GtkEmploymentObserver.php` |
| Integration event log | `app/Models/IntegrationEventLog.php` |
| Authorization manager | `app/Authorization/Services/AuthorizationManager.php` |

---

## Lampiran B. Ringkasan Keputusan Desain

| No | Keputusan | Alasan |
|---|---|---|
| D-01 | API Connect terpisah di `/api/connect/v1` | Isolasi dari POS/Recruitment; versioning jelas |
| D-02 | Read-only, hanya `GET` | Core adalah sumber kebenaran; Connect tidak menulis |
| D-03 | Reuse `VerifyIntegrationApiKey` + `IntegrationApiConfig` | Tidak menduplikasi logika auth; aditif |
| D-04 | UUID sebagai identifier utama | Stabil dan tidak sequential |
| D-05 | `gtk_work_unit.id` dan `structural_assignments.id` sebagai ID assignment | Mengidentifikasi baris, bukan entitas relasi |
| D-06 | Additional tasks memakai `(source, id)` | Dua tabel sumber; mencegah tabrakan |
| D-07 | Snapshot + changes + rekonsiliasi | Tahan terhadap perubahan dan kegagalan |
| D-08 | Change ledger direkomendasikan | Satu-satunya cara andal untuk tombstone dan incremental |
| D-09 | Audit event terpisah dari audit akses | Feed integrasi tidak boleh membocorkan audit internal |
| D-10 | Field PII tidak diekspos | Minimisasi data dan kepatuhan |

> **Catatan penutup:** seluruh desain di atas belum diimplementasikan. Implementasi memerlukan persetujuan atas keputusan §11.3, terutama change ledger dan cakupan assignment.
