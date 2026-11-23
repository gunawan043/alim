# Prosedur Staging ALIM Core untuk Integrasi ALIM Connect — PDCA Cycle 9

**Tanggal:** 2026-11-23
**Status:** Staging Core **lokal** siap dan terverifikasi (17/17 pemeriksaan HTTP lulus).
Staging server bersama Connect **belum** disediakan — lihat §9 Blocker.
**Kontrak acuan:** `docs/integration/alim-core-connect-staging-readiness.md` dan
`docs/integration/alim-connect-approval-command-api.md`

---

## 1. Apa yang sudah siap

| Komponen | Lokasi | Status |
| --- | --- | --- |
| Profil environment staging | `.env.staging` (di-gitignore, tanpa secret) | siap |
| Database staging terisolasi | `database/staging.sqlite` (di-gitignore) | migrasi lengkap |
| Secret manager kredensial | `~/.alim/staging/connect-secrets.enc.json` (0600, terenkripsi APP_KEY staging) | 2 secret, terpisah |
| Data uji | seeder `ConnectStagingTestDataSeeder` | siap |
| Kode handoff sekali pakai | `alim:connect-staging-handoff` | siap |
| Uji HTTP end-to-end | `alim:connect-staging-verify` | 17/17 lulus |

Kredensial SSO dan Approval **terpisah** (fingerprint `1924ece36829` vs `9fa75d9589f1`,
panjang 64 hex = 256-bit). Nilai tidak ditulis di dokumen ini dan tidak pernah dicetak.

---

## 2. Menjalankan staging

```bash
# 1. Database (sekali, atau setelah skema berubah)
php artisan migrate --pretend --env=staging      # tinjau dulu
php artisan migrate --env=staging
php artisan alim:verify-connect-schema --env=staging

# 2. Data uji (idempoten)
php artisan db:seed --class=ConnectStagingTestDataSeeder --env=staging

# 3. Server dengan kredensial dari secret manager
CONNECT_SSO_API_KEY="$(php artisan alim:connect-secret get sso:exchange --env=staging --stdout)" \
CONNECT_APPROVAL_API_KEY="$(php artisan alim:connect-secret get approval:command --env=staging --stdout)" \
php artisan serve --env=staging --host=127.0.0.1 --port=8001

# 4. Verifikasi
CONNECT_SSO_API_KEY="$(php artisan alim:connect-secret get sso:exchange --env=staging --stdout)" \
CONNECT_APPROVAL_API_KEY="$(php artisan alim:connect-secret get approval:command --env=staging --stdout)" \
php artisan alim:connect-staging-verify --env=staging
```

Mengembalikan permit uji ke `pending` setelah uji mutasi:

```bash
php artisan tinker --env=staging --execute="
DB::table('approval_commands')->delete();
DB::table('dormitory_permits')->where('purpose','Uji integrasi Connect stg-active')
  ->update(['status'=>'pending','secondary_status'=>null,'approved_by'=>null,
            'approved_at'=>null,'expected_return_datetime'=>now()->addDays(2)]);
"
```

---

## 3. Secret manager

`php artisan alim:connect-secret <aksi>`:

| Aksi | Keterangan |
| --- | --- |
| `generate sso:exchange` / `generate approval:command` | buat 64 hex, tolak jika sudah ada |
| `status` | tabel + fingerprint 12 karakter; gagal bila SSO == Approval |
| `verify` | uji nilai dari store diterima resolver middleware + scope benar |
| `rotate` | ganti semua secret sekaligus |
| `get <scope> --stdout` | satu-satunya jalur yang menulis nilai, untuk substituisi env |
| `export-env --stdout` | dua baris `KEY=value` untuk dipipe ke berkas 0600 |

Tanpa `--stdout`, perintah **menolak** mencetak nilai. Berkas disimpan dengan
`rename()` atomik dan dipaksakan mode `0600`.

---

## 4. Data uji staging

| Item | Nilai |
| --- | --- |
| Asrama | `STG-CONNECT-001` — Asrama Uji Connect (staging) |
| Kepala asrama (aktor) | `kepala.asrama@staging.invalid` |
| Identitas WA aktor | `6280000000001` |
| Nomor tanpa mapping (uji negatif) | `6280000000099` |
| User SSO berizin | `sso.penguji@staging.invalid` (`whatsapp_connect_view`) |
| User SSO tanpa izin | `sso.tanpaizin@staging.invalid` |
| Permit `pending` aktif | `purpose = 'Uji integrasi Connect stg-active'` |
| Permit `pending` kedaluwarsa | `purpose = 'Uji integrasi Connect stg-expired'` |
| Password semua akun uji | `Staging-Cycle9!` |

Semua memakai domain `@staging.invalid` dan nomor `6280000…` agar jelas bukan data produksi.
Seeder **menolak jalan** bila `APP_ENV` bukan `staging` atau driver bukan SQLite.

---

## 5. Kode handoff

```bash
php artisan alim:connect-staging-handoff --env=staging --json
```

TTL 60 detik — terbitkan saat Connect siap menukar, bukan sebelumnya. Hanya
`expires_at` dan metadata lain yang boleh diserahkan; kode mentah harus lewat kanal rahasia.

---

## 6. Hasil verifikasi (2026-11-23)

`alim:connect-staging-verify` — **17/17 lulus**:

| # | Pemeriksaan | Hasil |
| - | --- | --- |
| 0 | SSO dan Approval memakai credential terpisah | lulus |
| 1 | Kedua endpoint terjangkau (401 tanpa key, bukan 404/5xx) | lulus |
| 3a | Key SSO ditolak endpoint Approval (401) | lulus |
| 3b | Key Approval ditolak endpoint SSO (401) | lulus |
| 4 | Key salah ditolak 401 | lulus |
| 5 | Key Approval benar diterima (422 validasi, bukan 401/403) | lulus |
| 6a | `X-ALIM-API-Version: 2` → 422 `unsupported_api_version` | lulus |
| 6b | `Idempotency-Key` ≠ `command_id` → 422 `idempotency_key_mismatch` | lulus |
| 6c | Content-Type non-JSON → 415 | lulus |
| 7a | Kode tak dikenal → 404 `code_not_found` | lulus |
| 7b | `audience` salah → 403 `audience_mismatch` | lulus |
| 8a | Command sah → 200 `applied` | lulus |
| 8b | Replay identik → 200 `duplicate: true` | lulus |
| 8c | `command_id` sama, payload beda → 409 `command_id_reused` | lulus |
| 9 | `approval_commands.idempotency_key == command_id`, `api_version = 1` | lulus |
| 10 | `audit_logs` mencatat aktor + keputusan + `command_id` | lulus |

Pemeriksaan tambahan di luar skrip:

- **SSO positif**: kode handoff nyata → 200 dengan `core_user_id` yang benar; kode yang
  sama ditukar lagi → 409 `code_already_used`.
- **Allowlist**: `CONNECT_APPROVAL_ALLOWED_IPS=203.0.113.7` dengan klien `127.0.0.1`
  → 403 "Alamat IP tidak diizinkan".
- **Tanpa kebocoran**: tabel `approval_commands` sengaja dihilangkan → klien menerima
  `500 {"errors":{"code":"core_error","context":null}}` tanpa stack trace, SQL, atau nama
  tabel; detail lengkap hanya di `storage/logs/staging.log`.
- **Test suite**: 62 test (240 assertion) `ConnectApprovalCommandTest` +
  `WhatsAppConnectSsoTest` lulus.

---

## 7. Yang perlu disediakan untuk staging server bersama Connect

Nilai berikut masih menunggu operator/hosting. Untuk staging yang dijangkau Connect,
jalankan dengan profil yang sama lalu isi:

| Variable | Nilai sekarang (lokal) | Yang dibutuhkan di server |
| --- | --- | --- |
| `ALIM_CORE_BASE_URL` | `http://127.0.0.1:8001` | URL HTTPS host staging Core |
| `CONNECT_SSO_ALLOWED_IPS` | kosong | IP egress server Connect |
| `CONNECT_APPROVAL_ALLOWED_IPS` | kosong | IP egress server Connect |
| `CONNECT_SSO_FRAME_ORIGIN` | kosong | origin Connect yang meng-embed |
| `CONNECT_SSO_HANDOFF_URL` | kosong | URL halaman handoff Connect |
| `CONNECT_*_REQUIRE_HTTPS` | `false` | `true` pada staging ber-HTTPS |

Selain itu: staging **wajib** database terpisah dari produksi. Produksi saat ini
memakai SQLite (lihat memori proyek), sehingga menunjuk staging ke database produksi
dapat merusak data nyata. Jangan pakai credential produksi.

---

## 8. Batasan

1. Credential staging hanya bisa dibuka dengan `APP_KEY` staging di `.env.staging`.
   Bila `APP_KEY` itu dihapus, secret harus dibuat ulang (Connect harus ikut diganti).
2. Nilai secret hidup di environment proses `php artisan serve`. Untuk server nyata,
   gunakan secret manager host (vault / systemd `EnvironmentFile` ber-permisi 0600).
3. `alim:connect-staging-verify` mengubah status permit uji. Jangan diarahkan ke
   data produksi.
4. Kode handoff berumur 60 detik, jadi skip (`410 code_expired`) tidak bisa diuji
   lewat skrip; perlu jeda manual.

---

## 9. Blocker

| # | Blocker | Pemilik |
| - | --- | --- |
| 1 | Host staging Core + HTTPS belum disediakan | operator hosting |
| 2 | IP egress Connect belum diberikan | operator Connect |
| 3 | Origin dan URL handoff Connect belum diberikan | operator Connect |
| 4 | `ALIM_CONNECT_INGEST_KEY` (Connect → Core) belum diterima lewat kanal aman | operator Connect |

Integrasi **belum** dinyatakan berhasil: Connect belum menjalankan HTTP integration test
nyata terhadap staging Core.

---

## 10. Hasil verifikasi insiden Cycle 9.1 (2026-11-23)

### 10.1 Commit `1c069d99`

Isinya: 4 file pekerjaan evaluasi milik proses lain, **plus** `ConnectSecret.php` dan
satu baris `.gitignore` (`.env.staging`) yang ikut tersapu. Commit ini sudah ada di
`origin/main` dan sudah ter-deploy ke produksi.

### 10.2 Tidak ada artefak sensitif yang bocor

| Yang diperiksa | Hasil |
| --- | --- |
| `.env.staging` pernah ter-track di commit mana pun | tidak (`git log --all` kosong) |
| `staging.sqlite` / `connect-secrets.enc.json` pernah ter-track | tidak |
| Nilai credential berpola panjang di riwayat file env | tidak ditemukan |
| `git archive` + rsync exclude | hanya mengekspor isi commit; `.env*` dan `*.sqlite` tidak ikut |
| Produksi: `.env.staging`, `database/staging.sqlite` | tidak ada |
| Produksi: direktori `~/.alim` | tidak ada |

### 10.3 Audit `ConnectSecret.php` — kondisi produksi

| Butir | Sebelum perbaikan | Sesudah `bc75d397` |
| --- | --- | --- |
| Hanya berjalan saat dipanggil | ya, tidak ada constructor/boot side effect | sama |
| Terdaftar di scheduler / hook deploy | tidak | tidak |
| Enkripsi | `Crypt` dengan `APP_KEY` proses terkait | sama |
| Nilai bisa tercetak | hanya via `get --stdout` / `export-env --stdout` | sama |
| Kebocoran lewat exception/log | tidak ada `Log::`; exception ditelan tanpa memuat nilai | sama |
| Fallback ke APP_KEY/konfigurasi produksi | tidak ada — `getenv('HOME')` bisa jatuh ke `sys_get_temp_dir()` | tetap; jalur kompromi utama ditutup |
| Path di luar lokasi seharusnya | **cacat**: `--file` menerima path sembarang (bisa menimpa `.env`) | dibatasi ke `~/.alim/staging` |
| Berjalan di produksi | **cacat**: tidak ada pengaman | menolak bila `APP_ENV=production` |

Status produksi terbukti: `alim:connect-secret status` → kedua scope `BELUM ADA`,
`services.connect_sso.api_key` dan `connect_approval.api_key` keduanya kosong, dan
tidak ada key `CONNECT_*` di `.env` produksi. Penilaian risiko: **rendah**, karena
command hanya bersifat opt-in dan produksi tidak memiliki secret staging.

### 10.4 Bukti versi API

| Uji | Hasil |
| --- | --- |
| `X-ALIM-API-Version: 1` dengan payload tidak sah | 422 `validation_failed` — artinya **versi lolos**, request sampai ke validasi payload |
| `X-ALIM-API-Version: 1` dengan payload sah | 200 `applied`; `approval_commands.api_version = 1` |
| `X-ALIM-API-Version: 2` | 422 `unsupported_api_version` |

Staging lokal: 18/18 pemeriksaan lulus.

