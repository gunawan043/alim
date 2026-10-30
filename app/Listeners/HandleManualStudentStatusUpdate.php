<?php

namespace App\Listeners;

use App\Events\StudentStatusChanged;
use App\Jobs\RecordLifecycleAuditJob;
use App\Jobs\SendLifecycleNotificationJob;
use App\Models\Alumni;
use App\Models\StudentClassHistory;
use App\Services\StudentEnrollmentService;
use App\Support\LifecycleMessage;
use Illuminate\Support\Facades\DB;

final class HandleManualStudentStatusUpdate
{
    public function handle(StudentStatusChanged $event): void
    {
        $student = $event->student;
        $payload = $event->payload;

        // StudentController already saved the new status before dispatching.
        // We honor whatever value the payload carries (handles cases where
        // an admin dispatches this event from a CLI or test path).
        $newStatus = $payload['new_status'] ?? $student->status;
        $previousStatus = $payload['previous_status'] ?? null;

        if ($newStatus === $student->status) {
            // Status already persisted; just run side-effects below.
        } else {
            DB::transaction(function () use ($student, $newStatus) {
                $student->update(['status' => $newStatus]);
            });
        }

        $exitReasons = [
            'graduate' => StudentEnrollmentService::EXIT_GRADUATION,
            'transfer_out' => StudentEnrollmentService::EXIT_MUTATION,
            'dropped' => StudentEnrollmentService::EXIT_DROPOUT,
        ];

        DB::transaction(function () use ($student, $newStatus, $previousStatus, $payload, $exitReasons) {
            // Semua status keluar menutup rombel aktif — bukan hanya lulus.
            if (isset($exitReasons[$newStatus]) && $previousStatus !== $newStatus) {
                StudentClassHistory::where('student_id', $student->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'leave_date' => $payload['graduation_date']
                            ?? $student->graduation_date
                            ?? now()->toDateString(),
                    ]);
            }

            // Alumni hanya untuk kelulusan.
            if ($newStatus === 'graduate' && $previousStatus !== 'graduate') {
                Alumni::firstOrCreate(
                    ['student_id' => $student->id],
                    [
                        'school_id' => $student->school_id,
                        'graduation_year' => $student->graduation_year
                            ?: ($payload['graduation_year'] ?? now()->year),
                        'graduation_certificate_number' => $student->graduation_certificate_number ?? null,
                        'graduation_date' => $student->graduation_date ?? ($payload['graduation_date'] ?? now()->toDateString()),
                        'tracer_status' => 'pending',
                    ],
                );
            }
        });

        // Jejak keluar: alasan + tanggal efektif.
        if (isset($exitReasons[$newStatus])) {
            app(StudentEnrollmentService::class)->markExit(
                $student,
                $exitReasons[$newStatus],
                $payload['graduation_date'] ?? $student->graduation_date?->toDateString(),
            );
        } elseif ($newStatus === 'active') {
            app(StudentEnrollmentService::class)->clearExit($student);
        }

        // Parity with lifecycle events: write an audit row + notification
        // for manual changes too, so the trail is complete.
        $auditContext = array_filter([
            'actor_id' => $payload['actor_id'] ?? null,
            'source' => 'manual',
            'graduation_date' => $payload['graduation_date'] ?? null,
            'graduation_year' => $payload['graduation_year'] ?? null,
        ], static fn ($v) => $v !== null && $v !== '');

        $recipientUserId = $payload['actor_id'] ?? $this->resolveRecipientUserId($student);

        // Dispatch audit job
        RecordLifecycleAuditJob::dispatch([
            'event' => 'student.status_changed',
            'student_id' => $student->id,
            'school_id' => $student->school_id,
            'actor_id' => $payload['actor_id'] ?? null,
            'payload' => $auditContext + [
                'from_status' => $previousStatus ?? ($student->getOriginal('status') ?? 'unknown'),
                'to_status' => $newStatus,
            ],
        ]);

        // Dispatch notification job
        if ($recipientUserId !== null) {
            SendLifecycleNotificationJob::dispatch(
                userId: $recipientUserId,
                message: new LifecycleMessage(
                    event: 'student.status_changed',
                    student: $student,
                    previousStatus: $previousStatus ?? ($student->getOriginal('status') ?? 'unknown'),
                    newStatus: $newStatus,
                    context: $auditContext + ['out_type' => $newStatus === 'graduate' ? 'graduation' : null],
                ),
            );
        }
    }

    private function resolveRecipientUserId($student): ?string
    {
        try {
            $school = $student->school;
            if (! $school) {
                return null;
            }
            $user = $school->users()->roleName('admin')->first() ?? $school->users()->first();

            return $user?->id;
        } catch (\Throwable) {
            return null;
        }
    }
}
