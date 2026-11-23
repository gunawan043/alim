# Kesiapan Integrasi Staging — ALIM Core ↔ ALIM Connect

**Tanggal:** 2026-11-23
**Status:** Core siap diuji terhadap Connect. Belum ada integrasi produksi, belum ada kredensial diterbitkan.
**Kontrak acuan:** `docs/integration/alim-connect-core-integration-contract.md` (milik ALIM Connect)

Dokumen ini menjelaskan **apa yang disediakan Core** dan **apa yang harus disiapkan operator**
sebelum Connect dapat menjalankan pengujian integrasi nyata. Tidak ada kredensial asli di sini;
semua nilai rahasia ditulis sebagai placeholder.

---

## 1. Endpoint yang tersedia

| Kegunaan        | Method | Path                                  | Middleware                                            |
| --------------- | ------ | ------------------------------------- | ----------------------------------------------------- |
| SSO exchange    | `POST` | `/api/connect/v1/sso/exchange`        | `connect.sso-key`, `throttle:60,1`                    |
| Approval command| `POST` | `/api/connect/v1/approval-commands`   | `connect.approval-key`, `connect.contract-headers`, `throttle:60,1` |

Keduanya berada di `routes/api.php` (prefix `api`), sehingga URL penuh memakai `/api/...`.
Tidak ada endpoint Connect lain di Core. API master tetap read-only dan tidak berubah.

```bash
php artisan route:list --path=connect/v1
```

---

## 2. Base URL staging

| Item               | Nilai                                                         |
| ------------------ | ------------------------------------------------------------- |
| `ALIM_CORE_BASE_URL` | URL publik staging Core, **tanpa** garis miring di akhir     |
| Skema              | HTTPS wajib (middleware memaksa HTTPS saat `APP_ENV=production`) |
| Contoh bentuk      | `https://<host-staging-core>`                                  |

Nilai host sebenarnya diisi operator dan tidak ditulis di dokumen ini.

---

## 3. Mekanisme penyimpanan credential

Kedua key mengikuti mekanisme yang sama (`App\Support\IntegrationApiConfig`):

1. **Sumber utama: tabel `system_settings`** — kunci disimpan terenkripsi
   (`APP_KEY`, prefix `enc:v1:`), bukan plaintext.
   - `connect_approval_api_key`, `connect_approval_api_header`, `connect_approval_api_allowed_ips`, `connect_approval_api_enabled`
   - `connect_sso_api_key`, `connect_sso_api_header`, `connect_sso_api_allowed_ips`, `connect_sso_api_enabled`
2. **Fallback: `config/services.php` via `.env`** — dipakai bila nilai `system_settings` kosong.

Aturan:

- Jangan menaruh nilai key di repository, dokumentasi, tiket, atau log.
- Key Connect **berbeda** dari key POS, Recruitment, dan key pengguna.
- Perbandingan dilakukan dengan `hash_equals` (konstan waktu). Percobaan gagal di-rate-limit
  (`CONNECT_*_AUTH_FAIL_LIMIT`, default 10/menit per IP).
- Rotasi: isi `.env`/`system_settings` selagi jendela rotasi (dua nilai aktif), lalu hapus nilai lama.
  Karena hanya satu nilai aktif per konfigurasi, rotasi di Core dilakukan dengan mengganti nilai
  pada saat yang sama dengan Connect mengganti header-nya.
- Pembuatan nilai acak:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

---

## 4. Daftar environment variable

### 4.1 Approval Command (inbound)

| Variable                                | Wajib | Default                          | Keterangan |
| --------------------------------------- | ----- | -------------------------------- | ---------- |
| `CONNECT_APPROVAL_API_KEY`              | ya    | —                                | Key service-to-service; scope `approval:command` saja |
| `CONNECT_APPROVAL_API_HEADER`           | tidak | `X-ALIM-CONNECT-APPROVAL-KEY`    | Harus sama dengan header Connect |
| `CONNECT_APPROVAL_ALLOWED_IPS`          | tidak | kosong (semua IP)                | Daftar IP/CIDR dipisah koma; **wajib diisi di staging** |
| `CONNECT_APPROVAL_SCOPES`               | tidak | `approval:command`               | Harus memuat `approval:command`, jika tidak → 403 `insufficient_scope` |
| `CONNECT_APPROVAL_MAX_DURATION_DAYS`    | tidak | `365`                            | Batas atas `approved_duration_days` |
| `CONNECT_APPROVAL_MESSAGE_TEXT_MAX_LENGTH` | tidak | `500`                         | Cuplikan teks WhatsApp yang disimpan |
| `CONNECT_APPROVAL_REQUIRE_HTTPS`        | tidak | `true`                           | Hanya berlaku saat `APP_ENV=production` |
| `CONNECT_APPROVAL_AUTH_FAIL_LIMIT`      | tidak | `10`                             | Percobaan auth gagal per menit per IP |

### 4.2 SSO Handoff (inbound exchange)

| Variable                     | Wajib | Default                      | Keterangan |
| ---------------------------- | ----- | ---------------------------- | ---------- |
| `CONNECT_SSO_API_KEY`        | ya    | —                            | Key server-to-server; scope `sso:exchange` saja |
| `CONNECT_SSO_API_HEADER`     | tidak | `X-ALIM-CONNECT-SSO-KEY`     | Harus sama dengan header Connect |
| `CONNECT_SSO_ALLOWED_IPS`    | tidak | kosong (semua IP)            | Daftar IP/CIDR; **wajib diisi di staging** |
| `CONNECT_SSO_SCOPES`         | tidak | `sso:exchange`               | Harus memuat `sso:exchange` |
| `CONNECT_SSO_HANDOFF_URL`    | ya    | —                            | URL halaman handoff di Connect yang menerima form POST |
| `CONNECT_SSO_FRAME_ORIGIN`   | ya    | —                            | Origin Connect untuk CSP `frame-ancestors` |
| `CONNECT_SSO_HANDOFF_TTL`    | tidak | `60`                         | Masa berlaku kode handoff (detik) |
| `CONNECT_SSO_REQUIRE_HTTPS`  | tidak | `true`                       | Hanya berlaku saat `APP_ENV=production` |
| `CONNECT_SSO_AUTH_FAIL_LIMIT`| tidak | `10`                         | Percobaan auth gagal per menit per IP |

Nilai `CONNECT_APPROVAL_API_KEY` dan `CONNECT_SSO_API_KEY` **wajib berbeda** satu sama lain.

---

## 5. Konfigurasi akses jaringan

- Port 443 (HTTPS) terbuka ke host Connect saja; jangan ekspos ke internet umum.
- Isi `CONNECT_APPROVAL_ALLOWED_IPS` dan `CONNECT_SSO_ALLOWED_IPS` dengan IP keluar (egress)
  server Connect. Untuk Connect di belakang proxy, gunakan IP proxy tersebut, dan pastikan
  `TrustProxies` Core memetakan header `X-Forwarded-For` dengan benar agar allowlist menilai
  IP klien yang sebenarnya.
- Rate limit API: `throttle:60,1` per IP pada kedua endpoint. Saat melampaui, Core membalas `429`
  dengan header `Retry-After`; Connect akan mengulang dengan `command_id` yang sama.
- Respons disertai `Cache-Control: no-store` agar tidak tersimpan di cache perantara.

---

## 6. Kontrak request dan response

### 6.1 SSO exchange

```http
POST /api/connect/v1/sso/exchange
Content-Type: application/json
X-ALIM-CONNECT-SSO-KEY: <CONNECT_SSO_API_KEY>

{ "code": "<handoff code>", "audience": "connect", "scope": "whatsapp.embed" }
```

Sukses `200`:

```json
{
  "success": true,
  "message": "Handoff berhasil ditukar.",
  "data": {
    "core_user_id": "uuid-users",
    "name": "Nama Pengguna",
    "email": "user@example.org",
    "audience": "connect",
    "scope": "whatsapp.embed",
    "issued_at": "2026-11-23T09:00:00+08:00",
    "redeemed_at": "2026-11-23T09:00:03+08:00"
  },
  "meta": { "timestamp": "..." },
  "errors": null
}
```

Error (`errors.code` selalu ada, `context` selalu ada—boleh kosong):

| HTTP | `errors.code`        | Arti |
| ---- | -------------------- | ---- |
| 401  | —                    | Key tidak ada/salah |
| 403  | `insufficient_scope` | Key tanpa `sso:exchange` |
| 403  | `audience_mismatch`  | `audience` ≠ `connect` |
| 403  | `scope_mismatch`     | `scope` ≠ `whatsapp.embed` |
| 403  | `permission_revoked` | Pengguna kehilangan `whatsapp_connect_view` |
| 403  | `user_inactive`      | Akun Core tidak aktif |
| 404  | `code_not_found`     | Kode tidak dikenal |
| 409  | `code_already_used`  | Kode sudah ditukar |
| 410  | `code_expired`       | Kode melewati TTL |
| 422  | `code_required` / `validation_failed` | Payload tidak sah |
| 500  | `core_error`         | Kegagalan tak terduga |

Catatan: kode handoff hanya disimpan sebagai SHA-256. Kode sekali pakai; audience/scope yang salah
tidak menghabiskan kode.

### 6.2 Approval command

Request, header, dan respons mengikuti kontrak Connect §2–§8 apa adanya. Ringkasan perilaku Core
yang sudah diverifikasi:

- **Autentikasi**: `X-ALIM-CONNECT-APPROVAL-KEY`, atau `Authorization: Bearer` sebagai cadangan.
  401 bila salah, 403 `insufficient_scope` bila scope tidak ada.
- **`Idempotency-Key`**: opsional. Bila dikirim, nilainya **harus sama dengan `command_id`**;
  selisih → `422 idempotency_key_mismatch` dan tidak ada perubahan data. Header dikembalikan di
  respons sukses dan disimpan di kolom `approval_commands.idempotency_key`.
- **`X-ALIM-API-Version`**: opsional, nilai didukung `1`. Nilai lain → `422 unsupported_api_version`
  (bukan 500). Bila kosong, dianggap kompatibel. Disimpan di `approval_commands.api_version`.
- **`Content-Type`**: wajib `application/json`; selain itu `415 unsupported_media_type`.
- **Identitas aktor**: `actor_external_id` dipetakan ke `connect_actor_identities` yang
  `channel=whatsapp`, `is_active=true`, `verified_at` terisi. `actor_core_person_id` **tidak**
  dipakai untuk otorisasi. Aktor harus Kepala Asrama aktif pada asrama permit
  (`DormitoryPolicyAccess::canApprovePermitCommand`). Pencabutan pemetaan langsung berlaku.
- **Idempotensi**: `command_id` unik; replay dengan payload sama → `200`, `outcome: duplicate`,
  `duplicate: true`, isi `data` identik. `command_id` dengan payload beda → `409 command_id_reused`.
  `external_message_id` yang sudah dipakai → `409 external_message_replayed`.
- **Transaksi & audit**: perubahan status + baris `approval_commands` + `audit_logs` dalam satu
  transaksi. Kegagalan audit → rollback.
- **Kegagalan tak terduga**: `500` `core_error` tanpa detail internal (Connect akan mengulang).

Contoh:

```bash
curl -sS -X POST "$ALIM_CORE_BASE_URL/api/connect/v1/approval-commands" \
  -H "X-ALIM-CONNECT-APPROVAL-KEY: $KEY" \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -H 'Idempotency-Key: 6f1c2a44-3c55-4c1e-9c1f-2b7d8e6a5f01' \
  -H 'X-ALIM-API-Version: 1' \
  -d @command.json
```

---

## 7. Prosedur pengujian integrasi

Prasyarat data staging:

1. Satu asrama aktif dengan Kepala Asrama (via `head_id` **atau** penugasan aktif `kepala_asrama`).
2. Identitas WhatsApp kepala tersebut di `connect_actor_identities` (aktif + `verified_at`).
3. Satu permit berstatus `pending` dengan `departure_datetime` terisi dan `expected_return_datetime` di masa depan.
4. Satu permit kedua yang sudah lewat `expected_return_datetime` (untuk uji `expired`).
5. Satu pengguna dengan izin `whatsapp_connect_view` untuk uji SSO.

Urutan uji:

| # | Uji | Harapan |
| - | --- | ------- |
| 1 | SSO: minta handoff → tukar | `200`, `data.core_user_id` = id pengguna |
| 2 | SSO: tukar kode yang sama lagi | `409 code_already_used` |
| 3 | SSO: tukar setelah TTL | `410 code_expired` |
| 4 | SSO: kode palsu | `404 code_not_found` |
| 5 | SSO: cabut izin → tukar | `403 permission_revoked` |
| 6 | SSO: key salah / scope dicabut | `401` / `403 insufficient_scope` |
| 7 | Approval: command sah `approved` 2 hari | `200`, `applied`, kembali = berangkat + 2 hari |
| 8 | Approval: replay command yang sama | `200`, `duplicate: true`, status tidak berubah |
| 9 | Approval: `command_id` sama, payload beda | `409 command_id_reused` |
| 10 | Approval: `external_message_id` dipakai ulang | `409 external_message_replayed` |
| 11 | Approval: aktor bukan kepala asrama | `403 actor_not_authorized` |
| 12 | Approval: nomor tidak terpetakan | `403 actor_identity_unmapped` |
| 13 | Approval: permit sudah final | `409 permit_not_decidable` |
| 14 | Approval: `expired` pada permit yang belum lewat | `422 permit_not_expired` |
| 15 | Approval: `expired` pada permit yang sudah lewat | `200`, `outcome: expired` |
| 16 | Approval: durasi 0 / di atas batas | `422 duration_out_of_range` |
| 17 | Approval: `X-ALIM-API-Version: 2` | `422 unsupported_api_version` |
| 18 | Approval: `Idempotency-Key` ≠ `command_id` | `422 idempotency_key_mismatch` |

Uji otomatis di Core (mencakup seluruh baris di atas):

```bash
php artisan test tests/Feature/ConnectApprovalCommandTest.php tests/Feature/WhatsAppConnectSsoTest.php
```

Uji dari sisi Connect (tanpa Core sungguhan maupun terhadap staging):

```bash
npm run test:contracts
npx jest --config test/jest-e2e.json test/approval-http.e2e-spec.ts
```

---

## 8. Dependensi yang masih menunggu ALIM Connect

| # | Butir | Status Core |
| - | ----- | ----------- |
| 1 | `Idempotency-Key` divalidasi | **Dijawab**: divalidasi, harus sama dengan `command_id`, selisih → 422 |
| 2 | `X-ALIM-API-Version` divalidasi | **Dijawab**: hanya `1` diterima; nilai lain 422; kosong = kompatibel |
| 3 | Penerbitan kredensial | **Menunggu operator**: Core menyediakan mekanisme; nilai key diisi saat staging disiapkan, lalu ditukar ke Connect lewat kanal rahasia |
| 4 | Hanya pemetaan aktif+terverifikasi yang mewakili aktor | **Dijawab**: ya, dan pencabutan langsung berlaku termasuk saat retry |
| 5 | Endpoint organisasi untuk scoping `VIEWER` | **Belum** ada di Core; tetap jadi batasan yang diketahui |

Juga perlu dikonfirmasi Connect: nilai `ALIM_CORE_APPROVAL_API_HEADER` default cocok dengan header
Core, dan `ALIM_CORE_API_VERSION=1` dikirim.

---

## 9. Batasan yang diketahui

1. Kredensial Connect belum diterbitkan; endpoint baru teruji dengan key uji di test suite.
2. Belum ada scoping unit untuk `VIEWER` (dependensi Connect §11).
3. Kebijakan retensi PII (`approval_commands.message_text`, `audit_logs`) belum ditetapkan pemilik sistem.
4. Integrasi ini belum diaktifkan di produksi dan tidak boleh diaktifkan tanpa persetujuan.
