<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Subject;
use App\Services\SubjectGroupResolver;

/**
 * Tahap 1 — pemetaan otomatis mapel → rumpun saat mapel dibuat/diubah.
 * Nilai yang sudah ditetapkan admin tidak ditimpa (dapat di-override manual).
 */
class SubjectGroupAssignmentObserver
{
    public function __construct(private readonly SubjectGroupResolver $resolver) {}

    public function creating(Subject $subject): void
    {
        if (! $subject->subject_group_id) {
            $this->resolver->applyToSubject($subject);
        }
    }

    public function updating(Subject $subject): void
    {
        if ($subject->isDirty('name') && ! $subject->subject_group_id) {
            $this->resolver->applyToSubject($subject);
        }
    }
}
