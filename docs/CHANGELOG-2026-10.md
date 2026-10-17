# Changelog — Sesi Pengembangan Oktober 2026

Ringkasan seluruh modul yang dikerjakan (belum termasuk modul sebelumnya yang sudah di-commit di `main`).

## 0. LJK Smart Scanner — Pemindai Lembar Jawaban Berbasis Kamera HP (15 Okt 2026)

Fitur baru end-to-end: **Paket Soal final → LJK siap cetak A4 (kotak tanda silang) → pemindaian kamera HP → OMR deterministik → verifikasi guru → sinkron ke modul nilai resmi**.

Backend (Laravel):
- Migrasi aditif: `ljk_templates` (template ber-versi + geometri mm), `ljk_exams` (referensi paket + versi + digest naskah), `ljk_participants` (pemetaan urutan/opsi aktual), `ljk_sheets` (kode unik + QR HMAC + data strip), `ljk_scans` (gambar privat + metrik kualitas), `ljk_scan_answers` (hasil baca awal + evidence), `ljk_answer_corrections` (log koreksi append-only), `ljk_sync_logs` (idempotensi), `admin_nilai_sumatif.ljk_exam_id` (proventa nullable).
- Mesin OMR murni GD (`app/Services/Ljk/Omr/`): gerbang kualitas, deteksi penanda adaptif+filter kontras cincin, homografi perspektif, analisis fitur tanda silang (dua diagonal, sudut, pusat, run goresan, tinta biru), klasifikasi `terbaca/kosong/ganda/meragukan/tidak_dapat_diproses/manual`.
- Layanan: exam dari paket final (digest + peserta + lembar), QR GD (`LjkQrRenderer`, tanpa imagick), pemindaian & anti-duplikat, penilaian memakai kunci **snapshot versi paket yang benar**, koreksi guru ber-audit, sinkron lewat `SumatifHarianService` (bukan jalur nilai alternatif).
- Web guru: menu *Bank Soal & Asesmen → LJK Smart Scanner* (index, terbitkan, detail, **PDF siap cetak**, preview, **halaman kamera HP**, hasil & verifikasi, koreksi per nomor, finalisasi, sinkron). Seluruh halaman memakai konvensi UI `gtk/index.blade.php` (stat card, filter-badge, table-freeze, pagination bersama, tombol soft) dengan gaya bersama `resources/views/ljk/_styles.blade.php`.
- API mobile Sanctum: `GET ljk/exams`, `GET ljk/exams/{exam}`, `POST ljk/exams/{exam}/scans`, `GET ljk/scans/{scan}`, `PATCH ljk/scans/{scan}/answers/{answer}`; ability `ljk.*`; `/auth/login|me` menambah `role` + `capabilities` (aditif).
- Kalibrasi: `ljk:generate-dataset` (dataset sintetis berlabel) & `ljk:calibrate` (exact-sheet, per-question, false acceptance, review rate, waktu; `--tune`).
- Perbaikan insidental: guard `hasSession()` di `BindOrganizationContext`; `ljk_exam_id` masuk `$fillable` `NilaiSumatif`.
- Hasil ukur aktual (60 lembar × 20 soal, sintetis): exact-sheet **98,33 %**, per-question **99,92 %**, false acceptance **0,00 %**, review rate **4,83 %**, 0 lembar gagal, ±480 ms/lembar. Target ≥99 % exact-sheet belum tercapai dan dicatat apa adanya (kasus tersisa diarahkan ke pemeriksaan guru).
- Dokumentasi: `docs/features/LJK-SMART-SCANNER.md`.
- Revisi 2 (UI & lembar): kop lembaga pada lembar (gambar `kop_path` atau kop teks + logo), tata letak **2 kolom sejak awal** (kiri 1–10, kanan 11–20), **Master Fotokopi tanpa nama** (`?master=1&copies=N`, tanpa QR/data strip) dengan **Mode Fotokopi** (urutan seragam) saat menerbitkan, **nilai otomatis masuk** ke kolom resmi (STS/SAS/sumatif murni) begitu lembar bersih/dikoreksi (`ljk_sync_logs.action = auto`), halaman **Lihat Soal** (`/{userId}/ljk/{ljkUuid}/soal`, kunci mengikuti kebijakan `canViewAnswerKeys`), serta perbaikan bug **"Missing required parameter paketUuid"** pada view evaluasi paket soal (semua `route('user.paket-soal.*')` kini menyertakan `userId`).
- Revisi 3 (lembar soal & font): **Lembar Soal PDF** (`/{userId}/ljk/{ljkUuid}/soal/pdf`) mengikuti format naskah madrasah — judul jenis ujian + tahun ajaran, tabel identitas (Mapel/Hari-Tanggal/Kelas/Waktu/Guru Pengampu/Nama Peserta Didik bergaris), **PENANAMAN KARAKTER** 3 butir, instruksi PG, butir soal **tanpa bobot/poin/TP/kunci/pembahasan** dengan **opsi mendatar** yang turun baris otomatis bila tidak muat; kolom **Waktu Mulai/Selesai** pada ujian (tampil sebagai `07.30 – 09.00`); **font Cambria untuk semua PDF LJK** via `LjkPdfFonts` (fallback Caladea → DejaVu Serif, cache metrik di `storage/app/dompdf/fonts`, status font tampil di halaman Lihat Soal).
- Revisi 4 (lembar soal lanjutan): **opsi jawaban tersusun kolom sejajar** (A/B/C/D selalu pada posisi sama antar soal, `table-layout: fixed`), **fitur wacana/soal cerita** (`wacana_soal` + `soal.wacana_id`, dipasang dari form Bank Soal, dicetak sekali per kelompok dengan keterangan *"Wacana untuk soal nomor X–Y"*, legenda rentang di lembar jawaban, ikut dibekukan ke snapshot paket), **dukungan teks Arab** (shaping bentuk presentasi + susunan RTL deterministik di PHP — Dompdf tanpa mesin bidi, kelas `.ar` memakai DejaVu Sans bawaan atau Amiri/Scheherazade dari `storage/app/fonts/`, instruksi Arab otomatis), dan **opsi unduh PDF** `?download=1` untuk lembar soal maupun lembar jawaban (tombol *Unduh PDF* di UI). Seeder demo kini memuat contoh wacana Inggris (soal 11–13), wacana Arab + 3 soal Arab (21–23): total 23 butir.
- Revisi 5 (setelan cetak lembar soal): font **Cambria 12 pt**, **spasi antar baris single (1.0)** dengan **jarak antar soal 1.15**, **margin halaman 0.2"/0.1"/0.59"/0.63"** (diubah otomatis ke mm), **identitas berbentuk tabel maksimal 3 baris** (2 pasang label/nilai per baris, *Nama Peserta Didik* bergaris), **pemilihan kertas otomatis A4 → F4 (215×330 mm)** yang hanya dipakai bila menghemat halaman (`LjkSoalPdfRenderer`, header `X-LJK-Paper`), serta **huruf pilihan Arab ا، ب، ج، د، هـ** untuk soal berbahasa Arab (otomatis terdeteksi) di lembar soal dan label kotak lembar jawaban. Nomor halaman dipindah ke pita margin bawah agar tidak menimpa baris terakhir.
- Revisi 6 (perbaikan cetak): **margin `@page` kini benar-benar diterapkan** (akar masalah: reset `* { margin: 0 }` menimpa margin halaman di Dompdf — reset dibatasi pada elemen isi; hasil terukur kiri 14,7 mm · kanan 16 mm · atas 6,3 mm), **ukuran font dikompensasi** (`font_size_fallback_scale = 0.90`) sehingga 12 pt Cambria setara dengan ±10,8 pt font pengganti (DejaVu Serif tampil lebih besar), dan **huruf pilihan A/B/C/D tidak lagi bold**. Karena lebih padat, halaman 1 kini memuat 16 soal (sebelumnya 14).
- Seeder contoh: `database/seeders/LjkLaluSopianSeeder.php` (idempoten) — menyiapkan akun GTK **Lalu Sopian Harianto** + buku nilai **Bahasa Inggris · kelas 1B**, Bank Soal + 20 soal PG, kisi-kisi, **Paket Soal final ber-snapshot**, dan ujian LJK contoh berikut 22 lembar siap cetak. Jalankan: `php artisan db:seed --class=LjkLaluSopianSeeder`.

Mobile (repo `Alim-Mobile`): fitur `src/features/ljk/` + route `/ljk` (daftar ujian → peserta → **kamera** → hasil & koreksi) memakai `expo-image-picker` yang sudah terpasang (tanpa dependensi native baru), service/hook React Query, dan menu sidebar guru.

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
- **Input soal**: form input langsung di sistem tetap melalui **Bank Soal → Tambah Soal** (`/bank-soal/{bank}/soal/create`) dan kini memuat field terstruktur **Materi/Topik** serta **Pembahasan**; ditambahkan pintasan **Buat Soal** di halaman Repository dan menu **Bank Soal & Input Soal** di sidebar (Satuan Pendidikan & Waka).

## 19. Penyempurnaan Similarity → Reuse → Perbandingan → Workflow (Bank Soal Terpusat)
- **Kebijakan similarity diperbaiki (migrasi `2026_10_09_210000`)**: unique index `soal.content_hash` **dilepas** — exact duplicate kini dapat tersimpan dan menjadi **indikator** yang diperiksa, bukan kegagalan input/penolakan otomatis. Ditambah kolom pencatatan keputusan `similarity_ack_note` / `similarity_ack_by` / `similarity_ack_at`.
- **Algoritma yang benar-benar diterapkan (berbasis teks terukur — bukan AI/embedding)**:
  1. **Exact Duplicate** — `content_hash` SHA-256 dari pertanyaan + teks kunci **setelah normalisasi** (strip tag/HTML entity, collapse spasi, buang tanda baca, lowercase) → skor 100%.
  2. **Fuzzy Text Similarity** — Jaccard atas shingle karakter 3-gram dari teks ternormalisasi; ambang ≥ 70%.
  3. **Token Overlap (leksikal)** — Jaccard bag-of-words kata bermakna (stopwords dibuang) + penguat bila angka-angka soal identik; ambang ≥ 60%. Label level memakai istilah jujur "Kata Kunci Mirip (token overlap)", bukan "semantic".
- **Prefilter kandidat efisien**: kandidat lintas bank/satuan dibatasi mapel serumpun (id + nama, karena master mapel per sekolah berbeda baris) lalu **difilter via index** — `content_hash` sama, `tp_id`, `materi`, atau `whereJsonContains(shingles_hash)` (MySQL `json_contains`, SQLite `json_each`) — `limit 200`, bukan scan seluruh tabel; hasil akhir top-N (default 5).
- **Ringkasan pemeriksaan** tersedia bagi penyusun & reviewer: status, jumlah kandidat, skor tertinggi, tahun ajaran/semester/jenis asesmen/mapel/kelas/materi, tombol **Bandingkan**; kartu pemeriksaan juga tampil di halaman edit soal (penyusun) dan halaman tinjau (reviewer) dengan status "Perlu ditinjau reviewer".
- **Pengecualian tercatat**: saat mengajukan review, penyusun dapat mengisi **alasan tetap melanjutkan meski ada kemiripan** (opsional, tidak memblokir) — tersimpan di soal dan tampil ke reviewer.
- **Comparison view** (`GET /bank-soal-terpusat/{soal}/compare/{compared}`): dua soal berdampingan (pertanyaan, opsi, kunci, pembahasan, materi, TA/semester/jenis asesmen, pembuat, status validasi, skor + metode). **Kunci & pembahasan hanya untuk pengguna berwenang** — penyusun salah satu soal, reviewer yang ditugaskan, atau Waka/Kurikulum/TU/KSP; pengguna lain menerima `solution_visible=false` dengan kunci/pembahasan disamarkan.
- **Repository soal historis**: filter form lengkap (mapel, kelas, jenjang/fase, **materi**, TA, semester, **jenis asesmen**, **bentuk soal**, kesulitan, status validasi, satuan pendidikan untuk pengguna berwenang) + **detail soal** (modal, kunci mengikuti hak akses) + badge jumlah kemiripan + aksi **Gunakan sebagai Dasar**. Turunan: relasi `derived_from_soal_id` + `soal_clone_log`, soal asli tidak berubah, **similarity check dijalankan saat reuse**, dan **wajib review ulang**.
- **Workflow**: Input → Similarity Check → Draft hasil → Review serumpun → Perbaikan → Semua approve → Approved. Perubahan soal setelah approval **membatalkan approval, similarity, dan pengecualian lama** (dihitung ulang saat diajukan kembali).
- **Quality gate paket diperluas**: selain duplikasi internal & kemiripan historis, kini memeriksa **jumlah soal belum approved**, **kunci jawaban hilang** (PG/B-S/Menjodohkan tanpa opsi benar), dan **metadata wajib** (materi/pertanyaan/TP) — hasilnya bahan keputusan reviewer, bukan pengganti approval; paket final tetap menolak soal belum approved.
- Perbaikan bug ikutan: akses `tags` tanpa default di `SoalController` (input tanpa tags → 500), tombol "Submit untuk Review" di halaman edit yang menunjuk route tidak ada & men-submit form PUT, serta direktif Blade `@json(route(...))` multi-placeholder yang gagal kompilasi di halaman perbandingan.
- Test: `BankSoalTerpusatTest` **22 test / 188 assertion** (exact, normalisasi kapitalisasi/spasi/tanda baca, fuzzy, token overlap, historis lintas TA, filter repository, reuse + review ulang, pembatalan approval saat edit, tampilan ke reviewer, proteksi kunci/pembahasan, quality gate, penolakan paket belum approved, efisiensi prefilter) + regresi modul (Kaldik/Kurikulum/PROTA-PROSEM-RPM/Prosem Adjustment/Pelaksanaan/Sumatif) **72 test / 554 assertion** lulus; `view:cache` sukses; smoke MySQL (exact 100%, prefilter `json_contains`, kolom & index terverifikasi, rollback bersih).

## 20. UI Area Kurikulum Diseragamkan dengan Pola gtk/index
- **7 halaman master area Kurikulum** kini mengikuti pola `gtk/index.blade.php`: **Tahun Ajaran**, **Mata Pelajaran**, **Data Kelas**, **Rombel**, **Plotting Guru Mengajar**, **Jadwal Pelajaran (jadwal-kbm)**, dan **Jam Pelajaran**.
- Pola yang diterapkan seragam: kartu statistik `card card-animate` (avatar-sm + label `stat-label` + nilai `h3 fw-bold ff-secondary` + sub-badge), `card-header border-bottom-dashed` (judul + badge total + filter inline + tombol aksi), baris **Filter Cepat** `.filter-badge` di `card-header py-2 bg-light`, tabel `table table-hover align-middle table-freeze` dengan `thead table-light`, dan pagination.
- **Filter cepat baru**: Tahun Ajaran (Semua/Aktif/Nonaktif/Ganjil/Genap), Mata Pelajaran (Aktif/Nonaktif + Nasional/Muatan Lokal), Data Kelas (Aktif/Nonaktif + filter `is_active` baru), Rombel (Semester Aktif / Semua Tahun Ajaran via `semua_ta`), Jadwal Pelajaran (Semua/Sudah/Belum Terjadwal via `status`), Plotting Guru (status + peran), Jam Pelajaran (stat baru **Slot KBM**).
- **Statistik additive di controller** (tanpa query baru yang berat): total/aktif/nonaktif per halaman, total JP mapel & plotting, total santri/wali/kapasitas rombel, rombel terjadwal/total slot/guru terlibat pada jadwal, slot KBM vs istirahat pada jam pelajaran.
- Style bersama `resources/views/kurikulum/_styles.blade.php` ditambah animasi hover `card-animate` + sticky `thead` `table-freeze` (menyamai gtk), sehingga seluruh halaman yang sudah memakainya ikut konsisten.
- Perbaikan bug ikutan: direktif Blade `@else`/`@endif` yang menempel tanpa spasi (`Anda@else`) tidak terkompilasi dan menyebabkan parse error di halaman Data Kelas & Rombel.
- Test: `KurikulumMasterUiTest` **7 test / 42 assertion** (render + filter tiap halaman); regresi modul (Jadwal, Sumatif, Kaldik, Kurikulum, PROTA/PROSEM/RPM, Adjustment, Pelaksanaan, Bank Soal) **87 test / 761 assertion** lulus; `view:cache` sukses.

## 21. UI Kurikulum — Form & Detail Diseragamkan (Lanjutan)
- **Halaman form & detail 7 modul** kini konsisten dengan pola gtk/kurikulum: breadcrumb `Kurikulum`, `card-header border-bottom-dashed`, kolom form (8) + kartu **Panduan/Quick Actions** (4), `form-select` konsisten, tombol aksi `btn-success`/`btn-light`, dan `@include('kurikulum._styles')`:
  - **Tahun Ajaran** — create, edit, show (kartu ringkasan status/semester/durasi/pendaftaran + zona berbahaya).
  - **Mata Pelajaran** — create, edit, show.
  - **Data Kelas** — create, edit, show (stat cards gtk + sticky table-freeze).
  - **Rombel** — create, edit, show, serta form **Mapel Rombel** (create/edit).
  - **Plotting Guru Mengajar** — create, edit, show, dan **Matriks Pembagian Tugas (SK)**.
  - **Jadwal Pelajaran** — generate, show, edit manual, dan jadwal per guru.
- **Bug laten yang ditemukan & diperbaiki** (halaman sebelumnya tidak pernah bisa dibuka):
  1. `AcademicYearController` `show/edit/update/destroy/toggleActive` tidak menerima `$userId` sehingga parameter route bergeser secara posisional → `ModelNotFoundException`/404. Kini semua menerima `(string $userId, string $id)`.
  2. `teaching-assignments/show` memanggil `route('user.gtk.show', ['id' => ...])` padahal parameter route bernama `{uuid}` → `UrlGenerationException`; diperbaiki menjadi `'uuid'`.
  3. `TeachingAssignmentController::editMatrix` memakai `OtherTeacherTask` tanpa `use App\Models\OtherTeacherTask` → 500 pada halaman Matriks SK.
- Test: `KurikulumMasterUiTest` diperluas menjadi **14 test / 84 assertion** (render create/edit/show tiap modul + matriks); regresi modul (Jadwal, Sumatif, Kaldik, Kurikulum, PROTA/PROSEM/RPM, Adjustment, Pelaksanaan, Bank Soal) **94 test / 808 assertion** lulus; `view:cache` sukses.

## 22. Peserta Didik — Fase 1: Perbaikan P0 (Fungsional & Integritas Data)
- **Alumni otomatis dari jalur Lulus**: listener baru `CreateAlumniOnGraduation` (terdaftar untuk `StudentGraduated` + `StudentMutatedOut` tipe graduation) menulis baris `alumni` (tahun, tanggal, no. ijazah dari mutasi) — tidak lagi bergantung sync manual `?sync=1`; statistik alumni langsung akurat.
- **Lifecycle status manual hidup**: `StudentController::update` mendeteksi perubahan status dan men-dispatch `StudentStatusChanged` (sebelumnya event tidak pernah dipicu); listener `HandleManualStudentStatusUpdate` diresmikan di `EventServiceProvider` sehingga ubah status ke `graduate/dropped/transfer_*` menutup rombel, membuat alumni, menulis audit, dan mengirim notifikasi wali. Dispatch event lifecycle diperbaiki ke **parameter posisional** (trait `Events\Dispatchable` versi ini memakai `func_get_args()` dan menolak named arguments — sebelumnya semua pemanggilan event lifecycle berpotensi fatal).
- **Notifikasi wali hidup kembali**: `LifecycleMessage::forEvent()` ditambahkan + properti turunan `priority/title/body/actionUrl/actionText` yang dibutuhkan `SendLifecycleNotificationJob` (sebelumnya job selalu gagal "Undefined property priority").
- **Pindahkan Santri (student-move) berfungsi**: sebelumnya selalu melanggar unique `(student_id, academic_year_id)` karena menonaktifkan lalu membuat baris baru. Kini **memindahkan baris history yang sama**, memakai tahun ajaran rombel asal (bukan AY aktif global), menghitung nomor absen dari baris aktif, memvalidasi seluruh santri harus benar-benar aktif di rombel asal (gagal total, bukan skip diam-diam), dan menulis audit `student.moved_rombel`.
- **Mutasi Masuk untuk santri terdaftar tidak menduplikasi**: form `create` membaca `?student_id=` (hidden + banner prefill), dan approve memperbarui data santri yang ada tanpa menimpa kolom wajib dengan null (fix `students.gender` NOT NULL). Approve kedua controller kini **transaksional + state guard** (tolak proses ganda/approve setelah reject) dan seluruh aksi (show/submit/approve/reject/destroy/print) **dibatasi satuan pendidikan** (scope `school_id`).
- **Enum status `transfer` usang diganti**: validasi store/update + form create/edit memakai `transfer_in`/`transfer_out`; migrasi `2026_06_18_020000` kini juga menangani **SQLite** (rebuild CHECK constraint) sehingga nilai pindahan valid di semua driver; `BulkPromotionController` memakai jalur lifecycle resmi (`StudentMutatedOut`/`StudentGraduated`) dan kolom usang `academic_years.school_id` dihapus dari query.
- **Keamanan & scope**: `StudentController` show/edit/update/destroy/findStudent dan `StudentMahromController` global index/show kini memvalidasi `school_id` terhadap konteks sekolah (IDOR lintas sekolah ditutup, 404/403).
- **Foto santri**: `photo_path`/`alumni_year` masuk `$fillable` (sebelumnya upload sukses tapi nilai terbuang diam-diam), validasi file `image|mimes|max:2048`, dan route `students.photo.upload/destroy` didaftarkan.
- **Route orphan dibereskan**: `students.wali.edit/update` didaftarkan (lengkap dengan `resolveStudent` yang hilang di `WaliSantriController`), link rusak `student-class-histories.create` diganti, group route `mutations-in` duplikat dihapus (menyisakan satu + `find-student` + `hijri-convert`), dan view orphan `alumni/create.blade.php` (memanggil route tidak ada) dihapus.
- **Export & verifikasi alumni**: export PDF memakai Dompdf + view `alumni/export-pdf` (sebelumnya `\PDF` alias tak ada → 500), tombol verifikasi kini form POST (sebelumnya GET → 405), edit tracer tidak lagi menurunkan status `verified`, sinkronisasi dijalankan sebelum query dengan notifikasi jumlah data baru, urutan & `per_page` dibatasi.
- **Wizard Naik Kelas bisa dipakai**: URL AJAX `/api/...` tanpa prefix `{userId}` (404, tombol simpan tidak pernah aktif) diganti `route()` ber-`userId`; promosi `cancelled` tidak dapat dieksekusi; aksi detail/cancel/destroy dibatasi sekolah.
- **Mutasi Out**: relasi `Student::studyGroup` yang tidak ada diperbaiki (`currentClassHistory.studyGroup`), redirect/breadcrumb/label/aksi/print mengikuti jenis mutasi (Lulus / Drop Out / Mutasi Keluar), judul & nama file PDF per jenis, `head_nip` → `head_nupy`.
- Test: `PesertaDidikP0Test` **13 test / 44 assertion**; regresi modul sehat **108 test / 854 assertion** lulus; `view:cache` sukses.

## 23. Peserta Didik & Menu Akademik — Fase 2: UI Seragam Pola gtk
- **Peserta Didik**:
  - **Mutasi (4 index)**: Mutasi Masuk/Keluar/Lulus/Drop Out — stat cards Total/Draft/Diajukan/Disetujui/Ditolak, Filter Cepat status (+ jenis untuk Mutasi Keluar), tabel `table-freeze`, header `border-bottom-dashed`.
  - **Pindahkan Santri**: stat Total Santri Rombel Asal, Rombel Tujuan, Kapasitas, Slot Tersisa.
  - **Naik Kelas**: index (stat Total/Draft/Selesai/Dibatalkan + filter `status` baru), create (header/kartu ringkasan konsisten tanpa mengubah logika wizard), show (4 stat cards).
  - **Alumni**: index (+kartu Terverifikasi, Filter Cepat tracer, `table-freeze`), show, statistics (kartu gtk + tabel freeze), edit (`@error` per field).
  - **Mahrom**: index per-santri & global (stat cards, Filter Cepat status/hubungan).
  - **Data Santri (index)**: `table-freeze` + baris Filter Cepat (Aktif / Pindahan Masuk-Keluar / Lulus / L-P / Penerima PIP); **breadcrumb 28 halaman diseragamkan ke "Peserta Didik"**; form create/edit/import diberi `kurikulum._styles` + `border-bottom-dashed`.
- **Menu Akademik (Kepala TU & TU)**:
  - **Tugas Tambahan**: stat cards + Filter Cepat status; **fix bug filter tahun ajaran** yang saling menimpa; **fix form tambah** kini mengirim `academic_year_id` (validasi wajib — store sebelumnya selalu gagal).
  - **Prestasi Siswa**: stat cards (Juara 1/2/3, terverifikasi, tahun ini) + Filter Cepat; **fix bug** query quran/hadits selalu kosong (dua `where achievement_type` bertentangan).
  - **Absensi GTK**: stat Total/Hadir/Terlambat/Tidak Hadir + `table-freeze`.
  - **Absensi Harian**: stat Total/Hadir/Izin-Sakit/Alpa + Filter Cepat Hari Ini/Kemarin, Ganjil/Genap, Sudah/Belum tercatat.
  - **Kisi-kisi**: UI index baru + stat cards; **fix bug route** `user.kisi-kisi.*` → `user.kisi-kisi-soal.*` (halaman sebelumnya gagal render `RouteNotFoundException`) termasuk signature controller `show/edit/update/destroy` yang menerima `$userId`.
  - **Bank Soal / Paket Soal**: stat cards + Filter Cepat + `table-freeze` (Bank Soal kini mendukung filter tahun ajaran).
  - **Data Nilai & Rapor**: stat cards + `table-freeze`.
  - **QR Guru**: stat cards + Filter Cepat status QR.
  - **Ekstrakurikuler**: grid card → tabel `table-freeze` + stat Total/Aktif/Pembina/Peserta.
- Test: `PesertaDidikP0Test` diperluas menjadi **21 test / 95 assertion** (termasuk smoke UI Fase 2); regresi modul sehat **115 test / 898 assertion** lulus; `view:cache` sukses.

## 24. Peserta Didik — Fase 3: Import & Kualitas Data
- **Import santri transaksional & aman** (`StudentImport`):
  - Setiap baris dibungkus `DB::transaction` (siswa + riwayat rombel atomik — tidak ada data parsial).
  - Validasi kolom wajib sebelum insert: NISN wajib, jenis kelamin harus L/P (kolom DB NOT NULL) → pesan error per baris yang jelas, bukan 500.
  - **Kapasitas rombel diperiksa SEBELUM siswa dibuat** — baris dilewati dengan pesan `penuh`, tidak lagi membuat santri tanpa kelas.
  - Duplikat dicek untuk **NISN/NIK (global) dan NIS (per sekolah)**; riwayat rombel memakai `updateOrCreate` kunci `(student_id, academic_year_id)` + nomor absen berurutan + dispatch `StudentAssignedToRombel`.
  - Pesan error ke pengguna bersifat generik (detail SQL hanya ke log) — perilaku lama "Data truncated dianggap sukses" dihapus.
- **`importProcess` aman**: nama file temp unik (`temp_import_<uuid>.xlsx`) dan **selalu dihapus** via `finally`; pesan gagal tidak lagi membocorkan `$e->getMessage()`; `importForm` mengirim `$studyGroups` sebagai Collection (fix fatal `isNotEmpty()`), view import memperbaiki klaim "65 kolom" dan `ReferenceError submitBtn` pada peringatan kapasitas.
- **Validasi NIS unik per sekolah** (`Rule::unique` + `ignore` saat update) — input NIS duplikat kini ditolak dengan pesan validasi, bukan QueryException 500.
- **Privasi NIK/No. KK**: modal password mati dihapus; NIK & No. KK pada modal identitas **di-mask** (Str::mask) dan tombol salin dihapus (nilai asli tidak lagi ada di DOM); NIK mahrom di-mask pada halaman show & show-global.
- **Batas mahrom terpusat**: `config/alim.php` (`max_mahrom`, bisa di-override env `ALIM_MAX_MAHROM`) + `StudentMahrom::MAX_PER_STUDENT`; controller memakai nilai config dengan pesan dinamis.
- **Kapasitas rombel pada Naik Kelas**: `StudentPromotionController::execute` (promote & retain) dan `BulkPromotionController::promote` memvalidasi kapasitas tujuan **sebelum menutup histori lama** — siswa gagal diproses tidak kehilangan rombel (tidak orphan); detail ditandai gagal dengan alasan.
- **Filter lanjutan Data Santri lengkap**: modal "Filter Lanjutan" kini memuat agama, kabupaten/kota (kode), tingkat masuk, penerima PIP, dan status (transfer_in/transfer_out/graduate/dropped) — sebelumnya parameter sudah didukung controller tetapi tidak punya UI.
- Test: `StudentImportTest` **5 test** (import valid + nomor absen, kolom wajib, duplikat NISN/NIS, rombel penuh) dan `PesertaDidikP0Test` bertambah (validasi NIS store/update, kapasitas bulk promotion) → total suite sehat **123 test / 931 assertion** lulus; `view:cache` sukses.

## 25. Peserta Didik — Fase 4: Integritas Lanjutan (State Machine, Bulk, Notifikasi, Test Bersih)
- **Audit state machine mutasi**: migrasi `2026_10_09_230000` menambah `submitted_at`, `rejected_by`, `rejected_at` pada `student_mutations_in/out`; controller mengisi kolom tersebut (submit/approve/reject) dan model fillable/casts diselaraskan — jejak workflow draft → submitted → approved/rejected kini lengkap.
- **Aksi massal**:
  - Endpoint `POST .../bulk` untuk **Mutasi Masuk, Mutasi Keluar, Lulus, Drop Out** — aksi Ajukan/Setujui/Tolak dengan **state guard** (data final dilewati), scope sekolah, dan pesan hasil "N diproses, M dilewati".
  - Inti approval diekstrak ke `approveMutation()` sehingga tombol tunggal & massal memakai jalur yang sama (event lifecycle tetap terpicu).
  - UI: checkbox per baris + select-all + bar "Aksi Massal" di 4 halaman index mutasi; **Alumni: Verifikasi Terpilih** (hanya `filled` → `verified`, endpoint `alumni/bulk-verify`).
- **Notifikasi wali end-to-end terbukti**: test baru `StudentLifecycleNotificationTest` (DatabaseMigrations, tanpa transaksi pembungkus agar `DB::afterCommit` benar-benar jalan) memverifikasi kelulusan → notifikasi masuk `notifications_universal` untuk wali aktif + alumni + audit row.
- **Perbaikan migrasi lama**: `2026_10_08_120000` `down()` kini SQLite-safe (drop index sebelum drop kolom; dropUnique `code`) — `migrate:rollback`/`DatabaseMigrations` tidak lagi gagal.
- **Pembersihan test usang** (sebelumnya selalu gagal karena `mysql_test`/kolom lama):
  - `StudentLifecycleCascadeTest` & `StudentAssignedToRombelEventTest`: `DatabaseTransactions/SafeRefreshDatabase` → `RefreshDatabase` + seed di `setUp`.
  - `ExtendStudentStatusEnumTest`: ditambah `RefreshDatabase` (kolom sudah diselaraskan sejak Fase 1).
  - `StudentEventsTest`: ditulis ulang — helper berkolom usang (`mutation_date`, `arrival_date`, dll.) dibuang, kini menguji konstruktor event + `LifecycleMessage::forEvent` (termasuk properti notifikasi `priority/title/body`).
  - `StudentStatusSyncListenerTest`: disesuaikan dengan pipeline listener sebenarnya (status → tutup histori → enroll) dan perilaku `afterCommit` untuk notifikasi.
  - Trait `tests/Concerns/SafeRefreshDatabase.php` (hardcoded `mysql_test` + `db:safe-wipe`) dihapus karena tidak lagi dipakai.
  - Hasil: `tests/Feature/Student`, `tests/Unit/Student`, dan `StudentAssignedToRombelEventTest` **28 test lulus** (sebelumnya 19+ gagal).
- Test suite sehat total: **155 test / 1.044 assertion** lulus; `view:cache` sukses; migrasi diterapkan di MySQL dev.

## 26. Tahap 1 — Fondasi Kebijakan: Rumpun Mata Pelajaran & Guard Paket/Kunci
- **Entitas resmi rumpun** (migrasi `2026_10_09_240000`): tabel `subject_groups` (kode, nama, `coordinator_task_name`, pola nama mapel, urutan) + kolom `subjects.subject_group_id` (queryable, dapat ditimpa admin). Seed 5 rumpun: **Umum, Agama, Hadits, Bahasa Arab, Tahfidz**; prioritas pola Tahfidz > Hadits > Bahasa Arab > Agama > Umum; **backfill otomatis** saat migrasi + command `alim:sync-subject-groups` (dengan opsi `--force`).
- **`SubjectGroupResolver`**: pemetaan nama mapel → rumpun; **keanggotaan guru dari `TeachingAssignment` aktif** (mendukung multi-kelas & multi-rumpun — satu akun tidak diikat satu mapel/kelas); **koordinator dari tugas tambahan** (`gtk_additional_tasks.nama_tugas`, mis. "Koordinator Guru Umum"); daftar guru per rumpun & user koordinator per rumpun.
- **Observer `SubjectGroupAssignmentObserver`** memetakan mapel baru/berubah otomatis; **fix bug halt event**: closure `creating` di `Subject::boot()` mengembalikan nilai sehingga `event(... until)` berhenti sebelum observer lain dipanggil — kini tidak mengembalikan nilai (semua observer `creating` pada model tersebut berjalan).
- **Permission assignment-driven**: `SubjectGroupPermissionProvider` menghasilkan `subject-group.{code}.member` (dari penugasan) dan `subject-group.{code}.coordinator` (dari tugas tambahan); `PermissionRebuildObserver` diperluas untuk `TeachingAssignment`, `Subject`, `SubjectGroup`, dan `GtkAdditionalTask` agar snapshot dibangun ulang saat penugasan berubah.
- **Reviewer serumpun sesuai kebijakan**: **koordinator rumpun = reviewer utama** (prioritas pertama), lalu guru mapel yang sama, fallback mapel lintas tahun ajaran, lalu fallback guru lain dalam rumpun yang sama — penulis tetap dikecualikan, maksimum 4 (mekanisme existing dipertahankan).
- **Guard publish paket**: paket hanya dapat dipublikasikan setelah `workflow Approved` (publish dari draft tidak lagi memungkinkan) — menutup celah paket "final" tanpa approval.
- **Kunci jawaban dipisah dari hak melihat**: `KurikulumAccess::canViewAllAnswerKeys()` hanya tim kurikulum/Waka/Kepala (TU **tidak otomatis**, sesuai kebutuhan tugas); `compare` kini memasking kunci/pembahasan **per sisi** (hak atas satu soal tidak membuka kunci soal pasangannya).
- Test: `SubjectGroupPolicyTest` **9 test / 52 assertion** (pemetaan rumpun, keanggotaan multi-rumpun, koordinator, reviewer utama & fallback, guard publish, masking kunci); regresi **136 test / 1.013 assertion** lulus; `view:cache` sukses; migrasi + backfill diterapkan di MySQL dev.
- **Sisa Tahap 1 (increment berikutnya)**: policy per model (BankSoal/Soal/Paket/Perangkat/Nilai/Absensi), sweep scope+guard P0 menyeluruh (kisi-kisi, repository reuse/detail, nilai, kehadiran, API internal), dan UI pengelolaan master rumpun/penugasan koordinator.

## 27. Tahap 1 (Increment 2) — Sweep Guard Server-Side Kebijakan Akademik
- **Trait bersama `AuthorizesAcademicScope`**: batas satuan pendidikan (`ensureSchoolScope`), petugas lintas kelas (Kurikulum/Kepala/Wakil, TU, Kesiswaan), scope rombel (lihat semua dalam satuan; ubah hanya wali kelas/petugas), dan scope buku nilai (guru pengampu termasuk **co-teacher** pada mapel+rombel+TA yang sama).
- **Paket Soal**: seluruh aksi (show/publish/unpublish/reroll/destroy/quality-gate/submit-approval/distribution/distribute) kini ber-guard — batas satuan pendidikan (404 lintas sekolah) dan kewenangan ubah hanya **penyusun kisi** atau **tim kurikulum** (TU tidak mengubah isi akademik). Halaman distribusi baca-saja.
- **Kisi-kisi**: show/edit/update/destroy ber-scope satuan + kewenangan ubah (penyusun/tim kurikulum).
- **Bank Soal & Soal**: guard baca bank (owner/publik/internal/tim-TU) pada show/soal-list/index-form; guard kelola kini **rumpun-aware** (guru pada rumpun kewenangannya + bank accessible) menggantikan kecocokan mapel lintas sekolah; `submit-review` wajib lolos guard kelola; Gate `viewAny/update/delete/clone` yang mati (tanpa policy) diganti cek eksplisit sehingga halaman bank tidak lagi terkunci total.
- **Repositori terpusat**: `compare`, `detail`, dan `reuse` kini memvalidasi akses soal (tim/TU, penyusun, reviewer, atau bank accessible) dan bank tujuan reuse harus bank yang boleh dikelola.
- **Buku Nilai**: `storeSts`/`storeSas` ber-guard scope buku (satuan + pengampu/co-teacher); scope guru di `NilaiGuruController` memakai **auth id** (bukan `{userId}` URL) sehingga spoofing URL tidak lagi membuka buku guru lain.
- **Kehadiran**: ekspor per-santri dibatasi satuan + wali kelas (petugas berwenang dikecualikan); `recap/recapDetail/recapSemester` memvalidasi `study_group_id` (404 lintas satuan, tidak lagi IDOR); input harian memvalidasi seluruh santri adalah **anggota aktif rombel** (422 bila bukan).
- **API internal**: `grade-levels/by-school` & `teachers-by-school` menolak lintas satuan (403) kecuali global view; `study-groups/students/*` ber-scope satuan dan perubahan anggota rombel hanya wali kelas/petugas berwenang.
- Test: `AcademicServerSideAuthorizationTest` **10 test / 20 assertion** (paket lintas sekolah & non-penyusun, kisi lintas sekolah, bank privat, submit-review, reuse lintas sekolah, scope buku nilai via trait, ekspor/rekap kehadiran, API lintas satuan); regresi **174 test / 1.116 assertion** lulus; `view:cache` sukses.

## 28. Tahap 1 (Increment 3) — Master Rumpun Resmi & Penetapan Koordinator via Tugas Tambahan
- **Audit struktur**: `gtk_additional_tasks` (SK, tanpa `school_id`) vs `other_teacher_tasks` (`school_id`, `academic_year_id`, `is_active`, `task_name`); master mapel & penugasan. Keputusan berbasis data: koordinator rumpun **per satuan pendidikan** memakai `other_teacher_tasks` sebagai sumber utama; `gtk_additional_tasks` tetap dibaca sebagai **kompatibilitas legacy** (sekolah via decree/kepegawaian). Tidak ada jenis tugas tambahan baru.
- **Penyelarasan master rumpun (migrasi `2026_10_10_000000_align_subject_groups_with_policy`)**: empat rumpun resmi **Umum, Agama, Bahasa, Tahfidz**; rumpun `hadits` dilebur ke `agama`; `bahasa_arab` menjadi `bahasa`; nama tugas koordinator mengikuti empat nama resmi (Hadits ⊂ Agama sesuai kebijakan).
- **Otoritas rumpun = master data**: `SubjectGroupResolver::groupForSubject()` hanya membaca `subjects.subject_group_id`. Observer auto-pemetaan berbasis pola **dihapus**; `patterns` kini hanya **saran** (`suggestByName`) dan diterapkan lewat aksi admin eksplisit (`alim:sync-subject-groups --apply-suggestions`; tanpa flag hanya laporan).
- **Kolaborasi lintas satuan**: keanggotaan guru mapel yang sama lintas satuan pendidikan diturunkan dari `TeachingAssignment` (`teacherIdsForSubjectAcrossSchools`, `crossSchoolSubjectGroups`) — tanpa koordinator lintas satuan otomatis, sesuai kebijakan bahwa Koordinator Guru Umum bukan koordinator mapel lintas satuan.
- **Reviewer tetap terpisah dari approval**: koordinator rumpun kini **ber-scope satuan pendidikan asal soal** (`coordinatorUserIdsForGroup($group, $schoolId)`); koordinator tetap reviewer utama, bukan approver final, tanpa hak kunci jawaban otomatis.
- **UI Kurikulum**: halaman **Master Rumpun** (index per rumpun dengan statistik; kelola keanggotaan mapel; penetapan/penonaktifan koordinator via tugas tambahan; daftar guru pengampu) dan halaman **Kelompok Guru Lintas Satuan**; guard manajemen hanya tim kurikulum; entri sidebar Kurikulum/Pimpinan.
- Test: `SubjectGroupManagementTest` (akses manajemen, pemetaan via UI, penetapan koordinator per satuan, batas kewenangan, keanggotaan lintas satuan) + pembaruan `SubjectGroupPolicyTest`, `AcademicServerSideAuthorizationTest`, `BankSoalTerpusatTest` (fixture memetakan rumpun eksplisit); regresi terarah **93 test / 539 assertion** lulus; `view:cache` sukses; migrasi diterapkan di MySQL dev.
- **Keputusan bisnis (dikonfirmasi & diterapkan)**: (1) nama lama "Koordinator Guru Hadits"/"Koordinator Guru Bahasa Arab" **dipertahankan** sebagai alias legacy; (2) **satu** koordinator aktif per rumpun + satuan pendidikan + tahun ajaran (penetapan baru menggantikan yang lama); (3) **approver final paket/RPM = seluruh anggota rumpun** (kolektif — semua harus menyetujui), bukan koordinator tunggal; diterapkan pada submit-approval paket (`resolveRumpunApprovers`), RPM menyusul di Tahap 2. Regresi terarah diperluas menjadi **139 test / 948 assertion** lulus.

## 29. Tahap 2 (Increment 4) — RPM Satu Dokumen per Konteks, Versi, Review/Approval, dan Kunci Final
- **Audit**: RPM = `perangkat_pembelajaran` (turunan ATP) — sebelumnya **tanpa** unique konteks, **tanpa** versi, **tanpa** review/approval, dan `status=final` tidak benar-benar mengunci.
- **Konteks dokumen (asumsi terverifikasi data)**: satu RPM **current** per **mapel + jenjang + tahun ajaran + semester** — konsisten dengan ATP/PROTA (per tingkat) dan perilaku “RPM bersama serumpun”; `study_group` tetap atribut opsional, bukan penentu keunikan. Mudah disesuaikan bila kebijakan menghendaki per rombel.
- **Migrasi `2026_10_10_100000_add_rpm_versioning_and_workflow`**: `workflow_status` (draft/review/revisi/approved), `root_id/parent_id/version/is_current`, `submitted_at/approved_at/approved_by`, `locked_at/locked_by` + index konteks; backfill dokumen lama (root = diri sendiri; final lama dianggap approved + terkunci).
- **Model `PerangkatPembelajaran`**: konstanta workflow, relasi `reviewAssignments` (morph), `root/parent/versions`, helper `isLocked()/isEditable()/isApproved()`, dan `createNewVersion()` (revisi = versi baru; dokumen lama menjadi riwayat non-current).
- **Alur & guard**: `store` menolak dokumen kedua pada konteks sama (diarahkan ke dokumen ada); `update/destroy` ditolak saat final terkunci; penetapan `final` langsung hanya oleh tim kurikulum, selain itu melalui **approval kolektif seluruh anggota rumpun** (keputusan bisnis); `submit-review` menugaskan approver via `resolveRumpunApprovers`; `new-version` membuat salinan draft v+1.
- **Approval → kunci otomatis**: saat seluruh approver rumpun menyetujui, RPM menjadi `approved` + `final` + `locked_at/by` terisi; permintaan perbaikan mengembalikan ke `revisi` (tidak terkunci) dan dapat diajukan ulang.
- **Inbox review**: `ReviewSoalController` + view review kini mengenali jenis **RPM** (label, konteks mapel/kelas, versi) dan keputusan reviewer memakai alur kolektif yang sama.
- **Perbaikan scoping**: `academicContext()` kini menyertakan `school_id` untuk Soal/Paket/RPM sehingga koordinator yang diprioritaskan benar-benar **per satuan pendidikan asal dokumen**.
- Test: `RpmVersionLockPolicyTest` (satu dokumen per konteks; approval kolektif → kunci; update/hapus 403;versi baru;revisi tidak mengunci;final langsung kurikulum) + penyesuaian `ProtaProsemRpmTest`/`KurikulumPembelajaranTest` ke kebijakan final-kunci; regresi **143 test / 1.001 assertion** lulus; migrasi diterapkan di MySQL dev; `view:cache` sukses.

## 30. Tahap 2 (Increment 5) — Paket Soal sebagai Naskah: Komposisi, Snapshot Butir, State Machine, Distribusi `received`
- **Komposisi eksplisit**: kolom `paket_soal.komposisi` (jenis/jumlah/bobot/urutan) + form di halaman paket; baris kosong diabaikan; jenis didukung: `pg, mcc, bs, jodoh, isian, uraian` (**esai = uraian**, isian) — bukan hanya soal auto-gradable.
- **Generate butir dari komposisi**: `PaketNaskahService::generateDariKomposisi()` memilih soal approved sesuai jenis dari bank, mengisi `nomor`, `jenis_soal`, dan `bobot_override`; tombol "Generate Butir dari Komposisi".
- **Validasi komposisi**: submit approval & publish ditolak bila jumlah butir per jenis tidak sesuai komposisi (`≥/≠` dilaporkan eksplisit); `sinkronMeta()` mengisi nomor/jenis untuk butir hasil pilih otomatis lama.
- **Snapshot butir (naskah)**: `paket_soal_items.konten_snapshot` + `paket_soal.snapshot_at`; saat publish, pertanyaan/opsi/kunci/pembahasan/bobot dibekukan. Perubahan soal sumber setelah final **tidak mengubah naskah** (`PaketSoalItem::kontenFinal()` memakai snapshot).
- **State machine**: publish hanya dari `approved` (sudah) + khusus snapshot; `unpublish` kini mengembalikan status ke **approved** (bukan tetap `published`); `reroll` hanya draft/revisi dan tidak tersedia setelah snapshot; `submit-approval` hanya dari draft/revisi; paket final **tidak dapat dihapus**.
- **Distribusi `received`**: kolom `received_at/received_by` pada distribusi; penerima menandai "Tandai Diterima" dari halaman distribusi (hanya user penerima bersangkutan; pengguna lain 403) + audit `distribution_received`.
- Migrasi `2026_10_10_200000_add_paket_naskah_columns` diterapkan di MySQL dev (nomor/jenis butir lama di-backfill).
- Test: `PaketNaskahPolicyTest` (komposisi & validasi, generate isian/uraian, snapshot kebal perubahan, state machine, distribusi received) — regresi **148 test / 1.052 assertion** lulus; `view:cache` sukses.

## 31. Tahap 3 (Increment 6) — Buku Nilai Konsolidasi: Satu Buku per Konteks, Co-Teacher, Kunci Final + Audit, Rapor Baca-Saja
- **Audit**: buku nilai = `teacher_admin_books` (per guru sebelum ini); provisi `StudyGroupSubjectProvisioner::upsertAdminBook` memakai kunci `teacher_id+mapel+rombel+TA+semester` → **buku terpisah per guru**; belum ada kunci/audit; `raporNote` tanpa guard.
- **Satu buku per konteks**: provisi kini mencari `mapel+rombel+TA+semester`; jika ada, buku yang sama dipakai (**teacher_id hanya penyusun pertama, tidak ditimpa**); dua guru mapel+rombel sama berbagi satu buku.
- **Akses co-teacher**: `loadAdminBook` + daftar buku (`index`, navigasi wizard, hub) memakai scope penugasan aktif (`TeachingAssignment`) sehingga co-teacher membuka buku yang sama; guru tanpa penugasan tetap 404. Data lama yang masih duplikat ditampilkan **ter-dedupe per konteks** (belum di-merge — menunggu izin backfill).
- **Kunci nilai final + audit**: kolom `locked_at/locked_by` pada `teacher_admin_books`; endpoint `POST {nilai}/{book}/kunci` (**wali kelas rombel atau tim kurikulum**) dan `buka-kunci` (**hanya tim kurikulum**), keduanya menulis AuditLog (`book_locked`/`book_unlocked`). Saat terkunci: `storeSts`, `storeSas`, dan seluruh wizard/autosave guru → **403**.
- **Rapor baca-saja**: catatan rapor hanya wali kelas (`study_groups.homeroom_teacher_id`) atau tim kurikulum; guru pengampu lain → 403 (test lama disesuaikan: wali ditetapkan pada fixture).
- **Keputusan bisnis yang dipakai (dapat diubah)**: pengunci nilai final = **wali kelas atau tim kurikulum**; pembuka kunci = **tim kurikulum**; **tanpa merge data lama** sampai ada izin backfill eksplisit.
- Test: `BukuNilaiKonsolidasiTest` (provisi satu buku + co-teacher + 404 non-pengampu; kunci → blokir input → hanya kurikulum membuka + audit; catatan rapor wali/kurikulum) + regresi **151 test / 1.073 assertion** lulus; migrasi diterapkan di MySQL dev; `view:cache` sukses.

## 32. Tahap 3 (Increment 7) — Kehadiran Terhubung: Asrama/UKS → Presensi Harian & Mapel, Aktor, dan Verifikasi
- **Audit**: pipeline event sudah ada (`HealthPermitApproved`/`LeaveApproved` + listener boarding + `AcademicAttendanceSyncService`) tetapi **tidak ada yang men-dispatch**, dan service menulis status `alpha` ke `student_attendances` dengan kolom usang (`date`, `source`) — drift dari skema nyata.
- **Service sinkronisasi ditulis ulang** (`AcademicAttendanceSyncService`): target nyata **presensi harian** (`admin_presensi_harian`), **presensi mapel** (`admin_presensi_siswa` pada sesi hari yang sama), dan tabel legacy `student_attendances` (dibaca Mobile API). Status dipetakan sesuai kebijakan: **UKS/health → `sakit`**, **izin pulang asrama → `izin`**, **kembali → `hadir`**.
- **Perlindungan catatan manual**: baris hasil input wali kelas/guru mapel (`auto_filled=false`) **tidak ditimpa**; sinkronisasi hanya menandai `verified_at=null` agar dicek ulang — prinsip "saling cek kesesuaiannya".
- **Dispatch event diaktifkan**: `StudentHealthPermitController::approve` → `HealthPermitApproved`; `DormitoryPermitController::approve` → `LeaveApproved` (listener kini mengirim status `sakit`/`izin`).
- **Hook UKS**: `TreatmentStatusController` — status perawatan/observasi/rujuk → presensi `sakit` hari yang sama; status selesai/kembali/dijemput/pulang → `hadir` kembali.
- **Aktor absensi**: absensi kelas/harian tetap hanya **wali kelas atau petugas berwenang (TU/Waka)** — guru mapel ditolak (403) dan mengisi presensi di **jam pelajarannya** melalui wizard buku mapel (sudah berjalan, kini ber-guard kunci nilai).
- **Verifikasi**: endpoint `POST absensi/harian/{record}/verifikasi` (wali kelas/petugas) menandai `verified_at/by` + AuditLog `attendance_verified`; kolom `source/source_ref/auto_filled/verified` ditambahkan pada presensi harian & mapel.
- Test: `AttendanceSyncKehadiranTest` (UKS → sakit di harian+mapel+legacy; izin pulang → izin; event health permit → sakit; catatan manual tidak ditimpa; guru mapel ditolak absen kelas; verifikasi wali vs guru mapel) — regresi **128 test / 774 assertion** lulus; migrasi diterapkan di MySQL dev; `view:cache` sukses.
- **Penyelesaian catatan minor**: (a) alur **kepulangan asrama** kini ter-hook — `DormitoryReturnController::record` & `DormitoryPermitController::returnRecord` men-dispatch `LeaveReturned` → presensi kembali **`hadir`** pada tanggal kembali (teruji); (b) **UI verifikasi** di halaman presensi per-santri: badge **"Otomatis"** (beserta sumbernya) untuk baris hasil sinkronisasi, badge **"Terverifikasi"**, dan tombol **Verifikasi** untuk wali kelas/petugas berwenang (teruji render + aksi). Regresi terarah lanjutan **71 test / 373 assertion** lulus.

## 33. Kebijakan Final — Akses Kunci Jawaban (Serumpun & Kurikulum) + Arsip Buku Nilai Saat Naik Kelas
- **Kunci jawaban**: `KurikulumAccess::canViewAnswerKeys($user, $subject)` — hanya **guru serumpun** (mengampu mapel dalam rumpun yang sama, tugas aktif) dan **tim kurikulum** yang melihat kunci & pembahasan; **pihak lain = pelihat** (pertanyaan tetap tampil, kunci/pembahasan disamarkan). Repository `compare`/`detail` (`BankSoalTerpusatController::canSeeSolution`) kini memakai aturan ini, dihitung **per sisi**; TU tidak lagi otomatis melihat kunci.
- **Naik kelas = arsip permanen**: buku nilai tahun ajaran lampau **tidak dapat diubah** (guard: AY buku ≠ AY aktif → 403 "bersifat arsip") pada `storeSts`, `storeSas`, seluruh wizard/autosave, melengkapi kunci final yang sudah ada. Data lama **tetap tersimpan** sebagai arsip; guru/kelas berikutnya memulai **buku kosong** pada tahun ajaran baru (provisi per AY).
- **Hook promosi**: `StudentPromotionController::execute` membekukan **semua buku tahun ajaran asal** (`locked_at/locked_by`) + audit `books_archived_after_promotion` — sehingga user menaikkan kelas sekaligus mengarsipkan nilai secara permanen.
- Test: `test_kunci_jawaban_hanya_serumpun_dan_kurikulum`, `test_compare_masking_kunci_dihitung_per_sisi` (per-sisi), `test_buku_tahun_ajaran_lalu_arsip_dan_tahun_baru_kosong`; `BankSoalTerpusatTest` disesuaikan ke kebijakan baru; regresi **159 test / 1.120 assertion** lulus; `view:cache` sukses (tanpa migrasi baru — memakai kolom `locked_at` yang sudah ada).

## 34. Fitur Baru — Data Lengkap Alumni (Riwayat Nilai & Rapor) + Tipe Boarding Satuan Pendidikan & Klasifikasi Santri
- **Alumni**: halaman detail (`alumni/{uuid}`) kini menampilkan kartu **"Riwayat Nilai & Rapor"** — arsip nilai per **tahun ajaran & semester** (STS, SAS, formatif, nilai akhir per mapel) beserta **catatan wali kelas**, diambil dari arsip buku nilai (`admin_nilai_sumatif`, `admin_nilai_formatif`, `raport_registrations`). Bersifat **hanya-baca**.
- **Tipe boarding satuan pendidikan**: kolom `schools.boarding_type` (`boarding` | `non_boarding` | `both`) + pilihan pada form edit satuan pendidikan. Default `non_boarding` untuk data lama.
- **Klasifikasi santri**: kolom `students.boarding_status` (`boarding` | `non_boarding`) + pilihan pada form tambah/edit santri, dan **filter "Status Boarding"** pada daftar santri (termasuk opsi **Belum Ditentukan**). Ini memenuhi kebutuhan saat satuan pendidikan bertipe **both**: data santri boarding vs non-boarding menjadi jelas dan dapat disaring.
- Migrasi `2026_10_11_000000_add_boarding_type_and_status` diterapkan di MySQL dev.
- Test: `BoardingAlumniDataTest` (tipe boarding sekolah default & dapat diubah; klasifikasi santri tersimpan + filter daftar; alumni menampilkan riwayat nilai + catatan rapor) — regresi terarah **89 test / 581 assertion** lulus; `view:cache` sukses.

## 35. Gating Modul Asrama per Tipe Satuan Pendidikan + Sinkronisasi Otomatis `boarding_status`
- **Gating akses**: middleware `EnsureBoardingSchool` dipasang pada grup route `asrama` dan `dormitory-master` — satuan `non_boarding` menerima **403** ("Modul asrama tidak tersedia…"); `boarding`/`both` diteruskan; tanpa konteks sekolah (Super Admin/global) fail-open. Sidebar asrama menampilkan pesan informatif untuk satuan non-boarding (`school_supports_boarding()` helper).
- **Sinkronisasi (asrama menang)**: observer `DormitoryResidentObserver` — penghuni aktif ⇒ `students.boarding_status = boarding`; keluar/nonaktif/dihapus ⇒ `non_boarding`. Hanya berlaku untuk satuan `boarding`/`both` (satuan non-boarding tidak diubah).
- Test: `BoardingGatingSyncTest` (HTTP 403 non-boarding + middleware langsung; sinkronisasi boarding/keluar/hapus + skip non-boarding) — regresi terarah **91 test / 588 assertion** lulus; `view:cache` sukses (tanpa migrasi baru).

## 36. Audit & Penyempurnaan Asrama+UKS (1) — Bugfix Kuota Kritis, Guard Kepala/Wakil Asrama, Kuota Kunjungan, & Excel
- **Audit**: sistem izin (kuota per jenis + pulang), kunjungan (boarding policy: strategi/kuota/max visitor), penegakan (`LeaveWorkflowService`/`VisitWorkflowService`), modal limit, laporan, dan keterkaitan UKS (izin sakit wajib health permit approved; sinkronisasi kehadiran) — semuanya sudah ada; ditemukan **cacat runtime** yang membuat kuota tidak berfungsi.
- **BUGFIX 1 (kritis)**: `BoardingRulesEngine::countUsageForCurrentPeriod` memakai rentang `start + 1 HARI` untuk semua periode → kuota bulanan hanya menghitung tanggal 1. Kini rentang satu periode penuh (weekly/monthly/semester/yearly/daily) — kuota bulanan kunjungan terhitung benar.
- **BUGFIX 2 (kritis)**: `QuotaPeriod::rangeBound()` bertipe `CarbonImmutable` tetapi memakai `now()` (mutable) → **TypeError setiap pemanggilan** (seluruh pengecekan kuota mati). Diganti `CarbonImmutable` + penanganan defensif `semester_starts`.
- **Guard peran pengaturan kuota**: `DormitoryPolicyAccess` — hanya **Kepala Asrama (head_id)** dan **Wakil Kepala Asrama (jabatan)** (plus Super/System Admin) yang boleh mengubah kebijakan/kuota (`DormitoryLeavePolicyController::storeOrUpdate`/`applyDefaults`).
- **Kuota kunjungan keluarga per bulan**: `VisitWorkflowService::inspectQuotaForVisit()` (reuse rules engine) + endpoint `visits.quota.check` (JSON used/quota/remaining/period) + **panel jatah kunjungan per santri** di halaman izin + **modal limit** pada form pengajuan kunjungan (blokir saat jatah habis/dinonaktifkan; server tetap memblokir).
- **Laporan Excel rapi**: `DormitoryPermitsExport` (18 kolom terstruktur, header bergaya, auto-size) + route `reports.permits.excel` dengan filter bulan/tahun/jenis/status/santri — melengkapi CSV yang sudah ada.
- Test: `AsramaQuotaGovernanceTest` (guard kepala/wakil/staf; hitungan kuota bulan lintas tanggal; info sisa kuota; struktur export) — regresi terarah **64 test / 307 assertion** lulus; `view:cache` sukses.

## 37. Audit & Penyempurnaan Asrama+UKS (2) — Kebijakan Per Asrama, State Machine Scan, Akses Lintas Asrama Staf Perizinan
- **Kebijakan per asrama (bukan global)**: form **Kebijakan Kunjungan Keluarga** ditambahkan di halaman pengaturan izin per asrama (`leave-policies.index`); `VisitWorkflowService::saveDormitoryPolicy()` membuat/memperbarui `BoardingPolicy` **milik asrama** + assignment (idempoten, tanpa duplikat), hanya Kepala/Wakil Kepala Asrama (guard yang sama). Perubahan kebijakan **global** `boarding-policies.*` (store/update/updateQuota/destroy) kini dibatasi **Super/System Admin** — jalur operasional harian adalah kebijakan per asrama.
- **State machine status izin terstruktur**: konstanta + `TRANSITIONS` + `canTransitionTo()` + `phase()` (`menunggu → di_luar → kembali → selesai`; `rejected` terminal) di `DormitoryPermit`; `updateStatus` memakai peta model (satu sumber kebenaran); jalur **scan** diberi guard transisi eksplisit (Fase 1 scan keluar → `picked_up`/`di_luar`; Fase 2 scan kembali → `returned`/`kembali`) + `last_actioned_by`.
- **Staf Perizinan lintas asrama sesuai gender**: `User::perizinanGenderScope()` (dari role/jabatan 'perizinan putra/putri', fallback dari asrama yang ditugaskan) — staf perizinan putri dapat mengakses **semua asrama putri** (dan `campuran`), staf putra sebaliknya, sehingga bisa **saling backup** mengisi perizinan; `accessibleDormitoryIds()` ikut diperluas. Bukan staf perizinan tetap terbatas pada penugasan/head_id.
- **Housekeeping**: file sisa `DormitoryPermitController.php.bak` dihapus (sesuai persetujuan).
- Test: `AsramaPolicyExecutionTest` (akses lintas asrama per gender + batas non-staf; kebijakan kunjungan per asrama idempoten; state machine izin) — regresi terarah **57 test / 256 assertion** lulus; `view:cache` sukses.

## 38. Notifikasi WhatsApp Wali Santri (Alternatif Tanpa API Resmi) — Dijemput, Kembali, Sakit
- **Temuan audit**: pipeline WA universal memanggil job `SendUniversalWhatsApp` yang **tidak ada** (notifikasi WA praktis mati).
- **Gateway WA provider-agnostic** (`WhatsAppGatewayService`): driver **fonnte** (ada kuota gratis, populer), **wablas**, **generic webhook** (gateway sendiri), dan **log** (GRATIS — pesan dicatat ke log + link `wa.me` untuk kirim manual). Konfigurasi via `config/whatsapp.php` / `.env` (`WA_PROVIDER`, `WA_API_TOKEN`, `WA_API_URL`, `WA_SENDER`, `WA_ENABLED`) atau tabel settings (`wa.provider`, `wa.token`, `wa.url`, `wa.sender`, `wa.enabled`). Normalisasi nomor Indonesia (`08xx`/`8xx` → `628xx`).
- **Job hilang dibuat**: `SendUniversalWhatsApp` kini ada dan mendelegasikan ke gateway — pipeline notifikasi universal (izin disetujui, pelanggaran, alpa) ikut hidup kembali.
- **Notifier wali santri** (`GuardianWhatsAppNotifier`): template pesan untuk **DIJEMPUT** (penjemput, tujuan, estimasi kembali), **KEMBALI KE ASRAMA** (waktu kembali), dan **SAKIT** (catatan UKS). Nomor wali = mahrom utama aktif (`student_mahroms`), fallback nomor santri. Toggle per-event: `wa.events.*`.
- **Hook**: scan keluar & scan kembali (`DormitoryPermitController::scanStore`) dan status UKS masuk perawatan (`TreatmentStatusController`) — dibungkus try/catch agar kegagalan WA tidak mengganggu alur akademik.
- Test: `WhatsAppGuardianNotificationTest` (fonnte terkirim ke nomor wali ternormalisasi — dijemput/kembali/sakit; driver log gratis tanpa HTTP; normalisasi nomor) — regresi terarah **47 test / 183 assertion** lulus; `view:cache` sukses.

## 39. Notifikasi WA Terpusat (Nomor Admin ALIM), Pengaturan Gateway + Template + Log, Notifikasi Rapor/Libur, & Legenda Kaldik
- **Mode terpusat**: `wa_delivery_mode` = `central` (default) → semua notifikasi wali dikirim ke **satu nomor admin ALIM** (`wa_central_number`), pesan menyertakan identitas wali + santri agar bisa diteruskan; `guardian`/`both` tersedia; fallback otomatis ke nomor wali bila nomor pusat kosong.
- **Halaman pengaturan WA (Super Admin → Notifikasi)**: provider (log/fonnte/wablas/generic), token, URL, nomor pusat, mode penerima, aktif/nonaktif, **5 template pesan dapat diedit** (`wa_template_{key}`), dan **riwayat 15 pengiriman terakhir** dari tabel baru `wa_message_logs`.
- **Log pengiriman**: `WhatsAppGatewayService` mencatat setiap kiriman (sent/failed/logged) ke `wa_message_logs` + IntegrationEventLog — riwayat sukses/gagal terlihat di pengaturan.
- **Notifikasi tambahan ke wali**: **rapor siap** (dipicu saat buku nilai final dikunci, `NilaiController::lockBook`) dan **libur/jadwal** (dipicu saat Kaldik jenis `libur` dibuat). Pipeline lama (izin disetujui, pelanggaran, alpa) kini hidup karena job `SendUniversalWhatsApp` tersedia.
- **BUGFIX**: helper global `setting()` ternyata **tidak ada** (banyak modul lama bergantung padanya, termasuk override settings WA) — dibuat di `helpers.php` + cache `system_settings_map` dibersihkan saat pengaturan disimpan.
- **Legenda default Kaldik**: `config/kaldik.php` (libur = **merah #EF4444**, ujian, kegiatan, hari efektif, dll.), helper `kaldik_legend()`/`kaldik_default_color()`, warna default otomatis terisi saat menyimpan Kaldik tanpa warna, **preselect swatch warna mengikuti jenis** di form, dan dapat ditimpa via settings `kaldik_legend_{type}`.
- Migrasi `2026_10_11_100000_create_wa_message_logs_table` diterapkan di MySQL dev.
- Test: `WhatsAppGuardianNotificationTest` (7 test) — mode terpusat + identitas wali + log tersimpan, template override via settings, libur terpusat + legenda default/override, driver log gratis, normalisasi nomor — regresi terarah **50 test / 193 assertion** lulus; `view:cache` sukses.

## 40. Penemuan Halaman WA, Warna Kaldik Otomatis + "Program Bersama", Uji Kirim, UI Pengaturan Sistem, & Pencarian Global Topbar
- **Halaman pengaturan WA**: kini ada menu sidebar **"WA Gateway"** (Super Admin) yang langsung membuka tab Notifikasi + tab Notifikasi dirapikan menjadi kartu: **Notifikasi Umum**, **Gateway WhatsApp** (status/provider/mode/nomor pusat/token/URL + tombol **Uji Kirim ke Nomor Pusat**), **Template Pesan** (5 template), dan **Riwayat Pengiriman Terakhir** (`wa_message_logs`).
- **Uji kirim nyata**: pengaturan `wa_central_number` = **082146898982**; provider `log` (gratis) → pesan uji tercatat + link `wa.me` untuk kirim manual (terverifikasi; `sent=YA`, 2 baris log). Bila token Fonnte diisi, pesan otomatis terkirim ke nomor tersebut.
- **Kaldik — warna otomatis & tipe baru**: tipe kini **otomatis menentukan warna** (tidak perlu pilih 2×; swatch disembunyikan & tag warna terisi), server juga mengisi warna otomatis pada store **dan update**; ditambah tipe **"Program Bersama"** (lime `#84CC16`); kolom `type` diubah dari enum → string agar tipe baru mudah ditambah (migrasi `2026_10_11_200000_extend_kaldik_type`).
- **Pencarian global di topbar**: kolom cari (menu, fitur, **dan data santri**) dengan dropdown hasil berkelompok; registry menu `config/global_search.php` + `GlobalSearchController` (route `user.search`, scoped satuan pendidikan; santri berdasar nama/NISN/NIS).
- Test: `GlobalSearchAndKaldikTest` (pencarian menu + santri; tipe & warna otomatis Kaldik) — regresi terarah **52 test / 201 assertion** lulus; migrasi diterapkan di MySQL dev; `view:cache` sukses.

## 41. Perbaikan Fonnte (Token/URL), Template WA Diperluas, Legenda Kaldik Berwarna, & Pencarian Menu
- **Diagnosa Fonnte — 3 akar masalah ditemukan & diperbaiki**:
  1. **Mapping token**: halaman menyimpan `wa_api_token` tetapi gateway membaca `wa_token` → Authorization kosong → *invalid token*. Kini gateway membaca `wa_api_token`/`wa_api_url` juga.
  2. **Typo URL** yang tersimpan: `api.fonte.com` (kurang "n") → kini URL diperbaiki ke `https://api.fonnte.com/send`, plus **normalisasi otomatis** di halaman pengaturan (typo domain + trim token + bersihkan nomor).
  3. **Device terputus**: error tersisa `request invalid on disconnected device` — device Fonnte (`6281949069836`) perlu dihubungkan ulang di dashboard Fonnte (bukti: saat device terhubung, API Fonnte membalas `success! message in queue`, kuota 1000).
  - Error asli dari provider kini disimpan di `wa_message_logs.error` dan **ditampilkan di halaman pengaturan** (baris merah di riwayat).
- **Template WA default diperluas** (semua dapat diedit di halaman pengaturan): `picked_up`, `returned`, `sick`, `rapor`, `holiday`, **`permit_approved`**, **`permit_rejected`**, **`violation`** (pelanggaran), **`overdue`** (belum kembali), `achievement`, `announcement`, `visit` — plus metode notifier & hook izin **disetujui/ditolak** (mode terpusat/wali).
- **Legenda Kaldik**: panel legenda kini menampilkan **semua jenis dengan chip warna** (Libur merah, Ujian, Kegiatan, Hari Efektif, Tahunan, Mid Semester, **Program Bersama**, Lainnya) + keterangan kategori pondok/satuan kerja; warna tetap otomatis dari jenis.
- **Pencarian menu diperbaiki**: pembuatan URL menu defensif (route tanpa parameter lain dilewati, tidak lagi mematahkan seluruh pencarian), endpoint dikecualikan dari middleware permission, dan dropdown menampilkan pesan **"Pencarian gagal. Coba lagi."** alih-alih diam.
- Test: WA + pencarian/Kaldik **9 test / 31 assertion lulus**; `view:cache` sukses; lint bersih.

## 42. Fonnte Berhasil Terkirim, Waktu WITA, & Diagnosa Uji Kirim- **Pengiriman WA kini BERHASIL terverifikasi**: gateway `sent=YA`, riwayat `status=sent` pada **15:00:28 WITA**, respons provider **"success! message in queue"** (kuota Fonnte tersisa 997). Riwayat gagal lama (sebelum perbaikan URL/token/device) dibersihkan.
- **Zona waktu WITA**: `config/app.php` → `Asia/Makassar` (UTC+8) — seluruh `now()`, notifikasi, dan riwayat pengiriman memakai WITA (terverifikasi `15:00` vs sebelumnya `07:00` UTC).
- **Uji Kirim lebih diagnostik**: pesan flash kini menyertakan **respons asli provider** (`success! message in queue` / alasan gagal), dan kolom error pada riwayat menampilkan detail dari Fonnte.
- **Ringkasan akar masalah Fonnte yang telah diperbaiki**: (1) mapping token `wa_api_token` tidak dibaca gateway, (2) typo URL `api.fonte.com`, (3) device Fonnte terputus — + normalisasi otomatis URL/token/nomor pada halaman pengaturan.
- Test: WA + pencarian/Kaldik + asrama **12 test / 54 assertion lulus**; `view:cache` sukses.

## 43. Diagnosa Device Fonnte & Fallback Kirim Manual
- **Bukti API device**: `POST https://api.fonnte.com/device` → `device_status: connect`, device `6281949069836`, kuota `993`, paket `Free`, expired `11 November 2026`, `messages: 7` — server ALIM & API Fonnte **normal**; kegagalan fisik ada di jalur WhatsApp device.
- **Tombol "Cek Status Device"** di halaman WA Gateway (menampilkan status/kuaota/paket/expired langsung dari API) untuk monitoring mandiri.
- **Kolom "Kirim Manual (wa.me)"** pada Riwayat Pengiriman — satu klik membuka WhatsApp dengan isi pesan siap kirim (fallback gratis selama jalur device bermasalah).
- Saran operasional: gunakan provider `log` sementara; semua notifikasi tetap tercatat + bisa diteruskan manual.

## 44. Template WA Siap Pakai (12 Jenis) + Toggle per Jenis, & Notifikasi Topbar GTK (Per Satuan Kerja, Kaldik, Login, Suara)
- **Template WA**: halaman WA Gateway kini menampilkan **contoh isi default sebagai placeholder** untuk **12 jenis** (Dijemput, Kembali, Sakit, Rapor siap, Libur/Jadwal, Izin disetujui, Izin ditolak, Pelanggaran, Overdue, Prestasi, Pengumuman, Kunjungan). Kosongkan untuk memakai default; isi untuk menimpa.
- **Toggle per jenis**: setiap template punya switch **Aktif** (`wa_event_{key}`); notifier membaca toggle ini (nonaktif → tidak dikirim). Izin disetujui/ditolak + dijemput/kembali/sakit sudah ter-hook; jenis lain siap dipakai.
- **Notifikasi topbar GTK disempurnakan**:
  - **Per satuan kerja**: kolom baru `notifications_universal.work_unit_id` — notifikasi hanya masuk ke pengguna satuan kerja terkait (global `null` terlihat semua); `sendToWorkUnit()` + scope pada hitungan unread.
  - **Info Kaldik otomatis**: saat agenda Kaldik dibuat → notifikasi in-app ke pegawai satuan kerja tersebut (judul, tanggal, tautan Lihat Kaldik).
  - **Notifikasi login**: listener `SendLoginNotification` — memberi tahu perangkat (platform/browser), IP, dan waktu WITA, dengan peringatan keamanan.
  - **Suara notifikasi**: polling `unread-count` tiap 30 detik, badge diperbarui otomatis, dan **bunyi beep** (Web Audio, tanpa file) saat ada notifikasi baru. Route baru `user.notifications.unread-count`.
- Migrasi `2026_10_11_300000_add_work_unit_to_notifications_universal` diterapkan di MySQL dev.
- Test: `TopbarNotificationAndWaToggleTest` (scope satuan kerja + login + toggle WA) — total WA/topbar **10 test / 35 assertion lulus**; `view:cache` sukses.

## 45. Perbaikan Notifikasi GTK (In-App) — Resolusi Satuan Kerja & Generalisasi Semua Aspek
- **Akar masalah ditemukan**: Kaldik tersimpan dengan `work_unit_id = NULL` (dibuat pengguna global/Super Admin) sehingga `sendToWorkUnit` tidak pernah berjalan; notifikasi in-app hanya memakai pivot `gtk_work_unit`; dan hook hanya ada di `store` (bukan `update`).
- **Perbaikan**:
  - `userIdsForWorkUnit()` — resolusi pengguna satuan kerja **gabungan** pivot `gtk_work_unit` **dan** kepegawaian (`gtk_employments.school_id → schools.work_unit_id`).
  - `notifyGtk(?workUnitId, data)` — **fallback GLOBAL** ke semua pengguna aktif bila satuan kerja tidak diketahui (mis. dibuat Super Admin); per satuan kerja bila diketahui.
  - **Kaldik**: hook di **create & update** (hanya saat nama/tanggal/jenis berubah) dengan fallback satuan kerja **pembuat**.
  - **Perizinan asrama**: izin **disetujui/ditolak** kini juga mengirim notifikasi in-app ke **petugas asrama** (`notifyDormitoryStaff`: kepala + staf aktif).
  - Broadcast uji nyata: **56 pegawai** menerima notifikasi in-app (topbar + bunyi).
- **Generalisasi semua aspek**: API `notifyGtk()`/`notifyDormitoryStaff()` siap dipakai modul lain (nilai, bank soal, lifecycle santri, tagihan, pengumuman) — tinggal pemanggilan hook.
- Test: resolusi via kepegawaian + fallback global — total **4 test / 16 assertion lulus**; `view:cache` sukses.

## 46. Notifikasi Topbar Tampil (Endpoint JSON) + Suara notification.mp3
- **Akar masalah**: `notifications.init.js` memanggil `/{userId}/notifications` dengan `Accept: application/json`, tetapi controller mengembalikan **HTML** (konsol Super Admin) → daftar tidak pernah dirender (notifikasi tidak tampak meski data ada).
- **Perbaikan**: endpoint kini mengembalikan **JSON** untuk AJAX (`{notifications, unread_count}`) — **hanya notifikasi milik pengguna aktif** dan **ber-scope satuan kerja** (global `null` atau satuan pengguna). Respons `mark-read`/`mark-all-read` juga JSON untuk AJAX.
- **Global → semua GTK termasuk Super Admin**: `notifyGtk(null)` mengirim ke seluruh pengguna aktif (uji: **56 penerima**, total 224 baris notifikasi). Event Kaldik & pengumuman pondok kini masuk ke semua GTK.
- **Suara**: JS topbar sudah memuat **`/sounds/notification.mp3`** (40 KB, tersedia) dan memutarnya saat notifikasi baru; fallback beep bila audio tidak siap. Toast berwarna per tipe + badge jumlah diperbarui.
- Test: endpoint topbar JSON (judul & unread_count sesuai pengguna) — total **5 test / 19 assertion lulus**; `view:cache` sukses.

## 47. Uji End-to-End Kaldik → Notifikasi GTK, Perbaikan "Lihat Semua" 404, & UI Notifikasi
- **Akar masalah ganda ditemukan**: (a) daftar topbar tidak pernah dirender karena endpoint mengembalikan HTML (bukan JSON) — sudah diperbaiki; (b) aturan target Kaldik pondok keliru memakai satuan kerja **pembuat** alih-alih **global**.
- **Aturan baru**: **Kaldik pondok (kategori kaldik) → GLOBAL** (semua GTK seluruh satuan kerja, termasuk Super Admin); **Agenda satuan kerja → hanya satuan terkait + pembuat selalu menerima konfirmasi**.
- **Uji end-to-end via HTTP (2 test lulus)**: membuat Kaldik pondok → Super Admin + Guru Unit A + Guru Unit B semuanya menerima; membuat Agenda Unit B → hanya Guru Unit B + pembuat, Guru Unit A **tidak** menerima.
- **"Lihat semua notifikasi" 404 diperbaiki**: route baru `user.notifications.page` → halaman daftar notifikasi pengguna; tautan JS memakai `/{userId}/notifications/page`; aset JS diberi **cache-busting** (`?v=filemtime`) agar perubahan langsung tampil.
- **UI**: gaya item notifikasi (garis unread, hover, area gulir), tab Semua/Belum dibaca, toast berwarna, dan **suara `public/sounds/notification.mp3`** (fallback beep).
- Bukti uji nyata: broadcast global terkirim ke **56 GTK** (total 336 baris notifikasi). Test: **7 test / 27 assertion lulus** (Kaldik flow + topbar/WAtoggle).

## 48. Perbaikan Halaman "Lihat Semua Notifikasi" (Missing parameter userId)
- Error `Missing required parameter for [Route: user.notifications.index]` pada `resources/views/notifications/index.blade.php:127` diperbaiki: seluruh tautan/form di halaman itu kini memakai route **`user.notifications.page`** dengan parameter `userId` (sebelumnya memanggil route JSON tanpa parameter).
- Controller `NotificationPageController::index` kini mengirim `$userId` ke view.
- Test baru: halaman "Lihat semua" **render 200 + menampilkan notifikasi** — total **8 test / 29 assertion lulus** (Kaldik flow + halaman notifikasi + topbar/toggle WA); `view:cache` sukses.

## 49. Notifikasi Topbar Berfungsi Penuh — JSON Sesuai UI, Suara Polling, & Perbaikan JS Theme
- **Tiga akar masalah diperbaiki**:
  1. **Endpoint `unread-count` 500** — method `unreadCount()` sempat hilang (tertimpa patch) lalu dikembalikan + resolusi service via `app()`; kini mengembalikan `{success, count, unread_count}`.
  2. **Bentuk JSON tidak sesuai UI** — `notifications.init.js` mengharapkan `{success, data.data[], unread_count}`; controller kini mengirim bentuk itu (plus `notifications`) sehingga **daftar & badge topbar benar-benar terisi**.
  3. **JS `app.js` crash** — `layoutSwitch` mengakses `document.getElementById("body-img").style` tanpa guard (tema `galaxy`) → null.style TypeError yang menghentikan init JS. Kini dijaga (guard) di dua titik.
- **Suara saat polling**: `loadNotifications()` (interval 30 detik) melacak `lastUnreadCount`; saat jumlah naik → memutar **`/sounds/notification.mp3`** + menampilkan toast. Poller beep inline yang duplikat di topbar dihapus.
- **Cache-busting** untuk `notifications.init.js` **dan** `app.js` (`?v=filemtime`) agar browser memuat versi terbaru tanpa hard-refresh berulang.
- Test: endpoint topbar JSON (`data.data`), unread-count 200, halaman Lihat Semua, alur Kaldik — **9 test / 32 assertion lulus**; `view:cache` & validasi sintaks JS sukses.

## 50. Badge Notifikasi Tampil Saat Refresh, Suara Lebih Keras, & Poles UI Topbar
- **Badge merah kini tampil sejak halaman dimuat**: topbar menghitung `$notifUnreadCount` **server-side** (fallback bila JS belum jalan) dan menampilkan badge tanpa `d-none` saat > 0; JS tetap memperbarui via polling 30 detik.
- **Suara lebih keras & pasti berbunyi**: volume `notification.mp3` dinaikkan **0.4 → 1.0** + mekanisme **unlock autoplay** pada interaksi pertama pengguna (klik/tombol apa pun), sehingga browser mengizinkan pemutaran suara.
- **UI topbar/dropdown dipoles**: header gradien biru-teal, sudut membulat, bayangan halus, item unread bergaris kiri, tipografi judul/pesan/waktu lebih rapi, area gulir 340px — mengacu pada tampilan tema referensi yang Anda kirimkan.
- Catatan: markup yang Anda tempelkan adalah **demo tema asli** (berisi keranjang/bahasa/dummy user) — bukan dari aplikasi; kita adopsi gaya visualnya tanpa konten dummy tersebut.
- Test: endpoint & alur notifikasi tetap hijau — **9 test / 32 assertion lulus**; `view:cache` sukses; sintaks JS valid.

## 51. Redesign Topbar: Pencarian Global Benar-benar Berfungsi & Bell Dirapikan
- **Akar masalah pencarian (3 lapis)**:
  1. Route `/search` mereferensikan `GlobalSearchController` **tanpa `use` import** → `Target class does not exist` (**500**) — ini sebab utama "pencarian tidak bisa digunakan". Import ditambahkan (diverifikasi + **test HTTP** `GET /{userId}/search?q=santri` → 200 JSON).
  2. Panel hasil memakai class Bootstrap `dropdown-menu` → selalu `display:none` walau `d-none` dibuang; kini diganti panel kustom `.alim-search-panel` yang benar-benar tampil.
  3. Input disembunyikan di layar < lg dan terjepit di kanan; kini **dipindah ke kiri** (setelah tombol hamburger), lebar nyaman, gaya pill modern dengan ikon, fokus ring, dan panel hasil bergaya kartu (header grup + hover).
- **Bell/notifikasi dirapikan**: ukuran badge diseragamkan (min 18px, font 10px), badge merah **tampil sejak refresh** (dihitung server-side), dropdown bergaya gradient + item unread bergaris kiri (sebelumnya sudah), tab Semua/Belum dibaca.
- **Suara**: volume `notification.mp3` = **1.0** + unlock autoplay pada interaksi pertama (dari perbaikan sebelumnya).
- Test: **10 test / 35 assertion lulus** (termasuk endpoint pencarian HTTP); `view:cache` sukses; lint bersih.

## 52. Perbaikan "Tandai Semua Dibaca" (Per Akun) & Polesan Pencarian Global
- **BUG SERIUS diperbaiki**: `NotificationUniversalController::markAllRead()` sebelumnya meng-update **semua baris tanpa filter pengguna** (`where('is_read', false)` global) → menandai notifikasi akun lain sebagai terbaca (mis. aksi TU memengaruhi akun guru). Kini **per akun** (`markAllAsRead(auth()->id())`, hanya baris milik pengguna aktif).
- **Keamanan kepemilikan**: `markAsRead($id)` kini memverifikasi `user_id = auth()->id()` (`firstOrFail`) — akun lain mendapat **404** dan tidak dapat menandai notifikasi milik orang lain.
- **Polesan pencarian global**: indikator **"Mencari…"** saat memuat, header grup berikon (Menu & Fitur / Santri), item dengan label+meta, **empty state** ("Tidak ada hasil untuk …"), dan footer jumlah hasil + petunjuk Enter.
- Test baru: (1) mark-all-read hanya memengaruhi akun sendiri (akun lain tetap unread); (2) tidak dapat menandai notifikasi akun lain (404 + status tetap). Total **14 test / 48 assertion lulus**; `view:cache` sukses.

## 53. Notifikasi Gaya WA Web — Polling Adaptif 5 Detik, Instan Saat Fokus, Notifikasi Desktop
- **Tanpa perlu refresh**: JS beralih dari interval 30 detik tetap menjadi **polling adaptif** — **5 detik saat tab aktif**, 30 detik saat tab disembunyikan — dan **langsung memuat** begitu tab kembali terlihat/jendela fokus/koneksi online.
- **Anti-kedip**: render ulang hanya bila data berubah (hash id+status+unread), sehingga daftar tidak berkedip saat polling.
- **Notifikasi desktop** (opsional, seperti WA Web): izin diminta sekali saat interaksi pertama; saat tab tidak aktif, notifikasi muncul di OS (judul + isi). Tetap dibarengi **toast** + **bunyi `notification.mp3`** + badge.
- **Jalur instan sejati (opsional, tanpa perubahan kode)**: skrip sudah mendengarkan event real-time `.notification.received`; cukup isi kredensial **Pusher** (ada kuota gratis) atau pasang **Laravel Reverb** (`php artisan reverb:start`) — saat itu notifikasi muncul **0 detik** tanpa polling.
- Verifikasi: sintaks `notifications.init.js` valid; `view:cache` sukses.

## 54. Bugfix Kaldik — Tanggal Kegiatan Kurang 1 Hari (Off-by-One)
- **Akar masalah**: feed kalender mengisi `end` dengan `T23:59:59` (gaya inklusif), sedangkan **FullCalendar memakai `end` EKSKLUSIF** dan form edit menguranginya 1 hari — pasangan yang tidak konsisten membuat pilihan 1–7 Oktober tampil/tersimpan menjadi 1–6.
- **Perbaikan**: `toFullCalendarEvent()` kini menambah **1 hari** ke `end` (T00:00:00, eksklusif) — form edit (yang mengurangi 1 hari) otomatis kembali konsisten; kalender menampilkan **1–7**, form edit menampilkan **1–7**, dan penyimpanan tetap **1–7**.
- Simulasi terverifikasi: stored `1 s/d 7` → calendar end eksklusif `2026-10-08` (tampil 1–7) → form edit `2026-10-07` (tersimpan tetap 7).
- Aset `kaldik-calendar.init.js` diberi **cache-busting** (`?v=filemtime`) agar perbaikan langsung dimuat tanpa hard-refresh.
- Verifikasi: sintaks JS valid; `view:cache` sukses.

## 55. Bugfix Blok Warna Kalender Kaldik (Kurang 1 Hari)
- **Akar masalah**: fungsi `colorDayCells()` membangun peta warna dari **data mentah** (tanggal akhir **inklusif**, mis. 7 Okt) tetapi kemudian **mengurangi 1 hari** pada tanggal akhir (mengasumsikan konvensi eksklusif FullCalendar) → blok warna berakhir **6 Okt** untuk kegiatan 1–7.
- **Perbaikan**: warna kini memakai rentang **inklusif apa adanya** (`start … end`), tanpa pengurangan; kasus tanggal tunggal tetap terwarnai.
- Simulasi terverifikasi: kegiatan `1 s/d 7 Okt` → **blok warna 7 hari (1 s/d 7)**.
- Aset `kaldik-calendar.init.js` sudah ber-cache-busting (`?v=filemtime`) sehingga perbaikan langsung termuat.
- Verifikasi: sintaks JS valid; `view:cache` sukses.

## 56. Asrama P1/P3/P4 — Overdue Terjadwal, WA Darurat Nyata, Notifikasi Kunjungan, Pengingat H-1, & Bugfix Config Template
- **Overdue berjalan otomatis**: `dormitory:process-overdue` sudah terjadwal di Kernel (**setiap jam**, tanpa overlap) — ditemukan duplikasi jadwal tambahan dan dihapus.
- **WA darurat nyata**: stub `TODO` di `LeaveWorkflowService` diganti pengiriman sungguhan via `WhatsAppGatewayService` (nomor pusat ALIM) untuk peringatan darurat izin; hasil kirim dicatat di log.
- **Notifikasi kunjungan**: izin kunjungan **disetujui** → **WA ke wali** (template `visit`) + **notifikasi in-app petugas asrama** (`notifyDormitoryStaff`).
- **Pengingat H-1 kembali**: command baru `dormitory:remind-returns` (harian 16:00 WITA) mengirim **WA pengingat** ke wali untuk izin yang harus kembali besok + template `permit_reminder`.
- **BUGFIX besar ditemukan**: `config/whatsapp.php` hanya memuat **5 template**; template izin disetujui/ditolak, pelanggaran, overdue, prestasi, pengumuman, kunjungan, dan pengingat **tidak pernah ada** (percobaan sebelumnya gagal senyap) sehingga notifikasi jenis itu kosong/gagal. Kini **13 template lengkap + toggle events** ditulis ulang dan terverifikasi (termasuk placeholder di halaman pengaturan).
- Test: kunjungan + pengingat + jadwal + seluruh regresi notifikasi/asrama — **26 test / 102 assertion lulus**; `view:cache` sukses.

## 57. QR Kedaluwarsa Setelah Kembali, Alur Penempatan Kamar (ala Dapodik), & Bugfix Room Nullable
- **QR kadaluarsa**:
  - **Izin**: scan ulang setelah santri kembali → pesan tegas **"KARTU KADALUARSA — {nama} sudah kembali ke asrama pada {waktu}. QR Code kartu perizinan ini tidak berlaku lagi."** (ditampilkan sebagai modal pemberitahuan; status `expired`).
  - **Kunjungan**: scan ulang setelah check-out → **"KARTU KADALUARSA — kunjungan telah selesai (check-out pada {waktu})"**.
  - Housekeeping: `scan.blade.php.bak` dihapus.
- **Penempatan Kamar (alur jelas, ala Dapodik)**:
  - Halaman baru **Penempatan Kamar** per asrama (tombol di halaman Kamar): daftar kamar + penghuni + **nomor ranjang**, sisa kapasitas, tombol **Tempatkan Santri** & **Keluarkan**.
  - **Tarik Data Santri Belum Berkamar**: menarik santri aktif berkategori boarding (atau belum ditentukan) yang **belum memiliki kamar**, **sesuai gender asrama**, dengan pencarian; pilih kamar tujuan (hanya yang bersisa) → **penempatan massal**; **nomor ranjang otomatis**; validasi kapasitas (422 bila melebihi).
  - **Keluarkan dari kamar**: penghuni **tetap aktif di asrama** (room/bed dikosongkan) → **muncul kembali di tarik data** untuk penempatan ulang; penempatan ulang **tidak menduplikasi** record.
- **Bugfix penting**: `dormitory_residents.room_id` NOT NULL membuat endpoint "keluarkan dari kamar" **selalu gagal** — migrasi `2026_10_11_400000` membuat `room_id` & `bed_number` **nullable** (diterapkan di MySQL dev).
- Test: `DormitoryPlacementFlowTest` (tarik data gender & exclude yang sudah berkamar; kapasitas + nomor ranjang otomatis; keluar → tampil lagi → penempatan ulang tanpa duplikasi) — regresi asrama/WA **18 test / 83 assertion lulus**; `view:cache` sukses.

## 58. Penempatan Terkunci Satuan Pendidikan, Kelola Boarding/Non-Boarding, Berhenti Mondok, & Keterkaitan Mutasi
- **Penempatan terkunci pada satuan pendidikan asrama**: tarik data & penempatan hanya untuk santri **sekolah yang sama** dengan asrama (contoh: Asrama SMP IT Putra hanya menampilkan santri SMP IT Putra); percobaan menempatkan santri sekolah lain ditolak **422** dengan pesan jelas. Ditambah validasi **gender** (putra↔L, putri↔P) sesuai asrama/kamar, dan nama satuan pendidikan tampil di halaman penempatan.
- **Kelola Boarding/Non-Boarding**: modal baru **"Kelola Status Boarding"** — daftar santri aktif satuan pendidikan asrama (dengan pencarian, badge status, penanda "Berkamar") dan aksi massal **Tandai Boarding** / **Tandai Non-Boarding** — untuk sekolah tipe **boarding & non-boarding**.
- **Berhenti Mondok**: aksi per penghuni (dengan alasan) → penghuni **nonaktif**, kamar/ranjang dikosongkan, **status boarding otomatis non-boarding**, sementara **santri tetap aktif di sekolah**.
- **Keterkaitan mutasi/kelulusan**: listener baru `CloseDormitoryResidentOnStudentExit` pada event **`StudentMutatedOut`** & **`StudentGraduated`** → seluruh penghuni aktif santri tersebut **ditutup otomatis** (kamar dikosongkan, alasan "Mutasi keluar/Lulus" tercatat) + **notifikasi in-app ke petugas asrama**.
- Test: `DormitoryPlacementFlowTest` (6 test — tarik data gender/exclude assigned; kapasitas & ranjang otomatis; keluar & penempatan ulang tanpa duplikasi; **tolak lintas sekolah & gender**; kelola boarding + berhenti mondok; **mutasi menutup penghuni**) — regresi asrama/WA **23 test / 103 assertion lulus**; `view:cache` sukses.

## 59. Mutasi Masuk Otomatis ke Asrama & Mutasi Kamar dengan Jejak Approval
- **Mutasi masuk (`StudentMutatedIn`)**: listener `NotifyDormitoryOnStudentMutatedIn` —
  - sekolah bertipe **boarding** → status boarding santri baru **otomatis ditandai `boarding`** sehingga **langsung muncul di daftar "Tarik Data Santri Belum Berkamar"**;
  - **notifikasi in-app ke petugas asrama** yang sesuai (satuan pendidikan + gender, termasuk asrama `campuran`): "Santri mutasi masuk — belum memiliki kamar, silakan tempatkan."
- **Mutasi kamar antar-kamar dengan approval (`dormitory_room_moves`)**:
  - **Ajukan** (per penghuni, pilih kamar tujuan bersisa + alasan) → tercatat `pending` dengan `move_type` valid (`rotasi`/`permintaan`);
  - validasi kamar tujuan (satuan pendidikan/gender/kapasitas), kamar sama ditolak;
  - **Daftar "Mutasi Kamar Menunggu Persetujuan"** di halaman Penempatan dengan tombol **Setujui/Tolak**;
  - **Setujui** → penghuni benar-benar dipindah (ranjang otomatis) + jejak `approved_by`/`approval_status`; **Tolak** → penghuni tidak berpindah (catatan tersimpan).
- Test: `DormitoryPlacementFlowTest` bertambah menjadi **8 test** — termasuk alur mutasi (pending → approve → pindah; reject → tetap) dan mutasi masuk (boarding otomatis + notifikasi petugas) — regresi asrama/WA **23 test / 104 assertion lulus**; `view:cache` sukses.

## 60. Seeder WAJIB Struktur Kurikulum Pondok (SD/SMP/SMA-MA) + Dukungan Peminatan
- **`CurriculumStructureSeeder` (seeder wajib, idempoten)** menyiapkan per satuan pendidikan:
  - **Tingkat + fase**: SD kelas 1–6 (fase A/B/C), SMP 7–9 (D), SMA/MA 10–12 (E/F);
  - **Mapel + rumpun** (agama/tahfidz/bahasa/umum) sesuai struktur pondok;
  - **Alokasi jam per pekan per tingkat** (`grade_level_subjects`), termasuk **peminatan BAHASA/IPA** pada SMA via kolom baru **`track`**.
- **Data terverifikasi di MySQL dev**: SD **40 JP** (k1–k6), SMP **46 JP** (k7–k9), SMA **63 baris alokasi** (tanpa track 47, `bahasa` 10, `ipa` 6) — contoh Matematika Peminatan khusus jalur IPA.
- **Migrasi**: `add_track_to_grade_level_subjects` + perluasan keunikan alokasi menjadi **(grade_level, subject, track)** agar peminatan tidak bentrok.
- **Registrasi**: `CurriculumStructureSeeder` dipanggil dari `DatabaseSeeder` (setelah AcademicYear) — seeder wajib untuk instalasi baru.
- **Kebijakan nama vs jam (dasar berikutnya)**: struktur resmi dari master; satuan pendidikan nantinya hanya dapat **mengubah nama mapel**, **alokasi jam terkunci**.
- Test: `CurriculumStructureSeederTest` (SD 40 JP & SMP 46 JP per tingkat; peminatan IPA terisi; **idempoten**) — 2 test / 15 assertion lulus.

## 61. Menu Struktur Kurikulum (Super Admin) & Mapel Pemadatan (Satuan Pendidikan)
- **Menu Super Admin → Data Global → Struktur Kurikulum**: ringkasan per satuan pendidikan (jenjang, jumlah tingkat, **Mapel Pondok**, **Mapel Pemadatan**, baris alokasi jam) + tombol **"Terapkan Struktur (semua satuan)"** yang menjalankan seeder wajib secara idempoten (data pemadatan tidak tersentuh).
- **Mapel Pemadatan (diatur satuan pendidikan)**: kolom baru `subjects.source` (`pondok` | `pemadatan`) + `category='lokal'`; form per satuan (`subjects/pemadatan/create`) untuk menambah mapel tambahan (mis. **IPS**) **beserta alokasi jam per tingkat yang bebas diatur**; seeder menandai seluruh mapel kurikulum pondok sebagai `source=pondok` (di dev: **168 mapel pondok**, 0 pemadatan).
- **Kebijakan jam terkunci**: untuk mapel **pondok**, satuan pendidikan hanya dapat **mengubah NAMA** — `credit_hours`/kategori dipaksa tetap (guard server-side di `SubjectController::update`); Super Admin bebas penuh. Mapel pemadatan bebas diubah satuan.
- Migrasi `add_source_to_subjects` diterapkan; sidebar Super Admin ditambah entri **Struktur Kurikulum**.
- Test: `CurriculumStructureMenuTest` (seeder menandai pondok; satuan menambah pemadatan + jam; ubah nama mapel pondok sukses sementara kredit/jam tetap) — total **4 test / 21 assertion lulus** termasuk seeder test; `view:cache` sukses.

## 62. Kurikulum Pembelajaran Super Admin — Matriks per Jenjang (Format Tabel Pondok)
- **Menu "Kurikulum Pembelajaran" (Super Admin)** kini diarahkan ke halaman baru **Struktur Kurikulum per Jenjang** — tabel rapi format pondok:
  - **Kolom**: Mata Pelajaran × **Alokasi Waktu Per Pekan** (baris kedua = kolom tingkat).
  - **SD**: I–VI · **SMP**: VII–IX · **SMA**: X, XI (BHS), XI (IPA), XII (BHS), XII (IPA) — peminatan BAHASA/IPA terpisah.
  - Baris **"Jumlah Jam Pelajaran Per Pekan"** per kolom (SD 40, SMP 46, dst.) dan badge jumlah **mapel pemadatan** per jenjang.
- Data diambil dari struktur pondok hasil seeder (agregasi lintas satuan pendidikan per jenjang); guard hanya **Super Admin**.
- Test: `CurriculumMatrixPerJenjangTest` (SD 6 kolom & 40 JP; SMP 3 kolom & 46 JP; SMA 5 kolom; **Matematika Peminatan hanya jalur IPA**) — total 5 test / 36 assertion lulus (bersama test struktur/menu); `view:cache` sukses.

## 63. Matriks Kurikulum per Jenjang — Filter & CRUD Mapel (Super Admin)
- **Filter jenjang**: dropdown **Semua / SD / SMP / SMA** di halaman matriks (`?jenjang=`) — hanya blok jenjang terpilih yang ditampilkan.
- **Tambah / Edit / Hapus mapel langsung dari matriks** (khusus Super Admin): modal dinamis mengikuti kolom jenjang — **termasuk kolom peminatan SMA** (X, XI-BHS, XI-IPA, XII-BHS, XII-IPA); pilihan **rumpun**; perubahan **diterapkan ke seluruh satuan pendidikan pada jenjang tersebut** (struktur pondok konsisten); kolom jam diisi per kolom (0/kosong = tidak diajarkan).
- Kebijakan tetap: mapel **pondok** → satuan pendidikan hanya dapat mengubah **nama**, jam terkunci; **Mapel Pemadatan** diatur satuan masing-masing (tidak tersentuh perubahan struktur).
- Routes baru: `POST /sa/struktur-kurikulum/mapel`, `PUT /sa/struktur-kurikulum/mapel/{code}`, `DELETE /sa/struktur-kurikulum/mapel/{code}`.
- Test: `CurriculumMatrixPerJenjangTest` diperluas (filter jenjang + tambah mapel struktur + **kunci kolom peminatan** untuk modal) — total **6 test / 41 assertion lulus**; `view:cache` sukses.
- **Bugfix**: error JS `Unclosed '[' does not match ')'` pada halaman matriks disebabkan `@json(...)` **multi-baris** di Blade (direktif Blade tidak mendukung argumen multi-baris). Perhitungan kolom dipindah ke controller (`jenjangColumns`) dan Blade memakai `@json($jenjangColumns)` satu baris.
- **UI disamakan dengan pola `gtk/index.blade.php`**: 4 stat card animasi (`card-animate`: jenjang ditampilkan, mapel pondok, mapel pemadatan, total alokasi jam), filter di `card-header py-2 bg-light`, tabel `table-hover align-middle table-freeze` + `thead table-light`, badge jam `bg-primary-subtle`, tombol aksi `btn-soft-primary`/`btn-soft-danger`; fungsi Tambah/Edit/Hapus tetap sama.

## 64. Kebijakan Akses Modul Kurikulum (Guru, TU, KSP/Waka, Kurikulum)
- **Peran baru di `KurikulumAccess`**: `isKurikulumEditor()` (Koor/Tim Kurikulum + Super Admin — pengelola & validasi), `isKurikulumSupervisor()` (KSP & Wakil — lihat semua + validasi, **tidak mengedit**), `isKurikulumTeam()` = editor + pengawas (lihat semua).
- **Middleware baru `kurikulum.access`** (`EnsureKurikulumModuleAccess`) dipasang pada seluruh route grup `/kurikulum/*`: yang boleh masuk = tim kurikulum, guru/GTK pengajar (jabatan `guru*` atau punya penugasan mengajar aktif), Super Admin. **TU/unit non-pendidikan → 403** (menu memang tidak ditampilkan; aturan kini juga ditegakkan di backend).
- **Guru = "Kurikulum Saya"**:
  - Peta Kurikulum hanya menampilkan **mapel yang diampu**.
  - Daftar & filter CP/TP/ATP/PROTA/PROSEM/RPM dibatasi server-side ke **mapel diampu + serumpun** (`viewableSubjectIds()`: penugasan mengajar aktif ∪ mapel dalam rumpun yang sama via `subject_group_id`).
  - Dokumen (PROTA/PROSEM/RPM) mapel lain di luar rumpun → 403 (`canViewSubjectDocs()`).
- **KSP/Waka tidak lagi mengedit**: guard tulis CP/TP/ATP/PROTA/PROSEM/RPM & Master Rumpun dialihkan dari `isKurikulumTeam` → `isKurikulumEditor` (penyusun tetap pemilik dokumen / tim kurikulum).
- **Master Rumpun**: hanya tim kurikulum (view) & editor (kelola) — guru 403; tombol dipindah dari menu "Kurikulum Saya" (guru) ke menu tim kurikulum.
- **Sidebar**: label "RPM / Perangkat" & "Perangkat Pembelajaran" → **"Modul Ajar (RPM)"**; Master Rumpun kini muncul di menu tim kurikulum, bukan guru.
- Test: `KurikulumPembelajaranTest` +4 test kebijakan akses (TU 403; KSP lihat semua tapi tidak mengelola; peta guru hanya mapel diampu; guru melihat CP serumpun, bukan mapel di luar rumpun) — total suite terdampak **103 test / 741 assertion lulus**; `view:cache` sukses.

## 65. Permintaan Akses Edit Dokumen Kurikulum (Keroyokan Terkendali)
- **Kebijakan baru**: dokumen TP/ATP/PROTA/PROSEM/Modul Ajar adalah milik bersama rumpun (satu dokumen dikerjakan bersama), namun hak edit **terkendali**: pengampu mapel, penyusun, dan tim kurikulum otomatis; guru serumpun lain hanya **lihat + salin**, kecuali memperoleh **izin edit**.
- **Fondasi data**: tabel & model `kurikulum_edit_grants` (sekolah + mapel + tahun ajaran; status `pending/approved/rejected/revoked`; pemohon, pemutus, alasan, catatan keputusan). Migrasi `2026_10_11_700000` (sudah diterapkan di MySQL dev).
- **`KurikulumAccess`**: `hasEditGrant()`, `canApproveEditGrant()` (tim kurikulum **atau** pengampu mapel ybs), `canRequestEditGrant()`; `canManageSubject()` kini juga mengakui pemegang izin — otomatis berlaku untuk TP/ATP/PROTA/PROSEM/RPM.
- **Halaman "Permintaan Akses Edit"** (`/kurikulum/akses-edit`, menu di "Kurikulum Saya" guru & "Kurikulum & Perangkat" tim): form pengajuan (mapel serumpun + TA + alasan), daftar permintaan saya, daftar menunggu keputusan (setujui/tolak), daftar akses aktif (cabut).
- **Integrasi UI**: pemegang izin muncul sebagai mampu mengelola di daftar TP (tombol edit/kelola) dan dapat mengedit / membuat versi baru RPM; guard backend tetap sumber kebenaran.
- Test: +2 skenario penuh (`KurikulumPembelajaranTest`) — ajukan → ditolak pemutus tak berwenang → disetujui → kelola TP → dicabut → ditolak lagi; serta pengampu menyetujui permintaan mapelnya sendiri. Total file **15 test / 129 assertion**; suite kurikulum luas **83 test / 563 assertion lulus**; `view:cache` sukses.
- **Berikutnya (menunggu sesi ini)**: fitur **Salin** (TP/ATP/RPM) untuk konteks lain di sekolah yang sama **dan** lintas satuan pendidikan dalam rumpun — konten tersalin dengan penyesuaian waktu/pekan efektif.

## 66. Perbaikan Hasil Uji Coba: SK, Mapel per Kelas, Penugasan Massal & JP Otomatis
- **Bugfix halaman Detail & Cetak SK**: error `Undefined variable $studyGroups` (dan `$matrix`, `teachingHours`, `totalHours` pada cetak). Data matriks pembagian tugas kini dibangun di controller (`InstitutionDecreeController::buildDecreeMatrixData`) dan dipakai bersama oleh show & print; kondisi kartu matriks diperluas (SK Pembagian Tugas **atau** sudah punya penugasan).
- **"Data Kelas & Mapel"** ditambahkan ke menu Kurikulum satuan (sebelumnya hanya ada di bagian Peserta Didik sehingga sulit ditemukan): pilih tingkat → daftar mapel + JP, tambah/hapus mapel per kelas (halaman `grade-levels.show` yang sudah ada).
- **Halaman Mata Pelajaran**: banner penjelasan — mapel dikelola **per satuan pendidikan** ("Adab" wajar muncul beberapa kali bila melihat banyak sekolah) + **filter sekolah** untuk pengguna lintas sekolah + tautan ke Data Kelas & Mapel.
- **JP otomatis**: form Tambah/Edit Penugasan Guru kini mengisi `weekly_hours` **otomatis dari struktur kurikulum** (`grade_level_subjects` mapel × tingkat) saat mapel & kelas dipilih — tetap bisa disesuaikan.
- **Input massal penugasan**: tombol **"Isi Massal (Matriks SK)"** pada halaman Plotting Guru Mengajar mengarahkan ke alur SK → *Edit Isi Tabel* (matriks inline yang sudah ada, isi jam per guru × mapel × kelas sekaligus). Penugasan yang dibuat otomatis menyiapkan mapel per rombel (`SubjectAssignedToStudyGroup` → provisioning job) sebagai bahan generator jadwal.
- Catatan mapel **SK/SKI**: tidak ada di struktur pondok bawaan — tambahkan sebagai mapel pondok via Super Admin (Kurikulum Pembelajaran → Tambah Mapel) atau sebagai **Mapel Pemadatan** satuan (Mata Pelajaran → Tambah), lalu daftarkan ke tingkat di **Data Kelas & Mapel**.
- Test: `KurikulumMasterUiTest` +1 (halaman detail & cetak SK tampil matriks) — **15 test / 89 assertion**; suite kurikulum terkait **51 test / 366 assertion lulus**; `view:cache` sukses.

## 67. Privasi Antar Sekolah, Peta Mapel per Tingkat & Input Massal Penugasan
- **Privasi antar sekolah** (hanya Super Admin & Pimpinan/pemegang `view_global_school_data`):
  - Dropdown & daftar pada halaman **Plotting Guru Mengajar** (index/create/edit/bulk) dan **Tugas Tambahan** kini dibatasi ke satuan pendidikan pengguna.
  - `bulkStore` memvalidasi kepemilikan sekolah untuk **setiap baris** (mapel, rombel, guru) — menolak data lintas sekolah (403); store satuan tetap memaksa `school_id` dari konteks.
  - Filter sekolah di halaman Mata Pelajaran hanya tampil untuk viewer lintas sekolah (`isGlobalView`).
  - Catatan: izin `view_global_school_data` saat ini juga dipegang role **Humas Personalia** (selain Pimpinan & Super Admin) — menunggu keputusan apakah dipertahankan.
- **Peta Mapel per Tingkat** (`/subjects/peta`, menu baru di Kurikulum): matriks **mapel × tingkat** dengan JP per pekan, total JP per tingkat, badge pemadatan, tautan "kelola" per kelas (Data Kelas); school selector untuk viewer lintas sekolah. Banner halaman Mata Pelajaran diarahkan ke peta ini.
- **Input Massal Penugasan** (`/teaching-assignments/bulk`, tombol "Input Massal (Tabel)" di halaman Plotting): tabel multi-baris (Guru × Mapel × Kelas × JP) dalam satu submit; **SK opsional** — bila dikosongkan sistem membuat *SK Pembagian Tugas (Input Massal)* otomatis; JP terisi otomatis dari struktur kurikulum; baris duplikat dilewati dengan laporan jumlah.
- Halaman bulk juga menjelaskan **jabatan** (Kepala/Wakil/TU → Data GTK/Kepegawaian) dan **tugas tambahan** (koordinator, wali kelas → menu Tugas Tambahan).
- Test: `KurikulumMasterUiTest` +3 (peta mapel, dropdown tidak bocor lintas sekolah, input massal + SK otomatis) — **18 test / 102 assertion**; suite kurikulum terkait **83 test / 530 assertion lulus**; `view:cache` sukses.

## 68. Koreksi Uji Coba: Hapus Peta Mapel, Perbaikan Input Massal & Aturan Satu Mapel Satu Guru
- **Peta Mapel per Tingkat dihapus** (redundan dengan Peta Kurikulum yang sudah ada): menu, route, halaman, dan test dibersihkan; tautan di halaman Mata Pelajaran diarahkan kembali ke **Peta Kurikulum**.
- **Input massal diperbaiki**:
  - Daftar **guru** kini memuat seluruh GTK pengajar satuan (sebelumnya bergantung izin `general_teacher.readable` yang bisa kosong).
  - Daftar **rombel** dimuat lintas tahun ajaran dan **difilter otomatis mengikuti Tahun Ajaran yang dipilih** di form (termasuk saat TA diganti tanpa reload).
- **Aturan "satu mapel hanya satu guru per rombel"** (kecuali **Tahfidz/Hafalan & Hadits** — boleh banyak guru karena berkaitan dengan hafalan) lewat `Subject::allowsMultipleTeachers()`:
  - **Input massal**: baris bentrok ditolak; laporan menyebut jumlah disimpan / dilewati / ditolak.
  - **Form tunggal** (store & update): diblokir dengan pesan nama guru yang sudah mengajar.
  - **Matriks SK**: guru pertama yang diberi jam pada pasangan mapel+rombel menang; guru berikutnya dilewati.
  - **UI input massal**: baris bentrok ditandai **merah** + catatan "Sudah diajar oleh …", duplikat antar-baris ditandai **kuning**.
- Test: `KurikulumMasterUiTest` — **18 test / 107 assertion** (termasuk uji blokir ganda, pengecualian Tahfidz, dan dropdown guru/rombel tampil); suite kurikulum terkait **85 test / 566 assertion lulus**; `view:cache` sukses.

## 69. Perbaikan Dropdown Input Massal (Guru & Rombel) — Akar Masalah Data
- **Akar masalah guru**: `gtk_employments.school_id` **kosong di data nyata** — relasi guru–satuan pendidikan sebenarnya melalui **Work Unit** (`gtk_work_unit.work_unit_id` ↔ `schools.work_unit_id`). Daftar guru input massal kini: Work Unit → kepegawaian → fallback semua GTK pengajar (tidak pernah kosong); peta sekolah guru untuk filter lintas-sekolah juga dihitung via Work Unit.
- **Akar masalah rombel**: default Tahun Ajaran memakai TA **aktif (2025/2026)** padahal rombel berada di TA **2026/2027** → tersaring habis oleh filter JS. Kini **default TA mengutamakan TA yang benar-benar punya rombel**; filter rombel per-TA diberi **fallbackAll** sehingga daftar tidak pernah kosong; label rombel menyertakan nama TA.
- **Validasi simpan dilunakkan**: penautan guru via Work Unit/kepegawaian; bila data penautan tidak ada, baris tidak diblokir. Mapel & rombel tetap divalidasi ketat milik sekolah (anti data lintas sekolah).
- **Empty-state jelas** di halaman input massal: belum ada rombel → pesan + tautan **Pengaturan Rombel**; belum ada mapel → tautan **Mata Pelajaran/Data Kelas**; belum ada GTK → pesan.
- Test: `KurikulumMasterUiTest` **18 test / 107 assertion**; suite kurikulum terkait **81 test / 515 assertion lulus**; `view:cache` sukses.

## 70. Data GTK Kosong untuk Akun Satuan Pendidikan — Tiga Akar Masalah
- **Scoping salah kolom**: daftar GTK disaring via `gtk_employments.school_id` yang **kosong di data nyata**. Tautan guru–sekolah sebenarnya lewat **Work Unit**. Ditambahkan `SchoolGroupService::userIdsForSchool()` (Work Unit ↔ sekolah + kepegawaian, fallback aman) dan dipakai konsisten.
- **Filter Guru/Tendik memakai nilai `jenis_gtk` kaku** (`'Guru'` / `'Tenaga Pendidik Pondok'`) padahal data nyata **`Pendidik / Guru`** → daftar **Guru kosong** dan **Tendik berisi semua**. Kini klasifikasi: Guru = `jenis_gtk LIKE %guru%/%pendidik%` **atau** jabatan `%guru%`; Tendik = kebalikannya.
- **Daftar guru bersandar izin `general_teacher.readable` yang kosong di data** (0 pemegang) pada: SK create, Tugas Tambahan (2 halaman), Input Massal. Diganti basis "semua GTK pengajar minus non-eligible", tetap di-scope ke satuan.
- **Statistik GTK** (`getStatistics`) ikut di-scope ke sekolah konteks (sebelumnya jatuh ke hitungan global saat `employment.school_id` null).
- **Sidebar**: menu **Data GTK** kini tampil juga untuk **KSP/Waka (struktural), Kurikulum/Tim Kurikulum, Koordinator (termasuk Kesiswaan), dan TU** — bukan hanya Kepala TU.
- Diterapkan pada: GtkWizard (index/indexguru/indextendik/statistik), SK create, OtherTeacherTask, GtkAdditionalTask, StudyGroupSubject, GradeLevel API, Waka stats.
- **Verifikasi data nyata (SD IT Putra)**: roster 53 GTK → daftar **Guru 51**, **Tendik 2** (sebelumnya Guru 0).
- Test: `KurikulumMasterUiTest` +1 (GTK tampil via work unit) — **19 test / 111 assertion**; suite terkait **82 test / 519 assertion lulus**; `view:cache` sukses.

## 71. Dropdown Pencarian Nama & Master Nama Tugas Tambahan
- **Dropdown nama bisa dicari (Select2)** — pustaka memang sudah tersedia global di layout:
  - **Input Massal Penugasan**: guru, mapel, dan kelas/rombel kini bisa dicari; tetap berfungsi saat baris ditambah/diubah dan saat Tahun Ajaran diganti (options dibangun ulang + `trigger('change')`; event Select2 dipetakan ke pengisian JP otomatis & validasi anti-ganda).
  - **Form Penugasan tunggal** (tambah/edit) dan **filter guru** di halaman Tugas Tambahan.
  - **Tugas Tambahan GTK** (index/create/edit).
- **Nama tugas tambahan kini dropdown dari master** `additional_task_types` (bukan isian bebas):
  - Halaman **Tugas Tambahan** (satuan): opsi dari master; `task_code` terisi otomatis dari pilihan.
  - Halaman **Tugas Tambahan GTK**: input+datalist diganti dropdown master (nilai lama yang tidak ada di master tetap ditampilkan agar data historis tidak hilang; fallback daftar bawaan bila master kosong).
- **Seeder `AdditionalTaskTypeSeeder` diperbaiki**: nama `jenis_gtk` di data nyata lebih panjang (mis. "Tenaga Kependidikan Satuan Pendidikan") sehingga pencocokan kini memakai `LIKE`; di DB dev master terisi **21 jenis tugas** (Wali Kelas, Koordinator Guru/Tim Kurikulum, Wali Kamar, Admin UKS, Koordinator Alumni, dst.).
- Test: `KurikulumMasterUiTest` +1 (dropdown master tugas + pencarian tampil) — **20 test / 119 assertion**; suite terkait **83 test / 527 assertion lulus**; `view:cache` sukses.

## 72. Tugas Tambahan per Unit & Pilihan Rombel/Kamar
- **Jenis tugas tambahan difilter sesuai unit**: di halaman **Tugas Tambahan satuan pendidikan** hanya tampil jenis dari master `additional_task_types` yang terkait jenis GTK **"…Satuan Pendidikan…"** — tugas asrama (mis. Wali Kamar) tidak lagi tercampur di sana.
- **Wali Kelas → pilihan rombel**: saat memilih tugas yang berbasis kelas (mis. "Wali Kelas"), muncul **dropdown Rombel/Kelas** (bisa dicari) di bawah pilihan tugas; **wajib diisi** (validasi backend + JS); rombel divalidasi milik satuan pendidikan; daftar tugas menampilkan **badge rombel** (mis. *Wali Kelas · VII A*).
- **Wali Kamar → pilihan kamar**: sudah tersedia di **Asrama → Wali Kamar** (`RoomSupervisor`: GTK + Kamar + TA + status). Pada form **Tetapkan/Edit Wali Kamar** ditambahkan **pencarian (Select2)** pada dropdown GTK dan Kamar.
- Master tugas di dev kini 21 jenis (termasuk 'Wali Kelas' di satuan; 'Wali Kamar' di Keasramaan).
- Test: `KurikulumMasterUiTest` +1 (filter satuan + Wali Kelas wajib rombel + tersimpan) — **21 test / 125 assertion**; suite terkait **84 test / 533 assertion lulus**; `view:cache` sukses.

## 73. Generate Jadwal: TA Otomatis Benar & Default Seluruh Sekolah
- **Akar "Belum ada SK"**: halaman generate mengunci TA ke **TA aktif (2025/2026)** padahal penugasan berada di **TA 2026/2027** (terkonfirmasi di data: 4 penugasan di 2026/2027). Halaman generate kini memilih TA berjenjang: **pilihan pengguna → TA yang punya penugasan → TA aktif**, plus **pemilih Tahun Ajaran** di halaman; semester mengikuti TA tersebut; ringkasan penugasan & tugas tambahan mengikuti TA terpilih.
- **Label diperjelas**: "Belum ada SK" → **"Belum ada penugasan"**; kolom "Mapel (SK)/Total JP (SK)" → **"(Penugasan)"** (sumber jadwal = penugasan mengajar dari SK/pembagian tugas). Diberi alert **kesiapan**: "n rombel siap digenerate" atau tautan ke Plotting/Input Massal bila belum ada penugasan.
- **Generate sekolah-wide jadi default**: seluruh rombel **tercentang otomatis** dan tombol utama menjadi **"Generate Jadwal Sekolah"** — satu kali klik menyusun jadwal **semua rombel & mapel** sekaligus (memakai `generateBulk` yang sudah ada). Tombol per-rombel tetap tersedia untuk generate ulang sebagian (konfirmasi diperbarui).
- `generateSingle` (generate per rombel) juga memakai TA terpilih, bukan hanya TA aktif.
- Test: `KurikulumMasterUiTest` +1 (default TA berpenugasan + seluruh rombel terpilih) — **22 test / 129 assertion**; suite terkait **85 test / 537 assertion lulus**; `view:cache` sukses.

## 74. Buku Nilai (Observer Penugasan), Pindah Kepemilikan, Notifikasi Individu & Jadwal Hari×Jam
- **Akar buku nilai kosong**: `TeachingAssignmentObserver` **tidak pernah didaftarkan** sehingga penugasan mengajar (Input Massal/SK matrix) tidak memicu provisioning buku nilai. Kini terdaftar — penugasan → observer → provisioning job (sync) → `teacher_admin_books` + placeholder nilai. **Command backfill** `akademik:provision-books` dibuat & dijalankan di dev (4 buku terbentuk dari 4 penugasan).
- **Pindah kepemilikan buku nilai (ganti guru di tengah tahun)**: saat guru pengganti ditugaskan atau penugasan dihapus, `teacher_admin_books.teacher_id` **berpindah** ke guru aktif (data nilai/placeholder **tidak direset**); kolom `teaching_id` tidak lagi diisi karena FK legacy ke tabel `teachings` (termasuk perbaikan di `updateMatrix`).
- **Notifikasi individu** via topbar (plus suara):
  - **Penugasan mengajar** baru/diubah → guru penerima (aksi "Buka Buku Nilai").
  - **SK** diterbitkan/diperbarui → guru yang punya penugasan aktif pada SK tsb.
  - **Tugas tambahan** (satuan & Tugas Tambahan GTK) baru/diubah/dihapus → GTK ybs.
  - **Perubahan jabatan/kepegawaian** → GTK ybs.
  - Login sudah berjalan (`SendLoginNotification`).
- **Jadwal hari × jam**: halaman detail jadwal rombel kini menampilkan **matriks: baris = hari, kolom = jam pelajaran** (dengan rentang waktu di header) dan isi sel nama mapel + guru — sesuai bentuk "tabel hari dan jam" yang diharapkan; generator tetap sekolah-wide (default semua rombel).
- Test: `KurikulumMasterUiTest` +2 (buku nilai & notifikasi dari penugasan; pindah kepemilikan saat guru diganti) — **24 test / 136 assertion**; `AcademicServerSideAuthorizationTest` disesuaikan (idempoten dengan provisioning); suite terkait **95 test / 621 assertion lulus**; `view:cache` sukses.

## 75. Jadwal (UI Generate, Tabel Hari×Jam) & Buku Nilai (Nilai Murni STS/SAS, Tambah Kolom)
- **UI generate dirapikan**: pemilih TA dalam kartu ringkas, penghitung **"n terpilih dari N rombel"**, info kesiapan yang rapi; tombol utama tetap **"Generate Jadwal Sekolah"** (seluruh rombel tercentang otomatis).
- **Hasil jadwal kini berbentuk tabel sesuai permintaan**: **kolom = hari (Senin–Sabtu), baris = jam pelajaran** dengan kolom "Waktu"; baris **istirahat/kegiatan** ikut tampil dari master **Jam Pelajaran** (label per hari, mis. Istirahat/Upacara/Literasi/Pulang bila diisi di master slot dengan `is_break`), sel terisi mapel + guru. TA pada halaman detail otomatis memilih TA yang punya jadwal/penugasan (bukan hanya TA aktif).
- **Buku nilai — tambah kolom sumatif diperbaiki**: server menerima ID kolom aman (bukan hanya UUID), frontend memakai UUID fallback yang valid, dan pesan kesalahan HTTP ditampilkan jelas (sebelumnya gagal senyap).
- **Aturan Nilai Murni STS/SAS** (nilai hasil ujian tanpa elemen lain):
  - Kolom baru **"Nilai Murni STS"** & **"Nilai Murni SAS"** pada grid (migrasi `add_murni_to_admin_nilai_sumatif`).
  - **Kolom STS/SAS terkunci sampai Nilai Murni terisi** — penguncian di tampilan (readonly + auto-isi bantuan) **dan ditegakkan di backend** dengan pesan jelas ("Isi Nilai Murni STS (hasil ujian) terlebih dahulu…").
- Test: `BukuNilaiKonsolidasiTest` +2 (aturan murni STS; kolom tambahan id aman) — **6 test / 33 assertion**; suite terkait **97 test / 628 assertion lulus**; `view:cache` sukses; migrasi diterapkan ke MySQL dev.

## 76. Fix 403 Buku Nilai, Rapikan Generate/Jurnal & Seeder Rombel + Tautan Mapel
- **403 "Gagal menyimpan kolom"**: penyebabnya aturan arsip membandingkan buku dengan **TA aktif** (2025/2026) padahal buku ada di **TA operasional** (2026/2027, TA berpenugasan). Kini tulis diizinkan bila TA buku = **TA aktif ATAU TA operasional** (TA terbaru yang punya penugasan aktif di sekolah tsb); buku arsip lama tetap 403.
- **UI Generate dirapikan**: duplikat "Tahun Ajaran/Semester" dihapus — pemilih TA **dan Semester** kini di kartu atas (keduanya reload via GET), form POST memakai hidden TA/semester + ringkasan, opsi "Timpa jadwal" dipindah ke footer dekat tombol.
- **Jurnal Pembelajaran (Realisasi) dirapikan**: filter tahun/semester jadi ukuran kecil, tabel memakai sticky header (`table-freeze`), tombol reset konsisten.
- **Seeder wajib baru `SchoolRombelStructureSeeder`** (terdaftar di `DatabaseSeeder`, idempoten):
  - **SD**: 6 tingkat × 3 rombel (A–C) · **SMP**: 3 tingkat × 5 rombel (A–E) · **SMA/MA**: 3 tingkat × 3 rombel (A–C) — per satuan pendidikan, pada **TA operasional** (TA dengan rombel/penugasan terbanyak; fallback TA aktif).
  - **Langsung menghubungkan setiap mapel tingkat ke tiap rombel** (`grade_level_subjects` → `study_group_subjects`, JP mengikuti alokasi tingkat).
  - Hasil di dev: **102 rombel + 1.712 tautan mapel–rombel** (TA 2026/2027); contoh rombel 1A = 13 mapel, 7A = 16 mapel.
- Test: suite terkait **93 test / 577 assertion lulus**; `view:cache` sukses.

## 77. Penugasan Guru Otomatis & UI Jurnal (w2)
- **Command baru `akademik:assign-teachers`** — membuat penugasan mengajar otomatis dari data yang ada (rombel × mapel-rombel):
  - Opsi: `--school=` (batasi sekolah), `--ay=` (TA; default TA operasional), `--with-books` (sekalian provisioning buku nilai).
  - **Preferensi jabatan per mapel**: Tahfidz→Guru Tahfidz, Hadits→Guru Hadits, Arab/Qowaid→Guru Bahasa Arab, Fiqih/Akidah/Adab/SKI/Qur'an→Guru Agama, lainnya→Guru Umum/Kelas/Mapel; **round-robin beban terendah**; **SK otomatis per sekolah+TA**; **idempoten** dan menjaga **satu mapel satu guru per rombel**.
  - Observer penugasan memakai mode **senyap** (tanpa ledakan notifikasi) & provisioning dapat dimatikan saat seeding.
  - **Hasil di dev**: **1.712 penugasan baru** (total 1.716), **0 duplikat** mapel/rombel, seluruh **1.712 tautan SGS terisi guru**; beban terdistribusi (tertinggi 128/124, rata-rata ±32).
  - **Buku nilai**: `akademik:provision-books --school=…` → **256 buku** untuk SD IT Putra (termasuk buku Jurnal milik akun penguji, semester ganjil).
- **UI halaman Jurnal Pembelajaran (w2) dirapikan**: header info jadi badge rapi (mapel/rombel/semester/TA), 4 kartu statistik konsisten (ikon + progress realisasi TP), form terbagi 3 seksi (**Pertemuan → Rencana (PROSEM→TP→RPM) → Materi & Verifikasi**), tabel riwayat dengan hover + sticky header + empty-state, dan pemilih buku/Realisasi tetap.
- Test: `KurikulumMasterUiTest` +1 (command penugasan dari SGS) — suite terkait **98 test / 631 assertion lulus**; `view:cache` sukses.

## 78. Hapus Rombel Aman (FK), Fix Query TA & Nama Rombel A/B/C per Tingkat
- **Hapus rombel error `1451` (FK `jadwal_kbms`)**: ditambahkan `StudyGroupService::deleteSafely()` — membersihkan dependen **restriktif** (jadwal_kbms, riwayat kenaikan kelas, agenda attendee) lalu menghapus rombel; sisanya cascade (penugasan, mapel-rombel, buku nilai, absensi, dll.). Controller `destroy` memakai service + pesan ramah bila masih gagal. Test baru: hapus rombel yang punya jadwal → sukses.
- **Fix `1054 Unknown column school_id`**: `AcademicYear::where('school_id', …)` pada `KisiKisiController` (2 lokasi) diganti query yang benar (`academic_years` tidak punya kolom school_id).
- **Nama/kode rombel cukup A/B/C per tingkat**: unique lama `(school, TA, name)` diganti **`(school, TA, grade_level, name)`** (migrasi baru; index baru ditambah lebih dulu agar FK tetap punya index pendukung). Seeder rombel diperbarui: nama & kode = **A/B/C**, mengadopsi rombel lama bernama sama (menghapus legacy bentrok dengan aman) dan me-*rename* pola lama `1A/7A/10A` → `A`. Hasil di dev: **102 rombel**, **0 duplikat** per (sekolah, TA, tingkat, nama) — tampilan kini **"Kelas 1 A"**, bukan "Kelas 1 1A".
- Test: `KurikulumMasterUiTest` +1 — suite terkait **99 test / 634 assertion lulus**.

## 79. Akar Kekurangan JP Jadwal (Master Jam & Beban Guru) + Verifikasi Peta Kurikulum
- **Akar kekurangan JP** ditemukan dua: (1) master jam pelajaran hanya **6 baris (hanya Senin)** sehingga generator kehabisan slot; (2) **fallback penugasan lintas sekolah** membuat 53 guru SD menanggung 8 sekolah (≈4.600 JP) — mustahil dijadwalkan tanpa bentrok.
- **Seeder baru `ClassScheduleSlotSeeder`** (terdaftar di `DatabaseSeeder`, idempoten): grid default per hari — kegiatan pagi (Upacara/Literasi/Senam/Agama/Yasinan), **Jam 1–9**, istirahat, ishoma, dan Pulang; **SD Senin–Jumat (45 slot mengajar)**, **SMP/SMA Senin–Sabtu (54 slot)**.
- **Command penugasan diperkuat**: `--prune-foreign` (hapus penugasan yang gurunya bukan staf sekolah), `--rebalance --max-jp=24` (sebar beban), `--fallback-all` (mode demo lintas sekolah, default nonaktif). Observer `deleted` kini mengirim `null` (fix `SerializesModels` saat model sudah terhapus).
- **Hasil di dev**: 1.459 penugasan lintas sekolah dihapus → sisa **252 (SD IT Putra)**; **0 guru > 24 JP** (tertinggi 23). **Regenerate SD IT Putra: 714/720 slot (99,2%)** — hanya **4 baris kekurangan** (bentrok guru spesifik), dari sebelumnya hampir seluruh mapel kurang.
- **Peta kurikulum per jenjang diverifikasi** dengan data nyata: halaman *Super Admin → Kurikulum Pembelajaran* (`/{userId}/sa/struktur-kurikulum/per-jenjang`) render OK — tabel "Alokasi Waktu Per Pekan" ada (SD 16 mapel, SMP 18, SMA 25); halaman peta satuan juga render OK.
- Test: batch terkait **50 test / 246 assertion lulus**.

## 80. Peta Kurikulum per Tingkat (Filter) & Fix Error "name on null"
- **Filter per tingkat di Peta Kurikulum satuan** (`/{userId}/kurikulum`): dropdown **"Semua Tingkat / Kelas 1, 2, 3, …"** — memilih satu tingkat menampilkan tabel tingkat itu saja (tingkat lain disembunyikan); kombinasi dengan filter TA/semester.
- **Fix halaman peta kosong**: default Tahun Ajaran kini **TA yang punya penugasan (operasional)** → TA aktif → terbaru (sebelumnya terkunci ke TA aktif 2025/2026 yang belum punya data, sehingga guru melihat 0 mapel). Terverifikasi: TA terpilih **2026/2027**, guru (WILDAN) melihat **8 mapel**, filter **Kelas 1 → 1 baris**.
- **Fix error `Attempt to read property "name" on null`** pada `super-admin/curriculum-structure/index.blade.php`: baris sekolah kini memakai guard `$row['school']?->name ?? '—'` (dan `school_level`), sehingga tabel tetap tampil walau ada baris tanpa data sekolah.
- Test: batch kurikulum terkait **57 test / 332 assertion lulus**; `view:cache` sukses.

## 81. Perbaikan Lanjutan Satuan: Generate, Tingkat, Rombel-GTK, Wali Kelas, Urutan, Bank Soal
- **Generate jadwal tanpa pesan**: halaman generate kini menampilkan **alert validasi & error** (sebelumnya gagal senyap), dan `generate()` menangkap exception → pesan jelas.
- **Filter tingkat jadi filter cepat**: Peta Kurikulum memakai badge cepat (Semua, Kelas 1, 2, …) — bukan dropdown lagi.
- **Form rombel (tambah/edit)**: daftar **GTK/Wali Kelas kini terisi** (roster sekolah via work unit/kepegawaian + fallback) dan **dimuat otomatis** saat halaman dibuka untuk akun satuan; dropdown memakai **pencarian (Select2)**.
- **Wali Kelas ↔ rombel tersambung**: menetapkan tugas tambahan **"Wali Kelas" + rombel** otomatis menyetel `study_groups.homeroom_teacher_id`; memindahkan/menghapus tugas membersihkan wali lama.
- **Urutan rombel alami**: scope `StudyGroup::orderedByGrade()` (subquery level + nama) diterapkan di daftar rombel, halaman generate, tugas tambahan, dan input massal → 1A, 1B, 1C, 2A, … (bukan acak).
- **Fix `1054 Unknown column school_id`** di `BankSoalController` (satu-satunya sisa; telah dipastikan via grep).
- **Dokumen baru `docs/ALUR-SATUAN-PENDIDIKAN.md`**: pemetaan alur santri masuk → penempatan → penugasan → KBM → penilaian → naik kelas/lulus + daftar celah (kelulusan formal, PPDB, strukturisasi sidebar).
- Test: batch terkait **63 test / 355 assertion lulus**.

## 82. Restrukturisasi Sidebar Satuan Pendidikan (Struktur Baru Disetujui)
- **Struktur baru 14 bagian**: Menu · Dashboard (per jabatan) · **KBM Saya** · **Kesiswaan** · **Akademik** · **Bank Soal & Asesmen** · **GTK & Tugas** · Ekstrakurikuler · Laboratorium & Sarpras · Keuangan Sekolah · Administrasi · Monitoring Kehadiran · Absensi Kehadiran · Referensi · Laporan.
- **Duplikat dirapikan** (tanpa menghilangkan akses role): Kaldik 5→2 (Referensi + tim), Data Santri 4→2 (data + daftar dinamis per rombel), Pelanggaran 3→1, QR guru 3→1, Bank Soal 3→1 submenu, Prestasi 4→1 submenu, SK 2→1, Dokumen ISO 2→1, Jadwal 2→1, Alumni 2→1, Sarpras TU 2→1.
- **Bagian baru "Bank Soal & Asesmen"** (Guru, Koor Mapel, Kurikulum, TU) — memuat Kisi-Kisi, Bank Soal, Repository, Review Serumpun, Paket Soal, Cetak Paket Final — agar dedupe tidak menghilangkan akses peran.
- Seluruh tautan, kondisi jabatan/tugas, gating (`menu_allowed`), dan bagian dinamis (wali kelas, daftar rombel) dipertahankan; dokumen alur di `docs/ALUR-SATUAN-PENDIDIKAN.md` diperbarui sebagai acuan.
- Verifikasi render langsung: **Guru OK, Tata Usaha OK, Kepala OK**; suite terkait **103 test / 660 assertion lulus**.

## 83. Perketat Sidebar per Jabatan + Label Kelas Pekan Efektif
- **Sidebar kembali ketat sesuai jabatan/tugas** (hasil audit gate terlalu longgar):
  - **Kesiswaan**: Data Santri/Mahrom/Import/Alumni/Pengaturan Rombel/**daftar Santri per Rombel** hanya untuk **TU, Struktural, Koor/Tim Kesiswaan** (wali kelas hanya melihat blok kelasnya sendiri: konsolidasi nilai, rekap absensi, santri kelas saya).
  - **GTK & Tugas**: Data GTK dibatasi (TU, struktural, tim kurikulum, koor mapel); **Tugas Tambahan** (TU, struktural, tim kurikulum); Pengajuan GTK (TU).
  - **Laboratorium & Sarpras**: TU hanya melihat **Dashboard Sarpras**; item lengkap hanya Koor Lab/Sarpras.
  - Guru biasa tetap hanya: KBM Saya, Bank Soal & Asesmen, Absensi, Referensi (tidak ada menu manajerial).
- **Filter kelas Pekan Efektif** kini menampilkan nama lengkap (**"Kelas 1 A"**), bukan kode "A".
- Catatan: saat login **Super Admin** (`showAll`), seluruh menu memang tampil — gunakan akun satuan untuk melihat menu per jabatan.
- Test: batch render terkait **32 test / 173 assertion lulus**; `view:cache` sukses.

## 84. KKTP di Peta Kurikulum + Total JP per Tingkat (Matriks SA Dikembalikan)
- **KKTP diatur langsung di Peta Kurikulum** (satuan, `/{userId}/kurikulum`): kolom **N-KKTP** per (mapel × tingkat) + tombol **Simpan N-KKTP**; hak atur: tim kurikulum & koordinator mapel (lainnya read-only). Route `user.kurikulum.kktp.save`.
- **Total JP per tingkat** ditampilkan sebagai badge di Peta Kurikulum (dari struktur; track dihitung sekali via nilai maksimum per mapel).
- **Menu "KKTP" berdiri sendiri dihapus** dari sidebar satuan & dua sidebar tugas tambahan (koordinator kurikulum, koor mapel).
- **Catatan**: percobaan menaruh input KKTP di matriks **Struktur Kurikulum (Super Admin) dikembalikan** ke bentuk sebelumnya atas permintaan — halaman matriks kini tanpa input KKTP (render identik dengan versi sebelumnya) dan route/method KKTP SA dihapus. KKTP kini hanya di Peta Kurikulum.
- Test: batch terkait **45 test / 297 assertion lulus**; `view:cache` sukses.

## 85. Wizard Leger: Sheet Leger SAS + Kolom Lengkap Sheet per-Mapel
- **Halaman Leger rombel** (`/{userId}/schools/nilai-kelas/{rombel}/sts`) kini benar-benar berlapis sheet: tab atas **Leger + tab per-mapel**, dan di dalam tab Leger ada **pill "Leger STS | Leger SAS"**.
- **Leger SAS (baru, baca-saja)**: matriks santri × mapel yang menampilkan kolom `sas`; **Jumlah, Rata-rata, Peringkat, Predikat** dihitung dari nilai SAS; **Absensi S/I/A** tetap dari presensi harian semester. Input SAS tetap milik guru mapel di Buku Nilai (wizard 3) — halaman ini hanya menampilkan.
- **Sheet per-mapel dilengkapi** sesuai spesifikasi leger: Asesmen Sumatif Harian (S1–S6 + kolom dinamis) → **RS (%)** → **STS (%)** → **SAS (25%)** → **RSA** → **NR Murni** → **Penghargaan Akademik** (Jujur, Disiplin, Peduli, Adab, Kehadiran, Keaktifan — read-only dari w5) → **NR Final** → **Penilaian Asesmen Formatif** (LKPD, Diskusi, Kuis, Antar Teman + RATA-RATA — read-only dari w4).
- Judul halaman & catatan kaki **dinamis per sheet** ("Leger Nilai STS"/"Leger Nilai SAS"); parameter `userId`/`studyGroupId` di link pill ikut disertakan (menghindari error rute).
- Test regresi baru `test_sheet_leger_sas_dan_kolom_lengkap_mapel_render`: mode SAS terbukti **read-only** (tanpa input `leger_sts`), mode STS tetap editable, kolom SAS/RSA/NR Murni/Penghargaan/Formatif tampil.
- Batch: `SumatifHarianDinamisTest` **11 test / 82 assertion**, `BukuNilaiKonsolidasiTest` + `PesertaDidikP0Test` — semua lulus; `view:cache` sukses.
- **Catatan terbuka**: kolom **Pembiasaan Pagi (Do'a/Hiwar/Conversation)** dan **Tahfidz (Juz/Halaqoh)** pada Leger SAS belum diisi — menunggu konfirmasi sumber data (modul Tahfidz sudah ada: `TahfidzJuzProgress`/`TahfidzSetoran`; Pembiasaan Pagi belum ada modulnya).

## 86. Leger SAS Lengkap (Pembiasaan + Tahfidz + Grup Agama/Umum) & Sidebar Leger
- **Kolom baru di Leger SAS**: grup **PEMBIASAAN PAGI** (Do'a, Hiwar, Conversation dari `pembiasaan_pagi`) dan **TAHFIDZ** (Juz dari `tahfidz_juz_progress`; Halaqoh dari `tahfidz_group_members` ⨝ `tahfidz_groups`), hanya muncul di sheet SAS.
- **Header grup tegas "AGAMA | UMUM"** di tabel leger (STS & SAS): mapel dikelompokkan mengikuti aturan urutan lama (Agama/Arab/Hadits = AGAMA, sisanya UMUM) dengan warna pembeda; kolom SAS baru ikut terhitung di baris KKTP & baris kosong.
- **Fix bug nyata `PembiasaanPagi`**: model tidak men-generate UUID → `updateOrCreate`/simpan pembiasaan gagal (`NOT NULL constraint on id`). Kini memakai hook `creating` (pola `StudyGroup`).
- **Sidebar "Leger & Cetak Rapor" diperbaiki**: wali kelas → langsung ke leger rombelnya; TU/struktural/kepala → `nilai-kelas.index` yang kini **redirect ke rombel pertama** (sebelumnya `abort(404)` untuk non-wali, dan link wali memakai atribut `->uuid` yang tidak ada).
- **Pemilih kelas (rombel) di halaman leger** untuk TU/struktural/kepala — pindah kelas langsung dari baris filter; wali kelas tetap terpaku ke rombelnya.
- Test baru: grup & kolom SAS/STS (Pembiasaan/Tahfidz/AGAMA/UMUM) + index wali mengarah ke leger; `SumatifHarianDinamisTest` **13 test / 99 assertion lulus**; `view:cache` sukses.
- Catatan tindak lanjut: leger **cetak/PDF** belum ikut memuat kolom Pembiasaan/Tahfidz & grup Agama/Umum (menyusul bila disetujui).

## 87. Gating Akses Leger: Wali Kelas Hanya Kelasnya; KSP/Waka/Kurikulum/TU Semua Kelas
- **Helper akses di `NilaiKelasController`**: `legerAccess()` → `'full'` (KSP/Kepala, Waka, Koordinator/Tim Kurikulum, TU, Super Admin, atau pemegang permission `nilaikelas-*`) · `'wali'` (rombel yang diwalikan) · `'none'`.
- **Gerbang dipasang di semua rute modul leger**: `sts`, `sts.store`, `leger.cetak`, `leger.download`, `rapor`, `rapor.note`, `rapor.cetak`. Guru non-wali → **403**; wali kelas lain → **403**; pengunjung tak berhak ke index → **403** (bukan 404 samar).
- **Sidebar satuan** diperbaiki: menu "Leger & Cetak Rapor" untuk semua kelas kini hanya **Kepala/Wakil/TU/Koordinator Kurikulum/Tim Kurikulum** (Kesiswaan dikeluarkan); blok wali kelas tetap dan link-nya **langsung ke leger rombelnya** (diperbaiki juga di `tugas-tambahan/wali-kelas` — sebelumnya ke index).
- **Sidebar Koordinator Kurikulum (tugas tambahan)**: ditambah menu **Leger & Cetak Rapor** (semua kelas; pemilih kelas tersedia di halaman leger).
- Guru non-wali tetap mendapat **"Buku Nilai Saya"** (`guru-mapel`) untuk input nilai sesuai aturan Pak Prof.
- Test baru: guru non-wali 403 · wali kelas lain 403 & wali sendiri OK · TU semua kelas OK + index redirect. `SumatifHarianDinamisTest` **15 test / 106 assertion**; `BukuNilaiKonsolidasiTest` + `PesertaDidikP0Test` **33 test / 160 assertion** — semua lulus; `view:cache` sukses.
- Catatan kecil: pemegang **dua tugas sekaligus** (mis. wali + koordinator mapel) memakai sidebar tugas-nya (tanpa menu leger) — akses URL tetap sesuai hak; bisa disempurnakan nanti bila diperlukan.

## 88. Rute Lama "Data Nilai" (nilai/{buku}) Dialihkan ke Leger Baru
- **Temuan Pak Prof.**: halaman `/schools/nilai/{adminBookId}/sts` masih halaman lama (bukan leger baru) — berasal dari menu "Data Nilai" → browser buku → tombol STS/SAS.
- **Perbaikan rute**: halaman lama per buku (`nilai/{adminBookId}/sts` & `/sas`) kini **redirect otomatis**:
  - Wali kelas / KSP / Waka / Kurikulum / TU / Super Admin → **Leger baru** (sheet per-mapel buku tsb: `nilai-kelas.sts?tab=mapel&admin_book_id=…`).
  - Guru non-wali → daftar **Buku Nilai Saya** (`guru-mapel.index`).
- **Satu aturan akses**: helper dijadikan `NilaiKelasController::legerAccessFor()` (publik-statis) dan dipakai bersama oleh `NilaiController` — tidak ada logika hak ganda.
- **POST lama** (`storeSts`/`storeSas`) tetap berjalan untuk kompatibilitas; form lama tidak lagi terjangkau karena GET sudah dialihkan.
- Test diperbarui: rute lama → **302** lalu target **200** dengan kolom dinamis tampil. Batch `SumatifHarianDinamisTest` + `BukuNilaiKonsolidasiTest` + `PesertaDidikP0Test` = **48 test / 267 assertion lulus**.

## 89. Perbaikan 5 Titik Halaman Leger (UI, Autosave, Sortir, Rapor Massal, Semester Genap)
- **UI card & keseluruhan**: tombol aksi (Cetak Rapor, Rapor Massal, Leger STS/SAS, Download Excel) kini tampil juga untuk **wali kelas** (sebelumnya hanya mode TU/Waka); badge mode TU/Waka tetap; banner informasi otomatis saat buku semester dibuat.
- **Autosave diperkuat**: front-end kini mengirim `X-Requested-With`, menolak respons non-JSON dengan pesan "Sesi berakhir — muat ulang halaman", dan **tidak lagi menampilkan sukses palsu** saat respons error. Sisi server sudah mengembalikan JSON (`saved_rows`) dan dibuktikan lewat test baru.
- **Tabel & sortir**: header tabel **beku** (sticky) + kolom No/NIS/Nama **beku kiri** saat digeser; baris hover; input nilai lebih besar dengan focus ring; **klik header untuk mengurutkan** kolom (No/NIS/Nama, semua mapel, Jumlah/Rata/Peringkat/Predikat, S/I/A, serta Pembiasaan/Tahfidz/Juz/Halaqoh pada sheet SAS; juga S1–S6/RS/STS pada sheet per-mapel) dengan indikator ▲/▼.
- **Cetak Rapor Massal**: satu PDF berisi **seluruh santri rombel** (halaman terpisah per santri) — tombol di halaman leger ("Rapor Massal") dan di halaman Cetak Rapor ("Cetak Rapor Massal (1 PDF)"). Route baru: `nilai-kelas/{rombel}/rapor/cetak-massal`; memakai template rapor yang sama + menyimpan final_score/predikat untuk SAS.
- **Semester genap kosong**: penyebab terkonfirmasi — **0 buku semester genap** di DB. Kini saat membuka leger semester yang belum punya buku, sistem **otomatis menyalin struktur buku dari semester lain** pada rombel+TA tsb (idempoten) dan menampilkan banner bahwa N buku disiapkan otomatis.
- Refactor: logika rapor per-santri diekstrak ke `buildSantriRapor()` + `schoolKopBase64()` agar per-santri & massal memakai satu jalur.
- Test baru: autosave JSON (leger & mapel), auto-buku semester genap, cetak rapor massal PDF. `SumatifHarianDinamisTest` **18 test / 121 assertion**; `BukuNilaiKonsolidasiTest` + `PesertaDidikP0Test` **33 test / 160 assertion** — semua lulus; `view:cache` sukses.

## 90. Seragamkan Tipografi & Rapikan Kolom Beku Tabel Leger
- **Tipografi tabel diseragamkan 12px**: seluruh sel & header tabel Leger dan per-Mapel kini 12px (sebelumnya campur 10–14px); **semua `font-size` inline di dalam tabel dihapus** dan dipusatkan di CSS agar konsisten.
- Skala pendukung: header grup (AGAMA/UMUM/PEMBIASAAN/TAHFIDZ/PRESENSI) 11px · teks `small` di header 10px · badge 11px · avatar 10px · input nilai 12px (lebar 52px; SH 40px).
- Teks 13px yang tersisa di luar tabel (banner/legend/empty state) diseragamkan ke 12px.
- **Kolom beku kini presisi**: lebar kolom No/NIS/Nama dipatok via CSS + offset `left` dihitung ulang (50/134px Leger; 54/138px per-Mapel), nama panjang dipotong dengan elipsis.
- **Header multi-baris tidak lagi menumpuk**: posisi `top` sticky per baris header dihitung otomatis via JS (`applyStickyHeaderTops`) saat load/resize.
- Test: `SumatifHarianDinamisTest` **18 test / 121 assertion lulus**; `view:cache` sukses.

## 92. Dark/Light Mode, Lebar Kolom Konsisten, Label Sumatif Penuh, dan Absensi Tombol Huruf
- **Dark/light mode**: seluruh warna keras (putih/abu/kuning/biru) di tabel Leger & per-Mapel diganti **variabel tema Bootstrap** (`--bs-body-bg`, `--bs-tertiary-bg`, `--bs-*-bg-subtle`, dst.) — otomatis menyesuaikan mode gelap/terang dan tidak lagi "full color".
- **Lebar kolom konsisten di kedua tab**: No 50px & NIS 84px (sama persis di Leger dan per-Mapel); **Nama tanpa batas maksimum** (min 220px, nama tampil utuh); kolom mapel Leger semua **92px**; kolom agregat (Jumlah 76/Rata 82/Peringkat 64/Predikat 104) & Pembiasaan/Tahfidz (46–84px) tetap; kolom pendukung mapel diseragamkan (RS/STS/SAS/RSA/NR 80px; Penghargaan & Formatif 52px).
- **Label kolom sumatif tampil penuh**: lebar kolom S1–S6 dihitung otomatis dari panjang label (mis. "Sumatif Harian 1" tampil utuh, bukan terpotong).
- **Halaman absensi santri** (Absensi Harian → Input): pilihan status kini **tombol huruf H/S/I/A/T** (gaya *subtle*, aman dark mode); klik S/I/A/T **otomatis memunculkan kolom Keterangan** (fokus langsung), klik H menyembunyikannya; tombol "isi semua" & reset ikut menyinkronkan status + keterangan. **Mode default halaman = tombol huruf.**
- **Perbaikan fixture test lama** `AttendanceSyncKehadiranTest`: fixture membuat buku manual padahal observer penugasan sudah otomatis membuatnya → duplikat (unique constraint). Kini idempoten (`updateOrCreate`).
- Test batch: AttendanceSyncKehadiranTest + AcademicServerSideAuthorizationTest + PesertaDidikP0Test + SumatifHarianDinamisTest + BukuNilaiKonsolidasiTest = **69 test / 327 assertion lulus**; `view:cache` sukses.

## 93. Leger Baca-Saja, Nilai Murni STS/SAS di Tab Mapel, Z-Index Berlapis & Input Gaya Spreadsheet
- **Leger tidak lagi menerima input nilai** — semua nilai diambil dari **tab Mapel** (footer menjelaskan hal ini). Kolom KKTP tetap bisa diatur inline di baris KKTP leger.
- **Kolom Nilai Murni STS & SAS** ditambahkan di tab Mapel (input), mengikuti aturan yang sudah berlaku di Buku Nilai: **Nilai Murni wajib terisi sebelum kolom STS/SAS**:
  - UI: kolom STS/SAS otomatis **terkunci (disabled)** sampai Nilai Murni diisi; terbuka seketika saat Nilai Murni diketik (JS gating per baris).
  - Backend: pelanggaran → **422** dengan pesan `nilai.<santri>.sts` / `nilai.<santri>.sas` ("Isi Nilai Murni … terlebih dahulu"), berlaku juga saat autosave.
- **Z-index berlapis** sesuai permintaan: sel nilai (dasar) → **sel identitas tubuh No/NIS/Nama (2)** → **header tabel (3)** → **header identitas No/NIS/Nama (4, paling depan)**; latar sel identitas opak sehingga tidak tembus lapisan lain saat digulir.
- **Input gaya spreadsheet**: input di tab Mapel (SH, N. Murni STS/SAS, STS, SAS) kini **tanpa border & penuh kolom** (border hanya dari tabel; fokus memakai ring inset), KKTP di leger juga ikut gaya ini.
- **Fitur spreadsheet yang sudah aktif**: navigasi **Enter/↑/↓** antar sel, **paste matriks** dari Excel/Google Sheets mulai dari sel aktif (nilai terdistribusi ke kolom/baris berikutnya, memicu autosave). Blok/range selection untuk copy-paste antar sel dicatat sebagai rencana berikutnya.
- Test: `SumatifHarianDinamisTest` **20 test / 137 assertion** (termasuk uji aturan Nilai Murni di tab Mapel & leger baca-saja); batch lain (BukuNilai/Kesiswaan/Absensi/Otorisasi) **202 assertion lulus**; `view:cache` sukses.

## 94. Modal Peringatan Nilai Murni, Auto-Update Nilai Turunan, S6 = Rata-rata Formatif, Kelola Kolom di Leger
- **Modal peringatan "Nilai Murni Belum Diisi"**: kolom STS/SAS yang terkunci kini `readonly` (bukan disabled) sehingga **klik memunculkan modal** dengan penjelasan + tombol "Isi Nilai Murni Sekarang" (langsung fokus ke kolom murni). Diterapkan di **tab Mapel halaman Leger** dan **Buku Nilai (wizard 3)**.
- **Auto-update nilai turunan**: respons autosave kini mengirim `rs/sas/rsa/nr_murni/nr_final` per santri; UI memperbarui sel **RS, RSA, NR Murni, NR Final** seketika (tanpa reload) — pola sama dengan Buku Nilai.
- **S6 = rata-rata Penilaian Asesmen Formatif (otomatis)**:
  - Kolom S6 di **tab Mapel & wizard 3** kini **tampilan otomatis** (bukan input), nilainya dari rata-rata formatif (wizard 4).
  - Saat guru menyimpan **formatif (wizard 4)**, S6 + RS/RSA/NR Murni/NR Final **otomatis dihitung ulang** (server).
  - Perhitungan **RS kini menyertakan S6 otomatis** (JS + backend).
- **Fitur tambah kolom di halaman Leger**: tombol **Kelola Kolom** di tab Mapel — tambah/ganti nama/urutkan/hapus kolom sumatif (S1–S5 legacy terkunci, S6 otomatis terkunci); tersimpan via endpoint baru `nilai-kelas/{rombel}/sts/kolom`.
- Test: `SumatifHarianDinamisTest` + `BukuNilaiKonsolidasiTest` + `PesertaDidikP0Test` + `AttendanceSyncKehadiranTest` + `AcademicServerSideAuthorizationTest` = **72 test / 356 assertion lulus**; `view:cache` sukses.
- Catatan: format **Asesmen Formatif dengan sub-kolom 1–4 per aspek** (16 kolom + RATA-RATA) membutuhkan perubahan skema (JSON per aspek) — diusulkan sebagai iterasi berikutnya.

## 95. Template Modal Konsisten, Perbaikan Urutkan, dan Asesmen Formatif 4 Kolom per Aspek
- **Konsistensi template modal**: modal peringatan Nilai Murni (halaman Leger & Buku Nilai) dan modal **Kelola Kolom** kini memakai komponen modal bersama **`<x-modal>`** (template modal yang sudah ada di proyek) — header, footer, dan ukuran konsisten dengan modal-modul lain.
- **Perbaikan fitur urutkan**: akar masalahnya baris tabel **tab Mapel tidak memiliki `data-name`** sehingga pemilih baris sortir tidak menemukan apa pun. Kini sortir memilih semua baris ber-`data-col` → **klik judul kolom benar-benar mengurutkan di kedua tab** (Leger & Mapel).
- **Penilaian Asesmen Formatif — 4 kolom per aspek (bisa ditambah)**:
  - Migrasi baru: JSON `lkpd_scores`, `diskusi_scores`, `kuis_scores`, `antarteman_scores` pada `admin_nilai_formatif` (kolom skalar lama dipertahankan untuk kompatibilitas).
  - **Buku Nilai (wizard 4)**: setiap aspek (Mengerjakan LKPD, Diskusi Kelompok, Kuis/Tes Singkat, Penilaian Antarteman) kini punya **4 kolom nilai** dengan tombol **+** untuk menambah kolom; menampilkan **rata-rata per aspek** dan **NR Final** secara langsung.
  - **Server**: skor tiap aspek = rata-rata sub-kolomnya; **NR Final = rata-rata dari rata-rata aspek**; **S6 tersinkron otomatis** dari nilai formatif. Data format lama (skor tunggal) tetap terbaca.
  - **Leger**: yang ditampilkan adalah **rata-rata tiap aspek** (LKPD/Diskusi/Kuis/Antar Teman) + RATA-RATA — sesuai permintaan.
- Catatan: `StudyGroupSubjectAssignmentCascadeTest` gagal jika berdiri sendiri karena memakai `DatabaseTransactions` **tanpa menjalankan migrasi** (in-memory DB kosong) — **pre-existing**, tidak terkait perubahan ini.
- Test: 8 file terkait (leger, buku nilai, kesiswaan, absensi, otorisasi, kurikulum, pelaksanaan) = **100 test / 595 assertion lulus**; `view:cache` sukses; migrasi dev dijalankan.

## 96. Modal Gaya Lord-Icon (zoomIn) & Kolom Formatif Ala Nilai Sumatif
- **Konsistensi konsep modal (lord-icon/zoomIn)** sesuai template yang ada:
  - Modal peringatan **Nilai Murni Belum Diisi** (halaman Leger & Buku Nilai) kini memakai komponen bersama **`x-modal-confirm`** — lord-icon animasi, judul, pesan, tombol "Isi Nilai Murni Sekarang" + "Batal", kelas `zoomIn` (script lordicon dimuat di halaman terkait).
  - Komponen baru **`x-modal-success`** (konsep sama) — di Buku Nilai, setelah **simpan berhasil** muncul modal sukses otomatis.
  - Komponen bersama ini siap dipakai untuk konfirmasi **hapus/tambah/sukses** di halaman lain (rollout bertahap).
- **Penilaian Asesmen Formatif kini ber-“Kelola Kolom” ala Nilai Sumatif**:
  - Tombol **Kelola Kolom** di halaman formatif: modal mengelola **kolom tiap aspek** (Mengerjakan LKPD / Diskusi Kelompok / Kuis-Tes Singkat / Penilaian Antarteman) — minimal 4 kolom (default), maksimal 12, **label kolom bisa diganti**.
  - Definisi kolom tersimpan per buku (`teacher_admin_books.formatif_columns`, migrasi baru) via endpoint `guru-mapel/{buku}/w4/kolom`.
  - Grid mengikuti definisi (jumlah & tooltip label), **rata-rata per aspek + NR Final live**, **S6 tetap tersinkron otomatis**, dan Leger tetap menampilkan **rata-rata per aspek**.
- Test: 8 file terkait = **101 test / 601 assertion lulus**; `view:cache` sukses; migrasi dev dijalankan.

## 97. Ikon Modal Sesuai Konteks, Rollout Modal Verifikasi, Tabel Formatif Sistem Kolom, & Fix RS/S6
- **Ikon modal konteks**: modal "Nilai Murni Belum Diisi" tidak lagi memakai ikon tong sampah — kini memakai **ikon alert/peringatan** (`tdrtiskw`, warna amber) yang sesuai konteks.
- **Rollout konsep modal (lord-icon/zoomIn)** ke halaman verifikasi lain:
  - **Mutasi Masuk** — modal hapus → `x-modal-confirm` (ikon hapus, tombol "Ya, Hapus!").
  - **Jenis Izin (asrama/leave-policies)** — modal hapus → `x-modal-confirm` (form delete dipindah ke luar modal, pesan dinamis tetap).
  - **Promosi Siswa** — modal eksekusi diberi ikon peringatan lord-icon (checkbox konfirmasi tetap).
  - Catatan: konfirmasi lain di aplikasi banyak yang memakai SweetAlert2 — konversi bertahap bisa dilanjutkan sesuai prioritas.
- **Tabel Penilaian Asesmen Formatif kini benar-benar sistem kolom** (seperti Nilai Sumatif): **1 sub-kolom = 1 kolom tabel** (header dua baris: nama aspek + label sub-kolom + kolom **Rata** per aspek), input **tanpa border** di dalam sel berbingkai (gaya spreadsheet). Tidak lagi 4 input menumpuk dalam satu sel. Rata per aspek & NR Final tetap live; S6 tetap tersinkron.
- **Fix RS: S6 kini ikut dihitung** — akar masalah: **autosave Buku Nilai menghapus S6** (posting SH tanpa S6). Perbaikan di semua jalur simpan (autosave, w3, tab Mapel): S6 dipertahankan dari nilai formatif atau nilai tersimpan; tampilan S6 memakai nilai tersimpan lebih dulu. Rumus RS = **jumlah data ÷ jumlah kolom terisi** (rata-rata), kini sudah termasuk S6.
- Test regresi baru: autosave SH tidak menghapus S6 & RS = (S1+S6)/2. Batch 3 file = **58 test / 338 assertion lulus**; `view:cache` sukses.

## 98. RS Pembagi Seragam, UI Formatif Profesional, & Konversi Swal→Modal (Kelas & Rombel)
- **Rumus RS diperbaiki — pembagi SERAGAM per kelas**: RS = jumlah nilai SH terisi ÷ **banyak kolom terisi terbanyak di kelas** (bukan per santri). Contoh: ada santri mengisi 4 kolom → semua santri dibagi 4, sehingga santri yang tidak mengikuti sebagian sumatif tidak diuntungkan. Diterapkan di service (semua jalur simpan & recalc) dan hitungan live tab Mapel (`data-rs-divisor`). Contoh S1=90, S5=90, S6=85 dengan pembagi kelas 4 → RS = 66,25.
- **UI Asesmen Formatif lebih profesional**: header 2 baris **beku** (sticky) dengan perhitungan top otomatis, **kolom #/NIS/Nama beku kiri**, tipografi seragam 12px, warna memakai variabel tema (aman gelap/terang), input **tanpa border** penuh sel (gaya spreadsheet), kolom **Rata** per aspek disorot, hover baris, dan area gulir maksimum 70vh.
- **Konversi SweetAlert2 → modal Bootstrap lord-icon (bertahap)** dimulai di satuan pendidikan:
  - **Kelas (grade-levels)** & **Rombel (study-groups)**: konfirmasi hapus kini memakai `x-modal-confirm` (ikon hapus lord-icon, form tersembunyi, `formId`, tombol "Ya, Hapus!").
  - Lanjutan giliran berikutnya: Mutasi, Nilai, Kurikulum, Jadwal, Kaldik (komponen bersama sudah siap).
- Test: 3 file terkait = **59 test / 340 assertion lulus**; `view:cache` sukses.

## 99. Kolom Formatif Ditambah per Aspek, Placeholder Tengah, & Kolom Beku Opak
- **Tambah/hapus kolom per aspek** (tidak lagi satu modal terpusat yang membingungkan):
  - Setiap **header aspek** (Mengerjakan LKPD / Diskusi Kelompok / Kuis-Tes Singkat / Penilaian Antarteman) memiliki tombol **+** untuk menambah kolom aspek tersebut langsung dari tabel.
  - Setiap **header sub-kolom** memiliki tombol **×** (muncul saat kolom > 4) untuk menghapus kolom tersebut; perubahan langsung tersimpan dan halaman dimuat ulang.
  - Modal "Kelola Kolom" terpusat dihapus; catatan footer diperbarui mengikuti tombol baru.
- **Placeholder input di tengah**: `::placeholder { text-align: center }` pada input tabel Formatif (dan konsisten di tabel Leger/Mapel).
- **Kolom beku dibuat opak** — memperbaiki tampilan "tumpang tindih/transparan": hover pada kolom identitas (#/NIS/Nama) kini memakai warna **solid** (`--bs-secondary-bg`), bukan warna transparan; ditambah **bayangan tepi** pemisah kolom beku. Diterapkan di **Asesmen Formatif** dan **Leger/Mapel**.
- Test: 4 file terkait = **66 test / 362 assertion lulus**; `view:cache` sukses.

## 100. Modal Hapus Kolom, Penomoran Otomatis, UI Buku Nilai Gaya Leger, Bobot NR Final, & Pencarian Leger
- **Hapus kolom formatif pakai modal lord-icon**: tombol × pada sub-kolom tidak lagi memakai `confirm()` browser — muncul modal "Hapus kolom ini?" (ikon hapus, tombol "Ya, Hapus!").
- **Penomoran otomatis setelah hapus**: menghapus kolom ke-3/ke-2/terakhir membuat kolom sisa **dinomori ulang berurutan** (1,2,3,4) — di klien maupun server; label custom (mis. "Pertemuan") tetap dipertahankan.
- **UI tabel Buku Nilai (Nilai Sumatif) kini seragam dengan Leger**: header 2 baris **beku** (top dihitung otomatis), **kolom #/NIS/Nama beku** kiri, tipografi 12px, warna variabel tema (gelap/terang), input **tanpa border** penuh sel dengan placeholder tengah, bayangan tepi kolom beku, hover opak.
- **Bobot NR Final (guru mapel) berfungsi kembali**: panel "Pengaturan NR Final" di kartu header; setelah disimpan halaman **otomatis dimuat ulang** sehingga bobot RS/STS/SAS (dan seluruh NR Final) langsung terlihat terpakai.
- **Header kartu gaya Jurnal** diterapkan di Buku Nilai (Nilai Sumatif & Asesmen Formatif): `card border-primary-subtle`, judul berikon, badge subtle (mapel/kelas/semester/TA), indikator autosave, tombol **Realisasi**, dan pemilih buku.
- **Pencarian nama di Leger otomatis (tanpa Enter)**: filter berjalan saat mengetik dan berlaku di **kedua tab** (Leger & Mapel) berdasarkan nama santri.
- Test: 3 file terkait = **60 test / 343 assertion lulus**; `view:cache` sukses.

## 101. Kartu Seragam w1–w6, Tools Tabel Buku Nilai, Modal Hapus Kolom Sumatif, & Seeder Kurikulum
- **Kartu header gaya Jurnal kini di SEMUA halaman Buku Nilai (wizard 1–6)**: w1 (Presensi), w5 (Penghargaan), w6 (Catatan) menyusul w2–w4 — badge subtle (mapel/kelas/semester/TA), indikator autosave, tombol **Realisasi**, dan pemilih buku.
- **Tools tabel seragam di Buku Nilai (w1–w6)** via partial bersama `nilai-guru.partials.table-tools`:
  - **Pencarian nama otomatis tanpa Enter** di setiap tabel;
  - **Klik judul kolom untuk mengurutkan** (▲/▼) dengan pemetaan kolom visual (mendukung header bertingkat);
  - gaya tabel Leger: header beku (top per baris), kolom **#/NIS/Nama beku**, 12px, variabel tema (gelap/terang), input tanpa border + placeholder tengah, hover opak.
- **Hapus kolom Nilai Sumatif (w3) memakai modal lord-icon** ("Hapus kolom ini?" → "Ya, Hapus!") — konsisten dengan hapus kolom Formatif (tidak lagi `confirm()` browser).
- **Seeder Kurikulum GTK "Lalu Sopian Harianto"** (`KurikulumLaluSopianSeeder`, idempoten): membuat rantai **CP → TP → ATP (+item) → Prota (+item) → Prosem (+item + distribusi mingguan) → Perangkat (RPM)** untuk tiap konteks buku GTK (mapel × tingkat × TA × semester). Sudah dijalankan di dev: **47 konteks → 47 CP/ATP/Prota/Prosem/Perangkat + 282 TP/282 item**. Jalankan ulang kapan saja: `php artisan db:seed --class=KurikulumLaluSopianSeeder`.
- Test: 3 file terkait = **60 test / 343 assertion lulus**; `view:cache` sukses.

## 102. Presensi Buku Nilai: Tombol Huruf H/T/S/I/A + Keterangan, Enum Terlambat, & Laporan Seeder
- **Radio besar di Presensi Buku Nilai (w1) diganti tombol huruf kompak** `H T S I A` (Hadir, Terlambat, Sakit, Izin, Alpa) — ukuran kecil (24×22px), gaya *subtle* aman tema gelap/terang.
- **Klik S/I/A/T memunculkan form Keterangan** otomatis (fokus langsung); klik H menyembunyikannya. Nilai keterangan tersimpan ke kolom `notes` (sudah terbukti lewat test).
- **Status `terlambat` didukung penuh**: migrasi baru menambah enum pada `admin_presensi_siswa` (MySQL) + validasi store diperbarui (`in:hadir,terlambat,izin,sakit,alpa`).
- Tombol status di halaman **Absensi Harian** ikut diperkecil agar konsisten.
- **Seeder Kurikulum (Lalu Sopian Harianto)**: output kini juga menampilkan **total data saat ini** — 0 "baru" wajar karena **idempoten** (data sudah pernah dibuat): **47 CP, 282 TP, 47 ATP (282 item), 47 Prota (282 item), 47 Prosem (282 item), 47 Perangkat**.
- Test: 3 file terkait = **40 test + 1 risky / 240 assertion lulus**; `view:cache` sukses.

## 103. Tombol Status Bulat, Pencarian & Filter Buku Nilai, & Fix Autosave Wizard 4
- **Kolom "Status Kehadiran" kini tombol bulat berwarna** (seperti radio): `(H) (T) (S) (I) (A)` — lingkaran 26px, **berwarna saat dipilih**, gaya *subtle* aman tema gelap/terang. Berlaku di **Buku Nilai → Presensi (w1)** dan **Absensi Harian**.
- **Keterangan hanya muncul saat status bukan Hadir**: klik S/I/A/T → form keterangan tampil & fokus; klik H → tersembunyi.
- **Pencarian & filter di semua wizard Buku Nilai (w1–w6)**:
  - Kotak **Cari nama santri** berjalan **saat mengetik (tanpa Enter)**;
  - **Filter tambahan** yang menyesuaikan tabel: pada tabel presensi → filter **status** (Semua/Hadir/Terlambat/Sakit/Izin/Alpa); pada tabel nilai → filter **kelengkapan** (Semua/Belum Lengkap/Sudah Lengkap);
  - Pencarian dan filter dapat dikombinasikan.
- **Autosave Wizard 4 (Asesmen Formatif) diperbaiki**: akar masalah — autosave mengirim `type=formatif` tetapi endpoint hanya menangani `sumatif`. Kini ada penanganan **formatif** (ekstraksi `saveFormatifRows` dipakai bersama oleh simpan & autosave): menyimpan sub-kolom per aspek, menghitung **rata-rata per aspek + NR Final**, menyinkronkan **S6**, dan mengembalikan `saved_rows` untuk pembaruan tampilan.
- Test baru: autosave formatif berhasil (contoh: rata aspek 85/70/100/80 → NR Final 83,75 & S6 tersinkron). Batch 4 file = **69 test / 377 assertion lulus**; `view:cache` sukses.

## 104. Presensi Buku Nilai (w1) Kini Berbentuk Daftar Kartu
- **Input presensi w1 diubah dari tabel menjadi daftar kartu** sesuai contoh Pak Prof.: setiap santri satu kartu dengan **avatar inisial**, **nama tebal + NIS**, **tombol status bulat H/T/S/I/A** (berwarna saat dipilih), dan **kolom Keterangan/Alasan di bawah kartu** yang **hanya muncul saat status bukan Hadir**.
- **Pencarian nama tetap otomatis (tanpa Enter)** untuk daftar ini, ditambah **filter status** (Semua/Hadir/Terlambat/Sakit/Izin/Alpa) — dapat dikombinasikan; penghitung siswa ikut menyesuaikan.
- `setW1Status` kini menyinkronkan `data-status` kartu (untuk filter) dan membuka/menutup wrapper keterangan.
- Test: 2 file terkait = **56 test / 322 assertion lulus**; `view:cache` sukses.

## 105. AKAR MASALAH UI/JS: Layout Tidak Merender CSS & JS Halaman
- **Temuan penting**: `layouts/master.blade.php` hanya merender `@stack('scripts')`, padahal banyak halaman memakai `@section('script')` (Leger), `@section('css')` (rapor), dan `@push('style')` (Presensi w1) — akibatnya **CSS/JS halaman-halaman itu tidak pernah dimuat**:
  - Halaman **Leger** tampil tanpa gaya tabel (kolom beku/huruf/input kacau) dan tanpa JS (urutkan, pencarian, autosave) — persis keluhan "urutkan/cari belum bisa, autosave gagal, UI kacau".
  - **Presensi w1** tanpa CSS daftar kartu, **rapor** tanpa CSS, dsb.
- **Perbaikan layout (`master.blade.php`)**: kini merender `@yield('css')`, `@stack('style')`, `@stack('styles')` di kepala halaman serta `@yield('script')`, `@stack('script')`, `@stack('scripts')`, `@yield('js')` di akhir — semua CSS/JS halaman langsung dimuat kembali.
- **Regresi test** ditambahkan: halaman Leger wajib memuat `initSortableTable` (JS), halaman Presensi w1 wajib memuat CSS `.presensi-row` — mencegah masalah ini terulang.
- Test: 4 file terkait = **69 test / 381 assertion lulus**; `view:cache` sukses.

## 106. Presensi w1 Sesuai Mockup (Baris + Avatar Besar + Tombol Solid)
- **Daftar presensi w1 disamakan dengan mockup Pak Prof.**: baris tanpa kotak per-kartu, dipisah **garis tipis**; **avatar bulat 44px** berisi inisial; **nama tebal 16px** + **NIS** abu di bawahnya.
- **Tombol status bulat 34px**: tidak dipilih = transparan + garis abu + huruf abu; **dipilih = solid berwarna** (H hijau, T kuning, S kuning-emas seperti gambar, I biru, A merah) dengan huruf kontras — tetap aman di mode gelap/terang.
- **Keterangan/Alasan** tampil **di bawah baris** (input rounded 8px, border halus, latar transparan) dan **hanya muncul saat status bukan Hadir**.
- Tombol **T (Terlambat)** dipertahankan (5 tombol) sesuai permintaan sebelumnya; bila ingin persis 4 tombol seperti gambar, tinggal dihapus.
- Test: `SumatifHarianDinamisTest` **29 test / 199 assertion lulus**; `view:cache` sukses.

## 107. Tombol Status: Border Lingkaran Jelas + Ukuran & Font Seragam
- **Border lingkaran kini terlihat jelas** pada tombol status yang belum dipilih: **1.5px solid `--bs-secondary-color`** (abu, kontras di mode terang & gelap) — sebelumnya border terlalu samar.
- **Keseragaman ukuran & font**: tombol di Presensi w1 **34×34px / font 13px bold**; di tabel lain & Absensi Harian **30×30px / font 12px bold** — semuanya bulat penuh, `font-family: inherit`, `box-sizing: border-box`, dan `appearance: none` sehingga **ukuran tidak berubah saat dipilih** (border dipertahankan 1.5px, hanya warna yang berubah).
- **Status terpilih = solid** dan konsisten di semua tempat: H hijau, T kuning, S kuning-emas (#f0b429), I biru, A merah — huruf kontras (gelap untuk H/T/S, putih untuk I/A).
- Hover: border & huruf berubah ke warna primer (indikasi dapat diklik).
- Test: `SumatifHarianDinamisTest` + `AttendanceSyncKehadiranTest` = **36 test / 221 assertion lulus**; `view:cache` sukses.

## 108. AKAR MASALAH VISUAL: Variabel CSS Bootstrap Tidak Ada di Build Tema
- **Temuan kunci**: `bootstrap.min.css`, `app.min.css`, dan `custom.min.css` build tema ini **sama sekali tidak memuat variabel `--bs-*`** (0 definisi). Akibatnya **semua `var(--bs-*)` gagal senyap** — menjelaskan kenapa hanya warna hex (S & I) yang tampil pada tombol, kenapa border/background kolom beku sering kosong, dan kenapa UI terasa "kacau" di banyak halaman.
- **Perbaikan global** di `layouts/head-css.blade.php`:
  - Blok `<style id="bs-theme-vars">` berisi **variabel standar Bootstrap 5.3** (terang + `[data-bs-theme="dark"]`) sehingga seluruh `var(--bs-*)` di aplikasi berfungsi kembali.
  - **Urutan CSS dibetulkan**: CSS halaman (`@yield('css')`, `@stack('css'/'style'/'styles')`) kini dimuat **setelah** CSS tema (sebelumnya ada yang dimuat sebelum Bootstrap sehingga tertimpa). Duplikasi di `master` dihapus.
- **Wizard 1 (desain Bapak)**: tombol status diberi **nilai fallback hex** (`var(--bs-primary, #0d6efd)` dll.) sebagai pengaman ganda — H/T/A kini berwarna & berlingkaran seperti S/I.
- **Konsistensi tabel dengan Leger**: tabel presensi memakai **font 12px**, **header beku** dengan warna `--bs-tertiary-bg`, padding sel `.5rem`, hover baris, kolom No 50px center, NIS 12px, nama 12px/600.
- Test: 3 file terkait = **62 test / 360 assertion lulus**; `view:cache` sukses.

## 109. Hover Tombol Status Memakai Variabel Subtle Bawaan
- Warna hover tombol status tidak lagi memakai rgba keras, melainkan **variabel subtle Bootstrap** yang sudah ada (setara `badge bg-*-subtle`):
  - Hadir → `--bs-primary-bg-subtle` · Terlambat → `--bs-warning-bg-subtle` · Sakit → `--bs-warning-bg-subtle` · Izin → `--bs-success-bg-subtle` · Alpa → `--bs-danger-bg-subtle` (dengan fallback hex).
- Diterapkan seragam di **Presensi w1**, **tabel wizard lain**, dan **Absensi Harian**.
- Test: `SumatifHarianDinamisTest` + `AttendanceSyncKehadiranTest` lulus; `view:cache` sukses.

## 110. Wizard 1: Hapus CSS Kustom — Konsisten Memakai Kelas Velzon
- **Seluruh blok `@push('style')` kustom di wizard 1 (±135 baris CSS) dihapus**; tampilan kini murni memakai kelas **Velzon/Bootstrap**:
  - Tabel: `table table-hover align-middle table-nowrap` + `thead.table-light` + `table-responsive`; isi sel memakai `text-muted`, `fw-semibold`, `<code>` untuk NIS.
  - **Tombol status**: `btn btn-sm btn-icon rounded-circle` + **`btn-soft-{color}`** saat belum dipilih (subtle, setara `bg-*-subtle`) dan **`btn-{color}`** (solid) saat dipilih — warna: H primary, T warning, S warning, I success, A danger.
  - Keterangan: `form-control form-control-sm` + `mt-2` (tanpa CSS tambahan).
- **`setW1Status`** diperbarui: menukar kelas `btn-soft-*` ↔ `btn-*` berdasarkan `data-color` (tetap menyinkronkan hidden input, `data-status`, dan wraper keterangan).
- Hanya kelas semantik (`status-group`, `note-inline`, `presensi-row`) yang tersisa untuk kebutuhan perilaku JS — tanpa gaya.
- Regresi test diperbarui: halaman w1 wajib memuat kelas **`btn-icon`**, **`btn-soft-`**, dan fungsi `setW1Status`.
- Test: `SumatifHarianDinamisTest` + `AttendanceSyncKehadiranTest` = **36 test / 223 assertion lulus**; `view:cache` sukses.

## 111. Autosave Presensi w1, Style Tabel Velzon di Semua Wizard, & Inisial Nama Depan+Akhir
- **Simpan otomatis di Presensi (wizard 1)**: klik tombol status langsung **menyimpan otomatis** (debounce 1 detik) melalui endpoint yang kini mengembalikan **JSON** (`saved: true`); mengetik **Keterangan** juga ikut tersimpan otomatis. Indikator kecil di baris info menampilkan **"Tersimpan hh:mm"**; tombol "Simpan Presensi" tetap tersedia untuk simpan manual.
- **Style tabel wizard 1 diterapkan ke SEMUA wizard Buku Nilai (w1–w6)**:
  - Blok CSS kustom **dihapus** dari w3, w4, dan partial bersama (sisa hanya indikator urut ▲▼).
  - Semua tabel memakai kelas **Velzon/Bootstrap**: `table table-hover align-middle table-nowrap` + `thead.table-light` (tanpa `table-bordered`, tanpa sticky kustom).
  - **Input** di w3 & w5 (dan w4) dikonversi ke **`form-control form-control-sm`** — total **14 input** dibersihkan; tidak ada lagi style inline `#f8f9fa00/border:none`.
  - Toolbar pencarian/filter/urut tetap berfungsi (kelas utilitas Bootstrap).
- **Inisial kini nama depan + nama akhir** (bukan satu huruf): mis. "ABDUL BARR MIRAJI" → **AM**; nama satu kata memakai dua huruf pertama. Diterapkan di **avatar Leger & tab Mapel**, **rapor-index**, dan **tabel Riwayat wizard 1**.
- Test baru: autosave presensi w1 mengembalikan JSON + data tersimpan. Batch 3 file = **63 test / 364 assertion lulus**; `view:cache` sukses.

## 112. Kembalikan Tampilan Wizard Lain (w2–w6) Seperti Sebelumnya
- Atas permintaan Pak Prof., perubahan gaya tabel pada **wizard selain w1 dikembalikan** ke tampilan sebelumnya:
  - **Partial tools**: style tabel lengkap dikembalikan (header beku, kolom identitas beku, input borderless, hover, indikator urut).
  - **w4 (Asesmen Formatif)**: style `tf-scroll`/`#formatif-table` + JS header beku + kelas `table-bordered` + input borderless + sel Rata + input Ket dikembalikan.
  - **w5 (Penghargaan)**: kelas tabel & **6 input** dikembalikan ke borderless.
  - **w2 (Jurnal)**: kelas `table-freeze` dikembalikan; **w6 (Catatan)**: kelas tabel dikembalikan.
  - **w3 (Nilai Sumatif)** sudah dalam kondisi sebelumnya — tidak diubah.
- **Yang tetap dipertahankan** (sesuai permintaan sebelumnya): **simpan otomatis di w1** + indikator, **inisial nama depan+akhir** di seluruh tempat, dan wizard 1 yang sudah Velzon-native.
- Test: `SumatifHarianDinamisTest` + `BukuNilaiKonsolidasiTest` = **36 test / 237 assertion lulus**; `view:cache` sukses.

## 113. Autosave Presensi Wizard 1 (Dipasang Ulang & Terverifikasi)
- **Skrip autosave wizard 1 dipasang ulang** (file sempat ditulis ulang sehingga skrip sebelumnya hilang): kini **self-contained** di wizard1 —
  - Klik tombol status → tersimpan otomatis (debounce 0,9 detik) via `#presensiList` (listener delegasi, terbukti jalan terlepas dari urutan skrip partial).
  - Mengetik Keterangan → ikut tersimpan otomatis.
  - **Indikator** "Menyimpan… → Tersimpan hh:mm → Menunggu…" di sebelah tombol "Simpan Presensi" (badge Velzon).
  - Hook `window.__w1AutoSave` tetap didaftarkan bila partial memanggilnya.
- Endpoint `w1.store` mengembalikan **JSON** (`saved: true`) untuk permintaan AJAX (sudah ada, terverifikasi).
- Regresi test ditambah: halaman w1 wajib memuat `__w1AutoSave` dan `w1-autosave-indicator`.
- Test: `SumatifHarianDinamisTest` **30 test / 204 assertion lulus**; `view:cache` sukses.
- Sesuai instruksi: **tidak menyentuh wizard lain**.

## 114. Wizard 1: Aksi Cepat (Semua Hadir/Telat/Sakit/Izin/Alfa) + Rekap Harian
- **Aksi Cepat** ditambahkan di atas tabel presensi: tombol **Semua Hadir / Semua Telat / Semua Sakit / Semua Izin / Semua Alfa** (gaya `btn-soft-*` Velzon). Klik tombol → semua santri diubah ke status tersebut, keterangan otomatis terbuka untuk non-hadir, lalu **disimpan sekaligus (bulk save)** lewat autosave.
- **Rekapitulasi harian live** di baris yang sama: badge jumlah **H / T / S / I / A** yang **diperbarui otomatis** setiap kali status berubah (dan saat aksi cepat digunakan). Perhitungan dari nilai tersimpan di hidden input, jadi konsisten dengan data yang akan dikirim.
- Tombol **Simpan Presensi** manual tetap tersedia; indikator autosave tetap "Menyimpan… → Tersimpan hh:mm".
- Regresi test ditambah: halaman w1 wajib memuat `w1SetAll` dan `w1-count-hadir`.
- Test: `SumatifHarianDinamisTest` **30 test / 206 assertion lulus**; `view:cache` sukses.
- Sesuai instruksi: **hanya wizard 1** yang disentuh.

## 115. Absensi Harian (Wali Kelas) Bergaya Wizard 1 + Filter Rombel Dibatasi
- **Input Absensi Harian kini berfitur seperti wizard 1**:
  - Tombol status **bulat Velzon** (`btn btn-sm btn-icon rounded-circle`) — `btn-soft-*` saat belum dipilih, `btn-*` solid saat dipilih (H primary, T warning, S warning, I success, A danger) — CSS kustom tombol dihapus.
  - **Keterangan/Alasan** otomatis tampil hanya saat status bukan Hadir.
  - **Simpan otomatis**: perubahan status per santri & ketikan keterangan tersimpan otomatis (debounce 0,9 detik); ada **indikator** "Menyimpan… → Tersimpan hh:mm" di action bar; endpoint `absensi.harian.store` mengembalikan **JSON** untuk AJAX.
  - **Aksi Cepat**: tombol Semua Hadir/Telat/Sakit/Izin/Alfa — mengubah semua santri lalu **menyimpan borongan**.
  - **Rekapitulasi harian live** (badge H/T/S/I/A) yang diperbarui setiap perubahan status.
- **Filter rombel dibatasi**: hanya **TU, Waka, Kurikulum, KSP, dan setingkat (Super Admin)** yang boleh memilih rombel. **Wali kelas** melihat rombelnya sebagai **badge terkunci** (tanpa dropdown) — ditegakkan di **controller** (scoping query di 4 halaman absensi, `canFilterRombel()`), **otorisasi simpan**, dan **view**.
- Test baru: halaman input wali terkunci + memuat UI baru; autosave absensi mengembalikan JSON & tersimpan. Batch 5 file = **semua lulus** (AttendanceSync 9 test/30 assertion; batch lain tetap hijau); `view:cache` sukses.

## 116. Absensi Wali Kelas: Langsung ke Kelasnya Tanpa Filter
- **Wali kelas kini langsung masuk ke kelasnya**: saat membuka halaman input absensi **tanpa memilih apa pun**, rombelnya **otomatis terpilih** (controller mengisi rombel pertama milik wali) dan daftar santri **langsung tampil**.
- **Filter rombel dihilangkan untuk wali**: kolom "Rombel" tidak dirender (hanya hidden input), tombol "Tampilkan Siswa" tidak muncul. Kolom Tanggal/Semester/Tampilan otomatis lebih lebar.
- **Ganti tanggal/semester/tampilan langsung memuat ulang** untuk wali (tanpa tombol), sehingga alur input tetap lancar per hari.
- **TU, Waka, Kurikulum, KSP, dan setingkat** tetap memakai filter rombel + tombol "Tampilkan Siswa" seperti biasa.
- Test diperbarui: wali membuka halaman **tanpa parameter** → santri langsung tampil, tanpa "Pilih Rombel" dan tanpa "Tampilkan Siswa".
- Batch: AttendanceSync + Sumatif + PesertaDidik lulus; `view:cache` sukses.

## 117. Tabel Input Absensi Persis Seperti Wizard 1
- **Struktur tabel disamakan dengan wizard 1 Buku Nilai**: kolom **No | NIS | Nama | Presensi Kehadiran** — kolom **JK dihapus** dan **kolom Keterangan terpisah dihapus** (keterangan kini berada **di dalam sel status**, di bawah tombol, muncul otomatis saat status bukan Hadir — persis wizard 1).
- **Kelas tabel sama**: `table table-hover align-middle table-nowrap mb-0` + `thead.table-light`; baris tidak lagi di-highlight hijau (mengikuti wizard 1); NIS memakai `<code>`, nama `fw-semibold`.
- Tombol status tetap **bulat Velzon** (`btn btn-sm btn-icon rounded-circle`, soft → solid) dengan `data-color`; mode dropdown tetap tersedia di dalam sel untuk yang memakai mode tersebut.
- Ditambahkan kondisi kosong ("Belum ada santri di rombel ini.") dan catatan legend diperbarui.
- Semua fitur tetap: autosave, indikator tersimpan, aksi cepat (Semua Hadir/Telat/Sakit/Izin/Alfa), rekap harian live, rombel wali terkunci & auto-load.
- Test: `AttendanceSyncKehadiranTest` **9 test / 32 assertion lulus**; `view:cache` sukses.

## 118. Absensi Harian: Pencarian Nama + Filter Status (Seperti Wizard 1)
- Ditambahkan **toolbar pencarian & filter** di atas tabel input absensi:
  - **Cari nama santri** — berjalan otomatis saat mengetik (tanpa Enter).
  - **Filter status**: Semua / Hadir / Terlambat / Sakit / Izin / Alpa.
  - Penghitung **"X / Y santri"** yang menyesuaikan hasil filter.
- Baris tabel memakai `data-name` & `data-status`; status ikut tersinkron saat tombol status diklik, aksi cepat dijalankan, atau dropdown diubah — sehingga hasil filter selalu akurat.
- Regresi test ditambah: halaman input wali wajib memuat `absenSearchInput` + `absenStatusFilter`.
- Test: `AttendanceSyncKehadiranTest` **9 test / 32 assertion lulus**; `view:cache` sukses.

## 119. Perbaikan Leger & Buku Nilai: Lapisan Freeze, Input Spreadsheet, & Modal Nilai Murni
- **Bug lapisan tabel ditemukan & diperbaiki**: sel beku (frozen) di Leger memiliki **z-index 20** — lebih tinggi dari header (3/4) — sehingga **header tertindih oleh body**. Hierarki baru: **header identitas 40 > header 30 > sel beku body 10 > sel bergerak (auto)**; semua sel beku berlatar opak.
- **Tabel wizard Buku Nilai**: aturan **kolom beku dihapus** dari partial (penyebab tumpang tindih antar kolom karena lebar kolom berbeda-beda); kini hanya **header beku** (z-index 30, latar opak) — tidak ada lagi kolom yang saling menindih.
- **Input gaya spreadsheet (tanpa border)**:
  - Leger: `leger-input`, `sh-input`, `sts-input`, `kktp-input` → **border 0**, latar transparan; saat **klik/fokus tidak ada border/ring** — hanya latar halus (`rgba(primary,.06)`).
  - Tabel wizard: semua input di `.js-tools-table` juga borderless tanpa ring fokus.
- **Modal "Nilai Murni Belum Diisi" disamakan dengan template**: markup eksplisit gaya **lord-icon + zoomIn** (header close, ikon animasi, judul, pesan, tombol "Tutup" + "Isi Nilai Murni Sekarang") — sebelumnya di halaman Leger masih markup lama yang tidak sesuai. Berlaku di **Leger (tab Mapel)** dan **Buku Nilai (w3)**.
- Test: `SumatifHarianDinamisTest` + `BukuNilaiKonsolidasiTest` = **36 test / 241 assertion lulus**; `view:cache` sukses.

## 120. Leger & Buku Nilai: Lapisan z-index, Input Spreadsheet, & Modal Nilai Murni
- **Bug lapisan tabel**: selector ID (`#tab-leger th[...]`) mengalahkan aturan kelas — aturan freeze kini **hanya menyasar `td`**; header identitas dikunci `z-index: 40` lewat `#tab-leger thead th[data-sort=...]`. Hierarki: **header identitas 40 > header lain 30 > body identitas 10 > body nilai (auto)**.
- **Input gaya spreadsheet**: semua input tabel Leger & Mapel borderless; saat **klik/fokus tanpa border & ring** — hanya latar halus.
- **Modal "Nilai Murni Belum Diisi"** di Leger (tab Mapel) & w3 memakai **markup eksplisit template lord-icon/zoomIn** (ikon sesuai konteks).
- Test: `SumatifHarianDinamisTest` + `BukuNilaiKonsolidasiTest` = **36 test / 241 assertion lulus**.

## 121. Perbaikan Skrip Halaman Leger (SyntaxError `kolomColumnsUrl`) & Ring Fokus Biru
- **Akar masalah**: `@yield('script')` dirender **dua kali** (di `master.blade.php` dan `vendor-scripts.blade.php`) → `const kolomColumnsUrl` dideklarasikan ganda → **seluruh JavaScript halaman Leger mati** (urut, cari, autosave, header beku, modal kolom).
- Perbaikan: duplikat yield dihapus — yield tunggal tetap di tempat aslinya (`vendor-scripts`).
- `sheet-input:focus` masih memakai **ring biru** → diganti borderless + latar halus; ditambah **aturan catch-all `!important`** untuk semua input tabel Leger & Mapel: `border-color` transparan, `box-shadow: none`, `outline: 0` saat diklik/fokus.
- Regresi baru: skrip kolom wajib dirender **tepat satu kali**.

## 122. Kolom Beku: Mati Otomatis di Layar Kecil + Perbaikan di Wizard 3
- **Media query `max-width: 991.98px`**: kolom beku No/NIS/Nama **dimatikan** di Leger & Mapel (`left: auto`, `z-index: auto`) → tabel **bisa digeser ke samping di mobile**; header tetap beku di atas saat digulir vertikal.
- **Wizard 3 (Nilai Sumatif)**: hierarki lapisan diperbaiki — sebelumnya **header identitas z-10 tertindih header lain z-30** (dari partial) sehingga terlihat tanpa beku; kini **header identitas 40 > header 30 > body identitas 10**.
- Lebar kolom w3 diselaraskan (50 / 90 / 140 px) dan `min-width` inline dihapus → tidak ada kolom saling menimpa; kolom beku mati di layar kecil.

## 123. Penyesuaian Leger & Wizard 3: Kolom Ekstra, CSS W3 Selalu Aktif, & Lebar Predikat
- **Kolom ekstra di ujung tabel Leger dihilangkan**: header `PRESENSI` tercakup **dua kali** (`rowspan="2"` + filler `<td colspan="3">` di baris sub-header) → 3 sel hantu menonjol di ujung tabel; `rowspan` pada PRESENSI dihapus.
- **Wizard 3 tanpa kolom beku (temuan akar)**: seluruh blok `<style>` w3 — termasuk `.nilai-scroll` & semua aturan freeze — **terjebak di dalam `@if(session('success'))`** sehingga hanya aktif sesaat setelah simpan. Blok dipindah **keluar** dari `@if`; script modal sukses tetap di dalam `@if`.
- Focus input w3 kini **tanpa ring biru** (konsisten dengan Leger).
- **Kolom Predikat dilebarkan** `104px → 150px`; badge predikat `min-width: 130px` + `white-space: nowrap` → teks **"Mumtaz Murtafi'"** tidak terpotong.
- **Regresi baru**:
  - `assertLegerGridRapi()` — simulasi grid `rowspan`/`colspan` DOM: **semua baris tabel Leger (STS & SAS) wajib selebar sama** (mendeteksi kolom ekstra/kurang).
  - CSS kolom beku w3 wajib tampil **tanpa** session sukses.
- Test: `SumatifHarianDinamisTest` + `BukuNilaiKonsolidasiTest` = **36 test / 261 assertion lulus**; `view:cache` sukses.

## 124. Simpan KKTP di Tab Leger + Sel KKTP Ikut Beku
- **Gagal menyimpan KKTP (tab leger) diperbaiki**: blok simpan `leger_kkm` ternyata **berada di dalam** cabang `if (tab === 'leger' && filled('leger_sts'))` — padahal sejak leger baca-saja, `leger_sts` **tidak pernah dikirim** → setiap perubahan KKTP jatuh ke fallback *"Data tidak valid"* (indikator autosave menampilkan "Gagal menyimpan").
  - Cabang leger kini juga aktif saat **hanya `leger_kkm`** yang dikirim; pesan JSON menyesuaikan: **"KKTP disimpan."** / "Leger disimpan."
  - Ditambah **validasi server** untuk nilai KKTP: `nullable|numeric|min:0|max:100`.
- **Sel KKTP ikut beku horizontal**: sel `th` KKTP di baris KKTP (sejajar kolom No/NIS/Nama) kini `position: sticky; left: 0; z-index: 40` → tetap terlihat saat tabel digeser ke samping, disamakan dengan kolom identitas; otomatis dimatikan di layar kecil bersama kolom beku lainnya.
- **Regresi baru**: `test_kktp_tab_leger_tersimpan_tanpa_leger_sts` — simpan KKTP tanpa `leger_sts` → JSON `success:true` + baris `subject_kktp` ter-update + `kktp_id` buku tersinkron + CSS beku sel KKTP dirender.
- Test: `SumatifHarianDinamisTest` + `BukuNilaiKonsolidasiTest` = **37 test / 268 assertion lulus**; `view:cache` sukses.

## 125. Increment 1 — Fondasi Hak Akses & Konsistensi UI (Absensi, Data Nilai, Peta Kurikulum, Bank Soal, Leger TU, Monitoring Kehadiran)
- **Helper bersama baru `app/Support/UserJabatan.php`**: membaca jabatan dari **semua sumber** (`employment` utama, seluruh `employments`, relasi `jabatanRel`) + varian nama TU. Dipakai di `NilaiKelasController::legerAccessFor()`, `NilaiController::isNilaiPrivileged()`, dan `AbsensiHarianController::canFilterRombel()` — memperbaiki 403 palsu untuk akun TU yang jabatannya tersimpan di `jabatan_id` atau memakai varian nama (*TU Sekolah*, *Kepala TU Sekolah*, *Staf TU Sekolah*).
- **Absensi Kelas — hak akses**:
  - Backend: guard baru `assertAbsensiAccess()` pada `index`, `create`, `recap`, `recapSemester` — guru tanpa kewenangan (bukan wali, bukan KSP/Waka/Kurikulum/Kesiswaan/TU) **ditolak 403**; diletakkan *setelah* pemeriksaan scope rombel agar semantik 404 lama tetap terjaga.
  - `canFilterRombel()` kini juga mengenali **Koordinator Kesiswaan** dan **Tim Kesiswaan**.
  - Sidebar: menu *Absensi Kelas Saya* di seksi KBM hanya untuk guru **ber-kewenangan** (bukan guru biasa); wali kelas mendapat entri di seksi **Wali Kelas**; seksi *Absensi Kehadiran* menampilkan *Absensi Peserta Didik* untuk KSP/Waka/Kurikulum/Kesiswaan/TU.
- **Data Nilai — kewenangan & alur**:
  - `isNilaiPrivileged()` baru: KSP/Waka/Kurikulum/TU dikenali dari **jabatan/tugas nyata**, bukan hanya permission yang tidak terdaftar — memperbaiki **Waka yang hanya melihat 2 rombel** (kini seluruh rombel satuan pendidikan).
  - `user.schools.nilai.index`: wali kelas → **redirect ke Leger kelasnya**; guru mapel → **redirect ke Buku Nilai Saya**; hanya KSP/Waka/Kurikulum/TU yang melihat halaman Data Nilai umum.
  - Sidebar: menu *Data Nilai* hanya tampil untuk KSP/Waka/Kurikulum/TU (guru & wali memakai jalur masing-masing).
- **Peta Kurikulum**: guru kini melihat **seluruh struktur kurikulum jenjang** dalam cakupan satuan pendidikan (filter "hanya mapel diampu" dihapus). Hak edit tetap terjaga: KKTP via `$canEditKktp` + guard `saveKktp`, CP/TP/ATP/Perangkat via `KurikulumAccess::canManageSubject()`.
- **Bank Soal**:
  - `BankSoal::scopeAccessibleBy()` kini **sadar-rumpun**: soal bersama dalam rumpun kewenangan guru (satu satuan pendidikan, atau central/public_pool lintas satuan yang diizinkan) dapat dibuka. Tidak membuka bank rumpun lain/palsu.
  - Halaman detail bank & detail repository (yang sebelumnya 403) memakai scope yang sama — daftar dan guard detail kini **selaras**.
  - Bug filter `is_public` (`filled(...) !== null` yang selalu true) diperbaiki; index bank memakai `accessibleBy()` yang sama dengan guard detail.
  - Quick scope *Serumpun* pada repository kini berbasis **rumpun mapel** (bukan sekadar `subject_id` yang sama).
  - Sidebar master: 4 tautan Bank Soal rusak (single-brace + route butuh id) diganti satu tautan daftar yang valid.
- **Leger & Cetak Rapor TU (403)**: resolver jabatan tangguh (lihat helper) — akun TU dengan jabatan via relasi/varian nama kini memperoleh akses `full` sesuai kebijakan.
- **Monitoring Kehadiran (403)**: menu yang menautkan `user.teacher-qr.waka-dashboard`/`history` (satuan-pendidikan, koordinator-kurikulum, _koor-guru-shared, humas-personalia, pimpinan) kini digerbangi **`canPermission('teacher-attendance_view')`** — sejalan dengan middleware route, sehingga tidak ada lagi klik-through 403; pengguna berhak tetap melihat & membuka.
- **Regresi test baru**: guard absensi (403 guru biasa; 200 wali & TU), menu sidebar absensi, pemisahan sidebar *Absensi Kelas Saya*, alur Data Nilai Waka/wali/guru + sidebar, Peta Kurikulum seluruh jenjang tanpa hak edit, bank privat serumpun dapat dibuka & rumpun lain 403, gate menu Monitoring mengikuti permission.
- Test: `SumatifHarianDinamis + BukuNilaiKonsolidasi + KurikulumPembelajaran + SubjectGroupPolicy + AttendanceSyncKehadiran` = **75 test (+2 risky) / 533 assertion lulus**; `AttendanceSyncKehadiran` sendiri **10 (+2 risky) / 51 assertion**; `view:cache` sukses.
- Catatan pra-ada (bukan dari perubahan ini): `BankSoalTerpusatTest::test_sumatif_hanya_menerima_paket_final` gagal karena `UniqueConstraintViolationException` pada fixture test itu sendiri (`TeacherAdminBook::create` ganda pada kombinasi unik `unique_admin_book_per_teacher`); file test tidak diubah dalam increment ini.

## 126. Increment 2 (Bagian 1) — Komponen Empty State Bersama, Pekan Efektif, & Konteks Otomatis Kisi-Kisi
- **Komponen bersama baru `x-empty-state`** (`resources/views/components/empty-state.blade.php`): pola gtk/index (lord-icon + judul + pesan + aksi opsional), mendukung mode tabel (`colspan`) dan mode blok, serta memuat `lordicon.js` **sekali per halaman** via `@once @push('scripts')`.
- **Pekan Efektif — halaman guru/satuan pendidikan**:
  - Kartu ringkasan kini menampilkan data yang sebelumnya tidak tampil: **total minggu semester** dan **total hari libur** (dari `ringkasan`).
  - Header "Rincian Pekan" diberi **badge sumber data**: "Tersimpan (hasil generate)" vs "Hitung langsung dari Kaldik".
  - Tabel Alokasi JP menampilkan **kode mapel** (subject_code); semua empty-state diganti komponen bersama.
- **Pekan Efektif — halaman Waka**: empty-state memakai `x-empty-state`; pagination memakai partial bersama `shared._pagination`; **route sidebar waka yang tidak ada** (`waka.pekan-efektif`) diperbaiki ke `waka.pekan-efektif.index`; item sidebar master (single-brace) untuk Pekan Efektif diperbaiki.
- **Kisi-Kisi Soal — konteks otomatis sesuai penugasan (Increment 2 item 4A)**:
  - **Mapel** pada form hanya berisi mapel **penugasan guru** (TeachingAssignment aktif, TA berjalan, sekolah); Kurikulum/TU/KSP/Waka tetap melihat seluruh mapel satuan. Tingkat/Fase mengikuti penugasan; jika hanya satu mapel/tingkat langsung terpilih.
  - **Tahun Ajaran** otomatis memilih TA aktif (diberi label "(aktif)"), **Semester** otomatis mengikuti semester berjalan.
  - **Validasi backend baru**: guru yang mengirim mapel di luar penugasannya ditolak `422` — konteks tidak bisa dipalsukan dari klien.
  - **Dropdown TP terfilter** per Mapel + Tingkat (partial baru `evalusi/kisi-kisi/_tp-filter-script.blade.php`), label TP memuat **kode TP** untuk keterlacakan indikator.
  - **Bug enum C6 diperbaiki**: nilai valid `C6_mencipta` (validasi/UI) kini dinormalisasi ke enum database `C6_mencita` saat simpan — sebelumnya simpan item C6 berisiko gagal/terpotong. Label UI memakai "C6 — Mencipta".
  - **Bug halaman edit**: form edit sebelumnya **tidak memiliki field Mapel/Tingkat/TA** padahal validasi `update()` mewajibkan `subject_id` (update selalu gagal). Field dilengkapi; dropdown edit kini dibatasi konteks sekolah (sebelumnya `Subject::all()`, `GradeLevel::all()`, `BankSoal::all()` lintas satuan).
- **Test**:
  - Baru: `tests/Feature/KisiKisiKonteksTest.php` — 3 test / 21 assertion (form mengikuti penugasan; mapel luar penugasan 422 + normalisasi C6; halaman edit memuat field mapel & update berhasil).
  - `KaldikPekanEfektifTest` diperluas — ringkasan baru & badge sumber diuji (10 test / 58 assertion).
  - Regresi batch: `KisiKisiKonteks + PesertaDidikP0 + PaketNaskahPolicy` = **35 test / 199 assertion lulus**; `view:cache` sukses.

## 127. Increment 2 (Bagian 2) — Relasi Mapel ↔ Kelas: Propagasi ke Rombel, `track`, & Validasi Ter-scope
- **Akar masalah "mapel baru tidak muncul di kelas"** ditemukan & diperbaiki:
  1. **`GradeLevelSubject::$fillable` tidak memuat `track`** — semua `updateOrCreate([... 'track' => ...])` membuang nilai saat INSERT, sehingga baris peminatan tersimpan `NULL` dan **baris duplikat menumpuk** tiap seeder ulang (unique menganggap NULL berbeda). `track` kini fillable.
  2. **Tidak ada propagasi** dari mapel tingkat ke rombel — mapel yang ditambahkan ke kelas/tingkat tidak pernah muncul di layar berbasis rombel (Buku Nilai, Jadwal, Plotting, Leger). `GradeLevelController::addSubject` kini **mempropagasi otomatis** ke seluruh rombel aktif pada tingkat yang sama (`study_group_subjects`): baris yang pernah di-soft-delete dihidupkan kembali, dan jam mengajar yang sudah diatur di rombel **tidak ditimpa**.
  3. **Validasi `addSubject` tidak ter-scope satuan** — kini `Rule::exists('subjects')` dibatasi `school_id` kelas + `is_active`, sehingga mapel satuan pendidikan lain tidak bisa dipaksakan dari klien.
  4. **`removeSubject`** (hapus mapel dari tingkat) kini **menonaktifkan** mapel pada rombel-rombel tingkat itu (bukan menghapus) sehingga riwayat nilai tetap aman.
- **Test baru `MapelKelasPropagationTest`** (4 test / 11 assertion): propagasi ke rombel aktif, penolakan mapel lintas satuan, penonaktifan saat dihapus dari tingkat, dan regresi `track` tanpa duplikasi.
- Regresi: `CurriculumStructureSeederTest + KurikulumMasterUiTest + CurriculumStructureMenuTest` = **30 test / 163 assertion lulus**; `view:cache` sukses.

## 128. Increment 2 (Bagian 3) — Kartu Soal & Dokumen Validasi Soal
- **Kartu Soal (baru)** — halaman web + cetak per **paket soal**, struktur sesuai kebijakan:
  **Nomor Soal | Indikator | Soal | Kunci Jawaban | Level Kognitif | Skor Penilaian**.
  - Isi diambil dari **butir soal sebenarnya** (`PaketSoalItem::kontenFinal()` — memakai snapshot bila paket sudah dibekukan), termasuk opsi jawaban dan total skor.
  - **Indikator** ditelusuri dari TP butir soal (kode + deskripsi); **Level Kognitif** dari item kisi-kisi paket (map TP → level, label ramah C1–C6).
  - **Perlindungan kunci**: penyusun butir soal selalu melihat kuncinya; peran lain mengikuti kebijakan serumpun/tim kurikulum (`KurikulumAccess::canViewAnswerKeys`). Peran tak berwenang melihat badge **"Disembunyikan"** dan peringatan di atas tabel. Halaman cetak mengikuti aturan yang sama.
  - Tautan **"Kartu Soal"** ditambahkan pada halaman detail paket.
- **Validasi Soal (baru)** — dokumen validasi per paket dengan identitas **"VALIDASI SOAL {JENIS} {SEMESTER} TA. {TAHUN AJARAN}"**:
  - Identitas asesmen: Mata Pelajaran, Kelas, Tahun Ajaran, Semester, Jenis Asesmen, Paket.
  - **Pembuat Soal** (penyusun kisi + penulis butir), **Validator** (dari penugasan review serumpun yang sah + hitungan setuju/revisi/menunggu), **Soal yang Perlu Diperbaiki** (nomor soal, indikator, validator, status, catatan), dan **Rekomendasi** (catatan validator).
  - Tidak ada validator/status valid palsu — seluruh isi berasal dari `ReviewAssignment` workflow existing.
  - Versi web + versi **cetak** (blok tanda tangan Pembuat/Validator/Tim Kurikulum).
  - Tautan **"Validasi Soal"** ditambahkan pada halaman detail paket.
- **Test baru** (di `PaketNaskahPolicyTest`): kartu soal menampilkan butir + melindungi kunci (penyusun melihat, TU disamarkan) dan halaman cetaknya; dokumen validasi menampilkan pembuat, validator, catatan perbaikan, dan versi cetak.
- Regresi modul evaluasi: `PaketNaskahPolicy + BankSoalTerpusat + KisiKisiKonteks + PesertaDidikP0` = **59 test / 412 assertion lulus**; `view:cache` sukses.

## 129. Tindak Lanjut: Template Modal (Kelola Mapel Kelas), Format Validasi Soal, & Kolom Kisi-Kisi
- **Modal konfirmasi/informasi WAJIB memakai template bersama** (penegasan kebijakan UI):
  - `components.modal-confirm`, `modal-result`, dan `modal-success` kini **memuat `lordicon.js` otomatis** (sekali per halaman) — template selalu berfungsi tanpa perlu script manual per halaman.
  - **Halaman kelola mapel kelas** (`grade-levels/show`): hapus mapel dari tingkat tidak lagi memakai `confirm()` native browser — kini **modal lord-icon/zoomIn** (judul, pesan konsekuensi "riwayat nilai tetap aman", tombol **Ya, Hapus**), dengan form tersembunyi + JS yang mengisi `action` & nama mapel dari tombol baris.
  - **Halaman mapel rombel** (`study-group-subjects/index`): aktif/nonaktif assignment mapel kini melalui modal konfirmasi yang sama (bukan `confirm()`).
- **Validasi Soal — menyesuaikan format lembar** sesuai kebijakan:
  - Judul dokumen: `VALIDASI SOAL {JENIS} {SEMESTER} TA. {TAHUN AJARAN}`;
  - Header identitas: `MATA PELAJARAN: …` dan `KELAS: …`;
  - Tabel 4 baris: **Pembuat soal | Validator | Soal yang perlu diperbaiki | Rekomendasi** dengan ruang catatan memadai;
  - Versi web + versi cetak (blok tanda tangan Pembuat/Validator/Tim Kurikulum).
- **Kisi-Kisi Soal — kelengkapan kolom format**:
  - Migrasi baru `2026_10_16_100000_add_detail_columns_to_kisi_kisi_soal_items`: kolom `materi`, `bentuk_soal`, `nomor_soal` pada item kisi-kisi;
  - Form create/edit + halaman detail kini menampilkan: **No | Indikator | Materi | Bentuk Soal | Nomor Soal | Level Kognitif** (+ Jumlah/Bobot/Total Bobot);
  - Level kognitif berlabel ramah **C1–C6**; bentuk soal memakai label (Pilihan Ganda, PG Kompleks, Benar/Salah, Menjodohkan, Isian, Uraian) selaras enum `soal.tipe_soal`;
  - Stub SQLite `tests/TestCase.php` diselaraskan; empty-state bersama di tabel detail kisi-kisi.
- **Test**: `KisiKisiKonteksTest` diperluas (kolom baru tersimpan & tampil di halaman detail/edit); regresi `KisiKisiKonteks + PaketNaskahPolicy + KurikulumMasterUi + PesertaDidikP0 + MapelKelasPropagation` = **67 test / 382 assertion lulus**; `view:cache` sukses; migrasi dev dijalankan.

## 130. Modul Kedisiplinan Disamakan dengan UI ALIM + Perbaikan Error Supervisi
- **Dashboard Kedisiplinan** disamakan dengan pola dashboard ALIM: kartu statistik `card-animate` (avatar + label + catatan), header `border-bottom-dashed`, empty-state bersama, dan **query "Ringkasan per Santri" dipindahkan dari view ke controller** (view tidak lagi menjalankan query).
- **Poin Pelanggaran (daftar)**: header aksi lengkap (Dashboard / Rekap per Santri / Ekspor PDF / Tambah), filter rapi (`form-select-sm`), tabel standar, pagination bersama, empty-state bersama, dan **modal konfirmasi hapus memakai template lord-icon** — sebelumnya tombol hapus **tidak memiliki handler sama sekali** (bug fungsional: klik tidak melakukan apa pun).
- **Rekap Poin per Santri**: desain custom (`table-striped`, `card-shadow-hover`, header sekunder) diganti pola standar — kartu ringkasan, legenda tingkat rapi, filter konsisten, tabel `table-hover align-middle`, pagination & empty-state bersama.
- **Detail Poin per Santri**: kartu ringkasan standar (Total Poin, Jumlah Pelanggaran, Tingkat Pelanggaran, Identitas), riwayat pelanggaran kronologis, pagination & empty-state bersama.
- **Supervisi — perbaikan error (dari audit)**:
  1. **Sidebar waka** memanggil `route('waka.supervisi')` dan `route('waka.ekstrakurikuler')` yang **tidak terdaftar** → `RouteNotFoundException` mematikan semua halaman waka. Diperbaiki ke `.index`.
  2. Controller Supervisi menyentuh **kolom yang tidak ada di `gtk_profiles`** (`name`, `school_id`) → 500. Kini memakai `gtk.user.name` dan filter employment (`gtk_employments.school_id`), urut nama user.
  3. `store()` kini **memvalidasi konteks satuan pendidikan** dan **menyinkronkan `gtk_name`/`observer_name` dari relasi** (kolom denormalisasi), NUPY benar via accessor.
  4. View create/edit/index: `$gtk->name` → `$gtk->user?->name`.
- **Test baru `SupervisiSmokeTest`** (3 test / 11 assertion): route sidebar waka terdaftar; halaman index/create memuat daftar GTK tanpa kolom invalid; simpan supervisi menyinkronkan nama GTK.
- Regresi: `SupervisiSmoke + PesertaDidikP0 + KurikulumMasterUi + MapelKelasPropagation + DormitoryRouteSmoke` = **61 test / 291 assertion lulus**; `view:cache` sukses.
- **Berikutnya**: instrumen Supervisi sesuai format (Guru Umum & Guru Agama) + **fitur pengaturan item & poin instrumen**; penyamaan halaman create/edit/show kedisiplinan; lanjutan Increment 3–5.

## 131. Perbaikan `$tahunAjaran` (Rapor GTK/Supervisi) & Tipografi Halaman Pelanggaran
- **Error "Undefined variable $tahunAjaran"** pada halaman **Rapor GTK → Tahunan** (target menu *Supervisi & Kinerja GTK*):
  - akar: view memakai `{{ $tahunAjaran ? ... }}` pada pemakaian **kedua tanpa `??`**, sementara `RaporGtkController@tahunan` hanya mengirim `userId`.
  - perbaikan: controller kini mengirim **tahun ajaran aktif** (tahun mulai, fallback tahun berjalan) dan judul rekap menampilkan `2026/2027`.
  - regresi test baru: `test_halaman_rapor_gtk_tahunan_target_menu_supervisi_tidak_error`.
- **Tipografi halaman pelanggaran diseragamkan** (font terlalu besar & tidak konsisten):
  - akar: kelas `stat-label` (11px) dan `card-animate` berasal dari style modul kurikulum yang **tidak dimuat** di halaman pelanggaran → label kartu jatuh ke ukuran default (besar).
  - dibuat partial bersama baru **`shared/_styles.blade.php`** (filter-badge, stat-label, card-animate, table-freeze) dan dimuat di **7 halaman pelanggaran** (dashboard, daftar, rekap, detail, show, create, edit).
  - badge `fs-6` (poin) pada rekap & show dihapus → kembali ke ukuran badge standar (`fw-semibold`/`bg-*-subtle`), konsisten dengan halaman lain.
- Test: `SupervisiSmoke + DormitoryRouteSmoke + PesertaDidikP0` = **32 test / 140 assertion lulus**; `view:cache` sukses.

## 133. Kenaikan Kelas (1A→2A), Filter Santri per Rombel, Rekap Tahunan GTK, & Fondasi Instrumen Supervisi
- **Bug kenaikan kelas "1A → 2A tidak ada datanya" — 3 akar masalah diperbaiki** (`BulkPromotionController`):
  1. **TA asal keliru**: penutupan histori memakai TA **aktif global**, bukan TA rombel sumber → histori lama tidak tertutup / kelas tujuan tampak kosong. Kini memakai `study_group->academic_year_id`.
  2. **Rombel tujuan tidak paralel**: auto-target mengambil **rombel pertama** tingkat tujuan (bisa 2B/2C), bukan kelas paralel. Helper baru `targetStudyGroupId()` memetakan **1A → 2A** (cocokkan suffix nama), fallback baru ke rombel pertama.
  3. **Enroll senyap**: `enrollStudent()` berhenti diam bila sudah ada baris pada TA tujuan (termasuk baris sumber saat TA sama) → kelas tujuan kosong tapi dihitung "berhasil". Kini **idempoten**: baris yang ada dipindahkan/diaktifkan; kapasitas dihitung tanpa baris santri itu; pesan sukses jujur.
  - Daftar santri pada halaman kelas tujuan juga memakai **TA rombel terpilih** (bukan TA aktif global).
  - Regresi baru `BulkPromotionTest` (2 test / 8 assertion): paralel 1A→2A, kelas tujuan berisi data, re-run tidak menggandakan, dan skenario "rombel tujuan penuh" tetap dipertahankan.
- **Filter halaman Santri per Rombel dirapikan**: lebar kolom filter diseimbangkan (6+3+3) dan **filter jenis kelamin dihapus** (modal lanjutan + badge cepat) sesuai kebijakan.
- **Rekap Tahunan GTK** disamakan dengan pola `gtk/index.blade.php`: breadcrumb menggantikan header gradien custom, kartu statistik `card-animate` + `stat-label`, header `border-bottom-dashed`, tabel `table-hover align-middle` + `table-light`, badge predikat `bg-*-subtle`, `x-empty-state`, `shared._pagination`; **dropdown filter (GTK & Tahun Ajaran) kini terisi** dari kontroler (sebelumnya kosong).
- **Fondasi Instrumen Supervisi (format Bapak)**:
  - Migrasi baru `2026_10_16_200000_create_supervisi_instrumen_tables`: `supervisi_instrumen` (jenis: guru_umum/guru_agama, bisa per-satuan atau template global) → `supervisi_instrumen_bagian` (I, IIA, IIB, IIC) → `supervisi_instrumen_item` (teks, urutan, **bobot/poin**, butuh_kondisi, sub-item a–g via `parent_item_id`).
  - Model `SupervisiInstrumen`, `SupervisiInstrumenBagian`, `SupervisiInstrumenItem`.
  - **Seeder `SupervisiInstrumenSeeder`** (idempoten): **Guru Umum** (4 bagian / 37 item termasuk sub-item a–g) & **Guru Agama** (4 bagian / 25 item) — teks butir sesuai format Bapak.
- Test: `BulkPromotion + SupervisiSmoke + PesertaDidikP0 + DormitoryRouteSmoke` = **34 test / 148 assertion lulus**; `view:cache` sukses; seeder diverifikasi idempoten (run 2× tetap 37 item, 2 instrumen).

## 134. Tarik Santri ke Rombel (Fix) + Pengaturan Instrumen Supervisi (Item & Poin)
- **"Tarik Santri ke Rombel" diperbaiki** (`StudyGroupApiController`) — 2 akar masalah:
  1. `unassignedStudents()` mengecualikan santri yang aktif **lintas semua tahun ajaran** → daftar kandidat kosong. Kini pengecualian dibatasi **TA rombel** yang dituju.
  2. `bulkAddStudents()` & `addStudent()` memakai **TA aktif global** (bukan TA rombel) dan mengecek duplikat setelah deaktivasi → santri masuk ke TA yang salah / baris berganda. Kini memakai **TA rombel**, memindahkan baris yang ada (idempoten), dan menautkan `leave_date` dengan benar.
  - Regresi baru `StudyGroupPullStudentsTest` (1 test / 6 assertion).
- **Pengaturan Instrumen Supervisi (fitur yang diminta: mengatur isian + poinnya)**:
  - Skema: `supervisi_penilaian` (skor 1–4, kondisi Ya/Tidak, catatan per item) + kolom hasil pada `supervisi` (`instrumen_id`, `skor_total`, `skor_maks`, `nilai_akhir`, `ketercapaian`); model `SupervisiPenilaian` + relasi.
  - Halaman **Instrumen Supervisi** (`waka.supervisi-instrumen.*`): daftar instrumen (bawaan/satuan + jumlah bagian & item), tombol **Duplikat ke Satuan**, **Tambah Instrumen**.
  - **Editor instrumen**: ubah identitas; kelola **bagian** (kode/nama/urutan, hapus via modal template); kelola **item** inline — teks, **poin (bobot)**, kolom Ya/Tidak, aktif/nonaktif, naik/turun urutan, hapus, **sub-item a–g** — plus form tambah item/sub-item/bagian.
  - Kebijakan: instrumen **bawaan baca-saja** (duplikat dulu ke satuan) → tidak ada perubahan lintas satuan; instrumen satuan hanya bisa diubah satuan pemiliknya (guard backend).
  - Sidebar waka menambah tautan **Instrumen Supervisi**.
- Test: `SupervisiSmoke` bertambah skenario pengaturan (duplikat 4 bagian/37 item, tambah item dengan poin, 403 untuk edit template global) → **5 test / 21 assertion**; regresi batch `SupervisiSmoke + StudyGroupPullStudents + BulkPromotion + PesertaDidikP0` = **0 gagal**; `view:cache` sukses.

## 135. Audit Kenaikan Kelas/Mutasi, Menu Supervisi, UI Penilaian GTK, & Profil Santri (Histori + Leger/Rapor per TA)
- **Kenaikan kelas — audit TA & pemilihan rombel tujuan** (`BulkPromotionController` + view):
  - **TA sama = MUTASI KELAS** (bukan kenaikan): wajib memilih rombel tujuan eksplisit; auto-naik ditolak (backend 422 + aturan hanya untuk aksi `promote`, aksi lulus/mutasi keluar tetap boleh). Pesan dibedakan: **"Mutasi kelas selesai…"** vs "Kenaikan kelas selesai…".
  - **Rombel tujuan kini dapat dipilih di form** (dropdown terisi dari seluruh rombel aktif satuan lintas TA — sebelumnya dropdown di modal bisa kosong karena filter semester). Contoh 3C → 4B dapat dipilih langsung.
  - **Modal/peringatan bila rombel tujuan lebih rendah**: banner peringatan di konfigurasi + di dalam modal konfirmasi, label tombol berubah menjadi **"Ya, Mutasi Kelas"** — proses tetap diizinkan sebagai koreksi penempatan.
  - Regresi: `BulkPromotionTest` (TA sama + target → mutasi; paralel 1A→2A; idempoten) & `PesertaDidikP0Test` (TA sama tanpa target → ditolak) — **0 gagal**.
- **Menu Supervisi di sidebar**: tautan **Supervisi Pembelajaran** & **Instrumen Supervisi** ditambahkan pada sidebar Satuan Pendidikan (KSP/Waka/Kurikulum/TU) selain yang sudah ada di sidebar Waka.
- **UI halaman Penilaian GTK** (akademik, disiplin, kepribadian, administrasi) disamakan dengan `gtk/index.blade.php`: header gradien custom diganti **breadcrumb** + tombol navigasi antar-halaman, kartu statistik `card-animate` + `stat-label` + warna `bg-*-subtle`, tabel `table-hover align-middle table-freeze` + `thead table-light`, badge predikat `bg-*-subtle`, **`x-empty-state`**, dan **`shared._pagination`**. Smoke test baru: 4 halaman + pola UI standar.
- **Profil Santri — audit & penyempurnaan**:
  1. **Histori kelas tidak hilang**: baris `student_class_histories` tidak pernah dihapus saat kenaikan/mutasi (ditutup dengan `leave_date`) dan tab **Riwayat Kelas** menampilkan seluruh TA (Aktif/Selesai) dengan wali kelas & periode masuk→keluar.
  2. **Akses Leger & Rapor per tahun ajaran**: setiap baris histori kini memiliki tombol **Leger** (leger kelas TA tersebut) dan **Rapor** (cetak rapor individu TA tersebut; `raporCetak` sudah mendukung `academic_year_id`+`semester`), ditampilkan sesuai kewenangan leger pengguna.
  3. **Desain mengikuti template profil GTK**: struktur `profile-foreground` + `profile-wrapper` + nav-pills sudah sama; memperbaiki sisa teks salah ("tes {{name}}") pada overlay.
- Test: `SupervisiSmoke + BulkPromotion + StudyGroupPullStudents + PesertaDidikP0` = **38 test / 188 assertion lulus**; `view:cache` sukses.

## 136. ARSIP ROMBEL — Seluruh Data Rombel per Tahun Ajaran Wajib Tersimpan & Tetap Dapat Dilihat
**Analisis akar masalah**: data SEBENARNYA tersimpan per tahun ajaran (verified di DB: histori lama nonaktif + 6 baris arsip), tetapi **halaman-halaman menampilkannya kosong** karena semua query menyaring `is_active = true` tanpa memperhatikan tahun ajaran rombel → rombel TA lama terlihat "hilang".

**Aturan baru — keanggotaan rombel = berdasarkan TAHUN AJARAN rombel (bukan hanya baris aktif):**
- **Santri per rombel** (`StudentController@index`): anggota rombel TA lama tetap tampil; anggota yang sudah pindah/naik diberi **badge "Pindah"**; muncul **banner "Arsip rombel TA …"** saat membuka rombel non-aktif. Input (tulis) tetap hanya untuk anggota aktif.
- **Leger & tab Mapel** (`NilaiKelasController@sts`): daftar santri memuat seluruh anggota TA tersebut (aktif dulu), anggota arsip diberi badge **"Pindah"**; **input nilai (tab Mapel) otomatis melewati anggota nonaktif** (`@continue`).
- **Rekap kehadiran** (`recapDetail`, `recap`, `recapSemester`): memuat seluruh anggota rombel TA tersebut — rekap presensi TA lama tetap lengkap.
- **Buku mapel & guru** (`teacher_admin_books`) dan **nilai** (`admin_nilai_sumatif`/formatif) memang per TA dan **tidak pernah disentuh** oleh kenaikan kelas; dibuktikan test.
- **Untuk TA baru**: struktur mapel/guru di-provision otomatis (`ensureBooksForSemester`) saat leger TA tersebut dibuka.

**Regresi baru `RombelArchiveTest` (3 test / 9 assertion):**
1. Arsip rombel lama tetap menampilkan anggota (halaman santri + leger TA lama).
2. Kenaikan kelas **tidak menghapus** buku mapel & nilai TA lama (sts 88 tetap ada; 2 histori: lama nonaktif + baru aktif).
3. Arsip presensi TA lama tetap tampil di rekap semester.

**Perbaikan UI yang menyertai**: badge status anggota arsip, banner arsip TA, konsistensi tabel/badge dengan pola gtk.

Test: `RombelArchive + AttendanceSync + SumatifHarianDinamis` = **44 test / 304 assertion (+2 risky) lulus**; `view:cache` sukses.

## 137. Kenaikan Kelas — Histori Wajib Utuh, Hanya ke Tahun Ajaran Baru, Tanpa Mutasi
**Temuan kritis dari data nyata**: **6 santri memiliki histori NONAKTIF tanpa baris aktif** — promosi menutup kelas lama tetapi **gagal membuat baris baru** (target rombel tidak ditemukan), sehingga santri kehilangan kelas. Akar: penutupan histori dilakukan **sebelum** target dipastikan.
- **Perbaikan inti (anti kehilangan kelas)**:
  1. Target rombel **WAJIB ada SEBELUM** ada perubahan: bila tidak ditemukan → error, **tidak ada data yang diubah** (histori lama tetap aktif).
  2. Urutan operasi dibalik: **enrollment baru dibuat lebih dulu**, baru histori lama ditutup (tetap tersimpan dengan `leave_date`) → **pasangan histori 2A (TA lama) + 3A (TA baru) selalu terbentuk**.
- **Kebijakan halaman kenaikan kelas (sesuai arahan)**:
  1. **Hanya ke TAHUN AJARAN BARU** — TA yang sama/lebih lama ditolak dengan pesan *"gunakan fitur Mutasi Kelas"*.
  2. **Mutasi kelas dikeluarkan dari halaman ini** — panduan + tautan ke fitur **Mutasi Kelas** disediakan (`student-move`).
  3. **Rombel tujuan hanya dari TA baru yang dipilih** dan **hanya tingkat DI ATAS** kelas saat ini (filter server-side + JS).
  4. **Rombel tujuan wajib dipilih** (tanpa opsi "auto" yang tidak jelas); kelas paralel (2A → 3A) **dipilih otomatis** untuk mengurangi klik; bila TA baru belum punya rombel → banner peringatan.
- **Regresi diperbarui/ditambah** (`BulkPromotionTest` + `PesertaDidikP0Test`):
  - TA sama ditolak (errors `to_academic_year_id`) + data tidak berubah;
  - **TA baru tanpa rombel → gagal tanpa menutup histori lama** (regresi 6 santri kehilangan kelas);
  - kapasitas penuh di TA baru → histori lama tetap aktif (skenario dipindah ke lintas-TA);
  - pasangan histori 2A+3A & idempotensi tetap terjaga.
- Test: 5 berkas regresi (BulkPromotion, PesertaDidikP0, RombelArchive, AttendanceSync, SumatifHarian) — **0 gagal**; `view:cache` sukses.
- **Catatan pemulihan**: 6 santri tanpa kelas dapat dikembalikan lewat fitur **Tarik Santri** (ke rombel TA baru) atau **Mutasi Kelas** — keduanya sudah aman.

## 138. Supervisi — Form Penilaian (Skor per Item, Ketercapaian) & Cetak Instrumen
- **Form penilaian supervisi** (`waka.supervisi.penilaian` + `.store`):
  - Pilih **instrumen** (milik satuan atau bawaan) langsung dari halaman penilaian.
  - Seluruh bagian & item (termasuk sub-item a–g) ditampilkan dengan **Kondisi Ya/Tidak**, **Skor A(4)/B(3)/C(2)/D(1)**, dan **catatan per item**.
  - **Ringkasan live** (JS): subtotal per bagian, jumlah skor, skor maksimum, **nilai akhir**, dan **ketercapaian** (86–100 Amat baik · 70–85 Baik · 55–69 Cukup · <55 Kurang).
  - Simpan → menghitung `skor_total`, `skor_maks`, `nilai_akhir`, `ketercapaian` + menyimpan catatan penilai (temuan/rekomendasi/tindak lanjut).
- **Cetak instrumen** (`waka.supervisi.cetak`): format lembar sesuai instrumen Bapak — identitas (sekolah, guru, mapel, kelas/semester, hari/tanggal/jam, TA, supervisor), tabel **No | Aspek | Kondisi Ya/Tidak | A | B | C | D | Ket** dengan baris bagian, **JUMLAH SKOR / SKOR MAKSIMUM / NILAI AKHIR**, tabel **Ketercapaian** dengan penanda pencapaian, **Catatan Penilai**, dan tanda tangan Guru/Supervisor/Kepala.
- **Halaman detail supervisi**: tombol **Isi Penilaian** & **Cetak**, plus kartu **Hasil Penilaian** (instrumen, skor, nilai akhir, badge ketercapaian). Perbaikan nama GTK/Observer (memakai relasi `user`, karena `gtk_profiles` tidak punya kolom `name`).
- **Test baru**: seluruh item diberi skor B(3) → **nilai 75.00 = "Baik"**, jumlah baris penilaian sesuai item, halaman penilaian & cetak dapat dirender (`SupervisiSmokeTest` = **7 test / 47 assertion**).
- Regresi batch (Supervisi + BulkPromotion + RombelArchive + PesertaDidikP0 + AttendanceSync) — **0 gagal**; `view:cache` sukses.

## 139. Kalender Pendidikan — Scroll, Kalender Tahunan, & Privasi Agenda (Satuan/Pribadi)
- **Scroll daftar "Agenda Mendatang" diperbaiki**: `data-simplebar` + `overflow-hidden` diganti scroll asli (`overflow-y:auto`) — daftar kegiatan kini dapat digulir.
- **Tampilan KALENDER TAHUNAN**: tombol **Kalender Bulanan / Kalender Tahunan**; tampilan tahunan menampilkan **12 bulan penuh** (mengikuti TA terpilih, fallback TA aktif) dengan **penanda titik kegiatan** (warna sesuai jenis + tooltip judul), hari ini disorot, dan **klik nama bulan** kembali ke tampilan bulanan pada bulan tersebut (instance FullCalendar diekspos `window.KALDIK_CALENDAR`).
- **Privasi agenda berjenjang**:
  - Migrasi `visibility` (`satuan`/`pribadi`) + `owner_user_id` pada tabel `kaldik`.
  - Scope baru: **Kaldik pondok** (global) terlihat semua GTK · **Agenda satuan** hanya GTK dalam satuan kerja tsb · **Agenda pribadi HANYA pemiliknya** (tidak muncul di kalender/dashboard/agenda orang lain).
  - Form **Tambah/Edit** menambah pilihan **Privasi Agenda**; `store()`/`update()` menetapkan pemilik untuk agenda pribadi dan membersihkannya untuk agenda satuan.
- **Test baru `KaldikPrivasiTest`**: agenda pribadi terlihat pemilik, tidak bocor ke GTK lain; agenda satuan tidak menyeberang unit; kaldik pondok terlihat semua. `KaldikPekanEfektifTest` tetap hijau (**11 test / 68 assertion — 0 gagal**); `view:cache` sukses.

## 140. Agenda Kegiatan Terhubung ke Kaldik (Satu Sumber Data) + Verifikasi Privasi To-do List
- **Agenda Kegiatan (Kalender Akademik) kini memakai SATU sumber data: tabel `kaldik`** — tidak ada lagi halaman statis tanpa data:
  - `KalenderKegiatanController@akademik` memuat agenda (kategori `agenda`) dengan **scope yang sama seperti Kalender Pendidikan**: agenda satuan kerja sendiri + agenda pribadi milik sendiri (pribadi GTK lain tidak bocor); filter Tahun Ajaran.
  - Ringkasan otomatis: **Total Agenda · Berlangsung · Mendatang · Selesai**.
  - **UI disamakan dengan pola ALIM**: breadcrumb, kartu `card-animate` + `stat-label`, header `border-bottom-dashed`, tabel `table-hover` + `table-light`, badge privasi (**Pribadi/Satuan**) & status, `x-empty-state`.
  - **Integrasi navigasi**: tombol **Kalender Pendidikan** (halaman Kaldik) dan **To-do List**, plus **Tambah Agenda** (untuk Admin TU) yang langsung ke form Kaldik.
- **To-do List — privasi & penugasan diverifikasi dengan regresi baru `TodoPrivasiTest`** (1 test / 8 assertion):
  - Tugas **pribadi** hanya terlihat pemiliknya; tugas **delegasi** terlihat pemberi tugas (delegator) dan penerima (owner); tugas pribadi GTK lain **tidak bocor**. Modul sudah menyimpan `owner_id`/`created_by`/`delegated_by` + notifikasi delegasi.
- Test gabungan: `TodoPrivasi + KaldikPrivasi + KaldikPekanEfektif + SupervisiSmoke + RombelArchive` = **0 gagal**; `view:cache` sukses.

## 141. Absensi QR: Pengaturan Toleransi Keterlambatan (Default 5 Menit) & Geolokasi Opsional
- **Service baru `QrAttendanceSettings`** — satu sumber pengaturan absensi QR:
  - **Toleransi keterlambatan default 5 menit** (dapat diatur 0–120); nilai lama pada grup `qr_settings` tetap dibaca.
  - **Geofence opsional**: aktif/nonaktif + titik (lintang/bujur) + **radius 10–5000 m** (default 150).
  - `distanceMeters()` (haversine) + `geofenceStatus()` → **validasi di backend**. Koordinat guru **tidak disimpan** (hanya keputusan izin) — meminimalkan pengumpulan data lokasi.
- **Halaman Pengaturan Absensi GTK** mendapat kartu **"Toleransi Keterlambatan & Lokasi Scan QR"**: input toleransi, switch wajib-lokasi, titik lintang/bujur + radius, dan tombol **"Gunakan Lokasi Saya"** (mengambil titik dari perangkat admin).
  - **Kewenangan dibatasi**: hanya TU/Kepala/Wakil/Kurikulum (403 untuk lainnya; kartu disembunyikan bagi yang tidak berwenang).
- **Scan QR guru**: chip status lokasi + tombol "Ambil Ulang Lokasi"; koordinat dikirim melalui **POST body** (URL signed tetap valid; CSRF disertakan).
  - Tanpa izin/kegagalan GPS → pesan jelas & scan tidak dilanjutkan; **di luar radius** → pesan jarak & batas radius.
- **Sidebar Satuan Pendidikan**: menu **"Pengaturan Toleransi & Lokasi"** (TU/Struktural/Kurikulum).
- **Histori tidak berubah**: status & `late_minutes` tersimpan pada saat scan — mengubah konfigurasi TIDAK mengubah riwayat lama.
- **Test baru `QrAttendanceSettingsTest`** (4 test / 17 assertion): default 5 menit; geofence nonaktif/tanpa titik/dalam radius/luar radius; simpan pengaturan & dibaca ulang; guru biasa 403. Regresi `TeacherQrScanControllerTest + JadwalPergantianJamTest` = **18 test / 198 assertion — 0 gagal**; `view:cache` sukses.

## 142. Absensi QR — Modal Wajib, Izin Sekali, Toleransi per Jenis, Notifikasi Keterlambatan, Audit Log, & Kelonggaran GPS
- **Modal wajib di halaman scan** (`outcome` dari backend → modal berbeda):
  - **success** — lord-icon + "Absensi Berhasil!" (detail jadwal, status, keterlambatan);
  - **error** — QR tidak valid/kadaluarsa, akses sekolah salah;
  - **warning** — kendala lokasi (di luar area/GPS), lewat batas scan masuk/keluar;
  - **info** — jam belum dimulai, salah kelas/tidak ada jadwal, sudah absen lengkap, belum waktunya scan keluar.
  - Semua modal memakai tombol besar + pesan bahasa sederhana + tombol **Scan Ulang**.
- **Izin kamera & lokasi cukup sekali**: diminta otomatis saat pertama membuka halaman, hasil di-cache (`localStorage`); kartu panduan dengan tombol besar **"Izinkan Kamera"/"Izinkan Lokasi"** + panduan jika pernah ditolak (ikon gembok peramban). Jika izin peramban berubah, browser akan menanyakannya lagi saat dipakai.
- **Modal sukses di halaman Pengaturan** setelah menyimpan toleransi/lokasi (lord-icon) menggantikan sekadar alert.
- **Toleransi per jenis** (dapat diatur): **Scan Awal** (default 15 mnt) · **Keterlambatan** (default 5 mnt) · **Batas Scan Terlambat** (default 5 mnt) · **Pulang Cepat** (default 10 mnt).
- **Notifikasi keterlambatan** in-app otomatis ke satuan kerja sekolah (Waka/Kepala/TU/Kurikulum): nama GTK, menit terlambat, kelas, dan jam jadwal.
- **Audit log pengaturan** (`activity_log`, `log_name = qr-settings`): siapa mengubah, kapan, **nilai lama → baru** (toleransi & geometri).
- **Verifikasi GPS dengan kelonggaran akurasi**: jarak efektif = `jarak − akurasi perangkat`, sehingga tidak sering menolak palsu di dalam gedung; pesan penolakan menyertakan info akurasi.
- Test: `QrAttendanceSettingsTest` + `TeacherQrScanControllerTest` + `JadwalPergantianJamTest` = **22 test / 221 assertion — 0 gagal**; `view:cache` sukses.

## 143. Izin Sekali Saat Login + Opsi B (QR Cetak Tetap): Kunci Perangkat & Proteksi Berlapis
- **Modal izin sekali saat login/pertama akses** (partial `layouts/partials/permission-setup`): untuk pengguna guru/struktural —
  tombol besar **Izinkan Kamera**, **Izinkan Lokasi**, **Izinkan Notifikasi** + **Atur nanti**; tampil **sekali per peramban**
  (localStorage), dengan panduan jika pernah ditolak (ikon gembok). Halaman scan tetap memiliki panduan izin sebagai cadangan.
- **Opsi B — QR cetak tetap dengan proteksi berlapis**:
  1. **Jendela waktu scan** (toleransi awal/akhir) — sudah aktif.
  2. **Satu check-in & satu check-out per jadwal per hari** — sudah aktif.
  3. **Kunci perangkat (opsional, baru)**: tabel `teacher_attendance_devices`; perangkat pertama yang dipakai GTK
     didaftarkan otomatis; scan berikutnya wajib dari perangkat yang sama. Dapat diaktifkan per satuan
     (switch di Pengaturan Toleransi & Lokasi) dan tercatat di audit log. ID perangkat persisten dibuat di peramban
     (`alim_device_id_v1`) dan dikirim bersama koordinat.
- **Test baru `TeacherDeviceBindingTest`** (nonaktif → bebas; aktif tanpa perangkat → ditolak; perangkat pertama terdaftar;
  perangkat sama → diizinkan; perangkat lain → ditolak; satu baris per user).
- Test gabungan `TeacherDeviceBinding + QrAttendanceSettings + TeacherQrScanController + JadwalPergantianJam` =
  **23 test / 232 assertion — 0 gagal**; `view:cache` sukses.

## Testing
- `tests/Feature/JadwalPergantianJamTest.php` — generator, konflik, QR end-to-end, jam pelajaran, rekap.
- `tests/Feature/SumatifHarianDinamisTest.php` — SH dinamis, unifikasi kalkulasi, Leger/Rapor STS & SAS, **sheet Leger SAS & kolom lengkap per-mapel, Pembiasaan/Tahfidz/Agama/Umum, index wali → leger**, KKTP, catatan wali.
- `tests/Feature/TeacherQrScanControllerTest.php` — diperbarui agar berjalan di SQLite (snapshot permission + `Event::fake` terarah).
- `tests/Feature/KaldikPekanEfektifTest.php` — pembagian semester, generate pekan efektif dari kalender (minggu/hari/libur/ujian), alokasi JP efektif, policy pengelolaan (Super Admin/Pimpinan vs Satuan Pendidikan/Guru), halaman pekan efektif untuk Guru & Satuan Pendidikan.
- `tests/Feature/KurikulumPembelajaranTest.php` — rantai Kalender → Pekan Efektif → JP Efektif → CP → TP → ATP → Perangkat: fallback JP berjenjang, hub kurikulum, akses CP/TP (tim kurikulum vs guru mapel), urutan TP, indikator alokasi ATP (kurang/pas/lebih), desain Pembelajaran Mendalam perangkat, render semua halaman.
- `tests/Feature/ProtaProsemRpmTest.php` — PROTA dari ATP+Pekan Efektif (snapshot & indikator perlu diperbarui + sinkron), PROSEM distribusi pekan/bulan (libur dilewati, pekan ujian ditandai), RPM terstruktur umum vs agama, akses serumpun (lihat vs ubah), dan **cetak PDF 6 dokumen** (content-type `application/pdf` + magic bytes `%PDF`).
- Smoke MySQL untuk setiap modul dilakukan dengan data sementara yang selalu dibersihkan.
