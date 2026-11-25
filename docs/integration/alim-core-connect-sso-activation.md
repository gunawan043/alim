# Core ↔ ALIM Connect — Aktivasi SSO WhatsApp Connect di Production

**Tanggal:** 2026-11-25 · **Repo Core:** `main` @ `62f8ce24` (belum di-commit) · **Siklus:** SSO Activation
**Status akhir:** **Core siap dan teruji; aktivasi production TERHALANG di sisi ALIM Connect.**

Tidak ada nilai rahasia di dokumen ini. Semua key hanya disebut namanya.

---

## 1. Kontrak SSO aktual (hasil audit dua sisi)

Dibaca langsung dari source kedua repo, bukan dari asumsi.

### 1.1 Alur

```
Browser (halaman Core, dalam iframe)
   │  1. POST {userId}/whatsapp-connect/handoff   (sesi Core, CSRF, throttle 10/menit)
   ▼
Core /api/... → ConnectHandoffService::issue()
   │     → menyimpan SHA-256 kode, TTL 60 dtk
   │  ← { code, audience:"connect", scope:"whatsapp.embed", connect_sso_url }
   │  2. form POST code → target iframe
   ▼
Connect  POST /sso/handoff   (browser; Origin harus persis origin Core)
   │  3. server-to-server:
   │     POST https://alim.abuhurairah.id/api/connect/v1/sso/exchange
   │     header X-ALIM-CONNECT-SSO-KEY + body {code, audience, scope, state?}
   ▼
Core redeem → { success, data:{ core_user_id, name, email, audience, scope, ... } }
   │  4. Connect memetakan core_user_id → akun Connect AKTIF (tidak auto-create)
   │  5. Connect menerbitkan sesi, token ditulis ke sessionStorage di halaman iframe
   ▼
Workspace Connect tampil di dalam iframe
```

### 1.2 Endpoint dan kredensial

| Sisi | Item | Nilai aktual (dari kode) |
|---|---|---|
| Core | Rute exchange | `POST /api/connect/v1/sso/exchange` (`routes/api.php:502-506`) |
| Core | Middleware | `connect.sso-key` = `VerifyConnectSsoApiKey` (scope `sso:exchange` wajib) |
| Core | Header key | `X-ALIM-CONNECT-SSO-KEY` |
| Core | Envelope sukses | `{success:true, data:{core_user_id,...}}` — Connect menerima bentuk ini (`http-alim-core-sso.client.ts:117`) |
| Core | Envelope gagal | `{success:false, errors:{code, context}}` dengan status 401/403/404/409/410/422 |
| Connect | Halaman handoff | `GET|POST /sso/handoff` (`@Controller('sso')`, tanpa global prefix) |
| Connect | Header key | `X-ALIM-CONNECT-SSO-KEY` (`ALIM_CONNECT_SSO_API_HEADER`) |
| Connect | Origin allowlist | `ALIM_CORE_WEB_ORIGIN`, dicocokkan **persis** terhadap `Origin`/`Referer` |
| Connect | Kode error halaman | `not_configured` 503, `origin_required`/`origin_not_allowed` 403, `identity_not_mapped` 403, `code_expired` 410, dst. |

### 1.3 Pasangan konfigurasi yang harus sama

| Nilai | Nama di Core | Nama di Connect |
|---|---|---|
| Key `sso:exchange` | `CONNECT_SSO_API_KEY` (dicocokkan) | `ALIM_CONNECT_SSO_KEY` (dikirim) |
| Nama header | `CONNECT_SSO_API_HEADER` | `ALIM_CONNECT_SSO_API_HEADER` |
| Origin **Core** | — | `ALIM_CORE_WEB_ORIGIN=https://alim.abuhurairah.id` |
| Origin **Connect** | `CONNECT_SSO_FRAME_ORIGIN=https://alim-wa.abuhurairah.id` | — |
| URL handoff | `CONNECT_SSO_HANDOFF_URL=https://alim-wa.abuhurairah.id/sso/handoff` | — |

`CONNECT_SSO_FRAME_ORIGIN` dan `ALIM_CORE_WEB_ORIGIN` **berlawanan arah** — mengisi keduanya dengan nilai yang sama akan mematikan SSO di salah satu sisi.

---

## 2. Temuan kontrak yang diperbaiki di Core

| # | Temuan | Dampak bila dibiarkan | Perbaikan |
|---|---|---|---|
| 1 | Satu-satunya cara mengisi `CONNECT_SSO_*` adalah menyunting `.env` di server | Mengaktifkan SSO di production berarti membuka & menulis berkas rahasia lewat shell; risiko salah edit dan tidak ada jejak audit | Nilai dibaca dari `system_settings` (prioritas) lalu `.env`; key disimpan **terenkripsi APP_KEY** |
| 2 | CSP halaman embed **menimpa** kebijakan global dengan `frame-src 'self' <origin>` saja | Direktif lain (`default-src`, `object-src`, `base-uri`) hilang dari halaman — kehilangan proteksi sambil menambah izin | Hanya direktif `frame-src` yang diganti; direktif global lain dipertahankan |
| 3 | Halaman menampilkan instruksi generik "Isi A dan B" | Operator tidak tahu mana yang benar-benar kurang | Pesan menyebut persis setting yang belum valid |
| 4 | `fetch` handoff tanpa penanganan jaringan | Gagal jaringan menghasilkan error tak terbaca di UI | Pesan jelas + ajakan muat ulang |
| 5 | Form handoff dirender walau belum dikonfigurasi | Elemen mati di halaman | Dirender hanya saat konfigurasi valid |

### 2.1 Berkas yang diubah

| Berkas | Sifat |
|---|---|
| `app/Support/ConnectSsoConfig.php` | **baru** — pembacaan config terpusat + validasi origin/URL |
| `app/Http/Controllers/WhatsAppConnectController.php` | CSP scoped, alasan konfigurasi, origin memakai `ConnectSsoConfig` |
| `app/Services/Connect/ConnectHandoffService.php` | TTL & URL handoff dari `ConnectSsoConfig` |
| `app/Http/Controllers/SuperAdmin/SystemSettingController.php` | tab baru: simpan URL/origin/IP + buat key SSO |
| `resources/views/super-admin/system-settings/index.blade.php` | tab **WhatsApp Connect** |
| `resources/views/whatsapp-connect/index.blade.php` | alasan konfigurasi, error jaringan, form bersyarat |
| `tests/Feature/WhatsAppConnectSsoTest.php` | +5 skenario baru |

Tidak ada perubahan pada middleware `VerifyConnectSsoApiKey` maupun `IntegrationApiConfig` — jalur key `system_settings` memang sudah ada sejak POS/Recruitment, SSO tinggal memakainya.

### 2.2 Hasil uji

```
php artisan test tests/Feature/WhatsAppConnectSsoTest.php
Tests: 31 passed (132 assertions)
```

Mencakup: override `system_settings` di atas `.env`, key SSO dari database dipakai exchange (dan key `.env` ditolak 401 setelahnya), origin ber-path dipangkas, origin `http://` ditolak di luar lingkungan lokal, serta pesan setting yang kurang per-kondisi.

---

## 3. Konfigurasi yang disiapkan untuk production

Nilai alamat (bukan rahasia), aman ditulis di sini:

```
CONNECT_SSO_FRAME_ORIGIN = https://alim-wa.abuhurairah.id
CONNECT_SSO_HANDOFF_URL  = https://alim-wa.abuhurairah.id/sso/handoff
CONNECT_SSO_API_HEADER   = X-ALIM-CONNECT-SSO-KEY
CONNECT_SSO_ALLOWED_IPS  = (kosong sampai egress Connect punya IP tetap)
```

Pasangan di Connect:

```
ALIM_CORE_BASE_URL    = https://alim.abuhurairah.id
ALIM_CORE_WEB_ORIGIN  = https://alim.abuhurairah.id
ALIM_CONNECT_SSO_KEY  = <nilai key sso:exchange dari Core>
```

Catatan keamanan yang sudah diperiksa pada Core production:

| Pemeriksaan | Hasil |
|---|---|
| TLS | valid, HSTS `max-age=31536000` |
| Cookie sesi | `Secure=true`, `SameSite=lax`, domain `alim.abuhurairah.id` → form POST same-origin di iframe tetap membawa sesi |
| CSP web | `Content-Security-Policy-Report-Only` (tidak memblokir `form-action` ke Connect) |
| `frame-src` | hanya ditambahkan pada halaman WhatsApp Connect, bukan global |
| `X-Frame-Options` Core | `SAMEORIGIN` — membatasi yang meng-embed Core, bukan sebaliknya |

---

## 4. Bukti HTTP produksi (hari ini, 2026-11-25)

### 4.1 Core

```
POST https://alim.abuhurairah.id/api/connect/v1/sso/exchange
→ 503  "Integrasi ALIM Connect SSO belum dikonfigurasi pada server."
```

Artinya rute **sudah terpasang** di production (bukan 404) dan menolak dengan benar selama key belum ada. Kode SSO Core sudah ada di release production: `ConnectSsoExchangeController`, `VerifyConnectSsoApiKey`, dan `POST api/connect/v1/sso/exchange` terdaftar di `artisan route:list`.

`CONNECT_*` **tidak ada satu pun** di `.env` production (hanya 33 kunci; tidak ada `CONNECT_SSO_*`).

### 4.2 Connect

```
POST https://alim-wa.abuhurairah.id/sso/handoff        → 503 not_configured
POST https://alim-wa.abuhurairah.id/sso/handoff  (Origin: https://alim.abuhurairah.id) → 503 not_configured
POST https://alim-wa.abuhurairah.id/sso/handoff  (Origin: https://evil.example)        → 503 not_configured
```

Tiga request berbeda menghasilkan jawaban **identik**. Menurut `sso-handoff.controller.ts:98-112`, `evil.example` seharusnya dijawab `403 origin_not_allowed` dan request tanpa Origin `403 origin_required`. Karena ketiganya 503, jalur yang melayani request ini **tidak menjalankan kode SSO** yang ada di repo.

`ALIM_CONNECT_SSO_KEY`, `ALIM_CORE_BASE_URL`, `ALIM_CORE_WEB_ORIGIN` belum disetel di Connect production — tidak ada akses SSH ke host tersebut, sehingga tidak bisa diverifikasi dari sisi Core.

### 4.3 Kesiapan Connect production: belum

Rilis yang tercatat terpasang (`da5ac8187`, tag `alim-connect-prod-20261125`) memang berisi modul SSO, dan `SsoModule` terdaftar di `app.module.ts` rilis itu. Namun perilaku runtime tidak cocok dengan rilis tersebut:

| Uji | Hasil observasi | Perilaku yang seharusnya |
|---|---|---|
| `POST /sso/handoff` + Origin asing | 503 `not_configured` | 403 `origin_not_allowed` |
| `POST /sso/handoff` + tanpa Origin | 503 `not_configured` | 403 `origin_required` |
| `POST /api/approval/identities` | **404** | rute terdaftar (`approval.controller.ts:118`) → 401 |
| `POST /api/sso/handoff` | 503 `not_configured` | tidak ada rute ini di source |
| Header `X-Frame-Options` | `SAMEORIGIN` di semua rute | `frameguard:false` saat origin Core disetel |

Jadi artefak `dist` yang berjalan di host Connect **bukan build dari release yang tercatat**, atau konfigurasi server mengarah ke direktori lama. Ini juga menjelaskan mengapa `ALIM_CORE_WEB_ORIGIN` tetap kosong: CSP `frame-ancestors` masih `'self'` dan `X-Frame-Options: SAMEORIGIN` masih dikirim, sehingga **iframe Connect akan tetap ditolak browser** meskipun Core sudah dikonfigurasi penuh.

---

## 5. Verifikasi (status jujur per butir)

| # | Syarat | Status | Bukti |
|---|---|---|---|
| 1 | Halaman Core tidak lagi menampilkan "belum dikonfigurasi" | **Belum** (menunggu deploy Core) | Bagian 6 |
| 2 | Iframe memuat dashboard Connect | **Belum** — terhalang §4.3 | CSP Connect masih `frame-ancestors 'self'` + XFO `SAMEORIGIN` |
| 3 | Origin iframe sesuai domain Connect | **Siap di kode & uji** | Test `frame-src 'self' https://connect.test`; tidak ada wildcard |
| 4 | SSO mengidentifikasi pengguna yang benar | **Terbukti di staging** | Cycle 12: permit disetujui aktor terpetakan, 1 command = 1 audit |
| 5 | Sesi sesuai kebijakan keamanan | **Lulus di sisi Core** | Cookie `Secure`+`lax`, kode sekali pakai (SHA-256, TTL 60s), throttle |
| 6 | Pengguna tanpa otorisasi tidak dapat akses | **Lulus** | Tanpa `whatsapp_connect_view` → 403; user nonaktif & permission dicabut ditolak saat exchange |
| 7 | Kegagalan handoff ditangani dengan jelas | **Sebagian** | Connect punya halaman error per-kode; Core kini menyebut setting yang kurang. Blokir iframe lintas-origin tidak bisa dibaca dari sisi Core — karena itu tombol "Buka di tab baru" tetap disediakan |
| 8 | Tidak ada secret/token terekspos | **Lulus** | Kode tidak pernah di URL/log; key hanya header server-to-server; key Core hanya ditulis, tidak pernah ditampilkan |

**Belum ada satu pun bukti iframe berhasil di production.** Saya tidak menyatakan integrasi berhasil.

---

## 6. Langkah aktivasi (belum dijalankan)

Perubahan Core di atas masih **belum di-commit**, jadi belum ada di production. `git push` ke `main` memicu auto-deploy, dan production masih punya 10 berkas dirty sehingga penjaga remote (`scripts/auto-deploy-hostinger.sh`) akan **menghentikan** deploy.

### 6.1 Urutan yang saya sarankan

1. **Tutup pekerjaan lain lebih dulu.** Working tree lokal memuat perubahan Perpustakaan milik proses lain. Commit SSO harus dipisah agar tidak ikut terbawa.
2. **Selesaikan artefak Connect lebih dulu** (pemilik Connect): pasang `dist` dari `da5ac8187` (atau lebih baru), setel `ALIM_CORE_BASE_URL`, `ALIM_CORE_WEB_ORIGIN=https://alim.abuhurairah.id`, `ALIM_CONNECT_SSO_KEY`, lalu restart. Tanpa ini konfigurasi Core tidak ada gunanya.
3. **Deploy Core** setelah worktree host bersih.
4. **Isi konfigurasi Core** lewat *Pengaturan Sistem → WhatsApp Connect*: URL handoff, origin, lalu **Buat key SSO**. Key ditampilkan sekali ke layar operator untuk disalin ke Connect — tidak pernah ke log.
5. **Uji** dengan akun yang punya `whatsapp_connect_view` dan `core_user_id`-nya terpetakan di Connect.

### 6.2 Mengapa saya berhenti di sini

Konfigurasi Core memerlukan key yang harus **sama persis** dengan yang dipegang Connect, dan Connect harus menyetel origin Core. Keduanya tidak bisa saya selesaikan sendiri:

- Saya tidak punya akses SSH ke host Connect untuk menyetel env maupun mengganti artefak.
- Build-nya sudah terbukti tidak cocok dengan source, jadi menyalakan Core sekarang hanya menghasilkan pesan error yang lebih baik — bukan integrasi yang bekerja.
- Deployment Core tidak boleh dipicu sebelum worktree production bersih (risiko kehilangan perubahan orang lain).

---

## 7. Blocker yang tersisa

| # | Blocker | Pemilik keputusan |
|---|---|---|
| B1 | Artefak Connect tidak cocok dengan rilis `da5ac8187` (SSO tidak berperilaku sesuai source; `/api/approval/identities` 404) | Pemilik Connect |
| B2 | `ALIM_CORE_WEB_ORIGIN`, `ALIM_CORE_BASE_URL`, `ALIM_CONNECT_SSO_KEY` belum disetel di Connect | Pemilik Connect, setelah key dari Core |
| B3 | 10 berkas dirty di host Core menghalangi auto-deploy | Pemilik Core |
| B4 | Perubahan SSO Core belum di-commit | Persetujuan Anda |
| B5 | Egress IP server Connect belum diketahui → allowlist Core masih kosong (key satu-satunya faktor pembatas) | Pemilik Connect |
| B6 | `core_user_id` akun uji belum terpetakan ke akun Connect aktif | Pemilik Connect |
| B7 | Nama header key menyebut `SSO`, padahal perannya **masuk** ke Core; catatan yang benar ada di `alim-connect-env-reconciliation.md` §2.1 — jangan mengubahnya hanya karena namanya | — (catatan) |

---

## 8. Jalur mundur

Karena SSO gagal-tertutup (*fail-closed*), mematikannya tidak memerlukan perubahan kode:

1. *Pengaturan Sistem → WhatsApp Connect* → kosongkan URL handoff dan origin → halaman kembali menampilkan pesan belum dikonfigurasi, rute exchange menjawab 503.
2. Rotasi key: tombol **Buat key SSO baru** langsung membatalkan key lama; handoff berikutnya gagal sampai Connect diperbarui.
3. Di Connect, kosongkan `ALIM_CORE_WEB_ORIGIN` → CSP kembali `frame-ancestors 'self'` dan `X-Frame-Options: SAMEORIGIN`.

Data yang dibuat saat uji hanya baris `connect_sso_handoffs` dan `audit_logs`; keduanya bisa dibiarkan sebagai jejak audit.
