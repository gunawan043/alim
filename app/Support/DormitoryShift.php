<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DormitoryShift
{
    public const PAGI = 'pagi';

    public const SIANG = 'siang';

    public const MALAM = 'malam';

    /**
     * @return array<string, array{label: string, time: string}>
     */
    public static function options(): array
    {
        return [
            self::PAGI => ['label' => 'Pagi', 'time' => '06.00–14.00'],
            self::SIANG => ['label' => 'Siang', 'time' => '14.00–22.00'],
            self::MALAM => ['label' => 'Malam', 'time' => '22.00–06.00'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::options());
    }

    public static function label(?string $shift): string
    {
        return self::options()[$shift]['label'] ?? ucfirst((string) $shift);
    }

    public static function time(?string $shift): string
    {
        return self::options()[$shift]['time'] ?? '-';
    }

    public static function current(?CarbonInterface $at = null): string
    {
        $hour = (int) ($at ?? Carbon::now())->format('H');

        return match (true) {
            $hour >= 6 && $hour < 14 => self::PAGI,
            $hour >= 14 && $hour < 22 => self::SIANG,
            default => self::MALAM,
        };
    }
}
