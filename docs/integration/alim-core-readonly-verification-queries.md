---
name: alim-core-readonly-verification-queries
description: Query read-only untuk memverifikasi integritas data organisasi, GTK, assignment, dan akademik ALIM Core
metadata:
  type: runbook
  runbook-date: 2026-11-21
  status: draft
  scope: read-only
  depends-on:
    - docs/integration/alim-core-production-safety-plan.md
---

# Query Verifikasi Read-Only — ALIM Core

> **Hanya SELECT.** Jangan menjalankan UPDATE/DELETE/INSERT/DDL.
>
> Jalankan terlebih dahulu di **staging/salinan produksi**. Jika harus di produksi, gunakan read replica atau maintenance window dengan persetujuan.

## 1. Aturan keselamatan

1. Gunakan user database **read-only**.
2. Jalankan di salinan/staging; bukan produksi, kecuali disetujui.
3. Batasi hasil dengan `LIMIT` untuk eksplorasi awal.
4. Jangan menjalankan agregasi berat pada jam sibuk.
5. Jangan gunakan `SELECT *` pada tabel besar.
6. Catat waktu, operator, dan query yang dijalankan.
7. Simpan hasil sebagai laporan, bukan mengubah data.

Contoh pembuka sesi:

```sql
SET SESSION TRANSACTION READ ONLY;
START TRANSACTION READ ONLY;
-- jalankan query
COMMIT;
```

## 2. Ringkasan output yang diharapkan

| Kode | Pemeriksaan | Anomali yang dicari |
|---|---|---|
| V-01 | Hierarki unit | `parent_id` null/self/cycle |
| V-02 | Divisi/domain unit | `divisi_id`/`domain_id` null |
| V-03 | Posisi struktural | `domain_id`/`jenis_gtk_id` null |
| V-04 | Unit–sekolah | unit tanpa sekolah / multi-sekolah |
| V-05 | Penempatan GTK | orphan, posisi null, primary ganda |
| V-06 | Assignment struktural | orphan, tanggal invalid, aktif ganda |
| V-07 | SK | SK dengan assignment terkait |
| V-08 | Akademik | tahun/tingkat/rombel/mapel tanpa relasi |
| V-09 | GTK/employment | user aktif tanpa employment, status terminal |
| V-10 | Tugas tambahan | tipe null/orphan |
| V-11 | Permission | role/permission/snapshot tidak konsisten |
| V-12 | Audit/histori | delete events, soft-deleted |
| V-13 | Dampak delete | jumlah dependensi per entity |

## 3. Query pack

### V-01 — Hierarki unit

```sql
-- Unit tanpa induk
SELECT COUNT(*) AS units_without_parent
FROM work_units
WHERE parent_id IS NULL AND deleted_at IS NULL;

-- Unit dengan parent yang tidak ada (orphan)
SELECT w.id, w.code, w.name, w.parent_id
FROM work_units w
LEFT JOIN work_units p ON p.id = w.parent_id AND p.deleted_at IS NULL
WHERE w.parent_id IS NOT NULL AND p.id IS NULL AND w.deleted_at IS NULL
LIMIT 100;

-- Unit yang menjadi parent dirinya sendiri
SELECT id, code, name
FROM work_units
WHERE parent_id = id AND deleted_at IS NULL;

-- Unit nonaktif tetapi masih menjadi parent unit aktif
SELECT p.id AS parent_id, p.code, p.name, COUNT(c.id) AS active_children
FROM work_units p
JOIN work_units c ON c.parent_id = p.id AND c.deleted_at IS NULL
WHERE p.deleted_at IS NOT NULL OR p.is_active = 0
GROUP BY p.id, p.code, p.name
ORDER BY active_children DESC;
```

**Interpretasi:** `parent_id` null belum tentu salah (unit root), tetapi jika mayoritas null, hierarki tidak terbentuk. Orphan/self-parent adalah anomali.

### V-02 — Divisi dan domain unit

```sql
SELECT COUNT(*) AS units_without_divisi
FROM work_units
WHERE divisi_id IS NULL AND deleted_at IS NULL;

SELECT COUNT(*) AS units_without_domain
FROM work_units
WHERE domain_id IS NULL AND deleted_at IS NULL;

SELECT d.id, d.kode, d.nama, COUNT(w.id) AS units
FROM divisis d
LEFT JOIN work_units w ON w.divisi_id = d.id AND w.deleted_at IS NULL
GROUP BY d.id, d.kode, d.nama
ORDER BY units DESC;

SELECT COUNT(*) AS units_with_invalid_divisi
FROM work_units w
LEFT JOIN divisis d ON d.id = w.divisi_id
WHERE w.divisi_id IS NOT NULL AND d.id IS NULL AND w.deleted_at IS NULL;
```

### V-03 — Posisi struktural

```sql
SELECT COUNT(*) AS positions_without_domain
FROM structural_positions
WHERE domain_id IS NULL AND is_active = 1;

SELECT COUNT(*) AS positions_without_jenis
FROM structural_positions
WHERE jenis_gtk_id IS NULL AND is_active = 1;

SELECT sp.id, sp.code, sp.name, sp.domain_id, sp.jenis_gtk_id
FROM structural_positions sp
LEFT JOIN domains d ON d.id = sp.domain_id
WHERE sp.domain_id IS NOT NULL AND d.id IS NULL
LIMIT 100;

-- Distribusi level/hierarchy
SELECT level, hierarchy_level, COUNT(*) AS total
FROM structural_positions
WHERE is_active = 1
GROUP BY level, hierarchy_level
ORDER BY level, hierarchy_level;
```

### V-04 — Unit dan sekolah

```sql
-- Unit dengan lebih dari satu sekolah (penting untuk WorkUnit::school hasOne)
SELECT work_unit_id, COUNT(*) AS schools
FROM schools
GROUP BY work_unit_id
HAVING COUNT(*) > 1
ORDER BY schools DESC;

-- Sekolah aktif tetapi unit nonaktif/terhapus
SELECT s.id, s.npsn, s.name, s.work_unit_id, w.is_active, w.deleted_at
FROM schools s
JOIN work_units w ON w.id = s.work_unit_id
WHERE s.is_active = 1 AND (w.is_active = 0 OR w.deleted_at IS NOT NULL)
LIMIT 100;

-- Sekolah tanpa unit (secara FK seharusnya tidak ada)
SELECT s.id, s.npsn, s.name, s.work_unit_id
FROM schools s
LEFT JOIN work_units w ON w.id = s.work_unit_id
WHERE w.id IS NULL
LIMIT 100;
```

### V-05 — Penempatan GTK (`gtk_work_unit`)

```sql
-- Orphan user/unit/position
SELECT gw.id, gw.user_id, gw.work_unit_id, gw.position_id
FROM gtk_work_unit gw
LEFT JOIN users u ON u.id = gw.user_id
LEFT JOIN work_units w ON w.id = gw.work_unit_id
LEFT JOIN structural_positions sp ON sp.id = gw.position_id
WHERE u.id IS NULL OR w.id IS NULL OR (gw.position_id IS NOT NULL AND sp.id IS NULL)
LIMIT 100;

-- Penempatan tanpa position_id
SELECT COUNT(*) AS placements_without_position
FROM gtk_work_unit
WHERE position_id IS NULL;

-- Primary ganda per user
SELECT user_id, COUNT(*) AS primary_count
FROM gtk_work_unit
WHERE is_primary = 1
GROUP BY user_id
HAVING COUNT(*) > 1;

-- Penempatan pada unit nonaktif/terhapus
SELECT gw.id, gw.user_id, gw.work_unit_id
FROM gtk_work_unit gw
JOIN work_units w ON w.id = gw.work_unit_id
WHERE w.is_active = 0 OR w.deleted_at IS NOT NULL
LIMIT 100;
```

### V-06 — Assignment struktural

```sql
-- Orphan relasi
SELECT sa.id, sa.user_id, sa.position_id, sa.school_id, sa.academic_year_id, sa.decree_id
FROM structural_assignments sa
LEFT JOIN users u ON u.id = sa.user_id
LEFT JOIN structural_positions sp ON sp.id = sa.position_id
LEFT JOIN schools s ON s.id = sa.school_id
LEFT JOIN academic_years ay ON ay.id = sa.academic_year_id
LEFT JOIN institution_decrees d ON d.id = sa.decree_id
WHERE sa.deleted_at IS NULL
  AND (u.id IS NULL OR sp.id IS NULL OR s.id IS NULL OR ay.id IS NULL)
LIMIT 100;

-- Tanggal invalid
SELECT id, user_id, position_id, start_date, end_date, status
FROM structural_assignments
WHERE deleted_at IS NULL AND end_date IS NOT NULL AND end_date < start_date
LIMIT 100;

-- Aktif tetapi posisi nonaktif
SELECT COUNT(*) AS active_assignments_with_inactive_position
FROM structural_assignments sa
JOIN structural_positions sp ON sp.id = sa.position_id
WHERE sa.deleted_at IS NULL
  AND sa.status = 'active'
  AND sp.is_active = 0;

-- Assignment efektif ganda untuk kombinasi unik
SELECT user_id, position_id, school_id, academic_year_id, COUNT(*) AS duplicates
FROM structural_assignments
WHERE deleted_at IS NULL
GROUP BY user_id, position_id, school_id, academic_year_id
HAVING COUNT(*) > 1;

-- Assignment aktif di luar tahun ajaran aktif
SELECT sa.id, sa.user_id, sa.academic_year_id, ay.is_active, ay.school_id
FROM structural_assignments sa
JOIN academic_years ay ON ay.id = sa.academic_year_id
WHERE sa.deleted_at IS NULL AND sa.status = 'active' AND ay.is_active = 0
LIMIT 100;
```

### V-07 — SK dan dependensi

```sql
-- SK dengan assignment terkait
SELECT d.id, d.decree_number, d.status,
       COUNT(sa.id) AS structural_assignments,
       COUNT(ta.id) AS teaching_assignments
FROM institution_decrees d
LEFT JOIN structural_assignments sa ON sa.decree_id = d.id AND sa.deleted_at IS NULL
LEFT JOIN teaching_assignments ta ON ta.decree_id = d.id AND ta.deleted_at IS NULL
WHERE d.deleted_at IS NULL
GROUP BY d.id, d.decree_number, d.status
HAVING structural_assignments > 0 OR teaching_assignments > 0
ORDER BY structural_assignments DESC
LIMIT 100;
```

### V-08 — Akademik

```sql
-- Tahun ajaran tanpa sekolah valid
SELECT ay.id, ay.name, ay.school_id
FROM academic_years ay
LEFT JOIN schools s ON s.id = ay.school_id
WHERE s.id IS NULL
LIMIT 100;

-- Rombel tanpa tahun/tingkat/sekolah valid
SELECT sg.id, sg.name, sg.school_id, sg.academic_year_id, sg.grade_level_id
FROM study_groups sg
LEFT JOIN schools s ON s.id = sg.school_id
LEFT JOIN academic_years ay ON ay.id = sg.academic_year_id
LEFT JOIN grade_levels gl ON gl.id = sg.grade_level_id
WHERE s.id IS NULL OR ay.id IS NULL OR gl.id IS NULL
LIMIT 100;

-- Mapel tanpa sekolah valid
SELECT sub.id, sub.name, sub.school_id
FROM subjects sub
LEFT JOIN schools s ON s.id = sub.school_id
WHERE s.id IS NULL
LIMIT 100;

-- Penugasan mengajar orphan
SELECT ta.id, ta.teacher_id, ta.study_group_id, ta.subject_id, ta.school_id, ta.academic_year_id
FROM teaching_assignments ta
LEFT JOIN users u ON u.id = ta.teacher_id
LEFT JOIN study_groups sg ON sg.id = ta.study_group_id
LEFT JOIN subjects sub ON sub.id = ta.subject_id
LEFT JOIN schools s ON s.id = ta.school_id
LEFT JOIN academic_years ay ON ay.id = ta.academic_year_id
WHERE ta.deleted_at IS NULL
  AND (u.id IS NULL OR sg.id IS NULL OR sub.id IS NULL OR s.id IS NULL OR ay.id IS NULL)
LIMIT 100;
```

### V-09 — GTK dan employment

```sql
-- User aktif tanpa employment/penempatan/profil GTK
SELECT u.id, u.name, u.email
FROM users u
LEFT JOIN gtk_employments e ON e.user_id = u.id
LEFT JOIN gtk_work_unit gw ON gw.user_id = u.id
LEFT JOIN gtk_profiles p ON p.user_id = u.id
WHERE u.deleted_at IS NULL AND u.is_active = 1
  AND e.id IS NULL AND gw.id IS NULL AND p.id IS NULL
LIMIT 100;

-- Employment dengan status terminal tetapi user masih aktif
SELECT u.id, u.name, e.status_kepegawaian
FROM users u
JOIN gtk_employments e ON e.user_id = u.id
WHERE u.deleted_at IS NULL AND u.is_active = 1
  AND e.status_kepegawaian IN ('Berhenti', 'Wafat', 'Pensiun')
LIMIT 100;

-- User dengan banyak employment
SELECT user_id, COUNT(*) AS employment_rows
FROM gtk_employments
GROUP BY user_id
HAVING COUNT(*) > 1
ORDER BY employment_rows DESC;
```

### V-10 — Tugas tambahan

```sql
-- Tugas legacy tanpa tipe
SELECT COUNT(*) AS legacy_tasks_without_type
FROM gtk_additional_tasks
WHERE additional_task_type_id IS NULL;

-- Tugas per sekolah tanpa tipe
SELECT COUNT(*) AS school_tasks_without_type
FROM other_teacher_tasks
WHERE additional_task_type_id IS NULL;

-- Tipe tugas tanpa jenis GTK valid
SELECT att.id, att.nama, att.jenis_gtk_id
FROM additional_task_types att
LEFT JOIN jenis_gtk jg ON jg.id = att.jenis_gtk_id
WHERE att.jenis_gtk_id IS NOT NULL AND jg.id IS NULL
LIMIT 100;

-- Tugas aktif tetapi tipe nonaktif
SELECT COUNT(*) AS active_tasks_with_inactive_type
FROM other_teacher_tasks ott
JOIN additional_task_types att ON att.id = ott.additional_task_type_id
WHERE ott.is_active = 1 AND att.is_active = 0;
```

### V-11 — Permission dan snapshot

```sql
-- Snapshot kedaluwarsa
SELECT COUNT(*) AS expired_snapshots
FROM permission_snapshots
WHERE expires_at IS NOT NULL AND expires_at < NOW();

-- Snapshot current ganda per user/scope
SELECT user_id, scope_key, COUNT(*) AS current_count
FROM permission_snapshots
WHERE is_current = 1
GROUP BY user_id, scope_key
HAVING COUNT(*) > 1;

-- User aktif tanpa role
SELECT u.id, u.name, u.email
FROM users u
LEFT JOIN model_has_roles mhr
  ON mhr.model_id = u.id AND mhr.model_type = 'App\\Models\\User'
WHERE u.deleted_at IS NULL AND u.is_active = 1 AND mhr.role_id IS NULL
LIMIT 100;

-- Role tanpa permission
SELECT r.id, r.name, COUNT(rhp.permission_id) AS permissions
FROM roles r
LEFT JOIN role_has_permissions rhp ON rhp.role_id = r.id
GROUP BY r.id, r.name
HAVING permissions = 0
ORDER BY r.name;
```

Catatan: `model_type` harus sesuai nilai yang disimpan Spatie (biasanya `App\Models\User`); verifikasi sebelum menjalankan.

### V-12 — Audit dan soft delete

```sql
-- Jumlah record soft-deleted per tabel kunci
SELECT 'work_units' AS entity, COUNT(*) AS deleted FROM work_units WHERE deleted_at IS NOT NULL
UNION ALL SELECT 'divisis', COUNT(*) FROM divisis WHERE deleted_at IS NOT NULL
UNION ALL SELECT 'domains', COUNT(*) FROM domains WHERE deleted_at IS NOT NULL
UNION ALL SELECT 'structural_assignments', COUNT(*) FROM structural_assignments WHERE deleted_at IS NOT NULL
UNION ALL SELECT 'users', COUNT(*) FROM users WHERE deleted_at IS NOT NULL;

-- Aksi delete di audit log (jika ada)
SELECT action, table_name, COUNT(*) AS total
FROM audit_logs
WHERE action LIKE '%DELETE%' OR action LIKE '%DELETED%'
GROUP BY action, table_name
ORDER BY total DESC;

-- Event log yang gagal/retry
SELECT status, COUNT(*) AS total
FROM integration_event_log
GROUP BY status;
```

### V-13 — Dampak delete per entity

```sql
-- Unit: jumlah sekolah, GTK, asrama
SELECT w.id, w.code, w.name,
       COUNT(DISTINCT s.id) AS schools,
       COUNT(DISTINCT gw.id) AS gtk_placements,
       COUNT(DISTINCT d.id) AS dormitories
FROM work_units w
LEFT JOIN schools s ON s.work_unit_id = w.id
LEFT JOIN gtk_work_unit gw ON gw.work_unit_id = w.id
LEFT JOIN dormitories d ON d.work_unit_id = w.id
WHERE w.deleted_at IS NULL
GROUP BY w.id, w.code, w.name
ORDER BY schools DESC, gtk_placements DESC
LIMIT 100;

-- Sekolah: jumlah tahun ajaran, rombel, assignment
SELECT s.id, s.npsn, s.name,
       COUNT(DISTINCT ay.id) AS academic_years,
       COUNT(DISTINCT sg.id) AS study_groups,
       COUNT(DISTINCT sa.id) AS structural_assignments,
       COUNT(DISTINCT ta.id) AS teaching_assignments
FROM schools s
LEFT JOIN academic_years ay ON ay.school_id = s.id
LEFT JOIN study_groups sg ON sg.school_id = s.id
LEFT JOIN structural_assignments sa ON sa.school_id = s.id AND sa.deleted_at IS NULL
LEFT JOIN teaching_assignments ta ON ta.school_id = s.id AND ta.deleted_at IS NULL
GROUP BY s.id, s.npsn, s.name
ORDER BY structural_assignments DESC, teaching_assignments DESC
LIMIT 100;

-- Posisi: jumlah assignment dan penempatan
SELECT sp.id, sp.code, sp.name,
       COUNT(DISTINCT sa.id) AS structural_assignments,
       COUNT(DISTINCT gw.id) AS gtk_placements
FROM structural_positions sp
LEFT JOIN structural_assignments sa ON sa.position_id = sp.id AND sa.deleted_at IS NULL
LEFT JOIN gtk_work_unit gw ON gw.position_id = sp.id
GROUP BY sp.id, sp.code, sp.name
ORDER BY structural_assignments DESC, gtk_placements DESC
LIMIT 100;
```

## 4. Format laporan

```text
Tanggal/waktu:
Operator:
Database/staging:
Versi aplikasi/commit:
Query dijalankan:
Hasil ringkas:
Anomali ditemukan:
Dampak potensial:
Rekomendasi:
Apakah perlu eskalasi:
```

## 5. Aturan interpretasi

- **Jangan langsung memperbaiki** temuan.
- **Jangan menjalankan seeder** untuk “membersihkan” data.
- **Jangan backfill** tanpa backup dan persetujuan.
- Bedakan:
  - anomali data yang aman (mis. `parent_id` null pada unit root),
  - anomali yang memengaruhi akses (mis. assignment tanpa posisi),
  - anomali yang memengaruhi integrasi (mis. `domain_id` null).
- Eskalasi temuan yang berdampak pada assignment/permission.

## 6. Larangan

- Jangan menjalankan query ini pada produksi tanpa persetujuan.
- Jangan menggunakan user database dengan hak tulis.
- Jangan menyimpan hasil yang memuat PII di repo publik/GitHub.
- Jangan menganggap hasil query sebagai izin perbaikan otomatis.
