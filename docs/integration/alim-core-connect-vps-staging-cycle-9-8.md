# Cycle 9.8 — Repository Integrity & Production Deployment Reconciliation

**Tanggal:** 2026-11-24 · **Status:** pemeriksaan read-only selesai. **Tidak ada** commit, push, reset,
rebase, merge, cherry-pick, revert, deploy, atau perubahan berkas produksi.
**Baseline:** Cycle 9.5 `cbc6ddf9` · **HEAD saat laporan:** `2aad775c` (branch `main`, upstream
`origin/main`, ahead/behind `0 0`).

> **Koreksi laporan Cycle 9.7.** Di 9.7 saya menulis bahwa perubahan 9.6/9.7 "ikut ter-commit dan
> ter-push oleh proses lain" dan bahwa `resources/views/ljk/arab/soal-pdf.blade.php` "hilang".
> Keduanya **salah**; bukti di §1.3 dan §2. Koreksi ini disampaikan di awal laporan.

---

## 1. Fakta Git

### 1.1 Keadaan sekarang

| Item | Nilai |
| --- | --- |
| Branch | `main` |
| HEAD | `2aad775c5e31c04d4082b0f0c3d169fd9f64d2bd` |
| Upstream | `origin/main` (git ls-remote: `2aad775c`), ahead/behind `0 0` |
| Stash | kosong |
| Staged | tidak ada (kolom pertama `git status` kosong untuk semua berkas) |
| Unstaged | 7 berkas (5 milik saya, 2 milik proses lain) |
| Untracked | 7 entri (dokumen, skrip, vhost, 2 test) |

```
 M app/Console/Commands/BackupDatabase.php          ← saya (9.7)
 M app/Console/Commands/ConnectStagingVerify.php    ← saya (9.6)
 M app/Http/Controllers/DeployController.php        ← saya (9.6)
 M routes/web.php                                   ← saya (9.6)
 M scripts/build-connect-staging-profile.sh         ← saya (9.6)
 M app/Services/Ljk/LjkArabicText.php               ← proses lain (09:39–09:51)
 M app/Http/Controllers/StudentCardController.php   ← proses lain
?? docs/integration/alim-core-connect-vps-staging-cycle-9-6.md   ← saya
?? docs/integration/alim-core-connect-vps-staging-cycle-9-7.md   ← saya
?? docs/integration/deploy/                                      ← saya
?? scripts/provision-connect-staging-vps.sh                      ← saya
?? scripts/verify-connect-staging-vps.sh                         ← saya
?? tests/Feature/BackupDatabaseCommandTest.php                   ← saya
?? tests/Feature/DeployRouteAndHealthTest.php                    ← saya
```

### 1.2 Reflog dan urutan commit sejak baseline

`cbc6ddf9` (01:32) → 10 commit (semua `naskah-arab`/`santri`) → `2aad775c` (09:51).
Tidak ada `merge`, `rebase`, `pull`, atau `cherry-pick`. Satu-satunya operasi riwayat adalah
`reset: moving to HEAD~1` (09:33) — **reset lunak** (`reset` tanpa `--hard`), karena `74681df1`
digantikan `8eedf30e` tanpa perubahan berkas hilang.

| Commit | Waktu | Siapa | Isi |
| --- | --- | --- | --- |
| `7f3a1b09` | 02:00 | proses lain | grid kolom opsi sejajar |
| `f20d8c01` | 02:24 | proses lain | RTL opsi Arab, default bagian wajib |
| `8a3ff135` | 07:14 | proses lain | 422 saat konteks sekolah kosong |
| `b3f9c04b` | 07:39 | proses lain | marker opsi & identitas RTL |
| `549d2207` | 07:53 | proses lain | instruksi & daftar karakter wacana |
| `fa95deb1` | 08:14 | proses lain | preview kartu, PDF ukuran kartu, PNG |
| `8eedf30e` | 09:33 | proses lain | nomor soal berdigit Arab |
| `75635270` | 09:33 | proses lain | PDF kartu presisi & ekspor PNG |
| **`eac34abe`** | **09:43** | proses lain | **template RTL terpisah (view `ljk.arab.*`) + nama mapel/guru Arab** |
| **`2aad775c`** | **09:51** | proses lain | titik nomor di kiri, daftar karakter rata kanan |

### 1.3 Isi `eac34abe` dan `2aad775c`

```
eac34abe  8 berkas  +721 −60
  SoalSayaController           8 ±
  StudentCardController       67 ±   ← ikut terbawa commit naskah
  PaketSoalNaskahPdfService    7 ±
  SoalNaskahPdfService        37 ±
  LjkArabicText               57 ±
  ljk/arab/soal-opsi.blade.php  +123 (baru)
  ljk/arab/soal-pdf.blade.php   +431 (baru)
  BankSoalSkScopeTest          +51
  Pesan: "Test: BankSoalSkScopeTest 80 lulus (312 assertion), PaketNaskahPolicyTest 7 lulus."

2aad775c  3 berkas  +18 −9
  ljk/arab/soal-pdf.blade.php  4 ±
  ljk/soal-pdf.blade.php       4 ±
  BankSoalSkScopeTest        +11 −1
  Pesan: "Test: BankSoalSkScopeTest 80 lulus (313 assertion)."
```

Penulis kedua commit: `Wawan Gunawan <[email protected]>`. Keduanya sudah ada di `origin/main`.

### 1.4 Apa yang ada di `origin/main` dan apa yang tidak

**Perubahan Cycle 9.6/9.7 — TIDAK ada di `origin/main`.** Bukti per-onde:

```
git diff --quiet cbc6ddf9 HEAD -- <file>   → "tidak" untuk kelima berkas
git hash-object <file>  vs  git rev-parse HEAD:<file>   → berbeda semua
git show HEAD:app/Http/Controllers/DeployController.php | grep "'env' =>"   → masih ada
git show HEAD:routes/web.php | grep -c "environment('production')"          → 0
```

| Berkas | Blob worktree | Blob di HEAD | Di `origin/main`? |
| --- | --- | --- | --- |
| `BackupDatabase.php` | `07f980f2` | `67bae991` | ya (versi lama 9.5) |
| `ConnectStagingVerify.php` | `8c886a60` | `f89b05b8` | ya (versi lama) |
| `DeployController.php` | `c7ddb3fb` | `c4f3c3b7` | ya (versi lama, `/health` masih `env`) |
| `routes/web.php` | `5355b40a` | `c5012ceb` | ya (tanpa penjaga webhook) |
| `build-connect-staging-profile.sh` | `c6cf0bf7` | `5e145754` | ya (versi lama) |

Satu-satunya bagian `routes/web.php` yang berubah di `origin/main` sejak baseline adalah rute kartu
(`cards.preview`, `cards.png`) milik proses lain — bukan milik saya.

**Kesimpulan §1.4:** perubahan saya **belum masuk riwayat** sama sekali; tidak ada commit yang
memuatnya, dan tidak ada yang ter-push. Status "repo bergerak sendiri" di 9.7 benar soal
**munculnya commit proses lain** (dan `git diff` sempat kosong karena perlombaan indeks), tetapi
**salah** soal perubahan saya ikut ter-commit.

### 1.5 Perubahan yang hilang, tertimpa, atau belum masuk riwayat

| Kemungkinan | Hasil |
| --- | --- |
| Hilang | tidak ada berkas saya yang hilang; 5 berkas termodifikasi + 7 entri untracked utuh |
| Tertimpa | tidak ada: `74681df1` digantikan `8eedf30e` (reset lunak) dan tidak menyentuh berkas saya |
| Belum masuk riwayat | **semua** perubahan 9.6/9.7 (5 modifikasi + 7 berkas baru) |

Berkas milik proses lain yang **tidak** ada di commit mana pun: `app/Services/Ljk/LjkArabicText.php`
(modified, 57 baris perubahan diklaim tapi di worktree masih berbeda dari HEAD) dan
`StudentCardController.php`. Keduanya perubahan aktif proses lain — **tidak saya sentuh**.

## 2. `resources/views/ljk/arab/soal-pdf.blade.php`

| Pertanyaan | Jawaban (bukti) |
| --- | --- |
| Masih ada di HEAD? | **Ya** — `git cat-file -e HEAD:resources/views/ljk/arab/soal-pdf.blade.php` sukses |
| Ada di worktree? | **Ya**, 431 baris, `git status` **bersih** untuk path itu *pada 10:00*; pada 10:05 berubah (lihat catatan di bawah) |
| Sama dengan HEAD? | **Ya** — `git hash-object` Worktree `8140f405…` == `HEAD:` `8140f405…` |
| Ditambahkan kapan | `eac34abe` (09:43), `A` (added), 431 baris |
| Diubah kapan | `2aad775c` (09:51), 4± baris |
| Pernah dihapus? | **Tidak** — `git log --diff-filter=D` untuk path ini kosong |
| Sengaja dihapus? | Tidak ada penghapusan sama sekali; mtime `09:49:39` = saat proses lain menulis, bukan penghapusan |

**Mengapa 9.7 melihatnya "hilang":** laporan saya menyimpulkan berkas ada lalu "tidak ada" hanya dari
`git status` yang berubah-ubah selama proses lain bekerja. `git status` hanya menampilkan berkas
**terkomit** yang berbeda dari HEAD atau berkas untracked; saat berkas itu di-commit `eac34abe`
(09:43), ia otomatis **berhenti muncul** di `git status` — bukan hilang. Saya keliru menyimpulkannya
tanpa memeriksa `git cat-file`/`git log`. Tidak ada berkas yang dipulihkan atau dihapus sepanjang itu.

**Referensi aktif (dipakai fitur):**
- `app/Services/Evaluasi/SoalNaskahPdfService.php:45` → `'ljk.arab.soal-pdf'`
- `app/Services/Evaluasi/PaketSoalNaskahPdfService.php:131` → `'ljk.arab.soal-pdf'`
- `resources/views/ljk/arab/soal-pdf.blade.php:402,416` → `@include('ljk.arab.soal-opsi', …)`

**Risiko pada PDF LJK Arab:** **tidak ada saat ini** (berkas ada, ikut ter-commit, dan referenced).
Risiko yang tersisa hanyalah bila suatu commit berikutnya (bukan saya) menimpa pemilihan view.

**Rekomendasi:** tidak ada tindakan pemulihan. Bila proses lain meminta konfirmasi, cukup
tunjukkan `git hash-object` == `HEAD:` di atas.

> **Perubahan selama laporan ditulis.** Pada pemeriksaan terakhir (10:05) berkas ini **kembali
> muncul sebagai ` M`** — `git hash-object` = `6de61fd0…` sedangkan HEAD = `8140f405…` (18 baris,
> `soal-opsi.blade.php` 11 baris). Itu berarti **proses lain sedang mengedit view Arab saat ini**,
> bukan penghapusan. Saya tidak menyentuhnya. Kesimpulan §2 tetap: berkas ada, ter-commit, dipakai,
> dan kini sedang diedit oleh pemiliknya.

## 3. Status deployment produksi

### 3.1 Apa yang terbukti

| Item | Hasil | Bukti |
| --- | --- | --- |
| Host | `alim.abuhurairah.id` di belakang `server: hcdn` (Hostinger CDN) | header HTTP |
| `/health` | **HTTP 200**, `{"status":"ok","time":…,"app":"ALIM","env":"production","request_id":…}` | `curl -m15` |
| `/login` | **HTTP 200** | `curl -m15` |
| `/up` | 404 (memang tidak ada) | `curl` |
| Webhook deploy | **ada** di `routes/web.php` produksi (baris 9 dari `tail`); POST tanpa signature → **403 dari CDN (hcdn)**, bukan 401 aplikasi | header `x-hcdn-request-id` |
| Artefak staging | `.env.staging` tidak ada, `database/staging.sqlite` **tidak ada**, `storage/logs/staging.log` tidak ada; via HTTPS: 403 / 404 / 404 | `ls` + `curl` |
| Sumber aplikasi | `.env` produksi `0600`, di luar `public_html`/`public` | `ls -ld` |
| PHP | 8.4.28 (`/opt/alt/php84`), `display_errors=Off` | `php -r`, `php -i` |
| Ekstensi | `pdo_sqlite sqlite3 mbstring openssl json ctype fileinfo tokenizer xml curl zip dom intl gd` — **semua ada** | `php -r extension_loaded` |
| Permission | aplikasi `755` milik `u625880896`, `bootstrap/cache` dan `storage` `775` | `ls -ld` |

### 3.2 Apa yang **tidak dapat diverifikasi**

| Item | Alasan |
| --- | --- |
| Commit produksi | **tidak diketahui.** `~/alim-app` adalah clone terpisah di branch `master`, `HEAD = c6675692` (5 Nov), `origin/main` lokalnya **juga** `c6675692` (fetch tidak pernah jalan), dan `git rev-list --left-right --count` menghasilkan `0 0` terhadap keduanya — jadi perbandingan ke GitHub tidak bermakna. |
| Isi yang benar-benar berjalan | **tidak cocok dengan pola deploy yang didokumentasikan.** Status kerja produksi: 6626 `M`, 9 `D`, 186 `??`, dengan diff besar (mis. `JadwalGeneratorService.php` +1444). `routes/web.php` kerja 4427 baris vs HEAD 4323, dan memuat `cards.preview`/`cards.png` — fitur yang baru ada di `origin/main` sejak 08:14 (commit `fa95deb1`). Artinya berkas di produksi **lebih baru** dari commit `c6675692`, dan masuk lewat jalur selain "git archive dari commit" (rsync dari working tree, atau commit lokal di server). |
| Log deploy | `/tmp/alim-auto-deploy.log` **tidak ada** (hilang saat reboot/housekeeping). |

**Kesimpulan §3:** produksi menjalankan versi **lebih lama dari `cbc6ddf9`** untuk berkas Connect
(`/health` masih mengembalikan `env`, webhook belum dijaga), tetapi kode fitur naskah/kartu tampak
sama atau lebih baru dari `origin/main`. Ini **tidak konsisten** dengan satu commit mana pun, sehingga
"commit produksi" harus dinyatakan **belum diketahui**.

Risiko turunan: **deploy berikutnya dari `origin/main`** (`2aad775c`) akan **menimpa** worktree
produksi dengan isi commit (ribuan berkas kembali versi commit). Ini bukan tindakan saya, tetapi
perlu keputusan sebelum deploy apa pun dijalankan.

## 4. Konfigurasi VPS (`nginx-alim-staging.conf`)

Pemisahan **terbukti vs asumsi**:

| Aspek | Status | Keterangan |
| --- | --- | --- |
| `php8.4-fpm.sock` | **asumsi** | Ubuntu 24.04 `apt` memasang `php8.4-fpm` → `/run/php/php8.4-fpm.sock`. Socket produksi tidak dapat dibaca (shared hosting) — tidak dijadikan acuan |
| PHP ≥ 8.3 | **terbukti relevan** | `composer.json` butuh `^8.3`; produksi memakai 8.4.28 |
| Ekstensi wajib | **terbukti** untuk produksi (semua hadir di 8.4.28); di VPS tetap asumsi sampai dicek | daftar skrip sama dengan yang terpasang di produksi |
| Root `/srv/alim-staging/public` | **asumsi** | ditetapkan instalasi; `install/provision` menolak jalur terlalu umum |
| Permission | **asumsi** | produksi `755` untuk aplikasi, `775` untuk `storage`/`bootstrap/cache`; vhost menulis "milik www-data" — perlu diselaraskan dengan user php-fpm VPS |
| FastCGI | **asumsi** | `snippets/fastcgi-php.conf` + `$realpath_root`; satu-satunya bagian yang wajib disesuaikan bila socket beda |
| `.env` | **terbukti cukup** | `.env` berada di **luar** `root` (`/srv/alim-staging/.env`), jadi tidak bisa diakses lewat URL; ditambah `location ~ /\.(?!well-known) { deny all; }` |
| Pemisahan domain/docroot | **terbukti cukup** | `server_name` tunggal; docroot = `public` aplikasi, bukan docroot produksi; kedua skrip menolak jalur produksi |
| `client_max_body_size 20m` | **asumsi** | produksi `upload_max_filesize=1536M`; untuk endpoint Connect tidak relevan, untuk unggah soal bisa jadi batas |
| `try_files` tanpa `location = /index.php` | **temuan** | pada vhost ini tidak terjadi loop: `try_files … /index.php` adalah *internal redirect* (bukan 301) dan `.php$` menangani `/index.php`. Aman, tetapi lebih lazim ditulis eksplisit |

**Catatan keamanan (sama seperti 9.6 §6):** aturan "IP di luar allowlist → 403" boleh diuji hanya
bila nginx **tidak** menyalin `X-Forwarded-For` klien. Vhost saat ini memenuhi syarat itu
(baris 37–40 memberi komentar eksplisit dan tidak memakai `$proxy_add_x_forwarded_for`).
Tidak berubah dari 9.6; bukan temuan baru.

## 5. Risiko dan tindakan yang disarankan

| # | Risiko | Tingkat | Saran (butuh persetujuan Anda) |
| --- | --- | --- | --- |
| R1 | Perubahan 9.6/9.7 belum ada di riwayat: hilang bila worktree bersih/rusak | **tinggi** | Commit tersendiri `connect(staging)`: 5 modifikasi + 7 berkas baru, **tanpa** menyertakan berkas proses lain |
| R2 | Deploy dari `origin/main` akan menimpa 6821 berkas kerja produksi | **tinggi** | Bekukan deploy sampai pemilik memutuskan; jangan reset/revert |
| R3 | Commit produksi tidak diketahui | sedang | Dokumentasikan jalur deploy yang dipakai (rsync vs commit) agar dapat direkonsiliasi |
| R4 | `/health` produksi masih membocorkan `env` | rendah | Terbawa saat R1 ter-merge dan dideploy |
| R5 | Socket php-fpm VPS belum pasti | rendah | Verifikasi `ls /run/php/*.sock` sebagai langkah pertama setelah VPS siap |
| R6 | Webhook deploy dari luar ditolak CDN (403) | rendah | Perlu dikonfirmasi apakah signature GitHub masuk sebagai `role: deployment`; tidak memblokir staging |
| R7 | Koreksi 9.7 perlu dibaca pemilik | sedang | Laporan ini menggantikan bagian 1 dan 2 laporan 9.7 |

## 6. Keputusan

**Persiapan VPS staging boleh dilanjutkan**, dengan syarat (bukan penghalang):

1. R1 diselesaikan lebih dulu — seluruh perubahan 9.6/9.7 di-commit atas persetujuan Anda, agar
   VPS dibangun dari commit yang dapat diaudit, bukan dari worktree.
2. R2 diputuskan pemilik deploy; sampai itu, jangan menjalankan deploy produksi dari `origin/main`.
3. Nomor socket php-fpm diverifikasi di VPS (R5) sebelum `--install`.

**Status file `soal-pdf.blade.php`:** ada, ter-commit, dipakai fitur, tidak ada risiko.
**Status deployment produksi:** menjalankan `/health`/webhook versi lama; commit sumbernya
**belum diketahui** karena worktree tidak sesuai satu commit mana pun.
