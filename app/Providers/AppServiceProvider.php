<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Student;
use App\Models\GradeLevel;
use App\Models\StudyGroup;
use App\Observers\StudyGroupObserver;
use App\View\Composers\SidebarComposer;

use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\NotificationBroadcastService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Register sidebar composer globally
        view()->composer(['layouts.sidebar', 'components.sidebar-menu'], SidebarComposer::class);

        // ── Active Sidebar Route Detection ───────────────────────────
        // Share $activeSidebarRoute to all views for sidebar active state
        view()->composer('*', function (View $view) {
            $currentRoute = request()->route();
            $routeName = $currentRoute ? $currentRoute->getName() : '';
            $view->with('activeSidebarRoute', $routeName);
        });

        // Register isActiveRoute() as a Blade directive so every view can use it
        Blade::if('isActiveRoute', function ($routes) {
            $routeName = Route::currentRouteName() ?? '';
            if (!$routeName) return false;
            $routes = is_array($routes) ? $routes : [$routes];
            foreach ($routes as $r) {
                if ($routeName === $r || $routeName === 'user.' . $r) return true;
                if (str_starts_with($routeName, $r . '.') || str_starts_with($routeName, 'user.' . $r . '.')) return true;
            }
            return false;
        });

        // Share $errors to all views (fallback if ShareErrorsFromSession didn't run)
        view()->share('errors', app('session')->get('errors', new \Illuminate\Support\ViewErrorBag));

        // ── School Context Global Scope ──────────────────────────────
        // Automatically filters school-scoped models when NOT in global view.
        // Models with school_id: Student, GradeLevel, StudyGroup
        // Models without school_id (AcademicYear) are unaffected.
        //
        // To opt a query out of this scope: Model::withoutGlobalScope('school_context')->get()

        Student::addGlobalScope('school_context', function ($query) {
            $schoolId = $this->resolveSchoolContextId(request());
            if ($schoolId) {
                $query->where('students.school_id', $schoolId);
            }
        });

        GradeLevel::addGlobalScope('school_context', function ($query) {
            $schoolId = $this->resolveSchoolContextId(request());
            if ($schoolId) {
                $query->where('grade_levels.school_id', $schoolId);
            }
        });

        StudyGroup::addGlobalScope('school_context', function ($query) {
            $schoolId = $this->resolveSchoolContextId(request());
            if ($schoolId) {
                $query->where('study_groups.school_id', $schoolId);
            }
        });

        // ── Observers ───────────────────────────────────────────────
        StudyGroup::observe(StudyGroupObserver::class);
    }

    /**
     * Resolve the school context ID from the current request.
     * Returns null if user is in global view (has view_global_school_data permission).
     */
    private function resolveSchoolContextId(Request $request): ?string
    {
        if ($request->attributes->get('isGlobalView') === true) {
            return null; // no filter — global view
        }

        return $request->attributes->get('schoolContextId');
    }
}
