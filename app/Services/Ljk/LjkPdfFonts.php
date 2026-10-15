<?php

declare(strict_types=1);

namespace App\Services\Ljk;

use Dompdf\Options;

/**
 * Resolusi font PDF LJK.
 *
 * Prioritas: **Cambria** (bila berkas TTF disediakan sekolah/server) →
 * **Caladea** (metrik kompatibel dengan Cambria, lisensi terbuka) →
 * **DejaVu Serif** (bawaan Dompdf, selalu tersedia) — sehingga seluruh PDF
 * LJK konsisten serif meski Cambria berlisensi belum dipasang.
 *
 * Menaruh berkas font (salah satu):
 *   storage/app/fonts/Cambria.ttf (+ -Bold, -Italic, -BoldItalic)
 *   storage/app/fonts/Caladea-Regular.ttf (+ -Bold, -Italic, -BoldItalic)
 *   atau paket sistem: fonts-crosextra-caladea (Linux) / /Library/Fonts (macOS)
 */
final class LjkPdfFonts
{
    /** @var array<string, string>|null cache hasil pemindaian */
    private static ?array $cache = null;

    /**
     * Konfigurasi Dompdf: cache metrik font wajib writable agar font baru
     * (Cambria/Caladea/DejaVu Serif) dapat didaftarkan saat render.
     */
    public static function configure(Options $options): void
    {
        $cacheDir = (string) config('ljk.pdf.fonts.cache', storage_path('app/dompdf/fonts'));

        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }

        $options->setFontCache($cacheDir);
        $options->setChroot(base_path());
        $options->setIsFontSubsettingEnabled(true);
    }

    /**
     * Berkas font per slot (normal/bold/italic/bolditalic) — hasil pemindaian.
     *
     * @return array<string, string>
     */
    public static function faces(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $found = ['normal' => null, 'bold' => null, 'italic' => null, 'bolditalic' => null];

        foreach (self::candidateFiles() as $path) {
            $slot = self::slotFor(basename($path));
            if ($slot === null) {
                continue;
            }

            $isCambria = str_contains(mb_strtolower(basename($path)), 'cambria');

            // Cambria diprioritaskan atas Caladea.
            if ($found[$slot] === null || ($isCambria && ! str_contains(mb_strtolower(basename($found[$slot])), 'cambria'))) {
                $found[$slot] = $path;
            }
        }

        return self::$cache = array_filter($found, static fn ($p) => $p !== null);
    }

    /**
     * @return array<int, string> path absolut berkas kandidat
     */
    private static function candidateFiles(): array
    {
        $files = [];

        foreach ((array) config('ljk.pdf.fonts.paths', []) as $dir) {
            if (! is_string($dir) || ! is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            $depth = 0;
            foreach ($iterator as $file) {
                if ($depth++ > 400) {
                    break;
                }

                if ($file instanceof \SplFileInfo && $file->isFile()
                    && preg_match('/\.(ttf|otf)$/i', $file->getFilename())
                    && preg_match('/cambria|caladea/i', $file->getFilename())) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return array_values(array_unique($files));
    }

    /**
     * Tentukan slot font dari nama berkas.
     */
    private static function slotFor(string $filename): ?string
    {
        $name = mb_strtolower($filename);

        if (! preg_match('/cambria|caladea/i', $name)) {
            return null;
        }

        $bold = str_contains($name, 'bold');
        $italic = str_contains($name, 'italic') || str_contains($name, 'oblique');

        return match (true) {
            $bold && $italic => 'bolditalic',
            $bold => 'bold',
            $italic => 'italic',
            default => 'normal',
        };
    }

    /**
     * CSS @font-face untuk berkas yang ditemukan ('' bila tidak ada).
     */
    public static function faceCss(): string
    {
        $faces = self::faces();

        if ($faces === []) {
            return '';
        }

        $family = (string) config('ljk.pdf.fonts.family', 'LJK-Cambria');
        $styles = [
            'normal' => 'font-weight: normal; font-style: normal;',
            'bold' => 'font-weight: bold; font-style: normal;',
            'italic' => 'font-weight: normal; font-style: italic;',
            'bolditalic' => 'font-weight: bold; font-style: italic;',
        ];

        $css = '';
        foreach ($faces as $slot => $path) {
            $css .= "@font-face { font-family: '{$family}'; src: url('file://".$path."') format('truetype'); ".$styles[$slot]." }\n";
        }

        // Bila hanya ada satu berkas (umumnya normal), tetap sediakan varian tebal
        // agar judul tidak jatuh ke font lain.
        if (! isset($faces['bold']) && isset($faces['normal'])) {
            $css .= "@font-face { font-family: '{$family}'; src: url('file://".$faces['normal']."') format('truetype'); font-weight: bold; font-style: normal; }\n";
        }

        return $css;
    }

    /**
     * Stack font-family untuk seluruh PDF LJK.
     */
    public static function stack(): string
    {
        $family = (string) config('ljk.pdf.fonts.family', 'LJK-Cambria');

        if (self::faces() !== []) {
            return "'{$family}', Cambria, Caladea, 'DejaVu Serif', serif";
        }

        // Tanpa berkas Cambria: Cambria/Caladea bila terpasang di sistem,
        // selebihnya serif bawaan Dompdf (keduanya didaftarkan Dompdf bila ada).
        return "Cambria, Caladea, 'DejaVu Serif', serif";
    }

    /**
     * Label font aktif untuk keterangan di UI.
     */
    public static function activeLabel(): string
    {
        $faces = self::faces();

        if ($faces === []) {
            return 'DejaVu Serif (Cambria/Caladea belum dipasang)';
        }

        return str_contains(mb_strtolower($faces['normal']), 'cambria')
            ? 'Cambria (dari berkas sekolah)'
            : 'Caladea — metrik Cambria (lisensi terbuka)';
    }

    /**
     * Petunjuk pemasangan font untuk UI.
     */
    public static function installHint(): string
    {
        return 'Letakkan Cambria.ttf/Cambria-Bold.ttf (atau Caladea-*.ttf) di storage/app/fonts/ agar PDF memakai font Cambria.';
    }
}
