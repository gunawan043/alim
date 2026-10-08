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

## 11. Kalender Pendidikan → Pekan Efektif
- **Kalender Pendidikan** (`kaldik`) menjadi sumber data utama: tambah kolom `semester` (ganjil/genap) + tipe baru `libur`, `ujian`, `kegiatan`, `hari_efektif`; filter semester + tampilan semester di modal kalender.
- **Kewenangan**: pengelolaan Kaldik hanya Super Admin & Pimpinan (policy `canManageKaldik` + policy dipanggil langsung karena registrar snapshot meng-intercept ability Gate `create/update`); Admin TU tetap mengelola agenda satuan kerjanya; user lain melihat sesuai konteks satuan pendidikan (scope query dirapikan + tanpa kebocoran saat work unit kosong).
- **Pekan Efektif** kini turunan kalender via `PekanEfektifService`:
  - `semesterRange()` otomatis membagi semester dari tanggal tahun ajaran (Jul–Des / Jan–Jun; kolom override `academic_years.semester_{ganjil,genap}_{start,end}` tersedia bila ingin eksplisit).
  - `computeWeeks()`/`generate()` menghitung minggu efektif, hari efektif (Senin–Sabtu, dikurangi event **Libur**), minggu libur/ujian/kegiatan, keterangan event — tersimpan di `pekan_efektif` (`jumlah_hari`, `is_generated`, `generated_at`).
  - `effectiveJpForStudyGroup()` → JP efektif = JP/minggu × minggu efektif (dasar alokasi & perencanaan pembelajaran guru).
- **UI**: tombol *Generate Pekan Efektif* + kartu ringkasan di halaman Waka/Kurikulum; halaman read-only baru `/{userId}/pekan-efektif` untuk Guru & Kurikulum (rincian pekan + alokasi JP efektif per kelas), menu ditambahkan di sidebar Pimpinan, Super Admin, dan Satuan Pendidikan (Guru & Tim Kurikulum).
- Migrasi additive `2026_10_08_230000` (tanpa menghapus kolom/tabel lama; tabel `academic_calendars` legacy dibiarkan).

## 12. Kurikulum → CP → TP → ATP → Perangkat Pembelajaran
- **Tahap 1 — JP efektif satu aturan**: service baru `TeachingHoursResolver` (otoritatif `teaching_assignments.weekly_hours` → `study_group_subjects` → `grade_level_subjects` → `subjects.credit_hours` → default 2) dipakai bersama oleh Jadwal KBM dan Pekan Efektif; `effectiveJpForStudyGroup()` kini menggabungkan tiga sumber mapel (plotting SK → mapel rombel → mapel jenjang) tanpa duplikasi; `effectiveJpForSubject()` untuk konteks ATP/jenjang.
- **Model TP diperbaiki**: `TujuanPembelajaran` sebelumnya tidak cocok dengan skema fisik (fillable `kd/tp/indicator_code/...`); kini sesuai kolom asli + soft deletes, relasi (mapel, jenjang, tahun ajaran, CP, pembuat), scope, dan accessor kompatibilitas.
- **Migrasi additive `2026_10_09_100000`**: `grade_levels.fase` (data-driven, diisi per jenjang), `tujuan_pembelajaran.capaian_pembelajaran_id`, tabel `capaian_pembelajaran`, `alur_tujuan_pembelajaran`, `alur_tujuan_pembelajaran_items`, `perangkat_pembelajaran` (JSON `desain`).
- **Hub Kurikulum** `/{userId}/kurikulum`: peta per jenjang × fase × mapel dengan JP/minggu (resolver bersama), JP efektif (Pekan Efektif), jumlah CP/TP, status ATP, rombel, dan guru — tanpa tabel/sumber baru.
- **CP** (`/kurikulum/cp`): kelola per mapel + fase (opsi fase dari data jenjang), dikelola tim kurikulum.
- **TP** (`/kurikulum/tp`): diturunkan dari CP (fase/elemen otomatis), kode unik per mapel/jenjang/TA/semester, urutkan naik/turun, kewenangan guru mapel terkait atau tim kurikulum.
- **ATP** (`/kurikulum/atp`): susun TP dengan urutan & alokasi JP, `total_jp` dihitung dari item, dan indikator **pas / kurang / lebih** terhadap JP efektif (JP/minggu × minggu efektif dari Pekan Efektif) beserta progress bar.
- **Perangkat Pembelajaran** (`/kurikulum/perangkat`): dibuat dari ATP (kelas/mapel/guru/TA otomatis ikut ATP) dengan desain **Pembelajaran Mendalam** terstruktur: pertanyaan pemantik, pemahaman bermakna, pengalaman memahami → mengaplikasi → merefleksi, konteks nyata, asesmen formatif & sumatif, diferensiasi, media — bukan sekadar satu field.
- Sidebar: grup *Kurikulum Saya* (guru) & *Kurikulum & Perangkat* (tim kurikulum) di Satuan Pendidikan, Pimpinan, dan Super Admin; field fase di form Tingkat.

## 13. UI Velzon & Akses Kalender untuk Semua User
- Seluruh halaman baru modul Kurikulum (Peta Kurikulum, CP, TP, ATP, ATP detail, Perangkat, Perangkat detail) dan Pekan Efektif ditulis ulang mengikuti pola Velzon: page header + aksi kanan, kartu filter `row g-3`, stat card `card-animate` dengan `avatar-title bg-*-subtle`, tabel `table-hover align-middle` + `thead table-light`, badge `bg-*-subtle`, tombol `btn-soft-*`, modal `fade zoomIn`, empty state, dan alert dismissible.
- Halaman Waka/Pekan Efektif ikut diseragamkan (kartu generate, stat card, filter, tabel, detail pekan menampilkan hari efektif & sumber Kaldik/Manual).
- **Kalender Pendidikan dapat dilihat semua user lewat sidebar**: section *Referensi → Kalender Pendidikan* untuk seluruh pengguna Satuan Pendidikan, ditambahkan untuk role Asrama & UKS, dan menu fallback pengguna tanpa role; role lain sudah memiliki entri kalender masing-masing.

## 14. PROTA → PROSEM → RPM → Cetak PDF (Ekosistem Siap Guru)
- **PROTA** (`/kurikulum/prota`): dibangun dari ATP (TP + alokasi JP) + Pekan Efektif (minggu & JP efektif) — guru tidak mengisi ulang pekan efektif/JP. Snapshot `minggu_efektif`, `jp_per_minggu`, `jp_efektif` + item BAB/TP/alokasi; indikator **Perlu diperbarui** otomatis bila Pekan Efektif/ATP berubah, dengan aksi *Sinkronkan Snapshot* dan *Bangun Ulang dari ATP*.
- **PROSEM** (`/kurikulum/prosem`): distribusi otomatis PROTA/ATP ke **bulan & pekan efektif** dari Kalender (tanpa kalender kedua). Minggu libur dilewati; pekan **ujian** & **kegiatan sekolah** ditandai pada keterangan; JP yang melebihi pekan efektif ditandai; sinkron ulang dari PROTA.
- **RPM terstruktur** (Guru Agama & Guru Umum): `perangkat_pembelajaran.tipe` + JSON `desain` dengan bagian tetap — Informasi Umum, Identifikasi (Umum), Desain Pembelajaran, Pengalaman Belajar (Awal → Memahami → Mengaplikasikan → Merefleksikan → Penutup), Asesmen Formatif & Sumatif. **CP & TP otomatis dari ATP** (tidak diisi ulang); bagian fondasi lama tetap dipertahankan lewat merge.
- **Guru serumpun**: RPM untuk mapel & tahun ajaran sama dapat **dilihat dan dicetak bersama** (badge *Serumpun*); perubahan dibatasi penyusun atau tim kurikulum.
- **Cetak PDF 6 dokumen** (Kaldik, Pekan Efektif, ATP, PROTA, PROSEM, RPM) via Dompdf dengan template A4 resmi: kop identitas satuan pendidikan (nama/alamat/NPSN/logo), identitas guru/mapel/kelas/tahun ajaran, tabel tidak terpotong, nomor halaman, dan area tanda tangan (Kepala Satuan Pendidikan memakai `principal_name`/`principal_nip`).
- Migrasi additive `2026_10_09_140000` (`perangkat_pembelajaran.tipe`, `prota`, `prota_items`, `prosem`, `prosem_items`).

## 15. Pelaksanaan Pembelajaran — Jurnal → Realisasi TP/ATP → Asesmen → Buku Administrasi
- **Jurnal Pembelajaran (wizard2 Buku Administrasi)** kini terhubung ke rencana: `prosem_item_id`, `perangkat_pembelajaran_id`, `tujuan_pembelajaran_id` (migrasi additive `2026_10_09_160000`). TP terisi **otomatis** dari item PROSEM; RPM dapat dipilih; tanda tangan guru kini benar-benar tersimpan.
- Validasi konsistensi: rencana/TP/RPM yang dipilih harus satu mapel + tahun ajaran + semester dengan Buku Administrasi (mencegah salah tempel).
- Halaman Jurnal menampilkan **kartu realisasi** (TP terealisasi X/Y, jumlah pertemuan, status formatif & sumatif) + **saran pekan otomatis** dari tanggal pertemuan via Pekan Efektif; riwayat menampilkan TP, rencana pekan, dan RPM yang digunakan.
- **Halaman baru Realisasi Pembelajaran** (`/kurikulum/realisasi`): progres realisasi TP per Buku Administrasi (kelas & mapel), status asesmen formatif/sumatif, tindak lanjut langsung ke Jurnal/Formatif/Sumatif, plus **cakupan ATP lintas kelas** (tim kurikulum melihat semua kelas; guru hanya bukunya).
- PROSEM show: kolom **Realisasi** (jumlah pertemuan & kelas dari jurnal). ATP show: kolom **Realisasi** per TP.
- Rantai lengkap siap pakai: RPM/PROSEM → Jurnal → realisasi TP/ATP → asesmen formatif/sumatif → Buku Administrasi → Leger → Rapor (modul existing).
- Test: `PelaksanaanPembelajaranTest` 6 test/31 assertion; total **75 test / 544 assertion** lulus.

## 16. Manual Adjustment PROSEM (UI/UX Timeline)
- **Status PROSEM**: `Otomatis` (generator) / `Disesuaikan` (manual) / `Tidak Valid` (terdampak perubahan Kaldik) / `Perlu Diperbarui` — badge konsisten Velzon. Pendukung: `prosem_items.sumber`, `prosem.adjusted_at`, tabel `prosem_item_weeks` (distribusi JP per pekan; backfill data lama).
- **Header PROGRAM SEMESTER**: identitas satuan pendidikan, mapel, kelas/fase, semester, tahun ajaran, guru + aksi **[Sinkron dari PROTA] [Atur Distribusi] [Cetak PDF]**.
- **Summary cards**: Pekan Efektif, JP Tersedia, JP Terencana, dan Status.
- **Tabel distribusi**: No · CP/TP/Materi · JP · Distribusi (bulan–pekan + rincian JP per pekan bila manual) · Realisasi · Status · Aksi `[Atur]` / `[Atur Ulang]` / `[Kembalikan otomatis]`.
- **Modal "Atur Distribusi Pembelajaran"**: identitas TP + total alokasi, baris pekan (select dikelompokkan per bulan, pekan libur **disabled**), tombol *+ Tambah Pekan*, referensi ketersediaan pekan per bulan (Efektif/Libur/Ujian/Kegiatan dari Kaldik), validasi realtime (kurang/lebih/duplikat/pekan tidak tersedia), dan konfirmasi dua langkah. Tanpa drag & drop.
- **Endpoint**: `PUT /kurikulum/prosem/{id}/items/{itemId}/distribusi` dan `POST .../reset`; validasi server: hanya pekan efektif semester berjalan dan total JP wajib sama dengan alokasi TP (sumber tetap PROTA/ATP).
- **Perubahan kalender**: penyesuaian manual **tidak dihapus** — item ditandai *Tidak Valid*, alert menampilkan jumlah penyesuaian terdampak + **[Tinjau Perubahan] [Sinkronkan]**.
- **Sinkron aman**: distribusi otomatis diperbarui, penyesuaian manual dipertahankan, item tidak dihapus/dibuat ulang — relasi **Jurnal → realisasi TP/ATP tetap utuh**.
- Test: `ProsemAdjustmentTest` 9 test/51 assertion; total **84 test / 595 assertion** lulus + smoke MySQL (auto 12/13 pekan → manual → invalid → sync → reset).

## 17. Perbaikan Scope PROSEM & Konsistensi UI Modul Kurikulum
- **Fix bug** `Call to undefined method Illuminate\Database\Eloquent\Builder::bySchool()` pada halaman **PROSEM** (scope `bySchool`/`byAcademicYear`/`bySemester` belum ada di model `Prosem`) — ditambahkan; test render index PROTA/PROSEM/Realisasi ikut ditambahkan agar tidak terulang.
- **Konsistensi UI**: halaman modul Kurikulum (Peta Kurikulum, CP, TP, ATP, PROTA, PROSEM, Perangkat/RPM, Realisasi) dan Pekan Efektif disamakan dengan pola `gtk/index.blade.php`:
  - kartu statistik `card card-animate` (avatar-sm + label 11px + nilai `h3 fw-bold` + sub badge/progress),
  - `card-header border-bottom-dashed` (judul + badge kiri, filter inline + tombol aksi kanan),
  - baris **Filter Cepat** `.filter-badge` (semester/fase) di `card-header py-2 bg-light`,
  - tabel `table table-hover table-freeze` + `thead table-light`.
- Style bersama: `resources/views/kurikulum/_styles.blade.php` (filter-badge & stat-label) dipakai oleh index maupun halaman detail; detail page memakai `border-bottom-dashed` yang sama.
- Konvensi ini menjadi acuan pembuatan halaman modul berikutnya.

## 18. Bank Soal Terpusat Lintas Satuan Pendidikan
- **Repository terpusat**: `bank_soal` + `is_central`, `jenjang`, `grade_level_id`, `academic_year_id`, `semester`; scope akses diperluas sehingga bank **public_pool/central** dapat dipakai lintas satuan pendidikan. Serumpun = mapel + jenjang/kelas + tahun ajaran + semester — **bukan school_id**.
- **Halaman `/bank-soal-terpusat`**: statistik *Soal Saya / Soal Serumpun / Terverifikasi / Tahun Sebelumnya*, filter lengkap (mapel, kelas, fase, TA, semester, bentuk, kesulitan, status, satuan pembuat, guru), quick scope, dan aksi **Gunakan sebagai Dasar** → membuat turunan baru (`derived_from_soal_id` + `soal_clone_log` tipe `adapt`) tanpa mengubah soal asli.
- **Soal terstruktur**: kolom baru `materi` & `pembahasan`; options + kunci tetap terstruktur; hash & shingles ternormalisasi (`ContentHashEngine`).
- **Similarity berjenjang** lintas bank/tahun: Level 1 Exact (`content_hash`), Level 2 Text (shingles Jaccard ≥ 70%), Level 3 Semantic (token-set ≥ 60% + penguat kesamaan angka) — disimpan di `soal_similarities` + ringkasan di soal; **comparison view** side-by-side via endpoint JSON. Similarity = warning, bukan penolakan otomatis.
- **Review guru serumpun lintas satuan**: `review_assignments` (generik Soal & Paket) — reviewer otomatis dari guru mapel serumpun (cocok `subject_id`/nama mapel, maks 4, lintas sekolah), **semua harus menyetujui**; satu permintaan perbaikan → *Perlu Perbaikan*; ajukan ulang mereset seluruh approval; perubahan soal setelah approval membatalkan validasi lama. Inbox reviewer + halaman tinjau (metadata, CP/TP, kunci, pembahasan, similarity, comparison). Audit trail via `audit_logs`.
- **Paket soal**: quality gate (duplikasi internal + kemiripan historis, tersimpan di `similarity_summary`), approval seluruh reviewer, publish → final; **hanya soal approved** yang boleh menjadi bagian paket final; **distribusi via sistem** ke Tata Usaha/Waka/Kurikulum/Koordinator/KSP (`paket_soal_distributions`); **TU print jobs** (`paket_soal_print_jobs`: jumlah cetak, status produksi, tanggal, petugas) — TU tidak dapat mengubah isi akademik.
- **Integrasi Buku Administrasi**: wizard Sumatif memilih **paket final** (approved + dipublikasikan) → tersimpan pada `admin_nilai_sumatif.paket_soal_id` (validasi server menolak paket belum final).
- Perbaikan pendukung: signature `PaketSoalController` mengikuti parameter route posisional (`{userId}/{paketUuid}`); stub `kisi_kisi_soal` untuk SQLite dipindah ke `tests/TestCase.php`.
- Migrasi additive `2026_10_09_200000`; route baru `bank-soal-terpusat`, `review-soal`, `tu-paket-soal`; menu sidebar (guru, koor mapel, TU).
- Test: `BankSoalTerpusatTest` 7 test/69 assertion; total **91 test / 672 assertion** lulus + smoke MySQL (similarity semantic 81,67%).
- **Sidebar**: satu seksi *Bank Soal Terpusat* (Repository Soal + Review Soal Serumpun, serta TU — Cetak Paket Final khusus TU) untuk Guru, Koor Mapel, Kurikulum, Waka/KSP, dan TU pada sidebar Satuan Pendidikan (tanpa duplikasi); ditambahkan juga pada sidebar **Waka** (grup Pelaksanaan Sumatif), **Pimpinan**, dan **Super Admin**.
- **Akses semua soal**: **Waka, Kurikulum, TU, dan KSP** (Kepala/Wakil satuan pendidikan) melihat **seluruh repositori soal** via `KurikulumAccess::canAccessAllBankSoal`; role lain tetap terbatas pada bank accessible + soal miliknya. TU tetap tidak dapat mengubah isi akademik soal.

## Testing
- `tests/Feature/JadwalPergantianJamTest.php` — generator, konflik, QR end-to-end, jam pelajaran, rekap.
- `tests/Feature/SumatifHarianDinamisTest.php` — SH dinamis, unifikasi kalkulasi, Leger/Rapor STS & SAS, KKTP, catatan wali.
- `tests/Feature/TeacherQrScanControllerTest.php` — diperbarui agar berjalan di SQLite (snapshot permission + `Event::fake` terarah).
- `tests/Feature/KaldikPekanEfektifTest.php` — pembagian semester, generate pekan efektif dari kalender (minggu/hari/libur/ujian), alokasi JP efektif, policy pengelolaan (Super Admin/Pimpinan vs Satuan Pendidikan/Guru), halaman pekan efektif untuk Guru & Satuan Pendidikan.
- `tests/Feature/KurikulumPembelajaranTest.php` — rantai Kalender → Pekan Efektif → JP Efektif → CP → TP → ATP → Perangkat: fallback JP berjenjang, hub kurikulum, akses CP/TP (tim kurikulum vs guru mapel), urutan TP, indikator alokasi ATP (kurang/pas/lebih), desain Pembelajaran Mendalam perangkat, render semua halaman.
- `tests/Feature/ProtaProsemRpmTest.php` — PROTA dari ATP+Pekan Efektif (snapshot & indikator perlu diperbarui + sinkron), PROSEM distribusi pekan/bulan (libur dilewati, pekan ujian ditandai), RPM terstruktur umum vs agama, akses serumpun (lihat vs ubah), dan **cetak PDF 6 dokumen** (content-type `application/pdf` + magic bytes `%PDF`).
- Smoke MySQL untuk setiap modul dilakukan dengan data sementara yang selalu dibersihkan.
