# Menghubungkan ALIM ke Supabase (PostgreSQL)

Integrasi ini memakai **Supabase Database (PostgreSQL)**. Autentikasi tetap
memakai login/RBAC Laravel yang sudah ada; Supabase Auth dan Storage tidak
diaktifkan pada tahap ini.

## 1. Siapkan project Supabase

1. Buat project di https://supabase.com.
2. Buka **Project Settings → Database → Connection string**.
3. Pilih **Session pooler** (port `5432`) untuk aplikasi Laravel dan migration.
4. Salin connection string, contoh bentuknya:

   ```text
   postgresql://postgres.<project-ref>:<password>@aws-0-<region>.pooler.supabase.com:5432/postgres
   ```

   Jangan memakai Transaction Pooler port `6543` untuk `php artisan migrate`,
   karena mode itu tidak mendukung prepared statement/session seperti yang
   dibutuhkan migration.

## 2. Isi environment lokal

Tambahkan ke `.env` (jangan commit file ini):

```dotenv
SUPABASE_DB_URL=postgresql://postgres.<project-ref>:<password>@aws-0-<region>.pooler.supabase.com:5432/postgres
SUPABASE_DB_SSLMODE=require

# Biarkan tetap mysql sampai siap pindah:
DB_CONNECTION=mysql
```

Jika memakai field terpisah dan password mengandung karakter seperti `#`,
spasi, atau karakter khusus lain, apit nilai password dengan tanda kutip:

```dotenv
SUPABASE_DB_PASSWORD="password#anda"
```

Alternatif tanpa URL:

```dotenv
SUPABASE_DB_HOST=aws-0-<region>.pooler.supabase.com
SUPABASE_DB_PORT=5432
SUPABASE_DB_DATABASE=postgres
SUPABASE_DB_USERNAME=postgres.<project-ref>
SUPABASE_DB_PASSWORD=<password>
SUPABASE_DB_SSLMODE=require
```

## 3. Cek koneksi

```bash
php artisan supabase:check
```

Perintah ini menampilkan status koneksi, nama database/schema, jumlah tabel,
dan jumlah migration. Password tidak ditampilkan.

## 4. Buat schema di Supabase

Setelah koneksi OK, jalankan migration ke koneksi `supabase`:

```bash
DB_CONNECTION=supabase php artisan migrate --force
```

Catatan penting:

- Supabase Session Pooler dapat memutus koneksi pada migration panjang.
  Untuk aplikasi ini (ratusan migration), migration satu per satu lewat
  pooler tidak praktis.
- Disarankan membuat schema lewat **schema dump/import** menggunakan
  PostgreSQL lokal atau SQL Editor Supabase, lalu menandai migration.
- Aplikasi masih memiliki query khusus MySQL (`DATE_FORMAT`, `YEAR`,
  `MONTH`, `DATE_ADD`, `JSON_EXTRACT`, dan `ALTER TABLE ... MODIFY`).
  Query tersebut perlu diubah sebelum `DB_CONNECTION=supabase` digunakan.

Jika migration gagal, jangan mengubah database production. Kirim log error
untuk audit kompatibilitas MySQL → PostgreSQL.

## 5. Pindah koneksi aplikasi

Setelah schema berhasil dan data siap:

```dotenv
DB_CONNECTION=supabase
```

Lalu:

```bash
php artisan optimize:clear
php artisan supabase:check
```

## Hardening yang sudah diterapkan

- Runtime aplikasi memakai role PostgreSQL `alim_runtime`, bukan `postgres`.
- Role runtime hanya mendapat `SELECT/INSERT/UPDATE/DELETE`, bukan DDL.
- Role admin untuk migration dipisahkan melalui koneksi `supabase_admin`.
- Akses `anon` dan `authenticated` ke schema `public` dicabut.
- RLS diaktifkan pada seluruh tabel `public`; hanya role `alim_runtime`
  yang diberi policy untuk operasi aplikasi. Role API publik tidak memiliki
  policy sehingga aksesnya ditolak.
- Kredensial admin dan runtime dipisahkan di `.env` server.
- Password admin `postgres` yang lama terekspos di percakapan tetap harus
  direset dari dashboard Supabase karena role tersebut bukan superuser biasa
  dan password-nya tidak dapat diubah lewat koneksi aplikasi.

**Masih perlu dilakukan manual dari dashboard Supabase:**

- Nonaktifkan Data API jika tidak diperlukan.
- Aktifkan Network Restrictions/IP allowlist bila tersedia.
- Pastikan backup otomatis aktif dan uji restore ke staging.
- Rotasi password admin `postgres`.

## Status implementasi lokal

- Schema Supabase sudah dibuat ulang dan diimpor dari migration aplikasi.
- Seluruh 646 migration tercatat sebagai sudah dijalankan.
- 565 tabel aplikasi tersedia; jumlah ini sudah dicocokkan dengan MySQL lokal.
- Initial partial import sudah dijalankan: 80 tabel MySQL yang berisi data disalin
  ke Supabase, dan jumlah baris source/target sama.
- Data Santri belum ada di MySQL saat import ini; tabel Santri masih kosong.
- Command import ulang/full resync:
  `php artisan supabase:copy-data --truncate`
- `DB_CONNECTION` lokal masih `mysql`.
- Beberapa foreign key legacy tidak dibuat karena tipe kolom lama tidak kompatibel
  (misalnya `paket_soal.id` masih `char(36)` sementara tabel turunannya memakai UUID).
  Relasi Eloquent tetap dapat dipakai, tetapi constraint tersebut perlu dirapikan
  sebelum migrasi data final.

## Catatan

- Aplikasi saat ini memakai MySQL lokal dan SQLite di hosting. Mengganti ke
  Postgres bukan sekadar mengubah host: sebagian query/migration perlu diuji.
- Untuk data lama, pilih salah satu:
  - mulai baru dengan seeder struktur, atau
  - migrasi data MySQL → Postgres dengan ETL terkontrol (perlu persetujuan
    sebelum menyentuh data production).
- Hosting Hostinger perlu ekstensi PHP `pdo_pgsql`. Cek dari SSH:
  ```bash
  php -m | grep pdo_pgsql
  ```
  Jika belum ada, aktifkan lewat hPanel atau gunakan koneksi database lain.
