<?php

namespace App\Support;

use App\Models\TeacherAdminBook;

/**
 * Aturan kapan Nilai Murni STS/SAS tidak wajib diisi.
 *
 * Pada mapel tertentu nilai murni (nilai ujian tanpa elemen lain) tidak
 * relevan, sehingga kolom STS/SAS boleh langsung diisi tanpa gerbang
 * "isi Nilai Murni terlebih dahulu".
 */
class NilaiMurniRule
{
    /**
     * Mapel yang nilai murninya tidak wajib.
     *
     * - Tahfidz (termasuk Tahfiz/Tahfidzh)
     * - Hadits / Hadis / Hadist
     * - Bahasa Inggris khusus kelas 1–3 SD
     */
    public static function optionalFor(?string $subjectName, ?string $schoolLevel, ?int $level): bool
    {
        $name = mb_strtolower(trim((string) $subjectName));

        if ($name === '') {
            return false;
        }

        // Seragamkan tanda baca agar pencocokan kata tidak meleset.
        $name = str_replace(['’', "'", '`', '-', '_', '/'], ' ', $name);
        $name = (string) preg_replace('/\s+/', ' ', $name);

        // Tahfidz, Tahfidzul Qur'an, Tahfiz, Tahfidzh, dst.
        if (preg_match('/\btahfi(?:dzh|dz|z)/', $name)) {
            return true;
        }

        if (preg_match('/\bha(?:di|dhi)(?:ts|st|s)\b/', $name)) {
            return true;
        }

        $isEnglish = (bool) preg_match('/\binggris\b|\benglish\b/', $name);

        return $isEnglish
            && strtolower((string) $schoolLevel) === 'sd'
            && in_array($level, [1, 2, 3], true);
    }

    /**
     * Versi ringkas untuk buku nilai (admin book) beserta rombelnya.
     */
    public static function optionalForAdminBook(?TeacherAdminBook $book): bool
    {
        if (! $book) {
            return false;
        }

        $group = $book->studyGroup;

        return self::optionalFor(
            $book->subject?->name,
            $group?->school?->school_level,
            $group?->gradeLevel?->level ?? (int) ($group?->name ?? 0),
        );
    }
}
