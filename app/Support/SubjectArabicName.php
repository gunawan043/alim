<?php

namespace App\Support;

/**
 * Kamus nama mapel Indonesia → Arab.
 *
 * Dipakai sebagai fallback otomatis saat kolom `subjects.name_ar` kosong.
 * Nama yang sudah diisi manual di halaman mapel selalu diprioritaskan.
 */
final class SubjectArabicName
{
    /** @var array<string, string> */
    private const NAMES = [
        'adab' => 'الأدب',
        'aqidah' => 'العقيدة',
        'bahasa arab' => 'اللغة العربية',
        'bahasa indonesia' => 'اللغة الإندونيسية',
        'bahasa inggris' => 'اللغة الإنجليزية',
        'balagoh' => 'البلاغة',
        'balaghah' => 'البلاغة',
        "fara'id" => 'الفرائض',
        'faraid' => 'الفرائض',
        'fikih' => 'الفقه',
        'fiqih' => 'الفقه',
        'hafalan hadits' => 'حفظ الحديث',
        'hafalan hadist' => 'حفظ الحديث',
        'ilmu pengetahuan sosial' => 'العلوم الاجتماعية',
        'informatika' => 'المعلوماتية',
        'ipa' => 'العلوم الطبيعية',
        'ipa terpadu' => 'العلوم الطبيعية المتكاملة',
        'ipas' => 'العلوم الطبيعية والاجتماعية',
        'ips (ekonomi, sejarah, geografi & sosiologi)' => 'العلوم الاجتماعية (الاقتصاد، التاريخ، الجغرافيا، وعلم الاجتماع)',
        'khot' => 'الخط',
        'kitabah' => 'الكتابة',
        'life skill / cooking' => 'المهارات الحياتية / الطبخ',
        'liqo maftuh (upacara)' => 'اللقاء المفتوح (الحفل)',
        'manhaj' => 'المنهج',
        'mapel pilihan' => 'مادة اختيارية',
        'matematika' => 'الرياضيات',
        'matematika peminatan' => 'الرياضيات المتخصصة',
        'mustholahul hadits' => 'مصطلح الحديث',
        'nahwu' => 'النحو',
        'pembiasaan' => 'التعويد',
        'penjaskes' => 'التربية البدنية والصحية',
        'pjok' => 'التربية البدنية والصحية',
        'pkn' => 'التربية المدنية',
        'pkn / ips / seni' => 'التربية المدنية / العلوم الاجتماعية / الفنون',
        'pkn/seni budaya/prakarya/informatika (peminatan)' => 'التربية المدنية / الفنون / الحرف / المعلوماتية (تخصص)',
        'qowaid lughoh' => 'القواعد اللغوية',
        'seni budaya' => 'الفنون الثقافية',
        'shorof' => 'الصرف',
        "ta'bir" => 'التعبير',
        'ta’bir' => 'التعبير',
        'tahfidzul quran' => 'تحفيظ القرآن',
        "tahfidzul qur'an" => 'تحفيظ القرآن',
        'tahfidzul qur’an' => 'تحفيظ القرآن',
        'ushul fiqh' => 'أصول الفقه',
    ];

    public static function resolve(?string $name): ?string
    {
        $key = self::normalize((string) $name);

        if ($key === '') {
            return null;
        }

        return self::NAMES[$key] ?? null;
    }

    private static function normalize(string $name): string
    {
        $key = mb_strtolower(trim($name));
        $key = str_replace(['’', '‘'], "'", $key);
        $key = preg_replace('/\s+/', ' ', $key) ?? $key;

        return $key;
    }
}
