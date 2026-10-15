<?php

declare(strict_types=1);

namespace App\Services\Ljk\Omr;

use Throwable;

/**
 * Mesin OMR deterministik untuk LJK tanda silang (X).
 *
 * Alur: muat gambar → gerbang kualitas → deteksi penanda → koreksi
 * perspektif (homografi) → dekode data strip → analisis setiap kotak
 * jawaban → klasifikasi per nomor soal.
 *
 * Tidak memakai AI generatif. Semua keputusan berasal dari pengukuran
 * citra yang dapat diaudit dan dikalibrasi ulang dengan dataset berlabel.
 */
final class OmrEngine
{
    public function __construct(
        private readonly FiducialDetector $detector = new FiducialDetector,
        private readonly BoxAnalyzer $analyzer = new BoxAnalyzer,
    ) {}

    /**
     * @param  array<string, mixed>  $geometry
     * @param  array{page?: int|null}  $options
     * @return array<string, mixed>
     */
    public function process(string $imagePath, array $geometry, array $options = []): array
    {
        $started = microtime(true);
        $cfg = config('ljk');
        $engineVersion = (string) ($cfg['engine_version'] ?? 'omr');

        try {
            $loaded = ImageTools::load($imagePath, (int) ($cfg['process']['max_edge_px'] ?? 1800));
        } catch (Throwable $e) {
            return $this->failure('image_unreadable', $e->getMessage(), $started, $engineVersion);
        }

        $img = new GrayImage(
            $loaded['gd'],
            $loaded['width'],
            $loaded['height'],
            (float) ($cfg['detection']['adaptive_window_ratio'] ?? 0.035),
        );

        $quality = [
            'width' => $loaded['width'],
            'height' => $loaded['height'],
            'scaled' => $loaded['scaled'],
            'sharpness' => round($img->sharpness(), 2),
            'brightness' => round($img->brightness(), 2),
            'contrast' => round($img->contrast(), 2),
        ];

        $q = $cfg['quality'] ?? [];

        // Urutan gerbang: pencahayaan -> kontras -> ketajaman. Pada foto yang
        // sangat gelap/terang, pengukuran ketajaman tidak bermakna, sehingga
        // pesan yang dilaporkan harus soal pencahayaan lebih dahulu.
        if ($quality['brightness'] < (float) ($q['min_brightness'] ?? 42.0)) {
            return $this->failure('brightness_too_low', 'Gambar terlalu gelap — tambah pencahayaan.', $started, $engineVersion, $quality);
        }

        if ($quality['brightness'] > (float) ($q['max_brightness'] ?? 245.0)) {
            return $this->failure('brightness_too_high', 'Gambar terlalu terang — kurangi pantulan cahaya.', $started, $engineVersion, $quality);
        }

        if ($quality['contrast'] < (float) ($q['min_contrast'] ?? 18.0)) {
            return $this->failure('contrast_too_low', 'Kontras gambar terlalu rendah — perbaiki pencahayaan.', $started, $engineVersion, $quality);
        }

        if ($quality['sharpness'] < (float) ($q['min_sharpness'] ?? 55.0)) {
            return $this->failure('blur_too_high', 'Gambar buram — ambil ulang dengan fokus yang lebih baik.', $started, $engineVersion, $quality);
        }

        // Perkiraan skala mm→px dari asumsi lebar kertas; toleransi bentuk
        // penanda cukup lebar sehingga perkiraan kasar memadai.
        $paper = $geometry['paper'] ?? ['width_mm' => 210.0, 'height_mm' => 297.0];
        $longMm = max((float) $paper['width_mm'], (float) $paper['height_mm']);
        $pxPerMmGuess = max($loaded['width'], $loaded['height']) / max(1.0, $longMm);

        $page1 = $geometry['pages'][0] ?? null;
        if ($page1 === null) {
            return $this->failure('template_invalid', 'Template tidak memiliki halaman.', $started, $engineVersion, $quality);
        }

        $fiducialIds = [];
        foreach ($page1['fiducials'] as $f) {
            $fiducialIds[(string) $f['id']] = $f;
        }

        $detection = $this->detector->detect($img, $pxPerMmGuess, [
            'size_mm' => (float) ($page1['fiducials'][0]['size_mm'] ?? 6.0),
            'paper_width_mm' => (float) $paper['width_mm'],
            'paper_height_mm' => (float) $paper['height_mm'],
        ]);

        $quality['markers_found'] = count($detection['markers']);
        $quality['quad_area_ratio'] = round((float) $detection['quad_area_ratio'], 4);
        $quality['perspective'] = $detection['perspective'];
        $quality['warnings'] = $detection['warnings'];

        if (count($detection['markers']) !== 4) {
            return $this->failure('markers_not_found', 'Penanda lembar tidak ditemukan — pastikan seluruh sudut lembar masuk ke dalam foto.', $started, $engineVersion, $quality);
        }

        if ((float) $detection['quad_area_ratio'] < (float) ($q['min_paper_area_ratio'] ?? 0.28)) {
            return $this->failure('paper_too_small', 'Lembar tampak terlalu kecil/terpotong — dekatkan kamera dan ambil seluruh lembar.', $started, $engineVersion, $quality);
        }

        $perspective = $detection['perspective'];
        if ((float) $perspective['top_bottom_ratio'] > 1.55 || (float) $perspective['left_right_ratio'] > 1.55) {
            return $this->failure('perspective_too_extreme', 'Sudut pengambilan terlalu miring — foto lebih tegak dari atas lembar.', $started, $engineVersion, $quality);
        }

        if ((float) $perspective['max_angle_deviation'] > 26.0) {
            return $this->failure('perspective_too_extreme', 'Bentuk lembar tidak wajar — ratakan lembar lalu ambil ulang.', $started, $engineVersion, $quality);
        }

        try {
            [$mmToSource, $sourceToMm] = $this->buildHomographies($detection['markers'], $fiducialIds);
        } catch (Throwable $e) {
            return $this->failure('homography_failed', $e->getMessage(), $started, $engineVersion, $quality);
        }

        // ── Dekode data strip + pemilihan halaman ────────────────────────
        $strip = $this->decodeStrip($img, $mmToSource, $page1);
        $quality['strip'] = $strip;

        if ($strip === null || ($strip['checksum_valid'] ?? false) === false) {
            // Coba koreksi cermin: urutan penanda alternatif (TL↔TR, BL↔BR).
            try {
                [$mirrorMm, ] = $this->buildHomographies($detection['markers'], $fiducialIds, true);
                $mirrorStrip = $this->decodeStrip($img, $mirrorMm, $page1);

                if ($mirrorStrip !== null && ($mirrorStrip['checksum_valid'] ?? false)) {
                    $mmToSource = $mirrorMm;
                    $strip = $mirrorStrip;
                    $quality['strip'] = $strip;
                    $quality['mirrored'] = true;
                }
            } catch (Throwable) {
                // abaikan — tetap pakai orientasi awal
            }
        }

        $requestedPage = $options['page'] ?? null;
        $pageNumber = $requestedPage ?: (($strip['checksum_valid'] ?? false) ? (int) $strip['page'] : 1);

        $page = null;
        foreach ($geometry['pages'] as $p) {
            if ((int) $p['index'] === (int) $pageNumber) {
                $page = $p;
                break;
            }
        }
        $page ??= $page1;
        $quality['page'] = (int) $page['index'];

        // ── Analisis kotak jawaban ───────────────────────────────────────
        $detectionCfg = $cfg['detection'] ?? [];
        $questions = [];

        foreach ($page['questions'] as $question) {
            $questions[] = $this->analyzeQuestion($img, $mmToSource, $question, $detectionCfg);
        }

        $counts = array_fill_keys($this->statusKeys(), 0);
        foreach ($questions as $question) {
            $counts[$question['status']] = ($counts[$question['status']] ?? 0) + 1;
        }

        return [
            'ok' => true,
            'error_code' => null,
            'error_message' => null,
            'quality' => $quality,
            'page' => (int) $page['index'],
            'strip' => $strip,
            'questions' => $questions,
            'counts' => $counts,
            'engine_version' => $engineVersion,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ];
    }

    /**
     * @param  array<string, array{0: float, 1: float}>  $markers
     * @param  array<string, array<string, mixed>>  $fiducials
     * @return array{0: Homography, 1: Homography}
     */
    private function buildHomographies(array $markers, array $fiducials, bool $mirror = false): array
    {
        $order = $mirror
            ? ['TL', 'TR', 'BR', 'BL']
            : ['TL', 'TR', 'BR', 'BL'];

        $mirrorMap = [
            'TL' => 'TL',
            'TR' => $mirror ? 'BL' : 'TR',
            'BR' => 'BR',
            'BL' => $mirror ? 'TR' : 'BL',
        ];

        $src = [];
        $dst = [];

        foreach ($order as $localId) {
            $sourceId = $mirrorMap[$localId];
            if (! isset($markers[$sourceId], $fiducials[$localId])) {
                throw new \RuntimeException('Penanda '.$localId.' tidak tersedia.');
            }

            $marker = $markers[$sourceId];
            $fid = $fiducials[$localId];
            $half = ((float) $fid['size_mm']) / 2.0;

            $src[] = [(float) $marker[0], (float) $marker[1]];
            $dst[] = [(float) $fid['x_mm'] + $half, (float) $fid['y_mm'] + $half];
        }

        $sourceToMm = Homography::fromQuads($src, $dst);
        $mmToSource = Homography::fromQuads($dst, $src);

        return [$mmToSource, $sourceToMm];
    }

    /**
     * Dekode data strip 32 sel → {version, participant_seq, page, checksum}.
     *
     * @param  array<string, mixed>  $page
     * @return array{version:int, participant_seq:int, page:int, checksum:int, checksum_valid:bool, bits:array<int,int>}|null
     */
    public function decodeStrip(GrayImage $img, Homography $mmToSource, array $page): ?array
    {
        $strip = $page['strip'] ?? null;
        if (! is_array($strip)) {
            return null;
        }

        $count = (int) ($strip['count'] ?? 32);
        $cell = (float) ($strip['cell_mm'] ?? 2.0);
        $x0 = (float) $strip['x_mm'];
        $y0 = (float) $strip['y_mm'];

        $bits = [];
        for ($i = 0; $i < $count; $i++) {
            $cx = $x0 + ($i + 0.5) * $cell;
            $cy = $y0 + $cell / 2.0;

            // Sampel 3x3 di tengah sel; mayoritas gelap → bit 1.
            $ink = 0;
            $samples = 0;
            foreach ([-0.28, 0.0, 0.28] as $du) {
                foreach ([-0.28, 0.0, 0.28] as $dv) {
                    $samples++;
                    if ($this->inkAtMm($img, $mmToSource, $cx + $du * $cell, $cy + $dv * $cell, 8.0)) {
                        $ink++;
                    }
                }
            }
            $bits[$i] = $ink >= 5 ? 1 : 0;
        }

        $decoded = StripCodec::decodeBits($bits);

        if ($decoded === null) {
            return null;
        }

        return [
            'version' => $decoded['version'],
            'participant_seq' => $decoded['participant_seq'],
            'page' => $decoded['page'],
            'checksum' => $decoded['page'],
            'checksum_valid' => $decoded['checksum_valid'],
            'bits' => $bits,
        ];
    }

    private function inkAtMm(GrayImage $img, Homography $mmToSource, float $mx, float $my, float $c): bool
    {
        [$sx, $sy] = $mmToSource->map($mx, $my);

        if ($sx < 0 || $sy < 0 || $sx >= $img->width || $sy >= $img->height) {
            return false;
        }

        return $img->isInkAt($sx, $sy, $c);
    }

    /**
     * @param  array<string, mixed>  $question
     * @param  array<string, mixed>  $cfg
     * @return array<string, mixed>
     */
    private function analyzeQuestion(GrayImage $img, Homography $mmToSource, array $question, array $cfg): array
    {
        $base = [
            'nomor' => (int) $question['nomor'],
            'page' => (int) ($question['page'] ?? 1),
            'jenis' => (string) ($question['type'] ?? 'pg'),
            'manual' => (bool) ($question['manual'] ?? false),
            'opsi' => [],
            'status' => 'meragukan',
            'selected' => null,
            'selected_set' => [],
            'confidence' => 0.0,
            'evidence' => [],
            'needs_review' => true,
        ];

        if ($base['manual']) {
            $base['status'] = 'manual';

            return $base;
        }

        $strong = (float) ($cfg['option_strong'] ?? 0.56);
        $weak = (float) ($cfg['option_weak'] ?? 0.33);
        $singleMin = (float) ($cfg['single_candidate_min'] ?? 0.47);
        $margin = (float) ($cfg['strong_margin'] ?? 0.12);
        $emptyMax = (float) ($cfg['empty_max'] ?? 0.28);
        $inkMin = (float) ($cfg['ink_min_ratio'] ?? 0.015);

        $optionScores = [];
        $outOfBounds = 0;
        $hasInk = false;

        foreach ($question['options'] as $option) {
            $features = $this->analyzer->analyze($img, $mmToSource, [
                'x_mm' => (float) $option['x_mm'],
                'y_mm' => (float) $option['y_mm'],
                'w_mm' => (float) $option['w_mm'],
                'h_mm' => (float) $option['h_mm'],
            ], $cfg);

            $features = $this->analyzer->score($features, $cfg);

            if (in_array('out_of_bounds', $features['signature'] ?? [], true)) {
                $outOfBounds++;
            }
            if (($features['ink_ratio'] ?? 0) >= $inkMin) {
                $hasInk = true;
            }

            $optionScores[(string) $option['label']] = $features;
        }

        if ($outOfBounds === count($question['options']) && $outOfBounds > 0) {
            $base['status'] = 'tidak_dapat_diproses';
            $base['evidence'] = ['reason' => 'answer_area_out_of_bounds'];

            return $base;
        }

        // Urutkan skor menurun.
        $sorted = $optionScores;
        uasort($sorted, static fn (array $a, array $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        $strongLabels = [];
        $weakLabels = [];
        foreach ($sorted as $label => $features) {
            $score = (float) ($features['score'] ?? 0);
            if ($score >= $strong) {
                $strongLabels[] = (string) $label;
            } elseif ($score >= $weak) {
                $weakLabels[] = (string) $label;
            }
        }

        $top = $sorted !== [] ? array_key_first($sorted) : null;
        $topScore = $top !== null ? (float) ($sorted[$top]['score'] ?? 0) : 0.0;
        $secondScore = 0.0;
        if (count($sorted) > 1) {
            $values = array_values($sorted);
            $secondScore = (float) ($values[1]['score'] ?? 0);
        }

        $base['confidence'] = round($topScore, 4);
        $base['opsi'] = $sorted;

        if (count($strongLabels) === 1) {
            $base['status'] = 'terbaca';
            $base['selected'] = $strongLabels[0];
            $base['selected_set'] = $strongLabels;
            $base['needs_review'] = false;
        } elseif (count($strongLabels) > 1) {
            if (($topScore - $secondScore) >= $margin) {
                $base['status'] = 'terbaca';
                $base['selected'] = (string) $top;
                $base['selected_set'] = [(string) $top];
                $base['needs_review'] = false;
            } else {
                $base['status'] = 'ganda';
                $base['selected_set'] = $strongLabels;
            }
        } else {
            if (count($weakLabels) === 1) {
                // Kandidat tunggal yang mendekati ambang kuat dan tanpa tanda
                // anomali (satu-satunya tinta di kotak) → diterima terbaca.
                $singleMin = (float) ($cfg['single_candidate_min'] ?? 0.47);
                $winnerLabel = $weakLabels[0];
                $winner = $sorted[$winnerLabel] ?? [];
                $winnerScore = (float) ($winner['score'] ?? 0);
                $rivalMax = 0.0;
                foreach ($sorted as $label => $features) {
                    if ((string) $label === $winnerLabel) {
                        continue;
                    }
                    $rivalMax = max($rivalMax, (float) ($features['score'] ?? 0));
                }

                $anomalous = in_array('diagonal_line', $winner['signature'] ?? [], true)
                    || in_array('smudge', $winner['signature'] ?? [], true)
                    || in_array('edge_fill', $winner['signature'] ?? [], true)
                    || in_array('many_strokes', $winner['signature'] ?? [], true)
                    || in_array('solid_fill', $winner['signature'] ?? [], true)
                    || in_array('center_blob', $winner['signature'] ?? [], true)
                    || in_array('diffuse_strokes', $winner['signature'] ?? [], true);

                // Struktur X harus benar-benar terbentuk: kedua diagonal hadir,
                // sudut dan pusat berisi — bukan pola acak yang kebetulan skornya cukup.
                // X asli selalu memiliki tinta di titik temu (center tinggi).
                $structX = min((float) ($winner['diag1'] ?? 0), (float) ($winner['diag2'] ?? 0)) >= 0.35
                    && (float) ($winner['center_score'] ?? 0) >= 0.7
                    && (float) ($winner['ink_ratio'] ?? 1) <= 0.35;

                if (! $anomalous && $structX && $winnerScore >= $singleMin && $rivalMax < 0.28) {
                    $base['status'] = 'terbaca';
                    $base['selected'] = $winnerLabel;
                    $base['selected_set'] = [$winnerLabel];
                    $base['needs_review'] = false;
                    $topScore = max($topScore, $winnerScore);
                    $base['confidence'] = round($winnerScore, 4);
                } else {
                    $base['status'] = 'meragukan';
                    $base['selected_set'] = $weakLabels;
                }
            } elseif (count($weakLabels) > 1) {
                $base['status'] = 'ganda';
                $base['selected_set'] = $weakLabels;
            } else {
                $anyInk = false;
                foreach ($optionScores as $features) {
                    if (($features['ink_ratio'] ?? 0) >= $inkMin) {
                        $anyInk = true;
                        break;
                    }
                }
                $base['status'] = $anyInk ? 'meragukan' : 'kosong';
                if (! $anyInk && $topScore <= $emptyMax) {
                    $base['needs_review'] = false;
                }
            }
        }

        // Ringkasan bukti ringkas (bukan seluruh fitur) untuk audit cepat.
        $base['evidence'] = [
            'top' => $top,
            'top_score' => round($topScore, 4),
            'second_score' => round($secondScore, 4),
            'strong' => $strongLabels,
            'weak' => $weakLabels,
            'has_ink' => $hasInk,
            'out_of_bounds_boxes' => $outOfBounds,
        ];

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function failure(string $code, string $message, float $started, string $engineVersion, array $quality = []): array
    {
        return [
            'ok' => false,
            'error_code' => $code,
            'error_message' => $message,
            'quality' => $quality,
            'page' => null,
            'strip' => null,
            'questions' => [],
            'counts' => array_fill_keys($this->statusKeys(), 0),
            'engine_version' => $engineVersion,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function statusKeys(): array
    {
        return ['terbaca', 'kosong', 'ganda', 'meragukan', 'tidak_dapat_diproses', 'manual'];
    }
}
