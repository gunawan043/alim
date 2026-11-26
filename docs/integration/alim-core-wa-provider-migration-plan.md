# Rencana Migrasi WhatsApp: Fonnte → ALIM Connect

Tanggal: 2026-11-25. Status: **rencana, belum ada kode yang diubah.**
Lingkup: Core saja. Connect tidak diubah.

---

## 0. Keputusan yang sudah ditetapkan pemilik produk

| Pertanyaan | Keputusan |
|---|---|
| Fonnte setelah migrasi | **Cadangan otomatis hanya untuk pesan kritis** (OTP login, notifikasi keamanan) |
| Sesi Connect putus | **Antre lalu kirim ulang** saat sesi hidup kembali |
| Nomor pengirim | **Satu sesi pusat** untuk seluruh notifikasi |
| Broadcast bergambar | **Keduanya** — pesan pribadi bergambar ke tiap penerima **dan** posting Status/Story |

Dengan keputusan ini, "pindah total" berarti: **Connect satu-satunya kanal reguler**,
Fonnte berhenti dipakai sehari-hari dan hanya bangun ketika pesan kritis gagal.

---

## 1. Keadaan sekarang

| Aspek | Nilai di production |
|---|---|
| Provider aktif | `fonnte` |
| `wa_enabled` | `1` |
| `wa_api_url` | `https://api.fonnte.com` |
| `wa_delivery_mode` | `central` |
| Token & nomor pusat | terisi |

Fonnte **berjalan** dan mengirim notifikasi sekarang. Apa pun yang dilakukan di
bawah tidak boleh memutusnya sampai Connect terbukti stabil.

Satu masalah fondasi yang belum selesai dan menjadi prasyarat: **`QUEUE_CONNECTION`
masih `sync`**. Kebijakan "antre lalu kirim ulang" tidak bisa diwujudkan tanpa
antrean. Ini pekerjaan tersendiri (lihat Tahap 0b).

---

## 2. Mengapa ini bukan pekerjaan kecil

Mengganti provider **bukan** mengubah satu setting. Gateway WA dipakai di **6 berkas**
yang memanggil `send()`:

```
app/Jobs/SendUniversalWhatsApp.php:37
app/Services/SecurityNotificationService.php:146, 269
app/Services/LoginOtpNotifier.php:86
app/Services/Notifications/GuardianWhatsAppNotifier.php:643
app/Services/Boarding/LeaveWorkflowService.php:1126
app/Http/Controllers/SuperAdmin/SystemSettingController.php   (uji kirim manual)
```

Salah satunya adalah **OTP login** — bila pengiriman gagal, pengguna tidak bisa masuk.
Fonnte menanggung keandalan pengiriman; dengan Connect, tanggung jawab itu pindah ke kita.

---

## 3. Kontrak Connect yang sudah diverifikasi dari kode Connect

Semua di bawah dibaca langsung dari `~/OpenWA` (bukan dugaan):

| Kebutuhan | Endpoint | Catatan |
|---|---|---|
| Teks | `POST /api/sessions/{sessionId}/messages/send-text` | body `{ chatId, text }`, sukses **201**, role OPERATOR |
| Gambar + caption | `POST /api/sessions/{sessionId}/messages/send-image` | body `{ chatId, url \| base64, mimetype, caption }`; `caption` maks 1024 karakter |
| Banyak penerima sekaligus | `POST /api/sessions/{sessionId}/messages/send-bulk` | **maks 100 penerima per panggilan**, jawab **202** dengan `batchId`; progres lewat `GET .../messages/batch/{batchId}` |
| Bulk bergambar | item `send-bulk` ber-`type: 'image'` | `content: { image: { url }, caption }` — satu batch boleh campur teks & gambar |
| Status/Story | `POST /api/sessions/{sessionId}/status/send-text` dan `.../status/send-image` | `recipients` hanya dihormati di engine Baileys |
| Cek sesi | `GET /api/sessions/{sessionId}` | status `ready` / `connecting` / `disconnected` |
| Aman diulang | header `Idempotency-Key` (opsional) | retry dalam 24 jam dengan key+body sama tidak mengirim dua kali; 409 `IDEMPOTENCY_KEY_IN_PROGRESS` bila masih jalan |

Header autentikasi: `X-API-Key` dengan role **OPERATOR**.
Bentuk nomor: `628xxx@c.us` (bukan `628xxx` polos seperti Fonnte).
Batas media: `url` diunduh Connect dengan cap `MEDIA_DOWNLOAD_MAX_BYTES` (50 MiB);
body request dibatasi `BODY_SIZE_LIMIT` (25 MB). **Karena itu selalu kirim `url`,
jangan `base64`** — banner 1080×1350 WebP kita jauh di bawah cap.

### Yang menguntungkan

- **`send-bulk` menjawab 202 lalu memproses di latar belakang Connect**, dengan
  progres per penerima. Broadcast massal tidak perlu kita antre satu per satu;
  cukup bagi per 100 penerima. Ini yang membuat "antre & kirim ulang" masuk akal.
- **`Idempotency-Key` gratis.** Simpan key deterministik per penerima
  (mis. `broadcast:{id}:{phone}`) sehingga retry tidak menggandakan pesan.
- **Banner broadcast sudah dinormalisasi** oleh `BannerImageService` menjadi WebP
  1080×1350 dan tersimpan di disk `public`. Cukup kirim URL publiknya — tidak perlu
  ubah apa pun di sisi unggah.

### Yang perlu diperhatikan

1. **`sessionId` harus dikonfigurasi operator.** Kalau sesi logout / HP mati, seluruh
   pengiriman berhenti. Karena itu ada pengecekan sesi terjadwal (Tahap 4).
2. **Status `recipients` hanya andal di engine Baileys.** Di engine Baileys Status
   diposting **tepat ke daftar `recipients`** (maks 256 `628xxx@c.us`).
   Di whatsapp-web.js daftar itu diabaikan dan Status disiarkan ke seluruh audiens
   privasi akun. Jadi untuk sekolah dengan wali lebih dari 256, satu posting Status
   tidak menjangkau semua — posting harus dipecah, atau Status diterima sebagai
   jangkauan terbatas sementara pesan pribadi tetap ke semua penerima.
3. **Status hilang dalam 24 jam.** Status adalah kanal pelengkap, bukan arsip.

---

## 4. Prinsip rancangan

**Driver ditambah, bukan diganti.** `WhatsAppGatewayService::send()` sudah memakai
`match` pada provider (baris 125–131), sehingga driver baru cukup satu cabang:

```php
'connect' => $this->sendViaConnect($target, $message, $context, $provider),
```

Konsekuensi yang diinginkan: **kembali ke Fonnte cukup mengganti satu dropdown** di
Pengaturan Sistem, tanpa deploy.

Di atas driver itu diletakkan tiga lapis kebijakan yang tidak menyentuh 6 pemanggil:

| Lapis | Tugas | Berkas |
|---|---|---|
| Router kanal | Tentukan provider per pesan: kritis → Connect, gagal → Fonnte; biasa → Connect saja | `WhatsAppGatewayService` |
| Antrean ulang | Pesan yang gagal karena sesi mati dikembalikan ke antrean, bukan ditandai gagal permanen | Job + tabel antrean |
| Media | Kirim gambar + caption, bulk bergambar, posting status | `WhatsAppGatewayService` |

---

## 5. Tahapan

### Tahap 0a — Prasyarat di Connect (pemilik Connect)

- [ ] `ALIM_CONNECT_SSO_KEY`, `ALIM_CORE_BASE_URL`, `ALIM_CORE_WEB_ORIGIN` terpasang
- [ ] Ada **sesi WhatsApp aktif** (sudah scan QR) dengan engine **Baileys**
- [ ] API key Connect role **OPERATOR** dibuat
- [ ] Catat `sessionId` sesi pusat

Tanpa tahap ini, tahap berikutnya tidak bisa diuji.

### Tahap 0b — Antrean (prasyarat "antre & kirim ulang")

- [ ] Pindah `QUEUE_CONNECTION` ke `database` (atau Redis bila tersedia)
- [ ] Worker via cron: `queue:work --stop-when-empty --queue=wa,default`
- [ ] Job WA memakai `$queue = 'wa'` agar tidak tertahan job lain
- [ ] Uji: matikan worker → pesan tetap `pending`, tidak hilang

Risiko: bila worker mati dan tidak ada yang memantau, pesan menumpuk tanpa terkirim —
karena itu Tahap 4 menambahkan pemantauan backlog.

### Tahap 0c — Uji kirim (sudah tersedia di Core)

Halaman **Super Admin → Uji WhatsApp Connect** memakai jalur yang sama dengan
driver yang akan dibuat nanti, tetapi **tidak** menyentuh Fonnte dan tidak
mengubah `wa_provider`:

- simpan `connect_base_url`, `connect_session_id`, `connect_api_key`
  (key terenkripsi, sekali simpan, tidak pernah ditampilkan ulang)
- cek status sesi (`GET /api/sessions/{sessionId}`) beserta nomor & waktu aktif
- kirim satu pesan teks uji ke nomor operator, dicatat sebagai `wa.connect_test`

Kelasnya sengaja dipisah (`ConnectApiConfig`, `ConnectWhatsAppClient`) supaya
driver di Tahap 1 tinggal memakainya, tanpa menduplikasi aturan HTTP, JID, dan
terjemahan error.

### Tahap 1 — Driver `connect` (teks) + status sesi

Tambahkan ke `WhatsAppGatewayService`:
- `sendViaConnect(string $target, string $message, array $context): bool`
  → `chatId = $target.'@c.us'`, `text = $message`
  → kirim `Idempotency-Key` dari `$context['idempotency_key']` bila ada
  → 201 = sukses; 4xx/5xx dicatat apa adanya ke `lastResponseBody`
  → kegagalan yang menandakan sesi mati (400 "Session not active", 409 tidak ada engine,
    503) ditandai sebagai **retryable**, bukan gagal permanen
- `checkDevice()` untuk provider `connect` → `GET /api/sessions/{sessionId}`,
  kembalikan `ready/connecting/disconnected` + nomor + `lastActiveAt` supaya
  tombol "Cek Koneksi" menampilkan status nyata
- Konfigurasi baru (pola `IntegrationApiConfig` terenkripsi yang sudah dipakai SSO):
  `connect_base_url`, `connect_session_id`, `connect_api_key`
  ditambah `connect_status_enabled` (on/off posting Status)
- Opsi `wa_provider = connect` di dropdown — **default tetap `fonnte`** sampai Tahap 2 lulus

Yang **tidak** berubah: 6 pemanggil `send()`, `normalizePhone()`, bentuk pesan.

### Tahap 2 — Uji terbatas (non-kritis)

Pindahkan **hanya notifikasi non-kritis** ke Connect. Yang dikecualikan: OTP login,
notifikasi keamanan. Caranya tanpa menyentuh pemanggil: pemetaan event di gateway.

```php
'event_overrides' => [
    'broadcast'    => 'connect',
    'announcement' => 'connect',
    'attendance'   => 'connect',
    // 'login_otp', 'security' tetap memakai provider global (fonnte)
],
```

Kriteria lulus: 20 pesan berturut-turut terkirim, tercatat di Riwayat Pengiriman
dengan `provider=connect`, dan tidak ada kegagalan diam-diam.

### Tahap 3 — Fallback kritis (sesuai keputusan)

Untuk pesan kritis saja, gateway mencoba **Connect dulu, Fonnte kedua**:

1. Connect sukses → selesai, catat `provider=connect`
2. Connect gagal → catat percobaan gagal, lalu kirim via Fonnte, catat `provider=fonnte`,
   tandai `fallback_from=connect` di `wa_message_logs` agar terlihat di riwayat
3. Keduanya gagal → baru gagal, dengan sebab konkret di riwayat

Untuk pesan non-kritis: **tidak ada fallback Fonnte**. Kalau gagal karena sesi mati,
pesan dikembalikan ke antrean dengan backoff (mis. 1, 5, 15, 60 menit) sampai sesi hidup.

### Tahap 3b — Broadcast bergambar (fitur utama yang diminta)

Di `AnnouncementNotifier`, saat kanal `wa` dipilih dan broadcast punya `banner_path`:

- Bangun URL publik banner (`Storage::disk('public')->url()` → absolut via `APP_URL`)
- Kirim lewat `send-bulk` per 100 penerima, tiap item:
  `type: 'image'`, `content: { image: { url }, caption }`,
  `variables`/caption berisi judul + ringkasan 180 karakter + tautan halaman pengumuman
- `Idempotency-Key` per penerima: `broadcast:{id}:{phone}` → aman diulang saat retry
- Simpan `batchId` Connect di tabel agar progres batch bisa ditampilkan di halaman Broadcast
- Bila broadcast tanpa banner → tetap jalur teks biasa (caption kosong)

Karena keputusan **"keduanya"**, tambahkan juga opsi centang **"Posting juga sebagai Status"**:

- `POST /api/sessions/{sessionId}/status/send-image` dengan `{ image: { url }, caption, recipients }`
  (`recipients` wajib format `628xxx@c.us`, maks 256 alamat per posting)
- Ditawarkan hanya bila sesi berengine Baileys; bila bukan, opsi disembunyikan
  dengan keterangan singkat, bukan gagal diam-diam
- Status hanya boleh 1 per broadcast; kalau gagal, broadcast pesan tetap terkirim
  dan kegagalan Status dicatat terpisah di riwayat — jangan gagalkan seluruh broadcast

Catatan penting: caption WhatsApp dibatasi **1024 karakter**. Ringkasan sekarang 180
karakter, jadi aman; isi panjang tetap dibaca di halaman pengumuman lewat tautan.

### Tahap 4 — Pemantauan & pindahkan OTP terakhir

- Task scheduler tiap 5 menit: cek sesi. `disconnected` → alert admin + catat
  `IntegrationEventLog`; sesi pulih → picu ulang antrean `wa`
- Pantau backlog antrean (mis. > 50 pesan tertahan 15 menit → alert)
- **Setelah non-kritis stabil minimal 3 hari**, pindahkan OTP ke Connect (tetap dengan
  fallback Fonnte dari Tahap 3). Ini jalur login; kegagalannya mengunci pengguna keluar.

### Tahap 5 — Fonnte jadi cadangan saja

Karena keputusan pemilik: Fonnte **tidak dihapus**. Token disimpan, dipakai hanya untuk
fallback kritis. Halaman Pengaturan menampilkan status ganda:

- "Kanal utama: ALIM Connect — sesi `ready`, terakhir aktif …"
- "Cadangan kritis: Fonnte — sisa kuota …, terakhir dipakai …"

---

## 6. Rollback

| Tahap | Cara mundur | Waktu |
|---|---|---|
| 1–2 | Ubah `wa_provider` → `fonnte` di Pengaturan Sistem | detik, tanpa deploy |
| 3 | Sama; fallback otomatis mati bersama provider global | detik |
| 3b | Matikan centang kanal `wa` pada broadcast; pesan in-app tetap | detik |
| 4–5 | Sama, tetapi OTP harus diuji ulang setelahnya | menit |

Karena driver lama tidak dihapus, rollback tidak memerlukan revert kode.

---

## 7. Estimasi dampak

| Tahap | Berkas tersentuh | Risiko |
|---|---|---|
| 0b | `.env`, `Kernel.php`, `SendUniversalWhatsApp` | **sedang** — mengubah cara semua job jalan |
| 1 | `WhatsAppGatewayService`, view Pengaturan, `SystemSettingController` | rendah — belum aktif |
| 2 | `WhatsAppGatewayService` (pemetaan event) | sedang — perilaku berubah |
| 3 | `WhatsAppGatewayService` (router fallback) | sedang — dua kanal aktif |
| 3b | `AnnouncementNotifier`, `BroadcastController`, view Broadcast, migrasi kolom `batch_id` | sedang — fitur baru |
| 4 | `Kernel.php`, command baru, view status | rendah |
| 5 | tidak ada kode | rendah |

---

## 8. Urutan yang disarankan

1. **Selesaikan SSO Connect** — key masih harus dipasang di Connect dan `core_user_id`
   dipetakan. Tanpa itu Connect belum hidup, dan Tahap 2 tidak bisa diuji.
2. **Tahap 0b (antrean)** — karena keputusan "antre & kirim ulang" bergantung padanya.
3. **Tahap 1 + 2** — driver teks, uji terbatas non-kritis.
4. **Tahap 3b** — broadcast bergambar, karena inilah yang paling ditunggu.
5. **Tahap 3 → 4 → 5** — fallback kritis, pemantauan, lalu OTP terakhir.

Alasan OTP paling akhir: ia satu-satunya pesan yang kegagalannya membuat pengguna
tidak bisa masuk sama sekali, sementara broadcast yang gagal hanya tertunda.
