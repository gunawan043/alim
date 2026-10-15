<?php

declare(strict_types=1);

namespace App\Services\Ljk\Omr;

use RuntimeException;

/**
 * Homografi 2D (DLT) untuk korespondensi 4 titik.
 *
 * Dipakai mengoreksi perspektif: memetakan koordinat gambar sumber
 * (piksel foto) ke ruang kanonik template (piksel kanonik) dan sebaliknya.
 */
final class Homography
{
    /** @var array<int, float> [h1..h8] */
    private array $h;

    /**
     * @param  array<int, float>  $h
     */
    private function __construct(array $h)
    {
        $this->h = $h;
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $src  4 titik sumber (clockwise)
     * @param  array<int, array{0: float, 1: float}>  $dst  4 titik tujuan (urutan sama)
     */
    public static function fromQuads(array $src, array $dst): self
    {
        if (count($src) !== 4 || count($dst) !== 4) {
            throw new RuntimeException('Homography membutuhkan tepat 4 titik.');
        }

        // A * h = b  (8x8)
        $a = [];
        $b = [];

        for ($i = 0; $i < 4; $i++) {
            [$x, $y] = $src[$i];
            [$u, $v] = $dst[$i];

            $a[] = [$x, $y, 1.0, 0.0, 0.0, 0.0, -$x * $u, -$y * $u];
            $b[] = $u;
            $a[] = [0.0, 0.0, 0.0, $x, $y, 1.0, -$x * $v, -$y * $v];
            $b[] = $v;
        }

        return new self(self::solve($a, $b));
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $quad
     */
    public static function fromInverse(array $src, array $dst): self
    {
        return self::fromQuads($dst, $src);
    }

    /**
     * @return array{0: float, 1: float}
     */
    public function map(float $x, float $y): array
    {
        $h = $this->h;
        $den = $h[6] * $x + $h[7] * $y + 1.0;

        if (abs($den) < 1e-12) {
            $den = 1e-12;
        }

        return [
            ($h[0] * $x + $h[1] * $y + $h[2]) / $den,
            ($h[3] * $x + $h[4] * $y + $h[5]) / $den,
        ];
    }

    /**
     * Gaussian elimination dengan pivot parsial.
     *
     * @param  array<int, array<int, float>>  $a
     * @param  array<int, float>  $b
     * @return array<int, float>
     */
    private static function solve(array $a, array $b): array
    {
        $n = count($b);

        for ($col = 0; $col < $n; $col++) {
            $pivot = $col;
            $max = abs($a[$col][$col]);

            for ($r = $col + 1; $r < $n; $r++) {
                if (abs($a[$r][$col]) > $max) {
                    $max = abs($a[$r][$col]);
                    $pivot = $r;
                }
            }

            if ($max < 1e-10) {
                throw new RuntimeException('Sistem homografi singular — penanda geometris tidak valid.');
            }

            if ($pivot !== $col) {
                [$a[$pivot], $a[$col]] = [$a[$col], $a[$pivot]];
                [$b[$pivot], $b[$col]] = [$b[$col], $b[$pivot]];
            }

            $diag = $a[$col][$col];

            for ($c = $col; $c < $n; $c++) {
                $a[$col][$c] /= $diag;
            }

            $b[$col] /= $diag;

            for ($r = 0; $r < $n; $r++) {
                if ($r === $col) {
                    continue;
                }

                $factor = $a[$r][$col];
                if ($factor == 0.0) {
                    continue;
                }

                for ($c = $col; $c < $n; $c++) {
                    $a[$r][$c] -= $factor * $a[$col][$c];
                }

                $b[$r] -= $factor * $b[$col];
            }
        }

        return $b;
    }
}
