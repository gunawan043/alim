<?php

declare(strict_types=1);

namespace App\Services\Ljk\Omr;

use GdImage;
use SplFixedArray;

/**
 * Representasi grayscale + integral image untuk analisis OMR.
 *
 * - `gray` : nilai 0..255 per piksel (indeks y*w+x)
 * - `integral` : integral image (w+1)x(h+1) untuk rerata lokal adaptif
 *   (tahan terhadap bayangan dan pencahayaan tidak merata)
 */
final class GrayImage
{
    private SplFixedArray $gray;

    private SplFixedArray $integral;

    private int $window;

    public function __construct(
        public readonly GdImage $gd,
        public readonly int $width,
        public readonly int $height,
        float $windowRatio = 0.035,
    ) {
        $this->gray = new SplFixedArray($width * $height);
        $this->integral = new SplFixedArray(($width + 1) * ($height + 1));

        for ($y = 0; $y < $height; $y++) {
            $rowBase = $y * $width;
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($gd, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                // Luminance ITU-R BT.601
                $this->gray[$rowBase + $x] = (int) (0.299 * $r + 0.587 * $g + 0.114 * $b);
            }
        }

        $iw = $width + 1;
        for ($y = 1; $y <= $height; $y++) {
            $rowSum = 0;
            $grayBase = ($y - 1) * $width;
            $integralBase = $y * $iw;
            $prevBase = ($y - 1) * $iw;

            for ($x = 1; $x <= $width; $x++) {
                $rowSum += $this->gray[$grayBase + $x - 1];
                $this->integral[$integralBase + $x] = $rowSum + $this->integral[$prevBase + $x];
            }
        }

        $this->window = max(5, (int) round($windowRatio * min($width, $height)));
    }

    public function grayAt(int $x, int $y): int
    {
        $x = max(0, min($this->width - 1, $x));
        $y = max(0, min($this->height - 1, $y));

        return $this->gray[$y * $this->width + $x];
    }

    /**
     * Sampling bilinear pada koordinat pecahan.
     */
    public function bilinear(float $x, float $y): float
    {
        $x = max(0.0, min($this->width - 1.001, $x));
        $y = max(0.0, min($this->height - 1.001, $y));

        $x0 = (int) floor($x);
        $y0 = (int) floor($y);
        $x1 = min($this->width - 1, $x0 + 1);
        $y1 = min($this->height - 1, $y0 + 1);

        $fx = $x - $x0;
        $fy = $y - $y0;

        $g00 = (float) $this->gray[$y0 * $this->width + $x0];
        $g10 = (float) $this->gray[$y0 * $this->width + $x1];
        $g01 = (float) $this->gray[$y1 * $this->width + $x0];
        $g11 = (float) $this->gray[$y1 * $this->width + $x1];

        $top = $g00 + ($g10 - $g00) * $fx;
        $bottom = $g01 + ($g11 - $g01) * $fx;

        return $top + ($bottom - $top) * $fy;
    }

    /**
     * Rerata lokal via integral image (jendela persegi di sekitar titik).
     */
    public function localMean(int $x, int $y): float
    {
        $half = intdiv($this->window, 2);
        $x0 = max(0, $x - $half);
        $y0 = max(0, $y - $half);
        $x1 = min($this->width, $x + $half + 1);
        $y1 = min($this->height, $y + $half + 1);

        $iw = $this->width + 1;
        $sum = $this->integral[$y1 * $iw + $x1]
            - $this->integral[$y0 * $iw + $x1]
            - $this->integral[$y1 * $iw + $x0]
            + $this->integral[$y0 * $iw + $x0];

        $area = max(1, ($x1 - $x0) * ($y1 - $y0));

        return $sum / $area;
    }

    /**
     * Uji tinta pada koordinat pecahan: lebih gelap dari rerata lokal − C.
     */
    public function isInkAt(float $x, float $y, float $c, float $ratio = 0.22): bool
    {
        $gx = (int) round($x);
        $gy = (int) round($y);

        $mean = $this->localMean($gx, $gy);
        $value = $this->bilinear($x, $y);

        // Ambang hibrida: absolut + relatif (tahan bayangan/gradien).
        return $value < ($mean - max($c, $mean * $ratio));
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public function colorAt(float $x, float $y): array
    {
        $ix = max(0, min($this->width - 1, (int) round($x)));
        $iy = max(0, min($this->height - 1, (int) round($y)));
        $rgb = imagecolorat($this->gd, $ix, $iy);

        return [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
    }

    /**
     * Ketajaman: varians Laplacian (sampling bertingkat agar cepat).
     */
    public function sharpness(): float
    {
        $step = max(1, (int) round(min($this->width, $this->height) / 720));
        $sum = 0.0;
        $sumSq = 0.0;
        $n = 0;

        for ($y = $step; $y < $this->height - $step; $y += $step) {
            for ($x = $step; $x < $this->width - $step; $x += $step) {
                $lap = -4.0 * $this->gray[$y * $this->width + $x]
                    + $this->gray[($y - $step) * $this->width + $x]
                    + $this->gray[($y + $step) * $this->width + $x]
                    + $this->gray[$y * $this->width + $x - $step]
                    + $this->gray[$y * $this->width + $x + $step];
                $sum += $lap;
                $sumSq += $lap * $lap;
                $n++;
            }
        }

        if ($n === 0) {
            return 0.0;
        }

        $mean = $sum / $n;

        return max(0.0, $sumSq / $n - $mean * $mean);
    }

    public function brightness(): float
    {
        $step = max(1, (int) round(min($this->width, $this->height) / 480));
        $sum = 0.0;
        $n = 0;

        for ($y = 0; $y < $this->height; $y += $step) {
            for ($x = 0; $x < $this->width; $x += $step) {
                $sum += $this->gray[$y * $this->width + $x];
                $n++;
            }
        }

        return $n > 0 ? $sum / $n : 0.0;
    }

    public function contrast(): float
    {
        $step = max(1, (int) round(min($this->width, $this->height) / 480));
        $sum = 0.0;
        $sumSq = 0.0;
        $n = 0;

        for ($y = 0; $y < $this->height; $y += $step) {
            for ($x = 0; $x < $this->width; $x += $step) {
                $v = (float) $this->gray[$y * $this->width + $x];
                $sum += $v;
                $sumSq += $v * $v;
                $n++;
            }
        }

        if ($n === 0) {
            return 0.0;
        }

        $mean = $sum / $n;

        return sqrt(max(0.0, $sumSq / $n - $mean * $mean));
    }
}
