<?php

declare(strict_types=1);

namespace App\Services\Evaluasi;

use App\Services\Ljk\LjkPdfFonts;

/**
 * Tata letak pilihan jawaban pada naskah soal.
 *
 * Aturan (sesuai ketentuan sekolah):
 *  1. Jumlah kolom = JUMLAH OPSI. 4 opsi (A–D) → 4 kolom (jatah 1/4),
 *     5 opsi (A–E) → 5 kolom (jatah 1/5). Tidak perlu diatur manual.
 *  2. Setiap opsi mendapat jatah kolom sejumlah yang dibutuhkan agar
 *     muat dalam SATU baris:
 *        butuh ≤ 1 jatah → 1 jatah
 *        > 1 jatah (mis. makan 2/4) → 2 jatah
 *        > 2 jatah (mis. makan 3/4) → 3 jatah, dan seterusnya
 *  3. Jatah dijumlahkan per baris; baris baru dibuka bila jatahnya penuh.
 *     Contoh 4 opsi: A=2 jatah, B=C=D=1 jatah → A B C D muat satu baris
 *     (2+1+1+1 = 4 jatah).
 *  4. Tidak ada pilihan yang terlipat: lebar teks diukur dari berkas font
 *     asli (Cambria/Caladea/DejaVu) dengan GD, bukan taksiran jumlah huruf.
 *
 * Lebar kolom dihitung per baris dari TOTAL JATAH baris itu, sehingga
 * lebar setiap kolom selalu seragam dalam satu baris.
 */
class SoalOpsiLayout
{
    /** Jarak aman agar teks tidak menyentuh kolom sebelah (mm). */
    private const SAFETY_MM = 1.2;

    /** Batas atas jumlah kolom (A..E = 5 pilihan). */
    public const MAX_COLUMNS = 5;

    /**
     * Susun pilihan menjadi baris-baris berjatah kolom.
     *
     * @param  array<int, array{label?: string, teks?: string, display_label?: string}>  $opsi
     * @param  float  $availableMm  lebar area isi soal (setelah margin & nomor)
     * @param  float  $fontPt  ukuran font opsi dalam pt
     * @param  int  $maxColumns  0 = ikut jumlah opsi; selain itu batas atas
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

        // Jumlah kolom dasar = jumlah opsi (maks 5), dibatasi pengaturan.
        $columns = $maxColumns > 0 ? min(self::MAX_COLUMNS, $count, $maxColumns) : min(self::MAX_COLUMNS, $count);
        $columns = max(1, $columns);

        $widths = [];
        foreach ($opsi as $index => $option) {
            $widths[$index] = $this->textWidthMm($this->labelText($option), $fontPt);
        }

        // Jatah kolom tiap opsi berdasarkan lebar yang dibutuhkan.
        $unitMm = $availableMm / $columns;
        $spans = [];
        foreach ($widths as $index => $width) {
            $spans[$index] = $this->spanFor($width, $unitMm, $columns);
        }

        // Baris TIDAK diseimbangkan: aturan sekolah adalah "jatah dijumlahkan
        // per baris, baris baru hanya bila jatahnya penuh". Jadi
        // A(2) B(1) C(1) D(1) = 4 jatah tetap satu baris.
        $rows = $this->packRows($spans, $columns);

        return [
            'rows' => $rows,
            'columns' => $columns,
            'widths' => $widths,
            'spans' => $spans,
        ];
    }

    /**
     * Berapa jatah kolom yang dibutuhkan satu pilihan.
     *
     * Toleransi dibuat sedikit longgar: teks yang hanya kelebihan
     * sepersekian mm (pembulatan pengukuran) tetap dihitung 1 jatah,
     * karena CSS memberi padding kanan sebagai ruang kosong antar kolom.
     * Tanpa kelonggaran ini, "A. tujuh puluh dua" (39,42 mm vs jatah
     * 39,39 mm) akan naik ke 2 jatah dan mendorong pilihan lain ke baris
     * baru.
     */
    private function spanFor(float $widthMm, float $unitMm, int $columns): int
    {
        if ($unitMm <= 0) {
            return 1;
        }

        // Pengukuran teks bisa meleset ~1% karena hinting font vs Dompdf.
        $tolerance = max(1.0, $unitMm * 0.03);
        $needed = (int) ceil(($widthMm + self::SAFETY_MM) / $unitMm);

        // Muat di 1 jatah bila kelebihannya hanya sebatas toleransi.
        if ($needed > 1 && $widthMm <= $unitMm + $tolerance) {
            return 1;
        }

        return max(1, min($columns, $needed));
    }

    /**
     * Bagi pilihan ke baris berdasarkan jumlah jatahnya.
     *
     * @param  array<int, int>  $spans
     * @return array<int, array<int, array{index: int, span: int}>>
     */
    private function packRows(array $spans, int $columns): array
    {
        $rows = [];
        $current = [];
        $used = 0;

        foreach ($spans as $index => $span) {
            if ($current !== [] && $used + $span > $columns) {
                $rows[] = $current;
                $current = [];
                $used = 0;
            }

            $current[] = ['index' => $index, 'span' => $span];
            $used += $span;
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
