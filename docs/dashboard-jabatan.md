# Analisis Kebutuhan Dashboard per Jabatan

Dokumen ini menjadi acuan desain ulang dashboard ALIM dengan pola
**overview → analysis → drill-down → action**.

Prinsip yang dipakai:

1. **Satu dashboard = satu jabatan.** Isi dashboard ditentukan oleh jabatan
   struktural dan tugas tambahan yang aktif, bukan oleh nama role yang terlalu
   lebar.
2. **Angka harus punya sumber dan definisi.** Setiap indikator memiliki
   `source`, `period`, dan `definition`. Tidak ada angka karangan.
3. **Data kosong tetap terlihat.** Nilai `null` ditampilkan sebagai `—` dengan
   keterangan "data belum tersedia", bukan `0`.
4. **Temuan berbasis aturan.** Temuan otomatis hanya memakai aturan terukur
   (ambang batas, perbandingan periode, kelengkapan data). Dashboard tidak
   menulis sebab-akibat yang tidak dapat dibuktikan data.
5. **Drill-down wajib.** Setiap kartu/grafik dapat diklik menuju daftar detail
   yang sudah terfilter, lalu ke profil/riwayat sumber data.
6. **Aksi sesuai hak akses.** Tombol tindakan hanya muncul bila pengguna
   memiliki izin; selain itu tampil sebagai tautan baca-saja.

---

## 1. Struktur Halaman Standar

| Lapisan | Isi | Catatan implementasi |
|---|---|---|
| 01. Header & konteks | Judul, deskripsi, tahun ajaran, semester, unit, jenjang, kelas, waktu data terakhir diperbarui, tombol ekspor PDF/Excel | Filter disimpan di query string agar bisa dibagikan |
| 02. Ringkasan indikator | 4–8 kartu KPI dengan nilai, pembanding periode, dan tautan drill-down | KPI tanpa data pembanding menampilkan `—`, bukan `0%` |
| 03. Visualisasi & tren | Tren bulanan/tahunan, perbandingan antarunit/jenjang/kelas, distribusi | Grafik interaktif, tooltip, filter periode |
| 04. Temuan & interpretasi | Daftar temuan dengan aturan yang jelas + tingkat keyakinan | Maksimal 5 temuan teratas, bukan narasi panjang |
| 05. Tabel detail | Pencarian, sort, pagination, filter lanjutan, klik baris | Ekspor mengikuti filter aktif |
| 06. Aksi | Tautan ke modul tindak lanjut sesuai izin | Ditampilkan sebagai daftar tugas, bukan tombol besar |

Tambahan lintas jabatan:

- **Carousel Info** dari menu Info/Pengumuman yang berstatus terbit.
- **Modal Broadcast** untuk broadcast aktif yang ditujukan kepada pengguna
  (dengan opsi "Tandai sudah dibaca" dan konfirmasi bila `ack_required`).

---

## 2. Analisis Kebutuhan per Jabatan

Kolom **Pertanyaan kunci** adalah pertanyaan yang harus dijawab dashboard.
Kolom **Indikator** hanya indikator yang benar-benar dapat dihitung dari data
yang tersedia di ALIM.

### 2.1 Kepala Satuan Pendidikan

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Bagaimana kondisi santri, GTK, dan pembelajaran di unit saya bulan ini? Di mana masalah paling mendesak? |
| Indikator | Santri aktif, santri baru, mutasi masuk/keluar, pertumbuhan bersih, kehadiran santri hari ini, kehadiran guru hari ini, rombel tanpa wali, nilai belum lengkap, izin aktif, surat keterlambatan |
| Visualisasi | Tren kehadiran santri 14 hari, distribusi santri per jenjang, komposisi GTK, top rombel terpadat |
| Temuan | Kehadiran di bawah ambang (mis. < 85%), rombel tanpa wali, nilai belum lengkap > 20%, mutasi keluar naik dibanding bulan lalu |
| Drill-down | Daftar santri dengan kehadiran rendah → profil santri; daftar rombel bermasalah → detail rombel |
| Aksi | Pindahkan santri, tetapkan wali kelas, kirim broadcast, buka laporan akademik |

### 2.2 Wakil Kepala / Wakasek Satuan Pendidikan

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Apakah KBM berjalan sesuai jadwal? Guru mana yang perlu dibina? |
| Indikator | Kehadiran guru, kelas kosong hari ini, jurnal belum diisi, perangkat/RPM belum lengkap, supervisi mendatang, keterlaksanaan pembelajaran |
| Visualisasi | Tren kehadiran guru, keterlaksanaan per rumpun mapel, distribusi status jurnal |
| Temuan | Kelas kosong berulang, guru dengan jurnal kosong ≥ 3 pertemuan, mapel tanpa perangkat |
| Drill-down | Klik guru → riwayat presensi & jurnal; klik kelas → jadwal kelas |
| Aksi | Jadwalkan supervisi, kirim pengingat, buka laporan keterlaksanaan |

### 2.3 Guru / Guru Kelas / Guru Mapel

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Kelas apa yang saya ajar hari ini? Apa yang belum saya kerjakan? |
| Indikator | Jumlah santri diajar, kelas hari ini, jurnal belum diisi, nilai belum lengkap, rata-rata nilai kelas, bank soal saya |
| Visualisasi | Distribusi nilai kelas, tren kehadiran kelas, progres keterisian nilai |
| Temuan | Nilai belum lengkap menjelang batas publish, jurnal tertinggal, kehadiran kelas turun |
| Drill-down | Klik kelas → daftar santri; klik santri → riwayat nilai/presensi |
| Aksi | Isi nilai, isi jurnal, unduh leger, minta izin |

### 2.4 Wali Kelas

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Bagaimana kondisi kelas binaan saya? Santri mana yang perlu perhatian? |
| Indikator | Santri kelas saya, kehadiran kelas, nilai belum lengkap, pelanggaran, izin aktif, prestasi |
| Visualisasi | Kehadiran per santri, distribusi nilai, tren pelanggaran |
| Temuan | Santri dengan kehadiran < 85%, nilai kosong, pelanggaran berulang |
| Drill-down | Klik santri → profil, riwayat kelas, riwayat nilai |
| Aksi | Catat pelanggaran, hubungi wali, cetak rapor |

### 2.5 Kepala Tata Usaha / Tata Usaha Sekolah

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Apakah administrasi santri dan kepegawaian lengkap dan tertib? |
| Indikator | Santri aktif, data santri belum lengkap, mutasi masuk/keluar, GTK belum verifikasi, arsip belum lengkap, surat masuk/keluar |
| Visualisasi | Kelengkapan data per jenjang, tren mutasi bulanan |
| Temuan | Santri tanpa NISN, tanpa dokumen, GTK belum verifikasi |
| Drill-down | Klik jenjang → daftar santri belum lengkap; klik GTK → berkas |
| Aksi | Lengkapi data, verifikasi GTK, cetak surat, ekspor laporan |

### 2.6 Bendahara / Kasir Sekolah

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Berapa tagihan, penerimaan, dan tunggakan bulan ini? |
| Indikator | Tagihan SPP bulan ini, terbayar, tunggakan, realisasi anggaran, pengajuan pengadaan |
| Visualisasi | Tren penerimaan bulanan, status invoice, distribusi tunggakan per jenjang |
| Temuan | Tunggakan menurun/naik dibanding bulan lalu, invoice jatuh tempo, realisasi rendah |
| Drill-down | Klik status → daftar invoice santri |
| Aksi | Catat pembayaran, kirim pengingat, ekspor rekap |

### 2.7 Kepala Asrama / Wakil Kepala Asrama

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Bagaimana hunian, kehadiran, dan ketertiban asrama? |
| Indikator | Santri asrama, keterisian kamar, santri di luar izin, keterlambatan kembali, pelanggaran, kebersihan kamar |
| Visualisasi | Keterisian per kamar, tren perizinan, distribusi pelanggaran |
| Temuan | Kamar over-capacity, santri terlambat kembali berulang, kamar dengan skor kebersihan rendah |
| Drill-down | Klik kamar → daftar santri; klik santri → timeline asrama |
| Aksi | Catat izin, catat pelanggaran, jadwalkan inspeksi |

### 2.8 Musrif / Musrifah / Wali Kamar

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Siapa yang tidak hadir/tidak kembali hari ini? Apa yang harus saya tindak lanjuti? |
| Indikator | Santri kamar saya, absensi malam, izin aktif, keterlambatan, catatan pelanggaran, setoran hafalan |
| Visualisasi | Kehadiran kamar 7 hari, distribusi status perizinan |
| Temuan | Santri alpa berulang, izin kedaluwarsa, belum kembali |
| Drill-down | Klik santri → profil & timeline |
| Aksi | Catat kehadiran, catat pelanggaran, hubungi wali |

### 2.9 Kepala Departemen Tahfidz / Musyrif Tahfidz

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Bagaimana capaian setoran dan kehadiran halaqah? |
| Indikator | Jumlah halaqah, santri aktif, setoran hari ini, kehadiran halaqah, ujian tasmi'/UTHQ, syahadah tertunda |
| Visualisasi | Tren setoran per pekan, distribusi capaian juz, kehadiran halaqah |
| Temuan | Santri tanpa setoran > 7 hari, halaqah tanpa musyrif, ujian tertunda |
| Drill-down | Klik halaqah → daftar santri & setoran; klik santri → riwayat hafalan |
| Aksi | Input setoran, jadwalkan tasmi', cetak syahadah |

### 2.10 Kepala UKS / Staf UKS / Dokter Jaga

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Beban layanan kesehatan hari ini dan tren penyakit apa yang perlu diwaspadai? |
| Indikator | Kunjungan hari ini, santri di UKS, rujukan, stok obat kritis, izin sakit, inspeksi sanitasi |
| Visualisasi | Tren kunjungan 14 hari, distribusi keluhan, status stok obat |
| Temuan | Lonjakan kasus serupa, obat di bawah stok minimum, rujukan tanpa tindak lanjut |
| Drill-down | Klik keluhan → daftar santri; klik obat → riwayat pemakaian |
| Aksi | Catat kunjungan, buat rujukan, ajukan pengadaan obat |

### 2.11 Kepala Humas & Personalia

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Bagaimana kondisi SDM dan komunikasi publik? |
| Indikator | GTK aktif, GTK baru, presensi GTK, cuti aktif, kontrak habis, rekrutmen berjalan, aduan masuk, pengumuman terbit |
| Visualisasi | Komposisi GTK, tren presensi, tren aduan |
| Temuan | Kontrak habis < 60 hari, GTK belum verifikasi, aduan melewati SLA |
| Drill-down | Klik GTK → profil SDM; klik aduan → detail penanganan |
| Aksi | Verifikasi GTK, proses cuti, kirim broadcast, terbitkan pengumuman |

### 2.12 Kepala Unit Rumah Tangga / Sarpras

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Aset mana yang rusak, tiket mana yang terlambat, dan pengadaan apa yang tertunda? |
| Indikator | Tiket terbuka, tiket lewat SLA, aset rusak, armada tidak tersedia, stok gudang minimum, pekerjaan kebersihan |
| Visualisasi | Tiket per kategori, tren penyelesaian, status armada |
| Temuan | Tiket > 7 hari, aset rusak berat, stok di bawah minimum |
| Drill-down | Klik kategori → daftar tiket; klik aset → riwayat perbaikan |
| Aksi | Buat work order, ajukan pengadaan, jadwalkan perawatan |

### 2.13 Pimpinan / Pengasuh Pesantren

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Bagaimana kesehatan organisasi secara menyeluruh dan apa yang butuh keputusan saya? |
| Indikator | Santri aktif total, pertumbuhan, kehadiran, GTK, keuangan ringkas, aduan, persetujuan tertunda |
| Visualisasi | Tren lintas unit, perbandingan antarunit, distribusi persetujuan |
| Temuan | Unit dengan penurunan, persetujuan menumpuk, isu lintas unit |
| Drill-down | Klik unit → dashboard unit terkait (read-only) |
| Aksi | Setujui/tolak pengajuan, arahkan tindak lanjut |

### 2.14 Super Admin / System

| Aspek | Kebutuhan |
|---|---|
| Pertanyaan kunci | Apakah sistem sehat dan aman? |
| Indikator | Pengguna aktif, pengguna tanpa role, failed job, antrean job, error rate, aktivitas hari ini |
| Visualisasi | Aktivitas 7 hari, distribusi role, tren error |
| Temuan | Failed job, akun tanpa role, login gagal berulang |
| Drill-down | Klik role → daftar pengguna; klik error → log |
| Aksi | Reset akses, bersihkan queue, tinjau log |

---

## 3. Komponen Lintas Jabatan

### 3.1 Carousel Info

- Sumber: `humas_announcements` dengan `status = published` dan memiliki
  gambar (`humas_announcement_images` atau `banner_path`).
- Cakupan: global (`school_id` null) + sekolah pengguna.
- Urutan: `is_pinned` lalu `published_at` terbaru.
- Batas: maksimal 5 slide; otomatis berhenti saat kursor di atas carousel.
- Klik slide membuka detail pengumuman.

### 3.2 Modal Broadcast

- Sumber: `humas_announcements` dengan `category = broadcast`, `status =
  published`, belum kedaluwarsa, dan target cocok dengan pengguna.
- Modal tampil maksimal sekali per broadcast per pengguna (disimpan di
  `localStorage`, bukan cookie server).
- Bila `ack_required`, tombol tutup diganti "Saya sudah membaca" yang juga
  mencatat konfirmasi.
- Tidak menampilkan broadcast yang dibuat oleh pengguna sendiri.

---

## 4. Aturan Temuan (Contoh Terukur)

| Temuan | Aturan |
|---|---|
| Kehadiran rendah | Rasio hadir < 85% pada periode terpilih dan jumlah sampel ≥ 10 |
| Nilai belum lengkap | Persentase nilai kosong > 20% per kelas |
| Rombel tanpa wali | `study_groups.homeroom_teacher_id` null atau wali tidak aktif |
| Kontrak habis | `end_date` ≤ 60 hari dari hari ini |
| Tiket lewat SLA | `created_at` > 7 hari dan status belum selesai |
| Stok minimum | `stok <= stok_minimum` |
| Mutasi naik | Mutasi keluar bulan ini > bulan lalu ≥ 20% |

Setiap temuan wajib menyertakan: judul, angka pendukung, periode, dan tautan
drill-down. Bila data tidak cukup, temuan tidak ditampilkan.

---

## 5. Implementasi

### 5.1 Komponen bersama

| Komponen | File |
|---|---|
| Resolusi filter & data info/broadcast | `app/Services/Dashboard/DashboardContextService.php` |
| Agregasi angka (santri, mutasi, kehadiran, GTK, rombel, nilai, keuangan, asrama, tahfidz, UKS, SDM, sarpras, sistem) | `app/Services/Dashboard/DashboardAnalyticsService.php` |
| Penyusun KPI, grafik, dan temuan per jabatan | `app/Services/Dashboard/DashboardPresenter.php` |
| Aksi ekspor & broadcast | `app/Http/Controllers/Dashboard/DashboardActionController.php` |

Semua dashboard jabatan memakai `DashboardController` yang sama, sehingga
lapisan ini otomatis aktif untuk: satuan pendidikan, guru, wali kelas, kepala
TU/TU, asrama (kepala, wakil, musrif, wali kamar), tahfidz, keuangan,
personalia, UKS, unit rumah tangga, pimpinan, dan super admin.

### 5.2 Urutan tampilan

1. Header + filter + ekspor (`partials/header.blade.php`)
2. Carousel info (`partials/info-carousel.blade.php`) — hanya jika ada slide
3. Modal broadcast (`partials/broadcast-modal.blade.php`) — hanya jika ada broadcast
4. KPI jabatan (`partials/kpi-cards.blade.php`)
5. Grafik tren (`partials/analytics-charts.blade.php`)
6. Distribusi jenjang & keterisian rombel (`partials/distribution.blade.php`)
7. Temuan berbasis aturan (`partials/findings.blade.php`)
8. Tindak lanjut (`partials/actions.blade.php`)
9. Widget jabatan yang sudah ada (dipertahankan)

### 5.3 Aturan teknis penting

- Tahun ajaran yang tersimpan per semester digabung berdasarkan `name`.
- Unit pengguna dari konteks sekolah selalu mengunci filter; parameter unit lain
  diabaikan agar tidak terjadi kebocoran data.
- KPI tanpa sumber data bernilai `null` dan ditampilkan `—` dengan keterangan
  "data belum tersedia", bukan `0`.
- Temuan hanya muncul bila melewati ambang aturan; setiap temuan menampilkan
  aturan yang dipakai.
- Modal broadcast memakai `localStorage` untuk mencegah tampil berulang, dan
  mencatat `announcement_acknowledgements` bila `ack_required`.
- Carousel dan modal memakai data global + unit pengguna, bukan lintas unit.

### 5.4 Endpoint

| Method | Route | Kegunaan |
|---|---|---|
| GET | `/{userId}/dashboard/export/{role}` | Excel ringkasan KPI |
| GET | `/{userId}/dashboard/export/{role}/pdf` | PDF ringkasan KPI |
| POST | `/{userId}/dashboard/broadcasts/{announcementId}/acknowledge` | Konfirmasi baca broadcast |
| GET | `/{userId}/dashboard/broadcasts/active` | Daftar broadcast aktif |

### 5.5 Batas data yang perlu diketahui

- `gtk_employments` pada skema saat ini belum memiliki kolom status verifikasi
  dan tanggal akhir kontrak, sehingga KPI "belum verifikasi" dan "kontrak
  berakhir" tampil sebagai belum tersedia.
- Widget jabatan lama tetap dipertahankan; bila sumber datanya kosong, widget
  menampilkan keadaan kosong tanpa menghentikan dashboard.

---

## 6. Revisi tampilan dashboard

### 6.1 Salam Islami
Banner dashboard memakai salam Islami
**"Assalamu'alaikum warahmatullahi wabarakatuh"** diikuti nama pengguna.
Sapaan "Selamat pagi/siang/sore/malam" tidak lagi menjadi judul banner.

### 6.2 Kaldik bulan berjalan
Kartu **Kalender Pendidikan** hanya menampilkan kegiatan yang beririsan dengan
bulan berjalan, diambil dari tabel `kaldik` (filter tahun ajaran aktif, unit,
dan `is_active`). Kegiatan bulan lalu/bulan depan tidak ikut ditampilkan.
Setiap baris menampilkan tanggal, jenis, serta penanda Berlangsung / Selesai /
"n hari lagi" (≤ 7 hari).

### 6.3 Jadwal shalat
Kartu **Jadwal Shalat** menampilkan Subuh, Dzuhur, Ashar, Maghrib, dan Isya
untuk kota unit pengguna, dengan penanda shalat berikutnya.

- Kota diambil dari alamat/nama unit; bila tidak terdeteksi, dipakai
  `system_settings.dashboard_prayer_city` yang diisi administrator.
- Sumber data: Aladhan API, di-cache 12 jam per kota per tanggal.
- Bila API gagal, kartu menampilkan pesan "belum tersedia" — bukan angka
  perkiraan.
- Lokasi rerata: beberapa unit dalam satu yayasan dapat memakai kota default.

### 6.4 Grafik gender dihapus
Grafik "Sebaran Jenis Kelamin" dihapus dari dashboard satuan pendidikan dan
pimpinan, karena satu unit sekolah hanya menampung satu jenis kelamin sehingga
grafik tersebut tidak informatif.

### 6.5 Penghapusan data ganda
- Statistik inti (santri aktif, total GTK, rombel aktif) hanya tampil sekali
  pada kartu KPI; widget `stat-total-*` tidak dirender ulang.
- Grafik dengan deret data identik otomatis dideduplikasi sebelum dirender.
- Banner sapaan dipindahkan ke atas agar konteks halaman terbaca lebih dulu.

### 6.6 Animasi
- Kartu masuk dengan fade-up bertahap (stagger).
- Kartu KPI memiliki efek kilau saat hover.
- Grafik ApexCharts beranimasi saat dimuat.
- Kartu shalat berikutnya memiliki denyut halus (pulse ring).
- Semua animasi dinonaktifkan otomatis saat pengguna mengaktifkan
  `prefers-reduced-motion`.

### 6.7 Penyederhanaan header dan penghapusan grafik berulang

- Tombol dropdown **"Dashboard Lain"** dihapus dari header dashboard. Navigasi
  antarjabatan tetap tersedia melalui sidebar; header hanya berisi konteks
  laporan dan aksi ekspor.
- Teks pengantar generik *"Ringkasan operasional, tren, dan tindak lanjut
  sesuai jabatan Anda."* dihapus karena tidak menambah informasi; judul
  jabatan dan badge periode/unit sudah cukup menjadi konteks.
- Widget lama yang menampilkan data yang sama dengan KPI/grafik baru tidak
  dirender lagi, yaitu:
  `stat-total-santri`, `stat-total-gtk`, `stat-total-rombel`,
  `stat-santri-baru`, `stat-santri-masuk`, `stat-santri-keluar`,
  `stat-absensi-santri-hari-ini`, `chart-tren-kehadiran-santri` (versi 7 hari),
  `chart-top-rombel`, `chart-santri-per-tingkat`,
  `chart-statistik-santri-per-rombel`, `chart-gender-santri`, dan
  `table-rekap-siswa-tingkat`.
- Dengan begitu satu indikator hanya muncul sekali: KPI untuk ringkasan,
  grafik analitik untuk tren, dan tabel distribusi untuk rincian.
