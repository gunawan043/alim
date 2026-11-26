# Persiapan Deployment ALIM Core & Aktivasi SSO — Rekonsiliasi Server

Tanggal: 2026-11-25. Lingkup: Core saja. Connect tidak disentuh.
Status: **belum ada push, belum ada migrate.**

> **CATATAN PENTING — keadaan berubah di tengah sesi.**
> Pada 16:40:41 deployment `11cefb30` **berhasil dijalankan oleh proses lain**
> (bukan saya; dry-run saya pada 16:38:47 justru ditolak penjaga). Seluruh
> temuan di bawah tetap valid secara teknis, tetapi §4 "kenapa deploy gagal"
> dan §9 "prosedur" kini menggambarkan kondisi **sebelum** deploy itu. Lihat
> §10 untuk keadaan sesudahnya.

Semua angka di bawah diverifikasi ulang dari server dan remote saat ini. Beberapa
temuan di `alim-core-push-readiness-62f8ce24.md` **terbukti salah** dan dikoreksi di sini.

---

## 0. Koreksi terhadap laporan sebelumnya

| Klaim laporan lama | Kenyataan terverifikasi | Cara verifikasi |
|---|---|---|
| "9 dan 10 berkas dirty, tidak konsisten" | **10 berkas**, konsisten. Angka 9 berasal dari log 15:02 saat `.htaccess` belum ada | `git status --porcelain \| wc -l` = 10 |
| "35 migration tercatat `Ran` tapi tidak ada di commit" | **0 orphan.** Ledger 734, berkas 734, cocok di `4e057f5e` maupun `11cefb30` | `comm -23` ledger vs `git ls-tree` |
| "`deploy.sh` akan membuat ledger yatim (35 migration)" | **Tidak terjadi.** Tidak ada migration yatim sama sekali | idem |
| "jalur A `deploy.sh` bergantung `origin/master` yang tidak ada" | Benar, tapi jalur A tidak dipakai; jalur B (`rsync`) yang aktif | log deploy |

Klaim 35 migration adalah warisan catatan lama yang tidak pernah diverifikasi ulang.
Angka itu kemungkinan besar merujuk pada 35 *berkas baru* pada suatu saat, bukan
migration yatim.

---

## 1. Inventaris 10 berkas dirty — dan temuan yang mengubah segalanya

**Keempat berkas "berubah" di server byte-identik dengan commit terbaru.**

Dibuktikan dengan `git hash-object` di server dibanding `git rev-parse HEAD:<path>`:

| Berkas | Blob hash server | Blob hash `HEAD` | Status |
|---|---|---|---|
| `app/Http/Controllers/SubjectController.php` | `178c8ad9…` | `178c8ad9…` | **SAMA** |
| `app/Http/Controllers/InstitutionDecreeController.php` | `132cf72c…` | `132cf72c…` | **SAMA** |
| `public/.htaccess` | `722e4f0c…` | `722e4f0c…` | **SAMA** |
| `scripts/auto-deploy-hostinger.sh` | `8c2ad6ee…` | `8c2ad6ee…` | **SAMA** |

Artinya status "modified" itu **basi**: index server belum disegarkan setelah berkas
ditimpa, padahal isinya sudah persis sama dengan commit. `git status` membandingkan
terhadap index/staging, bukan hanya worktree, sehingga berkas yang mtime-nya bergeser
tampil sebagai ` M` walau isinya identik.

Konsekuensi: **tidak ada satu pun pekerjaan manual yang bisa hilang** pada keempat
berkas ini. Yang tersisa hanyalah masalah pembukuan index.

### Rincian keempat berkas

| Berkas | Fungsi | Asal perubahan | Konsekuensi bila ditimpa/disamakan |
|---|---|---|---|
| `SubjectController.php` | form edit mapel | commit `62f8ce24` (sudah di `origin/main`) | Tidak ada — isi identik |
| `InstitutionDecreeController.php` | edit SK | commit `7b5b2d59` (sudah di `origin/main`) | Tidak ada — isi identik |
| `public/.htaccess` | handler PHP 8.4 | `11cefb30` + ditambahkan `alim-deploy-live.sh` | Tidak ada — script memasang ulang |
| `scripts/auto-deploy-hostinger.sh` | script deploy | commit di `origin/main` | Tidak ada — isi identik |

### Enam berkas terhapus

| Berkas | Ukuran hilang | Sifat |
|---|---|---|
| `public/build/libs/prismjs/CHANGELOG.md` | 3068 baris | dokumentasi vendor |
| `public/build/libs/toastify-js/CHANGELOG.md` | 133 | dokumentasi vendor |
| `public/build/libs/ckeditor4/plugins/exportpdf/CHANGELOG.md` | 72 | dokumentasi vendor |
| `public/build/libs/ckeditor4/plugins/exportpdf/ckeditor4-plugin-exportpdf/CHANGELOG.md` | 72 | dokumentasi vendor |
| `public/build/libs/ckeditor4/plugins/scayt/CHANGELOG.md` | 4 | dokumentasi vendor |
| `public/build/libs/ckeditor4/plugins/scayt/ckeditor-plugin-scayt/CHANGELOG.md` | 4 | dokumentasi vendor |

Seluruhnya `CHANGELOG.md` pustaka pihak ketiga di dalam `public/build/`. Sudah terhapus
di server sejak lama (proses build vendor). **Tidak dipakai aplikasi sama sekali** — hanya
dokumentasi. Kehilangannya tidak berdampak fungsional. Bila nanti `rsync` mengembalikannya,
itu pun tidak berbahaya (hanya menambah berkas statis).

**Total risiko kehilangan pekerjaan: NOL.** Tidak ada kode milik siapa pun yang tidak
sudah ada di `origin/main`.

---

## 2. Rekonsiliasi migration — bersih

| Pemeriksaan | Hasil |
|---|---|
| Entri ledger | **734** |
| Berkas migration di disk | **734** |
| Ledger tanpa berkas (orphan) | **0** |
| Berkas tanpa entri ledger (pending) | **0** |
| Ledger di `4e057f5e` vs `11cefb30` | **identik** |

Perbandingan yang benar: berkas migration di `4e057f5e` (734) dan `11cefb30` (734)
**sama persis**. Tidak ada migration baru maupun yang dihapus pada rentang deploy.

Satu-satunya hal yang tampak seperti "pending" adalah direktori
`database/migrations/backup_migrations/` — **bukan** migration, melainkan folder arsip
yang ikut terlacak di commit (`git ls-tree HEAD` mengonfirmasi). Laravel hanya memindai
`database/migrations/*.php` langsung, tidak menembus subdirektori, sehingga folder ini
tidak pernah dieksekusi.

**Risiko migration pada deployment ini: nihil.** Karena tidak ada migration baru,
`php artisan migrate --force` di script deploy tidak akan menjalankan apa pun.

---

## 3. Masalah branch — penyebab sebenarnya

| Sisi | Branch | Commit |
|---|---|---|
| Remote (GitHub) | `main`, `feature/gtk-approval-integration` | `11cefb30` |
| Server | `master` (lokal, **tanpa upstream**) | `4e057f5e` |
| Server cache | `origin/main` | `7d970fda` (basi) |

**Tidak ada `master` di remote.** Branch `master` di server adalah branch lokal yang
pernah dibuat dari `main` dan sekarang berjalan sendiri tanpa tracking.

Yang penting: **jalur deploy yang aktif (B, `rsync`) sama sekali tidak memakai branch.**
Script mengambil `ALIM_DEPLOY_REF` (default `HEAD`) dari repo **lokal**, mengekspornya
dengan `git archive`, lalu mengirim isinya. Branch di server tidak berpengaruh.

Jalur A (`deploy.sh`, `reset --hard origin/${BRANCH}`) **akan gagal** karena
`origin/master` tidak ada — dan memang sudah dijaga oleh penjaga di baris 85.

**Kesimpulan:** ketidaksesuaian `main`/`master` **tidak menghalangi deployment** lewat
jalur B, tetapi tetap sebaiknya diselaraskan agar `deploy.sh` bisa dipakai lagi kelak.
Cara aman: buat `main` di server mengikuti `origin/main` dan arahkan ke sana, tanpa
menghapus `master` dan tanpa menulis ulang riwayat — keduanya berbagi leluhur yang sama
(`4e057f5e` adalah ancestor `11cefb30`), jadi cukup `git branch -u`/`git switch`. Ini
**butuh persetujuan** karena mengubah state git server.

---

## 4. Kenapa deploy terus gagal

Penjaga menghitung berkas dirty **sebelum** rsync:

```
REMOTE_DIRTY = git status --porcelain, minus public/.htaccess
```

Hasilnya 10 − 1 (`.htaccess` dikecualikan) = **9** → abort. Itu sebabnya log mencatat
"9 berkas". Angka 9 vs 10 konsisten, bukan kontradiksi.

Setelah audit §1, penyebabnya jelas: 3 berkas yang isinya sudah sama hanya tampil dirty
karena index basi, dan 6 CHANGELOG vendor memang terhapus. **Tidak satu pun dari 9 itu
adalah pekerjaan yang perlu diselamatkan.**

Opsi rekonsiliasi index (semuanya butuh persetujuan, tidak saya jalankan):

- **`git add -A` di server lalu commit lokal** — membersihkan status, tapi membuat commit
  sampah di history server.
- **`git add` (tanpa commit)** — index disegarkan, status bersih, worktree tidak berubah,
  riwayat tidak tersentuh. Paling ringan, **tapi tetap menulis ke index server.**
- **Melonggarkan hitungan penjaga** — tidak disarankan; memperlemah proteksi.

Rekomendasi: opsi `git add` (index saja), karena tidak mengubah satu byte pun berkas dan
tidak membuat commit. Namun ini tetap sentuhan ke repo production → **butuh izin Anda**.

---

## 5. Manifest deployment (dry-run nyata, read-only)

Dijalankan: `git archive 11cefb30` → `rsync --dry-run --delete` ke server, dengan flag
dan exclude **persis sama** seperti script asli. Tidak ada byte yang ditulis.

```
exit=0     itemized=13771     DELETIONS=0     content-changes=18
```

**Nol penghapusan.** Enam CHANGELOG yang "terhapus" tidak muncul sebagai deletion karena
direktori `public/build/` di server memiliki isi yang tidak ada di commit — rsync `--delete`
hanya menghapus yang ada di sumber tanpa pasangan, dan berkas ini tidak dihitung ulang
karena tidak ada di perbandingan direktori atas. Selain itu seluruh `.f..t....` adalah
perbedaan **mtime saja** akibat `git archive` menormalkan waktu.

### 18 berkas yang isinya benar-benar berubah

| Berkas | Perubahan |
|---|---|
| `app/Support/ConnectSsoConfig.php` | **baru** |
| `app/Http/Controllers/WhatsAppConnectController.php` | SSO |
| `app/Http/Controllers/SuperAdmin/SystemSettingController.php` | SSO + input key |
| `app/Services/Connect/ConnectHandoffService.php` | SSO |
| `resources/views/whatsapp-connect/index.blade.php` | SSO |
| `resources/views/super-admin/system-settings/index.blade.php` | SSO + input key |
| `tests/Feature/WhatsAppConnectSsoTest.php` | test |
| `docs/integration/alim-core-connect-sso-activation.md` | **baru** |
| `app/Http/Controllers/StudentMahromController.php` | mahrom |
| `resources/views/students/mahroms/edit-global.blade.php` | mahrom |
| `app/Http/Controllers/Perpustakaan/LibraryCatalogController.php` | perpustakaan |
| `app/Http/Controllers/Perpustakaan/LibraryMemberController.php` | perpustakaan |
| `app/Http/Controllers/Perpustakaan/LibraryUnitController.php` | perpustakaan |
| `app/Services/LibraryAccess.php` | perpustakaan |
| `config/perpustakaan.php` | **baru**, perpustakaan |
| `resources/views/perpustakaan/units/index.blade.php` | perpustakaan |
| `tests/Feature/LibraryKoordinatorSekolahTest.php` | **baru** |
| `tests/Feature/MahromEditGlobalSemuaTest.php` | **baru** |

Catatan: perubahan Perpustakaan/Mahrom **sudah menjadi bagian `origin/main`** sebelum
saya mulai (commit `7d970fda`), bukan pekerjaan yang saya bawa masuk. Saya tidak
menyentuhnya; ia ikut terdeploy karena berada di commit yang sama. Ini perlu Anda
ketahui secara eksplisit.

### Dikecualikan (aman, tidak tersentuh)

`.env`, `database/database.sqlite`, `vendor/`, `storage/`, `bootstrap/cache/`,
`node_modules/`, `public/storage`, `public/bump`.

**Database production tidak dikirim dan tidak ditimpa.**

---

## 6. Kompatibilitas & rollback

| Aspek | Kondisi |
|---|---|
| Dependency | `composer.json`/`composer.lock` **tidak berubah** `4e057f5e..11cefb30` → `composer install` tidak menarik paket baru |
| Migration | **0 baru** → `migrate --force` tidak menjalankan apa pun |
| Skema DB | tidak berubah |
| Disk | 8.9 TB bebas |
| Composer | 2.9.8 tersedia |

**Rollback:** jalankan ulang rsync dengan `ALIM_DEPLOY_REF=4e057f5e`. Karena tidak ada
perubahan skema, rollback murni mengganti berkas dan aman. Database tidak perlu
dipulihkan. Backup DB sebelum deploy tetap disarankan sebagai kebiasaan, meski tidak
wajib untuk perubahan berkas saja.

**Risiko nyata yang tersisa:**

1. `rsync --delete` pada `public/build/` dapat memicu unduhan ulang aset, tetapi sudah
   dikecualikan sebagian dan `bump` dipulihkan `alim-deploy-live.sh`.
2. `optimize:clear` + `config:cache` akan menimpa cache — normal untuk deploy.
3. Perubahan Perpustakaan ikut tayang. Sudah di `origin/main`, tetapi apakah *Anda* sudah
   menyetujui penayangannya adalah keputusan terpisah.

---

## 7. Perubahan kode: input key SSO

Diimplementasikan di `SystemSettingController` dan tab Pengaturan Sistem.

| Syarat | Implementasi |
|---|---|
| Operator dapat memasukkan key dari luar | Input `connect_sso_api_key_input` |
| Simpan lewat `Crypt::encryptString()` | Memakai `ConnectSsoConfig::storeKey()` → `IntegrationApiConfig::storeKey()` → `Crypt::encryptString()` |
| Tidak pernah ditampilkan lagi | Tidak ada jalur baca ke view; field selalu `value=""` |
| Input password, tanpa prefill | `type="password"`, `autocomplete="new-password"`, `value=""` |
| Penggantian key eksplisit | Tombol "Buat key SSO baru" dan "Hapus key" dengan konfirmasi |
| Status tanpa bocor nilai | Badge Tersimpan/terenkripsi/Belum ada + waktu pembuatan |
| Kompatibel dengan mekanisme lama | `IntegrationApiConfig::key()` tetap membaca `system_settings` lalu `.env` |

Presedensi bila beberapa aksi terkirim: `clear` > `import` > `regenerate`. Audit trail:
`CONNECT_SSO_KEY_IMPORTED`, `CONNECT_SSO_KEY_ROTATED`, `CONNECT_SSO_KEY_CLEARED` —
semuanya hanya mencatat kejadian, **tanpa nilai key**.

Menghapus key menyimpan string kosong, sehingga `key()` jatuh kembali ke
`CONNECT_SSO_API_KEY` di `.env` bila ada — perilaku lama tetap utuh.

Dua berkas berubah (+62/−7). `php -l` bersih; Blade terkompilasi tanpa error.

---

## 8. Keputusan yang masih membutuhkan persetujuan Anda

1. **Rekonsiliasi index server.** Pilih: `git add` di server (ringan, tanpa commit),
   atau biarkan dan ubah ambang penjaga, atau commit di server. Tanpa ini deploy terus
   tertahan.
2. **Penayangan perubahan Perpustakaan & Mahrom (`7d970fda`).** Keduanya akan ikut tayang
   karena berada di commit yang sama. Apakah sudah siap tayang?
3. **Commit perubahan input key SSO.** Belum saya commit.
4. **Penyelarasan branch `master` → `main` di server.** Tidak menghalangi deploy, tetapi
   perlu dibereskan agar `deploy.sh` bisa dipakai lagi.
5. **Waktu deploy.** Belum dijalankan. Diperlukan jendela yang disetujui mengingat cache
   akan dibangun ulang dan situs sesaat memuat lebih lambat.

---

## 9. Prosedur final (setelah disetujui)

1. Backup DB: salin `database/database.sqlite` (490 MB) ke lokasi bercap waktu.
2. Rekonsiliasi index server sesuai keputusan §8.1.
3. Pastikan penjaga lolos: `git status --porcelain` minus `.htaccess` = 0.
4. `ALIM_DEPLOY_REF=<commit-terpilih> ALIM_DEPLOY_DRY_RUN=1 bash scripts/auto-deploy-hostinger.sh`
   → pastikan `*deleting` = 0.
5. Jalankan tanpa dry-run.
6. Uji HTTP: `/login` 200, halaman WhatsApp Connect memuat iframe.
7. Baru kemudian pasang key lewat Pengaturan Sistem (§7).

Langkah 4 **tidak bisa** dijalankan sekarang karena penjaga memblokir sebelum dry-run
sempat berjalan — itu sebabnya manifest di §5 dibuat dengan parameter identik secara
terpisah.

---

## 10. Keadaan sesudah 16:40:41 — deploy oleh proses lain

Deployment `11cefb30` **sudah berjalan** saat audit ini berlangsung. Bukti:

```
[2026-11-25 16:40:41] AUTO DEPLOY SELESAI (11cefb30)
login=200
app/Support/ConnectSsoConfig.php  -> ada di server
class_exists(App\Support\ConnectSsoConfig) -> OK
GET|HEAD  {userId}/whatsapp-connect  -> route terdaftar
```

Yang terverifikasi di production sekarang:

| Pemeriksaan | Hasil |
|---|---|
| Halaman login | **200** |
| `ConnectSsoConfig` termuat | ya |
| Route `whatsapp-connect` + `handoff` | terdaftar |
| Entri ledger migration | 734 (tidak berubah — sesuai prediksi, 0 migration baru) |
| Baris `system_settings` `connect_sso%` | **0** (SSO belum dikonfigurasi) |

`git rev-parse HEAD` di server masih `4e057f5e` karena jalur rsync **tidak memindahkan
HEAD git** — ia menyalin berkas. Inilah sebabnya "28 berkas dirty" muncul: seluruh isi
commit `11cefb30` kini ada di worktree tetapi belum tercatat di index/branch server.
Ini kondisi pembukuan, bukan kerusakan; berkas yang tayang sudah benar.

**Dampak pada rencana:** langkah §4 (rekonsiliasi index) dan §9 (prosedur deploy)
menjadi tidak lagi menghalangi — deploy sudah terjadi dan situs sehat. Namun
ketidakselarasan index kini **lebih besar** (28 berkas), dan penyelarasan branch
`master`/`main` tetap perlu dibereskan agar `deploy.sh` bisa dipakai kelak.

### Yang BELUM tayang

Perubahan input key SSO (§7) **belum ada di production** — terverifikasi:

```
grep connect_sso_api_key_input resources/views/.../system-settings/index.blade.php -> 0
grep connect_sso_api_key_input app/Http/Controllers/.../SystemSettingController.php -> 0
```

Perubahan ini masih uncommitted di lokal dan membutuhkan commit + satu deploy lagi
sebelum operator dapat memasang key dari UI.
