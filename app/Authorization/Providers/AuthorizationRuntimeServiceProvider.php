<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionCacheManager;
use App\Authorization\Contracts\SnapshotRepository;
use App\Authorization\Contracts\SnapshotResolver;
use App\Authorization\Events\PermissionCacheInvalidated;
use App\Authorization\Listeners\PermissionCacheInvalidationListener;
use App\Authorization\Repositories\EloquentSnapshotRepository;
use App\Authorization\Services\AuthorizationManager;
use App\Authorization\Services\SnapshotRebuildService;
use App\Authorization\Services\SnapshotResolver as SnapshotResolverImpl;
use App\Authorization\Support\AuthorizationGateRegistrar;
use App\Authorization\Support\PermissionCacheManager as PermissionCacheManagerImpl;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthorizationRuntimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../../config/authorization.php',
            'authorization',
        );

        $this->app->singleton(PermissionCacheManager::class, function ($app) {
            $config = $app['config']->get('authorization');
            $store = $config['cache_store'] ?? null;

            return new PermissionCacheManagerImpl(
                cache: $app->make(CacheManager::class),
                ttl: (int) ($config['cache_ttl'] ?? 600),
                prefix: (string) ($config['cache_prefix'] ?? 'user_permissions'),
                useTags: (bool) ($config['use_cache_tags'] ?? true),
            );
        });

        $this->app->singleton(SnapshotRepository::class, EloquentSnapshotRepository::class);

        $this->app->singleton(SnapshotRebuildService::class, function ($app) {
            return new SnapshotRebuildService(
                builder: $app->make(\App\Authorization\Contracts\PermissionBuilder::class),
                repository: $app->make(SnapshotRepository::class),
                events: $app->make(Dispatcher::class),
                cache: $app->make(\App\Authorization\Contracts\PermissionCacheManager::class),
            );
        });

        $this->app->singleton(SnapshotResolver::class, function ($app) {
            $config = $app['config']->get('authorization');

            return new SnapshotResolverImpl(
                cache: $app->make(PermissionCacheManager::class),
                repository: $app->make(SnapshotRepository::class),
                rebuildService: $app->make(SnapshotRebuildService::class),
                events: $app->make(Dispatcher::class),
                emitEvents: (bool) ($config['emit_events'] ?? true),
                snapshotTtl: (int) ($config['snapshot_ttl'] ?? 3600),
            );
        });

        $this->app->singleton(AuthorizationManager::class, function ($app) {
            $config = $app['config']->get('authorization');

            return new AuthorizationManager(
                resolver: $app->make(SnapshotResolver::class),
                events: $app->make(Dispatcher::class),
                emitEvents: (bool) ($config['emit_events'] ?? true),
            );
        });

        $this->app->singleton(AuthorizationGateRegistrar::class, function ($app) {
            return new AuthorizationGateRegistrar(
                manager: $app->make(AuthorizationManager::class),
            );
        });

        $this->app->singleton(\App\Authorization\Support\PermissionRebuildObserver::class, function ($app) {
            return new \App\Authorization\Support\PermissionRebuildObserver;
        });

        $this->app->singleton(\App\Authorization\Support\AuthorizationBladeCompiler::class);
    }

    public function boot(): void
    {
        $config = $this->app['config']->get('authorization');

        if (($config['gate_enabled'] ?? true) === true) {
            $registrar = $this->app->make(AuthorizationGateRegistrar::class);
            $registrar->register($this->app->make(Gate::class));
        }

        $this->registerObservers($config);
        $this->registerBladeDirectives();
        $this->registerEventListeners();
        $this->bindRequestContext();
    }

    /**
     * Bind Eloquent observers for permission-bearing models.
     */
    private function registerObservers(array $config): void
    {
        if (($config['observers_enabled'] ?? true) !== true) {
            return;
        }

        $observer = $this->app->make(\App\Authorization\Support\PermissionRebuildObserver::class);

        $models = [
            \App\Models\User::class,
            \App\Models\Permission::class,
            \App\Authorization\Models\RevokedPermission::class,
        ];

        if (class_exists(\App\Models\GTKEmployment::class)) {
            $models[] = \App\Models\GTKEmployment::class;
        }

        foreach ($models as $modelClass) {
            $modelClass::observe($observer);
        }
    }

    /**
     * Bind OrganizationContext as a per-request scoped singleton.
     *
     * The value is set by middleware (or setContextByUser helper).
     * Defaults to null so first read throws (fail-closed).
     */
    private function bindRequestContext(): void
    {
        $this->app->singleton(\App\Authorization\ValueObjects\OrganizationContext::class, function () {
            // Null schoolId signals "no tenant context resolved yet".
            // BindOrganizationContext middleware (or equivalent) must call
            // app()->instance(OrganizationContext::class, ...) with a real
            // context before any tenant-aware handler executes.
            // We deliberately do NOT use sentinel strings ('unknown', 'global')
            // here — null propagates through hasValidSchool() / currentSchoolId()
            // and produces a correct fail-closed response.
            return new \App\Authorization\ValueObjects\OrganizationContext(
                schoolId: null,
                academicYearId: (string) config('authorization.default_academic_year_id', 'unknown'),
                roleDimension: 'default',
            );
        });
    }

    /**
     * Register the @permission and @endpermission Blade directives.
     */
    private function registerBladeDirectives(): void
    {
        $bladeCompiler = app(\Illuminate\View\Compilers\BladeCompiler::class);
        $compiler = $this->app->make(\App\Authorization\Support\AuthorizationBladeCompiler::class);
        $compiler->register($bladeCompiler);
    }

    /**
     * Register event listeners for authorization events.
     */
    private function registerEventListeners(): void
    {
        Event::listen(
            PermissionCacheInvalidated::class,
            PermissionCacheInvalidationListener::class,
        );
    }
}
