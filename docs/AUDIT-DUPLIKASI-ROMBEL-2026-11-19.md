# Audit Duplikasi Data Rombel — 2026-11-19

Pemicu: halaman `/{userId}/qr` menampilkan kelas ganda, dan beberapa halaman lain
menampilkan rombel duplikat.

Sumber data audit: database lokal (`common_admin`) — 8 sekolah, 717 santri.
Angka di produksi perlu diverifikasi ulang dengan query yang sama (§7).

## 1. Ringkasan

Ada **dua lapis masalah**, bukan satu:

1. **Data** — 20 rombel tahun ajaran 2025/2026 genap masih bertanda
   `is_active = 1` padahal tahun ajaran aktif sudah 2026/2027 ganjil.
   15 nama di antaranya sama dengan rombel tahun aktif → tampil dobel.
2. **Kode** — 45 query daftar rombel hanya menyaring `is_active`, tanpa
   menyaring `academic_year_id` tahun ajaran aktif. Beberapa bahkan sudah
   mengambil `$activeAy` tetapi tidak memakainya untuk daftar rombel.

Perbaikan data saja akan menghilangkan gejala hari ini. Tanpa perbaikan kode,
gejala yang sama muncul lagi setiap pergantian tahun ajaran.

## 2. Bukti data

| Pemeriksaan | Hasil |
| --- | --- |
| Tahun ajaran aktif | 2026/2027 ganjil (1 baris `is_active=1`) |
| Rombel `is_active=1` | 125 |
| Rombel di TA aktif | 105 |
| Rombel `is_active=1` di TA lain | 20 (semua 2025/2026 genap) |
| Duplikat nama **dalam** TA aktif | 0 |
| Duplikat nama lintas TA | 15 nama × 2 baris |
| Santri aktif di 20 rombel lama | 0 (semua kosong) |
| Sekolah terdampak | 1 sekolah (`2d30e191-…`), 38 rombel aktif → 18 unik |

Nama yang dobel: 1A, 1B, 1C, 1D, 2A, 2B, 2C, 2D, 3A, 3B, 3C, 4A, 4B, 4C,
5A, 5B, 5C, 6A, 6B, 6C (20 baris lama) berpasangan dengan nama sama di TA aktif.

Tidak ada duplikasi pada tabel lain: `grade_levels` (33), `subjects` (180),
`users` (477), `teaching_assignments` (860) — semuanya bersih.

**Koreksi penting (audit lanjutan 2026-11-19):** 20 rombel lama itu **bukan
baris kosong**. Masing-masing menampung 753–763 baris data terkait, termasuk
**393 penugasan mengajar** dan **393 buku nilai** dari SK
`110/PAH/SDIT-Pa/SK/VII/2026` — *Pembagian Tugas Semester Genap 2025/2026*
(terbit 2026-07-01). Jadi status "tidak punya santri" benar, tetapi "tidak
punya data lain" **salah**. Lihat §9.

## 3. Akar masalah

### 3.1 Data: rombel lama tidak pernah dinonaktifkan

- `BulkPromotionController` menonaktifkan `student_class_histories`, **tidak**
  menyentuh `study_groups.is_active`
  (`app/Http/Controllers/BulkPromotionController.php:177-178`,
  `:442-443`, `:506-508`).
- `academic:sync-active` memindahkan rombel yang masih punya santri aktif ke TA
  target, tetapi tidak menonaktifkan sisanya
  (`app/Console/Commands/SyncActiveAcademicYear.php:119-190`).
- Tidak ada satu pun kode yang menulis `study_groups.is_active = false`
  secara otomatis. Hanya bisa lewat edit manual di
  `StudyGroupController@update`.

### 3.2 Kode: daftar rombel tidak mengikuti TA aktif

Pola yang berulang: `StudyGroup::…->where('is_active', true)->get()`, tanpa
`where('academic_year_id', <TA aktif>)`.

Contoh paling jelas pada halaman yang dilaporkan:

```php
// app/Http/Controllers/ClassQrController.php:30-37
$activeAy = AcademicYear::where('is_active', true)->first();   // diambil

$allStudyGroups = StudyGroup::with(['gradeLevel', 'homeroomTeacher:id,name'])
    ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
    ->where('is_active', true)      // ← TA tidak difilter
    ->orderBy('grade_level_id')
    ->orderBy('name')
    ->get();
```

`$activeAy` hanya dipakai untuk memfilter token QR (baris 40), bukan daftar
kelasnya.

Pembanding yang **benar** — `StudyGroupController@index` memakai TA aktif
sebagai default dan menyediakan toggle "semua TA"
(`app/Http/Controllers/StudyGroupController.php:23-42`). Pola inilah yang
seharusnya dipakai halaman lain.

## 4. Daftar query terdampak (45 titik, 18 berkas)

| Berkas | Baris | Metode | Tampilan |
| --- | --- | --- | --- |
| `ClassQrController.php` | 32 | index | **QR Kelas** (dilaporkan) |
| `AbsensiHarianController.php` | 157, 436, 630, 812 | index, recapDetail, recap, recapSemester | Absensi harian + rekap |
| `JadwalKbmController.php` | 39, 82, 215, 408, 443, 547 | index, generateIndex, generateSingle, exportExcel, cetakAll, editAll | Jadwal KBM |
| `TeachingAssignmentController.php` | 65, 85, 125, 459 | index, create, bulkCreate, edit | Pembagian tugas mengajar |
| `BroadcastController.php` | 56 | index | Target broadcast |
| `ViolationPointController.php` | 49, 64, 111, 223, 277 | index, create, edit, dashboard, recap | Poin pelanggaran |
| `StudentHealthPermitController.php` | 68 | index | UKS — izin |
| `StudentHealthCheckupController.php` | 67 | index | UKS — pemeriksaan |
| `StudentHealthMetricController.php` | 62 | index | UKS — metrik |
| `StudentImmunizationController.php` | 52 | index | UKS — imunisasi |
| `StudentMedicineLogController.php` | 64 | index | UKS — obat |
| `StudentCounselingRecordController.php` | 72 | index | BK — konseling |
| `StudentAchievementController.php` | 117 | index | Prestasi santri |
| `OtherTeacherTaskController.php` | 84 | index | Tugas tambahan guru |
| `LabController.php` | 715, 973 | activityIndex, scheduleIndex | Lab — aktivitas & jadwal |
| `Bahasa/LanguageDailyLogController.php` | 62 | index | Bahasa — log harian |
| `Bahasa/MufradatController.php` | 249 | attendance | Bahasa — mufradat |
| `Personalia/AnalisisGtkController.php` | 33 | rasioIdeal | Analisis GTK |
| `Sarpras/SarprasBaseController.php` | 233 | responsibleRoomIds | Hak akses ruang Sarpras |
| `SatuanKerja/TaskController.php` | 259 | otherTasks | Tugas satuan kerja |
| `GradeLevelController.php` | 209, 266, 341 | propagateSubjectToStudyGroups, updateSubjectHours, removeSubject | Propagasi mapel ke rombel |

## 5. Dampak per kategori

| Dampak | Halaman | Tingkat |
| --- | --- | --- |
| Pilihan rombel dobel di dropdown | Jadwal KBM, absensi, UKS, BK, lab, bahasa, prestasi, tugas | Sedang — membingungkan, rawan salah pilih |
| **Data tercatat ke rombel yang salah** | Absensi harian, jadwal KBM | Tinggi — absensi/jadwal bisa masuk ke rombel arsip |
| **Broadcast dobel** | `BroadcastController` | Tinggi — pesan terkirim dua kali ke penerima yang sama |
| Statistik membengkak | Analisis GTK (rasio guru:rombel), dashboard sistem | Sedang — 125 vs 105 |
| Hak akses meluas | `SarprasBaseController::responsibleRoomIds` | Sedang — wali kelas lama tetap dianggap penanggung jawab |
| Propagasi mapel ke rombel arsip | `GradeLevelController` | Sedang — data mapel masuk ke rombel TA lama |

## 6. Rencana perbaikan

### Tahap 1 — Perbaikan data (cepat, tanpa ubah kode)

Nonaktifkan 20 rombel TA 2025/2026 genap yang tidak punya santri aktif.

- Wajib lewat **dry-run** dulu: tampilkan daftar 20 rombel + jumlah santri
  aktif + jumlah riwayat yang merujuk, lalu commit setelah disetujui.
- Aman karena `student_class_histories` tidak diubah — riwayat tetap utuh.
- Efek: 25–30 halaman langsung bersih tanpa deploy kode.

Risiko: rombel lama tidak lagi muncul di halaman master rombel
(`StudyGroupController@index` selalu memfilter `is_active = true`). Riwayat
santri tetap bisa dilihat lewat riwayat kelas, bukan lewat master rombel.

### Tahap 2 — Perbaikan kode (agar tidak terulang)

1. Tambah scope di `app/Models/StudyGroup.php`:

   ```php
   public function scopeForActivePeriod(Builder $q, ?string $academicYearId = null): Builder
   {
       $academicYearId ??= AcademicYear::where('is_active', true)->value('id');
       return $academicYearId ? $q->where('academic_year_id', $academicYearId) : $q;
   }
   ```

2. Terapkan ke 45 titik di §4, mulai dari yang berdampak tinggi:
   QR Kelas → Absensi harian → Jadwal KBM → Broadcast → Pembagian tugas.
3. Sisakan pengecualian yang memang lintas TA (mis. `whereIn('id', …)` untuk
   riwayat, `SarprasBaseController` bila penanggung jawab lama harus tetap
   diakui sampai serah terima).

### Tahap 3 — Pencegahan

- `academic:sync-active` menonaktifkan rombel di TA selain target yang tidak
  punya santri aktif (tambahan langkah, idempoten).
- Saat tahun ajaran diaktifkan (`AcademicYear`), jalankan penonaktifan yang sama.
- Pertahankan validasi unik `(school_id, academic_year_id, name)` yang sudah ada
  di `StudyGroupController@store` (`:173-180`) — ini sudah benar.

## 7. Verifikasi di produksi

Jalankan di server (read-only) untuk memastikan angka sama:

```sql
SELECT ay.name, ay.semester, COUNT(*) AS jumlah
FROM study_groups sg
JOIN academic_years ay ON ay.id = sg.academic_year_id
WHERE sg.is_active = 1
GROUP BY ay.name, ay.semester;

SELECT sg.school_id, sg.name, COUNT(*) AS n
FROM study_groups sg
WHERE sg.is_active = 1
GROUP BY sg.school_id, sg.name
HAVING n > 1;
```

Kalau hasilnya sama, Tahap 1 bisa dijalankan dengan aman.

## 8. Yang tidak saya ubah

Belum ada perubahan kode atau data. Dokumen ini hanya hasil audit.

## 9. Bisakah 20 rombel itu dihapus? — Tidak disarankan

Pertanyaan: kalau tidak punya santri dan datanya terkait, apakah aman dihapus?

Jawaban: **tidak**. "Tidak punya santri" benar, "tidak punya data lain" salah.

### 9.1 Isi setiap rombel lama

| Tabel | Baris per rombel | Catatan |
| --- | --- | --- |
| `gtk_gap_summaries` | 715 | snapshot analisis beban kerja (14.300 baris total) |
| `teaching_assignments` | 18–23 | SK Pembagian Tugas Genap 2025/2026 (393 total) |
| `teacher_admin_books` | 18–23 | buku nilai per guru (393 total) |
| `asset_rooms` | 1 | penanggung jawab ruang |
| `other_teacher_tasks` | 1 | tugas tambahan |
| **Total** | **753–763** | ~15.100 baris untuk 20 rombel |

Tidak ada satu pun dari 20 rombel yang benar-benar kosong.

### 9.2 Perilaku foreign key saat dihapus

Dari 36 constraint yang menunjuk `study_groups`:

- **CASCADE** (ikut terhapus diam-diam): `teacher_admin_books`,
  `qr_class_tokens`, `violation_points`, `teachings`, `homeroom_assignments`,
  `student_promotions`.
- **RESTRICT** (menghalangi): `jadwal_kbms`.
- **SET NULL** (jadi yatim): `gtk_employments`, `asset_rooms`,
  `other_teacher_tasks`, `exam_attempts`, `supervisi`, dll.
- **Tanpa FK sama sekali** (yatim tanpa peringatan): `teaching_assignments`,
  `gtk_gap_summaries`, `study_group_subjects`, `student_class_histories`.

Artinya satu `DELETE FROM study_groups` akan:
1. menghapus 393 buku nilai tanpa konfirmasi (CASCADE),
2. meninggalkan 393 penugasan mengajar sebagai baris yatim,
3. meninggalkan ~14.300 snapshot analisis sebagai baris yatim.

### 9.3 Mengapa datanya bernilai

SK `110/PAH/SDIT-Pa/SK/VII/2026` adalah dokumen resmi pembagian tugas
Semester Genap 2025/2026. Menghapus rombelnya berarti SK itu kehilangan
rujukan rombel, dan riwayat beban mengajar guru semester lalu ikut hilang.

### 9.4 Rekomendasi: nonaktifkan, jangan hapus

```sql
-- Tahap 1: hanya mengubah satu kolom, tidak menyentuh tabel lain
UPDATE study_groups
SET is_active = 0, updated_at = NOW()
WHERE is_active = 1
  AND academic_year_id <> '<id TA aktif>';
```

- Reversibel penuh (satu `UPDATE` balik).
- Tidak ada baris lain yang tersentuh.
- Duplikat hilang dari semua halaman yang memfilter `is_active`.
- Riwayat SK, buku nilai, dan analisis tetap utuh.

Konsekuensi: rombel lama tidak lagi muncul di halaman master rombel
(`StudyGroupController@index` selalu memfilter `is_active = true`). Riwayat
santri tetap dapat diakses lewat riwayat kelas dan SK.

Penghapusan permanen baru layak dipertimbangkan setelah:
1. SK Genap 2025/2026 diarsipkan/diekspor,
2. `teaching_assignments` dan `teacher_admin_books` untuk TA tersebut
   dipindahkan atau dihapus secara sadar,
3. `gtk_gap_summaries` dibersihkan lebih dulu (retensi snapshot).

### 9.5 Temuan sampingan yang lebih penting

Sekolah `2d30e191-…` **belum punya satu pun penugasan mengajar untuk TA aktif
2026/2027** (0 dari 467 baris; dua sekolah lain sudah terisi). Rombel aktifnya
juga punya struktur mapel (`study_group_subjects`) tetapi tanpa penugasan guru.
Jadi masalah sebenarnya bukan sekadar tampilan dobel — sekolah ini belum punya
SK pembagian tugas untuk tahun berjalan.

`gtk_gap_summaries` juga perlu perhatian terpisah: 237.717 baris menumpuk dari
beberapa kali analisis, tanpa mekanisme retensi.

## 10. Tindakan yang sudah dijalankan

### 10.1 Nonaktifkan rombel di luar TA aktif — SELESAI (database lokal)

Perintah baru: `php artisan rombel:deactivate-stale` (default dry-run,
`--commit` untuk menulis, `--school=` untuk membatasi).

```bash
php artisan rombel:deactivate-stale            # laporan
php artisan rombel:deactivate-stale --commit   # terapkan
```

Hasil pada database lokal (`common_admin`):

| Sebelum | Sesudah |
| --- | --- |
| 125 rombel `is_active = 1` | 105 |
| 20 rombel TA 2025/2026 genap aktif | 0 |
| 15 nama dobel | 0 |
| QR page sekolah `2d30e191` = 38 baris | 18 baris |
| 393 penugasan + 393 buku nilai TA lama | tidak berubah |

Rombel yang masih punya santri aktif otomatis dilewati (saat ini 0). Hanya
kolom `study_groups.is_active` yang diubah; tidak ada baris lain tersentuh dan
tidak ada penghapusan.

**Produksi belum dijalankan.** Setelah kode ter-deploy, jalankan perintah yang
sama di server: dry-run dulu, lalu `--commit`.

### 10.2 `gtk_gap_summaries` — temuan

| Metrik | Nilai |
| --- | --- |
| Total baris | 237.717 |
| Jumlah run (`gtk_analysis_runs`) | 4.429 dalam 8 hari |
| Rentang | 2026-10-28 s/d 2026-11-04 (tidak ada run baru sejak itu) |
| Ukuran tabel (MySQL, termasuk indeks) | 321,4 MB |
| Baris per run | min 2, rata-rata 54, maks 87 |
| Pemicu | `TeachingAssignmentChanged` 2.658, `StudyGroupSubjectChanged` 1.771 |

Akar masalah: setiap perubahan satu baris penugasan atau satu baris mapel
memicu satu analisis penuh. Impor 393 penugasan = 393 run = ~21.000 baris.
`RecalculateWorkloadJob` (`app/Jobs/RecalculateWorkloadJob.php`) tidak memakai
`ShouldBeUnique` atau debounce apa pun.

`gtk_gap_summaries.analysis_run_id` ber-FK **CASCADE** ke `gtk_analysis_runs`,
jadi membersihkan run lama otomatis membersihkan ringkasannya. Tidak ada
controller/view yang membaca `GtkAnalysisRun` secara langsung, sehingga
pemangkasan aman dari sisi UI.

Simulasi retensi "simpan 5 run terakhir per sekolah": 40 run, 1.945 baris
tersisa, **235.772 baris dapat dihapus** (~321 MB → ~3 MB).

Usulan (belum dijalankan):

1. `RecalculateWorkloadJob implements ShouldBeUnique` dengan
   `uniqueId() = school_id.':'.academic_year_id` dan `uniqueFor` 300 detik.
2. Command `gtk:prune-analysis-runs --keep=5` (dry-run default, `--commit`
   untuk menulis) yang menghapus run lama per sekolah; ringkasan ikut terhapus
   lewat CASCADE.
3. Jadwalkan pemangkasan mingguan di `app/Console/Kernel.php`.
