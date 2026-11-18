<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\School;
use App\Models\Subject;
use App\Services\SchoolUnitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Lengkapi struktur mapel tingkat yang masih kosong.
 *
 * Sumber, berurutan:
 *  1. Unit operasional → salin dari unit induk pada tingkat yang sama.
 *  2. Sekolah yang sama → salin dari tingkat lain yang sudah punya struktur
 *     (mis. PPS Wustho 7–9 memakai struktur Ulya kelas 10 sebagai titik awal).
 *  3. Sekolah lain dengan jenjang & gender sama pada tingkat yang sama.
 *
 * Setelah baris mapel tingkat dibuat, observer akan otomatis menyusulkan
 * mapel rombel dan buku nilainya.
 */
class SeedGradeLevelSubjects extends Command
{
    protected $signature = 'academic:seed-grade-subjects
                            {--school= : Batasi ke satu sekolah (UUID)}
                            {--dry-run : Hitung tanpa menulis}';

    protected $description = 'Lengkapi struktur mapel per tingkat dari unit induk/tingkat lain, lalu susulkan ke rombel.';

    public function handle(SchoolUnitService $units): int
    {
        $target = AcademicYear::where('is_active', true)->first();

        if (! $target) {
            $this->error('Tidak ada tahun ajaran aktif.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $schools = School::query()
            ->where('is_active', true)
            ->when($this->option('school'), fn ($q, $id) => $q->where('id', $id))
            ->orderBy('name')
            ->get();

        $this->info(($dryRun ? '[DRY-RUN] ' : '')."Periode aktif: {$target->name} {$target->semester}");

        $seeded = 0;
        $skipped = 0;

        foreach ($schools as $school) {
            $gradeLevels = GradeLevel::query()
                ->where('school_id', $school->id)
                ->where('is_active', true)
                ->orderBy('level')
                ->get();

            foreach ($gradeLevels as $gradeLevel) {
                $existing = GradeLevelSubject::query()
                    ->forAcademicYear($target->id)
                    ->where('grade_level_id', $gradeLevel->id)
                    ->where('is_active', true)
                    ->exists();

                if ($existing) {
                    continue;
                }

                $source = $this->resolveSource($school, $gradeLevel, $target, $units);

                if ($source === null || $source === []) {
                    $this->warn("  ! {$school->name} tingkat {$gradeLevel->level}: tidak ada sumber struktur mapel.");
                    $skipped++;

                    continue;
                }

                $label = $source[0]['source_label'] ?? 'sumber lain';
                $this->line("  → {$school->name} tingkat {$gradeLevel->level}: salin ".count($source)." mapel dari {$label}.");

                if (! $dryRun) {
                    DB::transaction(function () use ($source, $school, $gradeLevel, $target) {
                        foreach ($source as $item) {
                            $subject = $this->resolveSubject($school, $item['subject']);

                            if (! $subject) {
                                continue;
                            }

                            GradeLevelSubject::updateOrCreate(
                                [
                                    'grade_level_id' => $gradeLevel->id,
                                    'subject_id' => $subject->id,
                                    'academic_year_id' => $target->id,
                                    'track' => $item['allocation']->track ?? null,
                                ],
                                [
                                    'allocation_hours' => (int) ($item['allocation']->allocation_hours ?: 2),
                                    'is_active' => true,
                                ],
                            );
                        }
                    });
                }

                $seeded++;
            }
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY-RUN] ' : '')."Tingkat dilengkapi: {$seeded}; dilewati: {$skipped}.");
        $this->line('Observer akan menyusulkan mapel rombel & buku nilai secara otomatis.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{subject: Subject, allocation: GradeLevelSubject, source_label: string}>|null
     */
    private function resolveSource(School $school, GradeLevel $gradeLevel, AcademicYear $target, SchoolUnitService $units): ?array
    {
        // 1. Unit operasional → unit induk pada tingkat yang sama.
        if ($units->isOperational($school->id)) {
            $primaryId = $units->primarySchoolId($school->id);

            if ($primaryId && (string) $primaryId !== (string) $school->id) {
                $primaryGrade = GradeLevel::query()
                    ->where('school_id', $primaryId)
                    ->where('level', $gradeLevel->level)
                    ->first();

                if ($primaryGrade) {
                    $rows = $this->rowsFor($primaryGrade->id, $target->id);

                    if ($rows->isNotEmpty()) {
                        return $this->wrap($rows, 'unit induk ('.$primaryGrade->name.')');
                    }
                }
            }
        }

        // 2. Sekolah yang sama → tingkat lain yang sudah punya struktur.
        $sameSchool = $this->rowsFromOtherLevels($school, $gradeLevel, $target);

        if ($sameSchool->isNotEmpty()) {
            return $this->wrap($sameSchool, 'tingkat lain di sekolah yang sama');
        }

        // 3. Sekolah lain dengan jenjang/gender sama pada tingkat yang sama.
        $siblingSchool = School::query()
            ->where('is_active', true)
            ->where('id', '!=', $school->id)
            ->where('school_level', $school->school_level)
            ->when($school->school_gender, fn ($q) => $q->where('school_gender', $school->school_gender))
            ->get();

        foreach ($siblingSchool as $sibling) {
            $siblingGrade = GradeLevel::query()
                ->where('school_id', $sibling->id)
                ->where('level', $gradeLevel->level)
                ->first();

            if (! $siblingGrade) {
                continue;
            }

            $rows = $this->rowsFor($siblingGrade->id, $target->id);

            if ($rows->isNotEmpty()) {
                return $this->wrap($rows, $sibling->name.' tingkat '.$siblingGrade->level);
            }
        }

        return null;
    }

    private function rowsFor(string $gradeLevelId, string $academicYearId)
    {
        return GradeLevelSubject::query()
            ->with('subject')
            ->forAcademicYear($academicYearId)
            ->orderByAcademicYearFirst()
            ->where('grade_level_id', $gradeLevelId)
            ->where('is_active', true)
            ->get()
            ->unique('subject_id')
            ->filter(fn ($row) => $row->subject !== null)
            ->values();
    }

    private function rowsFromOtherLevels(School $school, GradeLevel $current, AcademicYear $target)
    {
        $others = GradeLevel::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->where('id', '!=', $current->id)
            ->orderByRaw('ABS(level - ?) ASC', [$current->level])
            ->get();

        foreach ($others as $other) {
            $rows = $this->rowsFor($other->id, $target->id);

            if ($rows->isNotEmpty()) {
                return $rows;
            }
        }

        return collect();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, GradeLevelSubject>  $rows
     * @return array<int, array{subject: Subject, allocation: GradeLevelSubject, source_label: string}>
     */
    private function wrap($rows, string $label): array
    {
        return $rows->map(fn (GradeLevelSubject $row) => [
            'subject' => $row->subject,
            'allocation' => $row,
            'source_label' => $label,
        ])->values()->all();
    }

    private function resolveSubject(School $school, Subject $source): ?Subject
    {
        $existing = Subject::query()
            ->where('school_id', $school->id)
            ->where(function ($q) use ($source) {
                $q->where('code', $source->code)
                    ->orWhere('name', $source->name);
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        return Subject::create([
            'school_id' => $school->id,
            'code' => $source->code,
            'name' => $source->name,
            'name_ar' => $source->name_ar,
            'subject_group_id' => $source->subject_group_id,
            'category' => $source->category,
            'source' => $source->source,
            'credit_hours' => $source->credit_hours,
            'is_active' => true,
        ]);
    }
}
