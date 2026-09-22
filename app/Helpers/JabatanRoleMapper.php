<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Maps GTK jabatan strings to the 14 official Spatie roles.
 *
 * Strict constraint: only these 14 role names are ever produced.
 * Any unrecognized jabatan falls back to 'Satuan Pendidikan'.
 *
 * Order matters: more specific patterns are checked before generic ones
 * to prevent shadowing (e.g. "Staf Tata Usaha" → Keuangan before "tata usaha" → Asrama).
 */
class JabatanRoleMapper
{
    private const OFFICIAL_ROLES = [
        'Super Admin',
        'Pimpinan',
        'Satuan Pendidikan',
        'Asrama',
        'UKS',
        'Departemen Tahfidz',
        'Departemen Bahasa',
        'Perpustakaan',
        'Satuan Keamanan',
        'Humas Personalia',
        'Unit Rumah Tangga',
        'Keuangan',
        'Teknologi Informasi',
        'Unit Pelayanan Gizi',
    ];

    /**
     * Normalize a jabatan string for comparison: trim, lowercase, collapse whitespace.
     */
    public static function normalize(string $jabatan): string
    {
        return preg_replace('/\s+/', ' ', trim(strtolower($jabatan)));
    }

    /**
     * Resolve a jabatan string to one of the 14 official role names.
     *
     * @return string One of the 14 official roles, never anything else.
     */
    public static function resolve(string $jabatan): string
    {
        $normalized = self::normalize($jabatan);

        // ── Super Admin ───────────────────────────────────────────────────
        if ($normalized === 'system administrator') {
            return 'Super Admin';
        }

        // ── Pimpinan ──────────────────────────────────────────────────────
        if (in_array($normalized, ['mudir', 'wadir 1', 'wadir 2'], true)) {
            return 'Pimpinan';
        }
        if (str_starts_with($normalized, 'kepala satuan pendidikan')
            || str_starts_with($normalized, 'wakil kepala satuan pendidikan')) {
            return 'Satuan Pendidikan';
        }
        if (str_starts_with($normalized, 'guru umum')
            || str_starts_with($normalized, 'guru kelas')
            || str_starts_with($normalized, 'guru agama')
            || str_starts_with($normalized, 'guru hadits')
            || str_starts_with($normalized, 'guru bahasa arab')
            || str_starts_with($normalized, 'guru tahfidz')) {
            return 'Satuan Pendidikan';
        }

        // ── Asrama — specific checks before generic "tata usaha" catch-all ─
        if (str_starts_with($normalized, 'kepala asrama')
            || str_starts_with($normalized, 'wakil kepala asrama')
            || str_starts_with($normalized, 'musrif')
            || str_starts_with($normalized, 'musyrif')
            || str_starts_with($normalized, 'musrifah')
            || str_starts_with($normalized, 'musyrifah')
            || str_starts_with($normalized, 'tata usaha asrama')
            || str_starts_with($normalized, 'staf perizinan')
            || str_starts_with($normalized, 'perizinan')) {
            return 'Asrama';
        }

        // ── UKS ───────────────────────────────────────────────────────────
        if (str_starts_with($normalized, 'kepala uks')
            || str_starts_with($normalized, 'staf uks')
            || str_starts_with($normalized, 'admin uks')) {
            return 'UKS';
        }

        // ── Departemen Tahfidz ────────────────────────────────────────────
        if (str_starts_with($normalized, 'kepala departemen tahfidz')
            || str_starts_with($normalized, 'wakil kepala departemen tahfidz')
            || str_starts_with($normalized, 'tata usaha departemen tahfidz')) {
            return 'Departemen Tahfidz';
        }

        // ── Departemen Bahasa ─────────────────────────────────────────────
        if (str_starts_with($normalized, 'kepala departemen bahasa')
            || str_starts_with($normalized, 'wakil kepala departemen bahasa')
            || str_starts_with($normalized, 'tata usaha departemen bahasa')) {
            return 'Departemen Bahasa';
        }

        // ── Perpustakaan ──────────────────────────────────────────────────
        if (str_starts_with($normalized, 'koordinator perpustakaan')
            || str_starts_with($normalized, 'staf perpustakaan')) {
            return 'Perpustakaan';
        }

        // ── Satuan Keamanan ───────────────────────────────────────────────
        if (str_starts_with($normalized, 'kepala satuan keamanan')
            || str_starts_with($normalized, 'koordinator satuan keamanan')
            || str_starts_with($normalized, 'anggota satuan keamanan')) {
            return 'Satuan Keamanan';
        }

        // ── Humas Personalia ──────────────────────────────────────────────
        if (str_starts_with($normalized, 'kepala humas & personalia')
            || str_starts_with($normalized, 'kepala humas')
            || str_starts_with($normalized, 'kepala personalia')
            || str_starts_with($normalized, 'staf humas')
            || str_starts_with($normalized, 'staf personalia')) {
            return 'Humas Personalia';
        }

        // ── Unit Rumah Tangga ─────────────────────────────────────────────
        if (str_starts_with($normalized, 'kepala unit rumah tangga')
            || str_starts_with($normalized, 'koordinator sarpras')
            || str_starts_with($normalized, 'tata usaha urt')
            || str_starts_with($normalized, 'staf urt')
            || str_starts_with($normalized, 'koordinator kebersihan')
            || str_starts_with($normalized, 'staf kebersihan')) {
            return 'Unit Rumah Tangga';
        }

        // ── Keuangan — check specific before generic "tata usaha" ─────────
        if (str_starts_with($normalized, 'kepala tata usaha')
            || str_starts_with($normalized, 'staf tata usaha')
            || str_starts_with($normalized, 'bendahara sekolah')
            || str_starts_with($normalized, 'kepala keuangan')
            || str_starts_with($normalized, 'staf keuangan')
            || $normalized === 'bendahara') {
            return 'Keuangan';
        }

        // ── Teknologi Informasi ───────────────────────────────────────────
        if (str_starts_with($normalized, 'kepala unit teknologi informasi')
            || str_starts_with($normalized, 'staf teknologi informasi')
            || str_starts_with($normalized, 'teknisi jaringan')) {
            return 'Teknologi Informasi';
        }

        // ── Unit Pelayanan Gizi ───────────────────────────────────────────
        if (str_starts_with($normalized, 'kepala unit gizi')
            || str_starts_with($normalized, 'koordinator logistik')
            || str_starts_with($normalized, 'staf gizi')
            || str_starts_with($normalized, 'staf logistik')) {
            return 'Unit Pelayanan Gizi';
        }

        // ── Fallback ──────────────────────────────────────────────────────
        // Generic "tata usaha" (not otherwise matched) defaults to Asrama,
        // matching JenisGtkSeeder where only "Tata Usaha Asrama" is listed.
        if (str_starts_with($normalized, 'tata usaha')) {
            return 'Asrama';
        }

        return 'Satuan Pendidikan';
    }

    /**
     * Validate that a resolved role name is one of the 14 official roles.
     */
    public static function validate(string $role): bool
    {
        return in_array($role, self::OFFICIAL_ROLES, true);
    }
}
