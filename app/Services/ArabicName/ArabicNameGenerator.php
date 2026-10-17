<?php

namespace App\Services\ArabicName;

/**
 * Generator nama Arab dari nama Latin (best-effort, kaidah transliterasi).
 *
 * Strategi:
 *  1. Kamus frasa umum (mis. "abdul rahman" → عبد الرحمن).
 *  2. Kamus nama populer Indonesia/Arab (mis. "muhammad" → محمد).
 *  3. Transliterasi per kata (digraf Indonesia: ng/ny/sy/kh/ch/gh/th/dh/ts).
 *
 * Implementasi ini deterministik dan tidak memanggil layanan eksternal.
 * Bila ingin memakai AI, ganti binding kelas ini di container dengan
 * implementasi yang memanggil penyedia AI — antarmuka `generate()` tetap.
 */
class ArabicNameGenerator
{
    /** Frasa umum (nama lengkap) → Arab. */
    private const PHRASES = [
        'abdul rahman' => 'عبد الرحمن',
        'abdurrahman' => 'عبد الرحمن',
        'abdul rahim' => 'عبد الرحيم',
        'abdul aziz' => 'عبد العزيز',
        'abdul hamid' => 'عبد الحميد',
        'abdul karim' => 'عبد الكريم',
        'abdul malik' => 'عبد الملك',
        'abdul latif' => 'عبد اللطيف',
        'abdul ghani' => 'عبد الغني',
        'abdul wahab' => 'عبد الوهاب',
        'abdul muhsin' => 'عبد المحسن',
        'abdul qadir' => 'عبد القادر',
        'abdul kadir' => 'عبد القادر',
        'abdul hakim' => 'عبد الحكيم',
        'abdul basit' => 'عبد الباسط',
        'abdul somad' => 'عبد الصمد',
        'abdul rozak' => 'عبد الرزاق',
        'abdul fatah' => 'عبد الفتاح',
        'abdul kholiq' => 'عبد الخالق',
        'muhammad fauzan' => 'محمد فوزان',
        'muhammad farhan' => 'محمد فرحان',
        'muhammad rizki' => 'محمد رزقي',
        'muhammad rizky' => 'محمد رزقي',
        'nur aisyah' => 'نور عائشة',
        'nurul aini' => 'نور العين',
        'siti aisyah' => 'ستي عائشة',
        'siti fatimah' => 'ستي فاطمة',
        'siti khadijah' => 'ستي خديجة',
        'siti maryam' => 'ستي مريم',
        'ahmad fauzi' => 'أحمد فوزي',
        'ahmad soleh' => 'أحمد صالح',
        'ahmad sholeh' => 'أحمد صالح',
    ];

    /** Nama populer → Arab (kunci: huruf kecil tanpa tanda baca). */
    private const NAMES = [
        // Nama nabi & sahabat
        'adam' => 'آدم', 'idris' => 'إدريس', 'nuh' => 'نوح', 'hud' => 'هود',
        'salih' => 'صالح', 'shalih' => 'صالح', 'ibrahim' => 'إبراهيم', 'ismail' => 'إسماعيل',
        'ishaq' => 'إسحاق', 'yaqub' => 'يعقوب', 'yakub' => 'يعقوب', 'yusuf' => 'يوسف',
        'ayyub' => 'أيوب', 'musa' => 'موسى', 'harun' => 'هارون', 'daud' => 'داود',
        'sulaiman' => 'سليمان', 'sulayman' => 'سليمان', 'zakaria' => 'زكريا',
        'yahya' => 'يحيى', 'isa' => 'عيسى', 'muhammad' => 'محمد', 'mohammad' => 'محمد',
        'mohamad' => 'محمد', 'muhamad' => 'محمد', 'muhammmad' => 'محمد', 'ahmad' => 'أحمد',
        'ahmet' => 'أحمد', 'ali' => 'علي', 'umar' => 'عمر', 'omar' => 'عمر',
        'usman' => 'عثمان', 'utsman' => 'عثمان', 'abu' => 'أبو', 'bakr' => 'بكر',
        'hasan' => 'حسن', 'hassan' => 'حسن', 'husain' => 'حسين', 'husein' => 'حسين',
        'hamzah' => 'حمزة', 'hamza' => 'حمزة', 'abbas' => 'عباس', 'ammar' => 'عمار',
        'bilal' => 'بلال', 'salman' => 'سلمان', 'khalid' => 'خالد', 'kholid' => 'خالد',
        'zubair' => 'زبير', 'thariq' => 'طارق', 'tariq' => 'طارق', 'anas' => 'أنس',
        'malik' => 'مالك', 'anwar' => 'أنور', 'ilham' => 'إلهام', 'ikhsan' => 'إحسان',
        // Nama perempuan
        'fatimah' => 'فاطمة', 'fatima' => 'فاطمة', 'aisyah' => 'عائشة', 'aisha' => 'عائشة',
        'khadijah' => 'خديجة', 'khodijah' => 'خديجة', 'zainab' => 'زينب', 'zaynab' => 'زينب',
        'maryam' => 'مريم', 'ruqayyah' => 'رقية', 'aminah' => 'آمنة', 'halimah' => 'حليمة',
        'sumayyah' => 'سمية', 'hafshah' => 'حفصة', 'safiyyah' => 'صفية',
        'nur' => 'نور', 'nurul' => 'نور ال', 'nabila' => 'نبيلة', 'nabilla' => 'نبيلة',
        'salma' => 'سلمى', 'salsabila' => 'سلسبيلا', 'salsabil' => 'سلسبيل',
        'wardah' => 'وردة', 'zahra' => 'زهراء', 'zahrah' => 'زهراء', 'kamilah' => 'كاملة', 'karimah' => 'كريمة',
        'rahmi' => 'رحمي', 'rahmat' => 'رحمة', 'rahmah' => 'رحمة',
        'amirah' => 'أميرة', 'amira' => 'أميرة', 'aula' => 'أولى', 'aulya' => 'أوليا',
        // Nama Indonesia umum
        'agus' => 'أغوس', 'budi' => 'بودي', 'dedi' => 'ديدي', 'eko' => 'إيكو',
        'putra' => 'بوترا', 'putri' => 'بوتري', 'dewi' => 'ديوي', 'sri' => 'سري',
        'wawan' => 'واوان', 'wawan setiawan' => 'واوان ستياوان', 'gunawan' => 'غونوان', 'darmawan' => 'دارماوان',
        'susanto' => 'سوسانتو', 'santoso' => 'سانتوسو', 'wijaya' => 'ويجايا',
        'saputra' => 'سابوترا', 'pratama' => 'براتاما', 'kurniawan' => 'كورنياوان',
        'hidayat' => 'هداية', 'rahman' => 'رحمن', 'rahim' => 'رحيم',
        'fauzi' => 'فوزي', 'fauzan' => 'فوزان', 'farhan' => 'فرحان', 'rizki' => 'رزقي',
        'rizky' => 'رزقي', 'reza' => 'رضا', 'rifqi' => 'رفقي', 'rizal' => 'ريزال',
        'hafiz' => 'حافظ', 'hafidz' => 'حافظ', 'hanif' => 'حنيف', 'hasbi' => 'حسبي',
        'iqbal' => 'إقبال', 'irfan' => 'عرفان', 'ismail' => 'إسماعيل', 'jafar' => 'جعفر',
        'kamal' => 'كمال', 'karim' => 'كريم', 'lukman' => 'لقمان', 'lutfi' => 'لطفي',
        'mahmud' => 'محمود', 'mansur' => 'منصور', 'mubarak' => 'مبارك', 'mukhlis' => 'مخلص',
        'munir' => 'منير', 'mustofa' => 'مصطفى', 'mustafa' => 'مصطفى', 'najib' => 'نجيب',
        'nasir' => 'ناصر', 'naufal' => 'نوفل', 'nizar' => 'نزار', 'rafiq' => 'رفيق',
        'ridwan' => 'رضوان', 'royyan' => 'ريان', 'sabri' => 'صبري', 'sadiq' => 'صادق',
        'safar' => 'سفر', 'salam' => 'سلام', 'samir' => 'سمير', 'shodiq' => 'صادق',
        'subhan' => 'سبحان', 'syaiful' => 'سيف', 'syafiq' => 'شفيق', 'syakir' => 'شاكر',
        'taufik' => 'توفيق', 'taufiq' => 'توفيق', 'wahid' => 'وحيد', 'wahyu' => 'وحي',
        'wafi' => 'وافي', 'yasir' => 'ياسر', 'yasin' => 'ياسين', 'zaki' => 'زكي',
        'zulkifli' => 'ذو الكفل', 'zulqarnain' => 'ذو القرنين',
        // Ejaan baku nama Arab yang lazim
        'abdullah' => 'عبد الله', 'abdillah' => 'عبد الله', 'abdurrahman' => 'عبد الرحمن',
        'abdulrahman' => 'عبد الرحمن', 'abdulaziz' => 'عبد العزيز', 'abdulhamid' => 'عبد الحميد',
        'abdulkarim' => 'عبد الكريم', 'abdulmalik' => 'عبد الملك', 'abdullatif' => 'عبد اللطيف',
        'fatimah' => 'فاطمة', 'khadijah' => 'خديجة', 'aishah' => 'عائشة', 'aisyah' => 'عائشة',
        'muhammad' => 'محمد', 'ahmad' => 'أحمد', 'hasanah' => 'حسنة', 'hasan' => 'حسن',
        'husain' => 'حسين', 'husein' => 'حسين', 'zainab' => 'زينب', 'maryam' => 'مريم',
        'sulaiman' => 'سليمان', 'ibrahim' => 'إبراهيم', 'ismail' => 'إسماعيل', 'yusuf' => 'يوسف',
    ];

    /**
     * Digraf Indonesia → Arab (fonetis).
     *  - ng  = /ŋ/  → نغ (bukan ج yang berbunyi j)
     *  - ny  = /ɲ/  → ني
     *  - sy  = /ʃ/  → ش ; kh = /x/ → خ
     *  - dz  = /z/  → ذ ; z = ز
     */
    private const DIGRAPHS = [
        'ng' => 'نغ', 'ny' => 'ني', 'sy' => 'ش', 'kh' => 'خ', 'ch' => 'تش',
        'th' => 'ت', 'dh' => 'د', 'gh' => 'غ', 'ts' => 'تس', 'dz' => 'ذ',
        'dj' => 'ج', 'tj' => 'تش', 'oe' => 'و',
    ];

    /** Huruf tunggal → Arab. */
    private const LETTERS = [
        'a' => 'ا', 'b' => 'ب', 'c' => 'تش', 'd' => 'د', 'e' => 'ي', 'f' => 'ف',
        'g' => 'غ', 'h' => 'ه', 'i' => 'ي', 'j' => 'ج', 'k' => 'ك', 'l' => 'ل',
        'm' => 'م', 'n' => 'ن', 'o' => 'و', 'p' => 'ف', 'q' => 'ق', 'r' => 'ر',
        's' => 'س', 't' => 'ت', 'u' => 'و', 'v' => 'ف', 'w' => 'و', 'x' => 'كس',
        'y' => 'ي', 'z' => 'ز',
    ];

    public function generate(?string $latinName): string
    {
        $name = trim((string) preg_replace('/\s+/', ' ', (string) $latinName));
        if ($name === '') {
            return '';
        }

        $normalized = mb_strtolower($name);
        $normalized = preg_replace('/[.,\'’`-]+/u', ' ', $normalized) ?? $normalized;
        $normalized = trim((string) preg_replace('/\s+/', ' ', $normalized));

        if (isset(self::PHRASES[$normalized])) {
            return self::PHRASES[$normalized];
        }

        $words = explode(' ', $normalized);
        $result = [];

        foreach ($words as $word) {
            $clean = preg_replace('/[^a-z]/', '', $word) ?? '';

            if ($clean === '' || in_array($clean, ['h', 'hj', 'bin', 'binti'], true)) {
                continue;
            }

            $result[] = self::NAMES[$clean] ?? $this->transliterate($clean);
        }

        return trim(implode(' ', $result));
    }

    /**
     * Transliterasi per kata (best-effort) untuk nama yang tidak ada di kamus.
     */
    public function transliterate(string $word): string
    {
        $word = mb_strtolower($word);
        $length = mb_strlen($word);
        $out = '';
        $i = 0;

        while ($i < $length) {
            $pair = mb_substr($word, $i, 2);

            if (mb_strlen($pair) === 2 && isset(self::DIGRAPHS[$pair])) {
                $out .= self::DIGRAPHS[$pair];
                $i += 2;

                continue;
            }

            $char = mb_substr($word, $i, 1);
            $out .= self::LETTERS[$char] ?? '';
            $i++;
        }

        return $out;
    }
}
