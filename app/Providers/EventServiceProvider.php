<?php

namespace App\Providers;

use App\Events\AssetLifecycleEvent;
use App\Events\GoodsReceived;
use App\Events\GtkProfileUpdated;
use App\Events\InvoiceApproved;
use App\Events\PoAccepted;
use App\Events\PoDelivered;
use App\Events\PoQcCompleted;
use App\Events\PoShipped;
use App\Events\PurchaseOrderCreated;
use App\Events\QualityChecked;
use App\Events\QuotationAccepted;
use App\Events\QuotationAwarded;
use App\Events\QuotationSubmitted;
use App\Events\RfqPublished;
use App\Events\StudentAssignedToRombel;
use App\Events\StudentGraduated;
use App\Events\StudentMutatedIn;
use App\Events\StudentMutatedOut;
use App\Events\StudentPromoted;
use App\Events\StudyGroupSubjectChanged;
use App\Events\SubjectAssignedToStudyGroup;
use App\Events\TeachingAssignmentChanged;
use App\Events\VendorRated;
use App\Listeners\AuditLifecycleChange;
use App\Listeners\Boarding\ConvertRoomDamageToMaintenance;
use App\Listeners\Boarding\RecordHospitalizedOnTimeline;
use App\Listeners\Boarding\RecordLeaveApprovedOnTimeline;
use App\Listeners\Boarding\RecordLeaveReturnedOnTimeline;
use App\Listeners\Boarding\RecordRecoveredOnTimeline;
use App\Listeners\Boarding\SyncBoardingHealthToAttendance;
use App\Listeners\Boarding\SyncBoardingLeaveToAttendance;
use App\Listeners\Boarding\SyncHealthToClinic;
use App\Listeners\ClosePreviousClassHistoryOnLifecycle;
use App\Listeners\DeactivateStudentAcademicRecordsListener;
use App\Listeners\NotifyGuardiansOnLifecycle;
use App\Listeners\NotifySarprasOfQuotation;
use App\Listeners\NotifyVendorsOfRfq;
use App\Listeners\PersistAssetEventLog;
use App\Listeners\ProvisionStudentAcademicDataListener;
use App\Listeners\ProvisionStudyGroupSubjectAcademicStructure;
use App\Listeners\RecordInvoiceApprovalTransition;
use App\Listeners\RecordPoTransition;
use App\Listeners\RecordQualityCheckTransition;
use App\Listeners\RecordQuotationTransition;
use App\Listeners\RecordVendorRatingTransition;
use App\Listeners\SyncStudentRombelAfterLifecycle;
use App\Listeners\TriggerGtkWorkloadRecalculation;
use App\Listeners\UpdateAssetCondition;
use App\Listeners\UpdateStudentStatusOnLifecycle;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        StudentAssignedToRombel::class => [
            ProvisionStudentAcademicDataListener::class,
        ],

        GtkProfileUpdated::class => [
            [TriggerGtkWorkloadRecalculation::class, 'handleGtkProfileUpdated'],
        ],

        TeachingAssignmentChanged::class => [
            [TriggerGtkWorkloadRecalculation::class, 'handleTeachingAssignmentChanged'],
        ],

        StudyGroupSubjectChanged::class => [
            [TriggerGtkWorkloadRecalculation::class, 'handleStudyGroupSubjectChanged'],
        ],

        SubjectAssignedToStudyGroup::class => [
            ProvisionStudyGroupSubjectAcademicStructure::class,
        ],

        // Student lifecycle events — status writes first, then history, then notifications, then audit.
        StudentPromoted::class => [
            UpdateStudentStatusOnLifecycle::class,
            ClosePreviousClassHistoryOnLifecycle::class,
            SyncStudentRombelAfterLifecycle::class,
            NotifyGuardiansOnLifecycle::class,
            AuditLifecycleChange::class,
        ],

        StudentExitedFromRombel::class => [
            DeactivateStudentAcademicRecordsListener::class,
        ],

        StudentGraduated::class => [
            UpdateStudentStatusOnLifecycle::class,
            ClosePreviousClassHistoryOnLifecycle::class,
            NotifyGuardiansOnLifecycle::class,
            AuditLifecycleChange::class,
        ],

        StudentMutatedOut::class => [
            UpdateStudentStatusOnLifecycle::class,
            ClosePreviousClassHistoryOnLifecycle::class,
            NotifyGuardiansOnLifecycle::class,
            AuditLifecycleChange::class,
        ],

        StudentMutatedIn::class => [
            UpdateStudentStatusOnLifecycle::class,
            SyncStudentRombelAfterLifecycle::class,
            NotifyGuardiansOnLifecycle::class,
            AuditLifecycleChange::class,
        ],

        // Asset lifecycle — persist log first, then condition update for maintenance/repair.
        AssetLifecycleEvent::class => [
            PersistAssetEventLog::class,
            UpdateAssetCondition::class,
            TriggerMaintenanceAutomation::class,
            NotifyOnCriticalAssetEvents::class,
        ],

        // Boarding Integration Events — fired into the integration layer
        \App\Events\Boarding\LeaveApproved::class => [
            [SyncBoardingLeaveToAttendance::class, 'handle'],
            [RecordLeaveApprovedOnTimeline::class, 'record'],
            [\App\Listeners\Boarding\BroadcastBoardingNotificationToBus::class, 'handleLeaveApproved'],
        ],

        \App\Events\Boarding\LeaveReturned::class => [
            [SyncBoardingLeaveToAttendance::class, 'handleReturn'],
            [RecordLeaveReturnedOnTimeline::class, 'record'],
            [\App\Listeners\Boarding\BroadcastBoardingNotificationToBus::class, 'handleLeaveReturned'],
        ],

        \App\Events\Boarding\HealthPermitApproved::class => [
            [SyncBoardingHealthToAttendance::class, 'handle'],
            [SyncHealthToClinic::class, 'handle'],
            [RecordHospitalizedOnTimeline::class, 'record'],
            [\App\Listeners\Boarding\BroadcastBoardingNotificationToBus::class, 'handleHealthApproved'],
        ],

        \App\Events\Boarding\HealthDischarged::class => [
            [SyncBoardingHealthToAttendance::class, 'handleDischarge'],
            [SyncHealthToClinic::class, 'handleDischarge'],
            [RecordRecoveredOnTimeline::class, 'record'],
            [\App\Listeners\Boarding\BroadcastBoardingNotificationToBus::class, 'handleHealthDischarged'],
        ],

        \App\Events\Boarding\RoomDamageReported::class => [
            [ConvertRoomDamageToMaintenance::class, 'handle'],
            [\App\Listeners\Boarding\BroadcastBoardingNotificationToBus::class, 'handleRoomDamage'],
        ],

        // Sarpras automation events — notification listeners.
        \App\Events\Sarpras\WorkOrderAssigned::class => [
            \App\Listeners\Sarpras\NotifyTechnicianAssignment::class,
        ],

        \App\Events\Sarpras\WorkOrderStarted::class => [
            \App\Listeners\Sarpras\NotifyWorkOrderLifecycle::class,
        ],

        \App\Events\Sarpras\WorkOrderCompleted::class => [
            \App\Listeners\Sarpras\NotifyWorkOrderLifecycle::class,
        ],

        \App\Events\Sarpras\RepairRequestSubmitted::class => [
            \App\Listeners\Sarpras\NotifyRepairRequestSubmitted::class,
        ],

        \App\Events\Sarpras\RepairApproved::class => [
            \App\Listeners\Sarpras\NotifyRepairLifecycle::class,
        ],

        \App\Events\Sarpras\RepairRejected::class => [
            \App\Listeners\Sarpras\NotifyRepairLifecycle::class,
        ],

        \App\Events\Sarpras\MaintenanceDue::class => [
            \App\Listeners\Sarpras\NotifyMaintenanceLifecycle::class,
        ],

        \App\Events\Sarpras\MaintenanceOverdue::class => [
            \App\Listeners\Sarpras\NotifyMaintenanceLifecycle::class,
        ],

        \App\Events\Sarpras\WarrantyExpired::class => [
            \App\Listeners\Sarpras\NotifyWarrantyExpired::class,
        ],

        \App\Events\Sarpras\StockOpnameStarted::class => [
            \App\Listeners\Sarpras\NotifyStockOpnameLifecycle::class,
        ],

        \App\Events\Sarpras\StockOpnameCompleted::class => [
            \App\Listeners\Sarpras\NotifyStockOpnameLifecycle::class,
        ],

        \App\Events\Sarpras\SlATrackerWarned::class => [
            \App\Listeners\Sarpras\NotifySlAEscalation::class,
        ],

        \App\Events\Sarpras\SlATrackerOverdue::class => [
            \App\Listeners\Sarpras\NotifySlAEscalation::class,
        ],

        \App\Events\Sarpras\SlATrackerEscalated::class => [
            \App\Listeners\Sarpras\NotifySlAEscalation::class,
        ],

        \App\Events\Sarpras\AssetMoved::class => [
            \App\Listeners\Sarpras\NotifyAssetMoved::class,
        ],

        \App\Events\Sarpras\AssetQrScanned::class => [
            \App\Listeners\Sarpras\RecordAssetScanAnalytics::class,
        ],

        \App\Events\Sarpras\LoanOverdue::class => [
            \App\Listeners\Sarpras\NotifyAssetMoved::class,
        ],

        \App\Events\Sarpras\LowStockDetected::class => [
            \App\Listeners\Sarpras\HandleLowStockEvent::class,
        ],

        \App\Events\Sarpras\RepairCostRecorded::class => [
            \App\Listeners\Sarpras\NotifyRepairLifecycle::class,
        ],

        \App\Events\Sarpras\SparepartReceived::class => [
            \App\Listeners\Sarpras\NotifySparepartReceived::class,
        ],

        \App\Events\Sarpras\SparepartAdjusted::class => [
            \App\Listeners\Sarpras\NotifySparepartReceived::class,
        ],

        \App\Events\Sarpras\VendorEvaluationCompleted::class => [
            \App\Listeners\Sarpras\PersistVendorEvaluationSnapshot::class,
        ],

        \App\Events\Sarpras\WarrantyClaimOpportunity::class => [
            \App\Listeners\Sarpras\NotifyWarrantyClaimOpportunity::class,
        ],

        \App\Events\Sarpras\WorkOrderProgressAdded::class => [
            \App\Listeners\Sarpras\NotifyWorkOrderLifecycle::class,
        ],

        // Vendor procurement workflow events.
        RfqPublished::class => [
            [NotifyVendorsOfRfq::class, 'handle'],
        ],

        QuotationSubmitted::class => [
            [RecordQuotationTransition::class, 'handleSubmitted'],
            [NotifySarprasOfQuotation::class, 'handle'],
        ],

        QuotationAwarded::class => [
            [RecordQuotationTransition::class, 'handleAwarded'],
        ],

        QuotationAccepted::class => [
            [RecordQuotationTransition::class, 'handleAccepted'],
        ],

        PurchaseOrderCreated::class => [
            [RecordPoTransition::class, 'handleCreated'],
        ],

        PoAccepted::class => [
            [RecordPoTransition::class, 'onAccepted'],
        ],

        PoShipped::class => [
            [RecordPoTransition::class, 'onShipped'],
        ],

        PoDelivered::class => [
            [RecordPoTransition::class, 'onDelivered'],
        ],

        PoQcCompleted::class => [
            [RecordPoTransition::class, 'onQcCompleted'],
        ],

        GoodsReceived::class => [
            [RecordPoTransition::class, 'onGoodsReceived'],
        ],

        QualityChecked::class => [
            RecordQualityCheckTransition::class,
        ],

        InvoiceApproved::class => [
            RecordInvoiceApprovalTransition::class,
        ],

        VendorRated::class => [
            RecordVendorRatingTransition::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
