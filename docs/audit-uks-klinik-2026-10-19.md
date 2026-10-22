# Audit & Implementasi Sistem Klinik UKS

Tanggal: 19 Oktober 2026
Modul: UKS (Unit Kesehatan Sekolah)
Stack: Laravel 11, Blade, Bootstrap 5, MySQL/SQLite

## 1. Ringkasan Eksekutif

Modul UKS sebelumnya sudah memiliki pendaftaran pasien, status perawatan, bed, catatan perkembangan, pemberian obat, jadwal shift, dan rekam medis dasar. Namun alurnya belum seperti klinik: belum ada antrean/triase, lembar pemeriksaan SOAP, resep terstruktur yang terhubung ke stok obat, surat rujukan pasien, dan farmasi sebagai antrean kerja.

Perbaikan yang diterapkan:

- Antrean klinik harian dengan nomor antrean otomatis dan prioritas triase P1/P2/P3.
- Lembar klinik (workspace) berisi SOAP, tanda vital, ICD-10, disposisi, dan resep.
- Resep multi-item yang terhubung ke stok obat dan antrean farmasi.
- Penyerahan obat memotong stok dan menulis log obat + riwayat pemberian.
- Rujukan pasien dengan nomor surat, fasilitas tujuan, urgensi, dan status kembali.
- Dashboard UKS diperbaiki dari status lama (`aktif`/`dirujuk`) ke status klinis saat ini.
- Perbaikan bug pada alur status, bed, penyimpanan JSON vital, dan rujukan.

## 2. Temuan Audit

### Kritis

1. **Status `dirujuk` tidak valid ditulis ke database**
   - `PatientController::discharge()` menerima aksi `dirujuk` lalu menyimpan `status = 'dirujuk'`.
   - Enum/status yang berlaku adalah `dirujuk_ke_klinik` atau `dirujuk_ke_rumah_sakit`.
   - Risiko: error SQL saat pasien dirujuk.

2. **Data pasien baru bisa hilang dari daftar berbasis sekolah**
   - `PatientController::store()` tidak mengisi `school_id` dari siswa/konteks sekolah.
   - Daftar pasien memfilter `school_id`, sehingga pasien dengan `school_id = null` tidak tampil.

3. **Vital dan gejala tersimpan double-encoded**
   - `vitals` dan `symptoms` di-cast `array`, tetapi controller memanggil `->toJson()` sebelum disimpan.
   - Eloquent melakukan encode ulang, sehingga akses `$patient->vitals['blood_pressure']` tidak bekerja.

4. **Dua alur perubahan status yang berbeda**
   - `PatientController::changeStatus()` dan `TreatmentStatusController::updateStatus()` punya logika berbeda.
   - Hanya salah satunya menulis `uks_status_histories`, sinkronisasi presensi, dan notifikasi wali.
   - `discharged_by` bisa tersimpan `null` pada status selesai.

### Tinggi

5. **Bed tidak konsisten**
   - `store()` bisa menandai `in_bed = true` tanpa assignment.
   - `assignBed()` di beberapa tempat memaksa status `rawat_uks` tanpa memeriksa transisi.
   - Status `uks_beds.status` tidak ikut berubah saat bed dipakai/dikosongkan.

6. **Dashboard UKS memakai status lama**
   - Widget `stat-pasien-aktif`, `table-pasien-aktif`, `stat-rujukan-bulan`, dan `table-rujukan-terbaru` masih memfilter `status = 'aktif'` / `'dirujuk'`.
   - Widget kunjungan klinik membaca tabel `student_clinic_visits` yang tidak dipakai alur UKS saat ini.

7. **Tidak ada antrean, triase, SOAP, resep, dan rujukan pasien**
   - Belum ada alur kerja klinik yang dapat dioperasikan harian.

8. **UI status tidak mengikuti transisi yang diizinkan**
   - Dropdown status di daftar pasien menampilkan semua status, bukan transisi yang valid.

### Sedang

9. **Duplikasi model/nama tabel**
   - Terdapat model dengan nama sama di `App\Models` dan `App\Models\Uks` untuk tabel yang sama.
   - Berisiko salah import dan perilaku tak terduga.

10. **Tidak ada nomor rekam medis**
    - Pasien berulang tidak punya identitas rekam medis yang stabil.

11. **Tidak ada audit trail farmasi**
    - Obat diberikan tanpa relasi ke resep/stok; stok tidak otomatis berkurang.

12. **Route UKS belum memakai permission granular**
    - Route hanya dilindungi `auth`, `employee.access`, `role.access`, dan konteks sekolah.
    - Rekomendasi: tambahkan `permission:uks_patient_view`, `uks_patient_create`, `uks_patient_edit` secara bertahap setelah snapshot permission siap.

13. **View Bed Management belum tersedia**
    - `BedManagementController` memanggil `uks.beds.index/create/show/edit`, tetapi file view-nya tidak ada.
    - Akibatnya menu Bed & Ruang Rawat berpotensi error saat dibuka.

14. **Lokasi perawatan santri belum tercatat**
    - Status klinis tidak cukup untuk menjawab: siapa dirawat di asrama, di UKS/ranjang, tetap ke sekolah, pulang, atau dirujuk.
    - Dibutuhkan field lokasi + histori perpindahan.

## 3. Implementasi

### 3.1 Database

Migrasi baru:

- `2026_10_19_120000_add_clinic_fields_to_uks_patients_table.php`
  - `medical_record_no`, `queue_number`, `queue_date`, `queue_status`, `triage_level`, `arrived_at`, `called_at`, `screened_at`, `completed_at`, `allergies`, `follow_up_date`.
- `2026_10_19_120001_add_soap_fields_to_uks_treatments_table.php`
  - `subjective`, `objective`, `assessment`, `plan`, `physical_exam`, `icd10_code`, `icd10_name`, `disposition`, `follow_up_date`, `is_locked`.
- `2026_10_19_120002_create_uks_prescriptions_table.php`
- `2026_10_19_120003_create_uks_prescription_items_table.php`
- `2026_10_19_120004_create_uks_referrals_table.php`
- `2026_10_19_130000_add_care_location_to_uks_patients_table.php`
- `2026_10_19_130001_create_uks_patient_location_logs_table.php`

Catatan: kolom `status` pada `uks_patients` di migrasi awal diubah dari `enum` menjadi `string` agar kompatibel dengan SQLite dan penambahan status baru. Pada database produksi yang sudah berjalan, perubahan ini tidak memengaruhi data lama.

### 3.2 Model

- `App\Models\Uks\UksPatient`
  - Konstanta status, queue, triase, dan lokasi perawatan.
  - Relasi `prescriptions`, `referrals`, `encounters`, `latestEncounter`, `locationLogs`.
  - Accessor label/status triase, label antrean, waktu tunggu, label lokasi.
- `App\Models\Uks\UksPatientLocationLog`
  - Histori perpindahan lokasi perawatan (asal → tujuan, bed, petugas, waktu).
- `App\Models\Uks\UksPrescription`
- `App\Models\Uks\UksPrescriptionItem`
- `App\Models\Uks\UksReferral`
- `App\Models\UksTreatment` diperluas untuk field SOAP dan relasi resep.

### 3.3 Service

`App\Services\Uks\UksClinicService` menjadi satu pintu untuk:

- Pendaftaran pasien + nomor antrean + nomor rekam medis.
- Perubahan status dengan validasi transisi, histori, timeline, presensi, dan notifikasi wali.
- Pemeriksaan SOAP.
- Resep dan penyerahan obat (potong stok + log).
- Rujukan dan kepulangan dari rujukan.
- Penempatan/pelepasan bed.

Semua controller UKS yang mengubah status kini memakai service ini agar tidak ada lagi dua alur berbeda.

### 3.4 Halaman Baru

- `/uks/clinic` — Antrean & triase klinik.
- `/uks/clinic/{uuid}` — Lembar klinik pasien (SOAP + resep + rujukan).
- `/uks/pharmacy` — Antrean resep, penyerahan obat, stok menipis, obat mendekati kedaluwarsa.
- `/uks/referrals` — Daftar rujukan pasien.
- `/uks/referrals/{uuid}/print` — Surat rujukan siap cetak.
- `/uks/prescriptions/{uuid}/print` — Resep siap cetak.
- `/uks/care-locations` — Papan lokasi perawatan santri (asrama/UKS/sekolah/rumah/faskes/lainnya).
- `/uks/beds` — Okupansi bed, detail bed, riwayat pemakaian, dan pelepasan bed dengan lokasi tujuan.

### 3.5 Alur Operasional Baru

1. **Pendaftaran** — petugas memilih siswa, keluhan, triase, dan tanda vital awal. Sistem membuat nomor antrean (`L-001`/`P-001`) dan nomor RM.
2. **Antrean** — pasien diurutkan berdasarkan triase P1 → P2 → P3, lalu nomor antrean.
3. **Pemeriksaan** — petugas memanggil, memulai pemeriksaan, mengisi SOAP, ICD-10, disposisi, dan resep.
4. **Farmasi** — resep masuk antrean farmasi. Saat obat diserahkan, stok berkurang, log obat dibuat, dan riwayat pemberian tercatat.
5. **Rujukan** — jika perlu, petugas membuat surat rujukan; status pasien otomatis menjadi dirujuk ke klinik/RS.
6. **Kembali** — hasil rujukan dicatat; pasien dapat kembali ke asrama/sekolah, dijemput wali, pulang, atau selesai.
7. **Rawat inap** — penempatan bed memvalidasi gender, ketersediaan, dan mengubah status bed menjadi `dipakai`.

### 3.6 Pendataan Lokasi Perawatan

Field baru pada `uks_patients`:

- `care_location`: `asrama`, `uks`, `sekolah`, `rumah`, `faskes`, `lainnya`.
- `location_notes`, `location_updated_at`, `location_updated_by`.

Tabel histori baru: `uks_patient_location_logs` mencatat perpindahan dari lokasi asal ke lokasi tujuan, bed, catatan, petugas, dan waktu.

Halaman baru `/uks/care-locations`:

- Kartu ringkasan jumlah santri per lokasi.
- Filter lokasi, status klinis, dan pencarian.
- Daftar santri: lokasi, status, bed, diagnosis/keluhan, waktu update, dan petugas.
- Form ubah lokasi langsung dari tabel.

Integrasi otomatis:

- Pendaftaran → lokasi `uks`.
- Penempatan bed → lokasi `uks` + bed.
- Pelepasan bed → lokasi tujuan dipilih (default asrama).
- Status kembali ke asrama → `asrama`.
- Status kembali ke sekolah → `sekolah`.
- Status pulang/dijemput/selesai → `rumah`.
- Status rujukan → `faskes`.
- Perubahan lokasi manual tetap dicatat di histori.

### 3.7 Bed & Ruang Rawat

- View `uks/beds/index`, `create`, `edit`, `show`, dan form partial dibuat.
- Halaman bed menampilkan okupansi per asrama/gedung, pasien yang menempati, No. RM, diagnosis, lokasi perawatan, waktu masuk, dan aksi.
- Detail bed menampilkan pasien saat ini, riwayat pemakaian, dan form kosongkan bed dengan pilihan lokasi tujuan.
- Status bed tidak bisa diubah menjadi tersedia/perbaikan selama masih ada pasien aktif.

### 3.8 Konsistensi UI Halaman UKS

Seluruh halaman daftar UKS diseragamkan mengikuti pola `gtk/index.blade.php`:

- Statistik card di atas.
- Header pencarian + tombol filter.
- Filter modal (`x-uks.filter-modal`) dan quick filter badge.
- Tabel `table-container` + `table-freeze`.
- Aksi baris berupa dropdown.
- Modal untuk aksi berisiko atau form panjang.

Halaman yang diterapkan:

| Halaman | Pola GTK | Modal |
|---|---|---|
| Daftar Pasien | Ya | Filter, ubah status |
| Antrean Klinik | Ya | Filter |
| Papan Lokasi Rawat | Ya | Filter, ubah lokasi |
| Farmasi & Resep | Ya | Filter, serahkan obat, batalkan resep |
| Rujukan Pasien | Ya | Filter, tandai kembali |
| Bed & Ruang Rawat | Ya | Filter, tambah/edit, hapus |
| Data Santri & Kesehatan | Sudah ada | Filter |
| Rekam Medis GTK | Sudah ada | Filter, tambah pemeriksaan |
| Status Perawatan | Detail | Ubah status, catatan, pemberian obat |
| Detail Pasien | Detail | Ubah status |
| Penjadwalan Shift | Ya | Tambah shift |

Komponen bersama:

- `resources/views/uks/_ui-styles.blade.php`
- `resources/views/components/uks/filter-modal.blade.php`

## 4. Verifikasi

Test baru: `tests/Feature/Uks/UksClinicWorkflowTest.php`

Mencakup:

- Skema kolom/tabel baru.
- Pendaftaran membuat antrean, RM, triase, dan status awal.
- Nomor RM stabil untuk kunjungan berulang.
- Transisi status menulis histori dan menyelesaikan antrean.
- Transisi tidak valid ditolak.
- Resep mengurangi stok saat diserahkan.
- Rujukan membuat record dan mengubah status pasien.
- Pasien dapat ditandai kembali dari rujukan.
- Penempatan dan pelepasan bed memperbarui okupansi.
- Lokasi perawatan tersimpan, berubah otomatis mengikuti status, dan tercatat di histori.
- Papan lokasi membedakan asrama, UKS/ranjang, sekolah, rumah, dan faskes.

Hasil:

```text
UksClinicWorkflowTest: 11 deprecated (75 assertions)
UksTreatmentStatusTest: 11 deprecated (48 assertions)
Total: 22 deprecated (123 assertions)
```

`php artisan view:cache` berhasil — seluruh Blade dapat dikompilasi.

Catatan: test lama `UksTreatmentStatusTest` diperbaiki setup-nya agar membuat baris `students` yang valid sebelum membuat `UksPatient`, karena foreign key `student_id` sekarang benar-benar dijaga.

## 5. Rekomendasi Lanjutan

1. **Permission granular**: aktifkan middleware `permission:uks_patient_view` dkk. setelah snapshot permission per sekolah dipastikan terbentuk.
2. **Master ICD-10**: tambahkan tabel referensi ICD-10 agar kode diagnosis bisa dipilih, bukan input bebas.
3. **Laboratorium**: tambahkan order lab dan hasil lab jika layanan UKS memiliki laboratorium sederhana.
4. **Farmasi lanjutan**: batch/lot obat, expired per batch, dan audit selisih stok opname.
5. **Billing/BPJS**: jika diperlukan, tambahkan penjamin, biaya tindakan, dan integrasi klaim.
6. **Backfill data lama**: isi `medical_record_no` dan `queue_number` untuk pasien lama; saat ini pasien lama tetap tampil di antrean lewat fallback `admitted_at`.
7. **Notifikasi**: tambahkan notifikasi antrean/farmasi realtime (Pusher sudah tersedia) dan WhatsApp untuk perubahan status penting.
8. **Audit akses**: catat siapa membuka/mengubah rekam medis (selain histori status yang sudah ada).
9. **Hapus/alias model duplikat** di `App\Models` dan `App\Models\Uks` secara bertahap agar satu sumber kebenaran.
