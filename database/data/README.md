# Data posisi ayat Mushaf Madinah

`tahfidz_quran_positions.json` berisi 6.236 baris pemetaan:

- `surah`
- `ayah`
- `page_start`
- `line_start`
- `page_end`
- `line_end`

Dataset diturunkan dari layout **QPC2 Hafs ʿan ʿĀṣim** pada proyek
[zonetecde/mushaf-layout](https://github.com/zonetecde/mushaf-layout),
yang menyediakan 604 halaman dan 15 baris per halaman untuk Mushaf Madinah.

Yang disimpan di ALIM hanya metadata posisi ayat, bukan teks Arab, glyph QPC,
maupun font. Dataset ini dipakai untuk mengisi halaman dan baris secara otomatis
saat guru memilih surah dan ayat.

Sumber data perlu diverifikasi ulang jika edisi Mushaf Madinah yang dipakai
pondok berbeda. Jalankan `php artisan db:seed --class=TahfidzAyatPositionSeeder`
setelah dataset diperbarui.
