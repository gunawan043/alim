<?php

declare(strict_types=1);

namespace App\Services\Evaluasi;

use App\Services\Ljk\LjkPdfFonts;

/**
 * Tata letak pilihan jawaban pada naskah soal.
 *
 * Aturan (sesuai ketentuan sekolah):
 *  1. Setiap pilihan WAJIB muat dalam 1 baris — tidak boleh terlipat.
 *  2. Lebar kolom SERAGAM: semua pilihan dalam satu baris memakai lebar
 *     yang sama, tidak ada yang penuh lalu sisanya sempit.
 *  3. "Otomatis" = satu baris diisi SEBANYAK YANG MUAT: bisa 5, 4, 3, 2,
 *     atau 1 pilihan, bergantung panjang teks masing-masing.
 *  4. Pilihan yang terlalu panjang untuk satu kolom mendapat satu baris
 *     penuh sendiri; pilihan di baris lain tidak ikut terpengaruh.
 *
 * Lebar teks diukur dengan GD dari berkas font asli (Cambria/Caladea/
 * DejaVu), sehingga keputusan kolom sama persis dengan hasil render
 * Dompdf — bukan taksiran jumlah karakter.
 */
class SoalOpsiLayout
{
    /** Jarak aman agar teks tidak menyentuh kolom sebelah (mm). */
    private const SAFETY_MM = 1.5;

    /** Batas atas kolom mode otomatis (A..E = 5 pilihan). */
    public const MAX_COLUMNS = 5;

    /**
     * Susun pilihan menjadi baris-baris berkolom seragam.
     *
     * @param  array<int, array{label?: string, teks?: string, display_label?: string}>  $opsi
     * @param  float  $availableMm  lebar area isi soal (setelah margin & nomor)
     * @param  float  $fontPt  ukuran font opsi dalam pt
     * @param  int  $maxColumns  0 = otomatis (sampai 5); selain itu batas atas
     * @return array{rows: array<int, array<int, array{index: int, span: int}>>, columns: int, widths: array<int, float>}
     */
    public function layout(array $opsi, float $availableMm, float $fontPt, int $maxColumns = 0): array
    {
        $count = count($opsi);

        if ($count === 0) {
            return ['rows' => [], 'columns' => 0, 'widths' => []];
        }

        $widths = [];
        foreach ($opsi as $index => $option) {
            $widths[$index] = $this->textWidthMm($this->labelText($option), $fontPt);
        }

        $limit = $maxColumns > 0 ? min(self::MAX_COLUMNS, $maxColumns) : self::MAX_COLUMNS;

        $rows = [];
        $columns = 1;
        $pending = array_keys($widths);

        // Isi baris demi baris: sebanyak yang muat.
        while ($pending !== []) {
            $perRow = $this->columnsThatFit(
                array_map(fn ($i) => $widths[$i], $pending),
                $availableMm,
                $limit
            );

            $slice = array_splice($pending, 0, $perRow);
            $rows[] = array_map(fn ($i) => ['index' => $i, 'span' => 1], $slice);
            $columns = max($columns, count($slice));
        }

        $rows = $this->balanceTail($rows, $widths, $availableMm, $limit);

        foreach ($rows as $row) {
            $columns = max($columns, count($row));
        }

        return ['rows' => $rows, 'columns' => $columns, 'widths' => $widths];
    }

    /**
     * Seimbangkan baris TERAKHIR bila isinya cuma 1 pilihan sementara baris
     * sebelumnya penuh — mis. 5 pilihan yang muat 4 per baris menjadi
     * "ABCD | E" dan diubah menjadi "ABC | DE" agar tidak ada baris
     * menggantung berisi satu pilihan saja.
     *
     * Hanya baris terakhir yang disentuh, sehingga pilihan sangat panjang
     * di tengah tidak merusak susunan baris lain.
     *
     * @param  array<int, array<int, array{index: int, span: int}>>  $rows
     * @param  array<int, float>  $widths
     * @return array<int, array<int, array{index: int, span: int}>>
     */
    private function balanceTail(array $rows, array $widths, float $availableMm, int $limit): array
    {
        $total = count($rows);

        if ($total < 2 || count($rows[$total - 1]) > 1) {
            return $rows;
        }

        // Coba pindahkan satu pilihan dari baris sebelumnya ke baris terakhir.
        $prevCount = count($rows[$total - 2]);

        for ($take = 1; $take <= $prevCount - 1; $take++) {
            $candidatePrev = array_slice($rows[$total - 2], 0, $prevCount - $take);
            $candidateLast = array_merge(array_slice($rows[$total - 2], $prevCount - $take), $rows[$total - 1]);

            if (count($candidateLast) > $limit) {
                continue;
            }

            $prevWidths = array_map(fn ($c) => $widths[$c['index']], $candidatePrev);
            $lastWidths = array_map(fn ($c) => $widths[$c['index']], $candidateLast);

            if ($this->fits($prevWidths, $availableMm, count($candidatePrev))
                && $this->fits($lastWidths, $availableMm, count($candidateLast))) {
                $rows[$total - 2] = $candidatePrev;
                $rows[$total - 1] = $candidateLast;

                return $rows;
            }
        }

        return $rows;
    }

    /**
     * Jumlah kolom terbanyak yang masih muat untuk sekelompok pilihan.
     *
     * Dicoba dari banyak ke sedikit supaya pilihan pendek selalu rapat
     * (A B C D E dalam satu baris).
     *
     * @param  array<int, float>  $widths
     */
    private function columnsThatFit(array $widths, float $availableMm, int $limit): int
    {
        for ($columns = min($limit, count($widths)); $columns >= 1; $columns--) {
            if ($this->fits($widths, $availableMm, $columns)) {
                return $columns;
            }
        }

        // Terlalu panjang untuk kolom mana pun: satu pilihan satu baris penuh.
        return 1;
    }

    /**
     * Apakah pilihan-pilihan ini muat bila dibagi ke `$columns` kolom seragam?
     *
     * @param  array<int, float>  $widths
     */
    private function fits(array $widths, float $availableMm, int $columns): bool
    {
        if ($columns < 1) {
            return false;
        }

        $columnMm = $availableMm / $columns;

        foreach ($widths as $width) {
            if ($width + self::SAFETY_MM > $columnMm) {
                return false;
            }
        }

        return true;
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
