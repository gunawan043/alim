<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use RuntimeException;

/**
 * Perender QR ke PNG memakai ext-gd.
 *
 * simple-qrcode versi proyek ini membutuhkan imagick untuk format PNG,
 * sedangkan server ALIM hanya menjamin ext-gd. Matriks QR diambil langsung
 * dari bacon-qr-code lalu digambar ke GD — tanpa dependensi native baru.
 */
final class LjkQrRenderer
{
    public static function pngDataUri(string $payload, int $targetPx = 420, int $quietZoneModules = 4): string
    {
        $png = self::png($payload, $targetPx, $quietZoneModules);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public static function png(string $payload, int $targetPx = 420, int $quietZoneModules = 4): string
    {
        $qr = Encoder::encode($payload, ErrorCorrectionLevel::M());
        $matrix = $qr->getMatrix();
        $moduleCount = $matrix->getWidth();
        $totalModules = $moduleCount + 2 * $quietZoneModules;

        $scale = max(2, (int) floor($targetPx / max(1, $totalModules)));
        $size = $totalModules * $scale;

        $image = imagecreatetruecolor($size, $size);
        if ($image === false) {
            throw new RuntimeException('Gagal membuat kanvas QR.');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $size - 1, $size - 1, $white);

        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }

                $x0 = ($x + $quietZoneModules) * $scale;
                $y0 = ($y + $quietZoneModules) * $scale;
                imagefilledrectangle($image, $x0, $y0, $x0 + $scale - 1, $y0 + $scale - 1, $black);
            }
        }

        ob_start();
        imagepng($image, null, 6);
        $binary = (string) ob_get_clean();
        unset($image); // PHP 8.5: imagedestroy() deprecated — unset cukup untuk melepas GD

        if ($binary === '') {
            throw new RuntimeException('Gagal meng-encode QR PNG.');
        }

        return $binary;
    }
}
