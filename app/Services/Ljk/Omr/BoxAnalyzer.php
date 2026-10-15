<?php

declare(strict_types=1);

namespace App\Services\Ljk\Omr;

/** Batasi nilai ke rentang 0..1. */
function clamp01(float $value): float
{
    return max(0.0, min(1.0, $value));
}

/**
 * Analisis satu kotak pilihan jawaban.
 *
 * Pendekatan: karakteristik tinta + pola garis (bukan sekadar jumlah
 * piksel gelap). Fitur yang diukur:
 *  - kerapatan tinta di dalam kotak,
 *  - cakupan dua garis diagonal secara terpisah + keseimbangannya,
 *  - tinta pada empat sudut dan titik pusat (ciri tanda X),
 *  - pita horizontal/vertikal (membedakan coretan/garis dari X),
 *  - keseragaman sebaran (mendeteksi blok tinta/penghapusan).
 *
 * Koordinat kotak dalam milimeter; homografi `mmToSource` memetakan ke
 * piksel foto sehingga sampel dibaca pada posisi yang benar meski foto
 * miring/perspektif.
 */
final class BoxAnalyzer
{
    private const INTERIOR_GRID_X = 21;

    private const INTERIOR_GRID_Y = 15;

    private const CORNER_GRID = 6;

    private const CENTER_GRID = 9;

    /**
     * @param  array{x_mm: float, y_mm: float, w_mm: float, h_mm: float}  $box
     * @param  array<string, mixed>  $cfg
     * @return array<string, mixed>
     */
    public function analyze(GrayImage $img, Homography $mmToSource, array $box, array $cfg): array
    {
        $excl = (float) ($cfg['border_exclusion_ratio'] ?? 0.16);
        $c = (float) ($cfg['adaptive_c'] ?? 10.0);

        $x0 = $box['x_mm'] + $box['w_mm'] * $excl;
        $y0 = $box['y_mm'] + $box['h_mm'] * $excl;
        $w = $box['w_mm'] * (1 - 2 * $excl);
        $h = $box['h_mm'] * (1 - 2 * $excl);

        if ($w <= 0.2 || $h <= 0.2) {
            return $this->emptyResult(true);
        }

        // Keluar bidang gambar?
        $outOfBounds = 0;
        $checks = 0;
        foreach ([[0, 0], [1, 0], [0, 1], [1, 1], [0.5, 0.5]] as [$u, $v]) {
            [$sx, $sy] = $mmToSource->map($x0 + $u * $w, $y0 + $v * $h);
            $checks++;
            if ($sx < 0 || $sy < 0 || $sx > $img->width || $sy > $img->height) {
                $outOfBounds++;
            }
        }
        $outsideRatio = $checks > 0 ? $outOfBounds / $checks : 0.0;

        // ── Kerapatan tinta interior ─────────────────────────────────────
        $ink = 0;
        $n = 0;
        $cellInk = array_fill(0, 9, 0);
        $cellN = array_fill(0, 9, 0);
        $blueInk = 0;
        $inkSamples = 0;

        for ($gy = 0; $gy < self::INTERIOR_GRID_Y; $gy++) {
            $v = ($gy + 0.5) / self::INTERIOR_GRID_Y;
            for ($gx = 0; $gx < self::INTERIOR_GRID_X; $gx++) {
                $u = ($gx + 0.5) / self::INTERIOR_GRID_X;
                $isInk = $this->inkAt($img, $mmToSource, $x0 + $u * $w, $y0 + $v * $h, $c);
                $n++;

                if ($isInk) {
                    $ink++;
                    $inkSamples++;
                    [$r, $g, $b] = $this->colorAt($img, $mmToSource, $x0 + $u * $w, $y0 + $v * $h);
                    if (($b - $r) > (int) ($cfg['blue_ink_min_delta'] ?? 14) && $b > 60) {
                        $blueInk++;
                    }
                }

                $cell = intdiv($gy * 3, self::INTERIOR_GRID_Y) * 3 + intdiv($gx * 3, self::INTERIOR_GRID_X);
                $cellN[$cell]++;
                if ($isInk) {
                    $cellInk[$cell]++;
                }
            }
        }

        $inkRatio = $n > 0 ? $ink / $n : 0.0;

        // Keseragaman: simpangan baku 9 sel dibagi rerata.
        $cells = [];
        for ($i = 0; $i < 9; $i++) {
            $cells[$i] = $cellN[$i] > 0 ? $cellInk[$i] / $cellN[$i] : 0.0;
        }
        $cellMean = array_sum($cells) / 9.0;
        $cellVar = 0.0;
        foreach ($cells as $cv) {
            $cellVar += ($cv - $cellMean) ** 2;
        }
        $uniformity = $cellMean > 1e-6 ? sqrt($cellVar / 9.0) / $cellMean : 0.0;

        // ── Cakupan diagonal ─────────────────────────────────────────────
        $samples = max(8, (int) ($cfg['diagonal_samples'] ?? 26));
        $band = max(0.15, (float) ($cfg['diagonal_band_mm'] ?? 0.55));
        $offsets = [];
        for ($o = -$band; $o <= $band + 1e-9; $o += max(0.1, $band / 3)) {
            $offsets[] = $o;
        }

        $diag1 = $this->diagonalCoverage($img, $mmToSource, $x0, $y0, $w, $h, $samples, $offsets, $c, false);
        $diag2 = $this->diagonalCoverage($img, $mmToSource, $x0, $y0, $w, $h, $samples, $offsets, $c, true);

        // ── Empat sudut ──────────────────────────────────────────────────
        $cornerScore = $this->cornerScore($img, $mmToSource, $x0, $y0, $w, $h, $c);

        // ── Pusat ────────────────────────────────────────────────────────
        $centerScore = clamp01($this->rectInkRatio($img, $mmToSource, $x0 + $w * 0.33, $y0 + $h * 0.33, $w * 0.34, $h * 0.34, self::CENTER_GRID, $c) / 0.30);

        // ── Pita horizontal/vertikal ─────────────────────────────────────
        $hBand = $this->rectInkRatio($img, $mmToSource, $x0, $y0 + $h * 0.40, $w, $h * 0.20, self::CENTER_GRID, $c);
        $vBand = $this->rectInkRatio($img, $mmToSource, $x0 + $w * 0.40, $y0, $w * 0.20, $h, self::CENTER_GRID, $c);

        // ── Sel tengah tepi ──────────────────────────────────────────────
        // Tanda X meninggalkan area tengah tepi relatif kosong (garis keluar
        // dari sudut), sedangkan coretan/garis/blok tinta mengisinya.
        $edgeMid = (
            $this->rectInkRatio($img, $mmToSource, $x0 + $w * 0.38, $y0, $w * 0.24, $h * 0.22, 5, $c)
            + $this->rectInkRatio($img, $mmToSource, $x0 + $w * 0.38, $y0 + $h * 0.78, $w * 0.24, $h * 0.22, 5, $c)
            + $this->rectInkRatio($img, $mmToSource, $x0, $y0 + $h * 0.38, $w * 0.22, $h * 0.24, 5, $c)
            + $this->rectInkRatio($img, $mmToSource, $x0 + $w * 0.78, $y0 + $h * 0.38, $w * 0.22, $h * 0.24, 5, $c)
        ) / 4.0;

        // ── Jumlah goresan (run tinta) pada garis tengah ────────────────
        // X: maksimum ~3 run; coretan banyak garis: >= 5 run; blok tinta: 1 run panjang.
        $hRuns = $this->maxRunCount($img, $mmToSource, $x0, $y0, $w, $h, $c, true);
        $vRuns = $this->maxRunCount($img, $mmToSource, $x0, $y0, $w, $h, $c, false);

        $signature = [];
        $maxDiag = max($diag1, $diag2);
        $minDiag = min($diag1, $diag2);
        $balanceMin = (float) ($cfg['diagonal_balance_min'] ?? 0.34);
        $balance = $maxDiag > 1e-6 ? $minDiag / $maxDiag : 0.0;

        if ($maxDiag > 0.62 && $balance < $balanceMin) {
            $signature[] = 'diagonal_line';
        }
        if ($hBand > 0.62 && $vBand < 0.28) {
            $signature[] = 'horizontal_lines';
        }
        if ($vBand > 0.62 && $hBand < 0.28) {
            $signature[] = 'vertical_lines';
        }
        // Tinta padat yang tersebar cukup merata = coretan/blok tinta, bukan X.
        // X asli punya variansi sel tinggi (tinta terkonsentrasi di sudut+pusat).
        if ($inkRatio > 0.28 && $uniformity < 0.35) {
            $signature[] = 'smudge';
        }
        if ($inkRatio > 0.02 && $inkRatio < 0.075 && $maxDiag < 0.45) {
            $signature[] = 'faint';
        }
        if ($centerScore > 0.85 && $cornerScore < 0.25) {
            $signature[] = 'center_blob';
        }
        if ($edgeMid > 0.42) {
            $signature[] = 'edge_fill';
        }
        // X = 2 coretan → maksimum 2-3 run; coretan berlapis menghasilkan >= 4.
        if (max($hRuns, $vRuns) >= 4 && $inkRatio > 0.12) {
            $signature[] = 'many_strokes';
        }
        if ($inkRatio > 0.78) {
            $signature[] = 'solid_fill';
        }
        // Kedua diagonal memang panjang, tetapi tinta TIDAK terpusat di titik
        // temu (center rendah) dan tidak terkonsentrasi di sudut — pola ini
        // khas coretan berlapis/garis silang acak, bukan tanda X tunggal.
        if ($minDiag > 0.5 && $centerScore < 0.6 && $inkRatio > 0.12) {
            $signature[] = 'diffuse_strokes';
        }
        if ($outsideRatio > 0.5) {
            $signature[] = 'out_of_bounds';
        }

        return [
            'ink_ratio' => round($inkRatio, 4),
            'diag1' => round($diag1, 4),
            'diag2' => round($diag2, 4),
            'diag_balance' => round($balance, 4),
            'corner_score' => round($cornerScore, 4),
            'center_score' => round($centerScore, 4),
            'h_band' => round($hBand, 4),
            'v_band' => round($vBand, 4),
            'h_runs' => $hRuns,
            'v_runs' => $vRuns,
            'edge_mid' => round($edgeMid, 4),
            'uniformity' => round($uniformity, 4),
            'ink_blue_ratio' => $inkSamples > 0 ? round($blueInk / $inkSamples, 4) : null,
            'ink_pixels' => $ink,
            'sample_count' => $n,
            'outside_ratio' => round($outsideRatio, 4),
            'signature' => $signature,
            'score' => 0.0,
        ];
    }

    /**
     * Hitung skor bukti 0..1 dari fitur mentah.
     *
     * @param  array<string, mixed>  $f
     * @param  array<string, mixed>  $cfg
     * @return array<string, mixed> (fitur + score + flags)
     */
    public function score(array $f, array $cfg): array
    {
        $w = $cfg['weights'] ?? [];
        $wDiag = (float) ($w['diagonal_pair'] ?? 0.42);
        $wCorner = (float) ($w['corner_coverage'] ?? 0.24);
        $wCenter = (float) ($w['center_crossing'] ?? 0.16);
        $wArea = (float) ($w['coverage_area'] ?? 0.10);
        $wStructure = (float) ($w['structure_bonus'] ?? 0.08);

        $balanceMin = (float) ($cfg['diagonal_balance_min'] ?? 0.34);
        $balanceFactor = $balanceMin > 0 ? clamp01(((float) $f['diag_balance']) / $balanceMin) : 0.0;
        $pair = min((float) $f['diag1'], (float) $f['diag2']) * $balanceFactor;

        $corner = (float) $f['corner_score'];
        $center = (float) $f['center_score'];
        $area = clamp01(((float) $f['ink_ratio']) / 0.14);

        $uniformPenalty = ((float) $f['ink_ratio'] > 0.45)
            ? min(0.6, (((float) $f['ink_ratio'] - 0.45) * 1.6))
            : 0.0;
        $structure = clamp01(($corner * 0.65 + $center * 0.35) * (1.0 - $uniformPenalty));

        $score = $wDiag * $pair
            + $wCorner * $corner
            + $wCenter * $center
            + $wArea * $area
            + $wStructure * $structure;

        $flags = $f['signature'] ?? [];

        // Penalti: garis tunggal / blok tinta / di luar bidang tidak boleh
        // pernah menjadi bukti kuat.
        $weakCap = (float) ($cfg['option_weak'] ?? 0.33);
        $capFlags = ['diagonal_line', 'smudge', 'horizontal_lines', 'vertical_lines', 'center_blob', 'edge_fill', 'many_strokes', 'solid_fill', 'diffuse_strokes'];
        foreach ($capFlags as $flag) {
            if (in_array($flag, $flags, true)) {
                $score = min($score, $weakCap);
                break;
            }
        }
        if (in_array('out_of_bounds', $flags, true)) {
            $score = 0.0;
        }

        $f['score'] = round(clamp01($score), 4);
        $f['pair'] = round($pair, 4);
        $f['area_score'] = round($area, 4);
        $f['structure_score'] = round($structure, 4);

        return $f;
    }

    /**
     * @param  array<int, float>  $offsets
     */
    private function diagonalCoverage(
        GrayImage $img,
        Homography $mmToSource,
        float $x0,
        float $y0,
        float $w,
        float $h,
        int $samples,
        array $offsets,
        float $c,
        bool $antiDiagonal,
    ): float {
        $covered = 0;

        // Vektor satuan diagonal + normalnya.
        $dx = $w;
        $dy = $antiDiagonal ? -$h : $h;
        $len = sqrt($dx * $dx + $dy * $dy);
        if ($len <= 1e-6) {
            return 0.0;
        }
        $ux = $dx / $len;
        $uy = $dy / $len;
        $nx = -$uy;
        $ny = $ux;

        // Diagonal kedua dimulai dari kiri-bawah agar seluruh segmen berada
        // di dalam kotak (anti-diagonal "/").
        $startY = $antiDiagonal ? $y0 + $h : $y0;

        for ($i = 0; $i < $samples; $i++) {
            $t = 0.18 + 0.64 * ($i / max(1, $samples - 1));
            $px = $x0 + $t * $dx;
            $py = $startY + $t * $dy;

            $hit = false;
            foreach ($offsets as $off) {
                if ($this->inkAt($img, $mmToSource, $px + $nx * $off, $py + $ny * $off, $c)) {
                    $hit = true;
                    break;
                }
            }

            if ($hit) {
                $covered++;
            }
        }

        return $covered / max(1, $samples);
    }

    private function cornerScore(
        GrayImage $img,
        Homography $mmToSource,
        float $x0,
        float $y0,
        float $w,
        float $h,
        float $c,
    ): float {
        $cw = $w * 0.26;
        $ch = $h * 0.26;
        $corners = [
            [$x0, $y0],
            [$x0 + $w - $cw, $y0],
            [$x0, $y0 + $h - $ch],
            [$x0 + $w - $cw, $y0 + $h - $ch],
        ];

        $total = 0.0;
        foreach ($corners as [$cx, $cy]) {
            $ratio = $this->rectInkRatio($img, $mmToSource, $cx, $cy, $cw, $ch, self::CORNER_GRID, $c);
            $total += clamp01($ratio / 0.22);
        }

        return $total / 4.0;
    }

    private function rectInkRatio(
        GrayImage $img,
        Homography $mmToSource,
        float $x0,
        float $y0,
        float $w,
        float $h,
        int $grid,
        float $c,
    ): float {
        $ink = 0;
        $n = 0;

        for ($gy = 0; $gy < $grid; $gy++) {
            $v = ($gy + 0.5) / $grid;
            for ($gx = 0; $gx < $grid; $gx++) {
                $u = ($gx + 0.5) / $grid;
                $n++;
                if ($this->inkAt($img, $mmToSource, $x0 + $u * $w, $y0 + $v * $h, $c)) {
                    $ink++;
                }
            }
        }

        return $n > 0 ? $ink / $n : 0.0;
    }

    /**
     * Jumlah maksimum run tinta pada 3 garis tengah (horizontal/vertikal).
     *
     * Run = rangkaian piksel tinta berurutan. Dipakai membedakan tanda X
     * (2 coretan) dari coretan banyak garis atau blok tinta.
     */
    private function maxRunCount(
        GrayImage $img,
        Homography $mmToSource,
        float $x0,
        float $y0,
        float $w,
        float $h,
        float $c,
        bool $horizontal,
    ): int {
        $maxRuns = 0;

        foreach ([0.25, 0.5, 0.75] as $fraction) {
            $samples = 40;
            $prevInk = false;
            $runs = 0;

            for ($i = 0; $i < $samples; $i++) {
                $t = ($i + 0.5) / $samples;

                if ($horizontal) {
                    $mx = $x0 + $t * $w;
                    $my = $y0 + $fraction * $h;
                } else {
                    $mx = $x0 + $fraction * $w;
                    $my = $y0 + $t * $h;
                }

                $ink = $this->inkAt($img, $mmToSource, $mx, $my, $c);
                if ($ink && ! $prevInk) {
                    $runs++;
                }
                $prevInk = $ink;
            }

            $maxRuns = max($maxRuns, $runs);
        }

        return $maxRuns;
    }

    private function inkAt(GrayImage $img, Homography $mmToSource, float $mx, float $my, float $c): bool
    {
        [$sx, $sy] = $mmToSource->map($mx, $my);

        if ($sx < 0 || $sy < 0 || $sx >= $img->width || $sy >= $img->height) {
            return false;
        }

        return $img->isInkAt($sx, $sy, $c);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function colorAt(GrayImage $img, Homography $mmToSource, float $mx, float $my): array
    {
        [$sx, $sy] = $mmToSource->map($mx, $my);

        return $img->colorAt($sx, $sy);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(bool $outOfBounds): array
    {
        return [
            'ink_ratio' => 0.0,
            'diag1' => 0.0,
            'diag2' => 0.0,
            'diag_balance' => 0.0,
            'corner_score' => 0.0,
            'center_score' => 0.0,
            'h_band' => 0.0,
            'v_band' => 0.0,
            'uniformity' => 0.0,
            'ink_blue_ratio' => null,
            'ink_pixels' => 0,
            'sample_count' => 0,
            'outside_ratio' => $outOfBounds ? 1.0 : 0.0,
            'signature' => $outOfBounds ? ['out_of_bounds'] : [],
            'score' => 0.0,
        ];
    }
}
