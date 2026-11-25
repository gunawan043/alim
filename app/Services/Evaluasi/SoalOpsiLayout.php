<?php

declare(strict_types=1);

namespace App\Services\Evaluasi;

use App\Services\Ljk\LjkArabicText;
use App\Services\Ljk\LjkPdfFonts;

/**
 * Tata letak pilihan jawaban pada naskah soal.
 *
 * Aturan (sesuai ketentuan sekolah): jumlah kolom per baris MENYESUAIKAN
 * panjang pilihan. Semua kolom dalam satu soal selalu SAMA LEBAR.
 *
 *  1) Muat 1 baris penuh (4 opsi A–D / 5 opsi A–E)
 *     |A.    |B.    |C.    |D.    |
 *
 *  2) Tidak muat 1 baris, tetapi masih bisa 2 kolom
 *     |A.         |B.         |
 *     |C.         |D.         |
 *
 *  3) Ada pilihan yang memakan area cukup panjang → semua pilihan
 *     menjadi 1 kolom per baris (lebar penuh)
 *     |A.                     |
 *     |B.                     |
 *     |C.                     |
 *     |D.                     |
 *
 * Lebar kolom = lebar area / jumlah kolom, sehingga A, B, C, D selalu
 * identik lebarnya.
 *
 * Naskah soal memakai mode GRID TETAP: jumlah kolom diambil dari jumlah opsi
 * terbanyak dalam satu naskah (bukan per soal), sehingga penanda A/B/C/D
 * berdiri di satu garis vertikal dari soal pertama sampai terakhir. Bila ada
 * opsi yang lebih panjang dari lebar kolom, teks dilipat di dalam selnya —
 * kolom tetap sejajar, tidak ada pilihan yang melebar.
 */
class SoalOpsiLayout
{
    /** Jarak aman agar teks tidak menyentuh kolom sebelah (mm). */
    private const SAFETY_MM = 1.2;

    /** Batas atas jumlah pilihan (A..E). */
    public const MAX_COLUMNS = 5;

    /**
     * Susun pilihan menjadi baris-baris.
     *
     * Bila `$kolomTetap` diisi, jumlah kolom tabel dipaksa sebanyak itu untuk
     * SEMUA soal pada naskah. Inilah yang membuat penanda A/B/C/D berdiri di
     * satu garis lurus dari soal nomor 1 sampai terakhir: soal yang opsinya
     * pendek tidak memakai 4 kolom sementara soal lain 1 kolom, karena lebar
     * tabel (dan pembagi kolomnya) sama di semua baris.
     *
     * @param  array<int, array{label?: string, teks?: string, display_label?: string}>  $opsi
     * @param  float  $availableMm  lebar area isi soal (setelah margin & nomor)
     * @param  float  $fontPt  ukuran font opsi dalam pt
     * @param  int  $maxColumns  0 = otomatis; selain itu batas atas kolom
     * @param  int  $kolomTetap  0 = hitung per soal; >0 = paksa jumlah kolom
     * @param  float  $indentMm  jarak antar kolom (padding) di dalam sel
     * @return array{
     *     rows: array<int, array<int, array{index: int, span: int}>>,
     *     columns: int,
     *     widths: array<int, float>,
     *     spans: array<int, int>,
     *     wrap: bool
     * }
     */
    public function layout(array $opsi, float $availableMm, float $fontPt, int $maxColumns = 0, int $kolomTetap = 0, float $indentMm = 2.0): array
    {
        $count = count($opsi);

        if ($count === 0) {
            return ['rows' => [], 'columns' => 0, 'widths' => [], 'spans' => [], 'wrap' => false];
        }

        $widths = [];
        foreach ($opsi as $index => $option) {
            $widths[$index] = $this->textWidthMm($this->labelText($option), $fontPt);
        }

        // Jumlah kolom yang dipakai tabel. Saat naskah memaksa grid tetap,
        // tabel memakai kolom sebanyak itu walau soalnya hanya punya 2 opsi —
        // sisanya diisi sel kosong, sehingga kolom A selalu di posisi sama.
        $columns = $kolomTetap > 0
            ? $kolomTetap
            : $this->columnsFor($widths, $availableMm, $maxColumns);

        $packColumns = max(1, min($columns, $count));

        // Semua kolom sama lebar; tidak ada pilihan yang dilebarkan.
        $unitMm = $availableMm / $columns;
        $rows = $this->packRows($widths, $unitMm, $packColumns);

        return [
            'rows' => $rows,
            'columns' => $columns,
            'widths' => $widths,
            'spans' => array_fill(0, $count, 1),
            // Hanya grid tetap yang boleh melipat teks. Mode otomatis sudah
            // memilih jumlah kolom yang muat tanpa lipatan.
            'wrap' => $kolomTetap > 0
                && $this->perluWrap($widths, max(1.0, $unitMm - max(0.0, $indentMm))),
        ];
    }

    /**
     * Apakah ada pilihan yang lebih lebar dari isi sel (setelah padding).
     *
     * @param  array<int, float>  $widths
     */
    private function perluWrap(array $widths, float $contentMm): bool
    {
        foreach ($widths as $width) {
            if ($width + self::SAFETY_MM > $contentMm) {
                return true;
            }
        }

        return false;
    }

    /**
     * Jumlah kolom grid tetap untuk satu naskah.
     *
     * Dipakai sebagai pembagi lebar yang SAMA pada semua soal. Nilainya
     * diambil dari jumlah opsi terbanyak (A–D → 4, A–E → 5) supaya tidak
     * ada baris yang terlipat, dan dibatasi pengaturan `option_columns`.
     *
     * @param  int  $maxOpsi  jumlah opsi terbanyak dalam naskah
     */
    public function kolomGrid(int $maxOpsi, int $maxColumns = 0): int
    {
        $maks = max(1, min(self::MAX_COLUMNS, $maxOpsi));

        return $maxColumns > 0 ? max(1, min($maks, $maxColumns)) : $maks;
    }

    /**
     * @param  array<int, float>  $widths
     */
    private function columnsFor(array $widths, float $availableMm, int $maxColumns): int
    {
        $kolomMaks = max(1, min(self::MAX_COLUMNS, count($widths)));

        if ($maxColumns > 0) {
            // Pengaturan manual: jumlah kolom tetap, tidak diturunkan otomatis.
            return max(1, min($kolomMaks, $maxColumns));
        }

        return $this->kolomTerbaik($widths, $availableMm, $kolomMaks);
    }

    /**
     * Pilih jumlah kolom terbanyak yang masih memuat SEMUA pilihan tanpa
     * terlipat. Bila tidak ada yang muat, semua pilihan jadi 1 kolom per
     * baris (lebar penuh).
     *
     * @param  array<int, float>  $widths
     */
    private function kolomTerbaik(array $widths, float $availableMm, int $kolomMaks): int
    {
        for ($columns = $kolomMaks; $columns > 1; $columns--) {
            $unitMm = $availableMm / $columns;
            $muatSemua = true;

            foreach ($widths as $width) {
                if ($width + self::SAFETY_MM > $unitMm) {
                    $muatSemua = false;
                    break;
                }
            }

            if ($muatSemua) {
                return $columns;
            }
        }

        return 1;
    }

    /**
     * Bagi pilihan ke baris, maksimal $columns per baris.
     *
     * @param  array<int, float>  $widths
     * @return array<int, array<int, array{index: int, span: int}>>
     */
    private function packRows(array $widths, float $unitMm, int $columns): array
    {
        $rows = [];
        $current = [];

        foreach (array_keys($widths) as $index) {
            $current[] = ['index' => $index, 'span' => 1];

            if (count($current) >= $columns) {
                $rows[] = $current;
                $current = [];
            }
        }

        if ($current !== []) {
            $rows[] = $current;
        }

        return $rows;
    }

    /**
     * Lebar teks "A. isi pilihan" dalam mm.
     *
     * Font pengukur HARUS sama dengan yang dipakai saat mencetak, kalau tidak
     * lebarnya salah jauh. Contoh nyata: "بَيْتٌ" diukur 20.37 mm dengan font
     * Latin tetapi hanya 12.44 mm dengan font Arab — selisih 64%. Lebar yang
     * terlalu besar membuat kolom opsi membengkak dan jarak antar opsi
     * terlihat renggang di PDF.
     */
    public function textWidthMm(string $text, float $fontPt): float
    {
        // Teks Arab diukur dengan font Arab, sisanya dengan font Latin.
        $path = LjkArabicText::hasArabic($text)
            ? (LjkPdfFonts::arabicFontPath() ?? LjkPdfFonts::faces()['normal'] ?? null)
            : (LjkPdfFonts::faces()['normal'] ?? null);

        if (! $path || ! is_file($path) || ! function_exists('imagettfbbox')) {
            return $this->estimateWidthMm($text, $fontPt);
        }

        // GD bekerja dalam piksel @96 dpi; pt → px = pt * 96 / 72.
        $size = max(1.0, $fontPt * 96 / 72);
        $box = @imagettfbbox($size, 0, $path, $text);

        if (! $box) {
            return $this->estimateWidthMm($text, $fontPt);
        }

        $pixels = abs($box[2] - $box[0]);

        return round($pixels * 25.4 / 96, 2);
    }

    /**
     * Perkiraan bila berkas font tidak tersedia (tanpa GD/TTF).
     */
    private function estimateWidthMm(string $text, float $fontPt): float
    {
        // Rata-rata karakter serif ≈ 0.5 em.
        $perChar = $fontPt * 0.5 * 25.4 / 72;

        return round(mb_strlen($text) * $perChar, 2);
    }

    /**
     * Teks yang diukur/ditampilkan: "A. isi pilihan".
     *
     * @param  array<string, mixed>  $option
     */
    private function labelText(array $option): string
    {
        $label = (string) ($option['display_label'] ?? $option['label'] ?? '');
        $teks = (string) ($option['teks'] ?? '');

        return trim($label.'. '.strip_tags($teks));
    }
}
