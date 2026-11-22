<?php

namespace App\Support\Reports;

/**
 * Cakupan data sebuah laporan.
 *
 * Satu laporan hanya boleh membaca data dari cakupan ini:
 * - global    : seluruh pondok (pemegang view_global_school_data, atau unit
 *               yang memang tidak dipetakan ke satuan pendidikan/asrama)
 * - school    : satu satuan pendidikan
 * - dormitory : satu atau beberapa asrama
 * - none      : tidak ada cakupan yang sah, laporan harus kosong
 */
final class ReportScope
{
    public const GLOBAL = 'global';
    public const SCHOOL = 'school';
    public const DORMITORY = 'dormitory';
    public const NONE = 'none';

    /** Penanda sekolah mustahil dipakai agar query mengembalikan nol baris. */
    public const NO_ACCESS = '__none__';

    /**
     * @param  array<int, string>  $dormitoryIds
     */
    public function __construct(
        public readonly string $type,
        public readonly ?string $schoolId = null,
        public readonly array $dormitoryIds = [],
        public readonly ?string $workUnitId = null,
        public readonly string $label = 'Seluruh pondok',
        public readonly ?string $reason = null,
    ) {}

    public static function global(string $label = 'Seluruh pondok'): self
    {
        return new self(self::GLOBAL, label: $label);
    }

    public static function school(string $schoolId, string $label): self
    {
        return new self(self::SCHOOL, schoolId: $schoolId, label: $label);
    }

    /**
     * @param  array<int, string>  $dormitoryIds
     */
    public static function dormitory(array $dormitoryIds, string $label, ?string $workUnitId = null): self
    {
        return new self(self::DORMITORY, dormitoryIds: array_values($dormitoryIds), workUnitId: $workUnitId, label: $label);
    }

    public static function none(string $reason): self
    {
        return new self(self::NONE, label: 'Tidak ada cakupan', reason: $reason);
    }

    public function isGlobal(): bool
    {
        return $this->type === self::GLOBAL;
    }

    public function isSchool(): bool
    {
        return $this->type === self::SCHOOL;
    }

    public function isDormitory(): bool
    {
        return $this->type === self::DORMITORY;
    }

    public function isEmpty(): bool
    {
        return $this->type === self::NONE;
    }

    /**
     * Cakupan yang hanya boleh membaca laporan akademik dan populasi.
     * Laporan itu disusun per satuan pendidikan, bukan per asrama.
     */
    public function allowsAcademicReports(): bool
    {
        return $this->isGlobal() || $this->isSchool();
    }

    /**
     * Nilai untuk parameter `?string $schoolId` pada service laporan.
     * null = semua sekolah, UUID = satu sekolah, NO_ACCESS = tidak ada data.
     */
    public function schoolFilter(): ?string
    {
        if ($this->isGlobal()) {
            return null;
        }

        if ($this->isSchool()) {
            return $this->schoolId;
        }

        return self::NO_ACCESS;
    }
}
