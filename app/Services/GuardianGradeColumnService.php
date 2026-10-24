<?php

namespace App\Services;

use App\Models\TeacherAdminBook;

/**
 * Kontrak kolom nilai yang boleh dilihat wali santri dan diproses API.
 *
 * Pengaturan disimpan per buku mapel (teacher_admin_books) dan diatur guru
 * pengampu. Null = semua kolom (data lama tetap kompatibel).
 */
class GuardianGradeColumnService
{
    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            'sumatif_harian' => 'Sumatif Harian (S1–S6 + kolom tambahan)',
            'rs' => 'RS',
            'sts_murni' => 'Nilai Murni STS',
            'sts' => 'STS',
            'sas_murni' => 'Nilai Murni SAS',
            'sas' => 'SAS',
            'rsa' => 'RSA',
            'nr_murni' => 'NR Murni',
            'raport_sts' => 'Raport STS',
            'nr_final' => 'NR Final',
            'ket' => 'Keterangan',
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::options());
    }

    /** @return array<int, string> */
    public static function visibleFor(TeacherAdminBook $book): array
    {
        $stored = $book->guardianVisibleColumns();

        if ($stored === null) {
            return self::keys();
        }

        return array_values(array_intersect(self::keys(), $stored));
    }

    /**
     * Filter payload nilai agar hanya berisi kolom yang diizinkan.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function filterPayload(array $payload, TeacherAdminBook $book): array
    {
        $allowed = self::visibleFor($book);

        // Identitas mapel/semester selalu dipertahankan.
        $filtered = array_intersect_key($payload, array_flip([
            'id', 'mata_pelajaran_id', 'subject_name', 'semester',
        ]));

        if (in_array('sumatif_harian', $allowed, true)) {
            foreach (['s1', 's2', 's3', 's4', 's5', 's6', 'sumatif_harian', 'kolom_sumatif'] as $key) {
                if (array_key_exists($key, $payload)) {
                    $filtered[$key] = $payload[$key];
                }
            }
        }

        foreach (['rs', 'sts_murni', 'sts', 'sas_murni', 'sas', 'rsa', 'nr_murni', 'raport_sts', 'nr_final'] as $key) {
            if (in_array($key, $allowed, true) && array_key_exists($key, $payload)) {
                $filtered[$key] = $payload[$key];
            }
        }

        if (in_array('ket', $allowed, true)) {
            if (array_key_exists('ket', $payload)) {
                $filtered['ket'] = $payload['ket'];
            }
            if (array_key_exists('keterangan', $payload)) {
                $filtered['keterangan'] = $payload['keterangan'];
            }
        }

        return $filtered;
    }
}
