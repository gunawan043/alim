<?php

namespace App\Exceptions;

use RuntimeException;

class PlacementLifecycleException extends RuntimeException
{
    public static function activePeriodExists(string $userId, string $workUnitId, string $startDate): self
    {
        return new self(sprintf(
            'Penempatan aktif sudah ada untuk user %s pada unit %s mulai %s.',
            $userId,
            $workUnitId,
            $startDate
        ));
    }

    public static function overlap(string $userId, string $workUnitId, string $startDate, ?string $endDate): self
    {
        return new self(sprintf(
            'Periode penempatan bertentangan untuk user %s pada unit %s (%s s.d. %s).',
            $userId,
            $workUnitId,
            $startDate,
            $endDate ?? 'sekarang'
        ));
    }

    public static function placementNotFound(string $userId, string $workUnitId): self
    {
        return new self(sprintf(
            'Penempatan tidak ditemukan untuk user %s pada unit %s.',
            $userId,
            $workUnitId
        ));
    }

    public static function invalidPeriod(string $message): self
    {
        return new self($message);
    }
}
