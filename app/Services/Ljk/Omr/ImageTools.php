<?php

declare(strict_types=1);

namespace App\Services\Ljk\Omr;

use GdImage;
use RuntimeException;

/**
 * Utilitas citra berbasis ext-gd (tanpa dependensi native tambahan).
 */
final class ImageTools
{
    /**
     * Muat gambar dari berkas, batasi ukuran kerja.
     *
     * @return array{gd: GdImage, width: int, height: int, mime: string, scaled: bool}
     */
    public static function load(string $path, int $maxEdge): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('Berkas gambar tidak ditemukan.');
        }

        $info = @getimagesize($path);
        if ($info === false) {
            throw new RuntimeException('Berkas bukan gambar yang dikenali.');
        }

        $mime = (string) ($info['mime'] ?? '');
        $src = match ($mime) {
            'image/jpeg', 'image/jpg' => self::createFromJpeg($path),
            'image/png' => self::createFromPng($path),
            'image/webp' => self::createFromWebp($path),
            default => throw new RuntimeException('Format gambar tidak didukung: '.$mime),
        };

        $w = imagesx($src);
        $h = imagesy($src);
        $longEdge = max($w, $h);
        $scaled = false;

        if ($longEdge > $maxEdge) {
            $ratio = $maxEdge / $longEdge;
            $nw = max(1, (int) round($w * $ratio));
            $nh = max(1, (int) round($h * $ratio));
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            unset($src); // PHP 8.5: imagedestroy() deprecated — unset cukup untuk melepas GD
            $src = $dst;
            $w = $nw;
            $h = $nh;
            $scaled = true;
        }

        return ['gd' => $src, 'width' => $w, 'height' => $h, 'mime' => $mime, 'scaled' => $scaled];
    }

    private static function createFromJpeg(string $path): GdImage
    {
        $gd = @imagecreatefromjpeg($path);
        if ($gd === false) {
            throw new RuntimeException('Gagal membaca JPEG.');
        }

        return $gd;
    }

    private static function createFromPng(string $path): GdImage
    {
        $gd = @imagecreatefrompng($path);
        if ($gd === false) {
            throw new RuntimeException('Gagal membaca PNG.');
        }

        return $gd;
    }

    private static function createFromWebp(string $path): GdImage
    {
        if (! function_exists('imagecreatefromwebp')) {
            throw new RuntimeException('Dukungan WebP tidak tersedia pada server.');
        }

        $gd = @imagecreatefromwebp($path);
        if ($gd === false) {
            throw new RuntimeException('Gagal membaca WebP.');
        }

        return $gd;
    }

    /**
     * Simpan GD ke JPEG (untuk normalisasi/arsip).
     */
    public static function saveJpeg(GdImage $gd, string $path, int $quality = 82): bool
    {
        $dir = dirname($path);
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return false;
        }

        return imagejpeg($gd, $path, $quality);
    }
}
