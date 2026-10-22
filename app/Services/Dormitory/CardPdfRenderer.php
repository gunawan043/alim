<?php

namespace App\Services\Dormitory;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Render kartu asrama (58 mm) dengan tinggi halaman mengikuti isi.
 *
 * Dompdf tidak mendukung `@page { size: 58mm auto; }`, jadi tinggi minimum
 * dicari dengan render berulang dan pemeriksaan jumlah halaman. Hasilnya
 * menghilangkan ruang kosong panjang di bawah kartu.
 */
class CardPdfRenderer
{
    private const WIDTH_PT = 164.41; // 58 mm

    private const MIN_HEIGHT_PT = 220;

    private const MAX_HEIGHT_PT = 760;

    /**
     * @return array{output:string,height:int,pages:int}
     */
    public function render(string $html, ?int $minHeight = null, ?int $maxHeight = null): array
    {
        $low = $minHeight ?? self::MIN_HEIGHT_PT;
        $high = $maxHeight ?? self::MAX_HEIGHT_PT;
        $bestOutput = null;
        $bestHeight = $high;
        $bestPages = 99;

        while ($low <= $high) {
            $height = intdiv($low + $high, 2);
            [$output, $pages] = $this->renderAtHeight($html, $height);

            if ($pages <= 1) {
                $bestOutput = $output;
                $bestHeight = $height;
                $bestPages = $pages;
                $high = $height - 1;
            } else {
                $low = $height + 1;
            }
        }

        if ($bestOutput === null) {
            [$bestOutput, $bestPages] = $this->renderAtHeight($html, self::MAX_HEIGHT_PT);
            $bestHeight = self::MAX_HEIGHT_PT;
        }

        return [
            'output' => $bestOutput,
            // Sedikit ruang aman agar teks terakhir tidak terpotong.
            'height' => min(self::MAX_HEIGHT_PT, $bestHeight + 4),
            'pages' => $bestPages,
        ];
    }

    /**
     * Render dengan estimasi tinggi dari controller. Jika ternyata masih
     * lebih dari satu halaman, tinggi dinaikkan bertahap. Ini jauh lebih
     * cepat daripada binary search untuk setiap kartu.
     *
     * @return array{output:string,height:int,pages:int}
     */
    public function renderEstimated(string $html, int $estimatedHeight): array
    {
        $height = max(self::MIN_HEIGHT_PT, min(self::MAX_HEIGHT_PT, $estimatedHeight));

        for ($attempt = 0; $attempt < 3; $attempt++) {
            [$output, $pages] = $this->renderAtHeight($html, $height);

            if ($pages <= 1) {
                return ['output' => $output, 'height' => $height, 'pages' => $pages];
            }

            if ($height >= self::MAX_HEIGHT_PT) {
                return ['output' => $output, 'height' => $height, 'pages' => $pages];
            }

            $height = min(self::MAX_HEIGHT_PT, $height + 70);
        }

        [$output, $pages] = $this->renderAtHeight($html, self::MAX_HEIGHT_PT);

        return ['output' => $output, 'height' => self::MAX_HEIGHT_PT, 'pages' => $pages];
    }

    /**
     * Render dengan tinggi tetap. Dipakai untuk PDF massal yang berisi
     * beberapa kartu dengan page break.
     *
     * @return array{output:string,height:int,pages:int}
     */
    public function renderFixed(string $html, int $height = 430): array
    {
        [$output, $pages] = $this->renderAtHeight($html, $height);

        return ['output' => $output, 'height' => $height, 'pages' => $pages];
    }

    /**
     * @return array{0:string,1:int}
     */
    private function renderAtHeight(string $html, int $height): array
    {
        $options = (new Options)
            ->set('isRemoteEnabled', false)
            ->set('isHtml5ParserEnabled', true)
            ->set('defaultFont', 'Courier');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, self::WIDTH_PT, $height]);
        $dompdf->render();

        return [$dompdf->output(), (int) $dompdf->getCanvas()->get_page_count()];
    }
}
