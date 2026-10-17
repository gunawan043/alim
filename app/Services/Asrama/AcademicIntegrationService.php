<?php

namespace App\Services\Asrama;

use App\Models\BoardingTimelineEvent;
use App\Models\Dormitory;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Bridges academic lifecycle changes (mutasi, lulus, nonaktif) into
 * the dormitory system: closes active permits, clears room/dorm
 * assignments, and writes timeline events so the dormitory history
 * stays aligned with academic records.
 */
class AcademicIntegrationService
{
    /**
     * Map academic status changes to timeline event types.
     */
    private const STATUS_TO_EVENT = [
        'graduate' => 'expelled',
        'inactive' => 'expelled',
        'dropped' => 'expelled',
        'transfer_out' => 'transfer',
        'transfer' => 'transfer',
    ];

    public function syncFromAcademicStatus(
        string $studentId,
        string $newStatus,
        ?string $reason = null,
        ?string $academicYearId = null,
        ?string $actorId = null,
    ): BoardingTimelineEvent {
        $student = Student::findOrFail($studentId);

        if (! in_array($newStatus, array_keys(self::STATUS_TO_EVENT))) {
            throw new RuntimeException("Unsupported academic status: {$newStatus}");
        }

        return DB::transaction(function () use ($student, $newStatus, $reason, $academicYearId, $actorId) {
            $previousStatus = $student->status;
            $previousDorm = $student->dormitory_id;
            $previousRoom = $student->room_id;

            $student->status = $newStatus;

            if (in_array($newStatus, ['graduate', 'inactive', 'dropped', 'transfer_out'])) {
                $student->dormitory_id = null;
                $student->room_id = null;
            }

            $student->save();

            $eventType = self::STATUS_TO_EVENT[$newStatus];
            $event = BoardingTimelineEvent::create([
                'student_id' => $student->id,
                'dormitory_id' => $previousDorm,
                'room_id' => $previousRoom,
                'event_type' => $eventType,
                'event_at' => now(),
                'subject_refs' => $academicYearId ? ['academic_year_id' => $academicYearId] : null,
                'payload' => [
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason,
                    'cleared_dorm' => in_array($newStatus, ['graduate', 'inactive', 'dropped', 'transfer_out']),
                ],
                'is_special_permission' => false,
                'recorded_by' => $actorId,
                'source_actor_id' => $actorId,
                'source_system' => 'academic',
            ]);

            Log::info('AcademicIntegration.sync', [
                'student_id' => $student->id,
                'previous' => $previousStatus,
                'new' => $newStatus,
                'event_id' => $event->id,
            ]);

            return $event->fresh();
        });
    }

    public function syncBatch(array $studentIds, string $newStatus, ?string $reason = null): array
    {
        $events = [];
        foreach ($studentIds as $id) {
            try {
                $events[] = $this->syncFromAcademicStatus($id, $newStatus, $reason);
            } catch (\Throwable $e) {
                Log::error('AcademicIntegration.batch_failed', [
                    'student_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $events;
    }
}
