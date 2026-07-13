<?php

namespace App\Providers;

use App\Models\DokumenIso;
use App\Models\BoardingPolicy;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Observers\DokumenIsoObserver;
use App\Observers\StudyGroupObserver;
use App\Observers\StudyGroupSubjectObserver;
use App\Observers\BoardingPolicyObserver;
use App\View\Composers\SidebarComposer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event as EventDispatcher;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\NotificationBroadcastService::class);

        // Sarpras workflow services — singleton so the in-memory state machine
        // registry survives across requests in the same PHP process.
        $this->app->singleton(\App\Services\Sarpras\StateMachine::class);
        $this->app->singleton(\App\Services\Sarpras\StateMachineRegistry::class);
        $this->app->singleton(\App\Services\Sarpras\AssetEventLogger::class);
        $this->app->singleton(\App\Services\Sarpras\AssetPassportService::class);
        $this->app->singleton(\App\Services\Sarpras\AssetRegistrationService::class);
        $this->app->singleton(\App\Services\Sarpras\RepairRequestWorkflow::class);
        $this->app->singleton(\App\Services\Sarpras\MaintenanceWorkflow::class);
        $this->app->singleton(\App\Services\Sarpras\StockOpnameWorkflow::class);
        $this->app->singleton(\App\Services\Sarpras\MovementWorkflow::class);
        $this->app->singleton(\App\Services\Sarpras\ChecklistEngine::class);
        $this->app->singleton(\App\Services\Sarpras\PhotoDocumentationService::class);
        $this->app->singleton(\App\Services\Sarpras\TechnicianWorkspaceService::class);
        $this->app->singleton(\App\Services\Sarpras\AuditorWorkspaceService::class);
        $this->app->singleton(\App\Services\Sarpras\DivisionPortalService::class);
        $this->app->singleton(\App\Services\Sarpras\OfflineSyncService::class);
        $this->app->singleton(\App\Services\Sarpras\WorkOrderExecutionService::class);
        $this->app->singleton(\App\Services\SarprasCacheInvalidator::class);

        // Boarding operations
        $this->app->singleton(\App\Services\Boarding\StudentStatusService::class);
        $this->app->singleton(\App\Services\Boarding\LeaveWorkflowService::class);
        $this->app->singleton(\App\Services\Boarding\VisitWorkflowService::class);
        $this->app->singleton(\App\Services\Boarding\HealthWorkflowService::class);
        $this->app->singleton(\App\Services\Boarding\BoardingApprovalService::class);

        // Bind BoardingRulesEngine as a singleton so DI can resolve it.
        // The class uses a private-constructor + getInstance() pattern, so we
        // use makeWith with a custom factory to satisfy the DI container.
        $this->app->singleton(\App\Domain\Services\BoardingRulesEngine::class, function () {
            return \App\Domain\Services\BoardingRulesEngine::getInstance();
        });
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
            if (! $routeName) {
                return false;
            }
            $routes = is_array($routes) ? $routes : [$routes];
            foreach ($routes as $r) {
                if ($routeName === $r || $routeName === 'user.'.$r) {
                    return true;
                }
                if (str_starts_with($routeName, $r.'.') || str_starts_with($routeName, 'user.'.$r.'.')) {
                    return true;
                }
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
        DokumenIso::observe(DokumenIsoObserver::class);
        StudyGroupSubject::observe(StudyGroupSubjectObserver::class);

        BoardingPolicy::observe(BoardingPolicyObserver::class);

        // ── Boarding Rules Engine Registration ─────────────────────
        $engine = \App\Domain\Services\BoardingRulesEngine::getInstance();
        $engine->registerEvaluator(new \App\Domain\Services\LeaveRuleEvaluator);
        $engine->registerEvaluator(new \App\Domain\Services\VisitRuleEvaluator);
        $engine->registerEvaluator(new \App\Domain\Services\HospitalizationRuleEvaluator);
        $engine->registerEvaluator(new \App\Domain\Services\AttendanceSyncRuleEvaluator);

        // ── Event → Listener Mapping ────────────────────────────
        $listeners = [
            \App\Domain\Events\BoardingPermitSubmitted::class => [
                [\App\Domain\Listeners\RecordBoardingPermitTimeline::class, 'onSubmitted'],
            ],
            \App\Domain\Events\BoardingPermitDecided::class => [
                [\App\Domain\Listeners\RecordBoardingPermitTimeline::class, 'onDecided'],
                [\App\Domain\Listeners\NotifyMahromOnPermitDecision::class, 'handle'],
                [\App\Domain\Listeners\SendWaliNotificationOnPermitDecision::class, 'handle'],
            ],
            \App\Domain\Events\BoardingVisitDecided::class => [
                [\App\Domain\Listeners\RecordBoardingVisitTimeline::class, 'onDecided'],
                [\App\Domain\Listeners\SendWaliNotificationOnVisitDecision::class, 'handle'],
            ],
            \App\Domain\Events\BoardingVisitCheckIn::class => [
                [\App\Domain\Listeners\RecordBoardingVisitTimeline::class, 'onCheckIn'],
            ],
        ];
        foreach ($listeners as $event => $list) {
            foreach ($list as $listener) {
                EventDispatcher::listen($event, $listener[0].'@'.$listener[1]);
            }
        }
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
