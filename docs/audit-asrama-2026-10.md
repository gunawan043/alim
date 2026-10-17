# Audit Fitur Asrama — Oktober 2026

Ruang lingkup: modul asrama (`/{userId}/asrama/...`), dashboard asrama, perizinan, presensi, kegiatan,
inventaris, kebersihan, laporan, dan integrasi wali.

Keterangan status: **Selesai** = berfungsi, **Diperbaiki** = baru diperbaiki pada batch ini,
**Perlu** = masih perlu tindak lanjut.

## 1. Daftar Fitur

| # | Fitur | Status | Catatan |
|---|---|---|---|
| 1 | Master asrama, gedung/wing, kamar | Perlu | Akses `dormitory-master-all-access` terlalu ketat untuk Kepala/Wali Asrama |
| 2 | Penghuni asrama (check-in/out, kamar, bed) | Perlu | Perlu validasi kapasitas/bed dan riwayat kamar |
| 3 | Mutasi/rotasi kamar | Diperbaiki | Enum `move_type` diselaraskan dengan DB; approve mengecek kapasitas & mengisi `bed_number` |
| 4 | Presensi harian/malam (per sesi) | Diperbaiki | Form/controller/rekap end-to-end; filter tanggal & kamar |
| 5 | Rekap presensi bulanan | Diperbaiki | Dihitung dari presensi nyata, ringkasan per santri & kamar |
| 6 | **Presensi kegiatan asrama** | **Baru** | Kepala/Wakil membuat kegiatan; Staf/Wali Kamar mengisi presensi; tutup/buka kegiatan |
| 7 | Template & log kegiatan | Perlu | UI lama masih ada; perlu disatukan dengan presensi kegiatan baru |
| 8 | Perizinan pulang (pengajuan, approve, QR, overdue) | Diperbaiki sebagian | Fatal `permitRepository`, `CarbonImmutable`, route verify, dispatch ganda, `wali_user_id` diperbaiki |
| 9 | Kuota izin & kebijakan per asrama | Perlu | Perlu uji kuota, cuti, dan pengecualian |
| 10 | Perizinan sakit/UKS | Perlu | Integrasi status kesehatan & izin belum konsisten |
| 11 | Kunjungan (visit) | Perlu | Perlu validasi & notifikasi wali |
| 12 | Pelanggaran & penghargaan | Perlu | Redirect reward masih ke daftar pelanggaran; perlu audit alur |
| 13 | Inventaris | Diperbaiki | Enum kondisi diselaraskan: `baik/rusak_ringan/rusak_berat/hilang` |
| 14 | Kebersihan/sanitasi | Perlu | Laporan memakai route/kolom yang tidak ada |
| 15 | Informasi & broadcast | Perlu | `broadcastToDormitoryWalis` placeholder; broadcast ganda |
| 16 | Dashboard asrama | Perlu | Statistik inventaris rusak & beberapa kartu belum akurat |
| 17 | Laporan & ekspor CSV/Excel | Perlu | Perizinan: `category`/`pickup_mode` kosong; sanitasi lintas asrama |
| 18 | Akses & peran | Diperbaiki sebagian | Presensi kegiatan memakai policy baru; akses master masih perlu dilonggarkan untuk Kepala/Wali |
| 19 | Notifikasi wali | Baru (rapor/nilai) | Notifikasi in-app web + API mobile wali sudah dipakai untuk publikasi rapor/nilai; asrama masih perlu diseragamkan |

## 2. Rekomendasi Tindak Lanjut

### P0 — Integritas & keamanan data
1. **Satukan template/log kegiatan lama dengan presensi kegiatan baru** agar tidak ada dua sumber data.
2. **Perbaiki akses master asrama** untuk Kepala/Wakil Asrama; masukkan `room_supervisors` ke `canAccessDormitory`.
3. **Validasi kepemilikan data**: `room_id`/`resident_id`/`student_id` harus milik asrama yang sama pada izin, pelanggaran, presensi, dan mutasi.
4. **Perbaiki laporan sanitasi & inventaris** (route, kolom, filter asrama).
5. **Uji ulang alur izin**: kuota, overdue, QR verify, notifikasi wali spesifik.

### P1 — Penyempurnaan operasional
6. **Presensi kegiatan**: tambahkan rekap per kegiatan/per santri, ekspor CSV/PDF, dan notifikasi wali untuk alpa.
7. **Presensi harian**: tampilan per kamar + status verifikasi musyrif, serta job rekap bulanan otomatis.
8. **Mutasi kamar**: riwayat kamar, persetujuan berjenjang, dan penomoran bed otomatis yang lebih kuat.
9. **Dashboard asrama**: KPI kehadiran malam, izin aktif, keterlambatan kembali, dan kegiatan hari ini.
10. **Broadcast darurat**: perbaiki pengiriman ke wali (bukan placeholder) dan hindari pengiriman ganda.

### P2 — Fitur lanjutan
11. **QR gatepass** untuk keluar/masuk asrama dan verifikasi petugas.
12. **Notifikasi wali terpusat** untuk izin, keterlambatan kembali, presensi kegiatan alpa, dan pelanggaran.
13. **Mode offline/PWA** untuk pencatatan presensi di malam hari.
14. **Audit log** perubahan data asrama (mutasi, izin, presensi).

## 3. Perubahan Batch Ini
- Presensi kegiatan asrama (tabel, model, controller, UI, sidebar, test).
- Perbaikan presensi harian & rekap bulanan.
- Perbaikan fatal perizinan.
- Penyelarasan enum inventaris & mutasi kamar.
- Perbaikan akses `{userId}` dan penguncian nilai/rapor.

## 4. Pembaruan Batch 2 (Kegiatan Rutin & Laporan)

### Kegiatan rutin (baru)
- Jadwal rutin per asrama: **harian** atau **malam tertentu** (Senin–Ahad), periode mulai/selesai, jam, lokasi.
- **Target peserta**: semua penghuni atau **kelas tertentu** (multi-kelas).
- Sesi presensi dibentuk otomatis:
  - 14 hari pertama saat jadwal disimpan,
  - 30 hari saat tombol **Generate 30 Hari**,
  - setiap hari oleh command `dormitory:generate-activities --days=7` (scheduler 00:05).
- Idempoten: sesi yang sudah ada tidak digandakan.
- Presensi kegiatan hanya menampilkan santri sesuai target kelas.
- Notifikasi wali otomatis untuk santri **alpa** pada kegiatan.

### Laporan baru (CSV)
- Kunjungan (`reports.visits`)
- Mutasi kamar (`reports.room-moves`)
- Keluar asrama / check-out (`reports.checkouts`)
- Kegiatan asrama + rekap hadir/izin/sakit/alpa (`reports.activities`)

### Perbaikan lain
- Akses master asrama: Kepala/Wakil/Staf/Wali Kamar dapat melihat asrama yang menjadi kewenangannya;
  daftar dibatasi ke asrama yang dapat diakses.
- `canAccessDormitory()` sekarang menghitung **wali kamar aktif** (`room_supervisors`).
- Modal global success/gagal/warning/info (lord-icon) dipasang di layout utama dan waka.

### Masih terbuka
- Laporan sanitasi masih memakai kolom/route lama; perlu diperbaiki.
- Broadcast darurat ke wali masih placeholder.
- Validasi kepemilikan `room_id`/`resident_id` di semua modul asrama.
- Template/log kegiatan lama perlu disatukan dengan kegiatan rutin baru.
- Beberapa halaman lama belum memakai pola UI `gtk/index` sepenuhnya.
