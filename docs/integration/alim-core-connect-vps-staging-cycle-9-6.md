# Cycle 9.6 — Persiapan VPS Staging Terisolasi (Skrip, Dry-Run, Ukuran)

**Tanggal:** 2026-11-24 · **Status:** persiapan + skrip siap ditinjau. **Belum ada yang dipasang.**
**Repo Core:** `alim`, `main`. Commit lokal terakhir sebelum cycle ini: `cbc6ddf9` (**belum di-push**).

Dokumen ini menjawab permintaan Cycle 9.6: menyiapkan (bukan menjalankan) VPS staging
2 vCPU / 4 GB / 25 GB, 3 kredensial staging, TLS, pengumpulan CIDR — dengan delapan syarat
pengerasan yang Anda tetapkan. Tidak ada VPS yang disewa, DNS yang diubah, atau akses publik
yang dibuka.

---

## 0. Ringkasan: delapan syarat dan bagaimana dipenuhi

| # | Syarat Anda | Status | Buke/berkas |
| --- | --- | --- | --- |
| 1 | `/health` minimal, tanpa `APP_ENV` | **selesai** | `DeployController::health()` hanya `status` + `time`; diperiksa `ConnectStagingVerify` cek 1b dan `verify-connect-staging-vps.sh` cek 1b |
| 2 | Staging tidak bisa memicu notifikasi/queue/scheduler produksi | **selesai** | `.env.staging` + builder: `MAIL_MAILER=log`, `BROADCAST_DRIVER=null`, `QUEUE_CONNECTION=sync`, `WA_ENABLED=false`; provision **menolak** bila `crontab` memuat `schedule:run` |
| 3 | `APP_KEY` & kredensial berbeda | **selesai** | provision menolak bila `APP_KEY` sama dengan produksi; builder menolak kunci SSO/Approval identik atau < 32 karakter |
| 4 | Hanya data sintetis | **selesai** | `ConnectStagingTestDataSeeder` menolak `APP_ENV != staging`; verifikasi menghitung santri ber-NISN, wajib 0 |
| 5 | Tidak ada salinan `.env`/DB/storage/backup produksi | **selesai** | provision menolak host yang punya direktori aplikasi produksi; DB dibuat kosong di host; tidak ada perintah penyalin |
| 6 | Rollback tidak pernah menyentuh produksi | **selesai** | §8; semua langkah hanya di VPS |
| 7 | Dry-run / preflight / verifikasi pasca-instalasi | **selesai** | §5–§6, hasil dry-run §7 |
| 8 | Ukuran disk dicek ulang | **selesai** | §4 — ada **koreksi**: 25 GB cukup, tetapi perlu `vendor` 215 MB + cadangan |

Tambahan yang saya temukan saat mengerjakan syarat 1–2 (di luar daftar, sudah diperbaiki):

- **`POST /webhook/deploy` (auto-deploy) sebelumnya terdaftar di semua lingkungan.** Di host
  staging publik, endpoint itu adalah jalur menarik kode/commit ke host uji. Route sekarang
  hanya didaftarkan di `production`, dan `DeployController::handle()` menolak (404) di luar
  produksi. Produksi tidak berubah perilakunya.
- `.env.staging` sebelumnya memakai `MAIL_MAILER=smtp` ke `smtp.example.com` dan
  `BROADCAST_DRIVER=pusher`. Keduanya sudah ditutup di berkas lokal dan dipaksa oleh builder.
- **`app:backup` menaruh kata sandi DB di nilai environment** (`MYSQL_PWD`/`PGPASSWORD`), dan
  ia berjalan terjadwal di produksi. Nilai environment proses dapat dibaca proses lain milik
  user yang sama (terbukti: `proc_open` dengan env anak tidak menandai proses sebagai "berbeda
  user", jadi `/proc/<pid>/environ` pada Linux ikut terbaca). Sekarang kata sandi lewat berkas
  sementara 0600 (`--defaults-extra-file` untuk MySQL, `PGPASSFILE` untuk PostgreSQL) yang
  dihapus setelah dump. Bonus: `--defaults-extra-file` juga mencegah MySQL mencetak
  `SET PASSWORD` bila ada yang memakai `DEBUG=1/2`. Jalur SQLite (yang dipakai produksi)
  tidak berubah.

## 1. Yang berubah di repo (semua lokal, belum di-push)

| Berkas | Perubahan |
| --- | --- |
| `app/Http/Controllers/DeployController.php` | `/health` minimal; `handle()` menolak non-produksi |
| `routes/web.php` | `POST /webhook/deploy` hanya didaftarkan di produksi |
| `app/Console/Commands/ConnectStagingVerify.php` | cek **1b**: `/health` hanya boleh `status`,`time` |
| `scripts/build-connect-staging-profile.sh` | memaksa kanal keluar tertutup + validasi baru |
| `scripts/provision-connect-staging-vps.sh` | **baru** — preflight/`--install` untuk VPS |
| `scripts/verify-connect-staging-vps.sh` | **baru** — verifikasi HTTPS pasca-instalasi |
| `app/Console/Commands/BackupDatabase.php` | kata sandi DB lewat berkas 0600, bukan nilai env proses |
| `docs/integration/deploy/nginx-alim-staging.conf` | **baru** — vhost staging + aturan deny |
| `.env.staging` (gitignored) | mail log, broadcast null, WA mati |

## 2. Isolasi — apa yang menjamin produksi tidak tersentuh

| Objek | Produksi | VPS staging | Penjaga otomatis |
| --- | --- | --- | --- |
| Mesin | shared hosting | VPS | provision `die` bila ada `~/alim-app` |
| Direktori | `~/alim-app` | `/srv/alim-staging` | app-dir ditolak bila sama dengan repo |
| Kode | checkout produksi | `git archive <commit>` | dari commit, bukan working tree |
| DB | berkas produksi | `database/staging.sqlite` baru | dibuat kosong; verifikasi mencocokkan nama berkas |
| `.env` | `.env` produksi | 600 dari profil | `APP_KEY` wajib beda; profil dilarang menyebut domain produksi |
| Kredensial | tidak ada `CONNECT_*` | 2 kunci + 1 ingest | SSO ≠ Approval, ≥ 32 karakter |
| Surel | SMTP nyata | `log` | builder + tinker check |
| Broadcast | pusher | `null` | builder + tinker check |
| WhatsApp | gateway aktif | `WA_ENABLED=false` | builder + tinker check |
| Scheduler | cron produksi | **tidak dipasang** | provision `die` bila crontab punya `schedule:run` |
| Auto-deploy | webhook | route tidak ada | `routes/web.php` + guard 404 |

Tidak ada satu pun perintah di skrip yang menulis ke host produksi.

## 3. Tiga kredensial staging

| Kunci | Pemilik | Isi |
| --- | --- | --- |
| `CONNECT_SSO_API_KEY` (`sso:exchange`) | Core staging | dibuat di secret manager mesin pengembang, diserahkan lewat `connect-keys.env` 600 |
| `CONNECT_APPROVAL_API_KEY` (`approval:command`) | Core staging | idem |
| `ALIM_CONNECT_INGEST_KEY` | **Connect** | **Core tidak membacanya** — tidak boleh masuk `.env` staging Core (builder + installer menolak nama ber-`INGEST`) |

Saat ini secret manager staging hanya memuat dua scope SSO/Approval. Kunci ingest milik Connect
harus diterbitkan di sisi Connect oleh operatornya, lalu diserahkan ke Core **sebagai nilai untuk
Connect**, bukan untuk `.env` Core.

## 4. Ukuran disk — dicek ulang (permintaan Anda)

Diukur langsung dari repo hari ini:

| Komponen | Ukuran | Catatan |
| --- | --- | --- |
| Berkas ter-track (uncompressed) | **29 MB** | 12.666 berkas |
| `git archive` tar (stream) | 281 MB | setelah ekstrak ukurannya sama, ± 280 MB |
| `public/build` | **183 MB** | ter-track, tidak perlu `npm run build` |
| `vendor/` hasil `composer install --no-dev` | **215 MB** | wajib; tidak ter-track |
| `database/staging.sqlite` setelah migrate+seed | **10 MB** | |
| Cadangan DB harian × 7 | **70 MB** | |
| Log staging | tumbuh ~10–50 MB/bln | tanpa rotasi |
| **Total setelah instalasi + 1 minggu cadangan** | **± 500 MB** | |

**Kesimpulan:** 25 GB jauh lebih dari cukup; kebutuhan nyata < 1 GB. Batas aman yang dipakai
skrip: **minimum 12 GB bebas** — memberi ruang untuk paket OS (~1,5 GB), PHP/nginx, snapshot,
dan log yang tidak dirotasi. Saran: 25 GB tetap diterima, tidak perlu menaikkan biaya.

Penting: `vendor/` **selalu** di-generate di VPS dari `composer.lock`; menyalin `vendor` dari
mesin pengembang (PHP 8.5.8 di sini, VPS PHP 8.3/8.4) berisiko binary/ekstensi tidak cocok.

## 5. Skrip yang diserahkan

### 5.1 `scripts/provision-connect-staging-vps.sh`

Default **preflight** (nol tulisan). Urutan preflight:

1. argumen lengkap · 2. bukan host produksi · 3. app-dir wajar & bukan repo · 4. host = staging
· 5. `php nginx git tar composer sqlite3` · 6. PHP ≥ 8.3 + 11 ekstensi · 7. php-fpm jalan ·
8. scheduler tidak aktif · 9. profil 600 + `APP_URL` = `https://<host>` + tanpa domain produksi ·
10. ruang bebas ≥ 12 GB · 11. status sertifikat.

Dengan `--install` (semuanya berhenti pada kegagalan pertama):

```
git archive <commit> | tar -x -C /srv/alim-staging     # dari commit, bukan working tree
composer install --no-dev --optimize-autoloader        # vendor dibuat di host
install -m 600 profil/.env.staging /srv/alim-staging/.env
mkdir storage/* bootstrap/cache database                # owner www-data
: > database/staging.sqlite                              # DB kosong
php artisan migrate --force
php artisan db:seed --class=ConnectStagingTestDataSeeder --force
php artisan alim:verify-connect-schema                  # exit 0 atau berhenti
php artisan config:cache route:cache view:cache
php artisan tinker --execute  # mail=log, broadcast=null, queue=sync, wa=false, db=staging.sqlite
```

### 5.2 `scripts/verify-connect-staging-vps.sh`

Dijalankan di direktori aplikasi staging setelah TLS terpasang. Menolak bila `.env` bukan
staging atau DB bukan berkas staging. Pemeriksaan:

| # | Uji | Harap |
| --- | --- | --- |
| 1 | `/health` | 200 |
| 1b | kunci JSON `/health` | **tepat** `status,time` |
| 2 | tanpa kunci / kunci salah / kunci tertukar (2 arah) | 401 |
| 3 | versi `2` → 422 `unsupported_api_version`; versi `1` lolos gate | 422, bukan `unsupported_api_version` |
| 4 | aktor tak terpetakan | 403 `actor_identity_unmapped` |
| 5 | `X-Forwarded-For` di luar allowlist | 403 |
| 6 | body error | tanpa `SQLSTATE`, stack, path, `vendor/` |
| 7a | permit sintetis | tepat 2 |
| 7b | santri ber-NISN | 0 |
| 8 | `alim:connect-staging-verify` penuh | 200, duplicate, 409, audit |

## 6. Keterbatasan yang harus Anda ketahui

1. **Cek 5 (allowlist lewat `X-Forwarded-For`) hanya bermakna** bila nginx tidak menyalin
   header klien. Vhost di `docs/integration/deploy/nginx-alim-staging.conf` **sengaja tidak**
   memakai `$proxy_add_x_forwarded_for`; kalau kelak ada proxy/CDN di depan VPS, uji ini harus
   disesuaikan dan allowlist dipindah ke tingkat nginx.
2. **Sertifikat TLS belum ada** sampai DNS `alim-staging.abuhurairah.id` menunjuk IP VPS. Tanpa
   DNS, hanya jalur B2 (`sslip.io`) yang mungkin, dan itu tidak akan lolos `--host` di skrip —
   disengaja, agar host tidak "kelihatan resmi" padahal bukan.
3. **php-fpm socket** di vhost diasumsikan `php8.4-fpm.sock`; bila VPS memakai 8.3, ubah satu
   baris itu (diberi komentar).
4. Skrip provision **belum diuji di server Linux nyata** — hanya `bash -n` + dry-run di macOS
   (berhenti di "nginx tidak ditemukan", exit 3). Uji sebenarnya baru bisa setelah VPS ada.
5. Connect tetap memegang tiga cacat runner (dari Cycle 9.3) — perbaikan ada di `~/OpenWA`
   tapi belum di-commit oleh pemiliknya.

## 7. Hasil dry-run (di mesin ini, hari ini)

```
$ scripts/provision-connect-staging-vps.sh --host alim-staging.abuhurairah.id \
      --profile /tmp/x --app-dir /srv/alim-staging
ALIM Core staging VPS — preflight
  [ok]   argumen lengkap
  [ok]   tidak ada direktori aplikasi produksi di host ini
  [ok]   app-dir terpisah dari repo: /srv/alim-staging
  [ok]   host = alim-staging.abuhurairah.id
  [STOP] nginx tidak ditemukan.
exit=3
```

Hentinya benar: lingkungan lokal bukan VPS. Yang terbukti dari dry-run adalah urutan penjaga
berjalan dan **tidak ada tulisan** sebelum prasyarat lengkap. Dengan `--install` skrip tetap
berhenti di baris yang sama.

Pemeriksaan host yang sudah berjalan: `/health` baru diuji lewat `ConnectStagingVerify` cek 1b
dan `verify-connect-staging-vps.sh` cek 1b (keduanya menolak bila ada kunci selain
`status`,`time`).

## 8. Backup dan rollback (hanya di VPS)

**Backup**
- Snapshot penyedia harian, retensi 7 hari.
- `sqlite3 database/staging.sqlite ".backup /srv/alim-staging/storage/app/alim-staging/connect/staging-$(date +%F).sqlite"` harian, retensi 7 hari.
- `.env` **tidak** ikut backup otomatis; sumbernya profil 600 di mesin pengembang + secret manager.

**Rollback** — tidak ada satu pun langkah yang menyentuh produksi:

| # | Aksi | Dampak ke produksi |
| --- | --- | --- |
| R1 | `ufw deny 443` | tidak ada |
| R2 | `systemctl stop nginx` / hapus vhost | tidak ada |
| R3 | `rm -rf /srv/alim-staging` | tidak ada |
| R4 | `certbot delete --cert-name alim-staging.abuhurairah.id` | tidak ada (sertifikat terpisah) |
| R5 | Hapus A record subdomain | tidak ada |
| R6 | `alim:connect-secret rotate` (staging), serahkan ulang ke Connect | tidak ada |
| R7 | Destroy VPS / simpan snapshot | tidak ada |

## 9. Yang masih ditunggu sebelum eksekusi

1. **Persetujuan sewa VPS** (2 vCPU / 4 GB / 25 GB, ± Rp 80–150 rb/bln) — belum disewa.
2. **A record** `alim-staging.abuhurairah.id` → IP VPS (tanpa ini TLS tidak bisa).
3. **CIDR egress Connect** dari operator Connect (untuk `CONNECT_*_ALLOWED_IPS`).
4. Konfirmasi kunci staging boleh diterbitkan baru (SSO/Approval sudah ada di secret manager
   staging; ingest diterbitkan di sisi Connect).

Setelah keempatnya ada: jalankan provision preflight → `--install` → nginx + certbot →
`verify-connect-staging-vps.sh`. Akses publik dibuka hanya setelah verifikasi lulus, dan
`cbc6ddf9` beserta commit cycle ini tetap **tidak** di-push sampai Anda memutuskan.
