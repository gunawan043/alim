<?php

namespace App\Domain\Types;

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
            self::WEEKLY => now()->startOfWeek(),
            self::MONTHLY => now()->firstOfMonth(),
            self::SEMESTER => \App\Models\AcademicYear::where('is_active', true)->first()
                ?->semester_starts
                ?? now()->startOfYear(),
            self::YEARLY => now()->startOfYear(),
            self::DAILY => now()->startOfDay(),
            default => now()->firstOfMonth(),
        };
    }

    public static function isValid(string $period): bool
    {
        return in_array($period, [self::WEEKLY, self::MONTHLY, self::SEMESTER, self::YEARLY, self::DAILY]);
    }
}
