<?php

namespace App\Providers;

use App\Bootstrap\SystemSuperAdminBootstrap;
use App\Domain\Events\BoardingPermitDecided;
use App\Domain\Events\BoardingPermitSubmitted;
use App\Domain\Events\BoardingVisitCheckIn;
use App\Domain\Events\BoardingVisitDecided;
use App\Domain\Listeners\NotifyMahromOnPermitDecision;
use App\Domain\Listeners\RecordBoardingPermitTimeline;
use App\Domain\Listeners\RecordBoardingVisitTimeline;
use App\Domain\Listeners\SendWaliNotificationOnPermitDecision;
use App\Domain\Listeners\SendWaliNotificationOnVisitDecision;
use App\Domain\Services\AttendanceSyncRuleEvaluator;
use App\Domain\Services\BoardingRulesEngine;
// use App\View\Composers\SidebarComposer; // REMOVED - Sidebar menu DB unused
use App\Domain\Services\HospitalizationRuleEvaluator;
use App\Domain\Services\LeaveRuleEvaluator;
use App\Domain\Services\VisitRuleEvaluator;
use App\Events\ContractExpiring;
use App\Events\DeliveryTrackingUpdated;
use App\Events\DocumentExpiring;
use App\Events\GoodsReceiptCreated;
use App\Events\InvoiceSubmissionApproved;
use App\Events\PoAccepted;
use App\Events\PoDelivered;
use App\Events\PoQcCompleted;
use App\Events\PoShipped;
use App\Events\QualityCheckCompleted;
use App\Events\QuotationAccepted;
use App\Events\QuotationSubmitted;
use App\Events\RfqPublished;
use App\Events\RmaSubmitted;
use App\Events\VendorAuditRecorded;
use App\Events\VendorNotificationDispatched;
use App\Listeners\NotifySarprasOfContractStatus;
use App\Listeners\NotifySarprasOfDocumentStatus;
use App\Listeners\NotifySarprasOfQuotation;
use App\Listeners\NotifyVendorOfInvoiceStatus;
use App\Listeners\NotifyVendorsOfRfq;
use App\Listeners\RecordDeliveryTransition;
use App\Listeners\RecordGoodsReceiptTransition;
use App\Listeners\RecordPoTransition;
use App\Listeners\RecordQualityTransition;
use App\Listeners\RecordQuotationTransition;
use App\Listeners\RecordRmaTransition;
use App\Listeners\RecordVendorAuditListener;
use App\Listeners\SendVendorNotificationListener;
use App\Models\BoardingPolicy;
use App\Models\DokumenIso;
use App\Models\GradeLevel;
use App\Models\GtkEmployment;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Observers\BoardingPolicyObserver;
use App\Observers\DokumenIsoObserver;
use App\Observers\GtkEmploymentObserver;
use App\Observers\StudyGroupObserver;
use App\Observers\StudyGroupSubjectObserver;
use App\Services\Boarding\BoardingApprovalService;
use App\Services\Boarding\HealthWorkflowService;
use App\Services\Boarding\LeaveWorkflowService;
use App\Services\Boarding\StudentStatusService;
use App\Services\Boarding\VisitWorkflowService;
use App\Services\NotificationBroadcastService;
use App\Services\Sarpras\AssetEventLogger;
use App\Services\Sarpras\AssetPassportService;
use App\Services\Sarpras\AssetRegistrationService;
use App\Services\Sarpras\AssetStatusTransitionService;
use App\Services\Sarpras\AuditorWorkspaceService;
use App\Services\Sarpras\ChecklistEngine;
use App\Services\Sarpras\DivisionPortalService;
use App\Services\Sarpras\MaintenanceWorkflow;
use App\Services\Sarpras\MovementWorkflow;
use App\Services\Sarpras\OfflineSyncService;
use App\Services\Sarpras\PhotoDocumentationService;
use App\Services\Sarpras\RepairRequestWorkflow;
use App\Services\Sarpras\StateMachine;
use App\Services\Sarpras\StateMachineRegistry;
use App\Services\Sarpras\StockOpnameWorkflow;
use App\Services\Sarpras\TechnicianWorkspaceService;
use App\Services\Sarpras\WorkOrderExecutionService;
use App\Services\SarprasCacheInvalidator;
use App\Services\WorkspaceActivationService;
use App\View\Composers\SidebarAccessComposer;
use App\View\Composers\SystemAdminMenuComposer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event as EventDispatcher;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NotificationBroadcastService::class);
        $this->app->singleton(WorkspaceActivationService::class);

        // Sarpras workflow services — singleton so the in-memory state machine
        // registry survives across requests in the same PHP process.
        $this->app->singleton(StateMachine::class);
        $this->app->singleton(StateMachineRegistry::class);
        $this->app->singleton(AssetEventLogger::class);
        $this->app->singleton(AssetPassportService::class);
        $this->app->singleton(AssetRegistrationService::class);
        $this->app->singleton(RepairRequestWorkflow::class);
        $this->app->singleton(MaintenanceWorkflow::class);
        $this->app->singleton(StockOpnameWorkflow::class);
        $this->app->singleton(MovementWorkflow::class);
        $this->app->singleton(ChecklistEngine::class);
        $this->app->singleton(PhotoDocumentationService::class);
        $this->app->singleton(TechnicianWorkspaceService::class);
        $this->app->singleton(AuditorWorkspaceService::class);
        $this->app->singleton(DivisionPortalService::class);
        $this->app->singleton(OfflineSyncService::class);
        $this->app->singleton(WorkOrderExecutionService::class);
        $this->app->singleton(SarprasCacheInvalidator::class);
        $this->app->singleton(AssetStatusTransitionService::class);

        // Boarding operations
        $this->app->singleton(StudentStatusService::class);
        $this->app->singleton(LeaveWorkflowService::class);
        $this->app->singleton(VisitWorkflowService::class);
        $this->app->singleton(HealthWorkflowService::class);
        $this->app->singleton(BoardingApprovalService::class);

        // Bind BoardingRulesEngine as a singleton so DI can resolve it.
        // The class uses a private-constructor + getInstance() pattern, so we
        // use makeWith with a custom factory to satisfy the DI container.
        $this->app->singleton(BoardingRulesEngine::class, function () {
            return BoardingRulesEngine::getInstance();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // ── System Super Admin bootstrap ───────────────────────────
        // Ensure the Power User (super.admin@alim.local) exists with
        // full permissions on every boot, even after migrate:fresh.
        // Idempotent — safe to call repeatedly.
        SystemSuperAdminBootstrap::ensure();

        // Register sidebar access composer globally
        view()->composer(['layouts.sidebar'], SidebarAccessComposer::class);

        // System Admin Menu (when current user is is_system_admin and not View-As)
        // Bound to '*' so viewAsSwitcherVisible is shared to ALL views (needed because
        // @include chains in master.blade.php don't trigger per-view composers).
        view()->composer('*', SystemAdminMenuComposer::class);

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
        view()->share('errors', app('session')->get('errors', new ViewErrorBag));

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
        GtkEmployment::observe(GtkEmploymentObserver::class);

        // ── Boarding Rules Engine Registration ─────────────────────
        $engine = BoardingRulesEngine::getInstance();
        $engine->registerEvaluator(new LeaveRuleEvaluator);
        $engine->registerEvaluator(new VisitRuleEvaluator);
        $engine->registerEvaluator(new HospitalizationRuleEvaluator);
        $engine->registerEvaluator(new AttendanceSyncRuleEvaluator);

        // ── Event → Listener Mapping ────────────────────────────
        $listeners = [
            BoardingPermitSubmitted::class => [
                [RecordBoardingPermitTimeline::class, 'onSubmitted'],
            ],
            BoardingPermitDecided::class => [
                [RecordBoardingPermitTimeline::class, 'onDecided'],
                [NotifyMahromOnPermitDecision::class, 'handle'],
                [SendWaliNotificationOnPermitDecision::class, 'handle'],
            ],
            BoardingVisitDecided::class => [
                [RecordBoardingVisitTimeline::class, 'onDecided'],
                [SendWaliNotificationOnVisitDecision::class, 'handle'],
            ],
            BoardingVisitCheckIn::class => [
                [RecordBoardingVisitTimeline::class, 'onCheckIn'],
            ],
            // Vendor Collaboration Platform
            VendorAuditRecorded::class => [
                [RecordVendorAuditListener::class, 'handle'],
            ],
            VendorNotificationDispatched::class => [
                [SendVendorNotificationListener::class, 'handle'],
            ],
            RfqPublished::class => [
                [NotifyVendorsOfRfq::class, 'handle'],
            ],
            QuotationSubmitted::class => [
                [NotifySarprasOfQuotation::class, 'handle'],
            ],
            PoAccepted::class => [
                [RecordPoTransition::class, 'handle'],
            ],
            PoShipped::class => [
                [RecordPoTransition::class, 'handle'],
            ],
            PoDelivered::class => [
                [RecordPoTransition::class, 'handle'],
            ],
            PoQcCompleted::class => [
                [RecordPoTransition::class, 'handle'],
            ],
            DeliveryTrackingUpdated::class => [
                [RecordDeliveryTransition::class, 'handle'],
            ],
            GoodsReceiptCreated::class => [
                [RecordGoodsReceiptTransition::class, 'handle'],
            ],
            QualityCheckCompleted::class => [
                [RecordQualityTransition::class, 'handle'],
            ],
            RmaSubmitted::class => [
                [RecordRmaTransition::class, 'handle'],
            ],
            QuotationAccepted::class => [
                [RecordQuotationTransition::class, 'handle'],
            ],
            InvoiceSubmissionApproved::class => [
                [NotifyVendorOfInvoiceStatus::class, 'handle'],
            ],
            ContractExpiring::class => [
                [NotifySarprasOfContractStatus::class, 'handle'],
            ],
            DocumentExpiring::class => [
                [NotifySarprasOfDocumentStatus::class, 'handle'],
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
