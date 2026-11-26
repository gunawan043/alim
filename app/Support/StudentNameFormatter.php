<?php

namespace App\Support;

/**
 * Format nama santri untuk cetak rapor.
 *
 * Pemanggil menyerahkan pilihan yang sudah tersimpan per sekolah; kelas ini
 * hanya menerjemahkannya menjadi teks. Nama berhuruf Arab tidak diubah karena
 * kapitalisasi hanya berlaku untuk huruf Latin.
 */
class StudentNameFormatter
{
    public const AS_IS = 'as_is';

    public const UPPER = 'upper';

    public const TITLE = 'title';

    /**
     * Daftar pilihan untuk form pengaturan.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::AS_IS => 'Sesuai Data',
            self::UPPER => 'HURUF BESAR SEMUA',
            self::TITLE => 'Kapital Di Awal Kata',
        ];
    }

    public static function normalize(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        return array_key_exists($value, self::options()) ? $value : self::AS_IS;
    }

    public static function format(?string $name, ?string $mode): string
    {
        $name = (string) $name;

        if ($name === '') {
            return $name;
        }

        return match (self::normalize($mode)) {
            self::UPPER => self::toUpper($name),
            self::TITLE => self::toTitle($name),
            default => $name,
        };
    }

    /**
     * HURUF BESAR SEMUA — hanya huruf Latin yang diubah.
     */
    private static function toUpper(string $name): string
    {
        return preg_replace_callback('/[a-zA-Z]+/u', fn ($m) => mb_strtoupper($m[0]), $name) ?? $name;
    }

    /**
     * Kapital Di Awal Kata.
     *
     * Kata dianggap diawali huruf Latin yang didahului bukan huruf. Huruf
     * lain di dalam kata diperkecil, sehingga "AHMAD" menjadi "Ahmad" dan
     * "muhammad abdul" menjadi "Muhammad Abdul".
     */
    private static function toTitle(string $name): string
    {
        return preg_replace_callback(
            '/(^|[^a-zA-Z])([a-zA-Z]+)/u',
            fn ($m) => $m[1].mb_strtoupper(mb_substr($m[2], 0, 1)).mb_strtolower(mb_substr($m[2], 1)),
            $name
        ) ?? $name;
    }
}
