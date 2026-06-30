<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionBuilder;
use App\Authorization\Contracts\PermissionProvider;
use App\Authorization\Contracts\SnapshotRepository;
use App\Authorization\Repositories\EloquentSnapshotRepository;
use App\Authorization\Services\SnapshotRebuildService;
use App\Authorization\Support\EffectivePermissionBuilder;
use App\Authorization\Support\PermissionConflictResolver;
use App\Authorization\Support\PermissionMergeResolver;
use App\Authorization\Support\RevocationResolver;
use App\Authorization\Support\SnapshotFingerprintFactory;
use App\Authorization\Support\SnapshotVersionResolver;
use Illuminate\Support\ServiceProvider;

class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionMergeResolver::class);

        $this->app->singleton(RevocationResolver::class);

        $this->app->singleton(PermissionConflictResolver::class);

        $this->app->singleton(SnapshotFingerprintFactory::class);

        $this->app->bind(SnapshotVersionResolver::class, function ($app) {
            $connection = $app['db']->connection('pgsql');
            return new SnapshotVersionResolver(
                connection: $connection,
                lockTimeoutSeconds: 5,
            );
        });

        $this->app->bind(PermissionBuilder::class, function ($app) {
            $providers = $app->tagged('permission_provider');
            return new EffectivePermissionBuilder(
                providers: $providers,
                mergeResolver: $app->make(PermissionMergeResolver::class),
                revocationResolver: $app->make(RevocationResolver::class),
                conflictResolver: $app->make(PermissionConflictResolver::class),
                fingerprintFactory: $app->make(SnapshotFingerprintFactory::class),
                versionResolver: $app->make(SnapshotVersionResolver::class),
                defaultProvider: 'default',
            );
        });

        $this->app->bind(SnapshotRepository::class, EloquentSnapshotRepository::class);

    public function boot(): void
    {
        $this->discoverProviderTags();
        $this->loadHelpers();
    }

    private function loadHelpers(): void
    {
        $helpersPath = __DIR__ . '/../helpers.php';
        if (is_file($helpersPath)) {
            require_once $helpersPath;
        }
    }

    private function discoverProviderTags(): void
    {
        $classes = [];

        $providerDir = app_path('Authorization/Providers');
        if (! is_dir($providerDir)) {
            return;
        }

        foreach (scandir($providerDir) as $file) {
            if (! str_ends_with($file, 'Provider.php')) {
                continue;
            }

            $class = 'App\\Authorization\\Providers\\' . rtrim($file, '.php');

            if (! class_exists($class)) {
                continue;
            }

            if (is_subclass_of($class, PermissionProvider::class)) {
                $classes[] = $class;
            }
        }

        $this->app->tag($classes, 'permission_provider');
    }
}