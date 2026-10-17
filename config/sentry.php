<?php

/**
 * Sentry Configuration — Add to Laravel app for crash reporting.
 *
 * Install: composer require sentry/sentry-laravel
 * Then run: php artisan sentry:publish
 */

declare(strict_types=1);

return [
    'dsn' => env('SENTRY_DSN'),

    // Capture 5% of traffic as sample (adjust as needed)
    'sample_rate' => env('SENTRY_SAMPLE_RATE', 0.05),

    // Transaction sample rate — 10% of requests traced
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    // Measure performance of database queries
    'monitoring_tags_keys' => [
        'user.id',
        'user.role',
        'organization.name',
        'sekolah.name',
    ],

    // Send user ID & role to Sentry (no emails/passwords!)
    'send_default_pii' => false,

    // Default tags for all events
    'tags' => [
        'env' => env('APP_ENV', 'local'),
        'version' => config('app.version', '1.0.0'),
    ],

    // Filter sensitive headers - handled in app service provider for serializability
    // 'before_send' is not allowed in config files because Laravel serializes config into a single PHP file
];
