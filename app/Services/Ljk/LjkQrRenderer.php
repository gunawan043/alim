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
    /** Cache gambar logo per request agar pembuatan QR massal tidak
     *  membaca ulang file 1000x1000 untuk setiap kode. */
    private static array $logoCache = [];
    public static function pngDataUri(string $payload, int $targetPx = 480, int $quietZoneModules = 4, string $errorCorrection = 'Q', ?string $logoPath = null, ?float $logoRatio = null): string
    {
        $png = self::png($payload, $targetPx, $quietZoneModules, $errorCorrection, $logoPath, $logoRatio);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public static function png(string $payload, int $targetPx = 480, int $quietZoneModules = 4, string $errorCorrection = 'Q', ?string $logoPath = null, ?float $logoRatio = null): string
    {
        $logoPath = ($logoPath !== null && is_file($logoPath) && is_readable($logoPath)) ? $logoPath : null;

        // Logo menutupi sebagian matriks, jadi selalu pakai level H (30%)
        // agar kode tetap terbaca pemindai.
        if ($logoPath !== null) {
            $errorCorrection = 'H';
        }

        $level = match (strtoupper($errorCorrection)) {
            'L' => ErrorCorrectionLevel::L(),
            'H' => ErrorCorrectionLevel::H(),
            'Q' => ErrorCorrectionLevel::Q(),
            default => ErrorCorrectionLevel::M(),
        };
        $qr = Encoder::encode($payload, $level);
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

        if ($logoPath !== null) {
            self::drawLogo($image, $size, $logoPath, $logoRatio);
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

    /**
     * Tempel logo di tengah QR dengan latar putih agar modul di belakangnya
     * tidak mengganggu bentuk logo.
     */
    private static function drawLogo($image, int $size, string $logoPath, ?float $ratio): void
    {
        $logo = self::loadImage($logoPath);

        // Tanpa logo yang bisa dibaca, jangan tinggalkan kotak putih kosong di tengah QR.
        if ($logo === null) {
            return;
        }

        $ratio = $ratio ?? 0.22;
        $ratio = max(0.10, min(0.30, $ratio));

        $logoSize = max(16, (int) round($size * $ratio));
        $logoSize = min($logoSize, (int) floor($size * 0.40));

        $x = (int) (($size - $logoSize) / 2);
        $y = $x;
        $pad = max(4, (int) round($logoSize * 0.10));

        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, $x - $pad, $y - $pad, $x + $logoSize + $pad, $y + $logoSize + $pad, $white);

        imagealphablending($image, true);
        imagecopyresampled(
            $image,
            $logo,
            $x,
            $y,
            0,
            0,
            $logoSize,
            $logoSize,
            imagesx($logo),
            imagesy($logo)
        );
    }

    private static function loadImage(string $path)
    {
        if (array_key_exists($path, self::$logoCache)) {
            return self::$logoCache[$path];
        }

        $info = @getimagesize($path);

        if ($info === false) {
            return self::$logoCache[$path] = null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null,
            default => null,
        };

        return self::$logoCache[$path] = ($image === false ? null : $image);
    }
}
