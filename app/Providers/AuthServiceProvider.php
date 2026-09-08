<?php

namespace App\Providers;

use App\Models\Dormitory;
use App\Models\GtkProfile;
use App\Models\Kaldik;
use App\Models\User;
use App\Policies\DormitoryPolicy;
use App\Policies\GtkProfilePolicy;
use App\Policies\GtkWorkspacePolicy;
use App\Policies\KaldikPolicy;
use App\Policies\SarprasWorkspacePolicy;
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
        GtkProfile::class => GtkProfilePolicy::class,
        Dormitory::class => DormitoryPolicy::class,
        Kaldik::class => KaldikPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, string $ability) {
            if ($user instanceof User && $user->isSystemAdmin()) {
                return true;
            }

            return null;
        });

        Gate::define('sarpras-access-building', [SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-access-room', [SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-access-asset', [SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-access-loan', [SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-access-procurement', [SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-access-booking', [SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-access-maintenance', [SarprasWorkspacePolicy::class, 'view']);
        Gate::define('sarpras-view-all', [SarprasWorkspacePolicy::class, 'viewAll']);
        Gate::define('sarpras-create', [SarprasWorkspacePolicy::class, 'create']);
        Gate::define('sarpras-update', [SarprasWorkspacePolicy::class, 'update']);
        Gate::define('sarpras-delete', [SarprasWorkspacePolicy::class, 'delete']);

        Gate::define('gtk-workspace-view', [GtkWorkspacePolicy::class, 'view']);
        Gate::define('gtk-workspace-create', [GtkWorkspacePolicy::class, 'create']);
        Gate::define('gtk-workspace-update', [GtkWorkspacePolicy::class, 'update']);
        Gate::define('gtk-workspace-delete', [GtkWorkspacePolicy::class, 'delete']);
    }
}
