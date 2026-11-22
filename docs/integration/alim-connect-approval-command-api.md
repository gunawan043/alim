---
name: alim-connect-approval-command-api
description: Kontrak final Approval Command API ALIM Core untuk ALIM Connect
metadata:
  type: api-contract
  version: 1
  status: final-for-connect-integration
  endpoint: POST /api/connect/v1/approval-commands
---

# Approval Command API — Kontrak untuk ALIM Connect

> **Kontrak ini terpisah dari API sinkronisasi master read-only.** Jangan menambahkan command persetujuan ke OpenAPI master/read-only.
>
> ALIM Core adalah pihak yang menentukan hasil akhir persetujuan. ALIM Connect hanya mengirim command dan menampilkan hasilnya.

## 1. Endpoint dan autentikasi

```http
POST /api/connect/v1/approval-commands
```

Header:

| Header | Wajib | Keterangan |
|---|---|---|
| `X-ALIM-CONNECT-APPROVAL-KEY` | Ya | Kunci service-to-service khusus approval (atau `Authorization: Bearer <key>`) |
| `Accept` | Ya | `application/json` |
| `Content-Type` | Ya | `application/json` |
| `X-Request-Id` | Tidak | Untuk korelasi audit |

Konfigurasi Core:

- `services.connect_approval.api_key`
- `services.connect_approval.header` (default `X-ALIM-CONNECT-APPROVAL-KEY`)
- `services.connect_approval.allowed_ips`
- `services.connect_approval.require_https`
- `services.connect_approval.scopes` — wajib memuat `approval:command`
- `services.connect_approval.max_duration_days` — default 365
- `services.connect_approval.message_text_max_length` — default 500

Key ini tidak berlaku untuk POS/Recruitment atau API master.

## 2. Aturan identitas aktor

- `actor_external_id` wajib berisi identitas WhatsApp pengirim (nomor/JID). Core menormalkan ke digit E.164.
- Core mencari baris di `connect_actor_identities` dengan:
  - `channel = whatsapp`
  - `external_identity` cocok
  - `is_active = true`
  - `verified_at` tidak null
- `actor_core_person_id` **tidak dipercaya**. Field ini opsional dan hanya untuk referensi audit/korelasi; otorisasi selalu memakai hasil pemetaan identitas.
- Aktor harus:
  - user aktif,
  - `dormitory_policy_access.canApprovePermitCommand() = true`,
  - Kepala Asrama pada asrama permohonan (head_id atau penugasan aktif `kepala_asrama`).
- Wakil Kepala Asrama, Super Admin, System Admin, dan pencocokan teks `employment.jabatan` **tidak** berlaku pada endpoint ini.
- Asrama harus aktif dan tidak terhapus.

## 3. Request

```json
{
  "command_id": "cmd-uuid-001",
  "request_id": "uuid-dormitory-permit",
  "decision": "approved",
  "approved_duration_days": 2,
  "actor_external_id": "6281234567890",
  "actor_core_person_id": "uuid-opsional-tidak-dipercaya",
  "source": "whatsapp",
  "external_message_id": "wamid-001",
  "message_text": "SETUJU 2 HARI",
  "notes": "Disetujui via WhatsApp"
}
```

| Field | Wajib | Aturan |
|---|---|---|
| `command_id` | Ya | String unik, max 100; idempotency utama |
| `request_id` | Ya | UUID `dormitory_permits.id` |
| `decision` | Ya | `approved`, `rejected`, `needs_clarification`, `expired`, `cancelled` |
| `approved_duration_days` | Untuk `approved` | Integer 1..`max_duration_days`; dilarang untuk keputusan lain |
| `actor_external_id` | Ya | Identitas WhatsApp terverifikasi |
| `actor_core_person_id` | Tidak | Tidak dipakai untuk otorisasi |
| `source` | Ya | Harus `whatsapp` |
| `external_message_id` | Ya | Unik; mencegah replay pesan |
| `message_text` | Tidak | Disimpan sebagai cuplikan max 500 karakter setelah normalisasi spasi |
| `notes` | Tidak | Alasan/catatan keputusan, max 1000 |

## 4. Aturan keputusan dan durasi

- Hanya permit berstatus `pending` yang dapat diputuskan.
- Permit dengan `secondary_status` `expired`/`cancelled` tidak dapat diputuskan lagi.
- Permit yang `expected_return_datetime` sudah lewat tidak dapat di-approve/reject/clarify; gunakan `expired`.
- `approved`:
  - Core menghitung `expected_return_datetime = departure_datetime + approved_duration_days`.
  - Tanggal keberangkatan tetap milik permit; Core tidak menerima tanggal dari Connect.
  - `approved_duration_days` harus 1..`max_duration_days`.
- `rejected`: status menjadi `rejected`; tidak boleh membawa durasi.
- `needs_clarification`: status tetap `pending`; `secondary_status=needs_clarification`; `approval_note` diisi.
- `expired`: hanya jika permit sudah melewati `expected_return_datetime`; status tetap `pending`; `secondary_status=expired`.
- `cancelled`: hanya untuk permit `pending`; status tetap `pending`; `secondary_status=cancelled`.
- Keputusan `approved`/`rejected` bersifat terminal untuk command berikutnya; perubahan harus melalui alur revisi resmi, bukan command baru.

## 5. Respons sukses

```json
{
  "success": true,
  "message": "Keputusan izin tersimpan.",
  "data": {
    "outcome": "applied",
    "duplicate": false,
    "command_id": "cmd-uuid-001",
    "request_id": "uuid-dormitory-permit",
    "decision": "approved",
    "status": "approved",
    "approved_duration_days": 2,
    "departure_datetime": "2026-11-23T08:00:00+08:00",
    "expected_return_datetime": "2026-11-25T08:00:00+08:00",
    "decided_at": "2026-11-22T10:00:00+08:00",
    "actor": {
      "core_person_id": "uuid-kepala-asrama",
      "name": "Nama Kepala Asrama"
    },
    "source": "whatsapp",
    "external_message_id": "wamid-001",
    "message_text": "SETUJU 2 HARI"
  },
  "meta": {"timestamp": "2026-11-22T10:00:00+08:00"},
  "errors": null
}
```

`outcome`:

- `applied` untuk `approved`/`rejected`.
- `needs_clarification`, `expired`, `cancelled` untuk keputusan terkait.
- `duplicate` untuk replay command.

## 6. Duplikat dan idempotency

- `command_id` unik. Command yang sama dengan payload hash sama mengembalikan hasil sebelumnya, HTTP 200, `duplicate: true`, `outcome: duplicate`.
- `command_id` sama dengan payload berbeda → HTTP 409 `command_id_reused`.
- `external_message_id` yang sudah dipakai command lain → HTTP 409 `external_message_replayed`.
- Unique constraint database + penanganan race memastikan tidak ada dua keputusan.
- Timeout setelah commit: Connect harus retry dengan `command_id` dan `external_message_id` yang sama; Core mengembalikan hasil sebelumnya.

## 7. Error

| HTTP | `errors.code` | Arti |
|---|---|---|
| 401 | — | API key tidak ada/salah |
| 403 | `insufficient_scope` | Key tidak punya `approval:command` |
| 403 | `actor_identity_missing` | `actor_external_id` kosong |
| 403 | `actor_identity_unmapped` | Nomor belum terverifikasi/terpetakan |
| 403 | `actor_inactive` | User pemetaan tidak aktif |
| 403 | `actor_not_authorized` | Bukan Kepala Asrama aktif pada asrama permit |
| 404 | `permit_not_found` | Permit tidak ditemukan |
| 409 | `permit_not_decidable` | Status bukan pending |
| 409 | `permit_not_actionable` | Permit sudah cancelled/expired |
| 409 | `permit_expired` | Permit sudah lewat waktu |
| 409 | `command_id_reused` | Command ID dipakai payload berbeda |
| 409 | `external_message_replayed` | Pesan eksternal dipakai command lain |
| 409 | `quota_exceeded` | Kuota izin tidak tersedia saat approval |
| 422 | `validation_failed` | Payload tidak valid |
| 422 | `duration_invalid` / `duration_out_of_range` | Durasi tidak valid |
| 422 | `permit_not_expired` | Command `expired` tetapi permit belum kedaluwarsa |
| 500 | — | Kegagalan tak terduga; transaksi di-rollback |

Contoh error:

```json
{
  "success": false,
  "message": "Aktor bukan Kepala Asrama aktif pada asrama permohonan ini.",
  "data": null,
  "meta": {"timestamp": "2026-11-22T10:00:00+08:00"},
  "errors": {
    "code": "actor_not_authorized",
    "context": {}
  }
}
```

## 8. Audit dan retensi

Setiap command yang berhasil membuat:

- Baris `approval_commands`: `command_id`, `permit_id`, `actor_user_id`, `actor_external_identity`, `decision`, `source`, `external_message_id`, `message_text` (cuplikan), `payload_hash`, `response_code`, `response_body`, `processed_at`.
- Baris `audit_logs`: aktor, waktu, aksi, permit, IP/user agent, metadata command/source/message ID/hash pesan.
- Audit command bersifat **wajib**: kegagalan audit menyebabkan transaksi rollback.

`message_text` dinormalisasi dan dipotong maksimal 500 karakter. Kebijakan retensi/penghapusan akhir tetap memerlukan keputusan pemilik sistem.

## 9. Batas tanggung jawab

ALIM Core hanya menyediakan endpoint ini dan validasi domain. ALIM Connect bertanggung jawab atas:

- webhook WhatsApp,
- parser pesan,
- notifikasi,
- inbox,
- retry client,
- tampilan status komunikasi.

ALIM Connect tidak boleh menyatakan izin disetujui sebelum menerima respons sukses Core.
