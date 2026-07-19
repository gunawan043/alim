<?php

namespace App\Support;

/**
 * Single point of access for personal-access-token TTL values.
 *
 * Sprint 1 rule: NO controller, NO migration, NO seeder may hardcode a
 * token TTL. All TTLs flow through Laravel configuration (config/sanctum.php
 * -> "token_expiration"). This class is the only place that reads that
 * configuration for mobile-issued tokens.
 */
final class TokenExpiration
{
    /**
     * TTL in minutes for a standard mobile-issued personal access token.
     *
     * Returns null when the operator has explicitly disabled expiration
     * for the mobile surface (intentionally opt-in, not the default).
     */
    public static function mobileDefaultMinutes(): ?int
    {
        $minutes = config('sanctum.token_expiration.mobile_default');

        if ($minutes === null) {
            return null;
        }

        if (! is_int($minutes) || $minutes < 1) {
            return 60 * 24 * 30;
        }

        return $minutes;
    }

    /**
     * Earliest expiration instant for a token issued now, suitable for
     * Sanctum's personal_access_tokens.expires_at column.
     */
    public static function mobileDefaultExpiresAt(): ?\DateTimeInterface
    {
        $minutes = self::mobileDefaultMinutes();

        return $minutes === null ? null : now()->addMinutes($minutes);
    }
}
