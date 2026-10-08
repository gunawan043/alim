# Changelog — Sesi Pengembangan Oktober 2026

Ringkasan seluruh modul yang dikerjakan (belum termasuk modul sebelumnya yang sudah di-commit di `main`).

## 1. Dashboard per Role (1 Role = 1 Dashboard)
- 9 dashboard role dibangun dengan pola `{role}/index` + `config/dashboard/{role}.php` + controller per role.
- Widget = gabungan jabatan (structural position) ∪ tugas tambahan (gtk_additional_tasks), deduplikasi, cache 5 menit per widget, isolasi error per widget, tanpa data dummy.
- Grid flex auto-fill (setiap baris selalu penuh), markup Skote/Velzon, ApexCharts.
- Library widget bersama di `resources/views/dashboard/_shared/` (partials + widgets + fallback `_stat/_chart/_quick-action/_table`).
- Konsol Super Admin dipindah ke `/system` (redirect dari dashboard Super Admin lama).

## 2. Konsol Sistem Super Admin
- `/system` (Konsol Sistem) dengan statistik nyata + 5 halaman tools: Features, Monitoring, Maintenance, Config, DevTools.
- Perbaikan route `permits.index` / `violations.index`, `DormitoryMasterController::myProfile`.
- `SystemSuperAdminBootstrap` diperbaiki (import Permission benar; role Super Admin kini memiliki 297 permission tanpa warning).

## 3. Hak Akses Menu (Sidebar Access)
- Tabel kontrol `sidebar_access` + `SidebarAccessController` (CRUD), 48 menu default di-seed idempotent.
- Helper `menu_allowed()` + directive `@menuallowed` diterapkan di seluruh sidebar role (86 seksi, 11 sidebar + 19 file tugas tambahan).
- Halaman super-admin: **Sidebar Access** & **Sidebar Menus**.

## 4. Approval Center
- `ApprovalService` ditulis ulang (start/approve/reject, cek step sebelumnya, otoritas, eksekusi final) di atas skema eksisting (tanpa migrasi destruktif; migrasi additive `2026_10_08_120000`).
- Halaman: Semua, Perlu Tindakan (filter actionable + tombol setujui/tolak inline + modal tolak), Riwayat, Detail, Track.
- `ApprovalRequestPolicy` diperbaiki; bug route-param controller diperbaiki.

## 5. Jadwal KBM & Master Jam Pelajaran
- `JadwalGeneratorService` ditulis ulang: sumber JP otoritatif `teaching_assignments.weekly_hours` (fallback `study_group_subjects.weekly_hours` → `grade_level_subjects.allocation_hours` → `subjects.credit_hours`), master slot `class_schedule_slots` (jam + `is_break`), laporan kekurangan JP, verifikasi konflik per tahun ajaran.
- Controller: bulk + per-rombel, validasi update (master slot + konflik per AY), permission domain `jadwalkbm.*`, route & form diperbaiki, view diseragamkan ke `layouts.master`.
- Menu baru **Jam Pelajaran** (master slot per hari: CRUD + template + validasi anti-tumpang-tindih), terhubung ke generator.
- `TeacherRosterService` untuk daftar guru (snapshot → data kepegawaian).

## 6. Absensi QR Guru & Rekap Pergantian Jam
- Perbaikan menyeluruh: paket `simple-qrcode` dipasang, QR kelas (SVG), signed URL benar, scanner in-app mem-parse payload JSON + signed URL map, bug listener `token_hash`, regenerate token, halaman *Absen Manual* (query kolom tidak ada), permission provider attendance `view/manual/export` (manual dibatasi jabatan tertentu).
- Halaman scan guru dirombak (statistik, spotlight kelas berjalan/berikutnya, tab kamera/manual, wizard 3 langkah, live clock + countdown, modal hasil).
- **Rekap Pergantian Jam** diimplementasikan penuh: jadwal vs scan, status Tepat/Terlambat/Keluar Cepat/Belum Keluar/Tidak Hadir (derivasi), filter + statistik + export Excel.
- Menu baru **QR Kelas**; sidebar role terkait dilengkapi.

## 7. Buku Administrasi → Leger → Rapor (Ekosistem Nilai)
- **Sumatif Harian dinamis**: `teacher_admin_books.sumatif_columns` (JSON definisi kolom) + `admin_nilai_sumatif.sumatif_harian` (JSON nilai kolom baru). S1–S6 legacy tetap (tidak dihapus), UI **Kelola Kolom** (tambah/rename/hapus/urut).
- **Satu aturan kalkulasi** via `SumatifHarianService` — wizard, autosave, grid wali kelas, halaman legacy, dan simpan SAS semuanya memakai service yang sama (RS = rata-rata semua SH terisi; RSA/NR Murni/NR Final seragam; simpan parsial tidak menimpa SH; dashboard `nr_final` konsisten).
- **Leger & Rapor STS + SAS**: parameter `jenis=sts|sas`; SAS memakai Nilai Akhir (NR Final, fallback SAS); `final_score` & `predicate` disimpan saat cetak SAS.
- **KKTP disatukan**: satu nilai (kolom `kkm_score` otoritatif + mirror `kktp_score`), backfill migrasi `2026_10_08_210000`, UI satu kolom, kedua penulis (KKTP admin & grade-levels) menulis kedua kolom. Istilah "KKM" dihapus dari seluruh UI → **KKTP**.
- **Catatan Wali Kelas** (kolom existing `raport_registrations.homeroom_note`): tombol + modal di halaman Rapor, dipakai di cetak rapor.
- API mobile: `RaportController` diperbaiki (tabel/join salah) + `SantriDataController` mengirim kolom dinamis.

## 8. Data Gap Ditutup (Armada, Aduan Wali, SPP)
- Migrasi `2026_10_08_220000`:
  - `vehicles` — armada/transport (URT: Driver/Pengemudi/Petugas Armada Kendaraan).
  - `guardian_complaints` — aduan wali santri (Personalia: Petugas Layanan Aduan).
  - `spp_bills` — tagihan & pembayaran SPP (Keuangan: Kasir SPP).
- 12 widget baru (stat + table) di `dashboard/_shared`, badge status `_table` diperluas, config role disambungkan (tanpa dummy — nilai 0 bila kosong).

## 9. Super Admin — Log & Monitoring
- 5 halaman dilengkapi kartu statistik ringkas: Audit Log, Password Reset Log, Token & Sesi, Failed Jobs, Notifikasi Sistem.
- Perbaikan query grouping `orWhereNull` pada Token & Sesi (Sanctum).

## 10. Lain-lain
- Unifikasi istilah KKTP di dokumen Leger/Rapor/export/paket soal/sidebar.
- Dokumentasi usulan skema 5 role tanpa tabel: `docs/usulan-skema-5-role.md`.
- Perbaikan snapshot authorization: arsip lama dihapus sebelum arsip baru (unique index `(user_id, scope_key, is_current)`).

## 11. Kalender Pendidikan → Pekan Efektif (Fondasi Perangkat Pembelajaran)
- **Kalender Pendidikan** (`kaldik`) menjadi sumber data utama: tambah kolom `semester` (ganjil/genap) + tipe baru `libur`, `ujian`, `kegiatan`, `hari_efektif`; filter semester + tampilan semester di modal kalender.
- **Kewenangan**: pengelolaan Kaldik hanya Super Admin & Pimpinan (policy `canManageKaldik` + policy dipanggil langsung karena registrar snapshot meng-intercept ability Gate `create/update`); Admin TU tetap mengelola agenda satuan kerjanya; user lain melihat sesuai konteks satuan pendidikan (scope query dirapikan + tanpa kebocoran saat work unit kosong).
- **Pekan Efektif** kini turunan kalender via `PekanEfektifService`:
  - `semesterRange()` otomatis membagi semester dari tanggal tahun ajaran (Jul–Des / Jan–Jun; kolom override `academic_years.semester_{ganjil,genap}_{start,end}` tersedia bila ingin eksplisit).
  - `computeWeeks()`/`generate()` menghitung minggu efektif, hari efektif (Senin–Sabtu, dikurangi event **Libur**), minggu libur/ujian/kegiatan, keterangan event — tersimpan di `pekan_efektif` (`jumlah_hari`, `is_generated`, `generated_at`).
  - `effectiveJpForStudyGroup()` → JP efektif = JP/minggu × minggu efektif (dasar alokasi & perencanaan pembelajaran guru).
- **UI**: tombol *Generate Pekan Efektif* + kartu ringkasan di halaman Waka/Kurikulum; halaman read-only baru `/{userId}/pekan-efektif` untuk Guru & Kurikulum (rincian pekan + alokasi JP efektif per kelas), menu ditambahkan di sidebar Pimpinan, Super Admin, dan Satuan Pendidikan (Guru & Tim Kurikulum).
- Migrasi additive `2026_10_08_230000` (tanpa menghapus kolom/tabel lama; tabel `academic_calendars` legacy dibiarkan).

## Testing
- `tests/Feature/JadwalPergantianJamTest.php` — generator, konflik, QR end-to-end, jam pelajaran, rekap.
- `tests/Feature/SumatifHarianDinamisTest.php` — SH dinamis, unifikasi kalkulasi, Leger/Rapor STS & SAS, KKTP, catatan wali.
- `tests/Feature/TeacherQrScanControllerTest.php` — diperbarui agar berjalan di SQLite (snapshot permission + `Event::fake` terarah).
- `tests/Feature/KaldikPekanEfektifTest.php` — pembagian semester, generate pekan efektif dari kalender (minggu/hari/libur/ujian), alokasi JP efektif, policy pengelolaan (Super Admin/Pimpinan vs Satuan Pendidikan/Guru), halaman pekan efektif untuk Guru & Satuan Pendidikan.
- Smoke MySQL untuk setiap modul dilakukan dengan data sementara yang selalu dibersihkan.
