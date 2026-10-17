<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use App\Models\LjkExam;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Pencetak PDF lembar soal dengan pemilihan kertas otomatis.
 *
 * Aturan (minimalisasi kertas):
 *  - default **A4**; bila isi tidak muat satu halaman, coba **F4 (folio)**;
 *  - pakai F4 hanya bila jumlah halaman lebih sedikit daripada A4;
 *  - bila keduanya butuh jumlah halaman sama, tetap A4 (default).
 */
class LjkSoalPdfRenderer
{
    /**
     * @return array{bytes: string, paper: string, pages: int, attempts: array<int, array{paper: string, pages: int}>}
     */
    public function render(LjkExam $exam, bool $autoPaper = true): array
    {
        $sheet = app(LjkSoalSheet::class);
        $default = (string) config('ljk.soal.paper.default', 'a4');
        $fallback = (string) config('ljk.soal.paper.fallback', 'f4');

        $attempts = [];

        [$bytes, $pages] = $this->renderWithPaper($sheet, $exam, $default);
        $attempts[] = ['paper' => $default, 'pages' => $pages];

        $chosen = ['bytes' => $bytes, 'paper' => $default, 'pages' => $pages];

        $shouldTryFallback = $autoPaper
            && (bool) config('ljk.soal.paper.auto', true)
            && $pages > 1
            && $fallback !== $default;

        if ($shouldTryFallback) {
            [$fallbackBytes, $fallbackPages] = $this->renderWithPaper($sheet, $exam, $fallback);
            $attempts[] = ['paper' => $fallback, 'pages' => $fallbackPages];

            if ($this->choosePaper($pages, $fallbackPages) === $fallback) {
                $chosen = ['bytes' => $fallbackBytes, 'paper' => $fallback, 'pages' => $fallbackPages];
            }
        }

        return $chosen + ['attempts' => $attempts];
    }

    /**
     * Pilih kertas dari jumlah halaman hasil render (fungsi murni & teruji).
     */
    public function choosePaper(int $defaultPages, int $fallbackPages): string
    {
        $default = (string) config('ljk.soal.paper.default', 'a4');
        $fallback = (string) config('ljk.soal.paper.fallback', 'f4');

        // Sudah muat satu halaman pada kertas default.
        if ($defaultPages <= 1) {
            return $default;
        }

        // F4 hanya bila benar-benar menghemat halaman.
        return $fallbackPages < $defaultPages ? $fallback : $default;
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function renderWithPaper(LjkSoalSheet $sheet, LjkExam $exam, string $paperKey): array
    {
        $html = $sheet->render($exam, $paperKey);
        $paper = $sheet->paper($paperKey);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        LjkPdfFonts::configure($options);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, $paper['width_mm'] * 2.834645669, $paper['height_mm'] * 2.834645669]);
        $dompdf->render();

        // Nomor halaman di pita margin bawah (margin 0.1" = 2.54mm) agar
        // tidak menimpa baris terakhir isi soal.
        $canvas = $dompdf->getCanvas();
        $canvas->page_text($canvas->get_width() - 140, $canvas->get_height() - 5, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', 'serif', 8, [0.35, 0.35, 0.35]);

        return [$dompdf->output(), (int) $canvas->get_page_count()];
    }
}
