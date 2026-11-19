# Tutorial Lengkap — Push Production, Sinkron Database, dan Sinkron Supabase

Dokumen ini adalah panduan operasional untuk proyek **ALIM**. Isinya disusun
sesuai konfigurasi yang benar-benar ada di repo ini:

- `deploy.sh` — script deploy di server (dipanggil webhook GitHub).
- `app/Http/Controllers/DeployController.php` — endpoint `POST /webhook/deploy` dan `GET /health`.
- `scripts/auto-deploy-hostinger.sh` + `.git/hooks/pre-push` — auto deploy rsync ke Hostinger setiap `git push`.
- `app/Console/Commands/SupabaseCheck.php` (`supabase:check`).
- `app/Console/Commands/SupabaseCopyData.php` (`supabase:copy-data`).

> **Penting:** ada DUA hal berbeda yang sering tertukar:
> 1. **Push production** = mengirim kode ke server (Hostinger) dan menjalankan
>    `deploy.sh` (composer, `migrate --force`, build asset, cache).
> 2. **Sinkron Supabase** = menyalin **data** dari MySQL ke PostgreSQL Supabase.
>    Ini **tidak** otomatis terjadi saat deploy. Harus dijalankan eksplisit
>    dengan `php artisan supabase:copy-data`.

---

## 0. Ringkasan cepat (TL;DR)

```bash
# ── A. Deploy kode ke production ──────────────────────────────
git status                      # pastikan perubahan yang ikut terkirim sudah benar
git add -A && git commit -m "..."   # commit (opsional: hook rsync juga kirim file uncommitted)
git push origin main            # hook pre-push otomatis rsync + deploy ke Hostinger

# ── B. Cek hasil deploy ──────────────────────────────────────
curl https://<domain>/health
ssh alim-deploy 'tail -40 ~/alim-app/storage/logs/deploy-$(date +%F).log'

# ── C. Sinkron MySQL production → Supabase ───────────────────
# Jalankan DI SERVER (atau di mesin yang .env-nya menunjuk MySQL production)
php artisan supabase:check
php artisan supabase:copy-data --truncate --chunk=500

# ── D. Verifikasi ────────────────────────────────────────────
php artisan supabase:check
```

---

## 1. Arsitektur deploy yang aktif

```
Laptop developer
   │
   │  git push origin main
   ▼
┌─────────────────────────────────────────────────────────────┐
│ Dua jalur deploy tersedia (pilih salah satu, jangan campur) │
└─────────────────────────────────────────────────────────────┘

JALUR 1 — GitHub Webhook (deploy.sh)
   git push ──► GitHub ──► POST https://<domain>/webhook/deploy
                              (HMAC signature + filter branch)
                              └─► nohup bash /var/www/alim/deploy.sh
                                     1. git fetch + reset --hard
                                     2. composer install --no-dev
                                     3. php artisan migrate --force
                                     4. npm ci && npm run build
                                     5. storage:link
                                     6. config/route/view cache
                                     7. queue:restart + reload php-fpm
                                     8. health check

JALUR 2 — Pre-push hook rsync (yang terpasang di repo ini)
   git push ──► .git/hooks/pre-push
                 └─► scripts/auto-deploy-hostinger.sh (delay 8 detik)
                       ├─ rsync -az --delete  → alim-deploy:~/alim-app/
                       └─ ssh alim-deploy "bash alim-deploy-live.sh"
```

Konfigurasi SSH Hostinger yang sudah ada di mesin ini (`~/.ssh/config`):

```sshconfig
Host alim-deploy
  HostName 46.202.138.95
  Port 65002
  User u625880896
  IdentityFile ~/.ssh/alim_deploy
  IdentitiesOnly yes
```

Artinya: `ssh alim-deploy` = masuk ke server production Hostinger, dan aplikasi
ada di `~/alim-app`.

---

## 2. Prasyarat (sekali saja)

### 2.1 Di laptop

```bash
php -v                 # >= 8.3
composer -V            # >= 2.x
node -v                # >= 18.x
git --version
rsync --version
ssh -T alim-deploy     # harus bisa login tanpa password
```

### 2.2 Di server production

```bash
ssh alim-deploy

php -v
composer -V
php -m | grep -E 'pdo_mysql|pdo_pgsql|pgsql'
cd ~/alim-app && ls -la .env deploy.sh
```

- `pdo_mysql` wajib ada (database utama production saat ini MySQL).
- `pdo_pgsql` + `pgsql` wajib ada **hanya jika** ingin sinkron/memakai Supabase.
  Jika belum ada, aktifkan lewat hPanel Hostinger (PHP Configuration → Extensions)
  atau minta support.
- `.env` di server **tidak ikut ter-rsync** (sengaja di-exclude). Isi manual.

### 2.3 Variabel `.env` production yang wajib

Salin dari `.env.production.example`, lalu sesuaikan:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<domain-anda>
APP_KEY=            # php artisan key:generate --force

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<db_production>
DB_USERNAME=<user_db>
DB_PASSWORD=<password_db>

# ── Supabase (untuk sinkron) ─────────────────────────────
SUPABASE_DB_URL=postgresql://alim_runtime.<ref>:<password>@aws-0-ap-northeast-1.pooler.supabase.com:5432/postgres
SUPABASE_DB_SSLMODE=require

# Wajib untuk copy-data (butuh role owner/DDL, BUKAN runtime)
SUPABASE_ADMIN_DB_USERNAME=postgres.<ref>
SUPABASE_ADMIN_DB_PASSWORD=<password_admin>

# ── Webhook deploy (Jalur 1) ────────────────────────────
DEPLOY_SECRET=<openssl rand -hex 32>
DEPLOY_BRANCH=main
DEPLOY_SCRIPT=/home/u625880896/alim-app/deploy.sh
```

> ⚠️ `SUPABASE_ADMIN_*` **jangan** disamakan dengan `SUPABASE_DB_USERNAME`
> (`alim_runtime`). Runtime role tidak punya hak DDL/TRUNCATE, sehingga
> `supabase:copy-data --truncate` akan gagal jika memakai runtime role.

---

## 3. Push production

Ada dua jalur. **Repo ini sudah memasang Jalur 2** (pre-push hook). Pilih satu
agar tidak deploy dua kali.

### 3.1 Cek dulu sebelum push

```bash
git status
git diff --stat
```

Perhatikan: hook rsync **ikut mengirim file yang belum di-commit** (by design,
agar bisa deploy cepat). Kalau tidak ingin itu terjadi, commit dulu semuanya
atau matikan hook sementara:

```bash
mv .git/hooks/pre-push .git/hooks/pre-push.off   # matikan sementara
# ... push ...
mv .git/hooks/pre-push.off .git/hooks/pre-push   # aktifkan lagi
```

Pastikan juga file sensitif tidak ikut: `.env`, `cookies.txt`, `*.log`,
`storage/`, `vendor/`, `node_modules/` sudah di-exclude oleh script.

### 3.2 Jalur 2 — Auto deploy Hostinger (yang aktif sekarang)

Cukup:

```bash
git push origin main
```

Yang terjadi:

1. Hook `pre-push` langsung `exit 0` (push tidak diblokir).
2. `scripts/auto-deploy-hostinger.sh` jalan di background setelah delay 8 detik.
3. `rsync -az --delete` mengirim working tree ke `alim-deploy:~/alim-app/`.
4. SSH menjalankan `bash ~/alim-app/alim-deploy-live.sh` di server.
5. Log lokal: `/tmp/alim-auto-deploy.log`.

Pantau log:

```bash
tail -f /tmp/alim-auto-deploy.log
```

Jika deploy gagal, `git push` tetap sukses. **Selalu cek log**, jangan hanya
melihat status push.

> Script remote `alim-deploy-live.sh` berada di server (tidak di repo). Isinya
> harus memuat: `composer install --no-dev`, `php artisan migrate --force`,
> `npm ci && npm run build` (atau pakai `public/build` yang sudah di-commit),
> `storage:link`, `config:cache`, `route:cache`, `view:cache`, `queue:restart`.

### 3.3 Jalur 1 — GitHub Webhook (`deploy.sh`)

Gunakan ini jika ingin deploy murni dari commit GitHub (tanpa rsync).

**Setup sekali:**

1. Pastikan `deploy.sh` ada di server dan executable:
   ```bash
   ssh alim-deploy 'chmod +x ~/alim-app/deploy.sh && bash -n ~/alim-app/deploy.sh'
   ```
2. Isi `.env` server: `DEPLOY_SECRET`, `DEPLOY_BRANCH=main`,
   `DEPLOY_SCRIPT=/home/u625880896/alim-app/deploy.sh`.
3. Di GitHub: **Settings → Webhooks → Add webhook**
   ```
   Payload URL  : https://<domain>/webhook/deploy
   Content type : application/json
   Secret       : <DEPLOY_SECRET>
   SSL verify   : enabled
   Events       : Just the push event
   Active       : ✓
   ```
4. Beri sudo tanpa password untuk reload PHP-FPM (opsional, agar `[7/7]` tidak skip):
   ```bash
   sudo visudo
   # www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload php*-fpm
   # www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart php*-fpm
   ```

**Deploy rutin:**

```bash
git add -A && git commit -m "feat: ..."
git push origin main
```

Pantau:

```bash
curl -i https://<domain>/health
ssh alim-deploy 'tail -f ~/alim-app/storage/logs/deploy-$(date +%F).log'
```

Contoh output sukses:

```
[2026-11-19 10:00:01] [1/7] Git pull...
[2026-11-19 10:00:05] [2/7] Composer install...
[2026-11-19 10:00:20] [3/7] Artisan migrate...
[2026-11-19 10:00:35] [4/7] NPM build...
[2026-11-19 10:00:50] [5/7] Storage:link...
[2026-11-19 10:00:52] [6/7] Clear & rebuild cache...
[2026-11-19 10:00:58] [7/7] Restart workers...
[2026-11-19 10:01:00] ✓ Health check OK
[2026-11-19 10:01:00] DEPLOY SELESAI dalam 59 detik
```

### 3.4 Uji webhook tanpa signature

```bash
curl -X POST https://<domain>/webhook/deploy \
  -H "Content-Type: application/json" \
  -H "X-GitHub-Event: ping" \
  -d '{"zen":"test"}'
# Harapan: 401 (webhook hidup, signature wajib)
```

---

## 4. Sinkronisasi database production (MySQL)

`deploy.sh` sudah otomatis menjalankan `php artisan migrate --force`, jadi
**schema** production ikut tersinkron setiap deploy. Yang perlu dicek manual
adalah backup dan status migration.

### 4.1 Backup sebelum apa pun

Di server production:

```bash
ssh alim-deploy
cd ~/alim-app

# Ambil nama DB dari .env
DB_NAME=$(php -r '$e=parse_ini_file(".env"); echo $e["DB_DATABASE"];')

mysqldump --single-transaction --quick --routines --triggers \
  -h 127.0.0.1 -u "$(php -r '$e=parse_ini_file(".env"); echo $e["DB_USERNAME"];')" \
  -p "$DB_NAME" > ~/backup-${DB_NAME}-$(date +%F-%H%M).sql

ls -lh ~/backup-*.sql | tail -3
```

> Simpan minimal 1 backup sebelum setiap migrasi besar. Untuk restore:
> `mysql -h 127.0.0.1 -u <user> -p <db> < ~/backup-....sql`

### 4.2 Cek status migration setelah deploy

```bash
ssh alim-deploy 'cd ~/alim-app && php artisan migrate:status | tail -20'
```

Kalau ada migration `Pending` yang seharusnya sudah jalan:

```bash
ssh alim-deploy 'cd ~/alim-app && php artisan migrate --force'
```

Kalau migration gagal di tengah:

```bash
ssh alim-deploy 'cd ~/alim-app && tail -80 storage/logs/laravel.log'
```

### 4.3 Menjalankan migration baru secara manual (tanpa deploy kode)

```bash
ssh alim-deploy 'cd ~/alim-app && php artisan down'
ssh alim-deploy 'cd ~/alim-app && php artisan migrate --force'
ssh alim-deploy 'cd ~/alim-app && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache'
ssh alim-deploy 'cd ~/alim-app && php artisan up'
```

---

## 5. Sinkronisasi Supabase

Supabase di proyek ini = **PostgreSQL** yang dipakai sebagai database kedua
(staging/initial import). Skema Supabase sudah pernah dibuat dan seluruh
migration sudah tercatat. Rutin sinkron yang dipakai adalah **menyalin data
MySQL → Supabase** dengan `supabase:copy-data`.

### 5.1 Di mana menjalankannya?

`supabase:copy-data` **mewajibkan koneksi default = MySQL** (sumber), dan
menulis ke koneksi `supabase_admin` (tujuan). Jadi:

- **Disarankan:** jalankan **di server production**, karena `.env` server
  menunjuk MySQL production. Hasilnya = data production masuk ke Supabase.
- Alternatif: di laptop, tetapi `.env` lokal harus menunjuk MySQL yang ingin
  disalin (hati-hati: `.env` lokal saat ini `DB_DATABASE=common_admin`).

Cek `.env` di mesin yang dipakai:

```bash
php -r '$e=parse_ini_file(".env"); foreach(["APP_ENV","DB_CONNECTION","DB_HOST","DB_DATABASE","SUPABASE_DB_HOST","SUPABASE_DB_USERNAME"] as $k) echo "$k=".($e[$k]??"-")."\n";'
```

### 5.2 Cek koneksi Supabase

```bash
php artisan supabase:check
```

Contoh output sehat:

```
+---------------------+----------------------------+
| Item                | Nilai                      |
+---------------------+----------------------------+
| Koneksi             | OK                         |
| Database            | postgres                   |
| Schema              | public                     |
| Jumlah tabel        | 565                        |
| Migration tercatat  | 646                        |
| Versi PostgreSQL    | PostgreSQL 15.x ...        |
+---------------------+----------------------------+
```

Jika gagal:

- `pdo_pgsql belum aktif` → aktifkan ekstensi PHP.
- `SUPABASE_DB_URL belum diisi` → isi `.env`.
- timeout / SSL → pastikan memakai **Session Pooler port 5432**, bukan 6543.

### 5.3 Uji rencana dulu (dry-run)

Selalu lihat rencana sebelum menulis:

```bash
php artisan supabase:copy-data --dry-run
```

Output: daftar tabel MySQL yang berisi data + jumlah baris.

Untuk sebagian tabel saja:

```bash
php artisan supabase:copy-data \
  --tables=users,gtk_profiles,gtk_employments,gtk_work_unit,roles,permissions \
  --dry-run
```

### 5.4 Full resync (menimpa isi Supabase)

> ⚠️ **DESTRUKTIF.** `--truncate` mengosongkan **semua** tabel `public` di
> Supabase (kecuali `migrations`) lalu menyalin ulang dari MySQL. Jangan
> dijalankan kalau Supabase sudah dipakai sebagai database utama.

Rekomendasi: masuk maintenance mode supaya data tidak berubah di tengah salin.

```bash
# 1. (Opsional) hentikan trafik tulis
php artisan down --render="errors::503" --retry=60

# 2. Backup Supabase dulu dari Dashboard: Database → Backups → Create backup

# 3. Salin ulang
php artisan supabase:copy-data --truncate --chunk=500

# 4. Buka kembali
php artisan up
```

Kalau hanya ingin menyalin tanpa mengosongkan (append/upsert sederhana — bisa
menghasilkan duplikat pada primary key), jalankan tanpa `--truncate`:

```bash
php artisan supabase:copy-data --chunk=500
```

Perilaku command (sesuai `SupabaseCopyData.php`):

- Melewati tabel `migrations`.
- Melewati tabel yang tidak ada di Supabase atau tidak punya kolom yang cocok.
- Melewati tabel MySQL yang kosong.
- Menonaktifkan trigger FK selama import (`session_replication_role = replica`).
- Mereset sequence PostgreSQL di akhir.
- Menampilkan tabel berhasil/gagal beserta jumlah baris sumber vs tujuan.

### 5.5 Verifikasi hasil sinkron

A. Lihat ringkasan dari output command — kolom `Sumber` harus sama dengan
`Tujuan` untuk setiap tabel.

B. Bandingkan total baris seluruh tabel:

```bash
php artisan tinker --execute='
$src = DB::connection("mysql");
$dst = DB::connection("supabase_admin");
$tables = collect($src->select("select table_name from information_schema.tables where table_schema = database() and table_type = ?", ["BASE TABLE"]))->pluck("table_name");
$bad = []; $ok = 0;
foreach ($tables as $t) {
    if ($t === "migrations") continue;
    try {
        $a = $src->table($t)->count();
        $b = $dst->table($t)->count();
        if ($a !== $b) { $bad[] = "$t: $a vs $b"; } else { $ok++; }
    } catch (Throwable $e) {}
}
echo "Cocok: $ok tabel\n";
echo $bad ? "BEDA:\n".implode("\n", $bad) : "Semua tabel cocok\n";
'
```

C. Uji baca dari aplikasi (tanpa mengubah DB utama):

```bash
php artisan tinker --execute='
echo DB::connection("supabase")->table("users")->count()." users di Supabase\n";
'
```

### 5.6 Cek data Supabase lewat Dashboard

1. Buka project Supabase → **Table Editor**.
2. Pilih tabel (mis. `users`, `gtk_profiles`).
3. Cocokkan jumlah baris dengan MySQL:
   ```bash
   php artisan tinker --execute='echo DB::connection()->table("users")->count();'
   ```
4. Untuk query bebas: **SQL Editor**, contoh:
   ```sql
   select count(*) from users;
   select count(*) from gtk_profiles;
   ```

### 5.7 Sinkron berkala (opsional)

Buat cron di server (contoh tiap hari 02:00 WIB):

```bash
crontab -e
```

```cron
0 2 * * * cd /home/u625880896/alim-app && php artisan supabase:copy-data --truncate --chunk=500 >> storage/logs/supabase-sync.log 2>&1
```

> Untuk production yang sibuk, jangan truncate saat jam kerja. Pertimbangkan
> jendela maintenance atau skema sinkron inkremental berbasis `updated_at`.

### 5.8 Cutover: menjadikan Supabase database utama

Status repo saat ini: `.env` masih `DB_CONNECTION=mysql`, dan dokumentasi
menyatakan cutover **belum** dilakukan karena masih ada query khusus MySQL.
Lakukan hanya setelah semua query kompatibel PostgreSQL dan hasil uji lolos.

```bash
# 1. Backup terakhir
mysqldump --single-transaction ... > final-backup.sql

# 2. Set di .env production
# DB_CONNECTION=supabase
# SUPABASE_DB_URL=... (Session Pooler 5432)

# 3. Bersihkan cache config
php artisan optimize:clear
php artisan supabase:check

# 4. Maintenance + smoke test
php artisan down
php artisan migrate --force        # seharusnya tidak ada migration baru
php artisan up
```

Rollback cepat: kembalikan `DB_CONNECTION=mysql`, lalu
`php artisan optimize:clear && php artisan config:cache`.

---

## 6. Rollback

### 6.1 Rollback kode (Jalur 1 / webhook)

```bash
ssh alim-deploy
cd ~/alim-app
git log --oneline -5
git reset --hard <commit-yang-stabil>
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
```

Atau lebih aman: `git revert <commit>` lalu push ulang.

### 6.2 Rollback kode (Jalur 2 / rsync)

Karena rsync mengirim working tree, checkout commit lama di lokal lalu push:

```bash
git checkout <commit-stabil> -- .
git commit -m "revert: kembali ke <commit>"
git push origin main
```

### 6.3 Rollback database MySQL

```bash
ssh alim-deploy
cd ~/alim-app
mysql -h 127.0.0.1 -u <user> -p <db> < ~/backup-<db>-<tanggal>.sql
```

### 6.4 Rollback Supabase

- **Terbaik:** Dashboard Supabase → Database → Backups → Restore.
- Alternatif: jalankan ulang `php artisan supabase:copy-data --truncate`
  dari MySQL yang masih bersih.

---

## 7. Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| `git push` sukses tapi situs tidak berubah | Hook deploy gagal di background | `tail -f /tmp/alim-auto-deploy.log`, cek `ssh alim-deploy 'tail -40 ~/alim-app/storage/logs/deploy-$(date +%F).log'` |
| `401 Missing signature` di webhook | Request bukan dari GitHub / secret beda | Samakan `DEPLOY_SECRET` di `.env` dan GitHub webhook |
| Webhook `Ignored: push to X, watching main` | Push ke branch lain | Push ke `main` atau ubah `DEPLOY_BRANCH` |
| `composer install` gagal | Versi PHP server beda / extension kurang | Cek `php -v` & `php -m` di server |
| `npm: command not found` | Node tidak ada di server | Pastikan `public/build` sudah di-commit (repo ini meng-commit 6217 file build) |
| `php artisan migrate` gagal | DB credential / migration konflik | Cek `storage/logs/laravel.log`, restore backup bila perlu |
| `pdo_pgsql belum aktif` | Ekstensi PHP belum ada | Aktifkan di hPanel Hostinger / `php -m \| grep pgsql` |
| `Koneksi Supabase gagal` | URL/port salah, SSL, password | Pakai Session Pooler `:5432`, `SUPABASE_DB_SSLMODE=require` |
| `supabase:copy-data` menolak jalan | `DB_CONNECTION` bukan `mysql` | Set `DB_CONNECTION=mysql` saat sinkron |
| `supabase:copy-data` gagal TRUNCATE | Memakai role runtime | Isi `SUPABASE_ADMIN_DB_USERNAME`/`PASSWORD` dengan role admin |
| Data Supabase beda jumlah | Tabel di-skip karena kolom tidak cocok | Cek output command, lihat pesan `SKIP`/`FAIL` |
| Sinkron menggandakan data | Dijalankan tanpa `--truncate` pada tabel ber-PK | Pakai `--truncate` untuk full resync |
| `relation ... does not exist` | Skema Supabase belum lengkap | Import schema/migration dulu (lihat `docs/supabase-integration.md`) |

---

## 8. Checklist copy-paste

### Deploy production (Jalur 2, yang aktif)

```bash
# 1. Cek perubahan
git status

# 2. Commit
git add -A
git commit -m "deploy: <ringkasan perubahan>"

# 3. Push (auto rsync + deploy)
git push origin main

# 4. Pantau
tail -f /tmp/alim-auto-deploy.log
```

### Verifikasi production

```bash
curl -s https://<domain>/health
ssh alim-deploy 'cd ~/alim-app && php artisan migrate:status | tail -10'
ssh alim-deploy 'cd ~/alim-app && tail -40 storage/logs/deploy-$(date +%F).log'
ssh alim-deploy 'cd ~/alim-app && tail -40 storage/logs/laravel.log'
```

### Sinkron MySQL → Supabase (di server)

```bash
ssh alim-deploy
cd ~/alim-app

# 1. Backup MySQL
DB_NAME=$(php -r '$e=parse_ini_file(".env"); echo $e["DB_DATABASE"];')
mysqldump --single-transaction --quick --routines --triggers \
  -h 127.0.0.1 -u "$(php -r '$e=parse_ini_file(".env"); echo $e["DB_USERNAME"];')" \
  -p "$DB_NAME" > ~/backup-${DB_NAME}-$(date +%F-%H%M).sql

# 2. Cek koneksi
php artisan supabase:check

# 3. Dry-run
php artisan supabase:copy-data --dry-run

# 4. Full resync (DESTRUKTIF untuk Supabase)
php artisan supabase:copy-data --truncate --chunk=500

# 5. Verifikasi
php artisan supabase:check
```

### Jika ingin deploy lewat webhook (Jalur 1)

```bash
git push origin main
curl -s https://<domain>/health
ssh alim-deploy 'tail -f ~/alim-app/storage/logs/deploy-$(date +%F).log'
```

---

## 9. Referensi file terkait

| File | Fungsi |
|---|---|
| `deploy.sh` | Script deploy server (git pull, composer, migrate, build, cache) |
| `app/Http/Controllers/DeployController.php` | Webhook + health endpoint |
| `config/deploy.php` | Konfigurasi `DEPLOY_SECRET`, `DEPLOY_BRANCH`, `DEPLOY_SCRIPT` |
| `scripts/auto-deploy-hostinger.sh` | Rsync + SSH deploy ke Hostinger |
| `scripts/install-auto-deploy.sh` | Memasang hook `.git/hooks/pre-push` |
| `app/Console/Commands/SupabaseCheck.php` | `php artisan supabase:check` |
| `app/Console/Commands/SupabaseCopyData.php` | `php artisan supabase:copy-data` |
| `config/database.php` | Koneksi `supabase` dan `supabase_admin` |
| `docs/supabase-integration.md` | Detail integrasi & hardening Supabase |
| `docs/migrasi-supabase.md` | Audit & rencana migrasi MySQL → PostgreSQL |
| `DEPLOY-SETUP.md` | Setup webhook GitHub langkah demi langkah |
| `.github/workflows/asrama-critical.yml` | CI test (bukan deploy) |
