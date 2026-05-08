# ALIM — Academic Learning & Information Management

Sistem manajemen pendidikan terpadu untuk Pondok pesantren, mendukung modul asrama, akademik, GTK, santri, absensi, dan kegiatan harian.

## Tech Stack

| Teknologi | Keterangan |
|-----------|------------|
| Laravel 10 | Framework PHP |
| MySQL | Database |
| Blade | Template engine |
| Vite | Frontend build tool |
| Bootstrap 5 | UI framework |

## Persyaratan

- **PHP** >= 8.1
- **Composer** >= 2.x
- **Node.js** >= 18.x
- **MySQL** >= 8.0

## Instalasi

### 1. Clone repository

```bash
git clone https://github.com/gunawan043/alim.git
cd alim
```

### 2. Install dependensi PHP

```bash
composer install
```

### 3. Install dependensi Node.js

```bash
npm install
```

### 4. Setup environment

```bash
cp .env.backup .env
# atau buat manual dari .env.example
```

### 5. Generate app key

```bash
php artisan key:generate
```

### 6. Setup database

```bash
# Buat database MySQL terlebih dahulu
mysql -u root -p -e "CREATE DATABASE alim_db;"

# Jalankan migration
php artisan migrate

# (Opsional) Seed data awal
php artisan db:seed
```

### 7. Build frontend

```bash
npm run build
```

### 8. Jalankan server

```bash
php artisan serve
```

Buka di browser: **http://127.0.0.1:8000**

---

## Struktur Direktori

```
alim/
├── app/
│   ├── Console/Commands/     # Artisan commands
│   ├── Http/Controllers/     # Controller utama
│   ├── Models/              # Eloquent models
│   └── Exports/              # Export Excel/PDF
├── database/
│   ├── migrations/          # Schema database
│   └── seeders/             # Data awal
├── resources/
│   ├── js/                  # Frontend assets
│   └── views/               # Blade templates
├── routes/
│   └── web.php              # Route definitions
└── public/build/            # Compiled assets
```

## Modul Utama

- **Asrama** — Manajemen asrama, kamar, penghuni, absensi, pelanggaran
- **Akademik** — Kelas, mata pelajaran, nilai, rapor
- **GTK** — Data guru & tenaga kependidikan
- **Santri** — Data siswa, kelas, kehadiran
- **Absensi** — Absensi harian & bulanan
- **Alumni** — Data lulusan
- **Laporan** — Export Excel & PDF

## Catatan Penting

- File `.env` berisi credential & konfigurasi, **jangan di-push** ke Git
- Vendor & node_modules **tidak di-commit** — install ulang saat clone
- Build frontend perlu dijalankan setelah `npm install`

## Lisensi

MIT License