# Analisis Gap — Penjadwalan Pelajaran Otomatis

Tanggal: 2026-11-19
Status: **analisis saja**, belum ada perubahan kode atau data.

Metode: pembacaan kode + simulasi generator pada data nyata di dalam
transaksi database yang **di-rollback**. Tidak ada baris jadwal yang tersimpan.

## 1. Ringkasan

Hasil generate yang jauh dari harapan bukan satu bug, melainkan tiga lapis
masalah yang saling menutupi:

| # | Lapis | Bukti |
| --- | --- | --- |
| 1 | **Data tidak layak** — beban guru melebihi kapasitas minggu | 1 guru diberi 120 JP; kapasitas maksimum 54 JP (9 slot × 6 hari) |
| 2 | **Data di tahun ajaran salah** — SK ada di TA lama | Sekolah `2d30e191`: 18 rombel, 0 JP; 393 assignment ada di 2025/2026 genap |
| 3 | **Algoritma greedy tanpa pra-cek** — tidak adil, berlubang, tanpa diagnosis | 8 dari 15 rombel mendapat 0 JP untuk satu mapel; 44 slot berlubang |

Poin penting: generator **tidak pernah salah menempatkan** (0 bentrok guru,
0 bentrok rombel di semua simulasi). Yang gagal adalah kecukupan, keadilan,
kerapian, dan pelaporan.

## 2. Cara kerja generator saat ini

`app/Services/JadwalGeneratorService.php`

```
generateBulk(rombel[])
  └─ per rombel (berurutan, satu per satu):
       generateForStudyGroup()
         ├─ ambil teaching_assignments aktif (TA + status)
         ├─ resolvePlans()      → mapel × guru × JP, urut JP terbesar
         ├─ masterSlotsByDay()  → slot dari class_schedule_slots per hari
         └─ per plan (urut JP desc):
              placePlan() → round-robin 6 hari, ambil slot bebas pertama
                ├─ isAllowed()      → teacher_schedule_availabilities
                ├─ isTeacherBusy()  → bentrok guru
                └─ isStudyGroupBusy() → bentrok rombel
```

Sifat algoritmanya: **greedy, tanpa backtracking, tanpa pra-cek kapasitas,
tanpa optimasi global.**

## 3. Bukti simulasi

Dijalankan pada dua sekolah yang punya SK di TA aktif.

### 3.1 Sekolah `27865788` — 12 rombel, 232 assignment, 552 JP

| Metrik | Hasil |
| --- | --- |
| JP terpasang | 550 / 552 |
| Rombel kurang | 2 (PKN/Sejarah 0/1) |
| Bentrok guru | 0 |
| Lubang jadwal | 17 |
| Hari mulai bukan slot 1 | 0 |

Kualitas cukup baik, tetapi tetap ada 17 lubang dan 2 JP gagal.

### 3.2 Sekolah `0606ab96` — 15 rombel, 235 assignment, 675 JP

| Metrik | Hasil |
| --- | --- |
| JP terpasang | 609 / 675 |
| Rombel kurang | **9 dari 15** |
| Bentrok guru | 0 |
| Lubang jadwal | **44** |

Kegagalan terpusat pada satu mapel: **Tahfidzul Qur'an**.

| Rombel | Hasil |
| --- | --- |
| 7A | 8/8 |
| 7B | 6/8 |
| 7C – 9E (8 rombel) | **0/8** |

Penyebabnya bukan slot rombel habis, tetapi **guru yang sama** dipakai untuk
15 rombel × 8 JP = **120 JP**, sedangkan kapasitas maksimum satu guru adalah
**54 JP/minggu** (9 slot × 6 hari).

Simulasi menunjukkan guru tersebut terisi tepat 54 JP — slot 1 sampai 9 pada
keenam hari, tanpa jeda — lalu delapan rombel sisanya tidak kebagian sama
sekali. Generator "menghabiskan" guru pada rombel pertama, bukan membagi.

### 3.3 Sekolah `2d30e191` — 18 rombel, 0 assignment di TA aktif

| Metrik | Hasil |
| --- | --- |
| JP diminta | 0 |
| JP terpasang | 0 |
| Pesan | "Tidak ada assignment (SK guru) aktif untuk rombel ini." |

393 assignment sekolah ini ada di **2025/2026 genap**, bukan di TA aktif.
Pesan generator benar secara teknis, tetapi tidak memberi tahu bahwa datanya
ada — hanya di tahun ajaran lain.

## 4. Daftar gap

Prioritas: **P0** menghalangi hasil benar, **P1** membuat hasil tidak rapi,
**P2** penyempurnaan.

### G1 — Tidak ada pra-cek kelayakan (P0)

Tidak ada pemeriksaan `butuh JP vs kapasitas` sebelum menulis apa pun.
Generator baru "tahu" gagal setelah slot habis, dan melaporkannya per rombel.

- Lokasi: `generateForStudyGroup()` baris 99–121, `placePlan()`.
- Dampak: 8 rombel kehilangan mapel tanpa penjelasan akar masalah.

### G2 — Alokasi tidak adil antar rombel (P0)

`generateBulk()` menyelesaikan satu rombel penuh sebelum pindah ke rombel
berikutnya. Guru bersama (mapel yang sama di banyak rombel) tersedot ke
rombel-rombel awal.

- Lokasi: `generateBulk()` baris 40–55.
- Bukti: guru `efebe403` = 54 JP, 8 rombel 0 JP.
- Seharusnya: alokasi bergilir antar rombel, atau tolak lebih awal dengan
  pesan "beban guru X 120 JP, kapasitas 54 JP".

### G3 — Tidak ada batas beban harian guru (P1)

Guru bisa ditempatkan 9 JP berturut-turut dalam sehari (bukti simulasi:
slot 1–9 penuh setiap hari). Tidak ada konfigurasi "maks JP per hari".

- Lokasi: `findFreeSlotOnDay()` — hanya cek bentrok, bukan beban.

### G4 — Jadwal berlubang (P1)

`findFreeSlotOnDay()` mengambil slot bebas **pertama** pada tiap hari secara
terpisah, sehingga jam mengajar bisa terputus (mis. slot 1, 2, lalu 5).

- Bukti: 44 lubang (sekolah `0606ab96`), 17 lubang (sekolah `27865788`).
- Catatan: `preferredAfter` hanya menjaga satu mapel tetap berurutan, bukan
  keseluruhan hari.

### G5 — TA default bisa salah (P0)

`resolveAy()` memilih tahun ajaran dengan **assignment terbanyak**, bukan TA
aktif. Sebelum perbaikan duplikasi rombel, halaman generate bisa default ke
2025/2026 genap dan menampilkan rombel lama.

- Lokasi: `JadwalKbmController::resolveAy()` baris 141–168.

### G6 — Pesan gagal tidak diagnostik (P0)

Laporan hanya berisi `subject`, `teacher`, `requested`, `placed`, `missing`.
Tidak menyebut sebab: kapasitas guru, slot rombel habis, availability, atau
TA salah.

- Lokasi: `generateForStudyGroup()` baris 109–117, `buildReport()`.

### G7 — Shift pagi/siang diabaikan (P2)

`study_groups.shift` ada, tetapi `class_schedule_slots` tidak punya kolom
shift dan generator tidak membacanya. Rombel siang memakai slot pagi.

### G8 — Blok 2 JP tidak dijamin (P1)

Mapel praktikum/PJOK idealnya 2 JP berurutan. Saat ini hanya "diutamakan"
lewat `preferredAfter`; kalau slot berikutnya sudah terpakai, JP pecah.

### G9 — Ruang/lab tidak dialokasikan (P2)

`room` hanya menyalin `study_groups.room`. Tidak ada cek bentrok ruang dan
tidak ada penugasan lab.

### G10 — Verifikasi bentrok terlalu sempit (P1)

`verifyConflicts([$studyGroupId])` hanya memeriksa rombel yang baru dibuat,
bukan seluruh sekolah. Bentrok dengan jadwal rombel lain tidak terdeteksi.

- Lokasi: `generateForStudyGroup()` baris 121.

### G11 — Tidak ada pratinjau (P1)

Generate langsung menulis. Tidak ada mode "hitung dulu, lihat hasil, baru
simpan" — padahal ini yang dibutuhkan untuk mencoba beberapa strategi.

### G12 — Proses berat & sinkron (P1)

Setiap JP butuh beberapa query cek + 1 insert. 675 JP ≈ ribuan query dalam
satu request. Di shared hosting, ini berisiko menyentuh `max_execution_time`.

### G13 — Kode mati membingungkan (P2)

`fallbackSlotsByDay()` dan konstanta `DEFAULT_*` tidak lagi dipakai
(`masterSlotsByDay()` mengembalikan `[]` bila master kosong). Menyulitkan
pembacaan dan menyesatkan saat maintenance.

## 5. Rekomendasi berurutan (belum dikerjakan)

### Tahap 1 — Supaya hasilnya bisa dipercaya (P0)

1. **Pra-cek kelayakan** sebelum menulis:
   - per guru: `Σ weekly_hours` vs `jumlah slot mengajar per minggu`;
   - per rombel: `Σ weekly_hours` vs `slot tersedia`;
   - per mapel: jumlah guru vs jumlah rombel.
   Tampilkan sebagai laporan pemblokir, bukan peringatan setelah gagal.
2. **Perbaiki `resolveAy()`** → default TA aktif; pilihan TA lain harus
   eksplisit.
3. **Laporan diagnostik**: untuk setiap kekurangan, sebut sebab dan saran
   ("tambah guru Tahfidz", "kurangi JP", "tambah slot hari Sabtu").

### Tahap 2 — Supaya jadwal rapi (P1)

4. **Alokasi bergilir**: satu JP per rombel per putaran, bukan satu rombel
   sampai selesai.
5. **Isi blok berurutan**: cari rentang slot kosong terpanjang, bukan slot
   bebas pertama.
6. **Batas JP/hari per guru** (konfigurasi sekolah, default 8).
7. **Blok 2 JP** untuk mapel yang ditandai butuh 2 JP berurutan.
8. **Pratinjau**: generate ke memori → tampilkan grid + laporan → tombol
   "Simpan".
9. **Verifikasi bentrok seluruh sekolah**, bukan hanya rombel yang digenerate.

### Tahap 3 — Penyempurnaan (P2)

10. Shift pagi/siang.
11. Alokasi ruang/lab.
12. Jalankan generate lewat queue untuk sekolah besar.
13. Hapus kode fallback yang sudah mati.

## 6. Catatan penting

- Perbaikan algoritma **tidak akan menolong** sekolah `2d30e191` selama SK
  TA 2026/2027 belum ada. Itu masalah data, bukan penjadwalan.
- Perbaikan algoritma **tidak akan menolong** sekolah `0606ab96` selama satu
  guru masih diberi 120 JP. Itu juga masalah data.
- Karena itu urutan yang disarankan: **pra-cek + laporan diagnostik dulu**,
  baru perbaikan algoritma. Tanpa pra-cek, algoritma sebaik apa pun tetap
  akan menghasilkan laporan yang membingungkan.

## 7. Cara mereproduksi analisis

Simulasi dijalankan di dalam transaksi yang di-rollback:

```php
DB::beginTransaction();
try {
    $svc = app(\App\Services\JadwalGeneratorService::class);
    $res = $svc->generateBulk($studyGroupIds, $academicYearId, 'ganjil', true);
    // ... analisis di sini ...
} finally {
    DB::rollBack();
}
```

Tidak ada data yang tersimpan dari proses ini.

## 8. Subsistem Halaqah Tahfidz (pemahaman awal)

Bagian ini mencatat bagaimana halaqah bekerja **saat ini**, sebagai dasar
integrasi ke penjadwalan otomatis. Belum ada perubahan.

### 8.1 Tabel dan relasi

| Tabel | Isi |
| --- | --- |
| `tahfidz_groups` | Halaqah: `name`, `gender`, `teacher_id` (guru utama), `coordinator_id`, `room`, `max_members`, `study_group_id`, `academic_year_id`, `school_id`, `work_unit_id` |
| `tahfidz_group_members` | Santri per halaqah: `student_id`, `academic_year_id`, `join_date`, `status` (aktif/pindah_halaqah/lulus) |
| `tahfidz_group_teachers` | Pivot guru per halaqah: `user_id`, `role` (`guru_utama`/`koordinator`), `can_input_setoran` |
| `tahfidz_schedules` | Jadwal halaqah: `day_of_week`, `time_start`, `time_end`, `room`, `schedule_type` (setoran/murajaah/tasmi/evaluasi/tikror), `effective_from`, `effective_until` |

Relasi:

```
rombel (study_groups) 1 ──< N halaqah (tahfidz_groups.study_group_id)
halaqah               1 ──< N guru (tahfidz_group_teachers)
halaqah               1 ──< N santri (tahfidz_group_members)
halaqah               1 ──< N jadwal (tahfidz_schedules)
```

- `study_group_id` pada halaqah baru ditambahkan 2026-10-19
  (`2026_10_19_100000_add_tahfidz_phase1_columns.php`) dan **nullable**.
- Satu santri hanya boleh aktif di satu halaqah per tahun ajaran; saat
  ditambahkan ke halaqah lain, keanggotaan lama diubah menjadi
  `pindah_halaqah` (`HalaqahController::attachMembers`).
- Keanggotaan **tidak** divalidasi terhadap rombel halaqah — santri dari
  rombel lain bisa masuk.

### 8.2 Siapa yang dianggap "Guru Tahfidz"

`HalaqahController::tahfidzTeachers()` menggabungkan tiga sumber:

1. `gtk_employments.jabatan` mengandung `tahfidz`/`tahfiz`/`musyrif`;
2. role atau tugas tambahan bernuansa tahfidz;
3. sudah mengampu halaqah di sekolah itu.

Guru yang dipilih untuk halaqah wajib lolos pemeriksaan ini
(`assertTeacherIsTahfidz`).

### 8.3 Jadwal halaqah saat ini

- Disimpan sebagai **jam** (`time_start`–`time_end`), bukan nomor slot.
- Bisa punya masa berlaku (`effective_from`/`effective_until`).
- `TahfidzScheduleConflictService` memeriksa tiga bentrok saat simpan:
  guru (termasuk koordinator dan guru pivot) bentrok dengan halaqah lain,
  ruang bentrok, dan guru bentrok dengan `jadwal_kbms` (overlap waktu).
- Di halaman Jadwal KBM, jadwal halaqah ditampilkan sebagai **kartu
  terpisah** di bawah grid (`resources/views/jadwal-kbm/show.blade.php:163`),
  tidak dimasukkan ke dalam sel grid.

### 8.4 Titik sambung yang belum ada

| # | Kenyataan | Kondisi kode |
| --- | --- | --- |
| H1 | Generator tidak tahu halaqah | `JadwalGeneratorService` hanya membaca `teaching_assignments` |
| H2 | SK tidak menyimpan halaqah | `teaching_assignments` tidak punya `tahfidz_group_id`; semua baris `role = guru_mapel` |
| H3 | Satu rombel bisa punya banyak guru tahfidz | `jadwal_kbms` unik per (rombel, hari, slot) — tidak bisa menampung halaqah paralel |
| H4 | Dua model waktu berbeda | KBM memakai `slot_index`; halaqah memakai `time_start`/`time_end` |
| H5 | Konflik hanya dicek saat simpan manual | `TahfidzScheduleConflictService` tidak dipanggil generator |
| H6 | Import SK mengizinkan banyak guru tahfidz per rombel | `ImportSkPembagianPelajaran.php:267` melewati penjagaan duplikat untuk mapel tahfidz |

### 8.5 Aturan yang sudah disepakati (2026-11-19)

Dari penjelasan pemilik sistem:

1. **Tahfidz berjalan sendiri.** Tidak boleh bersamaan dengan mapel lain —
   itu tabrakan.
2. **JP tahfidz per rombel**, bukan per halaqah. Besarnya mengikuti tingkat
   (SD/SMP/SMA) dan tercatat di SK.
3. **Boleh lintas rombel.** Karena itu tahfidz sering berjalan serentak
   dalam satu tingkat (mis. 7A–7E pada jam yang sama).
4. **Satu pekan dibagi beberapa hari.** Contoh 8 JP dibagi 2 hari × 4 JP.
   Diinginkan pengaturan "JP per hari" agar pembagian otomatis.
5. **Sumber kebenaran guru adalah SK.** Halaqah dibentuk setelah SK, sebagai
   pembagian internal dari guru-guru tahfidz yang sudah ditugaskan.

### 8.6 Konsekuensi ke desain generator

| # | Konsekuensi |
| --- | --- |
| K1 | Unit penjadwalan tahfidz = **tingkat**, bukan rombel. Blok tahfidz satu tingkat menempati slot yang sama untuk semua rombelnya. |
| K2 | Slot blok tahfidz harus **dikunci** agar mapel lain tidak masuk ke rombel mana pun di tingkat itu. |
| K3 | Guru tahfidz diambil dari SK (`teaching_assignments` mapel tahfidz) per rombel, lalu digabung per tingkat. |
| K4 | Blok tahfidz antar tingkat **tidak boleh berbagi guru** pada slot yang sama. |
| K5 | JP/hari perlu konfigurasi per sekolah (atau per tingkat): total JP dibagi otomatis ke beberapa hari. |
| K6 | Halaqah **tidak** dipakai untuk menempatkan slot — halaqah adalah pembagian guru/santri di dalam blok yang sudah dijadwalkan. Ini menghindari pelanggaran unik (rombel, slot) di `jadwal_kbms`. |

### 8.7 Pertanyaan yang masih terbuka

1. Apakah **semua** rombel satu tingkat wajib tahfidz serentak, atau hanya
   tingkat tertentu / hari tertentu?
2. Kalau JP tahfidz antar rombel dalam satu tingkat berbeda (mis. 8 vs 7),
   blok bersama mengikuti angka mana?
3. Apakah blok tahfidz selalu di **jam pertama**, atau bebas ditempatkan?
4. Halaqah lintas rombel: hanya dalam satu tingkat, atau boleh lintas tingkat?
5. `tahfidz_groups.study_group_id` hanya satu kolom — bagaimana menandai
   halaqah yang anggotanya dari beberapa rombel?
