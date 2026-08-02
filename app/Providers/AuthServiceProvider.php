<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\GtkProfile::class => \App\Policies\GtkProfilePolicy::class,
        \App\Models\Dormitory::class => \App\Policies\DormitoryPolicy::class,
        \App\Models\Kaldik::class => \App\Policies\KaldikPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, string $ability) {
            if ($user instanceof \App\Models\User && $user->isSystemAdmin()) {
                return true;
            }

            return null;
        });

        Gate::define('sarpras-access-building', [\App\Policies\SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-access-room', [\App\Policies\SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-access-asset', [\App\Policies\SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-access-loan', [\App\Policies\SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-access-procurement', [\App\Policies\SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-access-booking', [\App\Policies\SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-access-maintenance', [\App\Policies\SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-view-all', [\App\Policies\SarprasWorkspacePolicy::class, 'viewAll']);
        Gate::define('sarpras-create', [\App\Policies\SarprasWorkspacePolicy::class, 'create']);
        Gate::define('sarpras-update', [\App\Policies\SarprasWorkspacePolicy::class, 'update']);
        Gate::define('sarpras-delete', [\App\Policies\SarprasWorkspacePolicy::class, 'delete']);

        Gate::define('gtk-workspace-view', [\App\Policies\GtkWorkspacePolicy::class, 'view']);
        Gate::define('gtk-workspace-create', [\App\Policies\GtkWorkspacePolicy::class, 'create']);
        Gate::define('gtk-workspace-update', [\App\Policies\GtkWorkspacePolicy::class, 'update']);
        Gate::define('gtk-workspace-delete', [\App\Policies\GtkWorkspacePolicy::class, 'delete']);
    }
}
