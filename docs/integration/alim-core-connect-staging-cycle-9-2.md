# Serah-Terima Staging Core untuk ALIM Connect — Cycle 9.2

**Tanggal:** 2026-11-24 · **Repo:** ALIM Core (`alim`) · **Branch:** `main`
**Status:** profil staging **siap dipasang**, tetapi **belum** berjalan di host ber-HTTPS.

Dokumen ini hanya memuat konfigurasi **non-secret** dan daftar blocker. Semua nilai rahasia
diserahkan lewat secret manager, tidak lewat dokumen ini.

---

## 1. Yang sudah siap (terbukti di staging lokal)

| Item | Bukti |
| --- | --- |
| Kontrak approval | HTTP 200; replay identik → `duplicate`; `command_id` sama + payload beda → 409 `command_id_reused` |
| Versioning | `X-ALIM-API-Version: 1` diterima (tersimpan `api_version=1`); `2` → 422 `unsupported_api_version` |
| Kredensial terpisah | key salah → 401; tanpa key → 401; SSO dan Approval memakai secret berbeda |
| Aktor terpetakan | `628…0001` verified → 200; nomor tak terpetakan → 403 `actor_identity_unmapped` |
| Permit pending | 2 permit `pending` untuk aktor tersebut; setelah diputuskan → `approved` |
| Audit Core | `DORMITORY_PERMIT_APPROVED` tercatat dengan `user_id` (bukan null) |
| SSO exchange | kode sekali pakai → 200 + `core_user_id`; token **tidak** ada di body |
| Kebocoran internal | 0 kemunculan `SQLSTATE` / stack trace / path di respons error |

Data uji (staging saja, bukan produksi):

| Jenis | Nilai |
| --- | --- |
| Akun personel | `kepala.asrama@staging.invalid` (Kepala Asrama uji) |
| Akun SSO | `sso.penguji@staging.invalid` (berizin), `sso.tanpaizin@staging.invalid` (tanpa izin) |
| Aktor WhatsApp | `628…0001`, terverifikasi, `core_user_id` tercatat di `connect_actor_identities` |
| Permit | 2 permit `pending` (satu aktif, satu kedaluwarsa) |
| Kode handoff | diterbitkan per uji lewat `php artisan alim:connect-staging-handoff --env=staging` |

## 2. Artefak profil staging (sudah dibuat)

`scripts/build-connect-staging-profile.sh` menghasilkan `.env.staging` ber-`APP_KEY` staging
dan seluruh `CONNECT_*`, lalu **memverifikasi kelengkapan tanpa mencetak nilai** (hanya panjang
dan fingerprint SHA-256 12 karakter).

Pemakaian setelah host tersedia:

```
scripts/build-connect-staging-profile.sh \
  --stage-url      https://<host-staging> \
  --connect-origin https://<host-connect> \
  --connect-handoff https://<host-connect>/sso/handoff \
  --connect-egress <CIDR egress Connect> \
  --out /tmp/alim-staging-profile
```

Skrip menolak: URL non-HTTPS, secret < 32 karakter, SSO == Approval, dan `REQUIRE_HTTPS` bukan `true`.

## 3. Konfigurasi non-secret yang akan dipakai

| Variabel | Nilai |
| --- | --- |
| `APP_ENV` | `staging` |
| `APP_URL` | `https://<host-staging>` |
| `DB_CONNECTION` | `sqlite` (berkas terpisah dari produksi) |
| `LOG_CHANNEL` | `staging` (tidak menumpuk ke log produksi) |
| `CONNECT_SSO_ALLOWED_IPS` | CIDR egress Connect |
| `CONNECT_APPROVAL_ALLOWED_IPS` | CIDR egress Connect |
| `CONNECT_SSO_FRAME_ORIGIN` | origin Connect (`https://<host-connect>`) |
| `CONNECT_SSO_HANDOFF_URL` | `https://<host-connect>/sso/handoff` |
| `CONNECT_SSO_REQUIRE_HTTPS` | `true` |
| `CONNECT_APPROVAL_REQUIRE_HTTPS` | `true` |
| `X-ALIM-API-Version` | `1` |

Header kontrak: `X-ALIM-CONNECT-SSO-KEY`, `X-ALIM-CONNECT-APPROVAL-KEY`,
`Idempotency-Key` = `command_id`, `x-alim-connect-ingest-key` (Connect → arah masuk).

## 4. Yang belum siap — dan mengapa

| # | Blocker | Pemilik | Tindakan |
| --- | --- | --- | --- |
| B1 | **Tidak ada host staging ber-HTTPS** | pengelola hosting | Buat subdomain (mis. `staging.alim.abuhurairah.id`), arahkan document root ke `public/`, terbitkan sertifikat TLS |
| B2 | CIDR egress Connect | operator Connect | Berikan rentang IP agar allowlist terisi |
| B3 | Origin & URL handoff Connect | operator Connect | Konfirmasi `https://<host-connect>` dan path `/sso/handoff` |
| B4 | Penyerahan kunci ke Connect | operator Connect | Terima lewat secret manager, bukan chat |

**Bukti B1:** DNS `staging.alim.abuhurairah.id` belum ada (NXDOMAIN). Server hosting bisa ditulis
(home dan `~/domains` writable, PHP 8.4 dengan ekstensi SQLite), jadi hambatannya **pembuatan
subdomain + sertifikat**, bukan akses berkas.

Staging lokal (HTTP + SQLite di `127.0.0.1`) **tidak** dianggap setara staging hosting: tanpa TLS,
`REQUIRE_HTTPS` tidak bisa `true`, dan allowlist IP tidak bermakna.

## 5. Tindakan pengelola hosting (konkret)

1. Buat subdomain `staging.alim.abuhurairah.id` pada domain `abuhurairah.id`.
2. Arahkan document root ke `public/` (bukan `public_html` yang dipakai produksi).
3. Terbitkan sertifikat TLS (Let's Encrypt atau panel).
4. Pastikan subdomain memakai direktori dan database **terpisah** dari produksi.
5. Konfirmasi bahwa PHP 8.4 + ekstensi `pdo_sqlite` aktif di subdomain tersebut.

Setelah 1–5 selesai, pemasangan berjalan begini (belum dilakukan, menunggu nomor di atas):

```
# di server, di direktori staging (bukan ~/alim-app milik produksi)
php artisan migrate --force
php artisan db:seed --class=ConnectStagingTestDataSeeder
php artisan alim:verify-connect-schema
php artisan alim:connect-secret verify        # lewat environment proses, bukan .env repo
```

## 6. Yang tidak boleh dilakukan

- Migrasi atau seeder di produksi: seeder menolak berjalan bila `APP_ENV` bukan `staging`.
- Menyalin `database/staging.sqlite` ke server produksi.
- Menaruh kunci di repository, dokumen ini, atau chat.
- Mengubah kontrak API untuk menyesuaikan hasil uji.

## 7. Catatan temuan pada runner Connect (di luar repo Core)

Saat uji nyata, tiga cacat ditemukan di runner Connect yang membuat laporan "lulus" tidak dapat
dipercaya. Sudah dilaporkan terpisah dan **bukan** bagian dari repo Core:

1. `ALIM_CORE_BASE_URL` ditimpa alamat mock sebelum bagian "Core sungguhan" membacanya.
2. `ALIM_CORE_APPROVAL_API_KEY` / `ALIM_CONNECT_SSO_KEY` ditimpa kunci internal test.
3. `:lane` keluar 0 walau tidak ada satu pun request yang keluar.

Karena itu, bukti di §1 diambil langsung dengan HTTP ke Core, bukan dari keluaran runner.
