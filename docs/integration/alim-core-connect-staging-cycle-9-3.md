# Cycle 9.3 — Instalasi Staging HTTPS (`alim-staging.abuhurairah.id`)

**Tanggal:** 2026-11-24 · **Repo:** ALIM Core (`alim`) · **Branch:** `main` (lokal, belum di-push)

Domain yang dipakai:

| Peran | Domain | Status |
| --- | --- | --- |
| Staging Core (**target instalasi**) | `alim-staging.abuhurairah.id` | **belum ada** (NXDOMAIN) |
| Produksi Core (**tidak disentuh**) | `alim.abuhurairah.id` | berjalan |
| ALIM Connect (**tidak diubah**) | `alim-wa.abuhurairah.id` | berjalan; hanya jadi Origin/handoff SSO |

Skrip **tidak** dapat dipakai untuk memasang di dua domain terakhir: keduanya ditolak preflight.

---

## 1. Koreksi nama variabel ingest key

Temuan yang perlu dicatat: `CONNECT_CONNECT_INGEST_KEY` **bukan nama yang sah di Core** — nama itu
salah kaprah yang saya buat di Cycle 9.2. Hasil pemeriksaan aktual:

| Variabel | Pemilik | Dibaca oleh | Arah |
| --- | --- | --- | --- |
| `ALIM_CONNECT_INGEST_KEY` | **Connect** | `src/config/configuration.ts` Connect | Core → Connect (`POST /approval/requests`, header `x-alim-connect-ingest-key`) |
| `CONNECT_SSO_API_KEY` / `CONNECT_APPROVAL_API_KEY` | **Core** | middleware Core | Connect → Core |

Bukti bahwa Core tidak memakainya:

- `grep -rn "CONNECT_CONNECT_INGEST_KEY|connect_ingest" app/ config/ routes/` → **0 hasil**.
- Core belum punya kode yang mendorong permit ke Connect (`approval/requests` → 0 hasil).

Perbaikan yang diterapkan:

1. Variabel tersebut **dihapus** dari `.env.staging` Core.
2. `build-connect-staging-profile.sh` kini **menolak** profil yang memuat nama ber-`INGEST`,
   dengan pesan "milik Connect".
3. `install-connect-staging.sh` memeriksa hal yang sama saat preflight.

Akibatnya: kunci ingest tetap satu, disimpan di Connect, dan tidak pernah disalin ke Core.

## 2. Prasyarat hosting (yang harus dibuat pengelola hosting)

Saat ini `alim-staging.abuhurairah.id` belum ada di DNS dan direktorinya belum ada di server.
Yang dibutuhkan, konkret:

| # | Prasyarat | Kondisi sekarang |
| --- | --- | --- |
| P1 | Subdomain `alim-staging.abuhurairah.id` pada domain `abuhurairah.id` | belum ada |
| P2 | Document root terpisah, mis. `~/domains/alim-staging.abuhurairah.id/public_html` | belum ada |
| P3 | Sertifikat TLS untuk domain tersebut | belum ada |
| P4 | PHP ≥ 8.2, ekstensi `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl` | server **sudah** PHP 8.4.28 + SQLite |
| P5 | Direktori aplikasi terpisah, mis. `~/alim-staging` (bukan `~/alim-app`) | belum ada |
| P6 | Pemilik hosting mengonfirmasi CIDR egress Connect | belum diberikan |

P4 sudah terpenuhi di server yang ada; P1–P3 dan P5 menunggu panel hosting.

## 3. Cara pakai skrip instalasi

Default = **preflight saja**, tidak menulis apa pun.

```bash
# 1) dari mesin ini: bangun profil staging (secret dari secret manager, tidak dicetak)
scripts/build-connect-staging-profile.sh \
  --stage-url      https://alim-staging.abuhurairah.id \
  --connect-origin https://alim-wa.abuhurairah.id \
  --connect-handoff https://alim-wa.abuhurairah.id/sso/handoff \
  --connect-egress <CIDR egress Connect> \
  --out /tmp/alim-staging-profile

# 2) preflight di server (aman dijalankan berulang)
ssh alim-deploy '
  cd ~/alim-staging-src && ./scripts/install-connect-staging.sh \
    --docroot      ~/domains/alim-staging.abuhurairah.id/public_html \
    --app-dir      ~/alim-staging \
    --profile      ~/alim-staging-profile \
    --prod-app-dir ~/alim-app'

# 3) setelah preflight LULUS, ulangi dengan --install
```

**Status keluar:** `0` lolos · `2` argumen kurang · `3` kondisi keamanan tidak terpenuhi (tidak
ada yang ditulis) · `4` verifikasi pasca-instalasi gagal.

### Preflight yang dijalankan

1. Argumen lengkap.
2. Docroot **wajib** di bawah `domains/alim-staging.abuhurairah.id`; ditolak bila menunjuk domain
   produksi atau Connect.
3. `app-dir` ≠ `alim-app`, tidak di dalamnya, dan tidak memuatnya (dibandingkan dengan `realpath`).
4. Docroot ≠ document root produksi dan bukan domain produksi.
5. `APP_URL` profil = `https://alim-staging.abuhurairah.id`, dan profil tidak menyebut domain
   produksi.
6. Domain terjangkau + sertifikat TLS valid untuk nama domain itu.
7. PHP ≥ 8.2 dan seluruh ekstensi yang dibutuhkan ada.
8. Profil berpermisi `600`; kelengkapan diperiksa tanpa mencetak nilai (hanya panjang + fingerprint).
9. `APP_KEY` staging **berbeda** dari produksi.
10. Berkas database staging **bukan** berkas database produksi dan tidak berada di direktori produksi.
11. Docroot dan induk `app-dir` dapat ditulis.

### Instalasi (`--install`)

Menolak berjalan bila `app-dir/.env` atau `public_html/public` sudah ada (bukan pemasangan bersih).
Lalu: pasang kode dari **commit** (`git archive`, bukan working tree) → tulis `.env` (600) → buat
`storage/` + `database/staging.sqlite` **kosong** → `migrate` → seeder staging → 
`alim:verify-connect-schema` → cache config/route → tautkan docroot sebagai **symlink** ke
`app-dir/public` (folder `public_html` asli tidak dihapus/ditimpa).

## 4. Prosedur verifikasi setelah instalasi

Dijalankan dari mesin yang **diizinkan allowlist** (atau dari server staging sendiri bila
allowlist belum diisi).

| # | Yang diperiksa | Cara | Harapan |
| --- | --- | --- | --- |
| V1 | Endpoint hidup, tanpa key | `curl -X POST https://alim-staging.abuhurairah.id/api/connect/v1/approval-commands` | **401** (bukan 404/503) |
| V2 | SSO dengan kode baru | terbitkan `alim:connect-staging-handoff`, tukar dengan `CONNECT_SSO_API_KEY` | **200**, ada `core_user_id`, **tanpa** token di body |
| V3 | Kode dipakai ulang | kirim kode yang sama lagi | **409** `code_already_used` |
| V4 | Approval sah | permit pending + aktor `628…0001` | **200** |
| V5 | Replay identik | kirim ulang body yang sama | **200** `duplicate:true` |
| V6 | `command_id` sama, payload beda | ubah `decision` | **409** `command_id_reused` |
| V7 | Versi API | `X-ALIM-API-Version: 2` | **422** `unsupported_api_version` |
| V8 | Key salah | ganti key | **401** |
| V9 | Aktor tak terpetakan | nomor lain | **403** `actor_identity_unmapped` |
| V10 | Kebocoran internal | periksa body error V8–V9 | tanpa `SQLSTATE`/stack/path |
| V11 | Pemisahan staging–produksi | bandingkan `APP_KEY`, jalur DB, dan isi `system_settings` | berbeda semua |
| V12 | Produksi tidak berubah | `curl https://alim.abuhurairah.id/health` | **200**, versi kode produksi tidak berubah |

V4–V7 memakai permit yang di-reset di antara langkah; datanya **hanya** di staging.

## 5. Rollback

Instalasi staging dirancang agar rollback tidak menyentuh produksi.

| Langkah | Perintah | Catatan |
| --- | --- | --- |
| R1 | Nonaktifkan domain di panel hosting | Memutus akses publik staging lebih dulu |
| R2 | `rm ~/domains/alim-staging.abuhurairah.id/public_html/public` | Hanya symlink; docroot asli utuh |
| R3 | `rm -rf ~/alim-staging` | Menghapus kode, `.env`, dan `database/staging.sqlite` staging |
| R4 | `rm -rf ~/alim-staging-profile` | Direktori profil berisi salinan kunci |
| R5 | Cabut sertifikat TLS subdomain | Lewat panel hosting |
| R6 | Rotasi kunci | `php artisan alim:connect-secret rotate --env=staging`, lalu samakan di Connect |

R2–R4 tidak dapat mengenai produksi karena preflight sudah memastikan jalurnya berbeda, dan
preflight **dijalankan ulang** sebelum tap instalasi divalidasi. Tidak ada langkah rollback yang
menyentuh `~/alim-app`, `.env` produksi, atau database produksi.

## 6. Yang belum bisa dikerjakan

Instalasi **tidak** dijalankan karena P1–P3 dan P5 belum ada. Preflight saat ini berhenti tepat di:

```
[STOP] https://alim-staging.abuhurairah.id/ tidak dapat dijangkau (DNS/TLS belum siap).
```

Sembilan pemeriksaan isolasi sebelum titik itu sudah lulus semua, termasuk "docroot berada di domain
staging", "app-dir terpisah dari produksi", dan "profil tidak menunjuk domain produksi".
