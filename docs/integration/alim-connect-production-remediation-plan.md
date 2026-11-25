# Rencana Remediasi — Blocker ALIM Connect di Sisi Core Production

**Tanggal:** 2026-11-25 · **Siklus:** 12 (lanjutan) · **Sifat:** rencana saja, **belum dieksekusi**
**Batasan yang dipatuhi:** tidak ada perubahan konfigurasi/data production, tidak ada pemetaan aktor,
tidak ada migrasi, tidak ada kode SSO diterbitkan, tidak ada push/merge/deploy.
**Tidak ada credential, token, atau API key di dokumen ini.**

> **Catatan sumber:** `docs/integration/alim-connect-production-readiness.md` **tidak ada** di repo.
> Temuan yang dirujuk berasal dari pemeriksaan readiness read-only di sesi sebelumnya. Seluruh angka di
> bawah adalah hasil pemeriksaan ulang hari ini, bukan kutipan tanpa verifikasi.

---

## Ringkasan satu halaman

| # | Prioritas | Status blocker | Temuan baru yang mengubah rencana |
|---|---|---|---|
| 1 | Konfigurasi `CONNECT_SSO`/`CONNECT_APPROVAL` | **Terblokir, bukan karena Core** | Harus menunggu deploy Connect (lihat #6) |
| 2 | IP egress Connect | **Belum bisa ditetapkan** | Host Connect ada (`alim-wa.abuhurairah.id` → `91.108.119.178`), tetapi egress-nya **tidak terverifikasi**; IP yang terukur di mesin ini milik ISP rumahan |
| 3 | Permission `whatsapp_connect_view` | **Ada prosedurnya** | Seeder permission sudah memuatnya; `PermissionSeeder` cukup dijalankan |
| 4 | Prosedur pemetaan aktor | **Ada celah** | Nomor aktor produksi harus dipetakan lewat tinker/insert; tidak ada UI |
| 5 | Migration & schema | **Siap** | 4 migration `Ran`, 3 tabel lengkap, 0 pending |
| 6 | 6.830 berkas di luar commit | **Risiko tertinggi** | `deploy.sh` memakai `git reset --hard origin/main` → **menghapus seluruh 6.830 perubahan** |

**Kesimpulan:** blocker yang paling mendesak **bukan** konfigurasi Core, melainkan deployment Connect
yang tertinggal build (21 Nov) dan `deploy.sh` yang akan menghancurkan pekerjaan lokal production.

---

## Prioritas 6 dulu — karena ia mengubah semua prioritas lain

Urutan penulisan mengikuti riset, bukan prioritas. Saya letakkan #6 di depan karena temuannya
membatalkan asumsi yang dipakai #1 dan #2.

### 6.1 Asal 6.830 berkas

Dari `git status --porcelain` di `~/alim-app` production:

| Kategori | Jumlah | Sifat |
|---|---|---|
| `public/build/**` modified | **6.209** | **Hasil build Vite**, bukan tulisan tangan |
| Sumber lain modified | **420** | Perubahan kode nyata |
| Untracked (`??`) | **192** | Fitur/berkas baru |
| Deleted (`D`) | **9** | 4 di antaranya `CHANGELOG.md` vendor ckeditor/prismjs |

Rincian 420 sumber: `resources/views` 217, `app/Http` 83, `app/Services` 33, `tests/Feature` 26,
`app/Models` 21, sisanya tersebar.

### 6.2 Akar masalah — dibuktikan, bukan diduga

```
deploy.sh:15   BRANCH="main"
deploy.sh:55   git fetch origin "${BRANCH}"
deploy.sh:56   git reset --hard "origin/${BRANCH}"      ← menghapus SEMUA perubahan lokal
deploy.sh:79   php artisan migrate --force
deploy.sh:89   (npm tidak tersedia di server, skip — pastikan public/build/ sudah di-commit)
```

Branch git lokal production adalah **`master`** pada `c6675692` (2026-11-05), sedangkan `deploy.sh`
dan `DEPLOY_BRANCH=main` melakukan `reset --hard origin/main`. `origin/master` **tidak ada**
(`fatal: Needed a single revision`). `.git/hooks/` **kosong** — tidak ada `pre-push`, padahal sesi-sesi
awal menyebut hook auto-deploy.

**Kesimpulan asal:** perubahan masuk ke production **tanpa melalui commit** — lewat unggahan berkas
atau eksekusi di server, lalu `public/build` dihasilkan dari `npm run build` yang hanya terjadi di mesin
lain. Ini menjelaskan 6.209 berkas build dan 420 sumber yang tidak terikat commit.

### 6.3 Pemilik

Tidak ada jejak kepemilikan per-berkas: tidak ada commit, tidak ada hook, tidak ada log deploy
(`/tmp/alim-auto-deploy.log` tidak ada). Pemilik **harus dikonfirmasi manusia**. Yang bisa saya
pastikan hanya bahwa berkas integrasi Connect **7/7 identik** dengan `origin/main` (hash sama), jadi
bagian integrasi bukan sumber penyimpangan.

### 6.4 Risiko deployment berikutnya

**R1 — Kehilangan pekerjaan (kritis).** Satu kali `deploy.sh` berjalan → `reset --hard` menghapus 420
perubahan sumber + 192 berkas baru. Beberapa yang hilang bernilai nyata:
`app/Console/Commands/ConnectSecret.php`, `ConnectStagingHandoff.php`, `ConnectStagingVerify.php`,
`RolePermissionProvider.php`, delapan migration bertanggal 2026-11-03…11-20.

**R2 — Migrasi hilang, tabel yatim.** `migrate --force` jalan setelah reset. Migration yang dihapus
sebelum sempat di-commit tetapi **sudah dijalankan** akan meninggalkan tabel yang ada di
`migrations` ledger tanpa berkas sumbernya. Ledger production tercatat sampai `[46]`; delapan migration
untracked di atas belum tentu termasuk.

**R3 — Integrasi Connect berhenti bekerja.** `ConnectStagingHandoff`/`ConnectSecret` hilang → jalur
handoff dan penyimpanan secret staging lenyap dari production.

**R4 — `.gitignore` production dimodifikasi** (`M .gitignore`). Aturan ignore bisa berbeda dari
`origin/main`; setelah reset, berkas yang sebelumnya diabaikan bisa tiba-tiba dilacak atau sebaliknya.

**R5 — Build tidak dapat direproduksi di server.** deploy.sh mencetak "npm tidak tersedia di server,
skip — pastikan `public/build/` sudah di-commit", tetapi `public/build` **tidak** di-commit. Artinya
production berjalan dengan aset yang tidak bisa dibangun ulang dari commit mana pun.

### 6.5 Rencana remediasi #6 (bertahap, tanpa menghapus apa pun)

| Langkah | Tindakan | Risiko | Persetujuan |
|---|---|---|---|
| 6-A | **Bekukan** `deploy.sh` dari eksekusi (`mv` ke `deploy.sh.disabled` atau hapus dari crontab) sampai ada keputusan pemilik | Nol — mengurangi risiko | Pemilik deployment |
| 6-B | **Ambil salinan penuh** `~/alim-app` (rsync/tar ke direktori di luar repo), verifikasi dengan `sha256sum -c` | Nol | Pemilik deployment |
| 6-C | Klasifikasi 420+192 berkas per fitur; tentukan mana yang layak masuk branch sendiri | Nol (baca-saja) | Saya + pemilik |
| 6-D | Untuk tiap kelompok: buat commit di **branch baru** dari `c6675692`, lalu buka PR ke `main` | Rendah | Manusia |
| 6-E | Baru setelah penggabungan, jalankan deploy yang sudah diverifikasi | Sedang | Manusia |

**Yang TIDAK dilakukan:** `git stash`, `git reset`, `git checkout --`, atau penghapusan berkas apa pun.
Ketentuan Anda melarangnya, dan R1 menunjukkan itu akan destruktif.

---

## Prioritas 1 — Rancangan konfigurasi `CONNECT_SSO` dan `CONNECT_APPROVAL`

### 1.1 Prasyarat yang ditemukan

`.env` production **tidak memuat satu pun `CONNECT_*`**. Variabel yang sama juga **tidak
terdokumentasi** di `.env.example` maupun `.env.production.example` — jadi deploy baru akan
kehilangan konfigurasi ini tanpa peringatan.

### 1.2 Rancangan nilai (nama variabel + bentuk, bukan nilai)

Disimpan di `.env` production, **bukan** di dokumen ini:

```
CONNECT_SSO_API_KEY=<32-byte acak base64url, diterbitkan khusus production>
CONNECT_SSO_HEADER=X-ALIM-CONNECT-SSO-KEY
CONNECT_SSO_SCOPES=sso:exchange
CONNECT_SSO_ALLOWED_IPS=<CIDR egress Connect — menunggu #2>
CONNECT_SSO_REQUIRE_HTTPS=true
CONNECT_SSO_HANDOFF_URL=https://alim-wa.abuhurairah.id/api/sso/handoff
CONNECT_SSO_HANDOFF_TTL=120
CONNECT_SSO_AUTH_FAIL_LIMIT=10

CONNECT_APPROVAL_API_KEY=<32-byte acak base64url, BERBEDA dari SSO>
CONNECT_APPROVAL_HEADER=X-ALIM-CONNECT-APPROVAL-KEY
CONNECT_APPROVAL_SCOPES=approval:command
CONNECT_APPROVAL_ALLOWED_IPS=<CIDR egress Connect — menunggu #2>
CONNECT_APPROVAL_REQUIRE_HTTPS=true
CONNECT_APPROVAL_MAX_DURATION_DAYS=30
CONNECT_APPROVAL_MESSAGE_TEXT_MAX_LENGTH=500
CONNECT_APPROVAL_AUTH_FAIL_LIMIT=10
```

Alasan tiap pilihan non-obvious:

- **`HANDOFF_TTL=120`, bukan 60.** 60 detik terlalu sempit untuk penggunaan manusia sungguhan
  (memuat UI, jaringan). 120 masih pendek untuk kode sekali pakai.
- **`MAX_DURATION_DAYS=30`, bukan 365.** Di staging 365 wajar untuk uji; di production izin pulang
  setahun adalah celah kebijakan.
- **Dua key terpisah.** `VerifyConnectSsoApiKey` dan `VerifyConnectApprovalApiKey` adalah kelas
  berbeda dengan scope berbeda; menyamakan nilainya membuat satu kebocoran membuka keduanya.
- **`ALLOWED_IPS` wajib terisi.** Dikonfirmasi di `VerifyIntegrationApiKey.php:83`:
  `if ($allowedIps !== [] && ! IpUtils::checkIp(...))`. **Allowlist kosong = tanpa pembatasan IP.**
  Ini risiko keamanan yang harus ditutup sebelum production, bukan sesudah.

### 1.3 Prosedur penerapan (setelah semua prasyarat jelas)

1. Terbitkan dua key di mesin operator: `openssl rand -base64 32`.
2. Masukkan ke `.env` production lewat editor SSH — **jangan** lewat shell history, jangan `echo`.
3. `php artisan config:clear` **lalu** `php artisan config:cache`.
   Wajib: `bootstrap/cache/config.php` bertanggal 25 Nov 01:06 sementara `.env` 19 Nov — cache
   menyembunyikan perubahan `.env` bila tidak dibangun ulang.
4. Verifikasi tanpa mencetak nilai:
   `sso.api_key_set=yes`, `approval.api_key_set=yes`, `allowed_ips_count>0`.
5. Uji endpoint tanpa key → harus `401` (bukan lagi `503`).
6. Serahkan key ke operator Connect lewat jalur rahasia.

### 1.4 Tambahan dokumentasi

Tambahkan blok `CONNECT_*` di `.env.example` dan `.env.production.example` dengan nilai kosong
(komitmen dokumentasi, bukan perubahan runtime).

---

## Prioritas 2 — Sumber IP egress Connect

### 2.1 Yang terbukti

| Fakta | Bukti |
|---|---|
| Connect **sudah ter-deploy** di `alim-wa.abuhurairah.id` | HTTP/2 200, TLS valid, header CSP/COOP khas OpenWA |
| IP publik domain | `91.108.119.178` |
| Egress dari mesin operator ini | `125.164.41.241` — **IP ISP rumahan**, bukan server |

### 2.2 Yang **tidak** bisa ditetapkan

- **Egress server Connect tidak terverifikasi.** IP `91.108.119.178` adalah alamat **masuk**. Egress
  bisa berbeda (NAT, load balancer, beberapa host).
- **SSH ke `91.108.119.178` timeout** pada port 22 untuk semua user yang dicoba — saya tidak punya
  jalur untuk membacanya dari dalam.
- **Jangan memakai `125.164.41.241`.** Itu IP workstation, dinamis, dan tidak mewakili server.

### 2.3 Cara menetapkan (pilih satu, semuanya butuh akses pemilik Connect)

1. **Paling andal:** dari host Connect jalankan `curl -s https://api.ipify.org` (atau endpoint egress
   milik sekolah). Hasilnya CIDR `/32` yang tepat.
2. **Bila multi-host/NAT:** sediakan CIDR blok dari penyedia (`91.108.119.178` bukan blok tempat).
3. **Bila akan berubah-ubah:** sementara longgar, **jangan** kosongkan. Gunakan blok /24 yang
   pasti milik infrastruktur, lalu sempitkan setelah stabil.

### 2.4 Keputusan desain

| Pilihan | Konsekuensi |
|---|---|
| Kosongkan allowlist | **Ditolak.** Setara tanpa pembatasan; hanya dilindungi key |
| Isi `/32` egress tunggal | Terketat; gagal bila Connect di-scale ke host lain |
| Isi blok `/32` + IP cadangan | Seimbang untuk uji terbatas |

**Rekomendasi:** `/32` egress tunggal untuk pengujian terbatas, dicatat siapa pemiliknya, dan
direview sebelum produksi penuh.

---

## Prioritas 3 — Permission `whatsapp_connect_view`

### 3.1 Temuan

- Permission ini **sudah terdaftar** di `database/seeders/PermissionSeeder.php:24`
  (grup `Integrasi`), jadi tidak perlu menambah kode.
- Production saat ini: `permission_count=306`, `role_count=16`, tetapi
  `permissions` **tidak memuat** `whatsapp_connect_view` (pemeriksaan readiness: `perm_exists=no`).
- `ConnectHandoffService::userStillHasPermission()` menerima Super Admin/System Admin **atau**
  pemegang permission langsung **atau** pemilik via role **atau** domain aktif.
  Untuk penguji terbatas, kita inginkan **jalur eksplisit**: permission langsung, bukan Super Admin.

### 3.2 Prosedur

1. **Jalankan seeder** (idempoten — `PermissionSeeder` memakai `firstOrCreate`):
   `php artisan db:seed --class=PermissionSeeder --force`
2. **Verifikasi** permission ada:
   `DB::table('permissions')->where('name','whatsapp_connect_view')->exists()` → `true`
3. **Pilih akun penguji yang sah.** Kriteria wajib: `is_active=1`, personel sekolah nyata,
   **bukan** akun sistem, dan diketahui pemiliknya. Akun staging (`*@staging.invalid`) **dilarang**.
4. **Beri permission langsung**, bukan via role (lebih mudah dicabut, dan tercatat per akun):
   `$user->givePermissionTo('whatsapp_connect_view')`
5. **Catat** di audit: siapa memberi, akun mana, tanggal. `PermissionSeeder` memanggil
   `forgetCachedPermissions()` — pastikan cache Spatie bersih.
6. **Rencana pencabutan** sudah disiapkan sebelum diberikan (lihat 3.4).

### 3.3 Yang harus diputuskan Anda (bukan saya)

- **Akun mana** yang menjadi penguji. Saya tidak boleh mengarang atau menunjuk akun.
- **Apakah** permission ini diberikan sebagai permanen untuk operasional, atau sementara untuk uji.
  Ini menentukan apakah perlu tanggal pencabutan.

### 3.4 Pencabutan

`$user->revokePermissionTo('whatsapp_connect_view')` + `config:clear`.
`ConnectHandoffService` memeriksa **state DB segar** (bukan cache per-user), jadi pencabutan berlaku
segera — kode handoff lama pun langsung ditolak (`permission_revoked`).

---

## Prioritas 4 — Prosedur pemetaan identitas WhatsApp → `core_user_id`

### 4.1 Celah yang ditemukan

`alim:connect-staging-handoff` **menolak** berjalan di luar staging
(`ConnectStagingHandoff.php:34`). Seeder uji juga menolak production. Artinya untuk production
**tidak ada alat** — pemetaan harus lewat tinker atau UI, dan UI untuk memetakan aktor approval
**tidak ditemukan** di `routes/web.php`.

Jalur handoff production yang ada: `POST /{userId}/whatsapp-connect/handoff`
(`routes/web.php:3204`, middleware `permission:whatsapp_connect_view` + `throttle:10,1`). Ini
memadai untuk SSO, tetapi **bukan** untuk memetakan aktor approval.

### 4.2 Prosedur pemetaan (rencana, tidak dijalankan)

Prasyarat sebelum langkah mana pun:
- nomor WhatsApp penguji **disetujui Anda** (bukan nomor karangan, bukan nomor kerja orang lain tanpa izin)
- `core_user_id` penguji sudah ditentukan
- penguji adalah Kepala Asrama aktif pada asrama yang permitnya diuji
  (`DormitoryPolicyAccess::canApprovePermitCommand()` — hanya `dormitories.head_id` aktif atau
  `dormitory_staff_assignments` aktif berposisi `kepala_asrama`)

Langkah:
1. **Verifikasi kewenangan lebih dulu** (baca-saja):
   `app(DormitoryPolicyAccess::class)->canApprovePermitCommand($user, $dormitoryId)` → harus `true`.
   Tanpa ini, pemetaan hanya memindahkan kegagalan ke `403 actor_not_authorized`.
2. **Buat baris pemetaan** — `channel='whatsapp'`, `external_identity` = digit E.164 tanpa `+`,
   `is_active=true`, `verified_at=now()`, `verified_by=<id operator>`.
   `verified_at` **wajib terisi**: `resolveActor()` memfilter `whereNotNull('verified_at')`.
3. **Verifikasi** tiga hal dengan query yang sama seperti `resolveActor()`.
4. **Dokumentasikan** nomor + `core_user_id` + asrama di tiket internal (bukan rahasia menurut
   dokumen Connect §4, tetapi tetap catat pemiliknya).
5. Siapkan **penghapusan** (`is_active=false`) setelah uji selesai.

### 4.3 Alternatif yang lebih baik

Menambahkan artisan command production-safe (mis. `alim:connect-actor-map`) yang menolak
`APP_ENV=staging`, mencetak ringkasan tanpa nomor penuh, dan menulis audit. Ini **pekerjaan kode
baru** — perlu persetujuan Anda karena di luar "rencana", dan tidak boleh dicampur dengan deploy
yang belum stabil (#6).

---

## Prioritas 5 — Verifikasi migration & schema

### 5.1 Hasil (production, read-only)

```
2026_11_22_000001_create_connect_actor_identities_table ........... [43] Ran
2026_11_22_000002_create_approval_commands_table .................. [43] Ran
2026_11_22_000003_create_connect_sso_handoffs_table ............... [45] Ran
2026_11_23_000001_add_contract_headers_to_approval_commands_table . [46] Ran
Pending (seluruh project): 0
```

Isi kolom:

| Tabel | Hasil |
|---|---|
| `approval_commands` | **lengkap 14 kolom** (termasuk `idempotency_key`, `api_version`, `payload_hash`) |
| `connect_actor_identities` | **lengkap 6 kolom** |
| `connect_sso_handoffs` | **lengkap 7 kolom** |

### 5.2 Kesimpulan

**Schema production siap.** Tidak ada migration integrasi yang tertunda. Karena tidak ada yang perlu
dijalankan, ketentuan "jangan jalankan migrasi" tidak dilanggar dan tidak menghalangi apa pun.

### 5.3 Risiko yang tetap ada

Ledger production tercatat sampai `[46]`, sedangkan delapan migration **untracked** bertanggal
2026-11-03…11-20 ada di working copy. Bila salah satunya sudah dijalankan tanpa masuk ledger, atau
belum dijalankan tetapi berkasnya akan hilang pada `reset --hard` (#6, R2), statusnya ambigu.
**Jangan disentuh sampai #6-C selesai.**

---

## Temuan tambahan: Connect production tertinggal build

Ini **bukan** prioritas yang Anda minta, tetapi menghalangi #1 dan #2, jadi harus dilaporkan.

| Uji | Hasil |
|---|---|
| `GET /api/sessions` | **401** — rute ada (modul v1 berjalan) |
| `POST /api/approval/requests` | **404 `Cannot POST /api/approval/requests`** |
| `GET /api/sso/handoff` | **404** |
| Aset dashboard `Last-Modified` | **21 Nov 2026 10:30** |
| Tanggal commit modul approval | **22 Nov 2026 17:48** (`b3c4fa9f9`) |
| Tanggal commit modul SSO | **23 Nov 2026 11:38** (`12edf8f37`) |

**Kesimpulan:** instance production Connect menjalankan build **sehari sebelum** modul approval
di-commit. Kedua endpoint integrasi **belum ada** di sana — termasuk `/api/sso/handoff` yang menjadi
target `CONNECT_SSO_HANDOFF_URL` di #1.

**Konsekuensi:** mengisi konfigurasi Core sekarang akan mengarah ke endpoint yang belum ada. Core akan
mendapat `404` pada setiap exchange/ingest. **Core harus dikonfigurasi setelah Connect ter-deploy
ulang**, atau konfigurasi diisi lebih dulu (aman, karena fail-closed 503 berubah jadi 401/404) dengan
kesadaran bahwa uji belum bisa dimulai.

Catatan tambahan: database Connect (Supabase, PostgreSQL 17.12) **sudah punya** tabel
`approval_requests`, `approval_commands`, `whatsapp_identities`, `sessions`, tetapi `migrations`
ledger-nya **0 baris**. Artinya skema dibuat tanpa ledger — konsisten dengan pola "berkas tidak
terikat commit" yang juga terjadi di Core. Tidak ada yang saya ubah.

---

## Urutan eksekusi yang direkomendasikan

| Urutan | Langkah | Alasan |
|---|---|---|
| 0 | Konfirmasi pemilik 6.830 berkas + bekukan `deploy.sh` | Mencegah R1 sebelum langkah lain |
| 1 | Deploy ulang Connect dari `82f5a1ed3` (atau lebih baru) | Tanpa ini #1 dan #2 tidak bisa diuji |
| 2 | Tetapkan egress Connect dari host Connect | Prasyarat allowlist |
| 3 | Isi `CONNECT_*` Core + `config:cache` | Membuka endpoint (503 → 401) |
| 4 | Jalankan `PermissionSeeder`, beri permission ke akun pilihan Anda | Prasyarat SSO |
| 5 | Petakan aktor approval satu nomor | Prasyarat uji approval |
| 6 | Uji terbatas (approval dulu, SSO belakangan) | Sesuai keputusan siklus 12 |

Langkah 1 **tidak boleh** dijalankan sebelum langkah 0, karena `deploy.sh` memuat `reset --hard`.

---

## Hal yang masih menunggu keputusan Anda

1. **Pemilik 6.830 berkas** — siapa yang berhak menyatakan perubahan itu boleh di-commit, dan
   apakah ada perubahan yang sengaja tidak di-commit.
2. **Akun penguji** untuk `whatsapp_connect_view` (dan apakah pemberiannya permanen atau sementara).
3. **Nomor WhatsApp penguji** untuk pemetaan aktor, dan pengujian di asrama mana.
4. **Apakah anggaran** menyediakan host Connect terpisah untuk staging, agar uji tidak menyentuh
   production sejak awal.
5. **Apakah saya boleh** menulis command `alim:connect-actor-map` (pekerjaan kode, terpisah dari deploy).
