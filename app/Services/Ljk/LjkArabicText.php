<?php

declare(strict_types=1);

namespace App\Services\Ljk;

/**
 * Teks Arab untuk PDF (Dompdf).
 *
 * Dompdf tidak memiliki mesin tata bahasa kompleks (Arabic shaping/bidi), maka
 * pengolahan dilakukan deterministik di PHP:
 *
 *  1. **Shaping**: setiap huruf Arab dipetakan ke bentuk presentasi kontekstual
 *     (isolated / final / initial / medial, blok Unicode FB50–FDFF & FE70–FEFF)
 *     mengikuti aturan penyambungan huruf, termasuk ligatur lam-alef.
 *  2. **Susunan visual**: karena perender PDF menggambar kiri-ke-kanan, urutan
 *     gugus (huruf + harakat) dibalik agar terbaca kanan-ke-kiri; teks Latin
 *     dan angka tetap dalam urutan aslinya.
 *
 * Hasilnya dirender dengan font yang memuat bentuk presentasi Arab
 * (DejaVu Sans bawaan Dompdf — sudah diverifikasi memuat U+FEE0/U+FEFB).
 */
final class LjkArabicText
{
    /** Huruf yang dapat disambung ke huruf berikutnya (dual-joining). */
    private const DUAL_JOINING = [
        0x0626, 0x0628, 0x062A, 0x062B, 0x062C, 0x062D, 0x062E,
        0x0633, 0x0634, 0x0635, 0x0636, 0x0637, 0x0638, 0x0639, 0x063A,
        0x0641, 0x0642, 0x0643, 0x0644, 0x0645, 0x0646, 0x0647, 0x064A,
        // tambahan Persia/Urdu yang lazim di naskah pesantren
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
        // Persia/Urdu
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
     * HTML aman untuk Dompdf: gugus Arab dibungkus <span class="ar"> dengan
     * bentuk visual; teks Latin/angka tetap seperti aslinya.
     */
    public static function toHtml(?string $text, bool $nl2br = true): string
    {
        $text = (string) $text;

        if ($text === '') {
            return '';
        }

        // Pisahkan per baris agar <br> tetap bekerja.
        $lines = preg_split("/\r\n|\r|\n/", $text) ?: [$text];
        $htmlLines = [];

        foreach ($lines as $line) {
            $htmlLines[] = self::lineToHtml($line);
        }

        return $nl2br ? implode('<br>', $htmlLines) : implode(' ', $htmlLines);
    }

    /**
     * Versi RTL untuk dokumen Arab: satu baris utuh disusun visual RTL.
     *
     * Berbeda dari toHtml() yang memproses per segmen (aman untuk teks Latin
     * dengan kutipan Arab), metode ini mempertahankan urutan kata Arab dan
     * memindahkan tanda baca akhir (؟، .) ke kiri. Dipakai oleh template
     * naskah Arab yang terpisah.
     */
    public static function toHtmlRtl(?string $text, bool $nl2br = true): string
    {
        $text = (string) $text;

        if ($text === '') {
            return '';
        }

        $lines = preg_split("/\r\n|\r|\n/", $text) ?: [$text];
        $htmlLines = [];

        foreach ($lines as $line) {
            if ($line === '') {
                $htmlLines[] = '';

                continue;
            }

            // Jangan merusak markup HTML; pakai jalur lama untuk baris bertag.
            if (! self::hasArabic($line) || strip_tags($line) !== $line) {
                $htmlLines[] = self::lineToHtml($line);

                continue;
            }

            $htmlLines[] = '<span class="ar">'.e(self::shape($line)).'</span>';
        }

        return $nl2br ? implode('<br>', $htmlLines) : implode(' ', $htmlLines);
    }

    /**
     * Penanda pilihan jawaban pada baris RTL: "ا." — huruf lalu titik.
     *
     * Kaidah Arab: titik berada SETELAH huruf (di sisi kanannya saat urutan
     * dibaca kanan→kiri), sama seperti nomor "١." — bukan ".ا".
     *
     * Urutan visual disusun di PHP karena Dompdf mengabaikan
     * `display: inline-block` pada span, sehingga urutan elemen menjadi
     * urutan cetak yang sebenarnya.
     */
    public static function optionMarkerRtl(string $label): string
    {
        $label = trim($label);

        if ($label === '') {
            return '';
        }

        // Dompdf mencetak elemen sesuai urutan HTML (tanpa bidi), jadi huruf
        // ditulis LEBIH DULU dan titik menyusul: hasil cetak "ا." — huruf di
        // kanan, titik tepat di kirinya. Urutan sebaliknya mencetak ".ا".
        return '<span class="ar">'.e(self::shape($label)).'</span><span class="marker-dot">.</span>';
    }

    /**
     * Teks Arab menjadi HTML naskah cetak (satu baris, tanpa harakat).
     *
     * Harakat dibuang untuk tampilan cetak: Dompdf tidak menerapkan tabel
     * GPOS font, sehingga tanda tidak pernah naik ke atas huruf dan malah
     * menumpuk di garis dasar. Huruf, penyambungan, tanda baca, dan angka
     * tetap utuh. Data di database TIDAK diubah.
     *
     * Kata-kata disusun RTL, tetapi tanda baca penutup (؟ ! . ، :) harus
     * tetap berada di ujung KIRI baris — sama seperti hasil algoritma bidi
     * Unicode — bukan ikut terbawa ke depan kalimat.
     *
     * Paragraf multi-kalimat WAJIB dipecah per kalimat dulu: membalik seluruh
     * paragraf sebagai satu baris akan membalik urutan kalimatnya
     * ("A. B. C." menjadi "C. B. A."), padahal hanya urutan kata di dalam
     * satu kalimat yang perlu dibalik.
     */
    public static function marksToHtml(?string $text): string
    {
        $text = (string) $text;

        if ($text === '' || ! self::hasArabic($text)) {
            return e($text);
        }

        $clean = (string) self::stripMarks($text);
        $sentences = [];

        foreach (self::splitSentences($clean) as $sentence) {
            $shaped = self::shapeLine($sentence);

            if ($shaped !== '') {
                $sentences[] = $shaped;
            }
        }

        return '<span class="ar">'.e(implode(' ', $sentences)).'</span>';
    }

    /**
     * Pecah paragraf menjadi kalimat utuh (penutup tetap menempel).
     *
     * Pemisah kalimat Arab: `.` `!` `؟` serta padanannya `۔` `?`. Tanda baca
     * tersebut dipertahankan di akhir potongan agar shapeLine() masih bisa
     * menempatkannya di ujung kiri setiap kalimat.
     *
     * @return array<int, string>
     */
    public static function splitSentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?؟۔])\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_map('trim', $parts), static fn ($p) => $p !== ''));
    }

    /** Buang harakat dari teks Arab tanpa menyentuh huruf atau tanda baca. */
    public static function stripMarks(string $text): string
    {
        // Non-Spacing Mark apa pun (Unicode kategori Mn) adalah tanda
        // diakritik — aman dibuang untuk semua blok Arab, tidak menyentuh
        // huruf (Lo), tanda baca (Po), maupun angka (Nd).
        $text = (string) preg_replace('/\p{Mn}+/u', '', $text);

        // Bentuk presentasi untuk harakat tunggal (U+FE70–FE7F) ber-kategori
        // Lo di Unicode, sehingga tidak tertangkap \p{Mn}.
        return (string) preg_replace('/[\x{FE70}-\x{FE7F}]/u', '', $text);
    }

    /**
     * Susun satu baris Arab ke urutan visual (kiri→kanan) sesuai kaidah bidi.
     *
     * Langkah:
     *  1. Pisahkan tanda baca/tanda kurung di ujung baris (leading/trailing).
     *  2. Tokenisasi kata; deret angka di dalam kalimat menjadi satu token dan
     *     isinya tidak dibalik.
     *  3. Balik urutan kata, lalu tempelkan balik tanda baca awal & akhir.
     *  4. Cerminkan pasangan kurung/siku karena arah baca dibalik.
     */
    public static function shapeLine(string $line): string
    {
        $line = trim($line);

        if ($line === '') {
            return '';
        }

        [$body, $closing] = self::splitEdgePunctuation($line);

        // Spasi sebelum tanda baca netral (" :", " !") bukan bagian kaidah
        // Arab dan membuat jarak tidak konsisten; rapatkan ke kata sebelumnya.
        $body = (string) preg_replace('/\s+([,،.:؛؟!])/u', '$1', $body);

        $words = [];
        // `$glue[i] = true` berarti token ke-i menempel TANPA spasi pada token
        // sebelumnya. Dipakai untuk tanda baca yang secara logis menempel di
        // ekor kata ("الكتب،" / "المعلم:") agar hasil cetak pun rapat
        // ("ﺐﺘﻜﻟﺍ،" bukan "ﺐﺘﻜﻟﺍ ،").
        $glue = [];

        foreach (preg_split('/\s+/u', $body, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            // Pemisah netral ("/" "|") berdiri sendiri sebagai token: setelah
            // urutan dibalik ia tetap berada di antara kedua bagian kalimat,
            // tidak menempel pada angka di sebelahnya.
            if (preg_match('/^[\/|]+$/u', $word)) {
                $words[] = $word;
                $glue[] = false;

                continue;
            }

            // Tanda baca ekor kata ("الكتب،" / "المعلم:") dipisah sebagai
            // token sendiri, lalu ditempel kembali ke KIRI kata yang sama
            // setelah urutan dibalik — bukan ke kata berikutnya.
            if (preg_match('/^(.*[^\s,،.:؛؟!])([,،.:؛؟!]+)$/u', $word, $m)) {
                $words[] = self::shapeWord($m[1]);
                $glue[] = false;
                $words[] = $m[2];
                $glue[] = true; // tanda baca menempel pada kata di depannya

                continue;
            }

            $words[] = self::shapeWord($word);
            $glue[] = false;
        }

        // Balik urutan. Token tanda baca ekor ("المعلم:" → "المعلم" + ":")
        // kini berada TEPAT SEBELUM kata yang dipisahnya. Secara visual tanda
        // baca harus menempel di sisi KIRI kata itu, artinya ia menjadi AKHIRAN
        // dari kata tersebut — bukan awalan kata berikutnya.
        $words = array_reverse($words);
        $glue = array_reverse($glue);

        $out = '';
        $pending = ''; // tanda baca menunggu digabung ke AKHIR kata berikutnya

        foreach ($words as $i => $token) {
            if ($glue[$i] ?? false) {
                $pending .= $token;

                continue;
            }

            $out .= ($out === '' ? '' : ' ').$token.$pending;
            $pending = '';
        }

        // Tanda baca penutup kalimat berada di ujung KIRI baris visual.
        return $out.$pending.$closing;
    }

    /**
     * Pisahkan tanda baca penutup dari isi baris.
     *
     * Tanda di akhir teks ("… الإجابة!") dipindah ke kumpulan tanda penutup.
     * Tanda yang terlanjur berada di awal teks ("!… الإجابة", umum pada teks
     * hasil salin-tempel dari tampilan RTL) juga dianggap tanda penutup —
     * keduanya menghasilkan satu bentuk visual yang sama.
     *
     * @return array{0: string, 1: string} [body, closing]
     */
    private static function splitEdgePunctuation(string $line): array
    {
        $closing = '';

        // Tanda baca di akhir baris (bisa lebih dari satu, mis. "؟!").
        while ($line !== '' && preg_match('/[,.:،؛؟!؛]$/u', $line, $m)) {
            $closing = $m[0].$closing;
            $line = rtrim(mb_substr($line, 0, mb_strlen($line) - 1));
        }

        // Tanda baca di awal baris: hanya bila kata pertama seluruhnya tanda.
        $firstWord = (preg_split('/\s+/u', $line, 2)[0]) ?? '';
        if ($firstWord !== '' && preg_match('/^[,.:،؛؟!]+$/u', $firstWord)) {
            $closing = $firstWord.$closing;
            $line = ltrim(mb_substr($line, mb_strlen($firstWord)));
        }

        return [$line, $closing];
    }

    /**
     * Satu kata: shape huruf Arab, biarkan token angka/Latin apa adanya.
     */
    private static function shapeWord(string $word): string
    {
        $out = '';
        foreach (self::splitSegments($word) as ['text' => $segment, 'arabic' => $isArabic]) {
            $out .= $isArabic ? self::shape($segment) : $segment;
        }

        return $out;
    }

    /**
     * Baris Arab untuk instruksi/petunjuk yang mengandung kurung atau teks
     * Latin di tengah kalimat, mis. "… (X) pada huruf (أ، ب، ج، د) …".
     *
     * Berbeda dari marksToHtml(): tanda kurung dan isinya dijadikan satu
     * token ber-arah LTR di dalam <span class="ltr">, sehingga Dompdf
     * menggambar "(X)" apa adanya. Tanpa ini, pencerminan urutan RTL
     * menghasilkan ")X(".
     */
    public static function instructionsToHtml(?string $text): string
    {
        $text = trim((string) $text);

        if ($text === '' || ! self::hasArabic($text)) {
            return e($text);
        }

        $text = (string) self::stripMarks($text);
        $closing = '';

        // Tanda penutup kalimat di ujung: pindah ke kiri baris.
        while ($text !== '' && preg_match('/[,.:،؛؟!؛]$/u', $text, $m)) {
            $closing = $m[0].$closing;
            $text = rtrim(mb_substr($text, 0, mb_strlen($text) - 1));
        }

        // Pecah menjadi token berspasi; ":الحروف" → ":" dan "الحروف" agar
        // titik dua kembali menempel di KIRI kata setelah urutan dibalik.
        $tokens = [];
        foreach (preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            $tokens = array_merge($tokens, self::splitPunctuationToken($word));
        }

        // Gabungkan kata menjadi kelompok kutipan, lalu balik urutan kelompok.
        $groups = [];
        $buffer = '';
        foreach ($tokens as $word) {
            $buffer = $buffer === '' ? $word : $buffer.' '.$word;

            if (self::bracketBalance($buffer) === 0) {
                $groups[] = $buffer;
                $buffer = '';
            }
        }
        if ($buffer !== '') {
            $groups[] = $buffer;
        }

        $html = [];
        foreach (array_reverse($groups) as $group) {
            $html[] = self::groupToHtml($group);
        }

        return implode(' ', $html).e($closing);
    }

    /**
     * Selisih kurung buka/tutup pada sebuah potongan teks.
     * 0 berarti potongan sudah seimbang (bukan potongan dalam kurung).
     */
    private static function bracketBalance(string $text): int
    {
        return substr_count($text, '(') - substr_count($text, ')')
            + substr_count($text, '[') - substr_count($text, ']');
    }

    /**
     * Pisahkan tanda baca netral dari kata, mis. "الحروف:" → [":", "الحروف"].
     *
     * Tanda baca bukan bagian kata Arab; setelah urutan dibalik ia harus
     * tetap menempel di KIRI kata aslinya.
     *
     * @return array<int, string>
     */
    private static function splitPunctuationToken(string $word): array
    {
        if (preg_match('/^([^\s,،.:؛]+)([,،.:؛]+)$/u', $word, $m)) {
            // "الحروف:" → token kata lalu token tanda. Setelah urutan kata
            // dibalik, tanda ini akan berada di KIRI kata tersebut.
            return [$m[1], $m[2]];
        }

        return [$word];
    }

    /** Satu kelompok kata: segmen Arab di-shape, kurung/Latin dibiarkan LTR. */
    private static function groupToHtml(string $group): string
    {
        $hasBracket = (bool) preg_match('/[()\[\]«»]/u', $group);

        // Kelompok berkurung memakai font Latin agar "(X)" dan "(أ، ب، ج، د)"
        // tergambar sesuai urutan aslinya tanpa dibalik algoritma RTL.
        if ($hasBracket && self::hasArabic($group)) {
            $parts = [];
            foreach (preg_split('/([()\[\]«»])/u', $group, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
                $parts[] = preg_match('/^[()\[\]«»]$/u', $part)
                    ? '<span class="ltr">'.e($part).'</span>'
                    : '<span class="ar">'.e(self::shape($part)).'</span>';
            }

            return '<span class="ltr-group">'.implode('', $parts).'</span>';
        }

        if ($hasBracket) {
            return '<span class="ltr">'.e($group).'</span>';
        }

        return '<span class="ar">'.e(self::shape($group)).'</span>';
    }


    /**
     * Satu baris: pecah menjadi segmen Arab dan non-Arab.
     */
    private static function lineToHtml(string $line): string
    {
        if ($line === '' || ! self::hasArabic($line)) {
            return e($line);
        }

        $segments = self::splitSegments($line);
        $out = '';

        foreach ($segments as ['text' => $segment, 'arabic' => $isArabic]) {
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
     * Pecah teks menjadi segmen Arab / non-Arab (spasi mengikuti segmen
     * sebelumnya agar kalimat Latin di tengah teks Arab tidak terpotong).
     *
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

    /**
     * Terapkan bentuk kontekstual + ligatur lam-alef.
     */
    private static function applyContextualForms(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $codes = array_map(static fn (string $c) => mb_ord($c, 'UTF-8'), $chars);

        $out = [];
        $count = count($codes);

        for ($i = 0; $i < $count; $i++) {
            $code = $codes[$i];

            // Harakat: teruskan apa adanya (menempel pada huruf sebelumnya).
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

            // Ligatur lam-alef.
            if ($code === 0x0644 && isset(self::LAM_ALEF[$codes[$i + 1] ?? 0])) {
                $forms = self::LAM_ALEF[$codes[$i + 1]];
                $out[] = mb_chr($prevJoins ? $forms[1] : $forms[0], 'UTF-8');
                $i++; // lewati alef

                continue;
            }

            $forms = self::FORMS[$code];
            [$isolated, $final, $initial, $medial] = $forms;

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
     * Apakah huruf sebelumnya menyambung ke huruf ini (melewati harakat).
     *
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
     * Apakah huruf berikutnya dapat disambung (melewati harakat).
     *
     * @param  array<int, int>  $codes
     */
    private static function nextJoins(array $codes, int $index): bool
    {
        for ($j = $index + 1; $j < count($codes); $j++) {
            if (in_array($codes[$j], self::TRANSPARENT, true)) {
                continue;
            }

            // Huruf apa pun yang punya bentuk sambungan (final/medial) bisa
            // disambung dari kanan.
            return isset(self::FORMS[$codes[$j]]) || isset(self::LAM_ALEF[$codes[$j]]);
        }

        return false;
    }

    /**
     * Balik urutan gugus (huruf + harakat) — harakat tetap menempel pada
     * hurufnya sehingga terbaca benar pada perender kiri-ke-kanan.
     *
     * Deret ANGKA diperlakukan sebagai satu gugus utuh dan digitnya TIDAK
     * dibalik: pada teks Arab, "٢٠٢٦" harus tetap terbaca 2026, bukan 6202.
     */
    private static function reverseClusters(string $shaped): string
    {
        $chars = preg_split('//u', $shaped, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $clusters = [];
        $current = '';

        // Harakat berada SETELAH hurufnya pada urutan logis. Karena gugus
        // dibalik, harakat harus dikeluarkan lalu ditempelkan kembali tepat di
        // BELAKANG huruf setelah pembalikan — bila dibiarkan ikut terbawa
        // sebelum huruf, ia menempel pada huruf sebelah kiri setelah urutan
        // dibalik (fathah/kasrah tampak bergeser satu huruf).
        // Dikunci per INDEKS gugus: beberapa gugus bisa berupa huruf yang sama
        // (mis. dua kali "ﻣ"), sehingga kunci berbasis teks akan mencampur
        // harakat kedua gugus tersebut.
        $marks = [];
        $clusterIndex = -1;

        foreach ($chars as $char) {
            $code = mb_ord($char, 'UTF-8');

            if (in_array($code, self::TRANSPARENT, true)) {
                if ($current !== '') {
                    $marks[$clusterIndex][] = $char; // tangguhkan harakat

                    continue;
                }

                // Harakat tanpa huruf sebelumnya (mis. tunggal): satu gugus.
                $current = $char;

                continue;
            }

            // Deret angka dan kata Latin menempel menjadi satu gugus agar
            // urutannya tidak terbalik saat baris disusun RTL.
            $isWordChar = self::isDigit($code) || self::isLatinLetter($code);
            $currentIsWordChar = $current !== ''
                && (self::isDigit(mb_ord(mb_substr($current, 0, 1), 'UTF-8'))
                    || self::isLatinLetter(mb_ord(mb_substr($current, 0, 1), 'UTF-8')));

            if ($isWordChar && $currentIsWordChar) {
                $current .= $char;

                continue;
            }

            if ($current !== '') {
                $clusters[] = $current;
            }
            $current = $char;
            $clusterIndex++;
        }

        if ($current !== '') {
            $clusters[] = $current;
        }

        // Balik urutan huruf, lalu kembalikan harakat ke belakang huruf
        // asalnya. Urutan harakat pada satu huruf ikut dibalik karena
        // perender LTR menggambar tanda dari kiri ke kanan — tanpa ini
        // tasydid yang ditulis SEBELUM fathah (syadda + fatha) tampil di
        // sisi yang salah dibanding bentuk presentasi Unicode.
        $visual = [];
        foreach (array_reverse(array_keys($clusters)) as $index) {
            $cluster = $clusters[$index];
            $attached = $marks[$index] ?? [];

            // Deret angka/Latin tidak membawa harakat dan tidak dibalik isinya.
            if ($attached !== [] && ! self::isDigitOrLatinCluster($cluster)) {
                $attached = array_reverse($attached);
            }

            $visual[] = $cluster.implode('', $attached);
        }

        return implode('', $visual);
    }

    /** Gugus angka/Latin (bukan huruf Arab berharakat). */
    private static function isDigitOrLatinCluster(string $cluster): bool
    {
        $first = mb_ord(mb_substr($cluster, 0, 1), 'UTF-8');

        return self::isDigit($first) || self::isLatinLetter($first);
    }

    /**
     * Angka Arab-Indic (٠-٩), Arab-Indic ekstensi (۰-۹), dan Latin (0-9).
     * Deret angka tidak boleh dibalik saat menyusun urutan visual RTL.
     */
    private static function isDigit(int $code): bool
    {
        return ($code >= 0x0660 && $code <= 0x0669)   // ٠-٩
            || ($code >= 0x06F0 && $code <= 0x06F9)   // ۰-۹
            || ($code >= 0x0030 && $code <= 0x0039);  // 0-9
    }

    /** Huruf Latin dasar; dipakai untuk menjaga kata Latin utuh saat RTL. */
    private static function isLatinLetter(int $code): bool
    {
        return ($code >= 0x0041 && $code <= 0x005A)   // A-Z
            || ($code >= 0x0061 && $code <= 0x007A);  // a-z
    }
}
