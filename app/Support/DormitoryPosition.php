<?php

namespace App\Support;

class DormitoryPosition
{
    public const KEPALA = 'kepala_asrama';

    public const WAKIL = 'wakil_kepala_asrama';

    public const KOORDINATOR = 'koordinator';

    public const STAF = 'staf_asrama';

    public const WALI_KAMAR = 'wali_kamar';

    public const STAF_PERIZINAN = 'staf_perizinan';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::KEPALA => 'Kepala Asrama',
            self::WAKIL => 'Wakil Kepala Asrama',
            self::KOORDINATOR => 'Koordinator',
            self::STAF => 'Staf Asrama',
            self::WALI_KAMAR => 'Wali Kamar',
            self::STAF_PERIZINAN => 'Staf Perizinan',
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::options());
    }

    public static function label(?string $position): string
    {
        return self::options()[$position] ?? 'Staf Asrama';
    }

    /**
     * @return array<string, string>
     */
    public static function duties(): array
    {
        return [
            'kebersihan' => 'Kebersihan & Kerapian',
            'keamanan' => 'Keamanan & Ketertiban',
            'perizinan' => 'Perizinan & Penjemputan',
            'kesehatan' => 'Kesehatan & UKS',
            'kegiatan' => 'Kegiatan & Pembinaan',
            'tahfidz' => 'Tahfidz & Ibadah',
            'sarpras' => 'Sarana Prasarana',
            'administrasi' => 'Administrasi & Laporan',
            'konsumsi' => 'Konsumsi',
            'lainnya' => 'Tugas Lainnya',
        ];
    }

    /**
     * @return list<string>
     */
    public static function dutyKeys(): array
    {
        return array_keys(self::duties());
    }

    public static function dutyLabel(string $duty): string
    {
        return self::duties()[$duty] ?? ucfirst(str_replace('_', ' ', $duty));
    }

    /**
     * @return array<string, string>
     */
    public static function shiftOptions(): array
    {
        return DormitoryShift::options()
            + ['semua' => ['label' => 'Semua Shift', 'time' => 'Fleksibel']];
    }

    public static function shiftLabel(?string $shift): string
    {
        if (! $shift) {
            return '—';
        }

        $options = self::shiftOptions();

        return isset($options[$shift])
            ? $options[$shift]['label'].' ('.$options[$shift]['time'].')'
            : ucfirst($shift);
    }

    public static function fromEmployment(?string $jabatan): string
    {
        $normalized = mb_strtolower(trim((string) $jabatan));

        return match (true) {
            str_contains($normalized, 'wakil') && str_contains($normalized, 'asrama') => self::WAKIL,
            str_contains($normalized, 'kepala') && str_contains($normalized, 'asrama') => self::KEPALA,
            str_contains($normalized, 'wali kamar') => self::WALI_KAMAR,
            str_contains($normalized, 'perizinan') => self::STAF_PERIZINAN,
            str_contains($normalized, 'koordinator') => self::KOORDINATOR,
            default => self::STAF,
        };
    }
}
