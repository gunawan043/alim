<?php

namespace App\Providers;

use App\Events\AssetLifecycleEvent;
use App\Events\Boarding\HealthDischarged;
use App\Events\Boarding\HealthPermitApproved;
use App\Events\Boarding\LeaveApproved;
use App\Events\Boarding\LeaveReturned;
use App\Events\Boarding\RoomDamageReported;
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
use App\Events\Sarpras\AssetMoved;
use App\Events\Sarpras\AssetQrScanned;
use App\Events\Sarpras\LoanOverdue;
use App\Events\Sarpras\LowStockDetected;
use App\Events\Sarpras\MaintenanceDue;
use App\Events\Sarpras\MaintenanceOverdue;
use App\Events\Sarpras\RepairApproved;
use App\Events\Sarpras\RepairCostRecorded;
use App\Events\Sarpras\RepairRejected;
use App\Events\Sarpras\RepairRequestSubmitted;
use App\Events\Sarpras\SlATrackerEscalated;
use App\Events\Sarpras\SlATrackerOverdue;
use App\Events\Sarpras\SlATrackerWarned;
use App\Events\Sarpras\SparepartAdjusted;
use App\Events\Sarpras\SparepartReceived;
use App\Events\Sarpras\StockOpnameCompleted;
use App\Events\Sarpras\StockOpnameStarted;
use App\Events\Sarpras\VendorEvaluationCompleted;
use App\Events\Sarpras\WarrantyClaimOpportunity;
use App\Events\Sarpras\WarrantyExpired;
use App\Events\Sarpras\WorkOrderAssigned;
use App\Events\Sarpras\WorkOrderCompleted;
use App\Events\Sarpras\WorkOrderProgressAdded;
use App\Events\Sarpras\WorkOrderStarted;
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
use App\Listeners\Boarding\BroadcastBoardingNotificationToBus;
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
use App\Listeners\Sarpras\HandleLowStockEvent;
use App\Listeners\Sarpras\NotifyAssetMoved;
use App\Listeners\Sarpras\NotifyMaintenanceLifecycle;
use App\Listeners\Sarpras\NotifyRepairLifecycle;
use App\Listeners\Sarpras\NotifyRepairRequestSubmitted;
use App\Listeners\Sarpras\NotifySlAEscalation;
use App\Listeners\Sarpras\NotifySparepartReceived;
use App\Listeners\Sarpras\NotifyStockOpnameLifecycle;
use App\Listeners\Sarpras\NotifyTechnicianAssignment;
use App\Listeners\Sarpras\NotifyWarrantyClaimOpportunity;
use App\Listeners\Sarpras\NotifyWarrantyExpired;
use App\Listeners\Sarpras\NotifyWorkOrderLifecycle;
use App\Listeners\Sarpras\PersistVendorEvaluationSnapshot;
use App\Listeners\Sarpras\RecordAssetScanAnalytics;
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
        LeaveApproved::class => [
            [SyncBoardingLeaveToAttendance::class, 'handle'],
            [RecordLeaveApprovedOnTimeline::class, 'record'],
            [BroadcastBoardingNotificationToBus::class, 'handleLeaveApproved'],
        ],

        LeaveReturned::class => [
            [SyncBoardingLeaveToAttendance::class, 'handleReturn'],
            [RecordLeaveReturnedOnTimeline::class, 'record'],
            [BroadcastBoardingNotificationToBus::class, 'handleLeaveReturned'],
        ],

        HealthPermitApproved::class => [
            [SyncBoardingHealthToAttendance::class, 'handle'],
            [SyncHealthToClinic::class, 'handle'],
            [RecordHospitalizedOnTimeline::class, 'record'],
            [BroadcastBoardingNotificationToBus::class, 'handleHealthApproved'],
        ],

        HealthDischarged::class => [
            [SyncBoardingHealthToAttendance::class, 'handleDischarge'],
            [SyncHealthToClinic::class, 'handleDischarge'],
            [RecordRecoveredOnTimeline::class, 'record'],
            [BroadcastBoardingNotificationToBus::class, 'handleHealthDischarged'],
        ],

        RoomDamageReported::class => [
            [ConvertRoomDamageToMaintenance::class, 'handle'],
            [BroadcastBoardingNotificationToBus::class, 'handleRoomDamage'],
        ],

        // Sarpras automation events — notification listeners.
        WorkOrderAssigned::class => [
            NotifyTechnicianAssignment::class,
        ],

        WorkOrderStarted::class => [
            NotifyWorkOrderLifecycle::class,
        ],

        WorkOrderCompleted::class => [
            NotifyWorkOrderLifecycle::class,
        ],

        RepairRequestSubmitted::class => [
            NotifyRepairRequestSubmitted::class,
        ],

        RepairApproved::class => [
            NotifyRepairLifecycle::class,
        ],

        RepairRejected::class => [
            NotifyRepairLifecycle::class,
        ],

        MaintenanceDue::class => [
            NotifyMaintenanceLifecycle::class,
        ],

        MaintenanceOverdue::class => [
            NotifyMaintenanceLifecycle::class,
        ],

        WarrantyExpired::class => [
            NotifyWarrantyExpired::class,
        ],

        StockOpnameStarted::class => [
            NotifyStockOpnameLifecycle::class,
        ],

        StockOpnameCompleted::class => [
            NotifyStockOpnameLifecycle::class,
        ],

        SlATrackerWarned::class => [
            NotifySlAEscalation::class,
        ],

        SlATrackerOverdue::class => [
            NotifySlAEscalation::class,
        ],

        SlATrackerEscalated::class => [
            NotifySlAEscalation::class,
        ],

        AssetMoved::class => [
            NotifyAssetMoved::class,
        ],

        AssetQrScanned::class => [
            RecordAssetScanAnalytics::class,
        ],

        LoanOverdue::class => [
            NotifyAssetMoved::class,
        ],

        LowStockDetected::class => [
            HandleLowStockEvent::class,
        ],

        RepairCostRecorded::class => [
            NotifyRepairLifecycle::class,
        ],

        SparepartReceived::class => [
            NotifySparepartReceived::class,
        ],

        SparepartAdjusted::class => [
            NotifySparepartReceived::class,
        ],

        VendorEvaluationCompleted::class => [
            PersistVendorEvaluationSnapshot::class,
        ],

        WarrantyClaimOpportunity::class => [
            NotifyWarrantyClaimOpportunity::class,
        ],

        WorkOrderProgressAdded::class => [
            NotifyWorkOrderLifecycle::class,
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
