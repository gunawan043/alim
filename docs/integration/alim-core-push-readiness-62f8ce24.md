# Kelayakan Push — Audit `62f8ce24` dan Status Deployment SSO

Tanggal: 2026-11-25. Lingkup: repo Core saja. Connect tidak diubah.

---

## 0. Temuan yang mengubah premis tugas

Permintaan ini berasumsi kedua commit **belum** di-push. Kenyataannya, saat audit dijalankan:

```
local main      = 11cefb305d34f366b42df5adf9098bf53970fe26
origin/main     = 11cefb305d34f366b42df5adf9098bf53970fe26   (identik)
```

**Kedua commit sudah berada di `origin/main`.** Dibuktikan dengan `git merge-base --is-ancestor`:

| Commit | Ancestor `origin/main` |
|---|---|
| `62f8ce24` feat(mapel) | YA |
| `67a24a7a` feat(connect) SSO | YA |

`git log --oneline origin/main..HEAD` mengembalikan kosong. Reflog `main` menunjukkan commit lain masuk setelahnya (`7d970fda` perpustakaan, `11cefb30` .htaccess) oleh proses lain.

**Implikasi:** push yang diminta dalam tugas ini sudah terjadi, bukan oleh saya. Saya tidak menjalankan `git push`, dan sesi ini tidak men-trigger deployment apa pun. Yang perlu diputuskan sekarang bukan *apakah aman push*, melainkan *apakah aman men-deploy* — dan untuk itu ada temuan penting di §4.

---

## 1. Audit commit `62f8ce24` (mapel)

| Atribut | Nilai |
|---|---|
| Author | Wawan Gunawan |
| Waktu | 2026-11-25 12:59:22 +0800 |
| Berkas | `app/Http/Controllers/SubjectController.php` (1 berkas) |
| Diff | +4 / −33 |

**Isi perubahan:** menghapus kebijakan penguncian kolom untuk mapel ber-`source=pondok` pada `SubjectController::update()`.

Yang dihapus:
- Deteksi `$isSuper` dan perhitungan `$lockedFields` (JP / kategori / kelompok).
- Penggabungan balik nilai lama untuk field terkunci.
- Pesan sukses "Perubahan … tidak disimpan karena mapel pondok hanya boleh diganti namanya".

Yang disederhanakan: `$hoursChanged` kini selalu dibandingkan terhadap input, tanpa pengecualian field terkunci.

**Dampak fungsional:** pengguna non-Super Admin kini dapat mengubah JP, kategori, dan kelompok mapel yang berasal dari pondok. Sesuai judul commit, ini disengaja karena subjek sudah terpisah per `school_id` sehingga isolasi antar sekolah tetap terjaga. **Tidak ada perubahan skema, migration, route, permission, atau view** — murni logika otorisasi level field di satu controller.

**Dampak deployment:** rendah dan dapat dibalik. Tidak menyentuh data. Bila terjadi regresi, gejalanya terbatas pada form edit mapel.

**Catatan kualitas kode (bukan blocker):** setelah penghapusan, `$subject = Subject::findOrFail($id)` yang sebelumnya duplikat kini tinggal satu — perbaikan kecil yang ikut terbawa, bukan regresi.

---

## 2. Audit commit `67a24a7a` (SSO Connect)

Sudah dilaporkan di `docs/integration/alim-core-connect-sso-activation.md`. Ringkas untuk kelayakan push:

- 8 berkas, seluruhnya fitur SSO. Tidak ada berkas Perpustakaan.
- Menambah `app/Support/ConnectSsoConfig.php`, tab Pengaturan Sistem, perbaikan CSP, dan 31 test.
- **Tidak ada migration dan tidak ada perubahan skema.** Deployment tidak memicu DDL.
- Kontrak tidak berubah: `POST /api/connect/v1/sso/exchange`, header `X-ALIM-CONNECT-SSO-KEY`.

---

## 3. Apakah ada pekerjaan proses lain yang ikut terpaket?

Diperiksa dengan `comm -12` antara daftar berkas per commit:

- `62f8ce24` ∩ `67a24a7a` = **kosong** (tidak ada berkas yang bertabrakan).
- `67a24a7a` berisi **0** berkas yang cocok dengan pola `perpustakaan|library|InstitutionDecree`.
- `62f8ce24` hanya menyentuh `SubjectController.php`, tidak menyentuh `InstitutionDecreeController.php`.

Kedua commit bersih dan terpisah. Pekerjaan Perpustakaan (`7d970fda`) adalah commit tersendiri yang lebih baru — bukan bagian dari kedua commit yang diminta diaudit, dan tidak mungkin tercampur karena sudah terpisah di level commit.

**Working tree lokal:** `git status --porcelain` → **0 berkas**. Tidak ada perubahan yang menggantung. Saya tidak menjalankan reset/stash/clean kapan pun di sesi ini.

---

## 4. Status deployment sebenarnya — TEMUAN UTAMA

Push sudah terjadi, tetapi **deployment tidak**. Kedua commit tidak sampai ke production.

| Pemeriksaan | Hasil |
|---|---|
| HEAD production | `4e057f5e` (bukan `11cefb30`) |
| Branch production | `master` |
| Berkas dirty di host | 10 |
| `app/Support/ConnectSsoConfig.php` | **tidak ada** di server |

Log auto-deploy lokal menjelaskan sebabnya:

```
[2026-11-25 14:30:33] AUTO DEPLOY SELESAI (7b5b2d59)
[2026-11-25 15:02:58] ERROR: worktree server memuat 9 berkas di luar commit.
[2026-11-25 15:02:58] ERROR: rsync --delete akan MENGHAPUS berkas tersebut; deploy dibatalkan.
[2026-11-25 16:34:25] ERROR: worktree server memuat 9 berkas di luar commit.
[2026-11-25 16:34:25] ERROR: rsync --delete akan MENGHAPUS berkas tersebut; deploy dibatalkan.
```

**Penjaga remote bekerja sesuai rancangan.** Dua kali percobaan deploy otomatis (15:02 dan 16:34) dibatalkan karena worktree server memuat 9 berkas di luar commit. Commit terakhir yang benar-benar live adalah `7b5b2d59`.

Artinya seluruh rangkaian setelah `7b5b2d59` **belum tayang**:
- `67a24a7a` — SSO
- `7d970fda` — Perpustakaan
- `11cefb30` — .htaccess

Ini bukan kegagalan, melainkan perlindungan yang berfungsi. Yang dipertaruhkan bila penjaga dilonggarkan: `rsync --delete` akan menghapus 9–10 berkas milik orang lain di server (termasuk judul proses `InstitutionDecreeController.php` bila berada di host), dan `git reset --hard origin/main` pada `deploy.sh:99` akan menghapus 35 migration yang sudah tercatat `Ran` di ledger sehingga migrasi menjadi yatim.

---

## 5. Jalur deployment otomatis dan kondisinya

| Jalur | Pemicu | Langkah merusak | Status penjaga |
|---|---|---|---|
| A | `POST /webhook/deploy` → `deploy.sh` | `git reset --hard origin/main` (`deploy.sh:99`) | **Berpenjaga** — menolak bila ada berkas terlacak berubah/terhapus atau untracked |
| B | `git push` → `.git/hooks/pre-push` → `scripts/auto-deploy-hostinger.sh` | `rsync --delete` | **Berpenjaga** — preflight membaca `git status --porcelain` di server; gagal baca atau dirty → abort |

Kedua penjaga **tanpa jalur override lewat environment**. Melewatinya harus berupa keputusan eksplisit pemilik production, bukan variabel.

Perbedaan penting: penjaga B memeriksa **kondisi server**, bukan isi commit. Karena itu push tetap berhasil dan tetap memicu deploy — deploy yang kemudian menolak dirinya sendiri. Ini kondisi yang sekarang terjadi dan justru aman: **tidak ada yang tertimpa**.

Satu detail: `11cefb30` menambah `AddHandler application/x-httpd-php84 .php` di `public/.htaccess`. Perubahan ini baru berlaku setelah deploy berikutnya berhasil. Tidak berbahaya, tetapi ikut tayang bersama SSO.

---

## 6. Prasyarat Connect — berubah sejak audit terakhir

Blocker yang saya laporkan sebelumnya di sisi Connect **sudah teratasi**. Bukti HTTP langsung:

```
POST https://alim-wa.abuhurairah.id/sso/handoff            -> 503 (SSO belum aktif)
POST  .../sso/handoff   Origin: https://alim.abuhurairah.id -> 503 (SSO belum aktif)
content-security-policy: ... frame-ancestors 'self' https://alim.abuhurairah.id; ...
(tidak ada header X-Frame-Options)
```

Dua temuan positif:
1. **CSP kini mengizinkan Core sebagai frame-ancestor.** Sebelumnya `'self'` saja yang memblokir iframe. Ini perbaikan nyata di sisi Connect.
2. **`X-Frame-Options` sudah tidak dikirim**, sehingga tidak ada lagi konflik yang memblokir di Chrome/Edge.

Sisa 503 konsisten dengan Connect yang belum menerima `ALIM_CONNECT_SSO_KEY` dan `ALIM_CORE_BASE_URL` — kondisi konfigurasi, bukan kondisi kode. Belum ada yang bisa Core lakukan dari sisi ini.

Catatan yang belum selesai: `GET /api/approval/identities` masih **404**. Ini di luar lingkup SSO dan tidak menghalangi handoff, tetapi menandakan build Connect masih belum sepenuhnya sesuai sumbernya. Perlu diverifikasi pemilik Connect, bukan diasumsikan beres.

---

## 7. Verdict kelayakan

**Kedua commit layak dan sudah naik ke `origin/main`.** Tidak ada alasan teknis untuk menariknya:

| Kriteria | Hasil |
|---|---|
| Berkas tercampur Perpustakaan | Tidak |
| Menyentuh pekerjaan proses lain | Tidak |
| Migration / perubahan skema | Tidak ada |
| Perubahan kontrak integrasi | Tidak ada |
| Test | 31 lulus (SSO) |
| Dapat dibalik | Ya (revert per-commit) |

**Risiko deployment otomatis: terkendali dan sedang aktif menahan diri.** Setiap push memicu deploy, tetapi kedua jalur berhenti sendiri saat server dirty. Selama 9–10 berkas itu ada, tidak ada deploy yang akan berhasil — jadi bekerja pada kode tidak akan tiba-tiba menimpa server.

Konsekuensi yang perlu disadari: **kondisi ini juga memblokir SSO sendiri.** Tanpa deploy, `ConnectSsoConfig` tidak akan ada di server dan tab Pengaturan Sistem tidak akan muncul.

---

## 8. Blocker yang butuh keputusan pemilik production

Tidak satu pun saya selesaikan sendiri, karena semuanya menyentuh berkas milik orang lain.

1. **Kepemilikan 10 berkas dirty di server.** Perlu diputuskan: commit-kan, pindahkan ke luar, atau nyatakan aman dihapus. Tanpa ini tidak ada deploy yang bisa jalan.
2. **`35 migration` di server tercatat `Ran` tetapi tidak ada di commit.** Bila `deploy.sh` (jalur A) suatu saat jalan setelah worktree dibersihkan, ledger menjadi yatim. Jalur B (`rsync`) lebih aman karena tidak melakukan reset.
3. **Ketidaksesuaian branch `main` vs `master`.** Production di `master`, remote punya `main`. Jalur A bergantung pada `origin/${BRANCH}` yang saat ini tidak ada untuk `master` — penjaga memblokirnya, tetapi ini harus dibereskan sebelum deploy massal.
4. **`/api/approval/identities` 404 di Connect.** Perlu verifikasi pemilik Connect.

---

## 9. Prosedur memasukkan `CONNECT_SSO_API_KEY` setelah SSO tayang

Tujuan: key SSO tersimpan tanpa pernah muncul di terminal, log, laporan, atau riwayat shell.

**Fakta yang mendasari prosedur** (diverifikasi dari sumber):

- Key **dibuat di server** oleh `SuperAdmin\SystemSettingController` → `ConnectSsoConfig::storeKey(IntegrationApiConfig::generateKey())`, di mana `generateKey()` = `bin2hex(random_bytes(32))`.
- Penyimpanan: `SystemSetting::set('connect_sso_api_key', Crypt::encryptString($key))` — terenkripsi `APP_KEY`, kolom `integration`.
- **Tidak ada jalur untuk menampilkan ulang key.** UI hanya menampilkan status `isEncrypted` dan waktu pembuatan. Ini disengaja; key bersifat *write-only* bagi Core.
- Rotasi menulis audit `CONNECT_SSO_KEY_ROTATED`; penyimpanan biasa menulis `CONNECT_SSO_SETTINGS_UPDATED`.

### Langkah

1. **Prasyarat** — deploy berhasil dan `app/Support/ConnectSsoConfig.php` ada di server; `APP_KEY` sama dengan yang dipakai saat key di-generate.
2. Buka **Super Admin → Pengaturan Sistem → tab WhatsApp Connect**. Tab hanya muncul bila kode SSO sudah tayang.
3. Isi:
   - `connect_sso_handoff_url` = `https://alim-wa.abuhurairah.id/sso/handoff`
   - `connect_sso_frame_origin` = `https://alim-wa.abuhurairah.id`
   - `connect_sso_api_header` = `X-ALIM-CONNECT-SSO-KEY`
   - `connect_sso_api_allowed_ips` — **biarkan kosong hanya bila server Connect memakai IP tetap**; lihat peringatan di bawah.
4. Klik **Buat key SSO baru** (`regenerate_connect_sso_key=1`). Key di-generate server-side, langsung disimpan terenkripsi.
5. **Penyerahan key ke Connect — satu-satunya langkah yang berisiko.** Core tidak dapat menampilkan key, sehingga key **tidak bisa** disalin dari UI. Dua opsi yang dapat dipertanggungjawabkan:

   - **Opsi A (disarankan):** pemilik Connect membuat key sendiri dan menyimpannya ke `ALIM_CONNECT_SSO_KEY`, lalu key itu dimasukkan ke Core. Namun UI Core hanya menerima *generate*, bukan *input* — sehingga butuh penambahan kolom input. Tidak ada saat ini.
   - **Opsi B:** generate di Core, lalu ambil nilai plaintext di server lewat sesi operator pemilik production dan serahkan ke Connect lewat kanal rahasia. Nilai tidak boleh masuk laporan, log, atau riwayat shell.

   **Kesenjangan ini nyata dan perlu diputuskan sebelum aktivasi:** saat ini Core hanya bisa *membuat* key, sementara Connect butuh *nilai* key yang sama. Saya tidak menambal ini tanpa persetujuan karena menyangkut kebijakan penanganan rahasia.

6. **Verifikasi** — tanpa mencetak nilai apa pun:
   ```
   POST https://alim-wa.abuhurairah.id/sso/handoff   (dengan Origin Core)
   ```
   Sebelum key cocok: 503 / "SSO belum aktif". Setelah cocok dan Connect dikonfigurasi: halaman SSO Connect terbuka, bukan lagi 503.
7. **Uji negatif** — kirim key salah, pastikan `401`. Kirim `Origin: https://evil.example`, pastikan ditolak.

### Peringatan allowlist

Middleware `VerifyIntegrationApiKey` hanya membatasi IP **bila allowlist tidak kosong**. Allowlist kosong berarti **tanpa pembatasan IP**. Untuk `connect_sso`, isi allowlist bila egress Connect punya IP tetap; bila tidak, pengamanannya bertumpu sepenuhnya pada kerahasiaan key. Ini pertimbangan keamanan yang harus diputuskan, bukan diisi asal.

---

## Ringkasan satu paragraf

Kedua commit sudah di `origin/main` dan bersih — tidak ada Perpustakaan, tidak ada migration, tidak ada tabrakan berkas. Yang belum terjadi adalah **deployment**, dan itu karena penjaga worktree bekerja: server memuat 9–10 berkas di luar commit sehingga dua percobaan auto-deploy (15:02, 16:34) menolak diri sendiri. Tidak ada yang tertimpa. Production tetap `4e057f5e`. Blocker sesungguhnya bukan push, melainkan kepemilikan berkas dirty di server: selama belum diputuskan, SSO tidak akan pernah tayang. Di sisi Connect, hambatan CSP/X-Frame-Options yang saya laporkan sebelumnya sudah diperbaiki, dan yang tersisa hanyalah konfigurasi key — yang menghadapi kesenjangan nyata: Core dapat membuat key tetapi tidak dapat menampilkannya, sementara Connect membutuhkan nilainya.
