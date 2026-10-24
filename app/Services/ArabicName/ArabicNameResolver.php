<?php

namespace App\Services\ArabicName;

use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Support\SubjectArabicName;

/**
 * Satu sumber penentuan nama Arab yang dipakai pada dokumen resmi.
 *
 * Nama santri/GTK hanya dipakai bila statusnya **approved** (sudah ditinjau);
 * nama mapel memakai isian manual bila ada, lalu kamus otomatis; nama kepala
 * sekolah diisi manual. Fallback ke nama Latin dilakukan pemanggil.
 */
class ArabicNameResolver
{
    public function studentName(?Student $student): ?string
    {
        return $student && $student->name_ar_status === 'approved' && filled($student->name_ar)
            ? $student->name_ar
            : null;
    }

    public function userName(?User $user): ?string
    {
        return $user && $user->name_ar_status === 'approved' && filled($user->name_ar)
            ? $user->name_ar
            : null;
    }

    public function subjectName(?Subject $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        return filled($subject->name_ar)
            ? $subject->name_ar
            : SubjectArabicName::resolve($subject->name);
    }

    public function principalName(?School $school): ?string
    {
        return $school && filled($school->principal_name_ar) ? $school->principal_name_ar : null;
    }
}
