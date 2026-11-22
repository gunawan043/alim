# Prompt Perancangan ALIM Mobile Wali

> Gunakan dokumen ini sebagai prompt utama untuk merancang produk, UX, dan
> information architecture `alim-mobile`.
>
> Ini adalah **prompt perancangan**, bukan prompt implementasi kode produksi.

---

## PROMPT UTAMA

Anda adalah **Product Designer, Mobile UX Architect, dan Solution Designer**
untuk aplikasi `ALIM Mobile Wali`.

Tugas Anda adalah menyusun blueprint produk mobile yang lengkap untuk aplikasi
React Native/Expo yang digunakan **khusus oleh wali santri**.

Jangan merancang untuk GTK, guru, admin, kasir, atau pengguna internal sekolah.
Jangan memasukkan LJK scanner, BUMP, SIPLah, hutang lembaga, pembayaran, atau
modul keuangan GTK.

Gunakan Bahasa Indonesia. Hasil harus siap dijadikan acuan desain Figma,
pengembangan Expo, dan penulisan backlog.

---

## 1. Konteks produk

ALIM adalah sistem informasi sekolah/pesantren. `ALIM Mobile Wali` adalah
aplikasi pendamping untuk wali santri agar dapat:

- memantau perkembangan anak
- melihat informasi akademik dan asrama
- melihat kesehatan dan perpustakaan
- menerima pengumuman dan notifikasi
- mengajukan izin, kunjungan, dan kepulangan
- mengakses informasi ibadah harian

Aplikasi harus terasa:

- tenang
- terpercaya
- hangat
- Islami tanpa berlebihan
- mudah dipakai orang tua
- ramah untuk pengguna awam teknologi

---

## 2. Pengguna utama

### Persona utama
**Wali santri**
- orang tua, kakek/nenek, atau wali resmi
- bisa memiliki 1–5 anak
- bisa memiliki anak di lebih dari satu sekolah
- menggunakan Android atau iOS
- tidak selalu paham istilah teknis
- membutuhkan informasi cepat, jelas, dan aman

### Kebutuhan utama
- “Bagaimana kondisi anak saya hari ini?”
- “Apakah anak saya masuk sekolah?”
- “Bagaimana nilai dan hafalannya?”
- “Apakah ada masalah di asrama?”
- “Apakah anak saya sakit?”
- “Apakah ada buku perpustakaan yang terlambat?”
- “Apa agenda sekolah minggu ini?”
- “Bagaimana cara mengajukan izin?”
- “Jadwal shalat dan doa hari ini?”
- “Apakah ada pengumuman penting?”

---

## 3. Batas produk

### Termasuk
- autentikasi wali
- pemilihan sekolah
- pemilihan anak
- profil anak
- pendidikan
- tahfidz
- prestasi dan lomba
- pelanggaran
- kesehatan
- perpustakaan
- asrama
- ekstrakurikuler
- guru
- pengumuman
- notifikasi
- agenda
- peraturan
- kalender
- jadwal shalat
- Al-Qur’an
- doa-doa
- izin/sakit
- kunjungan
- kepulangan
- survei
- kritik/saran
- profil wali
- sesi perangkat
- pengaturan notifikasi

### Tidak termasuk
- login GTK
- scan LJK
- BUMP ordering
- SIPLah
- hutang lembaga
- pembayaran
- kasir
- keuangan
- manajemen stok
- administrasi internal sekolah

---

## 4. Struktur navigasi utama

Gunakan bottom navigation dengan 5 tab:

```text
1. Beranda
2. Anak
3. Ibadah
4. Informasi
5. Akun
```

### Aturan navigasi
- Tab **Anak** hanya muncul datanya untuk anak yang dipilih.
- Jika wali memiliki lebih dari satu anak, sediakan child switcher global.
- Jika wali memiliki anak di lebih dari satu sekolah, sediakan school switcher.
- Jangan meminta wali mengetik ID sekolah.
- Simpan anak dan sekolah aktif.
- Semua data anak mengikuti konteks sekolah yang aktif.

---

# 5. Menu dan fitur

## 5.1 Beranda

Tujuan: memberi ringkasan kondisi anak dan akses cepat.

### Komponen
- Sapaan wali
- Nama anak aktif
- Nama sekolah aktif
- Child switcher
- Ringkasan absensi bulan ini
- Nilai terbaru
- Progress tahfidz
- Prestasi terbaru
- Pelanggaran terbaru
- Status kesehatan penting
- Buku perpustakaan yang mendekati jatuh tempo
- Agenda terdekat
- Pengumuman terbaru
- Jadwal shalat berikutnya
- Notifikasi belum dibaca

### Quick actions
- Ajukan izin/sakit
- Ajukan kunjungan
- Ajukan kepulangan
- Lihat rapor
- Lihat perpustakaan
- Lihat jadwal shalat
- Lihat agenda
- Lihat pengumuman

### State
- loading skeleton
- empty state
- error dengan tombol coba lagi
- pull to refresh
- indikator data terakhir diperbarui

---

## 5.2 Anak

Tujuan: pusat informasi per anak.

### 5.2.1 Profil anak
Tampilkan:
- nama
- NISN/NIS
- jenis kelamin
- tempat/tanggal lahir
- kelas aktif
- wali kelas
- sekolah
- status santri
- alamat dan kontak bila diizinkan
- data wali lain bila diizinkan

Sensitive data:
- NIK/KK jangan ditampilkan penuh di kartu ringkasan.
- Gunakan masking pada tampilan awal.

### 5.2.2 Pendidikan
Menu:
- Absensi
- Nilai
- Rapor
- Kelas
- Riwayat kelas
- Jadwal pelajaran
- Jadwal ujian

#### Absensi
- hari ini
- riwayat harian
- rekap bulanan
- status: hadir, izin, sakit, alpa
- jam datang/pulang bila tersedia
- catatan

#### Nilai
- nilai sumatif
- nilai formatif
- filter mata pelajaran
- filter semester
- hanya nilai yang dipublikasikan
- keterangan bila nilai belum tersedia

#### Rapor
- status rapor
- semester
- nilai akhir
- predikat
- peringkat kelas bila tersedia
- PDF rapor
- hanya rapor yang sudah dipublikasikan

#### Kelas
- kelas aktif
- tingkat
- wali kelas
- nomor absen
- tanggal masuk kelas

#### Riwayat kelas
- tahun ajaran
- kelas
- wali kelas
- tanggal masuk/keluar
- status aktif

#### Jadwal pelajaran
- harian
- mingguan
- mata pelajaran
- guru
- jam
- ruang

#### Jadwal ujian
- tanggal
- mata pelajaran
- guru
- jenis ujian
- jam
- ruang
- status
- catatan

### 5.2.3 Tahfidz
- target hafalan
- juz target
- total halaman
- total hadits
- progress
- setoran terbaru
- evaluasi
- nilai tahfizh
- nilai tajwid
- nilai fashohah
- predikat
- rekomendasi
- sertifikat tahfidz

### 5.2.4 Prestasi dan lomba
- daftar prestasi
- jenis prestasi
- nama lomba
- penyelenggara
- tingkat
- peringkat
- tanggal
- lokasi
- status verifikasi
- sertifikat
- foto kegiatan

Catatan: data lomba adalah lomba/prestasi yang sudah tercatat. Jika belum ada
fitur pendaftaran lomba, tampilkan sebagai riwayat prestasi.

### 5.2.5 Pelanggaran
- pelanggaran sekolah
- pelanggaran asrama
- poin
- jenis
- deskripsi
- tindakan
- tindak lanjut
- waktu notifikasi wali

### 5.2.6 Kesehatan
- profil kesehatan terkini
- golongan darah
- tinggi/berat/BMI
- alergi
- penyakit kronis
- obat aktif
- catatan darurat
- asuransi
- riwayat pemeriksaan
- riwayat pertumbuhan
- riwayat izin sakit
- riwayat kunjungan UKS
- diagnosis dan tindakan
- rujukan
- riwayat obat

Aturan:
- data kesehatan adalah data sensitif
- jangan tampilkan di notifikasi push
- gunakan akses per anak
- jangan cache permanen di perangkat

### 5.2.7 Perpustakaan
- peminjaman aktif
- riwayat peminjaman
- jatuh tempo
- keterlambatan
- denda
- riwayat kunjungan
- kartu anggota
- katalog buku
- pencarian buku
- ketersediaan buku
- program literasi

### 5.2.8 Asrama
- asrama aktif
- kamar
- sayap
- nomor bed
- tanggal masuk
- absensi asrama harian/bulanan
- pelanggaran asrama
- izin pulang
- pengajuan kunjungan
- pengajuan kepulangan
- riwayat kamar
- riwayat asrama
- penghargaan asrama

### 5.2.9 Ekstrakurikuler
- nama kegiatan
- pembimbing
- hari
- jam
- lokasi
- status keanggotaan
- tanggal bergabung
- tanggal keluar
- catatan

### 5.2.10 Guru
- wali kelas
- guru mata pelajaran
- mata pelajaran
- kontak bila tersedia

---

## 5.3 Ibadah

Tab ini berisi fitur tambahan yang **menggunakan API eksternal**, bukan data
utama ALIM.

### 5.3.1 Jadwal shalat
- jadwal hari ini
- subuh, dzuhur, ashar, maghrib, isya
- waktu shalat berikutnya
- nama kota
- tanggal hijriah bila tersedia
- sumber data
- tarik untuk memperbarui
- cache harian
- fallback saat offline
- pesan jelas jika kota/sekolah belum diatur

### 5.3.2 Al-Qur’an
- daftar surah
- pencarian surah
- nomor surah
- nama Arab dan Latin
- jumlah ayat
- detail surah
- daftar ayat
- teks Arab
- terjemahan
- audio bila API mendukung
- penanda terakhir dibaca
- bookmark
- pengaturan ukuran teks

### 5.3.3 Doa-doa
- kategori doa
- daftar doa
- detail doa
- teks Arab
- latin
- terjemahan
- sumber
- favorit
- pencarian
- offline cache

### Aturan integrasi ibadah
- Gunakan adapter/service terpisah untuk API eksternal.
- Jangan menyimpan seluruh konten eksternal di database ALIM.
- Tampilkan atribusi sumber.
- Tangani timeout, rate limit, dan offline.
- Gunakan cache yang aman.
- Jangan mencampur data ibadah dengan data akademik.

---

## 5.4 Informasi

### 5.4.1 Pengumuman
- daftar pengumuman
- kategori
- pengumuman sekolah
- pengumuman asrama
- pengumuman global
- lampiran
- tandai dibaca
- konfirmasi baca
- pencarian

### 5.4.2 Notifikasi
- daftar notifikasi
- belum dibaca
- sudah dibaca
- prioritas
- waktu
- aksi
- mark as read
- mark all read
- push notification
- pengaturan preferensi notifikasi

### 5.4.3 Agenda
- agenda sekolah
- kegiatan
- tanggal mulai/selesai
- deskripsi
- warna/kategori
- agenda terdekat
- filter bulan

### 5.4.4 Peraturan
- daftar peraturan aktif
- kategori
- judul
- nomor dokumen
- tanggal berlaku
- versi
- dokumen
- tandai dibaca

### 5.4.5 Kalender akademik
- kalender bulanan
- hari efektif
- libur
- ujian
- kegiatan
- filter bulan/tahun

### 5.4.6 Profil sekolah
- nama sekolah
- NPSN
- alamat
- telepon
- email
- website
- akreditasi
- kepala sekolah
- jam operasional
- kontak wali kelas/guru

### 5.4.7 Layanan
- pengajuan izin/sakit
- pengajuan kunjungan
- pengajuan kepulangan
- survei kepuasan
- kritik dan saran

### 5.4.8 Pengajuan izin/sakit
- pilih anak
- jenis: izin, sakit, diserahkan kepada pihak lain
- tanggal mulai
- tanggal selesai
- alasan
- kirim
- status
- riwayat

### 5.4.9 Pengajuan kunjungan
- pilih anak
- tanggal kunjungan
- tujuan
- catatan
- status
- riwayat

### 5.4.10 Pengajuan kepulangan
- pilih anak
- tanggal pulang
- tanggal kembali
- alasan
- status
- riwayat

### 5.4.11 Survei
- daftar survei
- detail
- rating
- masukan
- kirim
- status sudah diisi

### 5.4.12 Kritik dan saran
- daftar aduan
- buat aduan
- detail
- status
- balasan sekolah

---

## 5.5 Akun

- Profil wali
- Nama
- Email
- Nomor HP
- Hubungan dengan santri
- Data anak terhubung
- Sekolah aktif
- Ganti sekolah
- Sesi perangkat
- Logout
- Logout semua perangkat
- Pengaturan notifikasi
- Kebijakan privasi
- Tentang aplikasi
- Versi aplikasi
- Bantuan

### Penghubungan anak
- daftar anak
- tambah anak
- verifikasi identitas
- pengajuan menjadi wali
- status pending/aktif
- tolak/lepas hubungan bila diizinkan

---

# 6. Alur utama

## 6.1 Login pertama
```text
Splash
→ Login
→ Simpan token
→ GET /auth/me
→ Jika multi-sekolah: pilih sekolah
→ Pilih anak
→ Beranda
```

## 6.2 Multi-sekolah
```text
Login
→ /auth/me
→ schools > 1
→ requires_school_selection
→ tampilkan school picker
→ simpan school_id
→ kirim X-Active-School-Id
```

## 6.3 Melihat perkembangan anak
```text
Beranda
→ pilih anak
→ Anak
→ Pendidikan
→ Absensi/Nilai/Rapor/Tahfidz
```

## 6.4 Melihat kesehatan
```text
Anak
→ Kesehatan
→ Profil kesehatan
→ Riwayat pemeriksaan
→ Riwayat UKS
→ Riwayat izin sakit
```

## 6.5 Melihat perpustakaan
```text
Anak
→ Perpustakaan
→ Peminjaman aktif
→ Jatuh tempo
→ Denda
→ Katalog buku
```

## 6.6 Ibadah
```text
Tab Ibadah
→ Jadwal shalat
→ Al-Qur’an
→ Doa-doa
```

## 6.7 Pengajuan izin
```text
Beranda / Layanan
→ Ajukan izin
→ Pilih anak
→ Isi tanggal dan alasan
→ Kirim
→ Status pengajuan
```

---

# 7. Prinsip UX

- bahasa sederhana, bukan istilah teknis
- navigasi maksimal 2–3 level
- target sentuh minimal 44×44 px
- label jelas, jangan hanya ikon
- dukung mode gelap bila memungkinkan
- loading skeleton
- empty state yang mendidik
- error state dengan tindakan
- pull to refresh
- status data terakhir diperbarui
- jangan menampilkan error mentah dari server
- jangan menampilkan NIK/KK penuh di ringkasan
- data kesehatan tidak tampil di notifikasi
- aksesibilitas: kontras, font dinamis, screen reader
- orientasi portrait

---

# 8. Prinsip data dan API

- Backend ALIM adalah sumber kebenaran untuk data wali.
- Jangan membuat endpoint sendiri di mobile.
- Jangan memanggil BUMP/SIPLah langsung.
- Jangan menyimpan token BUMP di perangkat.
- Gunakan Bearer token Sanctum.
- Gunakan `X-Active-School-Id`.
- Hormati ability token.
- `401` → logout dan login ulang.
- `403 INSUFFICIENT_ABILITY` → re-login bila `reauth_required`.
- `404` data anak → tampilkan “data tidak ditemukan” tanpa membocorkan informasi.
- `422` validasi → tampilkan pesan per field.
- Gunakan idempotency untuk pengajuan.

---

# 9. API yang dipakai

## Auth
```text
POST /auth/login
POST /auth/register
POST /auth/logout
GET  /auth/me
PUT  /auth/me
```

## Anak
```text
GET /santri
GET /santri/{id}
GET /dashboard
```

## Pendidikan
```text
GET /santri/{id}/attendance
GET /santri/{id}/attendance/history
GET /santri/{id}/grades
GET /santri/{id}/classes
GET /santri/{id}/class-history
GET /santri/{id}/exam-schedules
GET /raport/status
GET /raport/{student_id}
```

## Tahfidz dan prestasi
```text
GET /santri/{id}/tahfidz
GET /santri/{id}/achievements
GET /santri/{id}/competitions
GET /santri/{id}/extracurricular
```

## Kesehatan
```text
GET /santri/{id}/health
GET /santri/{id}/health/history
```

## Perpustakaan
```text
GET /santri/{id}/library/loans
GET /santri/{id}/library/visits
GET /santri/{id}/library/member-card
GET /library/books
GET /library/programs
```

## Asrama
```text
GET /santri/{id}/dormitory
GET /santri/{id}/dormitory-attendance
GET /santri/{id}/dormitory/history
GET /santri/{id}/dormitory/rewards
GET /dormitory-permits
GET /dormitory/visits
GET /dormitory/returns
```

## Sekolah dan informasi
```text
GET /schools/{id}/profile
GET /santri/{id}/teachers
GET /{userId}/wali/announcements
GET /{userId}/wali/reports
GET /agendas
GET /regulations
GET /prayer-times
```

## Pengajuan
```text
POST /permit/request
GET  /permit
POST /dormitory/permit
POST /dormitory/visit
POST /dormitory/return
```

## Notifikasi
```text
GET /notifications
PUT /notifications/{id}/read
PATCH /notifications/mark-all-read
POST /devices
DELETE /devices/{device}
```

---

# 10. API eksternal untuk fitur ibadah

Rancang adapter terpisah agar sumber dapat diganti tanpa mengubah UI.

## Jadwal shalat
Kebutuhan data:
- kota
- tanggal
- subuh
- dzuhur
- ashar
- maghrib
- isya
- waktu berikutnya
- hijriah
- sumber

Perilaku:
- cache harian
- fallback ke jadwal terakhir
- pesan jika kota belum diatur
- tarik untuk memperbarui

## Al-Qur’an
Kebutuhan data:
- daftar surah
- nomor
- nama Arab
- nama Latin
- jumlah ayat
- teks Arab
- terjemahan
- audio
- bookmark
- terakhir dibaca

Perilaku:
- cache daftar surah
- cache audio bila memungkinkan
- loading/error/retry
- atribusi sumber

## Doa
Kebutuhan data:
- kategori
- daftar doa
- teks Arab
- latin
- terjemahan
- sumber
- favorit

Perilaku:
- cache
- pencarian
- offline untuk data yang sudah diunduh

---

# 11. Arsitektur aplikasi yang disarankan

```text
app/
├── (auth)/
├── (app)/
│   ├── beranda
│   ├── anak
│   ├── ibadah
│   ├── informasi
│   └── akun
src/
├── api/
├── stores/
├── components/
├── hooks/
├── types/
├── utils/
└── services/
    ├── prayer/
    ├── quran/
    └── doa/
```

Teknologi:
- Expo Router
- TypeScript
- Expo SecureStore
- TanStack Query
- Zustand atau Context
- Expo Notifications
- Zod untuk validasi
- date-fns locale id

---

# 12. State yang wajib ditangani

Setiap layar harus memiliki:

- loading
- success
- empty
- error
- offline
- unauthorized
- forbidden
- refreshing
- retry

Contoh:
- sekolah belum dipilih
- anak belum dipilih
- data kesehatan belum ada
- belum menjadi anggota perpustakaan
- belum ada prestasi
- belum ada agenda
- kota jadwal shalat belum diatur
- API eksternal timeout

---

# 13. Hasil yang diminta dari prompt ini

Hasilkan dokumen perancangan berisi:

1. Product vision
2. Persona wali
3. Information architecture
4. Sitemap
5. Navigation map
6. Screen inventory
7. User flows
8. Component inventory
9. API mapping per screen
10. State matrix
11. Security and privacy rules
12. Design direction
13. MVP scope
14. Phase 2 scope
15. Acceptance criteria
16. Backlog epics dan user stories

Gunakan format tabel untuk screen inventory dan API mapping.

---

# 14. Definition of Done perancangan

Perancangan dianggap selesai jika:

- semua menu memiliki tujuan yang jelas
- semua fitur memiliki screen dan flow
- semua data memiliki sumber API
- semua akses data anak dijelaskan
- multi-anak dan multi-sekolah dijelaskan
- state loading/error/empty tersedia
- fitur ibadah dipisahkan sebagai integrasi eksternal
- tidak ada fitur GTK/LJK/BUMP/SIPLah/hutang
- ada prioritas MVP dan fase berikutnya
- ada acceptance criteria yang dapat diuji

---

# 15. Gaya bahasa dan desain

Gunakan gaya:

- tenang
- bersih
- humanis
- Islami
- tidak ramai
- tidak menggunakan gradien berlebihan
- tidak menggunakan emoji sebagai label menu
- tipografi mudah dibaca
- warna utama biru/teal yang menenangkan
- aksen hangat untuk momen ibadah
- kartu ringkasan sederhana
- ikon konsisten
- hindari istilah teknis di UI

Contoh copywriting:

- “Selamat pagi, Bapak Ahmad”
- “Ringkasan anak hari ini”
- “Belum ada pengumuman”
- “Jadwal shalat belum tersedia untuk kota sekolah”
- “Coba lagi”
- “Data terakhir diperbarui 5 menit lalu”

---

# 16. Larangan

Jangan menambahkan:

```text
Login GTK
LJK scanner
BUMP ordering
SIPLah
Hutang lembaga
Pembayaran
Kasir
Manajemen stok
Keuangan internal
```

Aplikasi ini hanya untuk wali santri.
