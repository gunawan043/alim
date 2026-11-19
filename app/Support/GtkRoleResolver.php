<?php

declare(strict_types=1);

namespace App\Support;

use App\Helpers\JabatanRoleMapper;
use App\Models\Role;
use App\Models\StructuralPosition;
use App\Models\WorkUnit;

/**
 * Menentukan role GTK dari jabatan dan satuan kerjanya.
 *
 * Setiap GTK wajib punya role resmi: user tanpa role akan diarahkan ke
 * halaman "Verifikasi Akun" saat pertama login, padahal datanya sudah ada.
 */
class GtkRoleResolver
{
    public static function resolve(?string $jabatan, ?string $jabatanId = null, ?string $workUnitId = null): ?string
    {
        $fromPosition = self::fromPosition($jabatan, $jabatanId);

        if ($fromPosition) {
            return $fromPosition;
        }

        if (trim((string) $jabatan) !== '') {
            // Hanya pakai hasil yang benar-benar dikenali; fallback generic
            // jangan menutupi kecocokan dari satuan kerja.
            $mapped = JabatanRoleMapper::resolveSpecific($jabatan);

            // Role harus benar-benar ada di tabel roles; kalau tidak, biarkan
            // akun tanpa role daripada menggagalkan seluruh baris import.
            if ($mapped !== null && JabatanRoleMapper::validate($mapped)
                && Role::where('name', $mapped)->exists()) {
                return $mapped;
            }
        }

        return self::fromWorkUnit($workUnitId) ?? self::defaultRole();
    }

    /**
     * Role yang menempel pada jabatan struktural (paling akurat).
     */
    public static function fromPosition(?string $jabatan, ?string $jabatanId = null): ?string
    {
        if (! empty($jabatanId)) {
            $position = StructuralPosition::with('role')->find($jabatanId);

            if ($position?->role) {
                return $position->role->name;
            }
        }

        if (empty($jabatan)) {
            return null;
        }

        $normalize = fn ($s) => preg_replace('/[^a-z0-9]/', '', strtolower((string) $s));
        $target = $normalize($jabatan);

        $position = StructuralPosition::with('role')
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(LOWER(name), ' ', ''), '/', ''), '-', ''), '.', '') = ?",
                [$target]
            )
            ->first();

        return $position?->role?->name;
    }

    /**
     * Role dari nama satuan kerja, mis. unit "Perpustakaan" -> role "Perpustakaan".
     */
    public static function fromWorkUnit(?string $workUnitId): ?string
    {
        if (! $workUnitId) {
            return null;
        }

        $unitName = WorkUnit::where('id', $workUnitId)->value('name');

        if (! $unitName) {
            return null;
        }

        $unitKey = self::key($unitName);

        if ($unitKey === '') {
            return null;
        }

        $roles = Role::query()->pluck('name');

        // Exact match dulu supaya "Unit Pelayanan Gizi" tidak tertukar.
        foreach ($roles as $role) {
            if (self::key($role) === $unitKey) {
                return $role;
            }
        }

        foreach ($roles as $role) {
            $roleKey = self::key($role);

            if ($roleKey === '' || $role === 'Super Admin') {
                continue;
            }

            if (str_contains($unitKey, $roleKey)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Role default untuk pegawai yang tidak bisa dipetakan dari jabatan/unit.
     */
    public static function defaultRole(): ?string
    {
        return Role::where('name', 'Satuan Pendidikan')->exists() ? 'Satuan Pendidikan' : null;
    }

    private static function key(?string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $value))) ?? '';
    }
}
