<?php

namespace App\Support;

use App\Models\Student;
use Illuminate\Contracts\Support\Arrayable;

final class LifecycleMessage implements Arrayable
{
    public function __construct(
        public readonly string $event,
        public readonly Student $student,
        public readonly string $previousStatus,
        public readonly string $newStatus,
        public readonly ?string $reason = null,
        public readonly array $context = [],
    ) {}

    public static function forPromotion(
        Student $student,
        string $previousStatus,
        ?string $promotionId = null,
        ?string $toStudyGroupId = null,
        ?string $toAcademicYearId = null,
        ?string $promotionDate = null,
    ): self {
        return new self(
            event: 'student.promoted',
            student: $student,
            previousStatus: $previousStatus,
            newStatus: 'active',
            reason: 'Kenaikan kelas',
            context: array_filter([
                'promotion_id' => $promotionId,
                'to_study_group_id' => $toStudyGroupId,
                'to_academic_year_id' => $toAcademicYearId,
                'promotion_date' => $promotionDate,
            ], static fn ($v) => $v !== null && $v !== ''),
        );
    }

    public static function forGraduation(
        Student $student,
        string $previousStatus,
        ?string $promotionId = null,
        ?string $graduationDate = null,
        ?string $graduationYear = null,
    ): self {
        return new self(
            event: 'student.graduated',
            student: $student,
            previousStatus: $previousStatus,
            newStatus: 'graduate',
            reason: 'Kelulusan',
            context: array_filter([
                'promotion_id' => $promotionId,
                'graduation_date' => $graduationDate,
                'graduation_year' => $graduationYear,
            ], static fn ($v) => $v !== null && $v !== ''),
        );
    }

    public static function forMutationOut(
        Student $student,
        string $previousStatus,
        string $outType,
        ?string $mutationOutId = null,
        ?string $leaveDate = null,
    ): self {
        $newStatus = match ($outType) {
            'graduation' => 'graduate',
            'dropout' => 'dropped',
            default => 'transfer_out',
        };

        $reason = match ($outType) {
            'graduation' => 'Mutasi keluar (kelulusan)',
            'dropout' => 'Mutasi keluar (putus sekolah)',
            default => 'Mutasi keluar (pindah)',
        };

        $graduationYear = $outType === 'graduation' && $leaveDate
            ? substr($leaveDate, 0, 4)
            : null;

        return new self(
            event: 'student.mutated_out',
            student: $student,
            previousStatus: $previousStatus,
            newStatus: $newStatus,
            reason: $reason,
            context: array_filter([
                'mutation_out_id' => $mutationOutId,
                'out_type' => $outType,
                'leave_date' => $leaveDate,
                'graduation_year' => $graduationYear,
            ], static fn ($v) => $v !== null && $v !== ''),
        );
    }

    public static function forMutationIn(
        Student $student,
        string $previousStatus,
        ?string $mutationInId = null,
        ?string $toStudyGroupId = null,
        ?string $toAcademicYearId = null,
        ?string $entryDate = null,
    ): self {
        return new self(
            event: 'student.mutated_in',
            student: $student,
            previousStatus: $previousStatus,
            newStatus: 'active',
            reason: 'Mutasi masuk',
            context: array_filter([
                'mutation_in_id' => $mutationInId,
                'to_study_group_id' => $toStudyGroupId,
                'to_academic_year_id' => $toAcademicYearId,
                'entry_date' => $entryDate,
            ], static fn ($v) => $v !== null && $v !== ''),
        );
    }

    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'student_id' => $this->student->id,
            'school_id' => $this->student->school_id,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'reason' => $this->reason,
            'context' => $this->context,
        ];
    }
}
