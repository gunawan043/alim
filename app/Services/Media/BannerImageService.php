<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Normalisasi banner pengumuman/broadcast.
 *
 * Aturan wajib: rasio 4:5, minimal 1080×1350, output selalu 1080×1350.
 * Format simpan: WebP (fallback JPEG).
 */
class BannerImageService
{
    public const WIDTH = 1080;

    public const HEIGHT = 1350;

    public const RATIO = 0.8; // 4:5

    private const MIN_WIDTH = 1080;

    private const MIN_HEIGHT = 1350;

    public function store(UploadedFile $file, string $folder = 'announcements/banners', string $field = 'banner'): string
    {
        $dimensions = @getimagesize($file->getRealPath());

        if (! $dimensions) {
            throw ValidationException::withMessages([
                $field => 'File banner tidak dapat dibaca sebagai gambar.',
            ]);
        }

        [$width, $height] = $dimensions;

        if ($width < self::MIN_WIDTH || $height < self::MIN_HEIGHT) {
            throw ValidationException::withMessages([
                $field => 'Banner minimal 1080×1350 px (rasio 4:5).',
            ]);
        }

        if (abs(($width / $height) - self::RATIO) > 0.01) {
            throw ValidationException::withMessages([
                $field => 'Banner wajib portrait 4:5 (contoh 1080×1350).',
            ]);
        }

        $contents = @file_get_contents($file->getRealPath());
        $source = $contents !== false ? @imagecreatefromstring($contents) : false;

        if (! $source) {
            throw ValidationException::withMessages([
                $field => 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.',
            ]);
        }

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            self::WIDTH,
            self::HEIGHT,
            $width,
            $height
        );

        ob_start();

        if (function_exists('imagewebp')) {
            imagewebp($canvas, null, 82);
            $extension = 'webp';
        } else {
            imagejpeg($canvas, null, 85);
            $extension = 'jpg';
        }

        $binary = ob_get_clean() ?: '';

        imagedestroy($canvas);
        imagedestroy($source);

        if ($binary === '') {
            throw ValidationException::withMessages([
                $field => 'Gagal memproses banner.',
            ]);
        }

        $path = trim($folder, '/').'/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public function delete(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
