<?php

namespace App\Support;

/**
 * Placeholder sederhana untuk catatan rapor.
 *
 * `{nama}` diganti dengan nama santri pada saat cetak, sehingga catatan wali
 * kelas bisa ditulis sekali untuk satu rombel dan tetap menyebut nama santri
 * yang berbeda-beda.
 */
final class NoteTemplate
{
    public const NAME_PLACEHOLDER = '{nama}';

    /**
     * Ganti seluruh placeholder nama. Null/teks kosong dikembalikan apa adanya.
     */
    public static function render(?string $note, ?string $name): ?string
    {
        if ($note === null || $note === '') {
            return $note;
        }

        return str_replace(
            [self::NAME_PLACEHOLDER, '{Nama}', '{NAMA}'],
            (string) ($name ?? ''),
            $note
        );
    }

    /**
     * Apakah catatan memakai placeholder nama.
     */
    public static function hasNamePlaceholder(?string $note): bool
    {
        if ($note === null || $note === '') {
            return false;
        }

        return str_contains($note, self::NAME_PLACEHOLDER)
            || str_contains($note, '{Nama}')
            || str_contains($note, '{NAMA}');
    }
}
