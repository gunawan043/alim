<?php

declare(strict_types=1);

namespace App\Services\Ljk\Omr;

use SplFixedArray;

/**
 * Deteksi empat penanda geometris (kotak hitam) di sudut lembar.
 *
 * Tahan terhadap rotasi ringan (±45°), skala, dan bayangan karena:
 * 1. pencarian blob pada resolusi kasar dengan threshold Otsu,
 * 2. penyaringan bentuk (persegi, luas wajar, tidak menyentuh tepi),
 * 3. penghalusan centroid pada resolusi penuh.
 */
final class FiducialDetector
{
    private const COARSE_EDGE = 420;

    /**
     * @param  array<string, float>  $expectedSizeMm
     * @return array{
     *     markers: array<string, array{0: float, 1: float}>,
     *     quad_area_ratio: float,
     *     perspective: array{top_bottom_ratio: float, left_right_ratio: float, max_angle_deviation: float},
     *     expected_size_px: float,
     *     warnings: array<int, string>
     * }
     */
    public function detect(GrayImage $img, float $pxPerMm, array $fiducials): array
    {
        $expectedSizePx = max(4.0, ($fiducials['size_mm'] ?? 6.0) * $pxPerMm);
        $coarse = $this->coarseCandidates($img, $expectedSizePx);

        if (count($coarse) < 4) {
            return $this->fail('Penanda geometris tidak lengkap pada gambar.');
        }

        $picked = $this->pickCornerQuad($coarse, $img->width, $img->height, $fiducials);

        if ($picked === null) {
            return $this->fail('Susunan penanda geometris tidak wajar (lembar terpotong?).');
        }

        // Haluskan centroid pada resolusi penuh.
        $markers = [];
        $warnings = [];
        foreach ($picked as $id => $candidate) {
            $refined = $this->refineCentroid($img, $candidate['x'], $candidate['y'], $expectedSizePx);

            if ($refined === null) {
                $markers[$id] = [$candidate['x'], $candidate['y']];
                $warnings[] = "Penanda {$id} hanya terdeteksi kasar.";
            } else {
                $markers[$id] = $refined;
            }
        }

        $metrics = $this->quadMetrics($markers, $img->width, $img->height);

        return [
            'markers' => $markers,
            'quad_area_ratio' => $metrics['area_ratio'],
            'perspective' => $metrics['perspective'],
            'expected_size_px' => $expectedSizePx,
            'warnings' => $warnings,
        ];
    }

    /**
     * Cari blob persegi pada resolusi kasar.
     *
     * @return array<int, array{x: float, y: float, area: float, size: float, fill: float, coarse_scale: float}>
     */
    private function coarseCandidates(GrayImage $img, float $expectedSizePx): array
    {
        $longEdge = max($img->width, $img->height);
        $scale = self::COARSE_EDGE / $longEdge;
        $cw = max(16, (int) round($img->width * $scale));
        $ch = max(16, (int) round($img->height * $scale));
        $stepX = $img->width / $cw;
        $stepY = $img->height / $ch;

        $small = new SplFixedArray($cw * $ch);

        for ($y = 0; $y < $ch; $y++) {
            for ($x = 0; $x < $cw; $x++) {
                $small[$y * $cw + $x] = (int) round($img->bilinear($x * $stepX, $y * $stepY));
            }
        }

        // Binarisasi adaptif (integral image) agar bayangan/gradien
        // pencahayaan tidak menelan penanda. Otsu hanya menjadi cadangan.
        $iw = $cw + 1;
        $integral = new SplFixedArray(($cw + 1) * ($ch + 1));
        for ($y = 1; $y <= $ch; $y++) {
            $rowSum = 0;
            for ($x = 1; $x <= $cw; $x++) {
                $rowSum += $small[($y - 1) * $cw + ($x - 1)];
                $integral[$y * $iw + $x] = $rowSum + $integral[($y - 1) * $iw + $x];
            }
        }

        $window = max(5, (int) round(0.09 * min($cw, $ch)));
        $half = intdiv($window, 2);
        $binary = new SplFixedArray($cw * $ch);

        for ($y = 0; $y < $ch; $y++) {
            $y0 = max(0, $y - $half);
            $y1 = min($ch, $y + $half + 1);
            for ($x = 0; $x < $cw; $x++) {
                $x0 = max(0, $x - $half);
                $x1 = min($cw, $x + $half + 1);
                $sum = $integral[$y1 * $iw + $x1] - $integral[$y0 * $iw + $x1]
                    - $integral[$y1 * $iw + $x0] + $integral[$y0 * $iw + $x0];
                $area = max(1, ($x1 - $x0) * ($y1 - $y0));
                $mean = $sum / $area;
                $v = $small[$y * $cw + $x];

                // Ambang hibrida: absolut + relatif terhadap rerata lokal.
                // Komponen relatif membuat deteksi tahan terhadap bayangan
                // dan gradien pencahayaan (area gelap tidak menjadi satu blob).
                // Tidak ada fallback threshold global — ambang global membuat
                // gradien bayangan ikut terdeteksi sebagai "gelap".
                $cut = max(22.0, $mean * 0.28);
                $binary[$y * $cw + $x] = $v < ($mean - $cut) ? 1 : 0;
            }
        }

        $expectedSmall = max(3.0, $expectedSizePx * $scale);

        // Filter kontras lokal: penanda adalah persegi gelap yang DIKELILINGI
        // area terang. Area gelap luas (bayangan/meja gelap) tidak memiliki
        // cincin terang sehingga tidak lolos — mencegah penanda "tenggelam"
        // di dalam blob bayangan.
        $binary = $this->filterByLocalContrast($binary, $cw, $ch, $expectedSmall);

        $minArea = max(6.0, 0.30 * $expectedSmall * $expectedSmall);
        $maxArea = max(24.0, 4.0 * $expectedSmall * $expectedSmall);

        return $this->components($binary, $cw, $ch, $minArea, $maxArea, 1.0 / $scale, $expectedSmall);
    }

    /**
     * Pertahankan hanya piksel gelap yang memiliki cincin terang di
     * sekelilingnya (ciri persegi penanda di atas kertas).
     *
     * @param  SplFixedArray<int, int>  $binary
     * @return SplFixedArray<int, int>
     */
    private function filterByLocalContrast(SplFixedArray $binary, int $w, int $h, float $markerSize): SplFixedArray
    {
        $rIn = max(2, (int) round($markerSize * 0.6));
        $rOut = max($rIn + 2, (int) round($markerSize * 1.9));

        $iw = $w + 1;
        $integral = new SplFixedArray(($w + 1) * ($h + 1));
        for ($y = 1; $y <= $h; $y++) {
            $rowSum = 0;
            for ($x = 1; $x <= $w; $x++) {
                $rowSum += $binary[($y - 1) * $w + ($x - 1)];
                $integral[$y * $iw + $x] = $rowSum + $integral[($y - 1) * $iw + $x];
            }
        }

        $filtered = new SplFixedArray($w * $h);
        for ($i = 0; $i < $w * $h; $i++) {
            $filtered[$i] = 0;
        }

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if ($binary[$y * $w + $x] !== 1) {
                    continue;
                }

                $x0o = max(0, $x - $rOut);
                $y0o = max(0, $y - $rOut);
                $x1o = min($w, $x + $rOut + 1);
                $y1o = min($h, $y + $rOut + 1);

                $x0i = max(0, $x - $rIn);
                $y0i = max(0, $y - $rIn);
                $x1i = min($w, $x + $rIn + 1);
                $y1i = min($h, $y + $rIn + 1);

                $outer = $integral[$y1o * $iw + $x1o] - $integral[$y0o * $iw + $x1o]
                    - $integral[$y1o * $iw + $x0o] + $integral[$y0o * $iw + $x0o];
                $inner = $integral[$y1i * $iw + $x1i] - $integral[$y0i * $iw + $x1i]
                    - $integral[$y1i * $iw + $x0i] + $integral[$y0i * $iw + $x0i];

                $ringArea = max(1, ($x1o - $x0o) * ($y1o - $y0o) - ($x1i - $x0i) * ($y1i - $y0i));
                $ringDark = max(0, $outer - $inner);
                $brightRatio = 1.0 - ($ringDark / $ringArea);

                if ($brightRatio >= 0.40) {
                    $filtered[$y * $w + $x] = 1;
                }
            }
        }

        return $filtered;
    }

    /**
     * Connected components (4-arah) pada peta biner.
     *
     * @return array<int, array{x: float, y: float, area: float, size: float, fill: float, coarse_scale: float}>
     */
    private function components(SplFixedArray $binary, int $w, int $h, float $minArea, float $maxArea, float $toFullScale, float $expectedSizeSmall = 0.0): array
    {
        $visited = new SplFixedArray($w * $h);
        for ($i = 0; $i < $w * $h; $i++) {
            $visited[$i] = 0;
        }

        $out = [];

        for ($sy = 0; $sy < $h; $sy++) {
            for ($sx = 0; $sx < $w; $sx++) {
                $start = $sy * $w + $sx;
                if ($visited[$start] || $binary[$start] === 0) {
                    continue;
                }

                // BFS
                $queue = [$start];
                $visited[$start] = 1;
                $head = 0;
                $count = 0;
                $sumX = 0;
                $sumY = 0;
                $minX = $sx;
                $maxX = $sx;
                $minY = $sy;
                $maxY = $sy;
                $touchesBorder = false;

                while ($head < count($queue)) {
                    $idx = $queue[$head++];
                    $cy = intdiv($idx, $w);
                    $cx = $idx - $cy * $w;

                    $count++;
                    $sumX += $cx;
                    $sumY += $cy;
                    $minX = min($minX, $cx);
                    $maxX = max($maxX, $cx);
                    $minY = min($minY, $cy);
                    $maxY = max($maxY, $cy);

                    if ($cx === 0 || $cy === 0 || $cx === $w - 1 || $cy === $h - 1) {
                        $touchesBorder = true;
                    }

                    foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
                        $nx = $cx + $dx;
                        $ny = $cy + $dy;
                        if ($nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h) {
                            continue;
                        }
                        $nIdx = $ny * $w + $nx;
                        if (! $visited[$nIdx] && $binary[$nIdx] === 1) {
                            $visited[$nIdx] = 1;
                            $queue[] = $nIdx;
                        }
                    }
                }

                if ($count < $minArea || $count > $maxArea) {
                    continue;
                }

                $bw = $maxX - $minX + 1;
                $bh = $maxY - $minY + 1;
                $aspect = $bw / max(1, $bh);
                if ($aspect < 0.45 || $aspect > 2.2) {
                    continue;
                }

                // Ukuran harus dekat dengan ukuran penanda yang diharapkan.
                if ($expectedSizeSmall > 0.0) {
                    $sizeSmall = ($bw + $bh) / 2.0;
                    if ($sizeSmall < $expectedSizeSmall * 0.55 || $sizeSmall > $expectedSizeSmall * 1.7) {
                        continue;
                    }
                }

                $fill = $count / max(1, $bw * $bh);
                if ($fill < 0.55) {
                    continue;
                }

                if ($touchesBorder) {
                    continue; // tepi gambar terpotong / bukan penanda utuh
                }

                $out[] = [
                    'x' => ($sumX / $count) * $toFullScale,
                    'y' => ($sumY / $count) * $toFullScale,
                    'area' => (float) $count * $toFullScale * $toFullScale,
                    'size' => (($bw + $bh) / 2) * $toFullScale,
                    'fill' => $fill,
                    'coarse_scale' => $toFullScale,
                ];
            }
        }

        return $out;
    }

    /**
     * Pilih 4 kandidat yang membentuk segi empat paling mirip halaman LJK.
     *
     * Kriteria bebas skala & rotasi: sudut mendekati 90°, rasio sisi
     * mendekati rasio kertas, ukuran penanda seragam. Ambiguitas cermin
     * (lembar terbalik) diselesaikan nanti lewat checksum data strip.
     *
     * @param  array<int, array{x: float, y: float, area: float, size: float, fill: float, coarse_scale: float}>  $candidates
     * @param  array<string, mixed>  $fiducials
     * @return array<string, array{x: float, y: float}>|null
     */
    private function pickCornerQuad(array $candidates, int $imgW, int $imgH, array $fiducials): ?array
    {
        $paperAspect = ((float) ($fiducials['paper_width_mm'] ?? 210.0))
            / max(1.0, (float) ($fiducials['paper_height_mm'] ?? 297.0));

        // Batasi kombinatorial: ambil kandidat dengan bentuk terbaik.
        usort($candidates, static fn (array $a, array $b) => ($b['fill'] <=> $a['fill']) ?: ($b['area'] <=> $a['area']));
        $candidates = array_slice($candidates, 0, 24);
        $n = count($candidates);

        $best = null;
        $bestCost = INF;

        for ($a = 0; $a < $n; $a++) {
            for ($b = $a + 1; $b < $n; $b++) {
                for ($c = $b + 1; $c < $n; $c++) {
                    for ($d = $c + 1; $d < $n; $d++) {
                        $pts = [$candidates[$a], $candidates[$b], $candidates[$c], $candidates[$d]];
                        $hull = $this->convexHullOrder($pts);

                        if ($hull === null) {
                            continue;
                        }

                        for ($start = 0; $start < 4; $start++) {
                            foreach ([1, -1] as $dir) {
                                $ordered = [];
                                for ($k = 0; $k < 4; $k++) {
                                    $idx = (($start + $dir * $k) % 4 + 4) % 4;
                                    $ordered[] = $hull[$idx];
                                }

                                $cost = $this->assignmentCost($ordered, $paperAspect);

                                if ($cost < $bestCost) {
                                    $bestCost = $cost;
                                    $best = $ordered;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($best === null || $bestCost > 0.6) {
            return null;
        }

        return [
            'TL' => ['x' => $best[0]['x'], 'y' => $best[0]['y']],
            'TR' => ['x' => $best[1]['x'], 'y' => $best[1]['y']],
            'BR' => ['x' => $best[2]['x'], 'y' => $best[2]['y']],
            'BL' => ['x' => $best[3]['x'], 'y' => $best[3]['y']],
        ];
    }

    /**
     * Urutkan 4 titik menjadi convex hull (atau null jika degenerate).
     *
     * @param  array<int, array{x: float, y: float}>  $pts
     * @return array<int, array{x: float, y: float}>|null
     */
    private function convexHullOrder(array $pts): ?array
    {
        $cx = array_sum(array_column($pts, 'x')) / 4.0;
        $cy = array_sum(array_column($pts, 'y')) / 4.0;

        usort($pts, function (array $p, array $q) use ($cx, $cy) {
            $angleP = atan2($p['y'] - $cy, $p['x'] - $cx);
            $angleQ = atan2($q['y'] - $cy, $q['x'] - $cx);

            return $angleP <=> $angleQ;
        });

        // Pastikan tidak ada 3 titik segaris (degenerate).
        for ($i = 0; $i < 4; $i++) {
            $a = $pts[$i];
            $b = $pts[($i + 1) % 4];
            $c = $pts[($i + 2) % 4];
            $cross = ($b['x'] - $a['x']) * ($c['y'] - $b['y']) - ($b['y'] - $a['y']) * ($c['x'] - $b['x']);
            if (abs($cross) < 4.0) {
                return null;
            }
        }

        return $pts;
    }

    /**
     * Biaya kecocokan [TL,TR,BR,BL] terhadap bentuk halaman LJK.
     *
     * @param  array<int, array{x: float, y: float}>  $q
     */
    private function assignmentCost(array $q, float $paperAspect): float
    {
        $points = array_map(static fn (array $p): array => [$p['x'], $p['y']], $q);

        $maxDev = max(
            $this->angleDeviation($points[3], $points[0], $points[1]),
            $this->angleDeviation($points[0], $points[1], $points[2]),
            $this->angleDeviation($points[1], $points[2], $points[3]),
            $this->angleDeviation($points[2], $points[3], $points[0]),
        );

        if ($maxDev > 40.0) {
            return 10.0;
        }

        $top = $this->distance($points[0], $points[1]);
        $bottom = $this->distance($points[3], $points[2]);
        $left = $this->distance($points[0], $points[3]);
        $right = $this->distance($points[1], $points[2]);

        $measured = (($top + $bottom) / 2.0) / max(1e-6, ($left + $right) / 2.0);
        $aspectErr = abs($measured - $paperAspect);

        return $maxDev / 90.0 + $aspectErr * 1.5;
    }

    /**
     * Perhalus centroid pada resolusi penuh dalam jendela di sekitar
     * perkiraan kasar.
     *
     * @return array{0: float, 1: float}|null
     */
    private function refineCentroid(GrayImage $img, float $cx, float $cy, float $expectedSizePx): ?array
    {
        $half = (int) max(6, round($expectedSizePx * 0.9));
        $x0 = max(0, (int) $cx - $half);
        $y0 = max(0, (int) $cy - $half);
        $x1 = min($img->width - 1, (int) $cx + $half);
        $y1 = min($img->height - 1, (int) $cy + $half);

        if ($x1 - $x0 < 3 || $y1 - $y0 < 3) {
            return null;
        }

        // Threshold global jendela (marker hitam pekat).
        $sum = 0.0;
        $n = 0;
        for ($y = $y0; $y <= $y1; $y += 2) {
            for ($x = $x0; $x <= $x1; $x += 2) {
                $sum += $img->grayAt($x, $y);
                $n++;
            }
        }
        $mean = $n > 0 ? $sum / $n : 128.0;
        $threshold = $mean - 28.0;

        // Ambil komponen terhubung yang memuat titik pusat (atau piksel gelap
        // terdekat) agar teks/tanda lain di sekitar jendela tidak menggeser
        // centroid.
        $cxI = (int) round($cx);
        $cyI = (int) round($cy);
        $seed = null;
        $bestDist = PHP_INT_MAX;

        for ($y = max($y0, $cyI - $half); $y <= min($y1, $cyI + $half); $y++) {
            for ($x = max($x0, $cxI - $half); $x <= min($x1, $cxI + $half); $x++) {
                if ($img->grayAt($x, $y) < $threshold) {
                    $dist = ($x - $cxI) ** 2 + ($y - $cyI) ** 2;
                    if ($dist < $bestDist) {
                        $bestDist = $dist;
                        $seed = [$x, $y];
                    }
                }
            }
        }

        if ($seed === null) {
            return null;
        }

        // BFS 8-arah dalam jendela.
        $visited = [];
        $queue = [$seed];
        $head = 0;
        $visited[$seed[1] * ($img->width + 1) + $seed[0]] = true;

        $sumX = 0.0;
        $sumY = 0.0;
        $count = 0;
        $minX = PHP_INT_MAX;
        $maxX = PHP_INT_MIN;
        $minY = PHP_INT_MAX;
        $maxY = PHP_INT_MIN;

        while ($head < count($queue)) {
            [$px, $py] = $queue[$head++];

            $sumX += $px;
            $sumY += $py;
            $count++;
            $minX = min($minX, $px);
            $maxX = max($maxX, $px);
            $minY = min($minY, $py);
            $maxY = max($maxY, $py);

            for ($dy = -1; $dy <= 1; $dy++) {
                for ($dx = -1; $dx <= 1; $dx++) {
                    if ($dx === 0 && $dy === 0) {
                        continue;
                    }
                    $nx = $px + $dx;
                    $ny = $py + $dy;
                    if ($nx < $x0 || $ny < $y0 || $nx > $x1 || $ny > $y1) {
                        continue;
                    }
                    $key = $ny * ($img->width + 1) + $nx;
                    if (isset($visited[$key])) {
                        continue;
                    }
                    if ($img->grayAt($nx, $ny) < $threshold) {
                        $visited[$key] = true;
                        $queue[] = [$nx, $ny];
                    }
                }
            }
        }

        if ($count < 12) {
            return null;
        }

        $bw = $maxX - $minX + 1;
        $bh = $maxY - $minY + 1;
        $size = ($bw + $bh) / 2.0;

        if ($size < $expectedSizePx * 0.45 || $size > $expectedSizePx * 2.2) {
            return null;
        }

        $fill = $count / max(1, $bw * $bh);
        if ($fill < 0.5) {
            return null;
        }

        return [$sumX / $count, $sumY / $count];
    }

    /**
     * @param  array<string, array{0: float, 1: float}>  $markers
     * @return array{area_ratio: float, perspective: array{top_bottom_ratio: float, left_right_ratio: float, max_angle_deviation: float}}
     */
    private function quadMetrics(array $markers, int $imgW, int $imgH): array
    {
        $tl = $markers['TL'];
        $tr = $markers['TR'];
        $br = $markers['BR'];
        $bl = $markers['BL'];

        $area = 0.5 * abs(
            $tl[0] * $tr[1] - $tr[0] * $tl[1]
            + $tr[0] * $br[1] - $br[0] * $tr[1]
            + $br[0] * $bl[1] - $bl[0] * $br[1]
            + $bl[0] * $tl[1] - $tl[0] * $bl[1]
        );

        $topLen = $this->distance($tl, $tr);
        $bottomLen = $this->distance($bl, $br);
        $leftLen = $this->distance($tl, $bl);
        $rightLen = $this->distance($tr, $br);

        $maxAngleDev = max(
            $this->angleDeviation($bl, $tl, $tr),
            $this->angleDeviation($tl, $tr, $br),
            $this->angleDeviation($tr, $br, $bl),
            $this->angleDeviation($br, $bl, $tl),
        );

        return [
            'area_ratio' => $area / max(1.0, $imgW * $imgH),
            'perspective' => [
                'top_bottom_ratio' => $this->safeRatio($topLen, $bottomLen),
                'left_right_ratio' => $this->safeRatio($leftLen, $rightLen),
                'max_angle_deviation' => $maxAngleDev,
            ],
        ];
    }

    /**
     * @param  array{0: float, 1: float}  $a
     * @param  array{0: float, 1: float}  $b
     */
    private function distance(array $a, array $b): float
    {
        return sqrt(($a[0] - $b[0]) ** 2 + ($a[1] - $b[1]) ** 2);
    }

    private function safeRatio(float $a, float $b): float
    {
        $min = min($a, $b);
        $max = max($a, $b);

        return $min <= 0.0 ? 9.99 : round($max / $min, 4);
    }

    /**
     * Deviasi sudut dari 90° pada titik b.
     *
     * @param  array{0: float, 1: float}  $a
     * @param  array{0: float, 1: float}  $b
     * @param  array{0: float, 1: float}  $c
     */
    private function angleDeviation(array $a, array $b, array $c): float
    {
        $v1x = $a[0] - $b[0];
        $v1y = $a[1] - $b[1];
        $v2x = $c[0] - $b[0];
        $v2y = $c[1] - $b[1];

        $dot = $v1x * $v2x + $v1y * $v2y;
        $mag = sqrt($v1x ** 2 + $v1y ** 2) * sqrt($v2x ** 2 + $v2y ** 2);

        if ($mag <= 0.0) {
            return 90.0;
        }

        $angle = rad2deg(acos(max(-1.0, min(1.0, $dot / $mag))));

        return abs(90.0 - $angle);
    }

    /**
     * @param  array<int, int>  $hist
     */
    private function otsu(array $hist, int $total): int
    {
        $sum = 0;
        for ($i = 0; $i < 256; $i++) {
            $sum += $i * $hist[$i];
        }

        $sumB = 0;
        $wB = 0;
        $best = 0;
        $bestVar = -1.0;

        for ($t = 0; $t < 256; $t++) {
            $wB += $hist[$t];
            if ($wB === 0) {
                continue;
            }
            $wF = $total - $wB;
            if ($wF === 0) {
                break;
            }

            $sumB += $t * $hist[$t];
            $mB = $sumB / $wB;
            $mF = ($sum - $sumB) / $wF;
            $between = $wB * $wF * ($mB - $mF) ** 2;

            if ($between > $bestVar) {
                $bestVar = $between;
                $best = $t;
            }
        }

        return max(20, min(200, $best));
    }

    /**
     * @return array{markers: array<string, array{0: float, 1: float}>, quad_area_ratio: float, perspective: array{top_bottom_ratio: float, left_right_ratio: float, max_angle_deviation: float}, expected_size_px: float, warnings: array<int, string>}
     */
    private function fail(string $reason): array
    {
        return [
            'markers' => [],
            'quad_area_ratio' => 0.0,
            'perspective' => ['top_bottom_ratio' => 0.0, 'left_right_ratio' => 0.0, 'max_angle_deviation' => 0.0],
            'expected_size_px' => 0.0,
            'warnings' => [$reason],
        ];
    }
}
