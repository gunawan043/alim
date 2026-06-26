<?php

namespace App\Listeners;

use App\Events\StudentGraduated;
use App\Events\StudentMutatedIn;
use App\Events\StudentMutatedOut;
use App\Events\StudentPromoted;
use App\Jobs\RecordLifecycleAuditJob;

class AuditLifecycleChange
{
    public function handle(object $event): void
    {
        $payload = [
            'event' => class_basename($event),
            'student_id' => $event->student->id,
            'school_id' => $event->student->school_id,
            'payload' => $this->payload($event),
            'actor_id' => $event->actorId ?? null,
            'occurred_at' => now()->toIso8601String(),
        ];

        \Illuminate\Support\Facades\DB::afterCommit(fn () => RecordLifecycleAuditJob::dispatch($payload));
    }

    private function payload(object $event): array
    {
        return match (true) {
            $event instanceof StudentPromoted => [
                'from_sg' => $event->fromStudyGroup->id,
                'to_sg' => $event->toStudyGroup->id,
                'from_ay' => $event->fromAcademicYear->id,
                'to_ay' => $event->toAcademicYear->id,
                'source' => $event->source,
            ],
            $event instanceof StudentGraduated => [
                'graduation_date' => $event->graduationDate,
                'source' => $event->source,
            ],
            $event instanceof StudentMutatedOut => [
                'out_type' => $event->outType,
                'mutation_id' => $event->mutation?->id ?? null,
            ],
            $event instanceof StudentMutatedIn => [
                'mutation_id' => $event->mutation->id,
                'enrolled_sg' => $event->enrollInStudyGroup?->id,
            ],
            default => [],
        };
    }
}
