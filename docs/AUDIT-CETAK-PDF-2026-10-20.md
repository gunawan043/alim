# Audit Halaman Cetak — 20 Oktober 2026

## Tujuan

Memastikan semua endpoint cetak yang digunakan pengguna langsung menghasilkan PDF
melalui Dompdf, bukan halaman HTML dengan tombol `window.print()`.

## Arsitektur

Semua endpoint baru memakai satu layanan:

`app/Services/Pdf/DocumentPdfService.php`

Layanan ini:

- merender Blade menjadi HTML;
- menjalankan Dompdf dengan opsi `isRemoteEnabled`, `isHtml5ParserEnabled`, dan font cache;
- mendaftarkan font Cambria/Caladea;
- mendukung kertas A4 dan orientasi portrait/landscape;
- menyembunyikan toolbar browser (`.no-print`, `.toolbar`, `.print-btn`, `.btn-print`);
- mengembalikan `Content-Type: application/pdf` dan nama berkas `.pdf`.

## Endpoint yang diubah menjadi PDF langsung

| Modul | Route | Orientasi |
|---|---|---|
| Supervisi | `waka.supervisi-sk.cetak` | Portrait |
| Supervisi | `waka.supervisi.jadwal.cetak` | Landscape |
| Supervisi | `waka.supervisi.cetak` | Portrait |
| Surat | `waka.surat-keluar.cetak` | Portrait |
| SK | `user.institution-decrees.print` | Landscape |
| Jadwal KBM | `user.jadwal-kbm.cetak` | Landscape |
| Evaluasi | `user.kisi-kisi-soal.cetak` | Landscape |
| Evaluasi | `user.paket-soal.naskah.cetak` | Portrait |
| Evaluasi | `user.paket-soal.kartu-soal.cetak` | Landscape |
| Evaluasi | `user.paket-soal.validasi-soal.cetak` | Portrait |
| Nilai | `user.schools.nilai-kelas.leger.cetak` | Landscape |
| Tahfidz | `user.tahfidz.muqorror.print` | Landscape |
| Tahfidz | `user.tahfidz.rpp.print` | Portrait |
| Tahfidz | `user.tahfidz.mutabaah.print` | Landscape |
| Tahfidz | `user.tahfidz.tasmi.certificate.print` | Landscape |
| UKS | `user.uks.prescriptions.print` | Portrait |
| UKS | `user.uks.referrals.print` | Portrait |
| Asrama | `user.boarding-regulations.print` | Portrait |
| Asrama | `user.asrama.permits.card` | PDF kartu (58 mm) |
| Asrama | `user.asrama.permits.bulk-card` | PDF kartu (58 mm) |
| Asrama | `user.asrama.visits.card` | PDF kartu (58 mm) |
| QR | `user.qr.print` | Portrait |
| Sarpras | `sarpras.qr.print` | PDF label A4 |
| Kurikulum | `user.kurikulum.proyek.print` | Landscape |
| Rekrutmen | `user.ats.applications.export-pdf` | Landscape |
| Pelanggaran | `user.violation-points.export-pdf` | Landscape |

## Endpoint yang sudah PDF sebelum audit

Endpoint berikut sudah memakai Dompdf dan tidak diubah:

- LJK (`user.ljk.pdf`, `user.ljk.soal.pdf`)
- Rapor (`user.schools.nilai-kelas.rapor.*`)
- Payroll (`user.payroll-slip.pdf`)
- Sarpras laporan aset, QR, dan sampah
- Mutasi siswa
- Laporan pengguna
- Seluruh cetak dokumen kurikulum (Kaldik, Pekan Efektif, ATP, PROTA, PROSEM, RPM)

## Catatan

- Modul Bahasa memiliki enam route `print` yang masih mengembalikan `view()`, tetapi
  file Blade-nya belum tersedia di repositori ini. Route tersebut tidak diubah karena
  berada di luar modul yang diaudit dan akan tetap gagal sampai view-nya dibuat.
- QR verifikasi surat sekarang memakai data URI SVG. SVG inline langsung tidak
  dirender oleh Dompdf; data URI berhasil dirender.

## Verifikasi

Pengujian HTTP dengan data nyata pada 14 endpoint utama:

- status `200`;
- `Content-Type: application/pdf`;
- isi berkas diawali `%PDF`;
- orientasi MediaBox diperiksa:
  - landscape: `841.89 × 595.28`;
  - portrait: `595.28 × 841.89`.

File yang diuji: SK supervisi, jadwal supervisi, surat keluar, SK institusi,
jadwal KBM, kisi-kisi, naskah soal, kartu soal, validasi soal, QR kelas,
muqorror, RPP tahfidz, mutabaah, dan leger.

Kartu izin dan kartu kunjungan diuji terpisah dengan data sementara:
keduanya menghasilkan PDF 58 mm dan QR tampil tanpa ekstensi Imagick.
