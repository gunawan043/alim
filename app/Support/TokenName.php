<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Server-authoritative generator for Sanctum personal_access_tokens.name.
 *
 * Tokens are addressed exclusively by their SHA-256 hash; the .name column
 * exists for human-facing audit / revocation UX. Because of that, .name MUST
 * never be trusted from the wire and MUST be shaped in a way that is
 * recognisable in logs.
 *
 * Format enforced via config('sanctum.token_name_format'):
 *
 *     <surface>:<client_kind>:<channel>:<platform>:<device-fingerprint>
 *
 * Example: "mobile:user:password:android:fp_a1b2c3d4"
 *
 * TokenSesiController (admin surface, pre-Sprint 1) uses a different
 * surface ("admin") and continues to coexist — see ADR-018.
 */
final class TokenName
{
    public const SURFACE_MOBILE = 'mobile';

    public const SURFACE_ADMIN = 'admin';

    public const CHANNEL_PASSWORD = 'password';

    public const CHANNEL_GOOGLE = 'google';

    public const CHANNEL_OTP = 'otp';

    /**
     * Build the canonical token name for a mobile-issued token.
     *
     * @param  string  $clientKind  "user" for end-user clients; reserved for future agents.
     * @param  string  $channel  One of TokenName::CHANNEL_*
     * @param  string  $platform  "android" | "ios" | "web" | "unknown"
     * @param  string  $deviceFp  Short, sanitised device fingerprint (max 32 chars).
     */
    public static function mobile(
        string $clientKind,
        string $channel,
        string $platform,
        string $deviceFp,
    ): string {
        $parts = [
            self::SURFACE_MOBILE,
            self::segment($clientKind, 'user'),
            self::segment($channel, self::CHANNEL_PASSWORD),
            self::segment($platform, 'unknown'),
            self::segment($deviceFp, 'fp_unknown'),
        ];

        $name = implode(':', $parts);

        $regex = config('sanctum.token_name_format.regex');

        if (is_string($regex) && ! preg_match($regex, $name)) {
            return self::SURFACE_MOBILE.':user:'.self::CHANNEL_PASSWORD.':unknown:fp_unknown';
        }

        return $name;
    }

    /**
     * Build the canonical token name for an admin-issued token (SuperAdmin
     * surface). Format: "admin:<client_kind>:<channel>:<platform>:<fingerprint>".
     *
     * Uses the same 5-segment shape as mobile tokens so the .name column
     * stays uniformly parseable for audit UX (see platformFromName()).
     */
    public static function admin(
        string $clientKind,
        string $channel,
        string $platform,
        string $deviceFp,
    ): string {
        $parts = [
            self::SURFACE_ADMIN,
            self::segment($clientKind, 'admin'),
            self::segment($channel, self::CHANNEL_PASSWORD),
            self::segment($platform, 'web'),
            self::segment($deviceFp, 'fp_unknown'),
        ];

        $name = implode(':', $parts);

        $regex = config('sanctum.token_name_format.regex');

        if (is_string($regex) && ! preg_match($regex, $name)) {
            return self::SURFACE_ADMIN.':admin:'.self::CHANNEL_PASSWORD.':web:fp_unknown';
        }

        return $name;
    }

    /**
     * Extract the platform segment (4th colon-segment) from a canonical
     * 5-segment token name. Returns "unknown" if the name does not match
     * the expected shape. Used by session-descriptor and audit UX.
     */
    public static function platformFromName(?string $name): string
    {
        if (! is_string($name) || $name === '') {
            return 'unknown';
        }

        $segments = explode(':', $name);

        if (count($segments) !== 5) {
            return 'unknown';
        }

        return $segments[3] !== '' ? $segments[3] : 'unknown';
    }

    /**
     * Best-effort extraction of platform from a User-Agent string. Falls
     * back to "unknown". Never trusts user-supplied device strings.
     */
    public static function platformFromRequest(?Request $request): string
    {
        if ($request === null) {
            return 'unknown';
        }

        $ua = strtolower($request->userAgent() ?? '');

        if ($ua === '') {
            return 'unknown';
        }

        if (str_contains($ua, 'android')) {
            return 'android';
        }
        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ios')) {
            return 'ios';
        }
        if (str_contains($ua, 'okhttp') || str_contains($ua, 'dart/')) {
            return 'android';
        }
        if (str_contains($ua, 'expo') || str_contains($ua, 'reactnative')) {
            return 'reactnative';
        }
        if (str_contains($ua, 'mozilla') || str_contains($ua, 'chrome') || str_contains($ua, 'safari')) {
            return 'web';
        }

        return 'unknown';
    }

    /**
     * Produce a short, sanitised identifier from a candidate string. Empty
     * or unsafe input returns the supplied fallback.
     */
    private static function segment(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return $fallback;
        }

        $value = preg_replace('/[^a-z0-9_-]/', '', $value) ?? '';

        if ($value === '') {
            return $fallback;
        }

        return substr($value, 0, 32);
    }
}
