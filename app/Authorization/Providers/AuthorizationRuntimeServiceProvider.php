<?php

declare(strict_types=1);

namespace App\Authorization\Providers;

use App\Authorization\Contracts\PermissionBuilder;
use App\Authorization\Contracts\PermissionCacheManager;
use App\Authorization\Contracts\SnapshotRepository;
use App\Authorization\Contracts\SnapshotResolver;
use App\Authorization\Events\PermissionCacheInvalidated;
use App\Authorization\Listeners\PermissionCacheInvalidationListener;
use App\Authorization\Models\RevokedPermission;
use App\Authorization\Repositories\EloquentSnapshotRepository;
use App\Authorization\Services\AuthorizationManager;
use App\Authorization\Services\SnapshotRebuildService;
use App\Authorization\Services\SnapshotResolver as SnapshotResolverImpl;
use App\Authorization\Support\AuthorizationBladeCompiler;
use App\Authorization\Support\AuthorizationGateRegistrar;
use App\Authorization\Support\PermissionCacheManager as PermissionCacheManagerImpl;
use App\Authorization\Support\PermissionRebuildObserver;
use App\Authorization\ValueObjects\OrganizationContext;
use App\Models\CoordinatorAssignment;
use App\Models\GTKEmployment;
use App\Models\HomeroomAssignment;
use App\Models\Permission;
use App\Models\StructuralAssignment;
use App\Models\User;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

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
                builder: $app->make(PermissionBuilder::class),
                repository: $app->make(SnapshotRepository::class),
                events: $app->make(Dispatcher::class),
                cache: $app->make(PermissionCacheManager::class),
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

        $this->app->singleton(PermissionRebuildObserver::class, function ($app) {
            return new PermissionRebuildObserver;
        });

        $this->app->singleton(AuthorizationBladeCompiler::class);
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

        $observer = $this->app->make(PermissionRebuildObserver::class);

        $models = [
            User::class,
            Permission::class,
            RevokedPermission::class,
        ];

        if (class_exists(GTKEmployment::class)) {
            $models[] = GTKEmployment::class;
        }

        // Assignment models: changes here affect workspace permissions (wali kelas,
        // koordinator rumpun, structural) so rebuild the snapshot immediately.
        $assignmentModels = [
            HomeroomAssignment::class,
            CoordinatorAssignment::class,
            StructuralAssignment::class,
        ];
        foreach ($assignmentModels as $assignmentModel) {
            if (class_exists($assignmentModel)) {
                $models[] = $assignmentModel;
            }
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
        $this->app->singleton(OrganizationContext::class, function () {
            // Null schoolId signals "no tenant context resolved yet".
            // BindOrganizationContext middleware (or equivalent) must call
            // app()->instance(OrganizationContext::class, ...) with a real
            // context before any tenant-aware handler executes.
            // We deliberately do NOT use sentinel strings ('unknown', 'global')
            // here — null propagates through hasValidSchool() / currentSchoolId()
            // and produces a correct fail-closed response.
            return new OrganizationContext(
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
        $bladeCompiler = app(BladeCompiler::class);
        $compiler = $this->app->make(AuthorizationBladeCompiler::class);
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
