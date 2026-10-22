# Modul Departemen Bahasa — 2026-10-20

## Cakupan MVP

### 1. Hafalan Mufradat
- Periode/pekan mufradat dengan rentang tanggal, mading sekolah, mading asrama, dan batas minimal kosakata.
- Daftar kosakata: kata Arab, arti Indonesia, contoh kalimat.
- Jadwal setoran minimal 3×/pekan.
- Pembagian mustami per periode dan asrama/kelas.
- Daftar hadir/setoran per tanggal; nilai dihitung dari kata benar ÷ total kata.
- Ambang lulus 80%; di bawahnya otomatis `ulang`.
- Laporan evaluasi per santri: jumlah setoran, kata, nilai rata-rata, tindak lanjut.
- Cetak: Daftar Mufradat, Daftar Hadir, Laporan Evaluasi.

### 2. Pelanggaran Bahasa
- Pencatatan pelanggaran: tanggal, tempat, santri, kelas, jenis, poin.
- Pembinaan dan berita acara; cetak laporan pembinaan.
- Rekap poin per santri; penanda pembinaan Wakil Departemen Bahasa pada ambang 100 poin.
- Filter bulan/status/nama.

### 3. AM Nasyath Syahri
- Perencanaan kegiatan bulanan: pidato, permainan kata, film, drama, lainnya.
- Tema, tanggal/waktu/tempat, pelaksana, MC, panitia, peserta.
- Catatan partisipasi dan laporan kegiatan.
- Cetak laporan kegiatan.

## Akses

- **Departemen Bahasa**: supervisor, pengelola struktur, laporan.
- **Guru Bahasa Arab/Inggris/Koordinator Guru Bahasa**: input operasional.
- **Asrama/Wali Kamar**: boleh mencatat setoran dan pelanggaran, tidak mengubah struktur program.
- **Kepala/Wakil Satuan Pendidikan**: monitoring.
- **Super Admin**: akses penuh.

## Route

Semua di bawah `/{userId}/bahasa` dengan nama `user.bahasa.*`:

- `user.bahasa.dashboard`
- `user.bahasa.mufradat.*`
- `user.bahasa.violations.*`
- `user.bahasa.activities.*`

## Data

Tabel utama:
- `language_vocabulary_periods`
- `language_vocabularies`
- `language_mustami_assignments`
- `language_mufradat_schedules`
- `language_mufradat_setorans`
- `language_violations`
- `language_coachings`
- `language_monthly_activities`
