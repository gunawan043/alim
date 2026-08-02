<?php

declare(strict_types=1);

namespace App\Authorization\Support;

final class CanonicalHasher
{
    public static function sha256(string $canonical): string
    {
        return hash('sha256', $canonical);
    }
}
