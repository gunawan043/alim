<?php

namespace App\Services\Uks;

class UksMode
{
    public const SIMPLE = 'simple';

    public const CLINIC = 'clinic';

    public static function current(): string
    {
        $mode = (string) setting('uks_mode', self::SIMPLE);

        return in_array($mode, [self::SIMPLE, self::CLINIC], true) ? $mode : self::SIMPLE;
    }

    public static function isSimple(): bool
    {
        return self::current() === self::SIMPLE;
    }

    public static function isClinic(): bool
    {
        return self::current() === self::CLINIC;
    }
}
