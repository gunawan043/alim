<?php

namespace App\Domain\Types;

use App\Models\AcademicYear;
use Carbon\CarbonImmutable;

final class QuotaPeriod
{
    public const WEEKLY = 'weekly';

    public const MONTHLY = 'monthly';

    public const SEMESTER = 'semester';

    public const YEARLY = 'yearly';

    public const DAILY = 'daily';

    public static function rangeBound(string $period): CarbonImmutable
    {
        return match ($period) {
            self::WEEKLY => CarbonImmutable::now()->startOfWeek(),
            self::MONTHLY => CarbonImmutable::now()->startOfMonth(),
            self::SEMESTER => self::semesterStart(),
            self::YEARLY => CarbonImmutable::now()->startOfYear(),
            self::DAILY => CarbonImmutable::now()->startOfDay(),
            default => CarbonImmutable::now()->startOfMonth(),
        };
    }

    /**
     * Awal semester aktif; defensif terhadap tipe data semester_starts.
     */
    private static function semesterStart(): CarbonImmutable
    {
        $value = AcademicYear::where('is_active', true)->value('semester_starts');

        if (is_string($value) && trim($value) !== '') {
            try {
                return CarbonImmutable::parse($value);
            } catch (\Throwable) {
                // jatuh ke fallback di bawah
            }
        }

        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        return CarbonImmutable::now()->startOfYear();
    }

    public static function isValid(string $period): bool
    {
        return in_array($period, [self::WEEKLY, self::MONTHLY, self::SEMESTER, self::YEARLY, self::DAILY]);
    }
}
