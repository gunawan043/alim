# Siklus 12 — Laporan Status Persiapan Integrasi ALIM Core ↔ ALIM Connect

**Tanggal:** 2026-11-25 · **Repo:** ALIM Core · **APP_ENV staging** (`http://127.0.0.1:8001`)
**Menjawab:** `~/OpenWA/docs/integration/alim-connect-cycle-12-core-request.md` §5
**Tidak ada:** perubahan data produksi, perubahan kontrak API, commit/push, kode SSO atau kunci layanan di dokumen ini.

---

## Ringkasan status per butir

| #   | Butir                          | Status         | Sebab singkat                                                                              |
| --- | ------------------------------ | -------------- | ------------------------------------------------------------------------------------------ |
| A   | Nomor WhatsApp uji terverifikasi | **SIAP**     | `6280000000001` — dipetakan & `verified_at` terisi di staging                                |
| B   | `core_user_id` personel        | **SIAP**       | `bb5d5499-ca08-4c31-99c8-39e96430cc35`, aktif                                               |
| C   | Kewenangan Kepala Asrama       | **SIAP**       | Kepala Asrama aktif pada asrama permit uji (dibuktikan lewat `canApprovePermitCommand`)      |
| D   | Satu permit terbuka            | **SIAP (Core) / MENUNGGU (Connect)** | Permit `112ba9cb-…` `pending`; pengiriman ke Connect belum bisa — API Connect tidak berjalan |
| E   | Kode SSO baru                  | **BELUM DITERBITKAN** (disengaja) | TTL 60 detik; diterbitkan saat Connect siap menerima (permintaan §1.3)                   |
| F   | Migration & ref git            | **SIAP dengan catatan** | Migration Connect `Ran`; 2 migration non-Connect `Pending`; HEAD = `origin/main` = `bc93bda8` |

Bentuk `actor_external_id` yang diharapkan Core: **digit E.164 tanpa `+`** (`6280000000001`). Tambahan `+`,
spasi, `08…`, atau `@s.whatsapp.net` tetap normalisasi ke bentuk yang sama
(`ConnectActorIdentity::normalizeIdentity()`), jadi tidak ada perubahan kontrak.

---

## A + B + C — Aktor staging

### Bukti dari database staging (baca-saja)

```
connect_actor_identities
  external_identity = 6280000000001
  channel           = whatsapp
  is_active         = 1
  verified_at       = 2026-11-23 17:51:03
  user_id           = bb5d5499-ca08-4c31-99c8-39e96430cc35
  display_name      = Kepala Asrama Uji (WA staging)

users
  id        = bb5d5499-ca08-4c31-99c8-39e96430cc35
  name      = Kepala Asrama Uji
  email     = kepala.asrama@staging.invalid
  is_active = 1
```

Nomor ini **nomor sintetis staging** (`628…0001`), bukan nomor orang yang bekerja. Tidak ada
`dormitory_staff_assignments` untuk personel ini — kewenangan berasal dari `dormitories.head_id`
(`DormitoryPolicyAccess::canApprovePermitCommand()`, `app/Services/Boarding/DormitoryPolicyAccess.php:53`),
salah satu dari dua jalur yang diterima jalur command.

### Verifikasi rantai otorisasi (jalankan di staging, tanpa menulis)

`php artisan tinker --env=staging` + `canApprovePermitCommand()`:

```
identity_active=yes
verified_at=2026-11-23 17:51:03
user_id=bb5d5499-ca08-4c31-99c8-39e96430cc35
user_active=yes
dorm_head_is_actor=yes
canApprovePermitCommand=yes
permit_status=pending  secondary=NULL
expected_return_in_future=yes
```

Catatan: `ApprovalCommandService::resolveActor()` menulis `last_seen_at`, jadi rantai di atas diverifikasi
dengan query setara dan **tidak** dengan mengirim command — agar uji nyata Connect tetap bersih.

### Kewenangan (jawaban butir C)

Kepala Asrama **Asrama Uji Connect (staging)** (`dormitories.id = bda4039e-a59c-4cc0-a857-acf6da441069`,
`is_active = 1`, `deleted_at` NULL). **Boleh menyetujui/menolak** izin di asrama itu: **ya**.

---

## D — Permit uji

| Hal          | Nilai                                                              |
| ------------ | ------------------------------------------------------------------ |
| `request_id` | `112ba9cb-3fc8-4cce-8c28-14bc6eeac840`                             |
| Asrama       | `bda4039e-a59c-4cc0-a857-acf6da441069` — Asrama Uji Connect        |
| Santri       | Santri Uji Connect (staging)                                        |
| Jenis        | `pulang`, tujuan "Rumah (uji staging)"                              |
| Berangkat    | 2026-11-23 19:51:03                                                 |
| Kembali (rencana) | **2026-11-26 01:17:03**                                        |
| Status       | `pending`, `secondary_status` NULL — **terbuka**                    |

**Peringatan waktu:** permit ini berhenti dapat diputuskan setelah 2026-11-26 01:17 (Core menjawab
`409 permit_expired`). Uji approval harus selesai sebelum itu, atau Core membuatkan permit baru.

Permit kedua (`72d9991e-…`) sengaja **tidak** dipakai: waktu kembalinya sudah lewat (kontrol negatif
`permit_expired`).

### Pengiriman ke Connect — belum bisa dikonfirmasi

Cara 1 (Core mengirim ke `POST /api/approval/requests`) **belum dijalankan** karena:

1. ALIM Core **tidak memiliki klien keluar** ke Connect — tidak ada route, command, atau service yang
   memanggil Connect. Integrasi ini searah (Connect → Core). Mengirim permit adalah pekerjaan baru, bukan
   konfigurasi.
2. **API Connect tidak berjalan** saat pemeriksaan: `127.0.0.1:2785` dan `127.0.0.1:2886` → `000`
   (tidak ada listener). Ingest tidak bisa menerima apa pun.
3. Payload `POST /approval/requests` (`IngestApprovalRequestDto`) membutuhkan `reference`, `studentName`,
   `permitType` — Core belum menyimpan semua itu dalam bentuk yang siap kirim.

Karena itu saya memakai **Cara 2**: `request_id` di atas sudah dikirim ke dokumen balasan. Andalkan
permit ini apa adanya — **jangan** kirim ulang lewat ingest dengan `coreRequestId` yang sama sebelum
sepakat, karena Connect menganggap ingest idempoten per `coreRequestId` dan penolakan subjek akan
mengunci permit itu.

---

## E — Kode SSO (belum diterbitkan, sesuai permintaan)

Permintaan §1.3 minta kode diterbitkan **saat uji siap**, bukan sebelumnya. `CONNECT_SSO_HANDOFF_TTL=60`
detik. Kode diterbitkan dengan:

```
php artisan alim:connect-staging-handoff --env=staging --user=sso.penguji@staging.invalid
```

Pengguna SSO yang sudah siap (diverifikasi): `7b07d977-7b2e-497c-b60f-59a7a6359ff2`
(`sso.penguji@staging.invalid`), aktif, pemegang langsung `whatsapp_connect_view`. **`core_user_id` yang
diharapkan: `7b07d977-7b2e-497c-b60f-59a7a6359ff2`** — beda dari aktor approval (B); memang dua akun uji
berbeda dan itu disengaja.

Saat ini ada 1 handoff lama yang belum dipakai (dari 2 total) — tidak dipakai ulang; kode diperlakukan
sekali pakai.

---

## F — Migration & ref git

| Hal                     | Bukti                                                                                     |
| ----------------------- | ----------------------------------------------------------------------------------------- |
| `HEAD`                  | `bc93bda8524bc181507f1d9c69c1de1364aac182`                                                |
| `origin/main`           | `bc93bda8524bc181507f1d9c69c1de1364aac182` (**sama**, ahead/behind 0/0)                    |
| `git describe`          | `post-laravel-upgrade-2026-10-21-398-gbc93bda8-dirty` (7 berkas kerja, non-Connect)        |
| Schema Connect staging  | `alim:verify-connect-schema --env=staging` → `connect_actor_identities` OK (6 kolom), `approval_commands` OK (14 kolom), `connect_sso_handoffs` OK (9 kolom) — **"Skema integrasi ALIM Connect lengkap"** |
| Migration Connect       | `2026_11_22_000003_create_connect_sso_handoffs_table` → **Ran**; `2026_11_23_000001_add_contract_headers_to_approval_commands_table` → **Ran** |
| Migration tertunda (2)  | `create_student_card_settings_table`, `create_admin_leger_presensi_table` → **Pending**, keduanya **bukan** Connect |

**Migration staging sudah dijalankan (ya).** Schema mandiri ini terpisah dari produksi; menjalankan dua
migration tertunda tidak akan mengubah perilaku Connect, tetapi saya **tidak** menjalankannya karena di
luar lingkup siklus ini dan bukan kewenangan saya memutuskan urutan rilis.

---

## Batasan yang dipatuhi

- Tidak ada perubahan data produksi (semua pemeriksaan di `database/staging.sqlite` dan HTTP staging).
- Tidak ada identitas/nomor dipakai tanpa verifikasi: semua nilai di atas dibaca dari database staging.
- Tidak ada kode SSO atau kunci di dokumen ini, commit, atau chat.
- Tidak ada perubahan kontrak API. Bentuk `actor_external_id` tetap digit E.164 tanpa `+` dan Core
  menormalisasi varian `+`/`08`/JID.

## Keputusan kesiapan

**Aktor approval siap (A, B, C). Permit siap di Core (D) dan menunggu diterima Connect.** SSO siap
diterbitkan kapan pun (E). Migration terkonfirmasi dengan catatan (F).

Yang menahan hanya dua hal, keduanya di sisi Connect: **API Connect belum berjalan**, jadi tidak ada
penerimaan permit yang bisa dikonfirmasi.

## Apakah persiapan VPS staging (siklus 9.6) boleh dilanjutkan?

Boleh, dan siklus ini justru menambah satu alasan: seluruh uji Connect → Core berjalan lewat
`127.0.0.1:8001`. Selama keduanya berada di satu mesin, uji bersandar pada instance lokal. VPS staging
memindahkan Core ke host terpisah sehingga jalur HTTP, allowlist IP, dan minimalisasi `/health` diuji
seperti produksi. Syarat 9.6 tetap berlaku, terutama verifikasi nama socket php-fpm sebelum `--install`.
