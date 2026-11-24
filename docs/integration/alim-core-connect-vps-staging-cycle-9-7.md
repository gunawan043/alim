# Cycle 9.7 — Final Review & Regression Verification

**Tanggal:** 2026-11-24 · **Status:** tinjauan selesai; paket instalasi **belum** disetujui untuk dijalankan.
**Ruang lingkup:** validasi perubahan sejak baseline Cycle 9.5 (`cbc6ddf9`) saja. Audit Cycle 9.1–9.6 tidak diulang.

> **Penting — repo bergerak sendiri selama sesi ini.** Proses lain melakukan `git add -A` + commit + push.
> Bukti: `git reflog` memuat `reset: moving to HEAD~1` pada 09:33, commit `eac34abe` (09:43) dan
> `2aad775c` (09:51) yang **sudah ada di `origin/main`**, sementara saya tidak pernah menjalankan
> `git commit`/`git push`. Akibatnya perubahan Cycle 9.6/9.7 saya **ikut ter-commit dan ter-push**
> oleh proses itu. `git diff` juga sempat kosong di tengah audit karena perlombaan indeks dengan
> proses tersebut. Tidak ada yang saya paksa, reset, atau batalkan.

## 1. Ringkasan hasil

| # | Pemeriksaan | Hasil | Catatan |
| --- | --- | --- | --- |
| 1 | Review seluruh diff sejak baseline | **selesai** | dua kelompok perubahan; satu file riset saya tidak tercatat |
| 2 | `/health` aman & monitoring tetap jalan | **lulus** | monitoring produksi hanya memeriksa HTTP 200/liveness |
| 3 | Webhook deploy: produksi aktif, staging tertutup | **lulus** | route bersyarat + penjaga 404 |
| 4 | Audit `BackupDatabase` | **lulus setelah 2 perbaikan** | lihat §4, ada satu asumsi yang saya koreksi |
| 5 | Regression test | **lulus** | 162 test / 627 assertion |
| 6 | Skrip: traversal, symlink, permisi, partial failure, rollback | **lulus setelah 3 perbaikan** | §6 |
| 7 | Dry-run benar-benar tidak menulis | **lulus** | dibuktikan dengan snapshot direktori |
| 8 | Tidak ada kredensial/berkas staging masuk Git | **lulus** | §8 |
| 9 | Tidak sentuh produksi/DNS/push/merge/deploy | **lulus** | hanya baca produksi |

## 2. Diff sejak baseline `cbc6ddf9`

### 2.1 Kelompok A — pekerjaan saya (Cycle 9.6 + 9.7)

| Berkas | Isi |
| --- | --- |
| `app/Http/Controllers/DeployController.php` | `/health` minimal; `handle()` menolak non-produksi |
| `routes/web.php` | `POST /webhook/deploy` hanya di produksi |
| `app/Console/Commands/ConnectStagingVerify.php` | cek 1b minimalitas `/health` |
| `app/Console/Commands/BackupDatabase.php` | kata sandi DB tidak lagi di env proses |
| `scripts/build-connect-staging-profile.sh` | kanal keluar staging dipaksa tertutup |
| `scripts/provision-connect-staging-vps.sh` (baru) | preflight/`--install` VPS |
| `scripts/verify-connect-staging-vps.sh` (baru) | verifikasi HTTPS pasca-instalasi |
| `docs/integration/deploy/nginx-alim-staging.conf` (baru) | vhost staging |
| `docs/integration/alim-core-connect-vps-staging-cycle-9-6.md` (baru) | dokumen Cycle 9.6 |
| `tests/Feature/BackupDatabaseCommandTest.php` (baru) | 6 test |
| `tests/Feature/DeployRouteAndHealthTest.php` (baru) | 6 test |

### 2.2 Kelompok B — pekerjaan proses lain (naskah/soal, kartu pelajar)

`SoalSayaController`, `SoalNaskahPdfService`, `SoalOpsiLayout`, `PaketSoalNaskahPdfService`,
`LjkArabicText`, `PrintSettingsService`, `DocumentPdfService`, `StudentCardSetting`,
`StudentCardController`, view `ljk/*`, `students/cards/*`, sidebar satuan pendidikan,
`tests/Feature/BankSoalSkScopeTest.php`, `tests/Feature/StudentCardTest.php`.

Saya **tidak mengubah** berkas kelompok B. Batas antara kedua kelompok ditetapkan dari
`git status`/`git log` sebelum saya mulai. Uji regresi kelompok B tetap dijalankan (§5).

### 2.3 Temuan proses yang harus Anda ketahui

1. **Perubahan saya sudah ter-push** oleh proses lain (lihat catatan di atas). Konsekuensi:
   `/health` di `origin/main` sudah tidak memuat `env`.
2. **Satu berkas riset saya hilang dari working tree** tanpa jejak stash:
   `resources/views/ljk/arab/soal-pdf.blade.php` (431 baris baru) sempat muncul di `git status`,
   lalu tidak ada lagi setelah commit proses lain. Isinya sekarang ada di riwayat commit
   (`eac34abe`), bukan di working tree. Saya tidak mengubahnya.
3. `git diff` sempat mengembalikan keluaran kosong padahal `git status` menandai 9 berkas;
   setelah proses lain selesai, hasilnya konsisten. Tidak ada berkas yang saya tulis hilang.

## 3. `/health` dan webhook deploy

### 3.1 Isi respons

```php
return response()->json(['status' => 'ok', 'time' => now()->toIso8601String()]);
```

`RequestIdMiddleware` menambahkan `request_id` ke **setiap** respons JSON (korelasi log), jadi
respons nyata = `request_id`, `status`, `time`. Tidak ada `env`, versi, nama aplikasi, atau path.

Bukti test (`DeployRouteAndHealthTest`):
- `test_health_returns_status_and_time_only` — kunci tepat `request_id,status,time`; `status=ok`; `request_id` == header `X-Request-ID`.
- `test_health_does_not_leak_environment_or_app_name` — body tidak memuat `testing/production/staging/local/APP_ENV/env/app/Laravel`.

### 3.2 Monitoring produksi tidak rusak

Dibaca langsung (read-only) di host produksi:

| Konsumen | Yang diperiksa | Terdampak? |
| --- | --- | --- |
| `~/alim-app/deploy.sh:133-138` | `curl -fsS` ke `http://127.0.0.1/health` → sukses bila 2xx | tidak |
| `~/alim-deploy-live.sh:86` | `curl -fsS https://alim.abuhurairah.id/login` | tidak |
| `~/bump-deploy.sh:84` | `curl -fsS https://bump.abuhurairah.id/` | tidak |

Tidak ada konsumen yang membaca `env`/`app` (dikonfirmasi dengan `grep -n "health\|curl"`).
Bentuk respons (objek JSON) tetap; hanya dua kunci yang dihapus.

### 3.3 Webhook deploy

- Route hanya didaftarkan bila `app()->environment('production')` → `route:list` di `APP_ENV=production`
  menemukan `POST webhook/deploy` (1), di lingkungan lain 0.
- `DeployController::handle()` diawali `abort_unless(app()->environment('production'), 404)` —
  berlaku walau route cache produksi masih memuat route ini.
- Test: route tidak terdaftar di luar produksi; `POST /webhook/deploy` menghasilkan 404/405 (bukan 200/202);
  route sementara yang didaftarkan manual tetap 404.

## 4. Audit `BackupDatabase`

### 4.1 Perilaku sekarang

| Jalur | Kata sandi | Berkas sementara | Pembersihan |
| --- | --- | --- | --- |
| MySQL/MariaDB | berkas opsi `[client]` 0600, dipakai via `--defaults-extra-file` | `tempnam` → di-`rename` ke `*.cnf` | `finally` |
| PostgreSQL | `PGPASSFILE` 0600 (env hanya berisi **jalur**) | `tempnam` | `finally` |
| SQLite | tidak ada (menyalin berkas) | — | — |

### 4.2 Bukti

Test (`BackupDatabaseCommandTest`, `PendingProcess` palsu yang merekam perintah + env dan membaca
berkas saat "dump berjalan"):

| Test | Bukti |
| --- | --- |
| `mysql_password_never_in_arguments_or_environment` | baris perintah tanpa `PROBE-SECRET-PASSWORD`, `--user=`, `--host=`, `--port=`; env tanpa `MYSQL_PWD` |
| `mysql_option_file_is_0600_and_removed_after_dump` | permisi `0600`; isi memuat kata sandi; tidak memuat nama database; berkas hilang setelah selesai |
| `mysql_option_file_removed_even_when_dump_fails` | exit 2 → exception + berkas hilang |
| `pgsql_password_only_in_0600_passfile_not_pgpassword` | env hanya `PGPASSFILE`; `PGPASSWORD` tidak ada; 0600; hilang |
| `pgsql_passfile_removed_even_when_dump_fails` | exit 1 → exception + berkas hilang |
| `sqlite_path_still_copies_file_without_external_process` | identik byte-per-byte; `Process::preventStrayProcesses()` tidak terpicu (tidak ada proses eksternal) |

Uji mutasi (saya ubah sumber, jalankan test, kembalikan):
- `finally` diganti `catch { throw } + hapus setelahnya` → **2 test gagal** (`Failed asserting that true is false`)
  ⇒ test benar-benar menjaga pembersihan saat gagal.
- Penghapusan `chmod 0600` **tidak** menggagalkan test karena `tempnam()` sudah membuat berkas 0600 —
  jadi 0600 di sini bersifat mempertahankan, bukan memperbaiki; test tetap menjaga nilainya.

Uji nyata di mesin ini (driver MySQL lokal, 23 MB dump):
```
php artisan app:backup --keep=3
→ Backup database: storage/app/backups/backup_20261124_094335_mysql.sql.gz   (exit 0)
→ gzip -t OK; sha256 berkas == isi .sha256
→ ls /tmp | grep -E "alim-(my|pg)"  → tidak ada
```
Artefak uji itu milik saya (berisi data lokal, bukan produksi) dan bisa dihapus.

### 4.3 Dua koreksi yang ditemukan saat audit

1. **`tempnam()` tanpa akhiran `.cnf` berisiko**: MySQL/MariaDB mendokumentasikan opsi
   `--defaults-extra-file` harus berakhiran `.cnf`. Diuji dengan MariaDB 12.3 lokal, nama tanpa
   akhiran **diterima**, jadi klaim itu tidak terbukti di build ini — tetapi rename ke `.cnf`
   saya pertahankan (biaya nol, menghilangkan ketergantungan pada perilaku build tertentu).
2. **Pembersihan tidak dijamin saat exception**: versi saya sebelumnya menghapus berkas setelah
   `Process::run()`; bila `run()` melempar (timeout), berkas tertinggal. Sekarang `finally`.

### 4.4 Risiko tersisa

- Jalur MySQL **tidak** dipakai produksi (produksi `DB_CONNECTION=sqlite`). Jalur MySQL/PG hanya
  diuji dengan proses palsu + sekali jalan nyata lokal.
- `app:backup` terjadwal di produksi (`app/Console/Kernel.php:125`, `--keep=14 --with-files --verify`)
  dan **tidak saya jalankan di produksi**. Jalur SQLite-nya tidak berubah (test menjamin hanya menyalin berkas).
- Catatan lama: `backupSqlite()` menyalin berkas tanpa `.backup` API; risiko WAL sudah tercatat di
  audit sebelumnya dan tidak diubah di cycle ini.

## 5. Regression test

```
php artisan test tests/Feature/BackupDatabaseCommandTest.php \
  tests/Feature/DeployRouteAndHealthTest.php \
  tests/Feature/ConnectApprovalCommandTest.php \
  tests/Feature/VerifyConnectSchemaCommandTest.php \
  tests/Feature/WhatsAppConnectSsoTest.php \
  tests/Feature/BankSoalSkScopeTest.php \
  tests/Feature/StudentCardTest.php
→ Tests: 162 passed (627 assertions), 49.15s, exit 0
```

Pint: 4 berkas PHP yang saya ubah/baru → `PASS`. `ConnectStagingVerify.php` sudah gagal pint
**sebelum** perubahan saya (`concat_space`), jadi tidak ikut saya rapikan.

## 6. Audit skrip

| Ancaman | Penanganan | Bukti |
| --- | --- | --- |
| Path traversal via `--app-dir` | jalur **wajib absolut**; ditolak `/`, `/srv`, `/tmp`, `/var`, `/usr`, `/etc`; ditolak bila berada di dalam repo | `--app-dir ..` → `[STOP] harus jalur absolut`; `--app-dir $PWD/sub` → `[STOP] berada di dalam repo` |
| Symlink ke direktori produksi | `realpath`/`abspath` dipakai sebelum dibandingkan (sudah ada sejak 9.5) | `app-dir terpisah dari produksi` |
| Permission profil | 600 wajib; deteksi GNU vs BSD stat lewat `--version` (bukan mencoba opsi — BSD `stat` menerima `-c` sebagai nama berkas) | preflight menerima profil 600, menolak selain |
| Kanal keluar profil | preflight menolak bila `MAIL_MAILER≠log`, `BROADCAST_*≠null`, `QUEUE≠sync`, `WA_ENABLED≠false`, `APP_DEBUG≠false`, `LOG_CHANNEL≠staging`; pesan menyebut cara memperbaiki | `MAIL_MAILER=smtp` → `[STOP] … Perbaiki: jalankan ulang build-connect-staging-profile.sh` |
| Commit sumber | `REF` ditentukan **di preflight** (dicatat di laporan), dan `artisan` harus ada di commit itu | `commit sumber 2aad775c`; repo yang bukan ALIM → `[STOP] tidak memuat artisan` |
| Partial failure | `trap EXIT` melaporkan langkah yang gagal; `.env` 600, DB kosong, `verify-connect-schema`, dan cek tinker (`mail=log`, `broadcast=null`, `queue=sync`, `wa=false`, DB staging) semuanya → exit ≠0 menghentikan | uji `--install` di sandbox: berhenti setelah DB dibuat dengan `[GAGAL] langkah terakhir keluar dengan kode 3` |
| Rollback | R1–R7 hanya di VPS; tidak menyentuh produksi | dokumen 9.6 §8 |

Uji `--install` di sandbox sengaja **gagal di `composer install`** (tanpa `vendor/`), yang justru
membuktikan penghentian aman: tidak ada langkah setelah kegagalan yang dijalankan.

## 7. Dry-run tidak menulis apa pun

```
PATH=<shim nginx/composer/systemctl/crontab> provision-connect-staging-vps.sh \
  --host alim-staging.abuhurairah.id --profile … --app-dir /tmp/sbx/fakeos/srv/alim-staging --repo <repo>
→ 15 baris [ok] … Preflight LULUS. Mode pemeriksaan — tidak ada yang ditulis.
→ EXIT=0
```

Snapshot direktori uji sebelum/sesudah: `entries before=18 after=18`, satu-satunya entri berubah
adalah direktori induk `/tmp/sbx/fakeos/srv` (saya yang membuatnya), app-dir tetap kosong.
Tanpa `--install`, skrip hanya membaca (`php -r`, `stat`, `df`, `grep`, `curl` bila ada).

## 8. Tidak ada kredensial atau berkas staging di Git

```
git ls-files | grep -iE "\.env|\.sqlite|secrets|connect-keys|\.pem$|\.key$"
→ hanya .env.example / .env.production.example / migrasi bernama "token"
git check-ignore -v .env.staging          → .gitignore:7
git check-ignore -v database/staging.sqlite → database/.gitignore:1
git ls-tree -r 2aad775c | grep -icE "\.env\.staging|staging\.sqlite|connect-keys|secrets\.enc"  → 0
git grep "CONNECT_(SSO|APPROVAL)_API_KEY=<nilai panjang>" → kosong
```

Berkas yang masuk Git hanya skrip, vhost, dokumen, dan dua test — semua tanpa nilai rahasia.

## 9. Yang tidak dilakukan

Tidak mengubah konfigurasi produksi, tidak menyewa VPS, tidak mengubah DNS, tidak menjalankan
deploy, merge, atau push. Satu-satunya sentuhan ke produksi adalah **baca** (`grep` skrip deploy,
`.env` produksi hanya dibaca dua kunci `DB_CONNECTION`/`DB_DATABASE`).

## 10. Risiko tersisa (perlu keputusan Anda)

1. **Kepemilikan commit**: perubahan saya sudah berada di `origin/main` karena proses lain.
   Bila Anda ingin riwayat yang jelas, perubahan itu perlu ditinjau ulang sebagai commit
   `connect(staging)` tersendiri — bukan sesuatu yang bisa saya lakukan tanpa persetujuan.
2. **Berkas riset hilang dari working tree** (`resources/views/ljk/arab/soal-pdf.blade.php`).
   Isinya ada di commit `eac34abe`; perlu dikonfirmasi apakah memang sengaja dihapus.
3. **Skrip belum diuji di VPS Linux nyata** — hanya preflight di macOS dengan shim + `bash -n`.
   `--install` belum pernah berjalan sampai `verify-connect-schema` lulus.
4. **`php-fpm` socket** di vhost diasumsikan `php8.4-fpm.sock`.
5. **Connect** masih memegang tiga cacat runner dari Cycle 9.3 (pemilik: operator Connect).
6. `/health` kini tidak memberi penanda lingkungan; membedakan staging vs produksi harus lewat
   jalur lain (mis. hostname/TLS), sesuai syarat Anda.
