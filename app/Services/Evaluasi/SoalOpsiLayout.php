<?php

declare(strict_types=1);

namespace App\Services\Evaluasi;

use App\Services\Ljk\LjkPdfFonts;

/**
 * Tata letak pilihan jawaban pada naskah soal.
 *
 * Aturan (sesuai ketentuan sekolah):
 *  1. Setiap pilihan WAJIB muat dalam 1 baris — tidak boleh terlipat.
 *  2. Lebar kolom SERAGAM dalam satu soal (mis. A dan B sama lebar).
 *  3. Pilihan dimasukkan ke satu baris sebanyak yang muat; sisanya ke
 *     baris berikutnya. Bila satu pilihan saja sudah lebih lebar dari
 *     satu kolom penuh, pilihan itu mendapat satu baris penuh sendiri.
 *
 * Lebar teks diukur dengan GD dari berkas font asli (Cambria/Caladea/
 * DejaVu), sehingga keputusan kolom sama persis dengan hasil render
 * Dompdf — bukan taksiran jumlah karakter.
 */
class SoalOpsiLayout
{
    /** Jarak aman agar teks tidak menyentuh kolom sebelah (mm). */
    private const SAFETY_MM = 1.5;

    /** Padding kanan antar kolom yang dipakai CSS `table.opts`. */
    private const GUTTER_MM = 1.5;

    /**
     * Susun pilihan menjadi baris-baris berkolom seragam.
     *
     * @param  array<int, array{label?: string, teks?: string, display_label?: string}>  $opsi
     * @param  float  $availableMm  lebar area isi soal (setelah margin & nomor)
     * @param  float  $fontPt  ukuran font opsi dalam pt
     * @return array{rows: array<int, array<int, array{option: array<string, mixed>, span: int}>>, columns: int, widths: array<int, float>}
     */
    public function layout(array $opsi, float $availableMm, float $fontPt, int $maxColumns = 4): array
    {
        $count = count($opsi);

        if ($count === 0) {
            return ['rows' => [], 'columns' => 0, 'widths' => []];
        }

        $widths = [];
        foreach ($opsi as $index => $option) {
            $widths[$index] = $this->textWidthMm($this->labelText($option), $fontPt);
        }

        // Cari susunan PALING PADAT: baris paling sedikit, lalu ruang
        // kosong paling sedikit. Contoh 4 pilihan yang tidak muat 4 kolom
        // dipilih 2x2 (2 baris, 0 lubang), bukan 3 kolom (2 baris, 2 lubang).
        $best = null;

        for ($columns = min($maxColumns, $count); $columns >= 1; $columns--) {
            $rows = $this->packInto($widths, $availableMm, $columns);

            if ($rows === null) {
                continue;
            }

            $rowCount = count($rows);
            $holes = ($rowCount * $columns) - $count;

            if ($best === null
                || $rowCount < $best['rows_count']
                || ($rowCount === $best['rows_count'] && $holes < $best['holes'])) {
                $best = [
                    'rows' => $rows,
                    'columns' => $columns,
                    'rows_count' => $rowCount,
                    'holes' => $holes,
                ];
            }
        }

        if ($best !== null) {
            return ['rows' => $best['rows'], 'columns' => $best['columns'], 'widths' => $widths];
        }

        // Tidak ada susunan yang muat: setiap pilihan satu baris penuh.
        $rows = [];
        foreach (array_keys($opsi) as $index) {
            $rows[] = [['index' => $index, 'span' => 1]];
        }

        return ['rows' => $rows, 'columns' => 1, 'widths' => $widths];
    }

    /**
     * Bagi pilihan ke baris dengan `$columns` kolom seragam.
     *
     * Mengembalikan null bila ada pilihan yang tidak muat di satu kolom,
     * sehingga pemanggil mencoba jumlah kolom lebih sedikit.
     *
     * @param  array<int, float>  $widths
     * @return array<int, array<int, array{index: int, span: int}>>|null
     */
    private function packInto(array $widths, float $availableMm, int $columns): ?array
    {
        if ($columns < 1) {
            return null;
        }

        $columnMm = $availableMm / $columns;

        // Satu pilihan pun tidak muat di kolom ini → coba kolom lebih sedikit.
        foreach ($widths as $width) {
            if ($width + self::SAFETY_MM > $columnMm) {
                return null;
            }
        }

        $rows = [];
        $current = [];

        foreach ($widths as $index => $width) {
            if (count($current) >= $columns) {
                $rows[] = $current;
                $current = [];
            }

            $current[] = ['index' => $index, 'span' => 1];
        }

        if ($current !== []) {
            $rows[] = $current;
        }

        return $rows;
    }

    /**
     * Lebar teks "A. isi pilihan" dalam mm.
     *
     * @param  array<string, mixed>  $option
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
