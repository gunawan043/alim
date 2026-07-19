# Wali Santri Mobile API — Database Schema Reference

Dokumentasi lengkap kolom database yang diperlukan untuk membangun API endpoint Wali Santri Mobile App (React Native).

Base URL: `https://alim.sekolah.sch.id/api/mobile/v1`

Semua endpoint membutuhkan auth header: `Authorization: Bearer {sanctum_token}`

---

## 1. Profil User (users)

**Endpoint**: `GET /auth/me`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| name | varchar(191) | Nama lengkap |
| email | varchar(191) | Email |
| avatar | varchar(191) | Default: `default-avatar.jpg` |
| no_hp | varchar(20) | No HP wali |
| hubungan | enum('ayah','ibu','kakek','nenek','wali','lainnya') | Hubungan dengan santri |
| nik_wali | varchar(30) | NIK wali |
| is_wali | tinyint(1) | Flag wali santri |
| is_active | tinyint(1) | Status aktif |
| last_login_at | timestamp | Login terakhir |
| created_at | timestamp | Waktu dibuat |

---

## 1A. Auth & Session Management

Token issuance, revocation, and per-device session listing.

### 1A.1 Register Wali Santri

**Endpoint**: `POST /auth/register`

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| name | string | ya | Nama lengkap |
| email | string | ya | Email unik |
| password | string | ya | Min 8 char |
| password_confirmation | string | ya | Sama dengan password |
| no_kk | string(20) | ya | Nomor KK |
| nik_wali | string(30) | ya | NIK wali |
| no_hp | string(20) | ya | No HP |
| hubungan | enum | ya | `ayah`/`ibu`/`kakek`/`nenek`/`wali`/`lainnya` |

Response 201:

```json
{
  "success": true,
  "message": "Registrasi berhasil.",
  "data": {
    "user": { "id": "...", "name": "...", "email": "...", "is_wali": true },
    "access_token": "<plaintext>",
    "token_type": "Bearer",
    "expires_in": 2592000,
    "expires_at": "2026-08-14T10:00:00+00:00",
    "abilities": ["attendance.read", "grades.read", "tahfidz.read", "permits.write", ...]
  }
}
```

### 1A.2 Login Email/Password

**Endpoint**: `POST /auth/login`

| Field | Tipe | Wajib |
|-------|------|-------|
| email | string | ya |
| password | string | ya |

Response 200: same envelope as register (no `user` omitted, `is_new_user` not present).
Response 422: validation error on invalid credentials OR on locked account.
Response 429: throttle on repeated failed attempts (10 attempts ⇒ 15 min lock).

### 1A.3 Login Google OAuth

**Endpoint**: `POST /auth/google`

| Field | Tipe | Wajib |
|-------|------|-------|
| google_id | string | ya |
| email | string | ya |
| name | string | ya |

Response 200:

```json
{
  "success": true,
  "data": {
    "user": {...},
    "access_token": "<plaintext>",
    "token_type": "Bearer",
    "expires_in": 2592000,
    "expires_at": "2026-08-14T10:00:00+00:00",
    "abilities": [...],
    "is_new_user": false
  }
}
```

### 1A.4 Logout (current device only)

**Endpoint**: `POST /auth/logout`

Auth: required.

Response 200: `{ "success": true, "message": "Logout berhasil." }`

### 1A.5 Logout All Devices

**Endpoint**: `POST /auth/logout-all`

Auth: required. Revokes every PAT for the authenticated user.

Response 200: `{ "success": true, "message": "Semua sesi berhasil dihapus.", "data": { "revoked": 3 } }`

### 1A.6 List Active Sessions

**Endpoint**: `GET /auth/sessions`

Auth: required.

The `platform` field is **derived at read time** from segment 3 of the
canonical `personal_access_tokens.name` (e.g. `mobile:wali:password:android:fp12345`
→ `platform = "android"`). It is not a dedicated column. See
`docs/sanctum-token-architecture.md` §2.3 for the parsing helper.

Response 200:

```json
{
  "success": true,
  "data": {
    "sessions": [
      {
        "id": "abc-123",
        "device_label": "HP Pak Kades",
        "platform": "android",
        "ip_last": "10.0.0.5",
        "abilities": ["attendance.read", ...],
        "current_device": true,
        "created_at": "2026-07-15T10:00:00+00:00",
        "last_used_at": "2026-07-15T11:23:00+00:00",
        "expires_at": "2026-08-14T10:00:00+00:00"
      }
    ]
  }
}
```

### 1A.7 Update Current Session Label

**Endpoint**: `PATCH /auth/sessions/current`

Auth: required.

Request body:

| Field | Tipe | Wajib |
|-------|------|-------|
| device_label | string(80) | ya |

Response 200: `{ "success": true, "data": { "session": { ... session object ... } } }`

### 1A.8 Revoke All Other Sessions

**Endpoint**: `DELETE /auth/sessions/others`

Auth: required. Revokes every PAT **except the current one** (matches "Sign out other devices" UX).

Response 200: `{ "success": true, "data": { "revoked": 2 } }`

### 1A.9 Token error responses

| HTTP | Code | When |
|------|------|------|
| 401 | `UNAUTHENTICATED` | Missing / invalid / expired Bearer token |
| 403 | `INSUFFICIENT_ABILITY` | Token lacks the required ability (Sprint 3+) |
| 422 | `VALIDATION_ERROR` | Validation failed (login lockout also surfaces here) |

---

## 2. Data Santri (students)

**Endpoint**: `GET /santri/{id}`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| name | varchar(255) | Nama santri |
| nisn | varchar(20) | NISN |
| nis | varchar(20) | NIS |
| nik | varchar(30) | NIK |
| no_kk | varchar(30) | No KK |
| gender | enum('L','P') | Gender |
| birth_place | varchar(100) | Tempat lahir |
| birth_date | date | Tanggal lahir |
| religion | varchar(50) | Agama |
| address | text | Alamat lengkap |
| rt | varchar(5) | RT |
| rw | varchar(5) | RW |
| hamlet | varchar(100) | Dusun |
| village_code | char(10) | Kode desa |
| district_code | char(7) | Kode kecamatan |
| city_code | char(4) | Kode kota |
| province_code | char(2) | Kode provinsi |
| postal_code | varchar(10) | Kode pos |
| phone | varchar(20) | No telepon |
| mobile_phone | varchar(20) | No HP |
| photo_path | varchar(255) | Path foto |
| father_name | varchar(255) | Nama ayah |
| father_occupation | varchar(100) | Pekerjaan ayah |
| father_income | decimal(15,2) | Penghasilan ayah |
| mother_name | varchar(255) | Nama ibu |
| mother_occupation | varchar(100) | Pekerjaan ibu |
| mother_income | decimal(15,2) | Penghasilan ibu |
| residence_type | enum(...) | Tipe tempat tinggal |
| transportation | enum(...) | Moda transportasi ke sekolah |
| child_number | int | Anak ke- |
| entry_date | date | Tanggal masuk |
| status | enum('active','inactive','graduate','dropped','transfer_in','transfer_out') | Status aktif |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## 3. Link Wali-Santri (wali_santri)

**Endpoint**: `GET /santri` (list all linked)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| user_id | char(36) | FK → users |
| student_id | char(36) | FK → students |
| role | varchar(20) | ayah/ibu/kakek/nenek/wali/lainnya |
| is_primary | tinyint(1) | Wali utama |
| status | varchar(20) | pending/active/suspended |
| verified_at | timestamp | |
| verified_by | char(36) | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## 4. Absensi Santri (student_attendances)

**Endpoint**: `GET /santri/{id}/attendance`, `GET /santri/{id}/attendance/history`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| study_group_id | char(36) | FK → study_groups (rombel) |
| academic_year_id | char(36) | FK → academic_years |
| attendance_date | date | Tanggal absensi |
| status | enum('hadir','izin','sakit','alpa') | Status kehadiran |
| arrival_time | time | Jam datang |
| leave_time | time | Jam pulang |
| notes | text | Catatan |
| recorded_by | char(36) | FK → users (pencatat) |
| verified_by | char(36) | FK → users (pemeriksa) |
| verified_at | timestamp | |
| created_at | timestamp | |

**Response format attendance/history?**  
`GET /santri/{id}/attendance/history?month=6&year=2025`

---

## 5. Absensi Asrama (dormitory_attendances)

**Endpoint**: `GET /santri/{id}/dormitory`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| room_id | char(36) | FK → dormitory_rooms |
| dormitory_id | char(36) | FK → dormitories |
| academic_year_id | char(36) | FK → academic_years |
| attendance_date | date | Tanggal |
| session | enum('subuh','pagi','siang','sore','isya','malam') | Sesi pemeriksaan |
| status | enum('hadir','izin','sakit','alpa','pulang') | Status |
| notes | text | Catatan |
| verified_by | char(36) | FK → users |
| verified_at | timestamp | |
| created_at | timestamp | |

---

## 6. Jadwal Pelajaran (jadwal_kbms)

**Endpoint**: `GET /santri/{id}/classes`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| school_id | char(36) | |
| academic_year_id | char(36) | FK → academic_years |
| study_group_id | char(36) | FK → study_groups |
| subject_id | char(36) | FK → subjects |
| teacher_id | char(36) | FK → users |
| day_of_week | tinyint | 1=Sen..7=Min |
| slot_index | smallint | Urutan slot di hari yang sama |
| start_time | time | Jam mulai |
| end_time | time | Jam selesai |
| room | varchar(50) | Ruang kelas |
| is_active | boolean | Status aktif |
| notes | text | Catatan |
| created_at | timestamp | |

**Hubungan ke tabel lain**:
- `subjects` → `code, name, category`
- `study_groups` → `name, code, grade_level_id`
- `grade_levels` → `level, name`
- `users` → `name, email` (guru)

---

## 7. Kalender Akademik (academic_calendars)

**Endpoint**: Belum ada (perlu dibuat baru)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| school_id | char(36) | |
| academic_year_id | char(36) | FK → academic_years |
| event_name | varchar(191) | Nama event |
| event_type | enum('hari_efektif','libur_nasional','libur_semester','libur_ponpes','ujian_harian','pts','pas','ujian_sekolah','kegiatan_ponpes','rapat','lainnya') | Tipe event |
| start_date | date | Tanggal mulai |
| end_date | date | Tanggal selesai |
| is_all_schools | tinyint | 1=all school |
| color | varchar(20) | Warna hex |
| description | text | Deskripsi |
| created_by | char(36) | FK → users |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## 8. Data Nilai (admin_nilai_sumatif + admin_nilai_formatif)

**Endpoint**: `GET /santri/{id}/grades`

**admin_nilai_sumatif**:
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| admin_book_id | char(36) | FK → teacher_admin_books |
| student_id | char(36) | FK → students |
| academic_year_id | char(36) | FK → academic_years |
| semester | enum('ganjil','genap') | |
| s1 | decimal(5,2) | Sumatif 1 |
| s2 | decimal(5,2) | Sumatif 2 |
| s3 | decimal(5,2) | Sumatif 3 |
| s4 | decimal(5,2) | Sumatif 4 |
| s5 | decimal(5,2) | Sumatif 5 |
| s6 | decimal(5,2) | Sumatif 6 |
| rs | decimal(5,2) | Rerata Sumatif |
| sts | decimal(5,2) | Sumatif Tengah Semester |
| sas | decimal(5,2) | Sumatif Akhir Semester |
| rsa | decimal(5,2) | Rerata Sumatif Akhir = (STS+SAS)/2 |
| nr_murni | decimal(5,2) | Nilai Raport Murni |
| nr_final | decimal(5,2) | Nilai Raport Final |
| ket | varchar(100) | Keterangan |
| created_at | timestamp | |

**admin_nilai_formatif**:
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| admin_book_id | char(36) | FK → teacher_admin_books |
| student_id | char(36) | FK → students |
| academic_year_id | char(36) | FK → academic_years |
| semester | enum('ganjil','genap') | |
| skor_lkpd | decimal(5,2) | Skor LKPD |
| skor_diskusi | decimal(5,2) | Skor Diskusi |
| skor_kuis | decimal(5,2) | Skor Kuis |
| skor_antarteman | decimal(5,2) | Skor Antarteman |
| created_at | timestamp | |

---

## 9. Prestasi Santri (student_achievements)

**Endpoint**: `GET /santri/{id}/achievements` (perlu dibuat)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| achievement_type | enum('akademik','non_akademik','hafalan','olahraga','seni','sains','lainnya') | Tipe |
| hafalan_category | enum('quran','hadits') | Kategori hafalan |
| event_name | varchar(191) | Nama event |
| organizer | varchar(191) | Penyelenggara |
| level | enum('internal','kecamatan','kabupaten_kota','provinsi','nasional','internasional') | Level |
| position | enum('juara_1','juara_2','juara_3','harapan_1','harapan_2','harapan_3','peserta','mumtaz_murtafi','lainnya') | Peringkat |
| position_detail | varchar(100) | Detail peringkat |
| event_date | date | Tanggal event |
| event_location | varchar(191) | Lokasi event |
| coach_id | char(36) | FK → users (pembina) |
| certificate_path | varchar(255) | Path sertifikat |
| photo_path | varchar(255) | Path foto |
| is_verified | tinyint | Terverifikasi |
| verified_by | char(36) | |
| verified_at | timestamp | |
| notes | text | Catatan |
| created_by | char(36) | |
| created_at | timestamp | |

---

## 10. Pengumuman / Surat Edaran (dormitory_posts)

**Endpoint**: `GET /santri/{id}/announcements` (perlu dibuat)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| dormitory_id | char(36) | FK → dormitories |
| title | varchar(191) | Judul |
| content | longtext | Isi |
| category | enum('pengumuman','peringatan','kegiatan','info_lainnya') | Kategori |
| visibility | enum('wali','semua','internal') | Target |
| needs_response | tinyint | Butuh tanggapan |
| is_pinned | tinyint | Disematkan |
| is_active | tinyint | Status aktif |
| attachment_path | varchar(191) | Lampiran |
| created_by | char(36) | FK → users |
| created_at | timestamp | |

**dormitory_post_responses** (tanggapan wali):
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| post_id | char(36) | FK → dormitory_posts |
| student_id | char(36) | FK → students |
| parent_name | varchar(191) | Nama wali |
| response_type | enum('ack','reply') | Jenis |
| message | text | Pesan |
| created_at | timestamp | |

---

## 11. Data Tahfidz & Hadits

### Tahfidz Setoran (tahfidz_setorans)
**Endpoint**: `GET /santri/{id}/tahfidz`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| teacher_id | char(36) | FK → users (guru) |
| tahfidz_group_id | char(36) | FK → tahfidz_groups |
| academic_year_id | char(36) | FK → academic_years |
| setoran_date | date | Tanggal setoran |
| week_number | tinyint | Pekan ke- |
| setoran_type | enum('ziyadah','murajaah','tikror') | Jenis |
| metode_pembelajaran | varchar(100) | Talaqqi/Tasmi/Mandiri |
| surah_start_id | smallint | Surah mulai |
| ayat_start | int | Ayat mulai |
| surah_end_id | smallint | Surah akhir |
| ayat_end | int | Ayat akhir |
| juz | tinyint | Juz |
| halaman_start | decimal(5,1) | Halaman mulai |
| halaman_end | decimal(5,1) | Halaman akhir |
| jumlah_halaman | decimal(4,1) | Jumlah halaman |
| jumlah_baris | int | Jumlah baris |
| hasil_hafalan | tinyint(1-100) | Nilai hafalan |
| khofi | tinyint | Nilai khofi (dalam hati) |
| jali | tinyint | Nilai jali (nyaring) |
| nilai_setoran | decimal(5,2) | Nilai akhir |
| capaian_target | enum('tercapai','belum_tercapai','melampaui') | |
| catatan_guru | text | Catatan |
| status | enum('lulus','ulang','ditunda') | Status |
| created_at | timestamp | |

### Tahfidz Evaluasi (tahfidz_evaluations)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| evaluator_id | char(36) | FK → users |
| evaluation_date | date | Tanggal evaluasi |
| evaluation_type | enum('bulanan','tengah_semester','akhir_semester','kenaikan_juz') | |
| juz_diuji | longtext | Juz yang diuji |
| halaman_diuji | decimal(5,1) | Halaman |
| nilai_tahfizh | decimal(5,2) | |
| nilai_tajwid | decimal(5,2) | |
| nilai_fashohah | decimal(5,2) | |
| nilai_keseluruhan | decimal(5,2) | |
| predikat | enum('mumtaz','jayyid_jiddan','jayyid','maqbul','rasib') | |
| rekomendasi | text | Rekomendasi |
| status | enum('lulus','tidak_lulus','perlu_perbaikan') | |
| notes | text | |
| created_at | timestamp | |

### Tahfidz Progress Recap (tahfidz_progress_recaps)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| total_juz_ziyadah | decimal | Juz baru ditambah |
| total_halaman_ziyadah | decimal | Halaman baru |
| total_juz_murajaah | decimal | Juz diulang |
| total_halaman_murajaah | decimal | Halaman diulang |
| total_setoran | int | Total setoran |
| total_hari_setoran | int | Hari ada setoran |
| rata_rata_nilai | decimal(5,2) | Rata-rata |
| pencapaian_target_persen | decimal | Persentase target |
| last_position_juz | tinyint | Juz terakhir |
| total_juz_completed | int | Juz selesai |
| hadits_count | int | Hadits |
| created_at | timestamp | |

### Tahfidz Mutabaah (tahfidz_mutabaah)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| recorded_by | char(36) | FK → users (musyrif) |
| record_date | date | Tanggal rekam |
| sholat_subuh | enum(...) | Berjamaah/sendiri/qadha/tidak/uzur |
| sholat_dzuhur | enum(...) | |
| sholat_ashar | enum(...) | |
| sholat_maghrib | enum(...) | |
| sholat_isya | enum(...) | |
| sholat_tahajud | tinyint | Jumlah rakaat |
| sholat_dhuha | tinyint | Jumlah rakaat |
| tilawah_halaman | int | Jumlah halaman tilawah |
| catatan_musyrif | text | Catatan |
| created_at | timestamp | |

### Tahfidz Juz Master (tahfidz_juz_master) — Data Referensi
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | tinyint | Juz 1-30 |
| juz_number | tinyint | Nomor juz |
| name | varchar(50) | Nama juz |
| halaman_start | int | Halaman mulai di mushaf |
| halaman_end | int | Halaman akhir |
| total_halaman | int | Total halaman |

### Tahfidz Surah Master (tahfidz_surah_master) — Data Referensi
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | smallint | 1-114 |
| number | tinyint | Nomor surah |
| name_arabic | varchar(100) | Nama Arab |
| name_latin | varchar(100) | Nama latin |
| juz | tinyint | Juz utama |
| total_ayat | int | Total ayat |
| total_halaman | decimal | Halaman di mushaf |
| revelation_type | enum('makkiyah','madaniyah') | |

### Tahfidz Hadits Master (tahfidz_hadits_master) — Data Referensi
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | tinyint | 1-99+ |
| hadits_number | tinyint | Nomor hadits |
| topic | varchar(191) | Topik |
| arabic_text | text | Teks Arab |
| narrator | varchar(100) | Periwayat |
| difficulty_level | enum('mudah','sedang','sulit') | Tingkat |

### Tahfidz Juz Progress (tahfidz_juz_progress)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| juz_number | tinyint | Juz yang dipantau |
| progress_type | enum('ziyadah','murajaah') | Jenis |
| halaman_start | decimal | |
| halaman_end | decimal | |
| status | enum('aktif','selesai','belum_mulai') | |
| completed_at | date | |
| created_at | timestamp | |

---

## 12. Target Hafalan (tahfidz_student_targets)

**Endpoint**: `GET /santri/{id}/tahfidz-targets` (perlu dibuat)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| tahfidz_group_id | char(36) | FK → tahfidz_groups |
| academic_year_id | char(36) | FK → academic_years |
| semester | enum('ganjil','genap') | |
| target_bulan | tinyint | Bulan 1-12 |
| juz_start | tinyint | Juz mulai |
| juz_end | tinyint | Juz akhir |
| surah_start_id | smallint | Surah mulai |
| ayat_start | int | Ayat mulai |
| surah_end_id | smallint | Surah akhir |
| ayat_end | int | Ayat akhir |
| target_halaman | decimal(5,1) | Target halaman |
| target_hadits | int | Target hadits |
| assigned_by | char(36) | FK → users (pengampu) |
| notes | text | Catatan |
| created_at | timestamp | |

---

## 13. Rapor Digital (admin_nilai_sumatif + admin_nilai_formatif + student_class_histories)

Tidak ada tabel baru — digabung dengan Data Nilai. Tapi perlu data tambahan dari:

**student_class_histories**:
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| study_group_id | char(36) | FK → study_groups |
| academic_year_id | char(36) | FK → academic_years |
| is_active | tinyint | Rombel aktif |
| join_date | date | Tanggal gabung |
| leave_date | date | Tanggal keluar |
| notes | text | |

**raport_registrations** (registrasi rapor):
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| academic_year_id | char(36) | FK → academic_years |
| semester | enum('ganjil','genap') | |
| status | enum('draft','final','printed') | |
| created_at | timestamp | |

---

## 14. Perizinan Santri (attendance_permits)

**Endpoint**: `GET /santri/{id}/permits` (perlu dibuat)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| school_id | char(36) | FK → schools |
| academic_year_id | char(36) | FK → academic_years |
| permit_type | enum('izin','sakit','cuti') | Tipe izin |
| start_date | date | Tanggal mulai |
| end_date | date | Tanggal selesai |
| reason | text | Alasan |
| document_path | varchar(255) | Dokumen lampiran |
| created_by | char(36) | FK → users |
| created_at | timestamp | |

---

## 15. Pengajuan Kunjungan Orang Tua (dormitory_visit_logs)

**Endpoint**: `GET /dormitory/visit-logs` (perlu dibuat)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| dormitory_id | char(36) | FK → dormitories |
| student_id | char(36) | FK → students |
| room_id | char(36) | FK → dormitory_rooms |
| visitor_name | varchar(191) | Nama pengunjung |
| visitor_id_number | varchar(30) | ID pengunjung |
| visitor_phone | varchar(20) | Telp pengunjung |
| visitor_relationship | enum('ayah','ibu','wali','lainnya') | Hubungan |
| purpose | enum('menjenguk','menjemput','lainnya') | Tujuan |
| expected_arrival_datetime | datetime | Waktu kedatangan rencana |
| actual_arrival_datetime | datetime | Waktu kedatangan aktual |
| departure_datetime | datetime | Waktu keberangkatan |
| expected_meet_duration_minutes | int | Durasi bertemu |
| notes | text | Catatan |
| approved_by | char(36) | FK → users |
| approved_at | timestamp | |
| approval_note | text | Catatan persetujuan |
| status | enum('pending','approved','rejected','completed','cancelled') | Status |
| created_by | char(36) | FK → users |
| created_at | timestamp | |

---

## 16. Pengajuan Kepulangan (dormitory_permits)

**Endpoint**: `POST /dormitory/permit`, `GET /dormitory/permits`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| room_id | char(36) | FK → dormitory_rooms |
| dormitory_id | char(36) | FK → dormitories |
| academic_year_id | char(36) | FK → academic_years |
| permit_type | enum('pulang','keluar_kota','berobat','sakit','keperluan_keluarga','lainnya') | Tipe |
| destination | varchar(191) | Tujuan |
| purpose | text | Keterangan tujuan |
| departure_datetime | datetime | Waktu berangkat |
| expected_return_datetime | datetime | Rencana pulang |
| actual_return_datetime | datetime | Aktual pulang |
| companion_name | varchar(191) | Nama penyerta |
| companion_relation | varchar(100) | Hubungan |
| companion_phone | varchar(20) | Telp penyerta |
| companion_is_mahrom | tinyint | Penyerta mahrom? |
| status | enum('pending','approved','rejected','returned','overdue') | Status |
| approved_by | char(36) | FK → users |
| approved_at | timestamp | |
| approval_note | text | Catatan approval |
| document_path | varchar(255) | Dokumen lampiran |
| created_by | char(36) | FK → users |
| created_at | timestamp | |

---

## 17. Riwayat Pelanggaran (violation_points) — Sekolah

**Endpoint**: `GET /santri/{id}/violations`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| study_group_id | char(36) | FK → study_groups |
| recorded_by | char(36) | FK → users |
| violation_date | date | Tanggal |
| violation_type | varchar(100) | Jenis pelanggaran |
| points | int | Poin |
| description | text | Deskripsi |
| action_taken | text | Tindakan |
| created_at | timestamp | |

---

## 18. Pelanggaran Asrama (dormitory_violations)

**Endpoint**: `GET /santri/{id}/dormitory-violations`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| room_id | char(36) | FK → dormitory_rooms |
| dormitory_id | char(36) | FK → dormitories |
| academic_year_id | char(36) | FK → academic_years |
| violation_date | date | Tanggal |
| violation_category | enum('ringan','sedang','berat') | Kategori |
| violation_type | varchar(100) | Jenis |
| description | text | Deskripsi |
| points | int | Poin |
| action_taken | text | Tindakan yang diambil |
| follow_up | text | Tindak lanjut |
| witness_id | char(36) | FK → users (saksi) |
| parent_notified_at | timestamp | Waktu wali dimaklumi |
| created_at | timestamp | |

---

## 19. Kegiatan Pesantren (agendas)

**Endpoint**: `GET /agendas` (perlu dibuat)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| agenda_category_id | char(36) | FK → agenda_categories |
| title | varchar(191) | Judul kegiatan |
| description | text | Deskripsi |
| start_date | date | Tanggal mulai |
| end_date | date | Tanggal selesai |
| location | varchar(191) | Lokasi |
| time_start | time | Waktu mulai |
| time_end | time | Waktu selesai |
| target_group | enum('semua','kelas','tahfidz','asrama','lainnya') | Target |
| status | enum('scheduled','ongoing','completed','cancelled') | Status |
| created_by | char(36) | FK → users |
| created_at | timestamp | |

**agenda_attendees**:
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| agenda_id | char(36) | FK → agendas |
| student_id | char(36) | FK → students |
| participant_type | enum('wajib','opsional') | Jenis |
| status | enum('confirmed','absent','excused') | Status hadir |
| created_at | timestamp | |

**agenda_categories**:
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| name | varchar(191) | Nama kategori |
| icon | varchar(191) | Icon |
| is_active | tinyint | Aktif |
| urutan | int | Urutan |
| created_at | timestamp | |

---

## 20. Kontak Ustadz/Wali Kelas (users + study_groups)

**Endpoint**: `GET /santri/{id}/teachers` (perlu dibuat)

Ambil dari:
- `study_groups.homeroom_teacher_id` → Wali Kelas
- `study_group_subjects.teacher_id` → Guru Mata Pelajaran
- `tahfidz_groups.teacher_id` → Guru Tahfidz
- `dormitories.supervisor_id` → Pengurus Asrama

Tabel `users` (guru):
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| name | varchar(191) | Nama lengkap |
| email | varchar(191) | Email |
| avatar | varchar(191) | Foto |
| no_hp | varchar(20) | No HP |
| roles | (via spatie permission) | Role: GTK, Guru, dll |
| created_at | timestamp | |

---

## 21. Pusat Dokumen / Surat Edaran (peraturan + dokumen_iso + sistem_settings)

**peraturan**:
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| peraturan_kategori_id | char(36) | FK → peraturan_kategori |
| judul | varchar(191) | Judul surat |
| deskripsi | text | Isi singkat |
| nomor_dokumen | varchar(191) | Nomor surat |
| tanggal_berlaku | date | Tanggal berlaku |
| tanggal_expired | date | Tanggal kadaluarsa |
| dokumen_path | varchar(191) | File PDF |
| versi | varchar(10) | Versi dokumen |
| status | enum('aktif','nonaktif','draft','revisi') | Status |
| catatan_perubahan | text | Catatan revisi |
| dibuat_oleh | char(36) | FK → users |
| ditandatangani_oleh | char(36) | FK → users |
| created_at | timestamp | |

**peraturan_kategori**:
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| nama | varchar(191) | SOP/SK/Peraturan/Yayasan |
| deskripsi | text | Deskripsi kategori |
| is_active | tinyint | Aktif |
| urutan | int | Urutan |
| created_at | timestamp | |

**dokumen_iso**:
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| nama_dokumen | varchar(255) | Nama dokumen |
| prosedur_konsultan | varchar(255) | Prosedur |
| pasal | varchar(100) | Pasal |
| kode_dokumen | varchar(50) | Kode |
| tanggal_berlaku | date | Berlaku |
| revisi_ke | varchar(20) | Revisi ke- |
| keterangan | text | Keterangan |
| kategori | varchar(20) | Kategori dokumen |
| link_dokumen | varchar(500) | URL download |
| divisi_id | char(36) | FK → divisis |
| is_active | tinyint | Aktif |
| created_at | timestamp | |

---

## 22. Data Kesehatan (student_health_records)

**Endpoint**: `GET /santri/{id}/health`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| blood_type | enum('A','B','AB','O','tidak_diketahui') | Golongan darah |
| height_cm | int | Tinggi badan cm |
| weight_kg | int | Berat badan kg |
| bmi | decimal(5,2) | BMI |
| allergies | text | Alergi |
| chronic_diseases | text | Penyakit kronis |
| current_medications | text | Obat yang dikonsumsi |
| emergency_notes | text | Catatan darurat |
| bpjs_number | varchar(30) | Nomor BPJS |
| insurance_provider | varchar(100) | Asuransi |
| insurance_number | varchar(50) | Nomor asuransi |
| last_physical_exam_date | date | Pemeriksaan terakhir |
| created_at | timestamp | |

**student_health_checkups** (checkup berkala):
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| checkup_date | date | Tanggal checkup |
| checkup_type | enum('rutin','akar','masuk') | Tipe |
| height_cm | int | Tinggi |
| weight_kg | int | Berat |
| bmi | decimal(5,2) | BMI |
| vision_left | decimal(4,2) | Penglihatan kiri |
| vision_right | decimal(4,2) | Penglihatan kanan |
| hearing_status | enum('normal','kurang','tidak_ada') | Pendengaran |
| dental_status | enum('normal','karies','gangguan') | Gigi |
| tb_screening_result | enum('negatif','akur','laten','aktif_suspect') | TB screening |
| exam_by | char(36) | FK → users |
| notes | text | Catatan |
| created_at | timestamp | |

---

## 23. Data Mahrom (student_mahroms)

**Endpoint**: `GET /santri/{id}/mahroms` (perlu dibuat)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| name | varchar(191) | Nama mahrom |
| id_number | varchar(30) | ID/NIK |
| relationship | enum('ayah','ibu','kakak','adik','paman','bibi','kakek','nenek','suami','istri','lainnya') | Hubungan |
| phone | varchar(20) | No HP |
| address | text | Alamat |
| photo_path | varchar(191) | Foto |
| is_active | tinyint | Aktif |
| is_primary | tinyint | Utama |
| notes | text | Catatan |
| created_at | timestamp | |

---

## 24. Info Asrama (dormitories + dormitory_rooms + dormitory_wings + dormitory_residents)

**dormitories**:
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| school_id | char(36) | FK → schools |
| code | varchar(20) | Kode asrama |
| name | varchar(191) | Nama asrama |
| gender | enum('putra','putri') | Gender penghuni |
| address | text | Alamat |
| capacity | int | Kapasitas |
| total_rooms | int | Total kamar |
| total_wings | int | Total sayap |
| head_id | char(36) | FK → users (kepala asrama) |
| supervisor_id | char(36) | FK → users (pengawas) |
| is_active | tinyint | Status |
| logo_path | varchar(255) | Logo |
| notes | text | Catatan |
| created_at | timestamp | |

**dormitory_wings**:
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| dormitory_id | char(36) | FK → dormitories |
| code | varchar(20) | Kode sayap |
| name | varchar(100) | Nama sayap |
| floor | tinyint | Lantai |
| capacity | int | Kapasitas |
| is_active | tinyint | Status |
| created_at | timestamp | |

**dormitory_rooms**:
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| dormitory_id | char(36) | FK → dormitories |
| wing_id | char(36) | FK → dormitory_wings |
| code | varchar(20) | Kode kamar |
| name | varchar(100) | Nama kamar |
| floor | tinyint | Lantai |
| gender | enum('putra','putri') | Gender |
| capacity | int | Kapasitas |
| room_type | enum('reguler','khusus','isolasi','musyrif') | Tipe |
| facility_notes | text | Fasilitas |
| is_active | tinyint | Status |
| created_at | timestamp | |

**dormitory_residents**:
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| room_id | char(36) | FK → dormitory_rooms |
| dormitory_id | char(36) | FK → dormitories |
| academic_year_id | char(36) | FK → academic_years |
| bed_number | tinyint | Nomor tempat tidur |
| is_active | tinyint | Status |
| check_in_date | date | Tanggal masuk |
| check_out_date | date | Tanggal keluar |
| check_out_reason | enum('lulus','mutasi','keluar') | Alasan keluar |
| notes | text | Catatan |
| created_at | timestamp | |

---

## 25. Notifikasi (notifications_universal)

**Endpoint**: `GET /notifications`, `PUT /notifications/{id}/read`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| user_id | char(36) | FK → users (penerima) |
| module | varchar(191) | Module source |
| reference_type | varchar(191) | Tipe referensi |
| reference_id | char(36) | ID referensi |
| reference_code | varchar(191) | Kode referensi |
| type | varchar(191) | Tipe notifikasi |
| action | varchar(191) | Aksi |
| title | varchar(191) | Judul |
| message | text | Isi pesan |
| data | longtext | Data tambahan JSON |
| is_read | tinyint | Sudah dibaca |
| read_at | timestamp | Waktu baca |
| is_archived | tinyint | Diarsipkan |
| priority | enum('low','medium','high','urgent') | Prioritas |
| action_url | varchar(191) | URL aksi |
| action_text | varchar(191) | Teks tombol |
| expires_at | timestamp | Kadaluarsa |
| created_at | timestamp | |

---

## 26. Riwayat Kelas (student_class_histories)

**Endpoint**: `GET /santri/{id}/class-history` (perlu dibuat)

Sama dengan poin 13 — ambil dari `student_class_histories`:
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | char(36) | UUID |
| student_id | char(36) | FK → students |
| study_group_id | char(36) | FK → study_groups (rombel) |
| academic_year_id | char(36) | FK → academic_years |
| attendance_number | int | No urut pindah |
| is_active | tinyint | Rombel aktif |
| join_date | date | Tanggal gabung rombel |
| leave_date | date | Tanggal keluar rombel |
| notes | text | Catatan |
| created_at | timestamp | |

---

## Fitur yang BELUM Ada di API (Perlu Dibuat Baru)

| Menu | Endpoint Baru | Tabel |
|------|--------------|-------|
| Jam Shalat Realtime | `GET /prayer-times` | system_settings (key: prayer_lat/prayer_lng/prayer_method) |
| Jam Shatan Realtime | `GET /prayer-times/{lat}/{lng}` | External API (Aladhan / Prayer-Asr) |
| Kalender Akademik | `GET /academic-calendars` | academic_calendars |
| Prestasi | `GET /santri/{id}/achievements` | student_achievements |
| Pengumuman | `GET /dormitory/posts` | dormitory_posts + responses |
| Pengajuan Kunjungan | `POST /dormitory/visit-request` | dormitory_visit_logs |
| Pengajuan Kepulangan | `POST /dormitory/permit` | dormitory_permits |
| Perizinan | `POST /santri/{id}/permits` | attendance_permits |
| Kegiatan Pesantren | `GET /agendas` | agendas + attendees |
| Surat Edaran | `GET /peraturan` | peraturan + peraturan_kategori |
| Kontak Ustadz | `GET /santri/{id}/teachers` | study_groups (homeroom_teacher_id) + users |
| Rapor Digital | `GET /santri/{id}/raport` | admin_nilai_sumatif + formatif + class_histories |
| Target Hafalan | `GET /santri/{id}/tahfidz-targets` | tahfidz_student_targets |
| Riwayat Absensi Bulanan | `GET /santri/{id}/attendance/recap` | student_attendances |
| Riwayat Kelas | `GET /santri/{id}/class-history` | student_class_histories |
