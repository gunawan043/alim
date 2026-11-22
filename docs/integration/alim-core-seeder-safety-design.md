---
name: alim-core-seeder-safety-design
description: Desain pengamanan seeder destruktif ALIM Core
metadata:
  type: design
  design-date: 2026-11-21
  status: proposal
  scope: p0-remediation
  depends-on:
    - docs/integration/alim-core-production-safety-plan.md
---

# Desain Pengamanan Seeder — ALIM Core

> **Desain saja.** Tidak ada seeder, migration, atau data yang dijalankan/diubah.
>
> Tujuan: memastikan `db:seed` dan seeder individual tidak dapat menghapus data produksi secara tidak sengaja.

## 1. Kondisi saat ini

`DatabaseSeeder` memanggil seeder yang bersifat destruktif:

| Seeder | Perilaku destruktif | Bukti |
|---|---|---|
| `JenisGtkSeeder` | Menghapus `structural_positions`, `jenis_gtk`, `AdditionalAssignment` | `JenisGtkSeeder.php:17–21` |
| `RoleSeeder` | Menghapus role legacy/duplikat | `RoleSeeder.php:74,82` |
| `PermissionRoleSeeder` | Menghapus seluruh `role_has_permissions` | `PermissionRoleSeeder.php:16` |
| `DomainPermissionSeeder` | Menghapus seluruh `domain_role_permissions` | `DomainPermissionSeeder.php:28` |
| `SuperAdminDomainPermissionSeeder` | Menghapus lalu insert ulang permission domain Super Admin | `SuperAdminDomainPermissionSeeder.php:44` |
| `KaldikSeeder` | `forceDelete` seluruh kalender kategori KALDIK | `KaldikSeeder.php:65–70` |
| `SarprasChecklistTemplateSeeder` | Menghapus item template | `SarprasChecklistTemplateSeeder.php:195` |

Seeder standalone yang juga destruktif:

| Seeder | Perilaku | Catatan |
|---|---|---|
| `SidebarAccessSeeder` | Menghapus menu di luar definisi | Tidak dipanggil `DatabaseSeeder` |
| `SkSmaItPutriGanjil2026Seeder` | Menghapus `OtherTeacherTask`/`AdditionalTaskType` | Tidak dipanggil `DatabaseSeeder` |
| `CleanSmallItemInventoriesSeeder` | `forceDelete` inventaris | Tidak dipanggil `DatabaseSeeder` |

**Kesimpulan:** `php artisan db:seed` saat ini **tidak aman** untuk produksi.

## 2. Prinsip desain

1. **Seeder bukan alat migrasi data produksi.**
2. **Tidak ada delete** di seeder yang dijalankan di produksi.
3. **Idempoten:** `updateOrCreate` berdasarkan natural key.
4. **Dry-run default** untuk command maintenance.
5. **Konfirmasi eksplisit** untuk operasi berisiko.
6. **Transaksi** untuk perubahan multi-tabel.
7. **Backup** sebelum eksekusi.
8. **Tidak mengubah FK/cascade.**
9. **Tidak mengubah data existing** kecuali field yang dimaksud.
10. **Tidak dijalankan otomatis** oleh deployment.

## 3. Klasifikasi seeder

| Kategori | Contoh | Cara aman |
|---|---|---|
| Bootstrap awal (hanya database kosong) | Role, Permission, Domain dasar | Dijalankan hanya saat instalasi; guard “database kosong” |
| Reference update (idempoten) | Divisi, WorkUnit, JenisGtk, AdditionalTaskType | `updateOrCreate`; tidak delete |
| Data operasional | Kaldik, Sidebar | Upsert; tidak forceDelete |
| Berisiko tinggi | PermissionRole, DomainPermission | Command terpisah; dry-run; backup |
| Dilarang di produksi | Seeder yang delete/truncate | Tidak boleh dieksekusi |

## 4. Desain `DatabaseSeeder`

### 4.1 Rekomendasi

`DatabaseSeeder` hanya boleh memanggil seeder yang **aman dan idempoten**. Seeder destruktif dipindahkan ke command terpisah.

```text
DatabaseSeeder
├── ReferenceSeeder (safe)
│   ├── DivisiSeeder
│   ├── WorkUnitSeeder
│   ├── JenisGtkSeeder (diubah menjadi upsert)
│   └── AdditionalTaskTypeSeeder (upsert)
├── OptionalSeeder (tidak default)
│   ├── KaldikSeeder (upsert)
│   └── SidebarAccessSeeder (upsert)
└── MaintenanceCommand (terpisah, manual)
    ├── roles:sync
    ├── permissions:sync
    └── domains:sync
```

**Jangan** memanggil seeder destruktif dari `DatabaseSeeder`.

### 4.2 Guard environment

Pseudocode:

```text
if app()->environment('production') && ! $this->option('force'):
    abort("Seeder destruktif dilarang di produksi. Gunakan command maintenance dengan --dry-run.")
```

### 4.3 Guard database kosong

Untuk seeder bootstrap:

```text
if roles()->exists() or permissions()->exists():
    skip/bootstrap-only warning
```

## 5. Desain command maintenance

### 5.1 `roles:sync`

- Default: `--dry-run`.
- Tampilkan role yang akan dibuat/dinonaktifkan.
- Jangan menghapus role yang masih dipakai user.
- Jangan memindahkan `model_has_roles` tanpa konfirmasi.
- Perubahan permission harus memicu rebuild snapshot.

### 5.2 `permissions:sync`

- Hitung diff `role_has_permissions`.
- Tampilkan penambahan/penghapusan.
- Jangan `delete()` seluruh tabel.
- Terapkan dalam transaksi.
- Backup pivot sebelum eksekusi.
- Setelah sukses: `forgetCachedPermissions` + rebuild snapshot.

### 5.3 `domains:sync`

- Upsert `domain_role_permissions` per pasangan.
- Jangan menghapus seluruh matriks.
- Hapus hanya pasangan yang secara eksplisit tidak lagi valid, dengan konfirmasi.

### 5.4 `jenisgtk:sync`

- Upsert posisi berdasarkan `code`.
- Jangan hapus posisi.
- Jika posisi tidak lagi ada di definisi: set `is_active=false`, bukan delete.
- Jangan hapus `jenis_gtk`; nonaktifkan.
- Jangan hapus `additional_task_types`; nonaktifkan.

### 5.5 `kaldik:sync`

- Upsert berdasarkan kombinasi unik (tanggal + nama/kategori).
- Jangan `forceDelete`.
- Data buatan pengguna tidak boleh dihapus.

### 5.6 `sidebar:sync`

- Upsert berdasarkan `menu_key`.
- Jangan hapus menu yang tidak ada di definisi; nonaktifkan atau laporkan.

## 6. Transaksi dan audit

- Bungkus perubahan multi-tabel dalam `DB::transaction`.
- Tulis audit sebelum/sesudah perubahan.
- Jika audit gagal untuk perubahan kritis → rollback (fail-closed).
- Jangan menulis PII ke log.

## 7. Backup sebelum eksekusi

- Backup tabel yang akan berubah.
- Hitung jumlah baris sebelum/sesudah.
- Simpan daftar perubahan (diff) sebagai artefak.
- Uji di staging.

## 8. Deployment

- **Jangan** menjalankan `db:seed` di pipeline produksi.
- **Jangan** menjalankan `migrate:fresh`, `migrate:refresh`, `db:wipe`.
- Seeder hanya dijalankan manual, dengan approval, dry-run, dan backup.
- Command maintenance harus memiliki audit dan rollback plan.

## 9. Test plan

| ID | Skenario | Ekspektasi |
|---|---|---|
| SD-01 | `DatabaseSeeder` di produksi | Ditolak/guard |
| SD-02 | Dry-run `jenisgtk:sync` | Tidak ada perubahan |
| SD-03 | `jenisgtk:sync` dua kali | Idempoten |
| SD-04 | Posisi tidak ada di definisi | Nonaktif, tidak delete |
| SD-05 | `permissions:sync` | Diff, bukan wipe |
| SD-06 | Gagal di tengah transaksi | Rollback |
| SD-07 | Role masih dipakai user | Tidak dihapus |
| SD-08 | Kaldik data pengguna | Tidak terhapus |
| SD-09 | Audit tercatat | Ya |
| SD-10 | Regresi POS/Recruitment | Tidak berubah |

## 10. Larangan

- Jangan menjalankan seeder destruktif di produksi.
- Jangan mengubah seeder sebelum desain ini disetujui.
- Jangan mengubah data existing untuk “menyesuaikan” seeder.
- Jangan menghapus role/permission/posisi.
- Jangan mengandalkan `--force` sebagai solusi.
