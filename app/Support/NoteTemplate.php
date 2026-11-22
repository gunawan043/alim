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

    /** Alias yang diterima: {nama} dan {santri} (case-insensitive). */
    private const NAME_VARIANTS = [
        '{nama}', '{Nama}', '{NAMA}',
        '{santri}', '{Santri}', '{SANTRI}',
    ];

    /**
     * Ganti seluruh placeholder nama. Null/teks kosong dikembalikan apa adanya.
     */
    public static function render(?string $note, ?string $name): ?string
    {
        if ($note === null || $note === '') {
            return $note;
        }

        return str_replace(
            self::NAME_VARIANTS,
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

        foreach (self::NAME_VARIANTS as $placeholder) {
            if (str_contains($note, $placeholder)) {
                return true;
            }
        }

        return false;
    }
}
