# Modul Laporan Analisis Akademik

Route utama: `GET /{userId}/laporan/akademik`

Tab laporan:

1. Analisis Nilai Murni STS/SAS
2. Nilai & Ketuntasan Akademik
3. Kehadiran Santri
4. Keterlaksanaan Pembelajaran
5. Kelengkapan Administrasi Akademik

## 1. Analisis Nilai Murni STS/SAS

Halaman: `/{userId}/laporan/akademik`

- Membaca `admin_nilai_sumatif.sts_murni` dan `sas_murni`.
- Filter tahun ajaran, semester, jenjang, kelas, mapel, dan pencarian santri.
- Rata-rata, median, nilai tertinggi/terendah, distribusi.
- Selisih SAS–STS dan status meningkat/menurun/tetap.
- Perbandingan semester sebelumnya atau tahun ajaran sebelumnya.
- Agregasi per kelas/mapel.
- Temuan otomatis: penurunan ≥ 5 poin, nilai kosong, SAS di bawah KKTP.
- Grafik per kelas; klik batang untuk memfilter kelas.
- Tombol riwayat pada detail membuka histori nilai santri untuk mapel tersebut
  lintas semester dan tahun ajaran.
- Export Excel dan PDF.

## 2. Nilai & Ketuntasan Akademik

Halaman: `/{userId}/laporan/akademik/ketuntasan`

- Kelengkapan nilai (`complete_rate`) dan ketuntasan (`mastery_rate`).
- Ketuntasan hanya dihitung jika KKTP tersedia.
- Rekap per kelas dan per mapel.
- Perbandingan kelengkapan/ketuntasan dengan periode pembanding.
- Detail status: tuntas, di bawah KKTP, belum lengkap, atau KKTP belum ada.
- Export Excel dan PDF.

## 3. Kehadiran Santri

Halaman: `/{userId}/laporan/akademik/kehadiran`

- Sumber: presensi harian atau presensi mapel (dipilih eksplisit).
- Filter periode tanggal, kelas, mapel, dan pencarian santri.
- Rekap hadir, terlambat, izin, sakit, alpa, serta persentase kehadiran.
- Tren bulanan dan rekap per kelas.
- Santri dengan kehadiran di bawah 85%.
- Perbandingan rata-rata nilai kelompok kehadiran rendah vs cukup.
  Ini bersifat observasi, bukan kesimpulan sebab-akibat.
- Export Excel dan PDF.

## 4. Keterlaksanaan Pembelajaran

Halaman: `/{userId}/laporan/akademik/keterlaksanaan`

- Per buku: perangkat/RPM, jurnal, presensi/realisasi, dan slot jadwal mingguan.
- Status: terlaksana, sebagian, atau belum terlaksana.
- Rekap per guru, mapel, kelas, dan semester.
- Temuan buku yang belum memiliki jurnal/presensi/perangkat.
- Export Excel dan PDF.

Catatan: jumlah pertemuan dan slot jadwal mingguan ditampilkan terpisah;
keduanya tidak dibagi menjadi persentase karena satuannya berbeda.

## 5. Kelengkapan Administrasi Akademik

Halaman: `/{userId}/laporan/akademik/administrasi`

Checklist per buku:

- perangkat/RPM,
- jurnal pembelajaran,
- presensi/realisasi,
- input nilai lengkap.

Menampilkan persentase per guru dan per buku, daftar komponen yang belum ada,
serta tindak lanjut. Persentase mengukur keberadaan komponen, bukan kualitas
dokumen.

## Sumber data

- `teacher_admin_books`
- `admin_nilai_sumatif`
- `student_class_histories`
- `subject_kktp`
- `admin_presensi_harian`
- `admin_presensi_mapel`
- `admin_presensi_siswa`
- `admin_jurnal_pembelajaran`
- `perangkat_pembelajaran`
- `jadwal_kbms`

## Prinsip

- Data kosong tetap ditampilkan sebagai belum lengkap.
- Indikator tanpa sumber data tidak diisi dengan asumsi.
- Temuan otomatis adalah penanda untuk ditinjau, bukan kesimpulan final.

---

# Laporan Mutasi & Populasi Santri

Lima laporan populasi memakai filter tahun ajaran, unit/satuan pendidikan,
jenjang, kelas/rombel, pencarian santri, serta filter tanggal dan jenis kelamin
pada halaman yang membutuhkannya. Tahun ajaran digabung berdasarkan nama
(`2026/2027`) agar dua baris semester tidak dihitung sebagai dua tahun.

## 6. Mutasi Santri

Halaman: `/{userId}/laporan/populasi/mutasi`

- Mutasi masuk dan pindahan masuk.
- Mutasi keluar, berhenti/drop out, dan lulus.
- Perpindahan internal unit, jenjang, dan rombel dari audit siklus santri.
- Alasan berhenti, sekolah asal/tujuan, serta temuan data tujuan yang kosong.
- Grafik bulanan dan tahunan; rekap per unit, jenjang, dan kelas.
- Hanya mutasi resmi berstatus `approved` yang dihitung.
- Data `student_transfers` yang beririsan dengan mutasi masuk/keluar pada
  santri dan tanggal yang sama tidak dihitung dua kali.
- Export Excel dan PDF.

## 7. Pertumbuhan Populasi

Halaman: `/{userId}/laporan/populasi/pertumbuhan`

- Populasi awal, santri baru, mutasi masuk, mutasi keluar, berhenti, lulusan,
  populasi akhir, pertumbuhan bersih, dan persentase pertumbuhan.
- Rekonsiliasi memakai populasi akhir tahun sebelumnya sebagai awal tahun
  berikutnya, bukan populasi mentah yang masih memuat lulusan/berhenti.
- Santri dihitung satu kali per tahun ajaran berdasarkan `student_id` unik.
- Grafik per tahun ajaran dan rekap per unit, jenjang, serta kelompok pendidikan.
- Selisih rekonsiliasi ditampilkan terbuka sebagai penanda data, bukan
  otomatis dianggap kesalahan.

## 8. Profil dan Sebaran Santri

Halaman: `/{userId}/laporan/populasi/profil`

- Jumlah santri aktif per unit, jenjang, kelas, dan rombel.
- Sebaran jenis kelamin dan kelompok usia bila tanggal lahir tersedia.
- Rata-rata santri per rombel, kapasitas, dan keterisian rombel.
- Perubahan komposisi populasi per unit dibandingkan tahun sebelumnya.
- Data jenis kelamin/usia yang tidak tersedia tetap ditampilkan sebagai
  kelompok tidak diketahui atau belum tersedia.

## 9. Keberlanjutan Pendidikan dan Retensi

Halaman: `/{userId}/laporan/populasi/keberlanjutan`

- Santri pada tahun acuan yang memiliki riwayat kelas setelahnya dianggap
  melanjutkan pendidikan di unit internal.
- Santri tanpa riwayat lanjutan ditampilkan sebagai **tidak tercatat**.
- Status lulus dan keluar tercatat ditampilkan terpisah.
- Persentase keberlanjutan per tahun acuan hanya dihitung bila tahun berikutnya
  sudah berjalan dan memiliki data.
- Perjalanan individual menampilkan tahun masuk, kelas awal, tahun/kelas
  terakhir, status, dan tahun kelulusan.

Catatan penting: tidak melanjutkan ke unit internal **tidak otomatis berarti
putus sekolah**. Santri dapat melanjutkan ke lembaga lain, pindah domisili, atau
belum tercatat karena data belum lengkap.

## 10. Kenaikan Kelas dan Kelulusan

Halaman: `/{userId}/laporan/populasi/kenaikan-kelulusan`

- Tindakan: naik kelas, tinggal kelas, pindah unit, pindah ke jenjang lebih
  rendah, lulus, atau belum tercatat.
- Perbandingan memakai riwayat kelas tahun ajaran berikutnya bila tersedia.
- Rekap tindakan per unit dan jenjang; daftar detail per santri.
- Rekap kelulusan per tahun ajaran, unit, jenjang, angkatan masuk, dan rata-rata
  masa studi.
- Angka kelulusan didefinisikan sebagai **lulusan tahun ajaran tersebut dibagi
  santri kelas akhir pada tahun ajaran yang sama**. Definisi ini ditampilkan di
  halaman agar pembaca tidak membandingkan angka dengan basis berbeda.
- Santri tanpa riwayat tahun berikutnya ditampilkan sebagai belum tercatat,
  bukan otomatis tinggal kelas.
- Export Excel dan PDF.

## Sumber data populasi

- `student_mutations_in`
- `student_mutations_out`
- `student_transfers`
- `student_lifecycle_audits`
- `student_class_histories`
- `student_grade_promotions`
- `alumni`
- `students`
- `schools`
- `grade_levels`
- `study_groups`
- `academic_years`

## Prinsip laporan populasi

- Data yang tidak ada tetap terlihat sebagai kosong/belum tercatat.
- Populasi memakai santri unik, bukan jumlah baris riwayat kelas.
- Mutasi resmi memakai data berstatus disetujui.
- Perpindahan internal memakai audit siklus santri dan dideduplikasi dengan
  data pindahan pada tanggal yang sama.
- Angka dan definisi ditampilkan berdampingan agar tidak menimbulkan tafsir
  yang berbeda.
