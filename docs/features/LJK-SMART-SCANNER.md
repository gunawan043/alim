# LJK Smart Scanner — Fitur Pemindai Lembar Jawaban Berbasis Kamera HP

> Status: **implementasi + teruji (backend, web, API mobile, aplikasi Expo)**.
> Tanggal: 15 Oktober 2026 · Engine: `omr-v1.0.0`

Fitur ini menambahkan alur lengkap: **Paket Soal ALIM → LJK siap cetak → santri
mengisi dengan pulpen (tanda silang X) → guru memindai dengan kamera HP →
hasil dibaca otomatis → guru memeriksa yang meragukan → nilai masuk ke modul
nilai resmi ALIM secara aman dan ber-audit.**

Bank Soal tetap menjadi satu-satunya sumber kebenaran soal. Fitur ini **tidak**
membuat bank soal baru dan **tidak** menyalin permanen isi soal/kunci ke tabel
LJK — yang disimpan hanyalah referensi (paket, versi, identitas butir, peserta,
template) beserta sidik jari versi naskah.

---

## 1. Ringkasan audit (kondisi sebelum implementasi)

| Area | Temuan | Keputusan |
|---|---|---|
| Bank Soal / Paket Soal | `bank_soal`, `soal`, `soal_options`, `paket_soal(_items)` lengkap; alur final = approved → published → **snapshot naskah** (`konten_snapshot` + `snapshot_at`) | Pakai `PaketSoalItem::kontenFinal()` sebagai sumber butir & kunci; LJK hanya menyimpan referensi + digest |
| Penilaian resmi | `admin_nilai_sumatif` (STS/SAS/murni/SH) + `teacher_admin_books` (bobot 50/25/25, KKTP, kunci buku); satu jalur tulis resmi `SumatifHarianService::upsertSumatif()` | Sinkronisasi LJK **hanya** lewat jalur ini |
| Proventa | `admin_nilai_sumatif.paket_soal_id` & `exam_attempt_id` sudah ada (nullable) | Tambah `ljk_exam_id` (nullable, aditif) |
| `exam_attempts` / `exam_student_answers` | Tabel dorman; model `StudentAnswer` menunjuk tabel/kolom yang tidak ada; `ExamSchedule` tanpa migrasi | **Tidak dipakai** (menghindari integrasi ke skema rusak); LJK punya tabel sendiri |
| PDF | `dompdf/dompdf ^3.1` terpasang; pola resmi `new Dompdf(new Options)` | Dipakai untuk LJK |
| QR | `simplesoftwareio/simple-qrcode` (PNG butuh imagick — tidak terpasang di audit) | QR dirender sendiri dari matriks `bacon/bacon-qr-code` via **ext-gd** |
| Gambar | Tidak ada paket image; **ext-gd tersedia**, imagick tidak | Mesin OMR ditulis murni dengan GD |
| Kamera web | Pola `<video>`/html5-qrcode sudah dipakai modul QR kelas/izin | Scan LJK mengikuti pola yang sama (`getUserMedia` + canvas → unggah) |
| Aplikasi Expo | Expo SDK 56, `expo-image-picker` terpasang, `expo-camera` **tidak** terpasang | Pakai `expo-image-picker.launchCameraAsync` — tanpa dependensi native baru |
| Peran mobile | `/auth/me` hanya memetakan `is_wali` | Tambah field aditif `role` + `capabilities` (tanpa mengubah kontrak lama) |

---

## 2. Alur guru (end-to-end)

1. **Terbitkan LJK** — menu *Bank Soal & Asesmen → LJK Smart Scanner →
   Terbitkan LJK*. Pilih **buku nilai** (mapel + rombel + TA + semester) dan
   **Paket Soal final** (approved & published). Sistem:
   - memvalidasi paket final + kesesuaian mapel,
   - membekukan **digest naskah** (sha256 urutan butir + identitas + kunci),
   - membangun **template geometri** (barisnya immutable, berversi),
   - mendaftarkan peserta dari rombel aktif + memetakan nomor lembar,
   - menerbitkan lembar (kode unik + QR bertanda tangan + data strip).
2. **Cetak** — *Cetak Semua Lembar (PDF)* atau per peserta. Kertas A4 biasa,
   hitam-putih, tanpa printer khusus. Tombol *Preview* menampilkan HTML lembar
   untuk uji cetak cepat.
3. **Santri mengisi** dengan pulpen hitam/biru: satu tanda silang (X) di dalam
   kotak pilihan. Petunjuk tercetak di lembar.
4. **Guru memindai** — halaman *Pindai LJK* (browser HP: `getUserMedia` +
   kanvas, atau unggah foto) atau **aplikasi Expo** (`expo-image-picker`).
   QR lembar dapat dipindai lebih dulu (html5-qrcode di web) atau peserta
   dipilih manual — **identitas tetap diverifikasi server** lewat data strip.
   Satu foto = satu lembar.
5. **Verifikasi** — halaman *Hasil & Verifikasi*: ringkasan per peserta,
   daftar nomor `ganda` / `meragukan` / `manual`, pratinjau gambar scan
   (route privat), dan koreksi per nomor dengan alasan.
6. **Finalkan & sinkronkan** — `Finalkan Ujian` lalu `Sinkron ke Nilai`.

**Nilai otomatis masuk:** begitu sebuah lembar terbaca **bersih** (tanpa nomor
⚠) — atau setelah koreksi guru membuatnya bersih — nilainya **langsung
disimpan** ke kolom resmi (`sts_murni` / `sas_murni` / kolom sumatif harian)
lewat `SumatifHarianService`, tanpa menunggu tombol sinkron. Tombol *Sinkron ke
Nilai* tetap ada untuk menyusul peserta yang tadinya tertunda. Syarat auto:
buku nilai tidak terkunci, naskah paket masih sesuai versi, dan tidak ada
nomor yang menunggu pemeriksaan (identitas strip juga harus cocok).

**Konvensi UI:** seluruh halaman LJK (daftar, terbitkan, detail, hasil,
periksa peserta, pindai, lihat soal) mengikuti pola `gtk/index.blade.php` — kartu statistik
`card-animate` dengan label `stat-label`, filter cepat `filter-badge`, tabel
`table-freeze` + `border-bottom-dashed`, pagination bersama
(`shared._pagination`), dan tombol `btn-soft-*`. Gaya bersama ada di
`resources/views/ljk/_styles.blade.php` (me-reuse `kurikulum._styles`) dan
dikunci oleh tes `test_ui_ljk_mengikuti_pola_gtk_index`.

---

## 3. Skema database (aditif, tanpa mengubah struktur lama)

| Tabel | Isi |
|---|---|
| `wacana_soal` (+ `soal.wacana_id`) | **Wacana/soal cerita**: bacaan bersama untuk beberapa nomor (judul, teks, sumber). Dicetak sekali per kelompok berurutan dengan keterangan rentang nomor; ikut dibekukan ke `konten_snapshot` saat paket dipublikasikan. |
| `ljk_templates` | Template **berversi**: kode unik, ukuran kertas, kolom, dan `geometry` JSON (penanda, QR, data strip, area jawaban dalam **mm**). Baris tidak diubah; tata letak baru = versi baru. |
| `ljk_exams` | Sesi ujian: `paket_soal_id` + `paket_version` + `paket_snapshot_at` + **`paket_digest`** + `template_id` + buku nilai + status (`draft→aktif→pengumpulan→review→final→tersinkron`). |
| `ljk_participants` | Peserta + `nomor_peserta` + **`mapping`** (urutan soal & opsi aktual per peserta untuk paket yang mengacak). |
| `ljk_sheets` | Lembar tercetak: `sheet_code` unik, `qr_token` (HMAC), versi template, status cetak. |
| `ljk_scans` | Riwayat pemindaian: gambar privat, `image_hash` (anti-duplikat), metrik kualitas, kode error, waktu proses, status (`processed/failed/superseded`). |
| `ljk_scan_answers` | **Hasil baca awal** per nomor: status, label, keyakinan, `evidence` (skor bukti tiap opsi + fitur citra) + kondisi review terkini. |
| `ljk_answer_corrections` | **Log koreksi guru append-only**: sebelum/sesudah, aktor, waktu, alasan. |
| `ljk_sync_logs` | Jejak sinkronisasi nilai + `payload_hash` (idempotensi). |
| `admin_nilai_sumatif.ljk_exam_id` | Proventa baru (nullable + index) untuk menelusuri nilai yang berasal dari LJK. |

**Versi paket:** `paket_digest` = sha256 dari (nomor, soal_id, jenis, bobot,
opsi[label,kunci]) seluruh butir **pada saat LJK diterbitkan**. Bila naskah
paket berubah setelah lembar dicetak, sinkronisasi **ditolak** (kecuali
sinkronisasi paksa oleh pihak berwenang, tercatat di log).

---

## 4. Format lembar & geometri

- A4 potrait, hitam-putih. Tata letak **diukur dalam milimeter** di
  `geometry` sehingga pembacaan memakai koordinat yang sama persis.
- **Kop lembaga**: gambar kop sekolah (`schools.kop_path`) dicetak di atas
  (maks 20 mm, lebar 145 mm); bila belum diunggah dipakai kop teks (nama +
  alamat + telepon sekolah, dengan logo bila ada).
- **Dua kolom sejak awal**: 20 soal → kolom kiri no 1–10, kolom kanan no 11–20
  (baris sejajar). Kolom tambahan otomatis bila jumlah soal tidak muat
  (`config('ljk.template.preferred_columns')`, `max_columns`).
- **Kotak jawaban** 6,2 × 4,8 mm dengan jarak 1,4 mm (cukup untuk tulisan
  tangan pulpen), garis kotak 0,45 mm, label opsi dicetak **di luar** kotak
  agar bagian dalam tetap bersih.
- **Lembar soal (naskah untuk santri)** — `/{userId}/ljk/{ljkUuid}/soal/pdf`
  (`?download=1` untuk mengunduh) mengikuti format naskah madrasah:
  judul dua baris (mis. *SOAL ASESMEN SUMATIF TENGAH SEMESTER GANJIL* /
  *TAHUN AJARAN 2026/2027*), tabel identitas (Mata Pelajaran, Hari/Tanggal,
  Kelas — *"Sepuluh (X) C & D"*, Waktu — *07.30 – 09.00*, Guru Pengampu,
  Nama Peserta Didik bergaris), **PENANAMAN KARAKTER** (3 butir), instruksi
  pilihan ganda, lalu butir soal. Isi soal **tanpa bobot/poin, jenis, TP,
  kunci, dan pembahasan**; **opsi jawaban tersusun kolom sejajar** — A, B, C, D
  pada soal nomor 1 berada tepat di atas A, B, C, D soal berikutnya (tabel
  `table-layout: fixed`, jumlah kolom = opsi terbanyak pada paket, teks panjang
  membungkus di dalam kolomnya; **huruf pilihan A/B/C/D tidak dicetak tebal**). **Untuk soal berbahasa Arab, huruf pilihan
  otomatis memakai ا، ب، ج، د، هـ** — baik di lembar soal maupun pada label
  kotak di lembar jawaban.
- **Catatan teknis (Dompdf):** jangan pernah memakai reset `* { margin: 0 }` —
  pada Dompdf aturan universal itu **menimpa margin `@page`** sehingga isi
  cetak mepet ke tepi kertas. Reset margin hanya untuk elemen isi
  (`body, div, table, td, …`); diuji oleh tes format lembar soal.
- **Setelan cetak (default MS Word)** — dapat diubah di `config/ljk.php → soal`:
  font **Cambria 12 pt**, **jarak antar baris single (1.0)**, **jarak antar soal
  1.15**, margin halaman **0.2" / 0.1" / 0.59" / 0.63"** (atas/bawah/kiri/kanan,
  dikonversi ke mm), dan **tabel identitas maksimal 3 baris** (2 pasang
  label/nilai per baris; baris *Nama Peserta Didik* bergaris putus-putus).
- **Kertas otomatis (minimalisasi halaman)** — default **A4**; bila isi tidak
  muat satu halaman, sistem mencoba **F4 (footscray, 215×330 mm)** dan memakai
  F4 **hanya bila jumlah halamannya lebih sedikit**. Bila keduanya sama
  (mis. 2 halaman), tetap A4. Header respons `X-LJK-Paper` menunjukkan pilihan
  yang dipakai.
- **Wacana / soal cerita**: bacaan dipasang dari form Bank Soal (*Wacana /
  Soal Cerita (opsional)* — pilih wacana yang ada atau tulis baru). Saat paket
  dipublikasikan, wacana ikut dibekukan. Di lembar soal wacana dicetak **sekali
  per kelompok** dengan keterangan *"Wacana untuk soal nomor 20–25"*; di lembar
  jawaban ditampilkan legenda *"Wacana/soal cerita: soal nomor 20–25"*.
- **Bahasa Arab (ponpes)**: teks Arab di-*shaping* deterministik di PHP
  (`LjkArabicText`: bentuk awal/tengah/akhir/terpisah + ligatur lam-alef) dan
  disusun ulang kanan-ke-kiri karena Dompdf tidak memiliki mesin bidi/shaping.
  Kelas `.ar` memakai font yang memuat bentuk presentasi Arab — **DejaVu Sans**
  bawaan Dompdf (terverifikasi), atau **Amiri/Scheherazade** bila ditaruh di
  `storage/app/fonts/`. Instruksi juga dicetak dalam bahasa Arab pada lembar
  yang memuat teks Arab.
- **Dua varian cetak**: (1) *lembar peserta* dengan nama/nomor/kode + QR +
  data strip; (2) *master fotokopi* (`?master=1`, opsi `&copies=N`) — identitas
  kosong, **tanpa QR/data strip**, untuk difotokopi dan diisi pulpen. Opsi
  **Mode Fotokopi** saat menerbitkan membuat urutan soal & opsi seragam
  sehingga satu master berlaku untuk semua santri.
- **4 penanda geometris** (persegi hitam 6 mm) di sudut untuk deteksi &
  koreksi perspektif; **QR** identitas; **data strip 32 sel** (2 mm/sel) yang
  dapat didekode server tanpa pustaka QR.
- Soal `uraian`/`isian` diberi area tulis dan berstatus **manual** (tidak
  dikoreksi OMR).
- Paket dengan jumlah soal besar otomatis memakai hingga 3 kolom dan/atau
  halaman tambahan — `geometry.pages`.

---

## 5. Mesin OMR (deterministik, `app/Services/Ljk/Omr/`)

1. **Muat & normalkan** — downscale sisi terpanjang 1400 px, grayscale, integral image.
2. **Gerbang kualitas** — ketajaman (varians Laplacian), kecerahan, kontras;
   pesan aksi jelas (`blur_too_high`, `brightness_too_low`, …).
3. **Deteksi penanda** — binarisasi **adaptif hibrida** (absolut + relatif
   terhadap rerata lokal) → filter kontras cincin (penanda = persegi gelap
   dikelilingi area terang) → pemilihan 4 sudut berbasis bentuk (bebas skala/
   rotasi) → penghalusan centroid per-komponen.
4. **Koreksi perspektif** — homografi DLT 4 titik; setiap kotak jawaban
   disampel di ruang kanonik (mm) lalu dipetakan balik ke piksel foto.
5. **Analisis tiap kotak** (`BoxAnalyzer`) — fitur yang menggambarkan **tanda
   X**, bukan sekadar jumlah piksel gelap:
   - cakupan dua diagonal **terpisah** + keseimbangannya,
   - tinta di empat sudut & titik temu pusat,
   - kerapatan area, keseragaman sebaran (variansi 9 sel),
   - pita tengah horizontal/vertikal dan **jumlah goresan** (run) pada garis tengah,
   - rasio tinta biru (mendukung pulpen biru).
6. **Peringkat keputusan** — skor bukti 0–1 per opsi dengan ambang yang
   dikalibrasi; aturan pengaman: garis tunggal, coretan berlapis, blok tinta,
   dan pola tak berkepala X **tidak boleh** menjadi bukti kuat.
7. **Klasifikasi per nomor** — `terbaca`, `kosong`, `ganda`, `meragukan`,
   `tidak_dapat_diproses`, `manual`; semua kecuali `terbaca`/`kosong` masuk
   antrean pemeriksaan guru (`needs_review`).

Ambang dan bobot fitur hidup di `config/ljk.php`, dapat diubah tanpa
menyentuh kode.

---

## 6. Kalibrasi & hasil pengukuran aktual

Perintah:

```bash
php artisan ljk:generate-dataset --count=60 --questions=20 --options=4 --out=storage/app/ljk/datasets/demo
php artisan ljk:calibrate --dataset=storage/app/ljk/datasets/demo          # metrik
php artisan ljk:calibrate --dataset=storage/app/ljk/datasets/demo --tune   # telusuri ambang
php artisan ljk:calibrate --dataset=storage/app/ljk/datasets/demo --json
```

Dataset sintetis berlabel (`label '?'` = tanda di luar format yang **harus**
masuk pemeriksaan) mencakup: bersih, miring+perspektif, bayangan kuat, blur
ringan, gelap, pulpen biru, X tipis/tebal/miring/tidak simetris, menyentuh
garis kotak, kosong, ganda, centang, garis miring, coretan, bekas penghapusan,
dan titik.

**Hasil nyata pada 60 lembar × 20 soal (1.200 nomor), engine `omr-v1.0.0`:**

| Metrik | Nilai |
|---|---|
| Exact-sheet accuracy | **98,33 %** (59/60 lembar tepat seluruhnya) |
| Per-question accuracy | **99,92 %** (1.199/1.200) |
| False acceptance rate | **0,00 %** |
| Review rate | **4,83 %** |
| Lembar gagal diproses | **0** |
| Waktu proses (min/median/max) | 464 / 480 / 623 ms per lembar |

Catatan kejujuran:
- Target awal **≥ 99 % exact-sheet** **belum tercapai** pada dataset sintetis
  ini (98,33 %). Satu-satunya nomor yang salah adalah X tipis yang sangat
  buram; mesin **tidak** menebak — nomor tersebut diarahkan ke pemeriksaan
  guru (`meragukan`), sehingga tidak menghasilkan nilai salah.
- Karena false acceptance 0 %, kesalahan yang tersisa berbentuk tambahan
  beban review, bukan nilai siswa yang salah.
- Dataset ini **sintetis** (render → distorsi foto). Validasi formal untuk
  sekolah perlu dataset **hasil cetak & foto nyata**: cetak lembar, isi
  manual, foto dengan beragam HP, verifikasi label secara manual, lalu
  jalankan `ljk:calibrate`. Ukur ulang sebelum mengklaim angka produksi.

---

## 7. Keamanan & otorisasi

- **Kunci jawaban tidak pernah** dicetak di lembar, tidak ada di QR/strip,
  dan tidak pernah dikirim ke klien; pencocokan kunci hanya di server
  (`LjkGradingService::keyLabels()` dari snapshot versi yang benar).
- Lembar memiliki `sheet_code` unik + **QR bertanda tangan HMAC** + data strip
  ber-checksum; pemindaian tanpa identitas valid ditolak.
- Gambar scan disimpan di **disk privat** (`storage/app/ljk/scans/...`) dan
  disajikan hanya lewat route/endpoint ber-otorisasi; tidak ada URL publik.
- Otorisasi server: guru pengampu buku nilai (atau co-teacher pada
  mapel+rombel+TA sama), kurikulum/TU/kesiswaan (petugas lintas kelas) — sama
  dengan aturan modul nilai. Guru lain mendapat **403**.
- Buku nilai terkunci (final) atau tahun ajaran arsip **menolak** sinkronisasi
  (aturan identik dengan input nilai manual).
- Koreksi guru dicatat **append-only** (aktor, waktu, sebelum/sesudah, alasan)
  dan hasil baca awal tetap tersimpan.
- Duplikasi pemindaian (gambar sama) tidak diproses ulang; scan lama untuk
  lembar yang sama ditandai `superseded` sehingga nilai tidak berganda.
- Sinkronisasi nilai **idempotent** (hash payload); peserta yang masih
  menunggu pemeriksaan **dilewati**, bukan dinilai otomatis.

---

## 8. Integrasi penilaian resmi

- Sinkronisasi memanggil `SumatifHarianService::upsertSumatif()` — satu jalur
  aturan nilai yang dipakai semua input manual (RS, RSA, murni, NR final).
- Target kolom mengikuti jenis ujian: `sts → sts_murni`,
  `sas → sas_murni`, `ulangan_harian/try_out/latihan → kolom sumatif harian`
  (dapat dipilih saat sinkronisasi).
- Bobot per soal diambil dari naskah beku (`bobot_override`/`bobot_default`),
  **bukan** asumsi seragam. Nilai akhir = `(jumlah poin / total bobot) × 100`.
- Soal uraian/isian berstatus **manual**: guru mengisi skor per nomor
  (dibatasi bobot soal) dan hasilnya ikut dihitung.
- Provenance: baris nilai resmi menerima `paket_soal_id` + `ljk_exam_id`.
- Workflow nilai yang ada (kunci buku, arsip TA, murni) tetap berlaku.

---

## 9. API mobile (Sanctum)

| Endpoint | Fungsi |
|---|---|
| `GET /api/mobile/v1/ljk/exams` | Ujian yang boleh dipindai oleh user. |
| `GET /api/mobile/v1/ljk/exams/{exam}` | Detail + daftar peserta/lembar. |
| `POST /api/mobile/v1/ljk/exams/{exam}/scans` | Unggah gambar (multipart) → hasil OMR. |
| `GET /api/mobile/v1/ljk/scans/{scan}` | Detail hasil pemindaian. |
| `PATCH /api/mobile/v1/ljk/scans/{scan}/answers/{answer}` | Koreksi guru (ber-audit). |

Halaman web guru: daftar, terbitkan, detail, **Lihat Soal**, PDF lembar
(+`?master=1&copies=N` untuk master fotokopi), halaman kamera, hasil &
verifikasi, periksa peserta.

- Format respons mengikuti konvensi mobile ALIM
  (`{ success, request_id, data }` / `{ success, request_id, error }`).
- `POST /auth/login` & `/auth/me` kini menambahkan `role` dan
  `capabilities.ljk_scan` (aditif; `is_wali` tidak berubah) sehingga aplikasi
  dapat menampilkan menu pemindai untuk guru tanpa memutus klien lama.
- Ability `ljk.read` / `ljk.scan` / `ljk.review` ditambahkan ke katalog
  `config/sanctum.php` (peran `guru` & `admin`).

---

## 10. Aplikasi Expo (`Alim-Mobile`)

- Fitur baru `src/features/ljk/` + route `src/app/ljk/`:
  daftar ujian → detail peserta → **Pindai** (kamera) → **hasil & koreksi**.
- Kamera memakai **`expo-image-picker`** (`launchCameraAsync`) yang sudah
  terpasang & dikonfigurasi; **tidak menambah dependensi native**, sehingga
  tetap kompatibel dengan Expo Go dan build EAS saat ini.
- Pemrosesan OMR di **server**: ponsel hanya mengambil/mengompres foto dan
  mengunggah; hasil deterministik dan dapat diaudit, ukuran unggahan wajar.
- Menu "Pindai LJK" ditambahkan pada sidebar peran guru (satuan pendidikan).
- `npm run typecheck` lulus; tidak ada error lint pada berkas baru.

---

### Setelan lembar soal (ringkas)

| Kunci `config/ljk.php` | Default | Arti |
|---|---|---|
| `soal.font_size` | `12.0` | Ukuran font dasar (pt), Cambria |
| `soal.font_size_fallback_scale` | `0.90` | Kompensasi ukuran bila Cambria/Caladea belum dipasang (DejaVu Serif tampil lebih besar pada pt yang sama) |
| `soal.line_height` | `1.0` | Jarak antar baris (single) |
| `soal.question_gap` | `1.15` | Jarak antar soal (kelipatan satu baris) |
| `soal.margin_in` | `0.20 / 0.10 / 0.59 / 0.63` | Margin atas/bawah/kiri/kanan (inci) |
| `soal.paper.default` / `paper.fallback` | `a4` / `f4` | Kertas & cadangan otomatis |
| `soal.huruf_arab` | `ا، ب، ج، د، هـ` | Huruf pilihan soal Arab |
| `soal.identitas_baris_maks` | `3` | Baris maksimum tabel identitas |

### Font PDF (Cambria)

Seluruh PDF LJK memakai **Cambria** melalui resolver `LjkPdfFonts`:

1. **Cambria** — bila berkas `Cambria*.ttf` tersedia;
2. **Caladea** — font metrik-kompatibel Cambria (lisensi terbuka) di
   `storage/app/fonts/`, paket sistem (`fonts-crosextra-caladea`, Linux) atau
   `/Library/Fonts` (macOS);
3. **DejaVu Serif** — bawaan Dompdf, dipakai otomatis bila Cambria/Caladea
   belum dipasang (tampilan tetap serif dan konsisten).

Pemasangan Cambria resmi cukup dengan menaruh berkasnya:
`storage/app/fonts/Cambria.ttf` (+ `-Bold`, `-Italic`, `-BoldItalic`) — tidak
perlu instalasi sistem. Status font aktif ditampilkan di halaman **Lihat Soal**.
Cache metrik font ditulis ke `storage/app/dompdf/fonts` (pastikan writable).

Untuk teks Arab: shaping & urutan RTL dihitung di PHP, lalu dirender dengan
font yang memuat *bentuk presentasi* Arab. Bila sekolah ingin tampilan naskh
yang lebih halus, letakkan `Amiri-Regular.ttf`/`Scheherazade-Regular.ttf`
(yang memuat blok FE70–FEFC) di `storage/app/fonts/`; sistem memilihnya
otomatis, jika tidak ada tetap memakai DejaVu Sans.

## 11. Deployment & operasional

- **Ekstensi**: `ext-gd` (wajib — sudah dibutuhkan modul QR yang ada).
  Imagick **tidak** diperlukan.
- **Penyimpanan**: pastikan direktori `storage/app/ljk/` dapat ditulis.
  Gambar scan tidak perlu `php artisan storage:link` (disk privat).
- **Memori**: mesin OMR memproses citra di memori; puncak ±70–90 MB pada
  gambar 1400 px. Untuk antrean/worker disarankan `memory_limit ≥ 256M`.
  Perintah `ljk:calibrate` & `ljk:generate-dataset` menaikkan limit sendiri.
- **Antrean**: v1 memproses sinkron saat request (±0,5–1 detik per lembar).
  Untuk volume besar, jalankan pemrosesan via queue
  (`ScanService` sudah memisahkan unggah/proses; pengembangan lanjutan dapat
  membungkusnya dalam job).
- **Migrasi**: `php artisan migrate` (semua aditif; tersedia `down()`).
- **Uji cetak**: gunakan *Preview* / PDF satu lembar lalu uji dengan printer
  sekolah sebelum cetak massal (skala 100 %, tanpa "fit to page").
- **Waktu ujian** (opsional, muncul di lembar soal): isi *Waktu Mulai* dan
  *Waktu Selesai* saat menerbitkan LJK.

### SOP singkat guru

1. Pastikan Paket Soal sudah **final** di Bank Soal.
2. *LJK Smart Scanner → Terbitkan LJK* → pilih buku nilai & paket → cetak PDF.
3. Bagikan ke santri; minta mengisi dengan **pulpen hitam/biru**, tanda X di
   dalam kotak, satu jawaban per nomor.
4. *Pindai LJK* → pilih peserta (atau scan QR) → foto seluruh lembar.
5. Periksa nomor bertanda ⚠ di *Hasil & Verifikasi* → koreksi bila perlu.
6. *Finalkan Ujian* → *Sinkron ke Nilai*. Cek hasil di Buku Nilai/leger.

### Seeder contoh (GTK siap menerbitkan LJK)

Untuk menyiapkan akun GTK agar langsung bisa menerbitkan LJK tanpa menyiapkan
data manual:

```bash
php artisan db:seed --class=LjkLaluSopianSeeder
```

Seeder ini idempoten dan menyiapkan (contoh: **Lalu Sopian Harianto — Bahasa
Inggris · kelas 1B · SD IT Putra Abu Hurairah Mataram**):

- buku nilai (mapel × rombel × TA × semester) — dipakai bila sudah ada;
- Bank Soal Bahasa Inggris kelas 1 + 20 soal PG + opsi jawaban;
- kisi-kisi STS;
- **Paket Soal final** (`PKT-BING-1B-…`): butir dibekukan (snapshot) lalu
  dipublikasikan sehingga muncul di form *Terbitkan LJK*;
- ujian LJK contoh (`LJK-…`) beserta peserta & **lembar siap cetak**;
- bila rombel masih kosong, 10 santri demo (NISN `SEED-…`) dibuat otomatis.

Ubah konstanta di `database/seeders/LjkLaluSopianSeeder.php`
(`TEACHER_EMAIL`, `SUBJECT_NAME`, `GROUP_NAME`, `JUMLAH_SOAL`) untuk akun atau
mapel/kelas lain. Seeder **tidak** didaftarkan di `DatabaseSeeder` agar data
contoh tidak ikut ter-seed di produksi.

---

## 12. Pengujian otomatis

| Berkas | Cakupan |
|---|---|
| `tests/Feature/Ljk/LjkOmrEngineTest.php` | X hitam/biru, tipis/tebal/miring/tidak simetris, menyentuh garis, kosong, ganda, centang/miring/coretan/hapus, foto miring+bayangan+blur, tolak buram/gelap/terpotong |
| `tests/Feature/Ljk/LjkFlowTest.php` | Paket → ujian → peserta → lembar → pindai → nilai → koreksi (audit) → sinkron resmi → idempotensi → tolak saat masih review |
| `tests/Feature/Ljk/LjkWebFlowTest.php` | HTTP guru: index/create/terbit/PDF/scan/koreksi/final/sinkron + 403 guru lain + **kepatuhan UI pola gtk/index** pada semua halaman (termasuk varian filter) |
| `tests/Feature/Ljk/LjkMobileApiTest.php` | API mobile: daftar, detail, unggah, koreksi, penolakan tanpa identitas |
| Tambahan | tata letak 2 kolom (kiri 1–10, kanan 11–20), master tanpa nama/QR/strip, auto-sync nilai ke kolom resmi, kunci tersembunyi di halaman *Lihat Soal*, lembar soal PDF (kop, penanaman karakter, opsi kolom sejajar, tanpa bobot) + font Cambria, wacana/soal cerita + legenda lembar jawaban, shaping teks Arab, unduh PDF (`?download=1`), **setelan cetak Word (12 pt, spasi 1.0/1.15, margin inci, tabel identitas 3 baris)**, **pemilihan kertas otomatis A4→F4**, **huruf pilihan Arab (ا، ب، ج، د)** di lembar soal & lembar jawaban, regresi route `paket-soal` (parameter `userId`) |

Hasil terakhir: **28 tes, 366 asersi — lulus** (PHP 8.5 + SQLite in-memory;
peringatan `PDO::MYSQL_ATTR_*` berasal dari lingkungan PHP 8.5, bukan fitur).

Regresi terkait yang dijalankan: `BukuNilaiKonsolidasiTest`,
`PaketNaskahPolicyTest`, `SumatifHarianDinamisTest`, `MobileVersionEndpointTest`
— lulus. (Kegagalan `MobileAuthTest` / `MobileMahrom*` bersifat **pra-eksisting**
— fixture tes menunjuk kolom/factory lama, tidak terkait fitur ini.)

### Perbaikan insidental

- `BindOrganizationContext`: guard `hasSession()` — mencegah error
  "Session store not set" pada request API stateless.
- `NilaiSumatif`: `ljk_exam_id` ditambahkan ke `$fillable`.

---

## 13. Batasan & pengembangan lanjutan

- Satu lembar per pemindaian (v1); lembar multi-halaman didukung pada
  template tetapi alur pindai masih satu halaman per foto.
- Tanda di luar format (centang, coretan, bekas hapus) **diarahkan ke
  pemeriksaan guru**; beberapa coretan padat yang menyerupai X bisa terbaca
  dan tetap dapat dikoreksi manual (tercatat).
- Arab: tanpa harakat lengkap pun aman (harakat dipertahankan sebagai gugus);
  bidi penuh (arah campuran bertingkat) tidak didukung — teks Arab + Latin
  sederhana sudah ditangani. Untuk naskah Arab berat (Quran/hadits berharakat
  penuh), disarankan menyediakan font Arab khusus di `storage/app/fonts/`.
- Deteksi QR di aplikasi Expo belum ada (tidak ada modul barcode);
  identitas diambil dari pemilihan peserta + verifikasi data strip di server.
  Web sudah mendukung scan QR memakai html5-qrcode.
- Item analysis (daya beda/kesukaran) belum dihitung dari hasil LJK.
- Untuk mencapai ≥ 99 % exact-sheet pada data nyata: kumpulkan dataset
  berlabel hasil cetak+foto, jalankan `ljk:calibrate --tune`, lalu bekukan
  ambang terbaik di `config/ljk.php` dan naikkan `engine_version`.
