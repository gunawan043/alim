# ALIM — Rangkuman Sistem (Materi Presentasi)

**ALIM — Academic Learning & Information Management**
Sistem informasi manajemen pendidikan terpadu untuk pondok pesantren / satuan pendidikan berbasis boarding.

---

## 1. Gambaran Umum

- Satu aplikasi web terpusat yang menyatukan **akademik (satuan pendidikan)** dan **pengasuhan (asrama)** dalam satu basis data santri.
- Data santri menjadi **single source of truth**: dipakai bersama oleh sekolah, asrama, UKS, perizinan, wali, dan aplikasi mobile.
- Multi-satuan pendidikan (multi-school) dengan pemisahan data per sekolah/unit (school context + global scope).
- Akses berbasis peran (RBAC) + izin granular, dengan jejak audit dan mekanisme "view as" untuk pengawasan.

**Skala sistem saat ini**

| Komponen | Jumlah |
|---|---|
| Model data (Eloquent) | ±486 |
| Migrasi database | ±681 |
| Controller | ±441 |
| Halaman (Blade view) | ±1.459 |
| Endpoint route | ±2.300 |
| Peran/role dengan menu tersendiri | ±20 |

**Teknologi:** Laravel 12 · PHP 8.3+ · MySQL 8 · Blade + Bootstrap 5 (RTL-ready) · Vite 5 · Sanctum (API mobile) · Pusher/Echo (realtime) · Elasticsearch (pencarian) · UUID untuk seluruh primary key.

---

## 2. Arsitektur & Karakter Sistem

- **Monolit modular** — satu basis kode, dipisah per domain (Akademik, Asrama, GTK, Sarpras, UKS, Keuangan, Humas, dll.).
- **Event-driven** — perubahan status santri (naik kelas, mutasi, lulus, keluar) otomatis memicu pembaruan data akademik & asrama.
- **Context-aware** — setiap query dibatasi otomatis ke sekolah/unit pengguna.
- **Otorisasi berlapis** — peran + permission snapshot + scope organisasi; gagal-tertutup (fail closed) bila konteks tidak jelas.
- **Integrasi kanal** — notifikasi in-app, WhatsApp, push notification (FCM/APNs), portal wali, dan API mobile.
- **Ramah audit** — approval center, audit log, dan pencatatan aksi pada modul sensitif (nilai, izin, mutasi kamar).

---

## 3. Sistem Pendidikan (Satuan Pendidikan)

### 3.1 Siklus Santri (ujung ke ujung)
Penerimaan & pendataan → penempatan rombel → penugasan guru/wali kelas → kalender & jadwal → KBM → penilaian → rapor → kenaikan kelas → kelulusan/alumni.

### 3.2 Modul Kesiswaan
- Data santri, data mahrom (keluarga), dan **import data santri**.
- **Pengaturan rombel** & penempatan santri per rombel.
- **Mutasi santri** (masuk, keluar, pindah unit, berhenti, lulus).
- **Leger & cetak rapor** per kelas.
- **Kedisiplinan**: dashboard, poin pelanggaran, rekap pelanggaran, konseling santri.
- Data alumni dan prestasi santri.

### 3.3 Modul Akademik
- Tahun ajaran, mata pelajaran, data kelas & mapel.
- **Plotting guru mengajar** (guru × mapel × rombel × jam pelajaran), termasuk input massal.
- **SK pembagian tugas** (surat keputusan) dan master rumpun mapel.
- **Jadwal pelajaran** (generate jadwal sekolah per hari × jam), jam pelajaran, pekan efektif.
- **Monitoring**: kehadiran guru per jam dan rekap pergantian jam.

### 3.4 Kurikulum
- Profil & info kurikulum, peta kurikulum, master rumpun.
- **Capaian Pembelajaran (CP) → Alur Tujuan Pembelajaran (ATP) → Tujuan Pembelajaran (TP)**.
- **Prota & Prosem**, perangkat/bahan ajar, dan persetujuan (KSP) perangkat.
- **Projek P5 / P5RA** (projek penguatan profil pelajar).
- Realisasi pembelajaran & jurnal mengajar.

### 3.5 Asesmen & Penilaian
- **Kisi-kisi soal → Bank soal → Paket soal** (alur review & approval).
- **LJK Smart Scanner**: cetak lembar jawaban, pindai dengan kamera HP, koreksi otomatis, nilai langsung masuk ke nilai resmi (ber-audit).
- **Buku nilai guru**: formatif, sumatif harian, STS/SAS, **nilai murni STS/SAS**, KKTP, dan penguncian nilai.
- **Rapor**: leger, catatan wali/kurikulum, tata letak & media rapor, finalisasi, publikasi, cetak single/massal.

### 3.6 Kekhasan Pesantren
- **Tahfidz**: setoran, verifikasi, mutaba'ah, target hafalan, bobot nilai rapor (setoran/STS/SAS), monitoring santri tertinggal.
- **Departemen Bahasa**: mufradat, percakapan harian, pelanggaran bahasa, monitoring jasus.
- **OSIPP**: kepengurusan organisasi santri, program kerja, kegiatan, administrasi, dan evaluasi.
- Ekstrakurikuler, perpustakaan, UKS/klinik, dan unit layanan gizi.

### 3.7 Laporan Pendidikan
- **Analisis akademik**: nilai murni STS/SAS, ketuntasan (KKTP), kehadiran, keterlaksanaan pembelajaran, kelengkapan administrasi.
- **Mutasi & populasi**: pertumbuhan populasi, profil & sebaran santri, keberlanjutan pendidikan/retensi.
- Laporan presensi, laporan santri, laporan GTK — semua dengan export Excel & PDF.

### 3.8 Pendukung
- **Portal wali** (web + mobile): lihat rapor/nilai, jadwal, tahfidz, izin.
- **API mobile (Sanctum)** untuk aplikasi wali & guru.
- **GTK**: profil, penempatan, beban kerja, kompetensi, cuti, kinerja, penggajian.

---

## 4. Sistem Asrama

### 4.1 Prinsip
Asrama bukan sekadar "data kamar", tetapi **sistem pengasuhan 24 jam**: hunian, kehadiran, kegiatan, perizinan, keamanan, pembinaan, dan komunikasi wali — terhubung ke data santri yang sama dengan sekolah.

### 4.2 Master & Hunian
- Master **asrama → gedung/wing → kamar** (kapasitas & nomor bed).
- **Penempatan kamar**, tarik santri yang belum berkamar, penempatan massal.
- **Mutasi/pindah kamar** dengan alur persetujuan (cek kapasitas & bed).
- **Wali kamar** (room supervisor) dan penugasan staf asrama.
- Import gedung/kamar via template XLSX.

### 4.3 Penghuni
- Check-in / check-out penghuni, status **berhenti mondok** (tetap bersekolah).
- Riwayat kamar dan status keberadaan santri (di asrama / izin / sakit / kegiatan).
- Data consent wali (persetujuan pemrosesan data pribadi).

### 4.4 Kehadiran
- **Presensi harian/malam per sesi** (oleh musyrif/wali kamar), dengan verifikasi.
- **Rekap presensi bulanan** otomatis per santri dan per kamar.
- Monitoring sesi yang belum diisi.

### 4.5 Kegiatan & Pembinaan
- **Jadwal kegiatan rutin** (harian atau malam tertentu), target peserta (semua/kelas tertentu).
- Sesi presensi dibentuk otomatis (14/30 hari ke depan + scheduler harian).
- **Presensi kegiatan** + rekap hadir/izin/sakit/alpa + notifikasi wali untuk alpa.
- **Waraqah Mutaba'ah** (jurnal ibadah/amalan harian santri).
- **Pelanggaran & poin** (katalog poin, aturan poin, ledger poin) dan **penghargaan** dengan persetujuan.
- Template & log kegiatan, inventaris kamar (baik / rusak ringan / rusak berat / hilang).

### 4.6 Perizinan (Pulang/Keluar)
- Pengajuan izin, kuota & kebijakan per asrama, persetujuan berjenjang.
- **Kartu izin ber-QR**: token bertanda tangan, masa berlaku, dapat dicabut/di-regenerate.
- Scan keluar & scan kembali, deteksi **overdue** (belum kembali), statistik kepulangan.
- **Safeguarding penjemput**: penjemput dicocokkan dengan daftar mahrom/orang tua; di luar daftar → wajib persetujuan Kepala/Wakil Asrama.
- Auto-approve untuk pengajuan oleh staf berwenang (dengan tetap tercatat).

### 4.7 Kunjungan & Keamanan
- Jadwal & log kunjungan, scan QR kunjungan, kalender kunjungan.
- **Blacklist tamu** (nama/NIK/no. HP) dan pembatasan pengunjung.
- **QR gatepass** keluar/masuk asrama + kartu QR penghuni + riwayat & statistik harian.
- PWA shell + halaman offline (persiapan pencatatan saat jaringan bermasalah).

### 4.8 Komunikasi & Pengawasan
- Broadcast/informasi asrama ke wali penghuni terkait (in-app + opsi WhatsApp).
- **Dashboard asrama**: hunian & okupansi, kamar tersedia, kehadiran malam, izin pending/overdue, kegiatan hari ini, pelanggaran & penghargaan, inventaris rusak.
- **Laporan**: kunjungan, mutasi kamar, checkout, kegiatan, sanitasi/kebersihan, audit log asrama.
- **Perlindungan data (PDP)**: masking NIK/no. HP tamu, retensi (kunjungan 24 bulan, izin 36 bulan, log scan QR 365 hari), backup harian terverifikasi.

---

## 5. Integrasi Sekolah ↔ Asrama

| Momen | Efek otomatis |
|---|---|
| Santri diterima / mutasi masuk | Siap ditempatkan di rombel **dan** kamar |
| Naik kelas | Rombel baru, buku nilai lama diarsipkan; hunian asrama tetap terlacak |
| Mutasi keluar / lulus / berhenti | Menutup status penghuni asrama & akses terkait |
| Izin/sakit | Memengaruhi kehadiran sekolah **dan** status keberadaan asrama |
| Pelanggaran / poin | Terhubung ke pembinaan dan kedisiplinan santri |
| Rapor dipublikasikan | Notifikasi ke wali (web + mobile) |

---

## 6. Peran Pengguna (ringkas)

Kepala/Wakil Satuan Pendidikan · Kepala TU & Tata Usaha · Guru (umum, agama, hadits, bahasa Arab, tahfidz, kelas) · Wali Kelas · Koordinator (kurikulum, kesiswaan, mapel, ekskul, lab, OSIPP) · Kepala/Wakil Asrama · Musyrif/Musyrifah · Wali Kamar · Staf Perizinan · UKS · Perpustakaan · Sarpras · Keuangan · Humas & Personalia · Pimpinan · Super Admin.

Setiap peran mendapat **dashboard dan menu yang berbeda**, disusun dari kombinasi jabatan struktural + tugas tambahan.

---

## 7. Nilai Jual untuk Presentasi

1. **Satu data, dua dunia** — sekolah dan asrama tidak lagi punya data terpisah.
2. **Otomatisasi lintas modul** — naik kelas, mutasi, dan kelulusan langsung berdampak ke asrama.
3. **Pengasuhan terukur** — kehadiran malam, kegiatan, poin, dan mutaba'ah terdokumentasi.
4. **Keamanan santri** — QR, verifikasi penjemput, blacklist tamu, dan audit log.
5. **Transparansi ke wali** — portal wali, notifikasi, dan rapor digital.
6. **Siap operasional** — laporan Excel/PDF, backup, retensi data, dan mode offline.

---

## 8. Saran Alur Slide (10–12 slide)

1. Judul & latar belakang pesantren
2. Masalah: data sekolah vs asrama terpisah
3. Solusi: ALIM sebagai satu platform
4. Arsitektur & teknologi (diagram sederhana)
5. Peran & hak akses
6. Sistem pendidikan — siklus santri
7. Sistem pendidikan — akademik, kurikulum, asesmen
8. Sistem pendidikan — rapor & laporan
9. Sistem asrama — hunian & kehadiran
10. Sistem asrama — izin, keamanan, pembinaan
11. Integrasi sekolah ↔ asrama (tabel momen)
12. Dampak, roadmap, dan penutup
