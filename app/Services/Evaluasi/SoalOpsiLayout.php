<?php

declare(strict_types=1);

namespace App\Services\Evaluasi;

use App\Services\Ljk\LjkPdfFonts;

/**
 * Tata letak pilihan jawaban pada naskah soal.
 *
 * Aturan (sesuai ketentuan sekolah):
 *  1. SEMUA kolom dalam satu soal lebarnya SAMA PERSIS. Tidak ada pilihan
 *     yang mendapat kolom lebih lebar dari yang lain — A, B, C, D sama.
 *  2. Jumlah kolom = jumlah opsi: A–D → 4 kolom, A–E → 5 kolom.
 *     Tidak perlu diatur manual.
 *  3. Satu baris memuat paling banyak sejumlah kolom itu. Pilihan yang
 *     teksnya tidak muat dalam satu kolom turun ke baris berikutnya
 *     (pilihan setelahnya ikut turun agar tidak ada baris yang isinya
 *     tidak sejajar).
 *  4. Tidak ada teks yang terlipat: lebar diukur dari berkas font asli
 *     (Cambria/Caladea/DejaVu) dengan GD, bukan taksiran jumlah huruf.
 *
 * Contoh 4 opsi (kolom ¼ lebar area):
 *   semuanya pendek          → A B C D  (satu baris)
 *   A panjang, sisanya pendek → A  (baris 1)
 *                               B C D  (baris 2, mulai kolom 1)
 */
class SoalOpsiLayout
{
    /** Jarak aman agar teks tidak menyentuh kolom sebelah (mm). */
    private const SAFETY_MM = 1.2;

    /** Batas atas jumlah kolom (A..E = 5 pilihan). */
    public const MAX_COLUMNS = 5;

    /**
     * Susun pilihan menjadi baris-baris.
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

        // Jumlah kolom = jumlah opsi (maks 5), dibatasi pengaturan.
        $columns = $maxColumns > 0
            ? min(self::MAX_COLUMNS, $count, $maxColumns)
            : min(self::MAX_COLUMNS, $count);
        $columns = max(1, $columns);

        // Lebar kolom SAMA untuk semuanya (bukan per pilihan).
        $unitMm = $availableMm / $columns;

        $widths = [];
        $spans = [];
        foreach ($opsi as $index => $option) {
            $widths[$index] = $this->textWidthMm($this->labelText($option), $fontPt);
            // span selalu 1: pilihan tidak pernah melebar. Nilai dipertahankan
            // agar bentuk keluaran tetap kompatibel dengan pemanggil lama.
            $spans[$index] = 1;
        }

        $rows = $this->packRows($widths, $unitMm, $columns);

        return [
            'rows' => $rows,
            'columns' => $columns,
            'widths' => $widths,
            'spans' => $spans,
        ];
    }

    /**
     * Bagi pilihan ke baris: maksimal $columns per baris, dan pilihan yang
     * teksnya tidak muat di satu kolom turun baris beserta sisanya.
     *
     * @param  array<int, float>  $widths  lebar teks tiap pilihan (mm)
     * @return array<int, array<int, array{index: int, span: int}>>
     */
    private function packRows(array $widths, float $unitMm, int $columns): array
    {
        $rows = [];
        $current = [];
        // True bila baris yang sedang diisi sudah penuh: karena berisi
        // pilihan yang tidak muat (berdiri sendiri) atau sudah N pilihan.
        $currentFull = false;

        foreach ($widths as $index => $width) {
            $muat = $width + self::SAFETY_MM <= $unitMm;

            // Tutup baris bila sudah penuh, atau bila pilihan panjang ini
            // tidak muat dan baris sudah ada isinya.
            if ($current !== [] && ($currentFull || ! $muat)) {
                $rows[] = $current;
                $current = [];
                $currentFull = false;
            }

            $current[] = ['index' => $index, 'span' => 1];

            // Baris otomatis penuh setelah pilihan panjang (berdiri sendiri)
            // atau setelah mencapai jumlah kolom.
            $currentFull = ! $muat || count($current) >= $columns;
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
