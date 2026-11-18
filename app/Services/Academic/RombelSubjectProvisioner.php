<?php

namespace App\Services\Academic;

use App\Models\AcademicYear;
use App\Models\GradeLevelSubject;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\SubjectKktp;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use App\Observers\StudyGroupSubjectObserver;

/**
 * Menyelaraskan struktur mapel rombel dengan struktur mapel tingkatnya.
 *
 * Sumber kebenaran: `grade_level_subjects` (mapel per tingkat + TA).
 * Turunan langsung:
 *  - `study_group_subjects` (mapel rombel)
 *  - `teacher_admin_books` (buku nilai per mapel/rombel/TA/semester)
 *
 * Dipakai oleh observer rombel, observer mapel tingkat, dan perintah
 * sinkronisasi periode agar tidak ada lagi rombel yang kosong mapel.
 */
class RombelSubjectProvisioner
{
    /**
     * @return array{assignments:int, books:int}
     */
    public function provision(StudyGroup $group, bool $createBooks = true): array
    {
        $group->loadMissing(['school', 'gradeLevel']);

        $academicYear = $group->academic_year_id
            ? AcademicYear::find($group->academic_year_id)
            : AcademicYear::where('is_active', true)->first();

        if (! $academicYear || ! $group->grade_level_id) {
            return ['assignments' => 0, 'books' => 0];
        }

        $rows = GradeLevelSubject::query()
            ->forAcademicYear($academicYear->id)
            ->orderByAcademicYearFirst()
            ->where('grade_level_id', $group->grade_level_id)
            ->where('is_active', true)
            ->get()
            ->unique('subject_id');

        if ($rows->isEmpty()) {
            return ['assignments' => 0, 'books' => 0];
        }

        $fallbackTeacherId = $group->homeroom_teacher_id
            ?: $group->school?->principal_user_id;

        $assignments = 0;
        $books = 0;

        // Bulk provisioning tidak perlu memicu job per baris; buku nilai
        // dibuat langsung oleh service ini.
        StudyGroupSubjectObserver::disable();

        try {
            foreach ($rows as $row) {
                $subject = StudyGroupSubject::withTrashed()->firstOrNew([
                    'study_group_id' => $group->id,
                    'subject_id' => $row->subject_id,
                    'academic_year_id' => $academicYear->id,
                ]);

                $wasRecentlyCreated = ! $subject->exists;

                if ($subject->trashed()) {
                    $subject->restore();
                }

                $subject->fill([
                    'school_id' => $group->school_id,
                    'weekly_hours' => (float) ($row->allocation_hours ?: 2),
                    'is_active' => true,
                ])->save();

                if ($wasRecentlyCreated) {
                    $assignments++;
                }

                if (! $createBooks) {
                    continue;
                }

                $teacherId = $subject->teacher_id
                    ?: TeachingAssignment::query()
                        ->where('school_id', $group->school_id)
                        ->where('study_group_id', $group->id)
                        ->where('subject_id', $row->subject_id)
                        ->where('academic_year_id', $academicYear->id)
                        ->where('status', 'active')
                        ->value('teacher_id')
                    ?: $fallbackTeacherId;

                if (! $teacherId) {
                    continue;
                }

                $existingBook = TeacherAdminBook::query()
                    ->where('subject_id', $row->subject_id)
                    ->where('study_group_id', $group->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('semester', $academicYear->semester)
                    ->first();

                if ($existingBook) {
                    $changes = [];

                    if (! $existingBook->teacher_id) {
                        $changes['teacher_id'] = $teacherId;
                    }
                    if ((string) $existingBook->school_id !== (string) $group->school_id) {
                        $changes['school_id'] = $group->school_id;
                    }
                    if (! $existingBook->is_active) {
                        $changes['is_active'] = true;
                    }

                    if ($changes !== []) {
                        $existingBook->forceFill($changes)->save();
                    }

                    continue;
                }

                $kktpId = SubjectKktp::query()
                    ->where('school_id', $group->school_id)
                    ->where('subject_id', $row->subject_id)
                    ->where('grade_level_id', $group->grade_level_id)
                    ->where('academic_year_id', $academicYear->id)
                    ->where('semester', $academicYear->semester)
                    ->value('id');

                TeacherAdminBook::create([
                    'teacher_id' => $teacherId,
                    'subject_id' => $row->subject_id,
                    'study_group_id' => $group->id,
                    'school_id' => $group->school_id,
                    'academic_year_id' => $academicYear->id,
                    'semester' => $academicYear->semester,
                    'kktp_id' => $kktpId,
                    'is_active' => true,
                ]);

                $books++;
            }
        } finally {
            StudyGroupSubjectObserver::enable();
        }

        return ['assignments' => $assignments, 'books' => $books];
    }

    /**
     * Sebarkan satu baris mapel tingkat ke seluruh rombel aktif pada
     * tingkat dan tahun ajaran yang sama.
     */
    public function provisionGradeLevelRow(GradeLevelSubject $row): int
    {
        $academicYearId = $row->academic_year_id
            ?: AcademicYear::where('is_active', true)->value('id');

        $groups = StudyGroup::query()
            ->with(['school'])
            ->where('grade_level_id', $row->grade_level_id)
            ->where('is_active', true)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->get();

        foreach ($groups as $group) {
            $this->provision($group);
        }

        return $groups->count();
    }
}
