# Rencana: Renderer Chromium untuk Naskah Arab

## Masalah

Dompdf tidak menerapkan tabel GPOS font, sehingga harakat selalu tergambar di
garis dasar atau melayang di atas huruf — tidak pernah menempel. Ini bukan bug
CSS yang bisa ditambal; mesinnya memang tidak punya OpenType shaping.

Akibatnya selama ini harakat dibuang dari PDF (data database tetap utuh), yang
Anda nilai sebagai "mengubah data saat ditampilkan".

## Tujuan

Harakat tercetak menempel dengan benar pada huruf di naskah PDF Arab, tanpa
mengubah isi database, dan tanpa merusak kondisi produksi bila server tidak
mendukung.

## Kondisi server (penting)

Produksi ada di Hostinger **shared hosting**. Chromium butuh `proc_open`/`exec`
dan ~500 MB RAM per render. Keduanya sering diblokir di shared hosting.

Karena itu rancangan ini **berlapis**: Chromium dipakai bila tersedia; bila
tidak, otomatis kembali ke Dompdf. Situs tetap jalan di kedua kondisi.

## Langkah

### 1. Abstraksi renderer (tanpa mengubah perilaku dulu)
- Buat `app/Services/Pdf/PdfRenderer.php` dengan dua metode:
  - `available(): bool` — cek Chromium bisa dipanggil
  - `render(string $html, array $paper): string` — hasilkan bytes PDF
- Deteksi Chromium: env `CHROMIUM_BINARY`, lalu lokasi umum
  (`/usr/bin/chromium`, `/usr/bin/chromium-browser`, `/usr/bin/google-chrome`).
- Uji kelayakan sekali per proses: jalankan `--version` lewat `proc_open`.
- Bila tidak layak → `available()` false.

### 2. Jalur Chromium
- Render HTML ke PDF dengan flag:
  `--headless --disable-gpu --no-sandbox --print-to-pdf --print-to-pdf-no-header`
- Ukuran kertas + margin dikirim lewat `@page { size: ...; margin: ... }`
  yang **sudah ada** di template, plus `--no-pdf-header-footer`.
- Nomor halaman: pindahkan dari `canvas->page_text()` (khusus Dompdf) ke CSS
  `@page` counter, supaya kedua renderer menghasilkan hal yang sama.
  → Ini satu-satunya perubahan template yang berdampak ke Dompdf juga.

### 3. Harakat diaktifkan lagi
- `marksToHtml()` / `instructionsToHtml()` **tetap** membuang harakat **hanya
  di jalur Dompdf**.
- Di jalur Chromium, harakat dipertahankan penuh dan shaping diserahkan ke
  browser (jangan pakai `shape()`/presentation form sama sekali — Chromium
  melakukan shaping sendiri dari teks logis).
- Artinya view perlu tahu renderer mana yang dipakai. Disuntik lewat data:
  `'pdfEngine' => 'chromium' | 'dompdf'`.
- Di view: bila `pdfEngine === 'chromium'`, cetak teks apa adanya
  (`{{ $teks }}` + `dir="rtl"`), bukan `marksToHtml()`.

### 4. Fallback otomatis
- `LjkSoalPdfRenderer` dan `SoalNaskahPdfService` memanggil `PdfRenderer`.
- Bila Chromium tidak tersedia ATAU gagal (exit code != 0 / PDF kosong),
  log peringatan lalu pakai jalur Dompdf seperti sekarang.
- Tidak ada halaman error ke pengguna.

### 5. Pemeriksa server
- `php artisan pdf:check-engine` → melaporkan: proc_open aktif/tidak,
  memori, biner Chromium ditemukan/tidak, hasil render uji (ukuran bytes).
- Ini yang dijalankan di produksi untuk memutuskan apakah langkah 2–3
  benar-benar aktif di sana.

## Berkas yang disentuh

| Berkas | Perubahan |
|---|---|
| `app/Services/Pdf/PdfRenderer.php` | baru — abstraksi + deteksi |
| `app/Services/Ljk/LjkSoalPdfRenderer.php` | pakai PdfRenderer |
| `app/Services/Evaluasi/SoalNaskahPdfService.php` | pakai PdfRenderer |
| `resources/views/ljk/arab/soal-pdf.blade.php` | cabang `pdfEngine`, nomor halaman CSS |
| `app/Services/Ljk/LjkArabicText.php` | helper `plainRtl()` untuk jalur Chromium |
| `app/Console/Commands/PdfCheckEngine.php` | baru — pemeriksa |
| `config/ljk.php` | kunci `pdf.engine`, `pdf.chromium_binary` |
| `tests/Feature/…` | tes fallback + tes view cabang engine |

## Risiko & mitigasi

| Risiko | Mitigasi |
|---|---|
| Chromium diblokir di produksi | Fallback Dompdf; `pdf:check-engine` membuktikan |
| Memori habis saat render | Batasi 1 proses sekaligus, timeout 60 s |
| PDF Chromium beda ukuran huruf | Bandingkan manual halaman A4 sebelum rilis |
| Nomor halaman hilang karena bukan canvas Dompdf | CSS `@page` counter di langkah 2 |
| Waktu render lebih lama (~1–3 s) | Hanya untuk naskah Arab, bukan semua PDF |

## Urutan rilis yang disarankan

1. Langkah 1 + 4 + 5 (abstraksi, fallback, pemeriksa) — **tidak mengubah
   tampilan sama sekali**. Aman dirilis.
2. Jalankan `pdf:check-engine` di produksi. Bila Chromium tidak ada,
   berhenti di sini — tidak ada gunanya lanjut.
3. Bila tersedia: langkah 2 + 3, uji visual, baru rilis.
