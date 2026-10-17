<?php

namespace App\Listeners;

use App\Events\StudentGraduated;
use App\Events\StudentMutatedOut;
use App\Models\Alumni;

/**
 * Membuat baris `alumni` otomatis saat santri dinyatakan LULUS,
 * baik lewat alur mutasi/kelulusan (mutations-lulus), promosi tingkat
 * akhir, maupun bulk graduation — tanpa perlu sync manual.
 */
class CreateAlumniOnGraduation
{
    public function handle(object $event): void
    {
        if (! $event instanceof StudentGraduated && ! $event instanceof StudentMutatedOut) {
            return;
        }

        if ($event instanceof StudentMutatedOut && $event->outType !== StudentMutatedOut::TYPE_GRADUATION) {
            return;
        }

        $student = $event->student;

        $graduationDate = $event instanceof StudentGraduated
            ? ($event->graduationDate ?: $student->graduation_date?->toDateString())
            : ($event->leaveDate ?: $student->graduation_date?->toDateString());

        $graduationYear = $event instanceof StudentGraduated
            ? ($event->graduationYear ?: $student->graduation_year)
            : $student->graduation_year;

        if (! $graduationYear && $graduationDate) {
            $graduationYear = (int) substr($graduationDate, 0, 4);
        }

        $certificateNumber = $event instanceof StudentMutatedOut
            ? ($event->mutation->graduation_certificate_number ?? $student->certificate_number)
            : $student->certificate_number;

        Alumni::firstOrCreate(
            ['student_id' => $student->id],
            [
                'school_id' => $student->school_id,
                'graduation_year' => $graduationYear ?: now()->year,
                'graduation_certificate_number' => $certificateNumber,
                'graduation_date' => $graduationDate ?: now()->toDateString(),
                'tracer_status' => 'pending',
            ],
        );
    }
}
