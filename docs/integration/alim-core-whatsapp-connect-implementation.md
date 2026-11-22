---
name: alim-core-whatsapp-connect-implementation
description: Laporan PDCA implementasi SSO handoff dan halaman embed WhatsApp Connect di ALIM Core
metadata:
  type: implementation-report
  date: 2026-11-23
  status: core-implemented-pending-connect
  scope: alim-core-only
---

# Implementasi WhatsApp Connect SSO Embed — Laporan PDCA

> **Status:** implementasi Core selesai untuk komponen yang dapat diuji secara lokal. Belum ada commit, push, merge, deployment, migration produksi, atau aktivasi permission kepada pengguna.
>
> **Connect belum diimplementasikan pada repository ini.** Kontrak yang membutuhkan Connect dicatat sebagai dependensi.

## 1. Hasil Validasi Desain

| Area | Hasil verifikasi |
|---|---|
| Laravel | Laravel 12.70.1; route web/API terpisah |
| Session | Session guard Laravel; cookie host-only pada `.env` aktif (`SESSION_DOMAIN=null`, `SameSite=lax`) |
| Route user | Grup `{userId}` memuat `auth`, `employee.access`, `role.access`, `school.context`, `organization.context` |
| Permission | Spatie + `canPermission()`; `permission` middleware memerlukan `OrganizationContext` |
| Permission catalogue | `PermissionSeeder` berbasis array; penambahan aditif aman |
| Audit | `AuditLog` memiliki `user_id`, `action`, `record_id`, `request_id`, `metadata` |
| CSP | `WebSecurityHeaders` (web) mengirim CSP **report-only** + `X-Frame-Options: SAMEORIGIN`; `SecurityHeadersMiddleware` (API) enforced |
| Migration | Pola UUID, timestamps, FK, dan index konsisten |
| SSO existing | Tidak ada; kontrak Connect belum tersedia |

**Ketidaksesuaian utama:** desain menyebut CSP Core perlu `frame-src`, tetapi implementasi web saat ini mengirim CSP report-only. Implementasi menambahkan **CSP enforced route-specific** hanya pada halaman WhatsApp Connect, tanpa melemahkan policy global.

## 2. Implementasi yang Dilakukan

### Core SSO handoff
- Permission `whatsapp_connect_view` ditambahkan ke `PermissionSeeder`.
- Halaman `/{userId}/whatsapp-connect`.
- Endpoint `POST /{userId}/whatsapp-connect/handoff`.
- API `POST /api/connect/v1/sso/exchange`.
- One-time code 32 byte acak, disimpan sebagai SHA-256 hash.
- TTL default 60 detik, configurable.
- Audience `connect`, scope `whatsapp.embed`.
- Redemption atomik dengan `lockForUpdate` + update `used_at` bersyarat.
- Validasi ulang user aktif dan permission saat exchange.
- Audit issue, success, dan failure tanpa code mentah.
- Dedicated SSO API key + scope `sso:exchange`.
- Halaman Blade responsif dengan iframe, loading/error, dan fallback tab baru.
- Menu sidebar hanya tampil jika `canPermission('whatsapp_connect_view')`.
- CSP `frame-src` route-specific, origin dari environment, tanpa wildcard.

## 3. File dan Migration

### Dibuat

| File | Fungsi |
|---|---|
| `database/migrations/2026_11_22_000003_create_connect_sso_handoffs_table.php` | Tabel handoff |
| `app/Models/ConnectSsoHandoff.php` | Model handoff |
| `app/Exceptions/ConnectSsoException.php` | Error domain SSO |
| `app/Services/Connect/ConnectHandoffService.php` | Issue/redeem/audit |
| `app/Http/Middleware/VerifyConnectSsoApiKey.php` | Auth service + scope `sso:exchange` |
| `app/Http/Requests/Connect/ExchangeConnectHandoffRequest.php` | Validasi exchange |
| `app/Http/Controllers/Api/Connect/V1/ConnectSsoExchangeController.php` | API exchange |
| `app/Http/Controllers/WhatsAppConnectController.php` | Halaman + handoff |
| `resources/views/whatsapp-connect/index.blade.php` | UI iframe/fallback |
| `tests/Feature/WhatsAppConnectSsoTest.php` | Test SSO |

### Diubah

| File | Perubahan |
|---|---|
| `database/seeders/PermissionSeeder.php` | Permission `whatsapp_connect_view` + group Integrasi |
| `app/Http/Kernel.php` | Alias `connect.sso-key` |
| `config/services.php` | Blok `connect_sso` |
| `routes/web.php` | Route halaman + handoff |
| `routes/api.php` | Route `/api/connect/v1/sso/exchange` |
| `resources/views/layouts/sidebar.blade.php` | Menu WhatsApp Connect dengan permission |
| `.env.example` | Placeholder env SSO (tanpa credential) |

### Migration

`connect_sso_handoffs`:

- `id` UUID
- `user_id` FK `users` cascade
- `code_hash` char(64) unique
- `audience` varchar(32)
- `scope` varchar(100) nullable
- `expires_at`, `used_at`
- `used_ip`, `used_user_agent`
- `metadata` json
- timestamps
- Index `(user_id, created_at)`, `expires_at`, `used_at`

Tidak ada code plaintext. Tidak ada perubahan tabel existing.

## 4. Kontrak API Final

### 4.1 Halaman Core

`GET /{userId}/whatsapp-connect`

- Auth session Core.
- `userId` harus sama dengan user login.
- Permission `whatsapp_connect_view`.
- Hanya menampilkan iframe jika `CONNECT_SSO_HANDOFF_URL` dan `CONNECT_SSO_FRAME_ORIGIN` terisi.

### 4.2 Issue handoff

`POST /{userId}/whatsapp-connect/handoff`

- Auth + permission + CSRF + throttle 10/menit.
- Response:

```json
{
  "success": true,
  "data": {
    "code": "base64url-random-32-bytes",
    "audience": "connect",
    "scope": "whatsapp.embed",
    "expires_at": "2026-11-23T10:01:00+08:00",
    "connect_sso_url": "https://connect.example.com/sso/handoff"
  },
  "message": "Handoff berhasil dibuat."
}
```

### 4.3 Exchange (Connect → Core)

`POST /api/connect/v1/sso/exchange`

Header: `X-ALIM-CONNECT-SSO-KEY`

Payload:

```json
{
  "code": "base64url-random-32-bytes",
  "audience": "connect",
  "scope": "whatsapp.embed",
  "state": "optional-correlation-id"
}
```

Response sukses:

```json
{
  "success": true,
  "message": "Handoff berhasil ditukar.",
  "data": {
    "core_user_id": "uuid",
    "name": "Nama Pengguna",
    "email": "user@example.test",
    "audience": "connect",
    "scope": "whatsapp.embed",
    "issued_at": "2026-11-23T10:00:00+08:00",
    "redeemed_at": "2026-11-23T10:00:05+08:00"
  },
  "meta": {},
  "errors": null
}
```

Tidak mengirim role, jabatan, assignment, permission bag, atau unit.

### 4.4 Error

| HTTP | Code |
|---|---|
| 401 | API key invalid |
| 403 | `insufficient_scope` |
| 403 | `audience_mismatch` |
| 403 | `scope_mismatch` |
| 403 | `user_inactive` |
| 403 | `permission_revoked` |
| 404 | `code_not_found` |
| 409 | `code_already_used` |
| 410 | `code_expired` |
| 422 | validasi payload / `code_required` |

### 4.5 Connect endpoint yang belum tersedia

Kontrak ini membutuhkan Connect menyediakan:

- `POST /sso/handoff` (menerima form POST code, bukan URL).
- Server-side call ke Core exchange.
- Mapping identity + session Connect.
- `frame-ancestors` Core.
- Logout/expiry Connect.
- Error `identity_not_mapped`.

## 5. Permission dan Otorisasi

- Permission baru: `whatsapp_connect_view`.
- Menu: `@if(canPermission('whatsapp_connect_view'))`.
- Route: `permission:whatsapp_connect_view`.
- Controller: cek ulang permission.
- Exchange: query fresh DB atas permission langsung, role permission, dan domain aktif.
- Tidak ada pemberian otomatis ke role/user.
- Tidak memakai teks jabatan.
- Connect tetap menjalankan RBAC sendiri.

## 6. Keamanan dan Audit

- Code 32 byte `random_bytes`, base64url, disimpan hash SHA-256.
- TTL default 60 detik.
- One-time redemption: `lockForUpdate` + conditional `used_at` update.
- User aktif dan permission divalidasi ulang saat exchange.
- SSO key terpisah dari approval key; scope `sso:exchange`.
- Rate limit auth gagal dari base middleware + throttle route.
- CSP `frame-src` route-specific; origin per environment; tanpa wildcard.
- `X-Frame-Options: SAMEORIGIN` halaman Core lain tidak diubah.
- Audit:
  - `WHATSAPP_CONNECT_HANDOFF_ISSUED`
  - `WHATSAPP_CONNECT_SSO_REDEEMED`
  - `WHATSAPP_CONNECT_SSO_REDEEM_FAILED`
- Tidak ada code/token/API key di audit atau log.

## 7. Hasil Pengujian

| Test | Hasil |
|---|---|
| `WhatsAppConnectSsoTest` | **18 passed, 71 assertions** |
| `ConnectApprovalCommandTest` | **23 passed, 84 assertions** |
| `GtkWorkUnitLifecycleTest` | **13 passed, 38 assertions** |
| `PosApiTest` + `RecruitmentApiTest` + `DormitoryTaskAssignmentTest` | **54 passed, 269 assertions** |
| `SecurityAuthorizationTest` (sebelumnya) | **10 passed, 23 assertions** |
| Migration SSO up/down | **lulus** dalam test (tabel dibuat, di-drop, dibuat ulang) |

Cakupan test SSO:

- Belum login → redirect.
- Tanpa permission → 403.
- Dengan permission → halaman.
- URL user lain → 403.
- Code hash, bukan plaintext.
- Exchange sukses + identitas minimal.
- Code sekali pakai.
- Expired, audience salah, scope salah.
- Code dimanipulasi.
- User inactive.
- Permission dicabut setelah issue.
- Key salah/scope key salah.
- Rate limit.
- Audit tanpa code.
- Route memakai grup `web` (CSRF).
- Migration up/down.

**Kegagalan view:** `DashboardJabatanTest` (2) dan `SchoolUnitTest` (1) terbukti juga gagal pada baseline `cfc86817`; bukan regresi implementasi ini.

## 8. Kekurangan dan Risiko Tersisa

1. Connect belum menyediakan `/sso/handoff`, exchange client, mapping, dan session.
2. Iframe cross-origin + cookie Connect belum diuji di browser nyata; cookie pihak ketiga dapat diblokir.
3. Origin Connect harus diisi per environment; jika kosong halaman menampilkan pesan konfigurasi.
4. `CONNECT_SSO_HANDOFF_URL` harus menunjuk endpoint Connect yang benar; jangan memakai URL fiktif.
5. Belum ada single logout; logout Core tidak mengakhiri session Connect.
6. Concurrent exchange diuji secara logika/atomic, belum dengan request paralel nyata.
7. Permission belum diberikan kepada role/user mana pun.
8. Belum ada cleanup job untuk handoff kedaluwarsa (index sudah mendukung).
9. CSP Connect (`frame-ancestors`) belum tersedia karena repository Connect tidak diubah.
10. Tiga test view pre-existing tetap gagal.

## 9. Dependensi yang Menunggu ALIM Connect

- Endpoint `POST /sso/handoff`.
- Verifikasi/exchange server-side ke Core.
- Mapping identitas Core → Connect.
- Session Connect, logout, expiry.
- `Content-Security-Policy: frame-ancestors <origin Core>`.
- Error `identity_not_mapped`.
- UI responsif untuk iframe.
- Kontrak final yang disepakati.

## 10. Kriteria untuk PDCA Berikutnya

1. Connect mengimplementasikan `/sso/handoff` dan exchange client.
2. Mapping identitas diuji end-to-end.
3. Browser test (Chrome/Safari/Firefox) untuk iframe + cookie.
4. Uji single logout/session expiry.
5. Uji concurrent exchange dengan request paralel.
6. Uji CSP Connect + Core di staging.
7. Tetapkan role/pengguna penerima `whatsapp_connect_view`.
8. Tetapkan cleanup/retention `connect_sso_handoffs`.
9. Perbaiki tiga test view pre-existing.
10. Review keamanan & persetujuan implementasi/deployment terpisah.

## Catatan Batas

Tidak ada commit, push, merge, deployment, migration produksi, credential produksi, atau pemberian permission kepada pengguna pada pekerjaan ini. Implementasi berada di working tree branch pengembangan saat ini.
