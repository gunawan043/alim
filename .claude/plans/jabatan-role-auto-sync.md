# Auto-Sync Jabatan → Role (Real-Time via Observer)

## Goal
Memastikan `user.role` (Spatie) otomatis mengikuti perubahan `gtk_employments.jabatan_id`.
Saat GTK di-create / update jabatannya, role Spatie di tabel `model_has_roles` ikut berubah.
Saat recruitment diterima menjadi GTK, role langsung ter-set sesuai jabatan barunya.

## Mapping source
Kolom `roles` (JSON array of strings) di tabel `jabatan`. Satu jabatan → banyak role diizinkan
(mis. `Wakil Kepala Lembaga Pendidikan` → `['Guru', 'Wakil Kepala Lembaga Pendidikan']`).

## Spatie role names (existing, from `RoleSeeder.php`)
`Mudir`, `Wadir 1`, `Wadir 2`, `Personalia`, `Administrator`, `Kepala Sekolah`,
`Wakil Kepala Sekolah`, `Admin Tata Usaha`, `Tata Usaha`, `Coordinator Guru`,
`Guru Umum`, `Guru Agama`, `Guru Hadits`, `Guru Tahfidz`, `Coordinator Tahfidz`,
`Departemen Tahfidz`, `Admin Departemen Tahfidz`, `Kepala Asrama`, `Admin Asrama`,
`Admin Pendidikan`, `Admin Kesehatan`, `Wali Asrama`, `Asrama`, `Keuangan`,
`Admin Sarpras`, `Sarpras`, `Wali Santri`, `GTK`.

Jabatan dari `JenisGtkSeeder` di-mapping satu-ke-satu ke nama role yang sama:
- "Mudir" → `["Mudir"]`
- "Wakil Mudir I" → `["Wadir 1"]`
- "Wakil Mudir II" → `["Wadir 2"]`
- "Staf Personalia" → `["Personalia"]`
- "Kepala Hubungan Masyarakat dan Personalia" → `["Personalia"]`
- "Kepala Lembaga Pendidikan" → `["Guru", "Kepala Lembaga Pendidikan"]` (guru + structural)
- "Guru" → `["Guru"]`
- "Koordinator KSP" → `["Coordinator Guru"]`
- dst.

Karena role spesifik (`Kepala Lembaga Pendidikan`) belum ada di `RoleSeeder`,
**plan ini juga menambah role baru** tersebut ke seeder. Alternatif: pakai role generik
(`GTK`) jika nama persis tidak ditemukan — pendekatan fallback.

### Final decision
Fallback approach: setiap jabatan selalu menambahkan role `GTK` (default GTK) + role
spesifik jika namanya cocok dengan tabel `roles`. Jika tidak ada yang cocok, hanya `GTK`.

## Files to change

### 1. Migration: tambah kolom `roles` JSON ke `jabatan`
`database/migrations/2026_XX_XX_add_roles_to_jabatan_table.php`
```php
$table->json('roles')->nullable()->after('deskripsi');
```

### 2. Update `Jabatan` model
`app/Models/Jabatan.php`
- Tambah `'roles'` ke `$fillable` & `$casts` (`array`).

### 3. Update `JenisGtkSeeder` — populate `roles` JSON
`database/seeders/JenisGtkSeeder.php`
Tambahkan mapping per jabatan (lihat tabel di bawah). Pemetaan awal berdasarkan pola
"nama jabatan = nama role Spatie":

| Jabatan                          | Roles                          |
|----------------------------------|--------------------------------|
| Mudir                            | Mudir                          |
| Wakil Mudir I                    | Wadir 1                        |
| Wakil Mudir II                   | Wadir 2                        |
| Kepala Hubungan Masyarakat dan Personalia | Personalia            |
| Staf Hubungan Masyarakat         | Personalia                     |
| Staf Personalia                  | Personalia                     |
| Kepala Kesekretariatan           | Administrator                  |
| Staf Kesekretariatan             | Tata Usaha                     |
| Kepala Keuangan                  | Keuangan                       |
| Staf Keuangan / Bendahara        | Keuangan                       |
| Kepala Lembaga Pendidikan        | GTK                            |
| Koordinator KSP                  | Coordinator Guru               |
| Guru                             | GTK                            |
| Kepala Departemen Tahfidz        | Departemen Tahfidz             |
| Ustadz/Ustadzah Tahfidz          | GTK                            |
| Kepala Departemen Bahasa         | GTK                            |
| Ustadz/Ustadzah Bahasa           | GTK                            |
| Staf Kependidikan                | GTK                            |
| Operator Akademik                | GTK                            |
| Administrasi Pendidikan          | GTK                            |
| Kepala Keamanan Pondok           | GTK                            |
| Koordinator Divisi Keamanan      | GTK                            |
| Anggota Keamanan Pondok          | GTK                            |
| Kepala Sarana dan Prasarana      | Admin Sarpras                  |
| Staf Sarana dan Prasarana        | Sarpras                        |
| Kepala Unit Gizi dan Logistik    | GTK                            |
| Staf Gizi dan Logistik           | GTK                            |
| Petugas Kebersihan Pondok        | Sarpras                        |
| Kepala Unit Kesehatan            | Admin Kesehatan                |
| Petugas Kesehatan Pondok         | Admin Kesehatan                |
| Kepala Unit Usaha Pondok         | GTK                            |
| Staf Unit Usaha Pondok           | GTK                            |

(Jabatan yang tidak punya padanan Spatie role spesifik → default `GTK` saja.)

### 4. New observer: `GtkEmploymentObserver`
`app/Observers/GtkEmploymentObserver.php`
- `created($employment)` → sync roles
- `updated($employment)` → jika `jabatan_id` berubah (wasChanged), sync roles
- `deleted($employment)` → hapus role `GTK` & role spesifik jika tidak ada employment aktif lain
- `syncRoles(GtkEmployment $employment)`:
  - load Jabatan → ambil `roles` JSON array
  - jika kosong → assign `['GTK']`
  - jika ada → assign `array_unique(array_merge(['GTK'], $jabatan->roles ?? []))`
  - pakai `$user->syncRoles($roles)` (Spatie, menggantikan semua role)
- Register di `AppServiceProvider::boot()`.

### 5. Update `GtkWizardController`
- Hapus baris `$user->assignRole('GTK')` & `$user->assignRole('gtk')` karena observer yang urus.
- Biarkan controller cukup menyimpan `jabatan_id`; observer akan picu sync.

### 6. Update `GtkRecruitmentController` (saat recruitment diterima)
- Saat status diubah ke `accepted` / `approved` dan dibuat `GtkEmployment` baru,
  observer `created` di GtkEmployment akan otomatis sync role.
- **Tambahan**: recruitment juga harus punya kolom `jabatan_id` agar tahu jabatan
  yang akan dipakai. Cek apakah ini sudah ada — lihat migration recruitment.

### 7. Test & verifikasi
- `php artisan migrate` (tambah kolom)
- `php artisan db:seed --class=JenisGtkSeeder` (update roles JSON)
- Buat GTK baru lewat wizard → cek `model_has_roles`
- Update jabatan GTK existing → cek role berubah
- Terima recruitment → cek user baru punya role sesuai jabatan

## Open questions (sudah ditanyakan user)
- Mapping source: kolom `role` di tabel `jabatan` ✓
- Sync trigger: real-time via observer + saat recruitment diterima + saat GTK wizard ✓
- Multi-role: ya, dukung multiple role per jabatan ✓
