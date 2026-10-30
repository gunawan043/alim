# Rancangan Integrasi SIPLah ALIM → BUMP

Status: rancangan kontrak. ALIM **tidak membuat tabel pengajuan SIPLah**. Seluruh data proposal, approval, status, dan realisasi disimpan di BUMP. ALIM hanya UI + API client.

## 1. Prinsip

- SIPLah di sini adalah **proposal/pengajuan kebutuhan barang**, bukan pembelian langsung ke marketplace SIPLah.
- ALIM mengelola formulir, pemeriksaan, persetujuan, dan menampilkan status.
- BUMP menyimpan data dan memproses pengadaan.
- ALIM tidak menyimpan tabel `siplah_*`.
- BUMP membuat tabel berdasarkan kontrak di dokumen ini.

## 2. Alur status

### Status ALIM/BUMP yang harus dibedakan
| Status | Sumber | Arti |
|---|---|---|
| `draft` | ALIM | Proposal masih disusun |
| `submitted` | ALIM | Diajukan untuk persetujuan |
| `approved` | ALIM | Disetujui, siap dikirim |
| `rejected` | ALIM | Ditolak saat persetujuan |
| `sent` | Integrasi | Sudah diterima BUMP |
| `waiting_confirmation` | BUMP | BUMP perlu konfirmasi |
| `processing` | BUMP | Sedang diproses |
| `partially_fulfilled` | BUMP | Dipenuhi sebagian |
| `completed` | BUMP | Selesai |
| `cancelled` | ALIM/BUMP | Dibatalkan |
| `failed` | Integrasi | Gagal sinkron/kirim |

Status approval ALIM dan status pemrosesan BUMP disimpan pada kolom berbeda.

## 3. Tabel di BUMP

### `siplah_proposals`
- `id` bigint PK
- `number` string unique (`SIPL-YYYYMM-XXXX`)
- `external_reference` string unique (ID ALIM, untuk idempotensi)
- `school_id` FK schools
- `work_unit_id` FK business_units/work_units
- `requested_by` string UUID ALIM
- `requester_name`, `requester_phone`
- `program_name`
- `purpose` text
- `budget_source`
- `priority` (`low/normal/high/urgent`)
- `needed_at` date
- `notes` text
- `status` string — status ALIM/approval
- `bump_status` string nullable — status pemrosesan BUMP
- `estimated_total`, `approved_total`, `final_total`, `realized_total` decimal
- `attachment_path`, `attachment_name`
- `rejection_reason`, `approved_by`, `approved_at`
- `sent_at`, `last_synced_at`, `completed_at`, `cancelled_at`
- `raw_payload` json
- timestamps

### `siplah_proposal_items`
- `id`, `siplah_proposal_id`
- `category`
- `name`
- `specification` text
- `unit`
- `qty`
- `unit_price`
- `total`
- `note`
- `qty_approved`, `qty_fulfilled`, `qty_backorder`
- `final_unit_price`, `realized_total`
- timestamps

### `siplah_proposal_logs`
- `id`, `siplah_proposal_id`, `status`, `source` (`alim`/`bump`), `note`, `user_id`, `created_at`

### `siplah_proposal_documents`
- `id`, `siplah_proposal_id`, `path`, `name`, `mime`, `size`, `uploaded_by`, timestamps
  (boleh disederhanakan menjadi kolom attachment pada proposal jika hanya satu dokumen)

### `siplah_integration_logs`
- `id`, `siplah_proposal_id`, `direction`, `action`, `http_status`, `request_payload` json, `response_payload` json, `error_message`, `created_at`

## 4. API BUMP yang harus disediakan

Base:
```text
{APP_URL}/api/procurement/v1
```
Auth:
```http
X-POS-PROCUREMENT-KEY: <token>
```
Payload dan respons memakai envelope:

```json
{
  "success": true,
  "message": "...",
  "data": {},
  "meta": {}
}
```

### `GET /siplah/proposals`
Filter: `status`, `bump_status`, `work_unit_id`, `school_id`, `search`, `page`, `per_page`.

### `POST /siplah/proposals`
Membuat proposal draft. ALIM mengirim seluruh data proposal.

```json
{
  "external_reference": "ALIM-SIPLAH-...",
  "school_id": "uuid",
  "work_unit_id": "uuid",
  "requested_by": "uuid",
  "requester_name": "Nama",
  "requester_phone": "08...",
  "program_name": "Program/Kegiatan",
  "purpose": "Alasan kebutuhan",
  "budget_source": "Sumber anggaran",
  "priority": "normal",
  "needed_at": "2026-11-05",
  "notes": "Catatan",
  "items": [
    {
      "category": "ATK",
      "name": "Kertas HVS",
      "specification": "A4 80gsm",
      "unit": "rim",
      "qty": 10,
      "unit_price": 50000,
      "total": 500000,
      "note": "Catatan item"
    }
  ],
  "estimated_total": 500000,
  "attachment": null
}
```

Response `201` mengembalikan `number`, `status`, `external_reference`, items, dan logs.

### `GET /siplah/proposals/{number}`
Detail proposal + status + logs + realisasi.

### `PUT /siplah/proposals/{number}`
Hanya saat status `draft`.

### `POST /siplah/proposals/{number}/submit`
Mengubah `draft` → `submitted`; mencatat log ALIM.

### `POST /siplah/proposals/{number}/approve`
Body: `approved_by`, `note`, optional `approved_total`.
Status `submitted` → `approved`; mencatat log ALIM.

### `POST /siplah/proposals/{number}/reject`
Body: `reason`, `user_id`.
Status `submitted` → `rejected`.

### `POST /siplah/proposals/{number}/cancel`
Body: `reason`.
Status `draft/submitted/approved` → `cancelled`.

### `POST /siplah/proposals/{number}/send`
Opsional; jika status `approved` langsung dikirim ke pemrosesan BUMP. BUMP mengubah status internal menjadi `sent`, lalu `bump_status` mengikuti pemrosesan.

### `GET /siplah/proposals/{number}/status`
Ringkasan status untuk polling ALIM.

## 5. Idempotensi dan kegagalan

- `external_reference` wajib unik. Request ulang dengan referensi yang sama mengembalikan proposal yang sama.
- `POST /siplah/proposals` aman diulang.
- ALIM menyimpan `external_reference` di **local state UI/session** hanya sementara; data utama tetap di BUMP.
- Setiap panggilan API dicatat di `siplah_integration_logs` BUMP.
- Jika gagal jaringan, ALIM menampilkan opsi **Coba Lagi** dengan `external_reference` yang sama.

## 6. Realisasi

Saat BUMP mengubah status, ALIM cukup mengambil detail proposal:
- `qty_approved`, `qty_fulfilled`, `qty_backorder`
- `final_unit_price`
- `final_total`
- `realized_total`
- `bump_status`
- `logs`

ALIM menampilkan realisasi tanpa menghitung ulang data internal BUMP.

## 7. Batasan

- Tidak ada integrasi langsung ke marketplace SIPLah.
- Tidak ada pembuatan tabel SIPLah di ALIM.
- Tidak mengubah `stock_requests` / `purchase_orders` BUMP.
- Kontrak ini harus disepakati sebelum BUMP menambah endpoint dan tabel.
