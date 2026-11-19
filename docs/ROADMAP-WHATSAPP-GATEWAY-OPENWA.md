# Roadmap — WhatsApp Gateway OpenWA untuk ALIM

Status: **rencana**, belum ada kode yang diubah.
Disusun dari kondisi repo per 2026-11-19.

## 1. Keputusan arsitektur

```
ALIM (Hostinger, PHP)  ──HTTPS outbound──▶  OpenWA (VPS, Node 22 + Docker)  ──▶  WhatsApp
        ▲                                            │
        └──────── webhook (HMAC-SHA256) ◀────────────┘
```

- **ALIM hanya klien HTTP.** Tidak ada Node/Chromium di shared hosting.
- **OpenWA di VPS terpisah**, engine **Baileys** (tanpa browser; whatsapp-web.js
  butuh ~500 MB–1 GB RAM per sesi).
- Nomor WhatsApp yang dipakai **bukan** nomor kepala sekolah / nomor pribadi
  yang aktif dipakai chat.
- Kanal untuk **wali santri** tetap disarankan lewat Cloud API resmi (driver
  `meta` sudah ada). OpenWA dipakai untuk notifikasi internal guru/staf lebih
  dulu, lalu dievaluasi.

## 2. Kondisi awal (fakta kode saat ini)

| Komponen | Lokasi | Status |
| --- | --- | --- |
| Gateway provider-agnostic | `app/Services/Notifications/WhatsAppGatewayService.php` | Ada. Driver: `meta`, `fonnte`, `wablas`, `generic`, `log` |
| Konfigurasi runtime | `setting()` di `app/Authorization/helpers.php:310` → tabel `system_settings`, cache 5 menit | Ada, **global** (tidak per sekolah) |
| UI pengaturan | `resources/views/super-admin/system-settings/index.blade.php` + `SystemSettingController` | Ada field `wa_*` |
| Riwayat kirim | tabel `wa_message_logs` + `IntegrationEventLog` | Ada |
| Webhook masuk | `WhatsAppWebhookController` (`GET/POST /webhook/whatsapp`) | Ada, khusus format Meta |
| CSRF exemption | `app/Http/Middleware/VerifyCsrfToken.php` (`webhook/*`) | Ada |
| Job notifikasi | `DispatchWhatsAppNotificationJob`, `SendUniversalWhatsApp` (`ShouldQueue`) | Ada |
| Queue | `QUEUE_CONNECTION=sync` | Belum siap volume besar |
| Scheduler | `app/Console/Kernel.php` | Ada, tinggal tambah task |

Implikasi: integrasi ini **menambah satu driver**, bukan membangun modul baru.
Semua pemanggil yang sudah ada otomatis ikut.

## 3. Daftar fitur

Prioritas: **P0** fondasi (wajib), **P1** aman dipakai produksi, **P2** multi-sekolah,
**P3** dua arah / lanjutan.

### P0 — Fondasi

| ID | Fitur | Ringkasan | Selesai bila |
| --- | --- | --- | --- |
| F1 | Driver `openwa` | Metode `sendViaOpenWA()` di `WhatsAppGatewayService`: `POST {url}/api/sessions/{sessionId}/messages/send-text`, header `X-API-Key`, body `{chatId, text}`, `chatId` = `normalizePhone()` + `@c.us` | Pilih provider `openwa` di halaman setting, kirim uji berhasil, `wa_message_logs` mencatat `provider=openwa` |
| F2 | Konfigurasi OpenWA | Key baru: `wa_openwa_url`, `wa_openwa_session_id`, `wa_openwa_api_key` (terenkripsi), `wa_openwa_engine`; tampil di UI setting | Simpan dari UI langsung berlaku (cache `system_settings_map` di-flush) |
| F3 | Cek status kanal | `checkDevice()` untuk OpenWA: `GET /api/sessions/{sessionId}` → tampilkan status `ready/connecting/disconnected`, nomor, dan `lastActiveAt` di halaman setting | Tombol "Cek Koneksi" menampilkan status nyata, bukan pesan generik |
| F4 | Penanganan error yang bisa ditindaklanjuti | Pesan gagal kirim menyebut sebab spesifik (sesi belum `ready`, nomor tidak terdaftar, 401 key salah) + langkah perbaikan | Tidak ada lagi pesan "gagal" tanpa sebab; detail teknis hanya di `Log::error()` |

### P1 — Aman untuk produksi

| ID | Fitur | Ringkasan | Selesai bila |
| --- | --- | --- | --- |
| F5 | Webhook status pengiriman | Endpoint `POST /webhook/openwa`; verifikasi `X-OpenWA-Signature` (HMAC-SHA256, `hash_equals`); petakan `message.ack`/`message.failed` → kolom `status` di `wa_message_logs` | Status berubah `sent` → `delivered`/`read`/`failed` tanpa polling |
| F6 | Idempotensi webhook | Simpan `event_id` yang sudah diproses (unik) agar retry OpenWA tidak menggandakan baris | Kirim event sama 2x → 1 baris terpengaruh |
| F7 | Antrian kirim | Pindah `QUEUE_CONNECTION` ke `database`; worker via cron `queue:work --stop-when-empty`; job kirim WA pakai `$queue = 'wa'` | Broadcast 100 pesan tidak memblokir request HTTP |
| F8 | Throttle & batching | Batasi laju (mis. 1 pesan / 3–5 detik per sesi), jeda acak, batas per menit | Kirim 50 pesan tidak memicu `429`/disconnect |
| F9 | Fallback berantai | Urutan kanal: `openwa` → `meta`/`fonnte` bila gagal; catat kanal yang akhirnya dipakai | Kegagalan OpenWA tidak menggagalkan notifikasi kritis |
| F10 | Monitoring sesi | Task scheduler tiap 5 menit cek `/api/sessions`; kalau `disconnected`, kirim alert ke admin + catat di `IntegrationEventLog` | Sesi mati terdeteksi < 10 menit tanpa ada yang melapor |
| F11 | Halaman riwayat WA | Tabel `wa_message_logs` di UI: filter status/event/tanggal, tombol kirim ulang | Admin bisa lihat & ulang pesan gagal tanpa akses DB |

### P2 — Multi-sekolah

| ID | Fitur | Ringkasan | Selesai bila |
| --- | --- | --- | --- |
| F12 | Tabel `wa_channels` | Satu baris per sekolah: `school_id`, `provider`, `session_id`, `api_key` (encrypted), `phone`, `status`, `last_seen_at`, `enabled` | Setiap sekolah punya kanal sendiri; `wa_message_logs` dapat `school_id` |
| F13 | Resolusi kanal per sekolah | `WhatsAppGatewayService` pilih kanal berdasarkan `school_id` konteks; fallback ke kanal global bila sekolah belum punya | Notifikasi santri sekolah A terkirim dari nomor sekolah A |
| F14 | Pairing dari dashboard | Tombol "Hubungkan WhatsApp": `POST /api/sessions` + `POST /api/sessions/{id}/pairing-code`, tampilkan 8 digit + hitung mundur 5 menit | Admin sekolah menghubungkan nomor tanpa menyentuh server |
| F15 | QR fallback | Tampilkan QR (`GET /api/sessions/{id}/qr`) bila pairing code tidak dipakai | QR tampil dan ter-refresh otomatis |
| F16 | API key per sesi | Buat key `allowedSessions=[sessionId]` per sekolah; ALIM simpan terenkripsi, tidak pernah ditampilkan ulang di UI | Bocor key satu sekolah tidak membuka sesi sekolah lain |

### P3 — Dua arah & lanjutan

| ID | Fitur | Ringkasan | Selesai bila |
| --- | --- | --- | --- |
| F17 | Pesan masuk | Webhook `message.received` → simpan ke tabel `wa_inbound_messages` (dengan `school_id`, `from`, `body`, `received_at`) | Pesan wali masuk terlihat di UI, bukan hanya `Log::info` |
| F18 | Perintah wali | Parser: `IZIN <nis> <tanggal>`, `HADIR <nis>`, `STATUS <nis>`; balasan otomatis terformat | Wali bisa izin tanpa buka aplikasi |
| F19 | Opt-out | Kata `STOP`/`BERHENTI` → baris di `wa_opt_outs`; semua pengiriman ke nomor itu dilewati kecuali darurat | Nomor opt-out tidak pernah dikirimi broadcast lagi |
| F20 | Kirim media | `send-document`/`send-image`: kirim PDF rapor, bukti pembayaran, foto kegiatan | File terkirim dan tercatat di riwayat |
| F21 | Template pesan per event | Template tersimpan per sekolah, mendukung variabel; sudah ada pola `wa_template_*` di config | Admin ubah template dari UI, langsung berlaku |

## 4. Roadmap per fase

### Fase 0 — Prasyarat (harus selesai sebelum kode)

- [ ] VPS disiapkan: Docker, minimal 2 vCPU / 2 GB RAM, domain + TLS.
- [ ] OpenWA jalan, satu sesi uji terhubung (Baileys), `/health` → 200.
- [ ] Nomor uji khusus, **bukan** nomor operasional sekolah.
- [ ] Kebijakan opt-in wali ditulis (siapa boleh dikirimi, bagaimana berhenti).
- [ ] Keputusan: kanal mana untuk wali (OpenWA / Cloud API / Fonnte).

**Gate:** kirim 1 pesan manual via `curl` ke OpenWA berhasil, tercatat di dashboard OpenWA.

### Fase 1 — Kirim pesan (F1–F4)

Tambah driver + config + UI + cek koneksi. Tidak menyentuh alur notifikasi lain.

**Gate:** dari halaman setting ALIM, tombol "Kirim Uji" mengirim WA nyata dan
baris `wa_message_logs` tercatat `provider=openwa`, `status=sent`.

### Fase 2 — Status & riwayat (F5, F6, F11)

Webhook masuk + halaman riwayat.

**Gate:** status pesan berubah menjadi `delivered`/`read` di UI tanpa cron polling;
event duplikat tidak menggandakan baris.

### Fase 3 — Siap volume (F7–F10)

Queue, throttle, fallback, monitoring.

**Gate:** broadcast 200 pesan selesai tanpa timeout, tanpa disconnect sesi,
dengan jeda sesuai batas; sesi mati memicu alert.

### Fase 4 — Multi-sekolah (F12–F16)

Tabel kanal, resolusi per sekolah, pairing/QR dari dashboard.

**Gate:** dua sekolah dengan dua nomor berbeda, notifikasi masing-masing
terkirim dari nomor yang benar; key sekolah A ditolak untuk sesi sekolah B.

### Fase 5 — Dua arah (F17–F21)

Inbound, perintah wali, opt-out, media, template.

**Gate:** wali kirim `IZIN 12345 2026-11-20`, tercatat sebagai izin di modul
perizinan dan dibalas konfirmasi.

## 5. Perubahan database

| Tabel | Perubahan | Fase |
| --- | --- | --- |
| `wa_message_logs` | `+ school_id`, `+ channel_id`, `+ attempt`, `+ provider_status` | 3–4 |
| `wa_channels` (baru) | kanal per sekolah, `api_key` cast `encrypted` | 4 |
| `wa_inbound_messages` (baru) | pesan masuk + `school_id` | 5 |
| `wa_opt_outs` (baru) | nomor yang berhenti menerima | 5 |
| `wa_webhook_events` (baru) | idempotensi (`event_id` unik) | 2 |

Catatan: produksi memakai SQLite — migrasi harus portabel (tanpa `ENUM`,
tanpa `ALTER` bertingkat, indeks eksplisit).

## 6. Risiko & mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Nomor diblokir WhatsApp (kanal tidak resmi) | Notifikasi berhenti total | Nomor khusus, warm-up bertahap, throttle, personalisasi pesan, opt-out, fallback ke Cloud API untuk wali |
| Sesi `disconnected` tanpa terlihat | Notifikasi senyap gagal | F10 monitoring + alert; status kanal di UI |
| API key bocor | Orang lain bisa kirim dari nomor sekolah | Key per sesi (`allowedSessions`), simpan terenkripsi, tidak ditampilkan ulang, rotasi dari UI |
| Webhook dipalsukan | Status/izin palsu | Wajib `X-OpenWA-Signature` + `hash_equals`, tolak bila secret kosong |
| Broadcast menembus `max_execution_time` | Request gagal separuh | F7 queue + F8 throttle |
| Data wali (nomor) tersebar | Isu privasi | Nomor hanya di `wa_message_logs`/`wa_channels`, akses dibatasi per sekolah |

## 7. Di luar cakupan

- Migrasi penuh notifikasi wali ke OpenWA (keputusan terpisah setelah Fase 3).
- Chatbot AI / auto-reply bebas (hanya perintah terbatas di F18).
- WhatsApp Channel / broadcast list.
- Multi-node OpenWA (Redis, horizontal scaling) — tidak perlu untuk skala sekolah.

## 8. Keputusan yang perlu disetujui sebelum mulai

1. **Kanal untuk wali:** OpenWA, Cloud API resmi, atau hybrid (internal OpenWA,
   wali Cloud API).
2. **Lokasi VPS & domain** OpenWA.
3. **Engine:** Baileys (disarankan) atau whatsapp-web.js.
4. **Cakupan awal:** hanya notifikasi internal guru/staf, atau langsung wali.
5. **Anggaran nomor:** satu nomor global dulu, atau langsung per sekolah.
