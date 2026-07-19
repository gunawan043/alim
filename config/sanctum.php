<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. If this value is null, personal access tokens do
    | not expire. This won't tweak the lifetime of first-party sessions.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may change the middleware listed below as required.
    |
    */

    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Expiration (config-driven)
    |--------------------------------------------------------------------------
    |
    | Centralised, configurable TTLs for personal access tokens. NO domain
    | (controller / migration / seeder) is permitted to hardcode TTL values.
    |
    | Values may be int (minutes) or null (no expiration).
    |
    */

    'token_expiration' => [
        'mobile_default' => env('SANCTUM_MOBILE_TOKEN_EXPIRATION_MINUTES', 60 * 24 * 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Name Format (structured, server-generated)
    |--------------------------------------------------------------------------
    |
    | Authoritative column layout used by App\Support\TokenName. The server
    | builds the token name from request metadata — clients NEVER supply it.
    |
    |   <surface>:<client-kind>:<channel>:<platform>:<device-fingerprint>
    |
    | Example: "mobile:user:password:android:fp_a1b2c3d4"
    |
    */

    'token_name_format' => [
        'parts' => ['surface', 'client_kind', 'channel', 'platform', 'device_fingerprint'],
        'regex' => '/^[a-z0-9_-]{1,32}(:[a-z0-9_-]{1,32}){4}$/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain-Based Abilities (ADR-018)
    |--------------------------------------------------------------------------
    |
    | Reusable, application-agnostic abilities. Designed to support future
    | clients without redesign — abilities are tied to DOMAINS, not to
    | application names. App\Support\AbilityRegistry reads this map and
    | resolves a User's abilities via User::effectiveRoles().
    |
    | Wildcards supported: "*" grants all abilities; "<domain>.*" grants all
    | abilities within that domain (e.g. "attendance.*").
    |
    */

    'abilities' => [

        // ── Catalogue of valid ability strings ──────────────────────────────
        'catalog' => [
            'attendance.read',
            'attendance.write',
            'grades.read',
            'grades.write',
            'permit.create',
            'permit.read',
            'permit.approve',
            'health.read',
            'health.write',
            'notification.read',
            'notification.write',
            'dashboard.read',
            'profile.read',
            'profile.write',
        ],

        // ── Role -> abilities mapping ────────────────────────────────────────
        'roles' => [

            'wali' => [
                'attendance.read',
                'grades.read',
                'permit.create',
                'permit.read',
                'health.read',
                'notification.read',
                'notification.write',
                'dashboard.read',
                'profile.read',
                'profile.write',
            ],

            'musyrif' => [
                'attendance.read',
                'attendance.write',
                'grades.read',
                'permit.read',
                'permit.approve',
                'health.read',
                'notification.read',
                'notification.write',
                'dashboard.read',
                'profile.read',
                'profile.write',
            ],

            'guru' => [
                'attendance.read',
                'attendance.write',
                'grades.read',
                'grades.write',
                'permit.read',
                'health.read',
                'notification.read',
                'notification.write',
                'dashboard.read',
                'profile.read',
                'profile.write',
            ],

            'admin' => ['*'],

            'super-admin' => ['*'],
        ],

        // ── Default abilities for users with no recognised role ────────────
        'default' => ['profile.read', 'profile.write'],
    ],

];
