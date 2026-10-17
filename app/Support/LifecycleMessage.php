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

    /**
     * Pabrik pesan dari event lifecycle — dipakai NotifyGuardiansOnLifecycle.
     * Mengembalikan null untuk event yang tidak dikenal.
     */
    public static function forEvent(object $event): ?self
    {
        $student = $event->student ?? null;

        if (! $student instanceof Student) {
            return null;
        }

        $previousStatus = $student->getOriginal('status') ?: 'unknown';

        return match (true) {
            $event instanceof \App\Events\StudentPromoted => self::forPromotion(
                student: $student,
                previousStatus: $previousStatus,
                toStudyGroupId: $event->toStudyGroup->id,
                toAcademicYearId: $event->toAcademicYear->id,
                promotionDate: $event->promotionDate,
            ),
            $event instanceof \App\Events\StudentGraduated => self::forGraduation(
                student: $student,
                previousStatus: $previousStatus,
                graduationDate: $event->graduationDate,
                graduationYear: $event->graduationYear,
            ),
            $event instanceof \App\Events\StudentMutatedOut => self::forMutationOut(
                student: $student,
                previousStatus: $previousStatus,
                outType: $event->outType,
                mutationOutId: $event->mutation?->id,
                leaveDate: $event->leaveDate,
            ),
            $event instanceof \App\Events\StudentMutatedIn => self::forMutationIn(
                student: $student,
                previousStatus: $previousStatus,
                mutationInId: $event->mutation?->id,
                toStudyGroupId: $event->enrollInStudyGroup?->id,
                toAcademicYearId: $event->enrollInAcademicYear?->id,
                entryDate: $event->joinDate,
            ),
            default => null,
        };
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

    /**
     * Properti turunan yang dipakai SendLifecycleNotificationJob.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'priority' => $this->priority(),
            'title' => $this->title(),
            'body' => $this->body(),
            'actionUrl' => $this->actionUrl(),
            'actionText' => 'Lihat Data Santri',
            default => null,
        };
    }

    public function __isset(string $name): bool
    {
        return in_array($name, ['priority', 'title', 'body', 'actionUrl', 'actionText'], true);
    }

    private function priority(): string
    {
        return match (true) {
            in_array($this->newStatus, ['graduate'], true) => 'success',
            in_array($this->newStatus, ['dropped', 'transfer_out'], true) => 'warning',
            default => 'info',
        };
    }

    private function title(): string
    {
        return match ($this->event) {
            'student.graduated' => 'Kelulusan Santri',
            'student.promoted' => 'Kenaikan Kelas',
            'student.mutated_in' => 'Mutasi Masuk',
            'student.mutated_out' => 'Mutasi Keluar',
            default => 'Perubahan Status Santri',
        };
    }

    private function body(): string
    {
        $reason = $this->reason ? " ({$this->reason})" : '';

        return "{$this->student->name}: status {$this->previousStatus} → {$this->newStatus}{$reason}.";
    }

    private function actionUrl(): ?string
    {
        try {
            return route('user.students.show', [
                'userId' => $this->context['actor_id'] ?? auth()->id() ?? $this->student->user_id,
                'santriUuid' => $this->student->id,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }
}
