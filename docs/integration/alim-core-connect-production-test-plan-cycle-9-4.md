# Cycle 9.4 — Rancangan Pengujian melalui Produksi (`alim.abuhurairah.id`)

**Tanggal:** 2026-11-24 · **Repo:** ALIM Core (`alim`) · **Branch:** `main` (lokal, belum di-push)

**Keputusan domain:** Core memakai `https://alim.abuhurairah.id/` yang sudah ada. Tidak ada
subdomain staging baru, tidak ada aplikasi kedua, tidak ada database staging di document root
produksi.

**Kesimpulan:** uji **baca** (kontrak, kredensial, allowlist, versi API, kebocoran error) aman
dilakukan di produksi. Uji **mutasi** (approve/reject permit) **tidak aman** dan tidak akan
dijalankan. Alasannya di §2, bukan sekadar kehati-hatian: satu request approve menulis ke lima
tabel produksi dan memicu notifikasi sungguhan.

---

## 1. Yang aman diuji di produksi (baca saja, tidak menulis data)

| # | Uji | Request | Yang dibuktikan | Menulis? |
| --- | --- | --- | --- | --- |
| R1 | Endpoint hidup | `POST /api/connect/v1/approval-commands` tanpa key | 401, bukan 404/503 | tidak |
| R2 | Endpoint SSO hidup | `POST /api/connect/v1/sso/exchange` tanpa key | 401 | tidak |
| R3 | Key salah ditolak | header key acak | 401 | tidak (`auth_fail` hanya dihitung di cache) |
| R4 | Versi API | `X-ALIM-API-Version: 2` + key sah | 422 `unsupported_api_version` | **tidak** — ditolak middleware sebelum service |
| R5 | Idempotency-Key ≠ command_id | key sah, versi 1, header beda | 401/422 `idempotency_key_mismatch` | **tidak** — gagal sebelum mutasi |
| R6 | Payload tidak sah | key sah, `{command_id:"x"}` | 422 validasi | **tidak** |
| R7 | `command_id` tak dikenal | key sah, payload sah, permit UUID acak | 4xx `permit_not_found` | **ya, sedikit** — lihat §2.1 |
| R8 | Aktor tak terpetakan | key sah, permit produksi **nyata**? | **JANGAN** | — lihat catatan |
| R9 | Kebocoran internal | periksa body R3–R7 | tanpa `SQLSTATE`/stack/path | tidak |
| R10 | Fail-closed Connect | `GET` halaman handoff Connect | menolak saat origin tidak cocok | tidak |

**Catatan R8:** untuk sampai ke `403 actor_identity_unmapped`, request harus lolos validasi dan
menyentuh permit **yang benar-benar ada** di produksi. Service memuat permit lalu memeriksa aktor
di dalam transaksi. Walaupun rollback terjadi, ini menyentuh baris produksi dan menambah entri
`last_seen_at` pada identitas. **Tidak dijalankan.** Sudah dibuktikan di staging lokal (403) —
tidak perlu diulang di produksi.

**Koreksi atas dugaan awal:** R7 **tidak** bebas tulis. `ApprovalCommandService::apply()` memanggil
`resolveActor()` di baris 90, dan `resolveActor()` menulis
`connect_actor_identities.last_seen_at` di baris 385 — yaitu **sebelum** permit dicari di baris 93.
Jadi setiap request dengan aktor sah meninggalkan jejak satu kolom di tabel produksi, meskipun
permitnya tidak ada. Karena itu R7 hanya boleh dijalankan bila kolom `last_seen_at` dianggap
jejak operasional yang boleh berubah; bila tidak, R7 juga dilarang dan kontrak `permit_not_found`
dibuktikan di staging.

Ini juga menjelaskan bahwa "tidak ada yang berubah" hanya berlaku untuk R1–R6, yang ditolak
middleware/validasi sebelum service dipanggil.

## 2. Mengapa mutasi tidak aman — bukti dari kode

Satu `POST /approval-commands` dengan `decision: approved` menjalankan:

```
ApprovalCommandService::apply()                       (baris 195)
└── LeaveWorkflowService::approve()                   DB::transaction
    ├── dormitory_permits.update(status=approved, approved_by, approved_at)
    ├── StudentStatusService::markOnLeave()           → status santri PRODUKSI jadi ON_LEAVE
    └── DB::afterCommit → Event LeaveApproved
        ├── SyncBoardingLeaveToAttendance::handle()   → INSERT student_attendances (status 'izin')
        ├── RecordLeaveApprovedOnTimeline::record()   → INSERT timeline santri
        └── BroadcastBoardingNotificationToBus        → notifikasi WhatsApp/push SUNGGUHAN
```

Ini bukan dugaan: `app/Providers/EventServiceProvider.php:196-200` mendaftarkan ketiga listener
tersebut, dan `SyncBoardingLeaveToAttendance` **tidak punya mode kering** — ia menulis langsung
(`markAsAbsent`) dan mencatat `IntegrationEventLog`.

Konsekuensi bila dijalankan di produksi:

1. **Data santri berubah.** Santri nyata berstatus `ON_LEAVE` sampai permit ditutup manual.
2. **Absensi produksi terisi.** Baris `student_attendances` bertanggal nyata, berstatus `izin`.
3. **Notifikasi terkirim ke orang sungguhan** — tidak bisa ditarik kembali.
4. **Izin produksi berubah status.** `approved_by` terisi akun uji; membalikkannya manual
   meninggalkan jejak audit yang menyesatkan.
5. **Rollback tidak bersih.** Listener berjalan **setelah commit**, jadi membatalkan transaksi
   tidak membatalkan absensi maupun notifikasi.

Karena poin 5, "transaksi bisa di-rollback" **bukan** jaminan. Itu sebabnya uji mutasi ditolak,
bukan hanya "dibatasi".

## 3. Risiko yang masih ada (dengan mitigasi)

| # | Risiko | Tingkat | Mitigasi |
| --- | --- | --- | --- |
| W1 | Allowlist produksi kosong → siapa pun dengan key bisa memanggil | sedang | Saat ini **tidak ada** `CONNECT_*` di `.env` produksi, sehingga endpoint menjawab 503/401 dan tidak dapat dipakai. Jangan aktifkan kredensial produksi sebelum allowlist diisi. |
| W2 | Uji baca R4–R7 memakai key sah → tercatat di log produksi | rendah | Log hanya mencatat `request_id`, `ip`, `endpoint` — bukan payload. Tetap harus dijadwalkan di luar jam sibuk. |
| W2b | R7 menulis `last_seen_at` pada identitas produksi | rendah | Satu kolom, satu baris. Bila harus nol tulis, R7 dipindah ke staging. |
| W3 | Throttle `auth_fail` (R3) bisa mengunci IP operator | rendah | Cache produksi menyimpan hitungan; bila salah beberapa kali, key/IP berbeda diblokir sementara. Jalankan R3 **paling banyak sekali**, dari IP yang dikenal. |
| W4 | Endpoint menyala tanpa disadari setelah kredensial dipasang | sedang | Pasang kredensial produksi **hanya** bersamaan dengan allowlist IP egress Connect, lalu jalankan R1–R7 dalam satu jendela. |
| W5 | Uji mutasi tetap dijalankan | **tinggi** | Dilarang di dokumen ini. Tidak ada flag atau akun uji yang membolehkannya. |
| W6 | Connect mengirim mutasi nyata saat uji | tinggi | Connect staging harus diarahkan ke staging Core, bukan produksi. Bila Connect produksi diaktifkan, batasi ke endpoint baca dan laporkan terpisah. |

## 4. Kebutuhan isolasi alternatif (bila mutasi harus dibuktikan)

Urut dari yang paling murah:

| Opsi | Isolasi | Catatan |
| --- | --- | --- |
| **A. Staging lokal yang sudah ada** | penuh | Sudah membuktikan 200/duplicate/409/422/401/403 dan SSO 200→409. **Cukup untuk membuktikan kontrak.** Kekurangannya hanya TLS + jaringan nyata. |
| **B. Staging Core di host lain** (VPS kecil / mesin penguji) | penuh | Satu-satunya cara membuktikan "HTTPS + egress Connect nyata" tanpa menyentuh produksi. Bisa dijalankan di luar domain produksi, mis. subdomain milik domain lain atau host sementara. |
| **C. Produksi dengan database salinan** | sebagian | Tetap menulis ke tabel produksi kecuali seluruh aplikasi diarahkan ke salinan — berisiko tertukar. **Tidak direkomendasikan.** |
| **D. Feature flag dry-run di listener** | sebagian | Menambah kode produksi dan mekanisme baru; melanggar arahan "jangan ubah konfigurasi produksi demi staging". |

Rekomendasi: **A untuk kontrak + B hanya bila TLS/jaringan harus dibuktikan.** Keduanya tidak
mengubah produksi.

## 5. Status fail-closed produksi saat ini (terverifikasi read-only)

| Item | Hasil |
| --- | --- |
| `CONNECT_*` di `.env` produksi | **0** |
| `/api/connect/v1/*` tanpa key | **503** (belum dikonfigurasi) |
| `alim:connect-secret status` di produksi | kedua scope BELUM ADA |
| `~/.alim`, `.env.staging`, `database/staging.sqlite` | tidak ada |
| Health produksi | 200 |

Jadi saat ini **tidak ada** jalur yang bisa memutasi data melalui endpoint integrasi di produksi.
Kondisi ini dipertahankan sampai ada keputusan eksplisit.

## 6. Batasan

- Dokumen ini tidak mengubah konfigurasi produksi apa pun.
- Tidak ada push, merge, atau deploy.
- Uji R1–R7 **belum** dijalankan; menunggu persetujuan dan jendela waktu.
