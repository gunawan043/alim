# Analisis Fitur Pengelolaan Sampah Sesuai DLH

## Tujuan
Mendukung pencatatan sampah pondok yang terpisah, terukur, terdokumentasi, dan siap diaudit
sesuai ketentuan Dinas Lingkungan Hidup (DLH).

## Jenis Sampah
- Organik: sisa dapur, daun, ranting, sisa makanan.
- Anorganik: plastik, kertas, kaleng, botol, kardus.
- B3/limbah khusus: lampu, baterai, elektronik, obat kedaluwarsa, kemasan kimia.
- Residu: sampah yang tidak dapat didaur ulang.

## Sumber Sampah
Dapur, asrama putra/putri, kelas, kantor, UKS, masjid, taman, kantin, dan area umum.

## Alur Yang Disarankan
1. Petugas mencatat timbulan sampah per sumber (kg atau m³).
2. Sampah dipilah: organik, anorganik, B3, residu.
3. Sampah organik diarahkan ke kompos/biopori; anorganik ke bank sampah/TPS;
   B3 disimpan terpisah dan diserahkan ke pihak berwenang.
4. Setiap penyerahan ke DLH/pihak ketiga dicatat dengan berat, tanggal, petugas,
   dan bukti foto/tanda terima.
5. Koordinator memverifikasi catatan harian dan rekap bulanan.

## Indikator Kepatuhan
- Persentase pemilahan benar.
- Berat sampah organik/anorganik/B3/residu per bulan.
- Jumlah sampah yang diserahkan ke DLH.
- Temuan sampah tercampur.
- Keterisian TPS dan jadwal pengangkutan.
- Dokumentasi foto sebelum/sesudah.

## Rancangan Tabel (tahap berikutnya)
- `waste_sources`
- `waste_logs` (source, date, type, weight_kg, volume_m3, sorting_status, notes)
- `waste_handovers` (date, destination, type, weight_kg, receipt_url, officer_id)
- `waste_facilities` (TPS, komposter, bank sampah, lokasi, kapasitas)
- `waste_violations`/temuan (foto, jenis, tindak lanjut)

## Catatan
Fitur ini belum dibuat pada tahap ini; dokumen ini menjadi dasar implementasi berikutnya.
