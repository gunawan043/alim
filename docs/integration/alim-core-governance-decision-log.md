---
name: alim-core-governance-decision-log
description: Catatan keputusan pemilik sistem untuk keamanan produksi dan integrasi ALIM Connect
metadata:
  type: decision-log
  date: 2026-11-21
  status: approved-for-planning
---

# Decision Log — ALIM Core Production Safety

> Catatan ini merekam keputusan pemilik sistem. Persetujuan di sini **bukan** persetujuan implementasi, migration, atau deployment.

## D-001 — Strategi penyimpanan backup

**Status:** Disetujui.

- Strategi utama: **backup lokal + offsite terenkripsi**.
- Backup database **tidak** dimasukkan ke repository GitHub, termasuk ciphertext, untuk saat ini.
- Jika di masa depan diperlukan repo privat terpisah:
  - hanya ciphertext;
  - encryption key tidak berada di repository atau lokasi backup yang sama;
  - akses minimum dan uji restore.

**Konsekuensi:** runbook backup harus mengikuti strategi ini; GitHub hanya untuk kode/dokumen.

## D-002 — PIC backup dan restore test

**Status:** Disetujui secara prinsip; nama PIC ditetapkan terpisah.

- Pelaksanaan backup/restore menjadi tanggung jawab PIC internal infrastruktur/database ALIM Core.
- Tidak boleh hanya mengandalkan agent pengembang.
- Perlu pemisahan:
  - pelaksana backup;
  - penanggung jawab penyimpanan;
  - reviewer hasil restore test.

**Tindak lanjut:** isi matriks RACI di `alim-core-backup-responsibility-matrix.md`.

## D-003 — Kebijakan penghapusan data

**Status:** Disetujui.

- **Archive/nonaktif adalah default** untuk data yang sudah pernah digunakan atau memiliki nilai historis.
- **Hard delete hanya boleh** jika:
  1. tidak memiliki dependensi/keterkaitan data;
  2. tidak berdampak pada histori akademik, kepegawaian, organisasi, dan audit;
  3. sudah melalui pemeriksaan dampak;
  4. mendapat persetujuan sebelum eksekusi.
- Tidak adanya foreign key **bukan** bukti data aman dihapus.

**Konsekuensi:** desain guard delete memakai `is_active`/`status` existing; hard delete harus melalui persetujuan.

## D-004 — Desain permission rebuild dan lifecycle penempatan

**Status:** Disetujui untuk disusun; **belum** disetujui untuk implementasi.

Ruang lingkup desain:

- analisis perilaku existing;
- inventaris assignment yang memengaruhi snapshot;
- desain invalidasi/rebuild;
- desain lifecycle `gtk_work_unit`;
- kompatibilitas data existing;
- strategi migrasi aman/backward-compatible;
- test dan rollback.

**Di luar ruang lingkup:** implementasi, migration, perubahan database, deployment.

## D-005 — Batas persetujuan

**Status:** Ditegaskan.

- Persetujuan desain **tidak** otomatis menjadi persetujuan implementasi.
- Setiap perubahan kode/migration/deployment memerlukan persetujuan terpisah.
- Freeze operasi destruktif tetap berlaku sampai guard/backup tersedia.

## Ringkasan status

| Item | Status |
|---|---|
| Freeze operasi destruktif | Disetujui |
| Backup lokal + offsite | Disetujui |
| Backup database di GitHub | Tidak untuk saat ini |
| PIC backup/restore | Menunggu nama |
| Archive/nonaktif default | Disetujui |
| Desain permission/lifecycle | Disetujui untuk disusun |
| Implementasi/migration/deploy | **Belum disetujui** |

## D-006 — Lifecycle `gtk_work_unit` Opsi B+

**Status:** Disetujui.

- Baris utama tetap di `gtk_work_unit`.
- Unique `(user_id, work_unit_id)` dipertahankan.
- Histori periode di `gtk_work_unit_periods`.
- Penempatan ulang memakai baris utama yang sama + periode baru.
- Baris `ended` tetap disimpan, tidak dipindahkan/dihapus.
- Histori tidak boleh hilang saat penempatan berakhir.

## D-007 — Foreign key `gtk_work_unit_periods`

**Status:** Disetujui.

- `user_id` dan `work_unit_id` memakai FK **RESTRICT**.
- Penghapusan induk harus melalui archive/nonaktif; hard delete memerlukan pemeriksaan dependensi dan persetujuan khusus.

## D-008 — Metadata permission snapshot

**Status:** Disetujui secara prinsip.

- Tambah `stale_at`, `scope_academic_year_id`, `scope_role_dimension`, `scope_tenant`.
- Format/identitas `scope_key` existing tidak berubah.
- Identitas snapshot `(user_id, scope_key, is_current)` tidak berubah.
- Snapshot lama harus kompatibel; metadata nullable; tidak boleh menjadi ketergantungan keras.
- Index disesuaikan dengan pola pencarian/rebuild.

## D-009 — Cakupan invalidasi

**Status:** Disetujui.

- Assignment: user + scope terkait.
- Role/permission: user/role terdampak.
- Global hanya jika perubahan benar-benar sistemik.
- Jangan rebuild seluruh snapshot untuk perubahan kecil.
- Rebuild gagal: snapshot stale tidak boleh memberi kewenangan; retry/logging terbatas.

## D-010 — PIC backup/restore

**Status:** Nama masih TBD.

- Tidak diisi berdasarkan asumsi.
- Matriks tanggung jawab dipertahankan sampai pemilik sistem menetapkan PIC.
- Staging/backfill tidak boleh dimulai sebelum backup + restore test diverifikasi PIC internal.

## D-011 — Jadwal staging/backfill

**Status:** Belum disetujui.

Prasyarat:

- inventaris pembaca/penulis `gtk_work_unit`;
- analisis kompatibilitas data;
- strategi backfill + validasi;
- estimasi dampak database/performa;
- rencana uji staging;
- kriteria sukses/gagal;
- rollback tanpa hapus data;
- feature flag.

## D-012 — Batas persetujuan

**Status:** Ditegaskan.

- Persetujuan arah desain bukan persetujuan implementasi.
- Tidak ada perubahan source code, migration, database, seeder, observer, job, atau konfigurasi pada tahap ini.
- Tidak ada backfill atau pengujian yang menulis data produksi.

## Ringkasan status terbaru

| Item | Status |
|---|---|
| Opsi B+ lifecycle | Disetujui |
| Baris ended tetap disimpan | Disetujui |
| FK periods RESTRICT | Disetujui |
| Metadata snapshot | Disetujui secara prinsip |
| Invalidasi berbasis cakupan | Disetujui |
| PIC backup/restore | TBD |
| Jadwal staging/backfill | Belum |
| Implementasi/migration/deploy | **Belum disetujui** |
