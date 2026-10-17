<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Teks Arab untuk PDF (Dompdf).
 *
 * Dompdf tidak memiliki mesin shaping/bidi Arab, sehingga pengolahan dilakukan
 * deterministik di PHP:
 *
 *  1. Shaping: huruf Arab dipetakan ke bentuk presentasi kontekstual
 *     (isolated/final/initial/medial, blok Unicode FB50–FDFF & FE70–FEFF),
 *     termasuk ligatur lam-alef.
 *  2. Susunan visual: karena perender PDF menggambar kiri-ke-kanan, urutan
 *     gugus (huruf + harakat) dibalik agar terbaca kanan-ke-kiri; teks Latin
 *     dan angka tetap dalam urutan aslinya.
 *
 * Dirender dengan font yang memuat bentuk presentasi Arab (DejaVu Sans bawaan
 * Dompdf) — lihat `font-family` pada view rapor bahasa Arab.
 */
final class ArabicText
{
    /** Huruf yang dapat disambung ke huruf berikutnya (dual-joining). */
    private const DUAL_JOINING = [
        0x0626, 0x0628, 0x062A, 0x062B, 0x062C, 0x062D, 0x062E,
        0x0633, 0x0634, 0x0635, 0x0636, 0x0637, 0x0638, 0x0639, 0x063A,
        0x0641, 0x0642, 0x0643, 0x0644, 0x0645, 0x0646, 0x0647, 0x064A,
        0x067E, 0x0686, 0x0698, 0x06A9, 0x06AF, 0x06CC,
    ];

    /** Harakat/tanda yang tidak memengaruhi penyambungan. */
    private const TRANSPARENT = [
        0x064B, 0x064C, 0x064D, 0x064E, 0x064F, 0x0650, 0x0651, 0x0652, 0x0653,
        0x0654, 0x0655, 0x0656, 0x0657, 0x0658, 0x0659, 0x065A, 0x065B, 0x065C,
        0x065D, 0x065E, 0x065F, 0x0670, 0x06D6, 0x06D7, 0x06D8, 0x06D9, 0x06DA,
        0x06DB, 0x06DC, 0x06DF, 0x06E0, 0x06E1, 0x06E2, 0x06E3, 0x06E4, 0x06E7,
        0x06E8, 0x06EA, 0x06EB, 0x06EC, 0x06ED,
    ];

    /**
     * Peta bentuk per huruf: [isolated, final, initial, medial|null].
     */
    private const FORMS = [
        0x0621 => [0xFE80, null, null, null],
        0x0622 => [0xFE81, 0xFE82, null, null],
        0x0623 => [0xFE83, 0xFE84, null, null],
        0x0624 => [0xFE85, 0xFE86, null, null],
        0x0625 => [0xFE87, 0xFE88, null, null],
        0x0626 => [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C],
        0x0627 => [0xFE8D, 0xFE8E, null, null],
        0x0628 => [0xFE8F, 0xFE90, 0xFE91, 0xFE92],
        0x0629 => [0xFE93, 0xFE94, null, null],
        0x062A => [0xFE95, 0xFE96, 0xFE97, 0xFE98],
        0x062B => [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C],
        0x062C => [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0],
        0x062D => [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4],
        0x062E => [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8],
        0x062F => [0xFEA9, 0xFEAA, null, null],
        0x0630 => [0xFEAB, 0xFEAC, null, null],
        0x0631 => [0xFEAD, 0xFEAE, null, null],
        0x0632 => [0xFEAF, 0xFEB0, null, null],
        0x0633 => [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4],
        0x0634 => [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8],
        0x0635 => [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC],
        0x0636 => [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0],
        0x0637 => [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4],
        0x0638 => [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8],
        0x0639 => [0xFEC9, 0xFECA, 0xFECB, 0xFECC],
        0x063A => [0xFECD, 0xFECE, 0xFECF, 0xFED0],
        0x0641 => [0xFED1, 0xFED2, 0xFED3, 0xFED4],
        0x0642 => [0xFED5, 0xFED6, 0xFED7, 0xFED8],
        0x0643 => [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC],
        0x0644 => [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0],
        0x0645 => [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4],
        0x0646 => [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8],
        0x0647 => [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC],
        0x0648 => [0xFEED, 0xFEEE, null, null],
        0x0649 => [0xFEEF, 0xFEF0, null, null],
        0x064A => [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4],
        0x067E => [0xFB56, 0xFB57, 0xFB58, 0xFB59],
        0x0686 => [0xFB7A, 0xFB7B, 0xFB7C, 0xFB7D],
        0x0698 => [0xFB8A, 0xFB8B, null, null],
        0x06A9 => [0xFB8E, 0xFB8F, 0xFB90, 0xFB91],
        0x06AF => [0xFB92, 0xFB93, 0xFB94, 0xFB95],
        0x06CC => [0xFBFC, 0xFBFD, 0xFBFE, 0xFBFF],
    ];

    /** Ligatur lam-alef: [lam][alef] => [isolated, final]. */
    private const LAM_ALEF = [
        0x0622 => [0xFEF5, 0xFEF6],
        0x0623 => [0xFEF7, 0xFEF8],
        0x0625 => [0xFEF9, 0xFEFA],
        0x0627 => [0xFEFB, 0xFEFC],
    ];

    public static function hasArabic(?string $text): bool
    {
        if ($text === null || $text === '') {
            return false;
        }

        return (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text);
    }

    /**
     * Konversi angka Latin (0-9) ke angka Arab-Indik (٠-٩) + pemisah desimal.
     * Dipakai untuk nilai, KKTP, rata-rata, tanggal, dsb. secara otomatis.
     */
    public static function digits(?string $value): string
    {
        return strtr((string) $value, [
            '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
            '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
            '.' => '٫',
        ]);
    }

    /**
     * Tanggal format Arab: "17 أكتوبر 2026" dengan angka Arab-Indik.
     */
    public static function formatDate(\DateTimeInterface|string|null $date): string
    {
        if (! $date) {
            return '';
        }

        $carbon = $date instanceof \DateTimeInterface
            ? Carbon::instance($date)
            : Carbon::parse($date);

        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return self::digits($carbon->format('j')).' '.$months[(int) $carbon->format('n')].' '.self::digits($carbon->format('Y'));
    }

    /**
     * Ubah teks Arab menjadi bentuk visual siap-render (shaping + susunan RTL).
     */
    public static function shape(string $text): string
    {
        if (! self::hasArabic($text)) {
            return $text;
        }

        return self::reverseClusters(self::applyContextualForms($text));
    }

    /**
     * HTML aman untuk Dompdf: segmen Arab dibungkus <span class="ar"> dengan
     * bentuk visual; teks Latin/angka tetap seperti aslinya.
     */
    public static function toHtml(?string $text, bool $nl2br = true): string
    {
        $text = (string) $text;

        if ($text === '') {
            return '';
        }

        $lines = preg_split("/\r\n|\r|\n/", $text) ?: [$text];
        $htmlLines = [];

        foreach ($lines as $line) {
            $htmlLines[] = self::lineToHtml($line);
        }

        return $nl2br ? implode('<br>', $htmlLines) : implode(' ', $htmlLines);
    }

    private static function lineToHtml(string $line): string
    {
        if ($line === '' || ! self::hasArabic($line)) {
            return e($line);
        }

        $out = '';

        foreach (self::splitSegments($line) as ['text' => $segment, 'arabic' => $isArabic]) {
            if ($segment === '') {
                continue;
            }

            $out .= $isArabic
                ? '<span class="ar">'.e(self::shape($segment)).'</span>'
                : e($segment);
        }

        return $out;
    }

    /**
     * @return array<int, array{text: string, arabic: bool}>
     */
    private static function splitSegments(string $text): array
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $segments = [];
        $current = '';
        $currentArabic = null;

        foreach ($chars as $char) {
            $isArabic = self::isArabicChar($char);

            if ($currentArabic === null || $isArabic === $currentArabic) {
                $current .= $char;
                $currentArabic = $isArabic;

                continue;
            }

            $segments[] = ['text' => $current, 'arabic' => (bool) $currentArabic];
            $current = $char;
            $currentArabic = $isArabic;
        }

        if ($current !== '') {
            $segments[] = ['text' => $current, 'arabic' => (bool) $currentArabic];
        }

        return $segments;
    }

    private static function isArabicChar(string $char): bool
    {
        $code = mb_ord($char, 'UTF-8');

        return ($code >= 0x0600 && $code <= 0x06FF)
            || ($code >= 0x0750 && $code <= 0x077F)
            || ($code >= 0xFB50 && $code <= 0xFDFF)
            || ($code >= 0xFE70 && $code <= 0xFEFF);
    }

    private static function applyContextualForms(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $codes = array_map(static fn (string $c) => mb_ord($c, 'UTF-8'), $chars);

        $out = [];
        $count = count($codes);

        for ($i = 0; $i < $count; $i++) {
            $code = $codes[$i];

            if (in_array($code, self::TRANSPARENT, true)) {
                $out[] = $chars[$i];

                continue;
            }

            if (! isset(self::FORMS[$code])) {
                $out[] = $chars[$i];

                continue;
            }

            $prevJoins = self::previousJoins($codes, $i);
            $nextJoins = self::nextJoins($codes, $i);

            if ($code === 0x0644 && isset(self::LAM_ALEF[$codes[$i + 1] ?? 0])) {
                $forms = self::LAM_ALEF[$codes[$i + 1]];
                $out[] = mb_chr($prevJoins ? $forms[1] : $forms[0], 'UTF-8');
                $i++;

                continue;
            }

            [$isolated, $final, $initial, $medial] = self::FORMS[$code];

            if ($prevJoins && $nextJoins && $medial !== null) {
                $out[] = mb_chr($medial, 'UTF-8');
            } elseif ($prevJoins && $final !== null) {
                $out[] = mb_chr($final, 'UTF-8');
            } elseif ($nextJoins && $initial !== null) {
                $out[] = mb_chr($initial, 'UTF-8');
            } else {
                $out[] = mb_chr($isolated, 'UTF-8');
            }
        }

        return implode('', $out);
    }

    /**
     * @param  array<int, int>  $codes
     */
    private static function previousJoins(array $codes, int $index): bool
    {
        for ($j = $index - 1; $j >= 0; $j--) {
            if (in_array($codes[$j], self::TRANSPARENT, true)) {
                continue;
            }

            return in_array($codes[$j], self::DUAL_JOINING, true);
        }

        return false;
    }

    /**
     * @param  array<int, int>  $codes
     */
    private static function nextJoins(array $codes, int $index): bool
    {
        for ($j = $index + 1; $j < count($codes); $j++) {
            if (in_array($codes[$j], self::TRANSPARENT, true)) {
                continue;
            }

            return isset(self::FORMS[$codes[$j]]) || isset(self::LAM_ALEF[$codes[$j]]);
        }

        return false;
    }

    private static function reverseClusters(string $shaped): string
    {
        $chars = preg_split('//u', $shaped, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $clusters = [];
        $current = '';

        foreach ($chars as $char) {
            $code = mb_ord($char, 'UTF-8');

            if (in_array($code, self::TRANSPARENT, true) && $current !== '') {
                $current .= $char;

                continue;
            }

            if ($current !== '') {
                $clusters[] = $current;
            }
            $current = $char;
        }

        if ($current !== '') {
            $clusters[] = $current;
        }

        return implode('', array_reverse($clusters));
    }
}
