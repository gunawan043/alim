<?php

namespace App\Services\Kurikulum;

use App\Models\PerangkatPembelajaran;
use App\Models\Subject;

/**
 * Menentukan format cetak perangkat berdasarkan JENIS MAPEL, bukan profil
 * Dinas/Kemenag dan bukan pilihan manual pengguna.
 *
 * - umum    : mapel umum, termasuk Bahasa Indonesia dan Bahasa Inggris.
 * - agama   : mapel agama dan mapel kebahasa-araban.
 * - tahfidz : diarahkan ke modul RPP Tahfizh (tidak dicetak sebagai RPM).
 */
class PerangkatFormatResolver
{
    public const UMUM = 'umum';

    public const AGAMA = 'agama';

    public const TAHFIDZ = 'tahfidz';

    private const ARABIC_PATTERNS = [
        'bahasa arab', 'b. arab', 'b.arab', 'b arab',
        'nahwu', 'sharaf', 'shorof', 'balaghah', 'balagoh',
        'qowaid', 'qawaid', 'imla', 'muthalaah', 'mutholaah',
        'insya', 'ta\'bir', 'ta’bir', 'muhadatsah', 'mufradat',
        'qiroatul', 'qiraah', 'qira\'ah', 'tashrif',
    ];

    private const ENGLISH_PATTERNS = [
        'bahasa inggris', 'b. inggris', 'b.inggris', 'b inggris',
        'english',
    ];

    private const TAHFIDZ_PATTERNS = [
        'tahfidz', 'tahfiz', 'hafalan', 'tahfidzul', 'tahfizh',
        'iqro', 'iqra', 'tajwid',
    ];

    public function resolve(Subject|string|null $subject): string
    {
        if (! $subject instanceof Subject) {
            $subject = $subject ? Subject::find($subject) : null;
        }

        if (! $subject) {
            return self::UMUM;
        }

        $group = strtolower((string) ($subject->subjectGroup?->code ?? ''));
        $name = mb_strtolower(trim((string) $subject->name));

        if ($group === 'tahfidz') {
            return self::TAHFIDZ;
        }

        if ($group === 'agama') {
            return self::AGAMA;
        }

        if ($group === 'bahasa') {
            // Rumpun bahasa berisi Bahasa Arab dan Bahasa Inggris. Bahasa
            // Inggris tetap format umum; mapel berbau Arab masuk format agama.
            if ($this->containsAny($name, self::ENGLISH_PATTERNS)) {
                return self::UMUM;
            }

            return $this->containsAny($name, self::ARABIC_PATTERNS)
                ? self::AGAMA
                : self::UMUM;
        }

        // Fallback untuk data lama yang belum terhubung ke subject group.
        if ($this->containsAny($name, self::TAHFIDZ_PATTERNS)) {
            return self::TAHFIDZ;
        }

        if ($this->containsAny($name, self::ARABIC_PATTERNS)) {
            return self::AGAMA;
        }

        return self::UMUM;
    }

    public function forPerangkat(PerangkatPembelajaran $perangkat): string
    {
        return $this->resolve($perangkat->subject);
    }

    public function label(string $format): string
    {
        return match ($format) {
            self::AGAMA => 'Rencana Pembelajaran Mendalam',
            self::TAHFIDZ => 'RPP Tahfizh',
            default => 'Perencanaan Pembelajaran Mendalam',
        };
    }

    public function isTahfidz(Subject|string|null $subject): bool
    {
        return $this->resolve($subject) === self::TAHFIDZ;
    }

    private function containsAny(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($value, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
