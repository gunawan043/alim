<?php

namespace App\Console\Commands;

use App\Models\GradeLevelSubject;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use Illuminate\Console\Command;

/**
 * Backfill mapel + JP untuk rombel yang belum memiliki study_group_subjects.
 * Dipakai untuk rombel lama sebelum provisioning otomatis ditambahkan.
 */
class ProvisionStudyGroupSubjects extends Command
{
    protected $signature = 'study-groups:provision-subjects
                            {--school= : UUID sekolah}
                            {--academic-year= : UUID tahun ajaran}
                            {--dry-run : Tampilkan jumlah tanpa menulis}';

    protected $description = 'Salin mapel dan JP dari tingkat ke rombel yang belum memiliki assignment.';

    public function handle(): int
    {
        $query = StudyGroup::query()
            ->with('gradeLevel')
            ->where('is_active', true)
            ->when($this->option('school'), fn ($q, $id) => $q->where('school_id', $id))
            ->when($this->option('academic-year'), fn ($q, $id) => $q->where('academic_year_id', $id));

        $created = 0;
        $groups = 0;

        foreach ($query->cursor() as $group) {
            if (! $group->grade_level_id) {
                continue;
            }

            $subjects = GradeLevelSubject::query()
                ->forAcademicYear($group->academic_year_id)
                ->orderByAcademicYearFirst()
                ->where('grade_level_id', $group->grade_level_id)
                ->where('is_active', true)
                ->get()
                ->unique('subject_id');

            if ($subjects->isEmpty()) {
                continue;
            }

            $groups++;

            foreach ($subjects as $subject) {
                $exists = StudyGroupSubject::query()
                    ->where('study_group_id', $group->id)
                    ->where('subject_id', $subject->subject_id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                if (! $this->option('dry-run')) {
                    StudyGroupSubject::create([
                        'school_id' => $group->school_id,
                        'academic_year_id' => $group->academic_year_id,
                        'study_group_id' => $group->id,
                        'subject_id' => $subject->subject_id,
                        'weekly_hours' => (float) ($subject->allocation_hours ?: 2),
                        'is_active' => true,
                    ]);
                }

                $created++;
            }
        }

        $this->info(($this->option('dry-run') ? '[dry-run] ' : '')."Rombel diproses: {$groups}; assignment dibuat: {$created}.");

        return self::SUCCESS;
    }
}
