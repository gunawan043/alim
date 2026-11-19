# Rancangan — Integrasi Tahfidz (TIM TAHFIDZ) ke Penjadwalan Otomatis

Tanggal: 2026-11-19
Status: **sudah diimplementasikan** (lihat §10).

Lanjutan dari `docs/ANALISIS-GAP-JADWAL-OTOMATIS-2026-11-19.md`.

## 1. Konsep kunci: TIM TAHFIDZ

Beberapa guru tahfidz dapat mengajar di rombel yang sama. Karena itu, di jadwal
pelajaran tahfidz **tidak ditampilkan sebagai satu guru**, melainkan sebagai
**TIM TAHFIDZ** — gabungan seluruh guru tahfidz yang tercantum di SK untuk
rombel tersebut.

Pembagian internal (guru mana mengajar halaqah mana) dilakukan **setelah**
jadwal terbentuk, lewat menu Halaqah. Jadi:

```
SK (teaching_assignments)  →  menentukan SIAPA saja anggota tim
jadwal_kbms                →  menempatkan BLOK tahfidz (bukan per guru)
tahfidz_groups             →  membagi tim ke halaqah (setelah jadwal)
```

## 2. Aturan final

| # | Aturan |
| --- | --- |
| A1 | Tahfidz tidak boleh bersamaan dengan mapel lain |
| A2 | JP tahfidz dihitung **per rombel**, nilainya dari SK (`weekly_hours`) |
| A3 | Satu tingkat wajib punya JP tahfidz yang sama (sumber: peta kurikulum) |
| A4 | Halaqah boleh lintas rombel, **hanya dalam satu tingkat** |
| A5 | Satu pekan dibagi beberapa hari; pembagian otomatis dari setting JP/hari |
| A6 | Sumber guru = SK; halaqah hanya membagi, tidak menambah guru |
| A7 | Guru tahfidz yang sama tidak boleh dipakai dua tingkat pada slot yang sama |

## 3. Perubahan skema

| Tabel | Perubahan | Alasan |
| --- | --- | --- |
| `jadwal_kbms` | `teacher_id` menjadi **nullable** | Baris blok tahfidz tidak mewakili satu guru |
| `tahfidz_groups` | tambah `grade_level_id` (uuid, nullable, index) | Halaqah lintas rombel dalam satu tingkat |

Catatan:

- `verifyConflicts()` sudah memakai `whereNotNull('teacher_id')`, jadi
  perubahan ini konsisten dengan kode yang ada.
- Produksi memakai SQLite — perubahan nullability dilakukan lewat rebuild
  tabel oleh Laravel; tabel `jadwal_kbms` kecil sehingga aman.
- Tidak perlu kolom penanda "tim": baris tahfidz dikenali dari `subject_id`
  yang termasuk grup mapel Tahfidz + `teacher_id` null.

## 4. Identifikasi mapel tahfidz

Semua 8 sekolah memiliki:

- `subject_groups.name = 'Tahfidz'`
- `subjects.name = "Tahfidzul Qur'an"`, `code = THF`

Deteksi memakai `subjects.subject_group_id` → `subject_groups.name`
(mengandung "tahfidz", case-insensitive). Bila sekolah punya penamaan lain,
sediakan setting `tahfidz.subject_ids` sebagai override.

## 5. Alur generator yang baru

Urutan penting: **blok tahfidz ditempatkan lebih dulu**, sebelum mapel lain,
supaya slotnya tidak didahului mapel.

```
1. Pra-cek
   ├─ JP tahfidz per rombel dalam satu tingkat harus sama
   ├─ setiap rombel punya mapel tahfidz di SK (kalau tidak → laporan)
   └─ jumlah guru tim vs jumlah rombel (peringatan, bukan blokir)

2. Untuk setiap tingkat yang punya tahfidz:
   ├─ hitung total JP (dari SK, bukan dijumlah antar guru)
   ├─ bagi ke hari: days = ceil(total / jp_per_day), bagi rata
   ├─ pilih hari & slot awal (setting)
   ├─ tulis 1 baris jadwal_kbms per rombel per slot
   │    subject_id = mapel tahfidz, teacher_id = NULL, notes = 'TIM TAHFIDZ'
   └─ tandai seluruh guru tim sibuk pada slot tersebut

3. Tempatkan mapel lain seperti biasa
   ├─ isStudyGroupBusy() otomatis menolak slot blok tahfidz
   └─ isTeacherBusy() juga menolak guru yang sedang di blok tingkat lain

4. Verifikasi bentrok seluruh sekolah
```

### Pembagian JP ke hari

Contoh dengan `jp_per_day = 4`:

| Total JP | Hari | Pembagian |
| --- | --- | --- |
| 8 | 2 | 4 + 4 |
| 7 | 2 | 4 + 3 |
| 5 | 2 | 3 + 2 |
| 6 | 2 | 3 + 3 |

Aturan: `days = ceil(total / jp_per_day)`, lalu sisa dibagi rata sehingga
selisih antar hari maksimal 1 JP.

## 6. Setting baru

| Key | Default | Cakupan | Arti |
| --- | --- | --- | --- |
| `tahfidz.jp_per_day` | 4 | sekolah | JP maksimum tahfidz per hari |
| `tahfidz.start_slot` | 1 | sekolah | slot awal blok |
| `tahfidz.preferred_days` | kosong | sekolah | daftar hari yang dipakai (kosong = bebas) |
| `tahfidz.subject_ids` | kosong | sekolah | override mapel tahfidz |

Disimpan di `system_settings` lewat halaman Jam Pelajaran / Jadwal.

## 7. Tampilan

- Grid jadwal menampilkan sel **TIM TAHFIDZ** (bukan nama guru), dengan
  tooltip berisi daftar anggota tim.
- Kartu "Jadwal Tahfidz Halaqah" tetap ada di bawah grid.
- Laporan generate menambahkan baris: "Tahfidz tingkat 7: 8 JP → 2 hari (4+4)".

## 8. Validasi & kasus tepi

| Kasus | Perlakuan |
| --- | --- |
| JP tahfidz beda dalam satu tingkat | Blokir + pesan tingkat & rombel mana |
| Rombel tanpa mapel tahfidz di SK | Peringatan; rombel tetap dapat blok? → keputusan |
| Guru tim juga mengajar mapel lain | Dianggap sibuk selama blok |
| Halaqah anggotanya lintas tingkat | Ditolak saat simpan halaqah |
| Halaqah lintas rombel | Diizinkan bila `grade_level_id` sama |
| Kapasitas slot tidak cukup | Blokir dengan hitungan JP vs slot |

## 9. Keputusan yang menunggu — sudah diambil

1. `jp_per_day` **per sekolah** (kunci `tahfidz.jp_per_day`; kunci
   `tahfidz.jp_per_day.<school_id>` dipakai lebih dulu bila ada).
2. Rombel tanpa mapel tahfidz di SK: **dikecualikan**, dengan pesan yang
   menyebut SK penugasan sebagai tempat perbaikan.
3. Blok mulai dari `tahfidz.start_slot` (default 1).
4. Hari: mengikuti `tahfidz.preferred_days`; bila kosong generator memilih
   hari yang belum dipakai tingkat lain lebih dulu, lalu hari lain bila perlu.

## 10. Implementasi

| Berkas | Perubahan |
| --- | --- |
| `database/migrations/2026_11_19_000001_add_tahfidz_team_support.php` | `jadwal_kbms.teacher_id` nullable; `tahfidz_groups.grade_level_id` + index |
| `app/Services/JadwalGeneratorService.php` | blok TIM TAHFIDZ 2 fase, deteksi mapel, jendela per tingkat, kunci guru, seed dari blok lama |
| `app/Http/Controllers/JadwalKbmController.php` | laporan memuat `tahfidz`; guru tim melihat blok di jadwal pribadi |
| `app/Http/Controllers/JamPelajaranController.php` | form + `saveTahfidz()` untuk 4 setting tahfidz |
| `routes/web.php` | `POST {userId}/jam-pelajaran/tahfidz` |
| `app/Models/TahfidzGroup.php` | `grade_level_id` fillable + relasi `gradeLevel()` |
| `app/Http/Controllers/Tahfidz/HalaqahController.php` | tingkat halaqah + penolakan anggota beda tingkat |
| `resources/views/jadwal-kbm/_matrix.blade.php` | sel blok menampilkan **TIM TAHFIDZ** |
| `resources/views/jadwal-kbm/{show,cetak,teacher}.blade.php` | label TIM TAHFIDZ |
| `resources/views/jadwal-kbm/_report.blade.php` | kolom Tahfidz (terpasang/total + pembagian hari) |
| `resources/views/jam-pelajaran/index.blade.php` | kartu pengaturan blok tahfidz |
| `resources/views/tahfidz/halaqah/form.blade.php` | pilihan Tingkat + kelas santri pada daftar |

### Perilaku generator

1. **Fase 0** — muat SK semua rombel, hapus jadwal lama bila `overwrite`.
2. **Fase 1** — kunci blok tahfidz tingkat lain yang tidak ikut dihapus, lalu
   tempatkan blok per tingkat: satu baris per rombel per slot
   (`teacher_id = NULL`, `notes = 'TIM TAHFIDZ'`).
3. **Fase 2** — mapel lain per rombel; `isStudyGroupBusy()` dan
   `tahfidzBusy` menolak slot blok.

### Hasil uji (data lokal, 2026-11-19)

| Sekolah | Rombel | JP diminta | Terpasang | Blok tahfidz | Bentrok |
| --- | --- | --- | --- | --- | --- |
| 0606ab96 (15 rombel, 3 tingkat) | 15 | 675 | 675 | 8/8 JP tiap rombel | 0 |
| 27865788 (12 rombel, 3 tingkat) | 12 | 552 | 549–551 | 5/5 dan 7/7 JP | 0 |

Pembagian hari yang dihasilkan: 8 JP → 4+4 (2 hari), 7 JP → 4+3, 5 JP → 3+2.
Setiap tingkat memakai hari berbeda (mis. tingkat 9 Senin–Selasa, tingkat 7
Rabu–Kamis, tingkat 8 Jumat–Sabtu) karena guru tahfidznya sama.

Sisa kekurangan hanya kapasitas guru mapel umum (mis. PKN/Sejarah 1 JP),
bukan blok tahfidz.
