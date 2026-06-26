<?php

namespace App\Providers;

use App\Events\GtkProfileUpdated;
use App\Events\StudentAssignedToRombel;
use App\Events\StudentGraduated;
use App\Events\StudentMutatedIn;
use App\Events\StudentMutatedOut;
use App\Events\StudentPromoted;
use App\Events\StudyGroupSubjectChanged;
use App\Events\SubjectAssignedToStudyGroup;
use App\Events\TeachingAssignmentChanged;
use App\Listeners\AuditLifecycleChange;
use App\Listeners\ClosePreviousClassHistoryOnLifecycle;
use App\Listeners\DeactivateStudentAcademicRecordsListener;
use App\Listeners\NotifyGuardiansOnLifecycle;
use App\Listeners\ProvisionStudentAcademicDataListener;
use App\Listeners\ProvisionStudyGroupSubjectAcademicStructure;
use App\Listeners\SyncStudentRombelAfterLifecycle;
use App\Listeners\TriggerGtkWorkloadRecalculation;
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
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
