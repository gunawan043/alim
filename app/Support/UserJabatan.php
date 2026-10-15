<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Resolver jabatan user yang tangguh.
 *
 * Sumber jabatan memang ambigu di data existing: bisa dari
 * `gtk_employments.jabatan` (string), `gtk_employments.jabatan_id`
 * (relasi `jabatanRel`), dan bisa ada lebih dari satu baris employment.
 * Pola ini menyamakan cara baca dengan AttendancePermissionProvider
 * sehingga gate menu/route tidak lagi salah menolak (403) hanya karena
 * jabatan tersimpan di kolom/relasi yang berbeda.
 */
final class UserJabatan
{
    /**
     * Semua kandidat nama jabatan (lowercase, trimmed) milik user.
     *
     * @return list<string>
     */
    public static function candidates(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $raw = [];

        $employment = $user->employment;
        if ($employment?->jabatanRel?->nama) {
            $raw[] = $employment->jabatanRel->nama;
        }
        if ($employment?->jabatan) {
            $raw[] = $employment->jabatan;
        }

        if (method_exists($user, 'employments')) {
            foreach ($user->employments as $row) {
                if ($row?->jabatanRel?->nama) {
                    $raw[] = $row->jabatanRel->nama;
                }
                if ($row?->jabatan) {
                    $raw[] = $row->jabatan;
                }
            }
        }

        return array_values(array_unique(array_map(
            static fn ($j) => strtolower(trim((string) $j)),
            array_filter($raw, static fn ($j) => $j !== null && $j !== ''),
        )));
    }

    /**
     * Apakah user memiliki salah satu jabatan (perbandingan case-insensitive).
     *
     * @param  list<string>  $allowed
     */
    public static function hasAny(?User $user, array $allowed): bool
    {
        $normalized = array_map(
            static fn ($j) => strtolower(trim((string) $j)),
            $allowed,
        );

        return array_intersect($normalized, self::candidates($user)) !== [];
    }
}
