---
name: alim-core-backup-restore-runbook
description: Runbook backup, verifikasi, dan restore ALIM Core produksi
metadata:
  type: runbook
  runbook-date: 2026-11-21
  status: draft
  scope: production-safety
  depends-on:
    - docs/integration/alim-core-production-safety-plan.md
---

# Runbook Backup & Restore — ALIM Core

> **Status:** draft operasional. Tidak ada perintah di dokumen ini yang dijalankan otomatis.
>
> **Prinsip:** backup belum sah sampai restore diuji. Jangan menjalankan operasi destruktif tanpa backup terverifikasi.

## 1. Tujuan dan ruang lingkup

Dokumen ini menjelaskan:

1. Cara membuat backup ALIM Core secara aman.
2. Cara memverifikasi backup.
3. Cara restore ke staging dan, bila darurat, ke produksi.
4. Kebijakan penyimpanan lokal dan GitHub.
5. Checklist sebelum/sesudah backup.

Cakupan data: database produksi, file dokumen (SK, logo/kop, tanda tangan/stempel), konfigurasi aplikasi yang tidak sensitif, dan metadata versi aplikasi.

## 2. Keputusan yang sudah disetujui

| Keputusan | Status |
|---|---|
| Freeze operasi destruktif | Disetujui |
| Backup di localhost | Disetujui |
| Backup di GitHub | **Perlu keputusan terkontrol** — lihat §6 |
| Boleh menambah kolom baru bila diperlukan | Disetujui secara prinsip, tetapi **belum ada kolom yang perlu ditambahkan untuk backup** |
| Implementasi kode/migration | **Belum disetujui** |

## 3. Rekomendasi arsitektur backup

Gunakan prinsip **3-2-1**:

- **3 salinan**: produksi, backup lokal, salinan offsite.
- **2 media**: disk lokal/NAS dan media offsite.
- **1 offsite**: lokasi terpisah, terenkripsi.

Rekomendasi terbaik:

1. **Backup lokal terenkripsi** di mesin backup/NAS.
2. **Salinan offsite terenkripsi** (external drive, NAS kedua, atau object storage privat).
3. **GitHub hanya untuk kode/dokumen/runbook**, bukan dump database mentah.
4. **Restore drill** berkala.

## 4. Inventaris data yang wajib dibackup

### 4.1 Database

| Kategori | Tabel |
|---|---|
| Organisasi | `work_units`, `divisis`, `domains`, `domain_role_permissions` |
| Sekolah/akademik | `schools`, `academic_years`, `grade_levels`, `study_groups`, `subjects`, `subject_groups` |
| GTK | `users`, `gtk_profiles`, `gtk_contacts`, `gtk_employments`, `gtk_work_unit`, `gtk_work_unit_histories` |
| Assignment | `structural_assignments`, `teaching_assignments`, `homeroom_assignments`, `coordinator_assignments`, `other_teacher_tasks`, `gtk_additional_tasks`, `gtk_cross_unit_assignments` |
| SK | `institution_decrees` |
| Permission | `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `permission_snapshots` |
| Audit | `audit_logs`, `activity_log` |
| Master pendukung | `jenis_gtk`, `additional_task_types` |
| Asrama (bila relevan) | `dormitories`, `dormitory_wings`, `dormitory_rooms`, `dormitory_residents`, `dormitory_permits`, `dormitory_attendances`, `dormitory_staff_assignments`, `room_supervisors` |

### 4.2 File

- Dokumen SK (`institution_decrees.document_path`).
- Logo/kop sekolah, tanda tangan/stempel.
- Foto/avatar bila dianggap penting.
- File lain di `storage/app/public` sesuai kebijakan retensi.

### 4.3 Metadata

- Tanggal/waktu backup.
- Ukuran dan checksum.
- Versi aplikasi/commit hash.
- Operator.
- Lokasi backup.
- Status verifikasi restore.

## 5. Pre-flight checklist

- [ ] Freeze operasi destruktif sudah diumumkan.
- [ ] Tidak ada migration/seeder yang berjalan.
- [ ] Kapasitas disk mencukupi (minimal 2× ukuran database + file).
- [ ] Kredensial database tersedia di secret manager, bukan di shell history.
- [ ] Maintenance window disetujui (jika perlu konsistensi penuh).
- [ ] Lokasi backup lokal dan offsite siap.
- [ ] Enkripsi tersedia (age/GPG).
- [ ] Staging/isolated instance siap untuk restore test.

## 6. Kebijakan penyimpanan: localhost dan GitHub

### 6.1 Rekomendasi utama

- **Localhost/NAS:** simpan backup terenkripsi.
- **Offsite:** simpan salinan terenkripsi di media terpisah.
- **GitHub:** simpan **runbook, skrip non-rahasia, dan dokumentasi**, bukan dump database.

### 6.2 Mengapa dump mentah tidak boleh di GitHub

Database ALIM mengandung:

- NIK, nomor KK, NPWP, NUPY, nomor SK.
- Data keluarga, kesehatan, kontak.
- Token/session/audit.
- Kredensial yang mungkin tersimpan di `system_settings`.

Risiko GitHub:

- Riwayat Git sulit dihapus permanen.
- Akses anggota repo/organisasi/token PAT.
- Ukuran file besar; LFS tetap dapat diunduh.
- Kebocoran tidak dapat ditarik kembali.
- Backup di repo bukan backup yang tahan ransomware/akun compromise.

### 6.3 Opsi GitHub terkontrol (hanya jika tetap diwajibkan)

Jika pemilik sistem tetap menghendaki GitHub:

1. Gunakan **repo privat terpisah**, bukan repo aplikasi.
2. Enkripsi **client-side** sebelum commit (age/GPG); jangan pernah commit plaintext.
3. Simpan hanya ciphertext + checksum + metadata.
4. Gunakan Git LFS untuk file besar.
5. Batasi akses ke anggota minimum; aktifkan 2FA.
6. Tetapkan retensi dan prosedur penghapusan.
7. Uji restore dari artefak GitHub.
8. **Jangan** menyimpan passphrase di repo/CI.

**Tetap tidak direkomendasikan** untuk data produksi penuh.

## 7. Prosedur backup lokal

> Placeholder: sesuaikan nama database, user, dan path. Jangan menulis password di command line.

### 7.1 Set environment (contoh)

```bash
export DB_HOST="127.0.0.1"
export DB_PORT="3306"
export DB_NAME="alim_db"
export DB_USER="backup_user"
export BACKUP_DIR="/backup/alim/$(date +%Y/%m/%d)"
export STAMP="$(date +%Y%m%dT%H%M%S)"
mkdir -p "$BACKUP_DIR"
```

Gunakan `~/.my.cnf` atau secret manager untuk password. Jangan gunakan `-p<PASSWORD>`.

### 7.2 Logical full backup

```bash
mysqldump \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  --events \
  --set-gtid-purged=OFF \
  --default-character-set=utf8mb4 \
  "$DB_NAME" | gzip -9 > "$BACKUP_DIR/alim-full-$STAMP.sql.gz"
```

Catatan:

- `--single-transaction` untuk konsistensi InnoDB.
- Untuk database sangat besar, pertimbangkan XtraBackup/physical snapshot.
- Jangan gunakan `--lock-all-tables` pada jam sibuk.

### 7.3 Backup tabel kunci (opsional, untuk restore selektif)

```bash
mysqldump --single-transaction --no-create-info \
  "$DB_NAME" \
  work_units schools structural_positions structural_assignments \
  gtk_work_unit gtk_employments institution_decrees \
  academic_years study_groups subjects \
  roles permissions model_has_roles role_has_permissions domain_role_permissions \
  | gzip -9 > "$BACKUP_DIR/alim-critical-$STAMP.sql.gz"
```

### 7.4 Backup file storage

```bash
tar -czf "$BACKUP_DIR/alim-storage-$STAMP.tar.gz" \
  -C /var/www/alim/storage/app/public .
```

Sesuaikan path dengan deployment.

### 7.5 Checksum dan enkripsi

```bash
# Linux: sha256sum; macOS: shasum -a 256
shasum -a 256 "$BACKUP_DIR"/* > "$BACKUP_DIR/SHA256SUMS"
age -r "<public-key>" -o "$BACKUP_DIR/alim-full-$STAMP.sql.gz.age" \
  "$BACKUP_DIR/alim-full-$STAMP.sql.gz"
```

Simpan private key di tempat aman, terpisah dari backup.

### 7.6 Salinan offsite

- Salin ciphertext ke media offsite.
- Verifikasi checksum setelah salin.
- Catat lokasi dan waktu.

## 8. Verifikasi backup

### 8.1 Verifikasi file

- [ ] Ukuran file wajar.
- [ ] Checksum cocok.
- [ ] File dapat didekripsi.
- [ ] `gzip -t` berhasil.

### 8.2 Restore ke staging

1. Siapkan database staging kosong.
2. Restore dump.
3. Jalankan migrasi status (jangan migrasi baru).
4. Jalankan smoke test:
   - Login Super Admin.
   - Buka daftar unit, sekolah, GTK.
   - Buka assignment struktural.
   - Buka halaman permission/role.
   - Cek jumlah baris tabel kunci.
5. Bandingkan jumlah baris dan checksum data kunci dengan sumber.

### 8.3 Kriteria lulus

- Restore selesai tanpa error.
- Jumlah baris tabel kunci sama.
- FK tidak rusak.
- Login dan halaman utama berfungsi.
- Tidak ada data sensitif yang terekspos di log.

## 9. Prosedur restore

### 9.1 Restore ke staging (wajib sebelum produksi)

```bash
gunzip -c alim-full-<stamp>.sql.gz | mysql "$DB_NAME"
```

Untuk ciphertext:

```bash
age -d -i <private-key> alim-full-<stamp>.sql.gz.age | gunzip -c | mysql "$DB_NAME"
```

### 9.2 Restore produksi (darurat, perlu persetujuan)

1. Umumkan maintenance/downtime.
2. Hentikan aplikasi/queue.
3. Backup kondisi saat ini (jika masih memungkinkan).
4. Restore backup terverifikasi.
5. Jalankan smoke test.
6. Aktifkan aplikasi.
7. Monitor log/error.
8. Catat insiden dan persetujuan.

**Jangan** menjalankan restore produksi tanpa persetujuan eksplisit dan backup kondisi terkini.

## 10. Retensi dan RPO/RTO

Usulan awal (perlu persetujuan):

| Parameter | Usulan |
|---|---|
| Backup harian | 30 hari |
| Backup mingguan | 90 hari |
| Backup bulanan | 1 tahun |
| Pre-change backup | Sampai perubahan stabil |
| RPO | ≤ 15 menit (transaksional), ≤ 24 jam (master) |
| RTO | ≤ 4 jam (kritis), ≤ 24 jam (penuh) |

## 11. Insiden data

1. Hentikan operasi destruktif.
2. Catat waktu, operator, aksi, tabel.
3. Jangan jalankan seeder/migration/rollback.
4. Amankan log.
5. Identifikasi backup terakhir.
6. Restore ke staging, verifikasi.
7. Restore produksi dengan persetujuan.
8. Post-mortem dan guard.

## 12. Peran

| Peran | Tanggung jawab |
|---|---|
| Pemilik sistem | Menyetujui kebijakan/retensi/restore produksi |
| Operator backup | Menjalankan backup/verifikasi |
| DBA/Platform | Restore, tuning, integritas |
| Security | Enkripsi, akses, audit |
| Pemilik data | Memverifikasi isi data |

## 13. Gate persetujuan

| Gate | Yang disetujui |
|---|---|
| B0 | Freeze dan backup lokal |
| B1 | Penyimpanan offsite/terenkripsi |
| B2 | Restore test |
| B3 | Restore produksi (darurat) |
| B4 | Perubahan kode/migration |

## Lampiran A. Checklist harian

- [ ] Backup selesai.
- [ ] Checksum cocok.
- [ ] Salinan offsite selesai.
- [ ] Ukuran/retensi sesuai.
- [ ] Error log backup kosong.
- [ ] Restore test berkala terjadwal.

## Lampiran B. Larangan

- Jangan commit dump database plaintext ke GitHub.
- Jangan menyimpan passphrase di repo.
- Jangan restore produksi tanpa persetujuan.
- Jangan mengandalkan satu backup.
- Jangan menganggap backup valid tanpa restore test.
