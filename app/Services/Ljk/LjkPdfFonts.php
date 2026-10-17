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

            foreach (self::filesIn($dir) as $file) {
                if (preg_match('/cambria|caladea/i', basename($file))) {
                    $files[] = $file;
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
     * Berkas font Arab terdaftar: ['normal' => path, 'bold' => path].
     *
     * @var array{normal: ?string, bold: ?string}|null
     */
    private static ?array $arabicFaces = null;

    /**
     * @return array{normal: ?string, bold: ?string}
     */
    public static function arabicFaces(): array
    {
        if (self::$arabicFaces !== null) {
            return self::$arabicFaces;
        }

        $candidates = [];

        foreach ((array) config('ljk.pdf.fonts.arabic_paths', []) as $dir) {
            if (! is_string($dir) || ! is_dir($dir)) {
                continue;
            }

            foreach (self::filesIn($dir) as $file) {
                if (preg_match('/amiri|scheherazade|kfgqpc|naskh|arabic|lateef|geeza|nile|thabit|sakkal|majalla|shafigh|niloofar|riyaz|zar/i', basename($file))
                    && self::hasArabicPresentationForms($file)) {
                    $candidates[] = $file;
                }
            }
        }

        if ($candidates === []) {
            return self::$arabicFaces = ['normal' => null, 'bold' => null];
        }

        $preferred = (array) config('ljk.pdf.fonts.arabic_preferred', []);
        usort($candidates, function (string $a, string $b) use ($preferred) {
            $score = function (string $file) use ($preferred): int {
                $name = mb_strtolower(basename($file));
                foreach ($preferred as $i => $needle) {
                    if ($needle !== '' && str_contains($name, mb_strtolower((string) $needle))) {
                        return $i;
                    }
                }

                return count($preferred) + 1;
            };

            return $score($a) <=> $score($b) ?: strcmp($a, $b);
        });

        $normal = null;
        $bold = null;

        foreach ($candidates as $file) {
            $name = mb_strtolower(basename($file));
            $isBold = str_contains($name, 'bold');
            $isItalic = str_contains($name, 'italic') || str_contains($name, 'oblique');

            if (! $isItalic && ! $isBold && $normal === null) {
                $normal = $file;
            }
            if (! $isItalic && $isBold && $bold === null) {
                $bold = $file;
            }
        }

        $normal ??= $candidates[0];
        $bold ??= $normal;

        return self::$arabicFaces = ['normal' => $normal, 'bold' => $bold];
    }

    /**
     * Font Arab utama (varian Regular).
     */
    public static function arabicFontPath(): ?string
    {
        return self::arabicFaces()['normal'];
    }

    /**
     * @font-face untuk font Arab terdaftar (normal + bold).
     */
    public static function arabicFaceCss(): string
    {
        $faces = self::arabicFaces();
        $normal = $faces['normal'];
        $bold = $faces['bold'];

        if ($normal === null) {
            return '';
        }

        $css = "@font-face { font-family: 'LJK-Arabic'; src: url('file://".$normal."') format('truetype'); font-weight: normal; font-style: normal; }\n";

        if ($bold !== null && $bold !== $normal) {
            $css .= "@font-face { font-family: 'LJK-Arabic'; src: url('file://".$bold."') format('truetype'); font-weight: bold; font-style: normal; }\n";
        }

        return $css;
    }

    /**
     * Stack font untuk teks Arab — DejaVu Sans bawaan Dompdf memuat bentuk
     * presentasi Arab (U+FE70–FEFC) sehingga shaping PHP tetap terbaca.
     */
    public static function arabicStack(): string
    {
        return self::arabicFontPath() !== null
            ? "'LJK-Arabic', 'DejaVu Sans', sans-serif"
            : "'DejaVu Sans', sans-serif";
    }

    /**
     * Cek apakah berkas font memuat bentuk presentasi Arab (ctx: U+FEE0/U+FEFB).
     */
    public static function hasArabicPresentationForms(string $ttfPath): bool
    {
        try {
            return \Illuminate\Support\Facades\Cache::rememberForever(
                'ljk:arabic-forms:'.sha1($ttfPath.':'.@filemtime($ttfPath)),
                function () use ($ttfPath) {
                    $font = \FontLib\Font::load($ttfPath);
                    $font->parse();
                    $map = $font->getUnicodeCharMap();

                    return isset($map[0xFEE0], $map[0xFEFB]);
                }
            );
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, string>
     */
    private static function filesIn(string $dir): array
    {
        $files = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            $limit = 0;
            foreach ($iterator as $file) {
                if ($limit++ > 400) {
                    break;
                }
                if ($file instanceof \SplFileInfo && $file->isFile() && preg_match('/\.(ttf|otf)$/i', $file->getFilename())) {
                    $files[] = $file->getPathname();
                }
            }
        } catch (\Throwable) {
            return [];
        }

        return $files;
    }

    /**
     * Apakah font Cambria/Caladea tersedia (bukan font pengganti serif bawaan).
     */
    public static function hasCambria(): bool
    {
        $faces = self::faces();

        if ($faces === []) {
            return false;
        }

        $name = mb_strtolower((string) ($faces['normal'] ?? ''));

        return str_contains($name, 'cambria') || str_contains($name, 'caladea');
    }

    /**
     * Faktor skala ukuran font agar setara Cambria pada pt yang sama.
     */
    public static function sizeScale(): float
    {
        return self::hasCambria() ? 1.0 : (float) config('ljk.soal.font_size_fallback_scale', 0.9);
    }

    /**
     * Label font Arab aktif (untuk keterangan di UI).
     */
    public static function arabicFontLabel(): string
    {
        $file = self::arabicFontPath();

        return $file !== null
            ? pathinfo((string) $file, PATHINFO_FILENAME).' (font Arab terpasang)'
            : 'DejaVu Sans (bawaan Dompdf)';
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
