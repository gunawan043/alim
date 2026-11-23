<?php

declare(strict_types=1);

namespace App\Services\Evaluasi;

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
     * @param  array<int, array{label?: string, teks?: string, display_label?: string}>  $opsi
     * @param  float  $availableMm  lebar area isi soal (setelah margin & nomor)
     * @param  float  $fontPt  ukuran font opsi dalam pt
     * @param  int  $maxColumns  0 = otomatis; selain itu batas atas kolom
     * @return array{
     *     rows: array<int, array<int, array{index: int, span: int}>>,
     *     columns: int,
     *     widths: array<int, float>,
     *     spans: array<int, int>
     * }
     */
    public function layout(array $opsi, float $availableMm, float $fontPt, int $maxColumns = 0): array
    {
        $count = count($opsi);

        if ($count === 0) {
            return ['rows' => [], 'columns' => 0, 'widths' => [], 'spans' => []];
        }

        $widths = [];
        foreach ($opsi as $index => $option) {
            $widths[$index] = $this->textWidthMm($this->labelText($option), $fontPt);
        }

        $kolomMaks = max(1, min(self::MAX_COLUMNS, $count));
        if ($maxColumns > 0) {
            // Pengaturan manual: jumlah kolom tetap, tidak diturunkan otomatis.
            $columns = max(1, min($kolomMaks, $maxColumns));
        } else {
            $columns = $this->kolomTerbaik($widths, $availableMm, $kolomMaks);
        }

        // Semua kolom sama lebar; tidak ada pilihan yang dilebarkan.
        $rows = $this->packRows($widths, $availableMm / $columns, $columns);

        return [
            'rows' => $rows,
            'columns' => $columns,
            'widths' => $widths,
            'spans' => array_fill(0, $count, 1),
        ];
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
     */
    public function textWidthMm(string $text, float $fontPt): float
    {
        $path = LjkPdfFonts::faces()['normal'] ?? null;

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
