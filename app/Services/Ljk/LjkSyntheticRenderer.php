<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use GdImage;
use RuntimeException;

/**
 * Perender LJK sintetis berbasis GD.
 *
 * Dipakai untuk:
 *  - pengujian mesin OMR tanpa memerlukan pemindaian fisik,
 *  - membangun dataset berlabel (ljk:generate-dataset),
 *  - uji cetak cepat sebelum tata letak final.
 *
 * Menghasilkan gambar "lembar tercetak + isian" sesuai geometri template,
 * lengkap dengan penanda geometris, data strip, dan distorsi foto
 * (rotasi, perspektif, bayangan, blur, kompresi).
 */
final class LjkSyntheticRenderer
{
    /**
     * @param  array<string, mixed>  $geometry
     * @param  array{answers?: array<int, string>, styles?: array<int, string>, page?: int}  $content
     * @param  array<string, mixed>  $opts
     */
    public function renderToFile(array $geometry, array $content, array $opts = []): string
    {
        $gd = $this->render($geometry, $content, $opts);
        $path = $opts['path'] ?? (sys_get_temp_dir().'/ljk-'.uniqid('', true).'.jpg');
        $quality = (int) ($opts['jpeg_quality'] ?? 86);

        if (! imagejpeg($gd, $path, $quality)) {
            throw new RuntimeException('Gagal menyimpan gambar sintetis.');
        }

        unset($gd); // PHP 8.5: imagedestroy() deprecated — unset cukup untuk melepas GD

        return $path;
    }

    /**
     * @param  array<string, mixed>  $geometry
     * @param  array{answers?: array<int, string>, styles?: array<int, string>, page?: int}  $content
     * @param  array<string, mixed>  $opts
     */
    public function render(array $geometry, array $content, array $opts = []): GdImage
    {
        $scale = (float) ($opts['scale'] ?? 4.6); // piksel per mm pada halaman datar
        $pageIndex = (int) ($content['page'] ?? $opts['page'] ?? 1);

        $page = null;
        foreach ($geometry['pages'] as $p) {
            if ((int) $p['index'] === $pageIndex) {
                $page = $p;
                break;
            }
        }

        if ($page === null) {
            throw new RuntimeException('Halaman template tidak ditemukan.');
        }

        $widthMm = (float) $geometry['paper']['width_mm'];
        $heightMm = (float) $geometry['paper']['height_mm'];
        $pw = (int) round($widthMm * $scale);
        $ph = (int) round($heightMm * $scale);

        $flat = imagecreatetruecolor($pw, $ph);
        $white = imagecolorallocate($flat, 255, 255, 255);
        imagefilledrectangle($flat, 0, 0, $pw, $ph, $white);
        $black = imagecolorallocate($flat, 16, 16, 16);
        $gray = imagecolorallocate($flat, 120, 120, 120);

        $this->drawFiducials($flat, $page['fiducials'], $scale, $black);
        $this->drawStrip($flat, $page['strip'], (int) ($opts['participant_seq'] ?? 0), $pageIndex, $scale, $black);
        $this->drawFakeQr($flat, $page['qr'], $scale, $black, $gray);
        $this->drawHeader($flat, $geometry, $pageIndex, $scale, $black, $gray);
        $this->drawQuestions($flat, $page, $content, $scale, $black, $gray);

        $out = $this->applyPhotoEffects($flat, $opts);
        unset($flat); // PHP 8.5: imagedestroy() deprecated — unset cukup untuk melepas GD

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fiducials
     */
    private function drawFiducials(GdImage $gd, array $fiducials, float $scale, int $color): void
    {
        foreach ($fiducials as $fid) {
            $x0 = (int) round((float) $fid['x_mm'] * $scale);
            $y0 = (int) round((float) $fid['y_mm'] * $scale);
            $size = (int) round((float) $fid['size_mm'] * $scale);
            imagefilledrectangle($gd, $x0, $y0, $x0 + $size, $y0 + $size, $color);
        }
    }

    private function drawStrip(GdImage $gd, array $strip, int $participantSeq, int $page, float $scale, int $color): void
    {
        $cell = (float) $strip['cell_mm'];
        $count = (int) $strip['count'];
        $x0 = (float) $strip['x_mm'] * $scale;
        $y0 = (float) $strip['y_mm'] * $scale;
        $cellPx = $cell * $scale;

        $bits = self::encodeStripBits($participantSeq, $page, $count);

        for ($i = 0; $i < $count; $i++) {
            if (($bits[$i] ?? 0) !== 1) {
                continue;
            }
            $x = (int) round($x0 + $i * $cellPx);
            $y = (int) round($y0);
            imagefilledrectangle($gd, $x, $y, $x + (int) round($cellPx) - 1, $y + (int) round($cellPx) - 1, $color);
        }
    }

    /**
     * Bit data strip: byte0,byte1 = participant_seq (16 bit), byte2 = 0,
     * byte3 = versi(4 bit atas) + halaman(4 bit bawah); checksum XOR.
     *
     * @return array<int, int>
     */
    public static function encodeStripBits(int $participantSeq, int $page, int $count = 32): array
    {
        return \App\Services\Ljk\Omr\StripCodec::encodeBits($participantSeq, $page, $count);
    }

    private function drawFakeQr(GdImage $gd, array $qr, float $scale, int $dark, int $light): void
    {
        $x0 = (int) round((float) $qr['x_mm'] * $scale);
        $y0 = (int) round((float) $qr['y_mm'] * $scale);
        $size = (int) round((float) $qr['size_mm'] * $scale);

        // Pola pseudo-QR deterministik (hanya untuk visual & uji tata letak).
        $n = 21;
        $cell = $size / $n;
        for ($r = 0; $r < $n; $r++) {
            for ($c = 0; $c < $n; $c++) {
                $isFinder = ($r < 7 && $c < 7) || ($r < 7 && $c >= $n - 7) || ($r >= $n - 7 && $c < 7);
                $on = false;
                if ($isFinder) {
                    $rr = $r % 7;
                    $cc = $c % 7;
                    if ($r >= $n - 7) {
                        $rr = $r - ($n - 7);
                    }
                    if ($c >= $n - 7) {
                        $cc = $c - ($n - 7);
                    }
                    $on = ($rr === 0 || $rr === 6 || $cc === 0 || $cc === 6) || ($rr >= 2 && $rr <= 4 && $cc >= 2 && $cc <= 4);
                } else {
                    $on = (($r * 31 + $c * 17 + $r * $c) % 3) === 0;
                }

                if ($on) {
                    imagefilledrectangle(
                        $gd,
                        (int) round($x0 + $c * $cell),
                        (int) round($y0 + $r * $cell),
                        (int) round($x0 + ($c + 1) * $cell) - 1,
                        (int) round($y0 + ($r + 1) * $cell) - 1,
                        $dark
                    );
                }
            }
        }
    }

    private function drawHeader(GdImage $gd, array $geometry, int $page, float $scale, int $dark, int $gray): void
    {
        $margin = (float) ($geometry['paper']['width_mm'] * 0.06);
        $y = 16.0;

        imagestring($gd, 5, (int) ($margin * $scale), (int) ($y * $scale), 'MADRASAH ALIM', $dark);
        imagestring($gd, 3, (int) ($margin * $scale), (int) (($y + 6) * $scale), 'Lembar Jawaban Komputer (LJK) - isi dengan pulpen hitam/biru, tanda silang (X)', $gray);
        imagestring($gd, 3, (int) ($margin * $scale), (int) (($y + 11) * $scale), 'Halaman '.$page.' - Jangan membuat tanda lain di luar kotak', $gray);
        imagestring($gd, 3, (int) ($margin * $scale), (int) (($y + 16) * $scale), 'Nama: ____________________     No. Peserta: __________', $gray);
    }

    /**
     * @param  array{answers?: array<int, string>, styles?: array<int, string>}  $content
     */
    private function drawQuestions(GdImage $gd, array $page, array $content, float $scale, int $dark, int $gray): void
    {
        $answers = $content['answers'] ?? [];
        $styles = $content['styles'] ?? [];

        foreach ($page['questions'] as $question) {
            $nomor = (int) $question['nomor'];
            $x = (float) $question['x_mm'] * $scale;
            $y = (float) $question['y_mm'] * $scale;

            $label = str_pad((string) $nomor, 2, '0', STR_PAD_LEFT).'.';
            imagestring($gd, 3, (int) $x, (int) ($y + 2), $label, $gray);

            foreach ($question['options'] as $option) {
                $bx = (float) $option['x_mm'] * $scale;
                $by = (float) $option['y_mm'] * $scale;
                $bw = (float) $option['w_mm'] * $scale;
                $bh = (float) $option['h_mm'] * $scale;

                // Kotak jawaban: garis 1 px pada skala render.
                imagerectangle($gd, (int) $bx, (int) $by, (int) ($bx + $bw), (int) ($by + $bh), $dark);
                imagestring($gd, 2, (int) ($bx + $bw * 0.2), (int) ($by - 9), (string) $option['label'], $gray);
            }

            if ($question['manual'] ?? false) {
                imagerectangle($gd, (int) $x, (int) ($y + 1), (int) ($x + (float) $question['w_mm'] * $scale), (int) ($y + (float) $question['h_mm'] * $scale), $gray);
            }

            if (! isset($answers[$nomor]) || $answers[$nomor] === '') {
                continue;
            }

            $selected = str_split(strtoupper((string) $answers[$nomor]));
            $style = $styles[$nomor] ?? 'x';

            foreach ($question['options'] as $option) {
                if (! in_array(strtoupper((string) $option['label']), $selected, true)) {
                    continue;
                }

                $this->drawMark($gd, [
                    'x' => (float) $option['x_mm'] * $scale,
                    'y' => (float) $option['y_mm'] * $scale,
                    'w' => (float) $option['w_mm'] * $scale,
                    'h' => (float) $option['h_mm'] * $scale,
                ], $style, $scale);
            }
        }
    }

    /**
     * @param  array{x: float, y: float, w: float, h: float}  $box
     */
    private function drawMark(GdImage $gd, array $box, string $style, float $scale): void
    {
        $thickness = (int) max(2, round(1.6 * $scale / 4.6));
        $x0 = $box['x'];
        $y0 = $box['y'];
        $x1 = $box['x'] + $box['w'];
        $y1 = $box['y'] + $box['h'];

        $blackInk = imagecolorallocate($gd, 22, 22, 22);
        $blueInk = imagecolorallocate($gd, 24, 32, 120);

        $color = str_contains($style, 'blue') ? $blueInk : $blackInk;

        $inset = 1.5;
        $ax0 = $x0 + $inset;
        $ay0 = $y0 + $inset;
        $ax1 = $x1 - $inset;
        $ay1 = $y1 - $inset;

        switch (true) {
            case str_starts_with($style, 'x'):
                imagesetthickness($gd, $thickness);
                imageline($gd, (int) $ax0, (int) $ay0, (int) $ax1, (int) $ay1, $color);
                imageline($gd, (int) $ax1, (int) $ay0, (int) $ax0, (int) $ay1, $color);
                break;

            case str_starts_with($style, 'thin'):
                imagesetthickness($gd, 1);
                imageline($gd, (int) $ax0, (int) $ay0, (int) $ax1, (int) $ay1, $color);
                imageline($gd, (int) $ax1, (int) $ay0, (int) $ax0, (int) $ay1, $color);
                break;

            case str_starts_with($style, 'thick'):
                imagesetthickness($gd, max(3, $thickness * 2));
                imageline($gd, (int) $ax0, (int) $ay0, (int) $ax1, (int) $ay1, $color);
                imageline($gd, (int) $ax1, (int) $ay0, (int) $ax0, (int) $ay1, $color);
                break;

            case str_starts_with($style, 'slanted'):
                imagesetthickness($gd, $thickness);
                imageline($gd, (int) $ax0, (int) ($ay0 + 2), (int) $ax1, (int) ($ay1 - 3), $color);
                imageline($gd, (int) ($ax1 - 1), (int) $ay0, (int) ($ax0 + 2), (int) $ay1, $color);
                break;

            case str_starts_with($style, 'asymmetric'):
                imagesetthickness($gd, $thickness);
                imageline($gd, (int) $ax0, (int) $ay0, (int) ($ax1 - 3), (int) ($ay1 + 1), $color);
                imageline($gd, (int) ($ax1 - 1), (int) ($ay0 + 1), (int) ($ax0 + 3), (int) $ay1, $color);
                break;

            case str_starts_with($style, 'touch_border'):
                // X melewati garis kotak.
                imagesetthickness($gd, $thickness);
                imageline($gd, (int) ($x0 - 2), (int) ($y0 - 2), (int) ($x1 + 2), (int) ($y1 + 2), $color);
                imageline($gd, (int) ($x1 + 2), (int) ($y0 - 2), (int) ($x0 - 2), (int) ($y1 + 2), $color);
                break;

            case str_starts_with($style, 'check'):
                imagesetthickness($gd, $thickness);
                imageline($gd, (int) $ax0, (int) ($y0 + $box['h'] * 0.55), (int) ($x0 + $box['w'] * 0.45), (int) $ay1, $color);
                imageline($gd, (int) ($x0 + $box['w'] * 0.45), (int) $ay1, (int) $ax1, (int) ($ay0 + 1), $color);
                break;

            case str_starts_with($style, 'slash'):
                imagesetthickness($gd, $thickness);
                imageline($gd, (int) ($x0 - 2), (int) ($y1 + 2), (int) ($x1 + 2), (int) ($y0 - 2), $color);
                break;

            case str_starts_with($style, 'horiz'):
                imagesetthickness($gd, $thickness);
                imageline($gd, (int) ($x0 - 2), (int) ($y0 + $box['h'] / 2), (int) ($x1 + 2), (int) ($y0 + $box['h'] / 2), $color);
                break;

            case str_starts_with($style, 'scribble'):
                imagesetthickness($gd, max(1, $thickness - 1));
                for ($i = 0; $i < 6; $i++) {
                    $yy = $y0 + 2 + ($box['h'] - 4) * ($i / 5);
                    imageline($gd, (int) ($x0 - 1), (int) $yy, (int) ($x1 + 1), (int) ($yy + 1.5), $color);
                }
                break;

            case str_starts_with($style, 'erase'):
                // Bekas penghapusan: kabut abu-abu (tinta tersisa sebagian).
                $smudge = imagecolorallocatealpha($gd, 90, 90, 90, 70);
                imagefilledrectangle($gd, (int) $x0, (int) $y0, (int) $x1, (int) $y1, $smudge);
                imagesetthickness($gd, 1);
                imageline($gd, (int) $ax0, (int) $ay0, (int) $ax1, (int) $ay1, imagecolorallocate($gd, 150, 150, 150));
                break;

            case str_starts_with($style, 'dot'):
                imagefilledellipse($gd, (int) (($x0 + $x1) / 2), (int) (($y0 + $y1) / 2), (int) ($box['w'] * 0.35), (int) ($box['h'] * 0.35), $color);
                break;

            default:
                imagesetthickness($gd, $thickness);
                imageline($gd, (int) $ax0, (int) $ay0, (int) $ax1, (int) $ay1, $color);
                imageline($gd, (int) $ax1, (int) $ay0, (int) $ax0, (int) $ay1, $color);
        }

        imagesetthickness($gd, 1);
    }

    /**
     * Efek foto: rotasi, perspektif, bayangan gradien, blur, kompresi.
     *
     * @param  array<string, mixed>  $opts
     */
    private function applyPhotoEffects(GdImage $flat, array $opts): GdImage
    {
        $rotation = (float) ($opts['rotation_deg'] ?? 0.0);
        $perspective = (float) ($opts['perspective'] ?? 0.0);
        $canvasScale = (float) ($opts['canvas_scale'] ?? 1.18);

        $srcW = imagesx($flat);
        $srcH = imagesy($flat);

        $outW = (int) round($srcW * $canvasScale);
        $outH = (int) round($srcH * $canvasScale);
        $canvas = imagecreatetruecolor($outW, $outH);
        // Latar meja (bukan kertas) agar realistis seperti foto HP.
        $white = imagecolorallocate($canvas, 196, 198, 200);
        imagefilledrectangle($canvas, 0, 0, $outW, $outH, $white);

        // Peta sudut halaman pada kanvas (dengan margin + rotasi + perspektif).
        $cx = $outW / 2;
        $cy = $outH / 2;
        $rad = deg2rad($rotation);
        $cosR = cos($rad);
        $sinR = sin($rad);

        $corners = [
            [0.0, 0.0],
            [$srcW, 0.0],
            [$srcW, $srcH],
            [0.0, $srcH],
        ];

        $mapped = [];
        foreach ($corners as $i => [$x, $y]) {
            $dx = $x - $srcW / 2;
            $dy = $y - $srcH / 2;

            // Perspektif: tarik sudut atas keluar/masuk secara proporsional.
            if ($perspective > 0.0) {
                $factor = match ($i) {
                    0 => 1.0 + $perspective * 0.9,
                    1 => 1.0 + $perspective * 0.55,
                    2 => 1.0 - $perspective * 0.35,
                    3 => 1.0 - $perspective * 0.65,
                    default => 1.0,
                };
                $dx *= $factor;
                $dy *= $factor;
            }

            $rx = $dx * $cosR - $dy * $sinR;
            $ry = $dx * $sinR + $dy * $cosR;

            $mapped[] = [$cx + $rx, $cy + $ry];
        }

        // Warp: inverse homografi kanvas → halaman datar.
        $inv = \App\Services\Ljk\Omr\Homography::fromQuads(
            $mapped,
            [[0.0, 0.0], [$srcW, 0.0], [$srcW, $srcH], [0.0, $srcH]]
        );

        $shadow = (float) ($opts['shadow'] ?? 0.0);
        $brightness = (float) ($opts['brightness'] ?? 0.0);

        $minX = max(0, (int) floor(min(array_column($mapped, 0))));
        $maxX = min($outW - 1, (int) ceil(max(array_column($mapped, 0))));
        $minY = max(0, (int) floor(min(array_column($mapped, 1))));
        $maxY = min($outH - 1, (int) ceil(max(array_column($mapped, 1))));

        for ($y = $minY; $y <= $maxY; $y++) {
            for ($x = $minX; $x <= $maxX; $x++) {
                [$sx, $sy] = $inv->map((float) $x, (float) $y);

                if ($sx < 0 || $sy < 0 || $sx >= $srcW || $sy >= $srcH) {
                    continue;
                }

                $rgb = imagecolorat($flat, (int) $sx, (int) $sy);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                if ($shadow > 0.0) {
                    $t = $y / max(1, $outH);
                    $factor = 1.0 - $shadow * $t;
                    $r = (int) ($r * $factor);
                    $g = (int) ($g * $factor);
                    $b = (int) ($b * $factor);
                }

                imagesetpixel($canvas, $x, $y, ($r << 16) | ($g << 8) | $b);
            }
        }

        if ($brightness !== 0.0) {
            imagefilter($canvas, IMG_FILTER_BRIGHTNESS, (int) round($brightness));
        }

        $blur = (int) ($opts['blur'] ?? 0);
        for ($i = 0; $i < $blur; $i++) {
            imagefilter($canvas, IMG_FILTER_GAUSSIAN_BLUR);
        }

        // Simulasi foto terpotong: pangkas tepi kanvas.
        $crop = (float) ($opts['crop'] ?? 0.0);
        if ($crop > 0.0) {
            $cutX = (int) round($outW * $crop);
            $cutY = (int) round($outH * $crop);
            $nw = max(8, $outW - 2 * $cutX);
            $nh = max(8, $outH - 2 * $cutY);
            $cropped = imagecreatetruecolor($nw, $nh);
            $white2 = imagecolorallocate($cropped, 250, 250, 248);
            imagefilledrectangle($cropped, 0, 0, $nw, $nh, $white2);
            imagecopy($cropped, $canvas, 0, 0, $cutX, $cutY, $nw, $nh);
            unset($canvas); // PHP 8.5: imagedestroy() deprecated — unset cukup untuk melepas GD
            $canvas = $cropped;
        }

        return $canvas;
    }
}
