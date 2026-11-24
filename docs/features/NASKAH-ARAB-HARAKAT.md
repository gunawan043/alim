# Naskah Arab: Harakat, Bidi, dan Pilihan Font

Dokumen ini mencatat kaidah cetak naskah soal Arab beserta batasan teknis
Dompdf. Baca ini sebelum mengubah `LjkArabicText` atau template
`ljk/arab/soal-pdf.blade.php`.

## Batasan mesin (Dompdf)

Dompdf **tidak punya** mesin OpenType (shaping) dan **tidak menerapkan tabel
GPOS**. Konsekuensinya:

1. Penyambungan huruf Arab dilakukan manual di PHP
   (`LjkArabicText::applyContextualForms()` → bentuk presentasi U+FB50–U+FEFC).
2. Urutan visual (kiri→kiri) disusun manual, karena tidak ada algoritma bidi.
3. **Posisi harakat di atas huruf sepenuhnya bergantung pada metrik bawaan
   font.** GPOS — yang seharusnya memberi tahu posisi tanda — diabaikan.

Poin 3 adalah sumber masalah yang paling sering muncul kembali.

## Harakat: font menentukan, bukan kode

Karena GPOS diabaikan, harakat ditempatkan memakai `advance width` lalu
`y` bawaan glyph, tanpa penyesuaian titik jangkar. Hasilnya beda per font:

| Font | Perilaku harakat di Dompdf |
|---|---|
| **SFArabic** (macOS) | Menempel benar. Terbukti pada uji banding. |
| **Scheherazade New** | Menempel benar (rekomendasi untuk server). |
| **Noto Naskh Arabic** | Menempel benar (rekomendasi untuk server). |
| **KFGQPC Uthmanic** | Menempel benar. |
| **Amiri** | **Melayang** — fathah/dammah terpisah di atas huruf. |

Amiri punya tabel GPOS lengkap, tetapi justru itulah masalahnya: posisi
harakat hanya ada di GPOS, dan Dompdf tidak membacanya. Font yang metrik
bawaannya sudah baik tetap tampil benar tanpa GPOS.

### Cara memasang font pengganti

Taruh berkas `.ttf` (bukan `.ttc` — Dompdf gagal membaca koleksi) di salah
satu direktori `config('ljk.pdf.fonts.arabic_paths')`:

```
storage/app/fonts/     ← disarankan, tidak ikut repo
resources/fonts/
```

Font dikenali otomatis bila namanya cocok dengan `arabic_preferred`
**dan** memuat bentuk presentasi Arab. Urutan preferensi sengaja
menempatkan font harakat-baik di atas Amiri.

Verifikasi font yang sedang dipakai:

```bash
php -r 'require "vendor/autoload.php"; $a=require "bootstrap/app.php";
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo App\Services\Ljk\LjkPdfFonts::arabicFontLabel(), "\n";'
```

## Kaidah tata letak yang harus dijaga

1. **Tanda baca penutup di KIRI baris.** `؟`, `!`, `.` harus berada di ujung
   kiri kalimat visual. `shapeLine()` menaruhnya di akhir string.
2. **Jangan memakai `direction: rtl` pada `.ar`.** Teks sudah dibalik di PHP;
   `direction: rtl` membuat bidi Dompdf membaliknya **sekali lagi** sehingga
   `؟` kembali ke kanan dan `«»` terbalik. Cukup `text-align: right`.
3. **Tanda baca menempel pada kata induknya.** `المعلم:` dan `الكتب،` tanpa
   spasi (`shapeLine()` memakai penanda `$glue`).
4. **Paragraf dipecah per kalimat** sebelum dibalik
   (`splitSentences()`), supaya urutan kalimat tidak terbalik.
5. **Penanda opsi `.ا`**: titik ditulis **sebelum** huruf di HTML, karena
   `.ar` ber-RTL membalik urutan elemen saat cetak.
6. **Harakat tidak dibuang.** Tampilan tidak boleh mengubah isi database.

## Riwayat keputusan

- Penanda opsi pernah ditulis `huruf` lalu `titik`, menghasilkan `د.` yang
  salah. Urutan HTML yang benar adalah `titik` lalu `huruf`.
- Harakat pernah dibuang dari PDF (`stripMarks()`) karena posisinya rusak
  dengan Amiri. Pembuangan itu **dicabut**: diganti pemilihan font yang
  benar.
- Rencana memindahkan renderer ke Chromium dibatalkan setelah terbukti
  masalahnya ada di font, bukan di mesin.
