# Audit & Rencana Migrasi MySQL → Supabase (PostgreSQL)

Tanggal audit: 2026-10-28 · Database sumber: `common_admin` (MySQL 8, InnoDB, utf8mb4_unicode_ci)

## 1. Ringkasan data

| Item | Nilai |
|---|---|
| Total tabel | 565 |
| Total baris | 58.321 |
| Tabel berisi data | 80 |
| Tabel kosong | 485 |
| Foreign key | 1.458 |
| Data santri/siswa | **0 baris** (26 tabel `student_*`, `students`, `wali_santri` kosong) |

### Tabel inti GTK (terisi)

| Tabel | Baris |
|---|---|
| users | 477 |
| gtk_profiles | 476 |
| gtk_employments | 476 |
| gtk_work_unit | 476 |
| gtk_contacts | 476 |
| gtk_addresses | 454 |
| gtk_educations | 423 |
| structural_positions | 103 |
| jenis_gtk | 14 |
| work_units | 29 |
| schools | 8 |
| dormitories | 5 |
| model_has_roles | 363 |
| permissions | 306 |
| roles | 16 |

Tabel besar non-GTK: `gtk_gap_summaries` 42.590, `activity_log` 2.391, `gtk_analysis_runs` 1.712,
`study_group_subjects` 1.712, `audit_logs` 989, `dokumen_iso` 591.

## 2. Peta relasi inti

```
users ─┬─ gtk_profiles ──── gtk_addresses (gtk_profile_id)
       ├─ gtk_employments ─┬─ structural_positions (jabatan_id)
       │                   └─ jenis_gtk (jenis_gtk_id)
       ├─ gtk_contacts
       ├─ gtk_educations
       ├─ gtk_work_unit ──── work_units
       └─ model_has_roles ── roles ── role_has_permissions ── permissions

work_units ─┬─ schools (work_unit_id) ──┬─ users (principal_user_id)
            │                          └─ indonesia_* (wilayah)
            ├─ dormitories (work_unit_id) ── users (head_id)
            └─ divisis (divisi_id)

structural_positions ── jenis_gtk, roles, domains
```

Tidak ada relasi melingkar wajib antara `schools`/`dormitories`/GTK; urutan salin aman:
master → users → gtk_* → sekolah/asrama → transaksi.

## 3. Hambatan kompatibilitas PostgreSQL

| Kategori | Jumlah | Dampak |
|---|---|---|
| Migrasi dengan DDL MySQL (`MODIFY COLUMN`, `CHANGE COLUMN`, `FOREIGN_KEY_CHECKS`) | 16 file | Gagal saat `migrate` di PostgreSQL |
| Migrasi stored procedure / trigger MySQL | 6 file | Gagal (sintaks `CREATE PROCEDURE`, `DELIMITER`) |
| Kolom `enum()` | 395 | Aman — Laravel membuat `varchar` + check constraint |
| Kolom `year()` | 26 | Aman — dipetakan ke `smallint` |
| Kolom `char(36)` UUID | 2.074 | Aman |
| FULLTEXT index | 0 | — |
| Case sensitivity | global | MySQL `_ci` vs PostgreSQL case-sensitive: pencarian/unique email & username harus diuji |

### Daftar file yang perlu diperbaiki (driver-aware)

```
2026_04_20_100000_add_terlambat_to_admin_presensi_harian.php
2026_04_22_999999_add_mumtaz_murtafi_to_student_achievements_position.php
2026_06_17_100000_add_default_student_and_student_nik_to_wali_santri.php
2026_06_18_020000_extend_students_status_enum.php
2026_07_12_000002_add_sakit_to_dormitory_permits_enum.php
2026_07_12_000003_fix_inventory_condition_enum.php
2026_07_15_100000_alter_activity_log_for_uuids.php
2026_07_22_010000_extend_institution_decrees_with_approval_workflow.php
2026_07_26_065431_expand_uks_patient_status_enum.php
2026_07_26_233850_expand_uks_patient_status_enum.php
2026_07_27_000001_add_picked_up_status_to_dormitory_permits_table.php
2026_07_28_031209_add_picked_up_to_dormitory_permits_status.php
2026_07_31_120800_alter_work_units_add_health_type.php
2026_10_15_200000_add_terlambat_to_admin_presensi_siswa.php
2026_10_21_120300_fix_uks_patient_status_enum.php
2026_10_25_000001_align_permit_and_visit_policies.php
2026_02_14_135553_create_activate_academic_year_procedure.php
2026_02_14_135553_create_generate_teaching_decree_procedure.php
2026_02_14_135554_create_get_gtk_report_procedure.php
2026_05_20_120000_create_activate_academic_year_procedure.php
2026_06_18_034850_create_jadwal_kbms_table.php
2026_10_27_000200_allow_jadwal_conflicts.php
```

## 4. Rencana cutover

### Tahap 1 — Persiapan
- Buat project Supabase (region Singapore), simpan connection string **Session Pooler :5432**.
- Simpan kredensial di `.env` (`SUPABASE_DB_URL`), jangan commit.
- Backup MySQL: `mysqldump --single-transaction --routines --triggers`.

### Tahap 2 — Skema
- Opsi A (disarankan): perbaiki 22 migrasi agar driver-aware → `php artisan migrate --database=supabase`.
- Opsi B: `pgloader mysql://… postgresql://…` (skema + data sekaligus).

### Tahap 3 — Data
- Salin per tabel urut FK, chunk 500 baris, `session_replication_role = replica` saat load.
- Lewati: `migrations`, `activity_log`, `audit_logs` (opsional), `permission_snapshots`.

### Tahap 4 — Verifikasi
- Bandingkan jumlah baris per tabel (565 tabel) — harus identik.
- Cek orphan FK: setiap FK diuji `LEFT JOIN … WHERE child IS NULL`.
- Cek sequence/identity untuk tabel auto-increment.
- Uji login, daftar GTK, filter satuan kerja, simpan kepala sekolah/asrama.

### Tahap 5 — Cutover hosting
- Install/aktifkan `pdo_pgsql` + `pgsql`.
- Set `DB_CONNECTION=supabase`, `php artisan config:cache`.
- Mode maintenance → migrasi terakhir → buka akses.

### Tahap 6 — Backup & restore
- Backup harian Supabase (PITR) + `pg_dump` manual sebelum cutover.
- Uji restore ke project Supabase staging, verifikasi jumlah baris sama.

## 5. Yang masih dibutuhkan

1. Connection string Supabase (Session Pooler, port 5432).
2. Akses hosting (cPanel/SSH) untuk mengaktifkan `pdo_pgsql`.
3. Keputusan metode skema: Laravel migrations (Opsi A) atau pgloader (Opsi B).
