# Operasional, Safeguarding, dan Keamanan Modul Asrama

Dokumen ini adalah runbook minimum sebelum modul asrama dipakai penuh.

## 1. Alur penjemput (safeguarding)

1. Sistem mencocokkan penjemput dengan daftar mahrom/orang tua santri.
2. Jika cocok, `pickup_authorization_status = approved`.
3. Jika penjemput di luar daftar mahrom, status menjadi `pending`.
4. Scan keluar diblokir sampai Kepala/Wakil Asrama menyetujui di halaman detail izin.
5. Jika ditolak, token QR lama dicabut. Kartu harus dicetak ulang setelah data penjemput yang sah diajukan.

Aturan ini menjaga agar keluarga di luar daftar mahrom tetap dapat diakomodasi tanpa melemahkan kontrol.

## 2. Keamanan QR

- Token izin memiliki masa berlaku (`scan_token_expires_at`).
- Token dapat dicabut (`scan_token_revoked_at`).
- Kartu kunjungan memakai token bertanda tangan dengan masa berlaku.
- Semua percobaan scan dicatat di `dormitory_qr_scan_logs`.
- Endpoint scan dibatasi 30 request/menit.
- Jika kartu hilang atau difoto pihak tidak berwenang, gunakan revoke/regenerate token.

## 3. PDP / data pribadi

- Nomor HP dan nomor identitas tamu dimasking pada daftar kunjungan.
- Data lengkap hanya untuk petugas yang berwenang.
- Export laporan harus dibatasi ke role yang berwenang.
- Simpan data sesuai kebutuhan operasional dan audit; tetapkan kebijakan retensi.
- Jangan membagikan file export berisi NIK/HP melalui kanal publik.

## 4. Backup dan restore

Backup minimum:

```bash
php artisan db:backup
# atau mysqldump sesuai konfigurasi produksi
tar -czf storage-backup.tar.gz storage/app public/storage
```

Wajib diuji:

1. Restore database ke environment staging.
2. Restore file storage/foto.
3. Login sebagai superadmin.
4. Buka halaman izin, kunjungan, scan, dan cetak kartu.
5. Pastikan QR baru dapat discan.

## 5. Mode darurat/offline

Siapkan prosedur manual jika server atau scanner mati:

- formulir izin/kunjungan kertas;
- buku serah-terima penjemput;
- verifikasi KTP dan telepon wali;
- input ulang ke sistem setelah server pulih;
- catat insiden di audit log.

## 6. Import data

Menu **Administrasi → Import Data** digunakan untuk:

- import gedung dan kamar via template XLSX;
- update kamar berdasarkan `kode_kamar`;
- membuat gedung otomatis jika belum ada.

Data santri **tidak** diimpor dari modul asrama; sumbernya tetap Satuan Pendidikan.

## 7. UAT minimum

- satu asrama putra dan satu asrama putri;
- izin: setujui → scan keluar → scan kembali;
- kunjungan: setujui → scan keluar → scan kembali;
- penjemput mahrom dan non-mahrom;
- kartu hilang/revoke;
- cetak PDF single dan massal;
- restore backup dan scan ulang.

## 8. Revoke/regenerate kartu

- Tombol **Cabut QR Kartu** membuat token lama tidak berlaku.
- Tombol **Regenerate QR** membuat token baru; kartu wajib dicetak ulang.
- Gunakan jika kartu hilang, difoto pihak lain, atau penjemput berubah.

## 9. Backup otomatis

```bash
php artisan app:backup --keep=14 --with-files --verify
php artisan app:backup:verify --latest
php artisan app:backup:restore storage/app/backups/backup_xxx.sql.gz --database=alim_restore_drill --force
```

Backup berjalan setiap hari pukul 02:15. Restore harus diuji ke database drill/staging, bukan langsung ke produksi.

## 10. Consent dan retensi PDP

- Consent wali dicatat dari halaman Detail Santri → Persetujuan Data Wali.
- Retensi:
  - kunjungan dianonimkan setelah 24 bulan;
  - izin dianonimkan setelah 36 bulan;
  - audit scan QR dihapus setelah 365 hari.
- Jalankan dry-run:

```bash
php artisan dormitory:purge-retention --dry-run
```

## 11. Blacklist tamu

Menu **Operasional → Blacklist Tamu** menolak pengajuan kunjungan berdasarkan nama, NIK, atau nomor HP. Notifikasi penjemput pending dikirim ke staf asrama dan Kepala Asrama.
