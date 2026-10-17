# Alur Satuan Pendidikan ALIM — dari Santri Masuk sampai Lulus

Dokumen ini memetakan perjalanan santri di satu satuan pendidikan ke fitur ALIM yang
tersedia saat ini, beserta celah yang perlu dilengkapi.

## 1. Penerimaan & Pendataan Santri
| Tahap | Menu | Status |
|---|---|---|
| Data santri baru (identitas, NISN, wali) | Peserta Didik → Data Santri | ✅ ada (`students.index`) |
| Mutasi masuk dari satuan lain | Peserta Didik → Mutasi | ✅ ada |
| Penerimaan & pembagian ke tingkat | Data Kelas & Rombel | ✅ ada |

## 2. Penempatan (Akademik & Asrama)
| Tahap | Menu | Status |
|---|---|---|
| Masuk rombel (kelas) | Pengaturan Rombel → Data santri per rombel | ✅ ada |
| Penempatan kamar asrama | Asrama → Penempatan Kamar (tarik santri belum berkamar) | ✅ ada |
| Perpindahan kamar (approval) | Asrama → Mutasi Kamar | ✅ ada |
| "Berhenti mondok" (tetap di sekolah) | Penempatan Kamar → tandai berhenti mondok | ✅ ada |

## 3. Penugasan & Wali Kelas
| Tahap | Menu | Status |
|---|---|---|
| Plotting guru mengajar (guru × mapel × rombel × JP) | Kurikulum → Plotting Guru Mengajar (± Input Massal, matriks SK) | ✅ ada |
| Susun SK pembagian tugas | Plotting → Lihat SK / buat SK | ✅ ada |
| Wali Kelas ditetapkan | Tugas Tambahan → pilih "Wali Kelas" + rombel | ✅ **tersinkron**: rombel otomatis mencatat wali kelasnya |
| Wali Kamar (asrama) | Asrama → Wali Kamar (pilih kamar) | ✅ ada |
| Guru hanya mengampu rombel-kelasnya | Buku Nilai & Jurnal | ✅ otomatis dari penugasan |

## 4. Kalender, Jam & Jadwal
| Tahap | Menu | Status |
|---|---|---|
| Kalender pendidikan (libur, kegiatan) | Kaldik | ✅ ada |
| Pekan efektif | Pekan Efektif (turunan kaldik) | ✅ ada |
| Master Jam Pelajaran (kegiatan/istirahat/pulang) | Jam Pelajaran | ✅ seeder default tersedia |
| **Generate jadwal sekolah (semua rombel)** | Jadwal Pelajaran → Generate Jadwal Sekolah | ✅ ada; tabel tampil per hari × jam |

## 5. Kegiatan Belajar (KBM)
| Tahap | Menu | Status |
|---|---|---|
| Absensi kelas | Absensi (wali kelas/petugas) | ✅ ada |
| Absensi mengajar guru (QR) | Absensi Guru QR | ✅ ada |
| Jurnal pembelajaran (realisasi TP + RPM) | Buku Nilai → Jurnal (w2) | ✅ ada |
| Izin/sakit santri (UKS & asrama) | UKS / Izin → memengaruhi kehadiran | ✅ ada |

## 6. Penilaian & Rapor
| Tahap | Menu | Status |
|---|---|---|
| Asesmen formatif | Buku Nilai → Formatif (w4) | ✅ ada |
| Nilai sumatif harian, STS/SAS (+ murni) | Buku Nilai → Sumatif (w3) | ✅ ada; aturan **Nilai Murni STS/SAS wajib dulu** |
| Rapor (leger, catatan wali/kurikulum) | Nilai Kelas → Rapor | ✅ ada |
| Wali kelas/kurikulum mengunci nilai final | Buku Nilai → Kunci | ✅ ada |

## 7. Naik Kelas / Mutasi / Kelulusan
| Tahap | Menu | Status |
|---|---|---|
| Proses naik kelas (rombel baru, buku lama diarsipkan) | Kenaikan Kelas | ✅ ada |
| Mutasi keluar/lulus menutup semua penghuni asrama | Otomatis (observer) | ✅ ada |
| Kelulusan & alumni (kartu riwayat nilai & rapor) | Alumni | ✅ ada |
| **Alur kelulusan formal (SK lulus, cetak ijazah)** | — | ⏳ perlu pengembangan |
| **PPDB online** | — | ⏳ perlu pengembangan (saat ini pendataan manual/import) |

## Celah & Prioritas Berikutnya
1. **Strukturisasi sidebar** satuan pendidikan (banyak duplikat: Kaldik ×5, Data Santri ×4, dll).
2. **Alur kelulusan formal**: SK kelulusan, status lulus terkunci, ijazah/sertifikat.
3. **PPDB**: formulir pendaftaran, seleksi, penerimaan → otomatis menjadi Data Santri.
4. **Wali Kelas dua arah**: dari form rombel → membuat tugas tambahan otomatis (saat ini arah sebaliknya sudah tersinkron).
