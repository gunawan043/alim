# Remediasi ALIM Core — Production Safety & Connect Integration, Siklus 1

**Tanggal:** 2026-11-25 · **Repo:** ALIM Core (`~/Documents/@ALIM PROJECT/alim`)
**Branch:** `main` @ `e45a7d9c` · **Sifat:** implementasi di dalam repository, **belum di-commit**
**Agen paralel:** laporan ini hanya mencakup repo Core. Repo Connect tidak disentuh dan klaim Connect
**tidak** diambil dari laporan ini.

## Ringkasan

Dua penjaga destruktif dipasang pada jalur deployment yang **benar-benar aktif**, dan satu command
pemetaan aktor dibuat. Tidak ada konfigurasi, data, atau deployment production yang disentuh.

| Prioritas | Status | Bukti |
|---|---|---|
| 1 — Lindungi production | **Selesai (kode)** | 2 guard terpasang, `bash -n` lulus, urutan diverifikasi |
| 2 — Konfigurasi integrasi | **Rancangan + satu celah kode ditemukan** | Allowlist kosong = terbuka; lihat §5 |
| 3 — Permission | **Selesai (audit)** | Terdefinisi benar; tidak dijalankan di production |
| 4 — Command pemetaan aktor | **Selesai (kode)** | Command terdaftar, validasi diuji, DB tidak berubah |
| 5 — Migration & kontrak | **Terverifikasi** | 4 Ran/0 pending; normalisasi & idempotensi diuji |

---

## 1. Temuan yang sudah diverifikasi

### 1.1 Ada **dua** jalur destruktif, bukan satu

Laporan sebelumnya hanya menyebut `deploy.sh` (`git reset --hard`). Audit hari ini menemukan jalur
kedua yang **lebih berbahaya** karena sudah terpasang aktif:

| Jalur | Mekanisme | Status aktif | Akibat pada production |
|---|---|---|---|
| **A. GitHub webhook** → `deploy.sh` | `git fetch` + **`git reset --hard origin/main`** | Rute `/webhook/deploy` terdaftar di production, HMAC-SHA256 aktif | Menghapus **semua** 6.830 berkas |
| **B. `git push` lokal** → `pre-push` hook → `scripts/auto-deploy-hostinger.sh` | `git archive` + **`rsync --delete`** | **Hook terpasang** (`.git/hooks/pre-push`, 22 Nov 15:56) | Menghapus berkas server yang tidak ada di commit |

Bukti jalur B:

```
scripts/auto-deploy-hostinger.sh:84   RSYNC_FLAGS=(-az --partial --delete --timeout=120)
scripts/auto-deploy-hostinger.sh:118  rsync ... "${EXPORT_DIR}/" "alim-deploy:alim-app/"
.git/hooks/pre-push                   → memanggil script itu dengan nohup setelah tiap push
```

Keduanya tidak bergantung pada bekerja di branch benar: `deploy.sh:15` `BRANCH="main"` dan
`DEPLOY_BRANCH=main`, sementara worktree production berada di branch `master`.

### 1.2 Inventaris 6.830 berkas (Prioritas 1, butir 7)

| Kategori | Jumlah | Contoh / catatan |
|---|---|---|
| **Hasil build** | **6.209** | Seluruh `public/build/**`; aset dashboard yang tidak dapat dibangun ulang dari commit |
| **Sumber aplikasi berubah** | **420** | `resources/views` 217, `app/Http` 83, `app/Services` 33, `tests/Feature` 26, `app/Models` 21 |
| **Berkas baru** | **192** | `database/migrations` 35, `resources/views` 34, `tests/Feature` 23, `app/Http` 23, `app/Services` 16, `app/Console` 13 |
| **Berkas terhapus** | **9** | 2 kode (`RemindReturnPermitsCommand`, `LjkController` + testnya), 6 `CHANGELOG.md` vendor, 1 lain |
| **Migration (baru)** | **35** | 5 di antaranya integrasi Connect |
| **Konfigurasi** | **8** | `.env.example`, `config/{dashboard/asrama,ljk,logging,sanctum,services,whatsapp}.php` |
| **Berpotensi dibuat proses deploy** | **6.209** | `public/build/**` — satu-satunya kategori yang jelas berasal dari mesin build, bukan tulisan tangan |

**Temuan kritis tambahan:** **seluruh 35 migration untracked sudah tercatat `Ran` di ledger
production.** Diverifikasi satu per satu. Artinya `git reset --hard` atau `rsync --delete` akan
menghapus berkas migration yang **sudah dijalankan** — meninggalkan tabel nyata tanpa sumber, dan
`migrate:status` production menjadi palsu. Ini risiko yang lebih berat dari sekadar "kehilangan
berkas".

### 1.3 Yang tidak dapat saya pastikan

Pemilik 6.830 berkas **tidak dapat ditentukan dari artefak**: tidak ada commit, tidak ada hook
server (`.git/hooks/` kosong di server), dan `/tmp/alim-auto-deploy.log` tidak ada. Ini kebutuhan
keputusan manusia, bukan analisis teknis.

---

## 2. Perubahan kode yang benar-benar dilakukan

### 2.1 `deploy.sh` — penjaga worktree sebelum `git reset --hard`

Ditambahkan sebelum langkah `[1/7]`, dan sebelum `START_TIME`:

- menghitung berkas **terlacak** yang berubah/terhapus (`git status --porcelain --untracked-files=no`);
- menghitung berkas **baru** yang tidak diabaikan `.gitignore`;
- bila salah satu > 0 → cetak rincian, arahkan ke dokumen ini, **`exit 3` sebelum satu byte terhapus**;
- memverifikasi `origin/${BRANCH}` ada, karena `reset --hard` ke ref yang tidak ada gagal separuh jalan.

**Sengaja tidak ada override lewat environment variable.** Jalur "override cepat" justru yang akan
dipakai saat panik. Melewati penjaga harus lewat keputusan sadar (ubah kode).

Verifikasi:

```
$ bash -n deploy.sh          → syntax OK
guard line 71-80  ← exit 3
reset  line 99    ← reset --hard (di bawah guard)
```

### 2.2 `scripts/auto-deploy-hostinger.sh` — penjaga **remote** sebelum `rsync --delete`

Ditambahkan setelah `sleep`, sebelum rsync:

- membaca `git status --porcelain | wc -l` **di server** lewat SSH;
- bila pembacaan gagal → **batalkan** (gagal-tertutup: tidak bisa memverifikasi = tidak jalan);
- bila > 0 → `exit 3` sebelum rsync mengirim apa pun.

Verifikasi:

```
$ bash -n scripts/auto-deploy-hostinger.sh   → syntax OK
guard line 51-68  ← exit 3
rsync line 118    ← setelah guard
```

### 2.3 `app/Console/Commands/ConnectActorMap.php` — **berkas baru**

Perintah `alim:connect-actor-map` dengan empat mode:

| Mode | Sifat | Perilaku |
|---|---|---|
| `--inspect` | baca-saja | Daftar pemetaan; menandai baris yang **tidak** dipakai `ApprovalCommandService` (`is_active` / `verified_at` kosong) |
| `--create` | mutasi | Tolak bila nomor sudah dipetakan (cegah ganda lewat `unique(channel, external_identity)`) |
| `--update` | mutasi | Ubah personel/tampilan/status; menampilkan peringatan bila personel diganti |
| `--revoke` | mutasi | `is_active=false`; baris **dipertahankan** sebagai jejak, bukan dihapus |

Kontrol yang dipasang:

| Kontrol | Implementasi |
|---|---|
| Validasi E.164 | Normalisasi lewat `ConnectActorIdentity::normalizeIdentity()` (model yang sama dengan runtime), lalu regex `^[1-9][0-9]{7,14}$` |
| Validasi `core_user_id` | `User::find()`; tolak bila tidak ada atau `is_active=false` (mencegah `actor_inactive`) |
| **Tanpa bypass RBAC** | Memanggil `DormitoryPolicyAccess::canApprovePermitCommand()` — fungsi yang **sama persis** dengan jalur command |
| Audit trail | Setiap mutasi menulis `AuditLog` (`CONNECT_ACTOR_IDENTITY_MAPPED/UPDATED/REVOKED`) dengan `before`/`after`, operator, dan fingerprint SHA-256 |
| Data sensitif | Nomor dicetak **masked** (`…0001`); audit menyimpan fingerprint hash, bukan nomor asli |
| Validasi lingkungan | Menolak `APP_ENV=production` tanpa `--allow-production` |
| Konfirmasi eksplisit | Mutasi butuh `--force` atau jawaban `confirm()` interaktif |
| Operator wajib | `--operator=<email>` wajib untuk semua mutasi; harus user aktif; tanpa itu tidak ada penulisan |

Verifikasi yang sudah dijalankan (lokal, **tidak menulis ke DB**):

```
$ php -l app/Console/Commands/ConnectActorMap.php   → No syntax errors
$ php artisan list | grep connect-actor-map         → terdaftar
$ php artisan alim:connect-actor-map --inspect      → "Belum ada pemetaan identitas Whatsapp."
$ php artisan alim:connect-actor-map --create --revoke → "Pilih tepat satu mode"
$ --number=123                    → rejected (E.164)
$ --number="+62 812-3456-7890"    → dinormalisasi ke 6281234567890
$ --number=6281234567890123456    → rejected (>15 digit)
$ --number=abc                    → rejected
```

Bukti DB tidak berubah: `connect_actor_identities` tidak ada di DB lokal (0 tabel), dan
`database/database.sqlite` tetap 0 byte.

---

## 3. Daftar berkas berubah

| Berkas | Jenis | Baris efektif |
|---|---|---|
| `deploy.sh` | diubah | +42 (guard) |
| `scripts/auto-deploy-hostinger.sh` | diubah | +28 (guard) |
| `app/Console/Commands/ConnectActorMap.php` | **baru** | ~400 |
| `docs/integration/alim-core-production-remediation-cycle-1.md` | **baru** | laporan ini |

**Tidak di-commit** sesuai batasan. Tidak ada push, merge, atau deployment.

---

## 4. Risiko yang masih terbuka

| # | Risiko | Tingkat | Status |
|---|---|---|---|
| R1 | Penjaga hanya mencegah **jalur di repo**. Eksekusi manual `git reset --hard` / `rsync --delete` oleh manusia tetap bisa menghancurkan production | **Kritis** | Terbuka — butuh persetujuan & tindakan pemilik |
| R2 | 6.209 berkas `public/build` tidak dapat dibangun ulang dari commit mana pun (`npm` tidak tersedia di server) | Tinggi | Terbuka |
| R3 | 35 migration untracked **sudah Ran**; hilangnya berkas membuat ledger yatim | Tinggi | Terbuka |
| R4 | Branch worktree production `master` ≠ branch deploy `main` | Sedang | Terbuka — keputusan pemilik |
| R5 | Pemilik 6.830 berkas tidak teridentifikasi | Tinggi | Butuh keputusan manusia |
| R6 | Command baru belum punya test otomatis di repo (pengujian lokal tidak saya jalankan karena belum diminta) | Sedang | Terbuka |

---

## 5. Celah kode pada Prioritas 2 — allowlist kosong **tidak** fail-closed

Instruksi mensyaratkan "allowlist kosong harus fail-closed". **Kode saat ini tidak begitu.**

```php
// app/Http/Middleware/VerifyIntegrationApiKey.php:83
if ($allowedIps !== [] && ! IpUtils::checkIp((string) $request->ip(), $allowedIps)) {
```

Allowlist kosong → kondisi pertama `false` → **seluruh pemeriksaan IP dilewati**. Artinya integrasi
menerima request dari IP mana pun yang memegang key.

Saya **tidak mengubah** perilaku ini, karena:

1. Mengubahnya menjadi fail-closed akan **mematikan integrasi lain** yang memakai middleware yang sama
   (POS, Recruitment) dan mungkin sengaja dikonfigurasi tanpa allowlist.
2. Itu perubahan kontrak operasional yang butuh persetujuan pemilik.

**Rekomendasi:** tambahkan flag konfigurasi terpisah (mis. `*_ALLOW_ANY_IP=false` default) khusus
`connect_sso` dan `connect_approval`, bukan mengubah semantik global. Butuh keputusan Anda.

Sampai itu ada, mitigasinya operasional: **isi `CONNECT_*_ALLOWED_IPS`** agar jalur ketat aktif.

### Rancangan konfigurasi (nama variabel, tanpa nilai)

```
CONNECT_SSO_API_KEY=<kunci A, production>
CONNECT_SSO_ALLOWED_IPS=<CIDR egress Connect>       ← WAJIB terisi
CONNECT_SSO_HANDOFF_URL=https://<host-connect>/api/sso/handoff
CONNECT_SSO_HANDOFF_TTL=120
CONNECT_SSO_REQUIRE_HTTPS=true

CONNECT_APPROVAL_API_KEY=<kunci B, HARUS ≠ kunci A>
CONNECT_APPROVAL_ALLOWED_IPS=<CIDR egress Connect>  ← WAJIB terisi
CONNECT_APPROVAL_REQUIRE_HTTPS=true
CONNECT_APPROVAL_MAX_DURATION_DAYS=30
```

**IP egress Connect tidak saya tebak.** `91.108.119.178` adalah alamat **masuk** domain Connect,
bukan egress; SSH ke host itu tidak tersedia untuk membuktikannya.

---

## 6. Prosedur rollback

### 6.1 Rollback perubahan kode siklus ini

| Berkas | Cara | Verifikasi |
|---|---|---|
| `deploy.sh` | `git checkout -- deploy.sh` (belum di-commit) | `grep -c GUARD_BLOCKED deploy.sh` → `0` |
| `scripts/auto-deploy-hostinger.sh` | `git checkout -- scripts/auto-deploy-hostinger.sh` | `grep -c REMOTE_DIRTY` → `0` |
| `ConnectActorMap.php` | hapus berkas | `php artisan list` tidak lagi memuatnya |

**Peringatan:** rollback `deploy.sh` **mengembalikan** perilaku destruktif. Rollback hanya boleh
dilakukan setelah worktree production bersih, bukan untuk "sementara supaya bisa deploy".

### 6.2 Rollback pemetaan aktor (bila nanti dibuat)

`alim:connect-actor-map --revoke` — baris tidak dihapus sehingga `ApprovalCommand` historis tetap
punya rujukan. Bila baris harus hilang sepenuhnya (mis. salah input belum dipakai), hapus manual
**hanya** setelah memastikan tidak ada `approval_commands.actor_external_identity` yang menunjuk ke
nilai itu.

### 6.3 Rollback deployment production

Tidak ada release-directory scheme (`KEEP_RELEASES=5` dideklarasikan di `deploy.sh` tapi tidak
dipakai). Karena itu **tidak ada rollback otomatis**: pemulihan bergantung pada salinan penuh
`~/alim-app`. Ambil salinan itu **sebelum** deploy berikutnya.

---

## 7. Persyaratan sebelum konfigurasi production

1. Keputusan pemilik: status 6.830 berkas (commit ke branch mana, atau dibuang).
2. Salinan penuh `~/alim-app` terverifikasi (sha256).
3. Cabang `main` dan `master` disatukan atau deploy dipindah ke `master` — tidak boleh deploy dari
   branch yang bukan branch worktree.
4. Kunci SSO dan Approval diterbitkan terpisah; tidak ada nilai yang ditulis di repo/dokumen.
5. `CIDR egress Connect` dibuktikan dari host Connect.
6. Keputusan atas celah allowlist (§5): isi allowlist, atau setujui perubahan middleware.
7. `config:cache` dijalankan ulang **setelah** `.env` diubah (cache production saat ini lebih baru
   dari `.env`, sehingga perubahan `.env` tidak terbaca).
8. Jalankan `PermissionSeeder` (production belum punya `whatsapp_connect_view`).

## 8. Persyaratan sebelum pengujian transaksi production

1. Connect production harus sudah memuat modul approval & SSO — saat pemeriksaan terakhir
   `POST /api/approval/requests` mengembalikan `404`, artinya modul belum ada di instance tersebut.
   **Klaim ini milik agen Connect dan harus dikonfirmasi ulang olehnya**, bukan diambil dari laporan ini.
2. Akun penguji ditunjuk pemilik sistem (saya tidak menunjuk), sudah diberi `whatsapp_connect_view`.
3. Satu pemetaan aktor dibuat dengan `--operator` yang sah dan `--dormitory` yang kewenangannya
   terverifikasi.
4. Permit uji **bukan** permit produksi nyata; status dan batas kedaluwarsanya diketahui.
5. Persetujuan eksplisit Anda untuk transaksi production — **belum diberikan**.

---

## 9. Blocker yang butuh keputusan pemilik production

1. **Pemilik 6.830 berkas** dan izin untuk meng-commit-nya.
2. **Izin menonaktifkan jalur auto-deploy** (hook lokal) sampai worktree bersih.
3. **Celah allowlist** (§5): isi allowlist atau ubah middleware.
4. **Penyatuan `main`/`master`**.
5. **Akun & nomor penguji** untuk pemetaan aktor.

Tidak ada commit, push, merge, deployment, perubahan konfigurasi production, atau transaksi
production dalam pekerjaan ini.
