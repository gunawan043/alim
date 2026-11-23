<?php

declare(strict_types=1);

namespace App\Services\Evaluasi;

use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\InstitutionDecree;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Lingkup mengajar guru menurut SK Pembagian Tugas yang AKTIF.
 *
 * Bank soal, input soal, impor, dan cetak hanya boleh memakai mata pelajaran
 * dan kelas yang benar-benar diamanahkan SK. Sumber tunggalnya ada di sini
 * agar seluruh titik input memakai aturan yang sama.
 *
 * Penugasan pada SK yang sudah diarsipkan (atau belum aktif) tidak dihitung:
 * `teaching_assignments.status` tetap `active` walau SK-nya ditutup, jadi
 * status SK induk harus diperiksa terpisah.
 */
class TeachingScopeService
{
    public const DECREE_TYPE = 'SK Pembagian Tugas';

    /** @var array<string, Collection<int, TeachingAssignment>> */
    private array $cache = [];

    /**
     * Penugasan aktif guru pada SK yang aktif, untuk sekolah + tahun ajaran.
     *
     * @return Collection<int, TeachingAssignment>
     */
    public function assignments(User $user, ?string $schoolId, ?string $academicYearId): Collection
    {
        if (! $this->schemaReady() || ! $schoolId) {
            return collect();
        }

        $academicYearId ??= AcademicYear::query()->where('is_active', true)->value('id');
        $key = $user->id.'|'.$schoolId.'|'.($academicYearId ?? '');

        return $this->cache[$key] ??= TeachingAssignment::query()
            ->with([
                'subject:id,name,code,subject_group_id',
                'studyGroup:id,name,grade_level_id',
                'decree:id,decree_number,status,decree_type,academic_year_id,school_id',
            ])
            ->where('teacher_id', $user->id)
            ->where('status', 'active')
            ->where('school_id', $schoolId)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->whereHas('decree', function ($q) use ($schoolId, $academicYearId) {
                $q->where('decree_type', self::DECREE_TYPE)
                    ->where('status', InstitutionDecree::STATUS_ACTIVE)
                    ->where('school_id', $schoolId);

                if ($academicYearId) {
                    $q->where('academic_year_id', $academicYearId);
                }
            })
            ->get()
            ->filter(fn (TeachingAssignment $a) => $a->subject !== null)
            ->values();
    }

    /**
     * Mapel yang diamanahkan SK, terurut nama.
     *
     * @return Collection<int, Subject>
     */
    public function subjects(User $user, ?string $schoolId, ?string $academicYearId): Collection
    {
        return $this->assignments($user, $schoolId, $academicYearId)
            ->pluck('subject')
            ->unique('id')
            ->sortBy(fn (Subject $s) => mb_strtolower((string) $s->name))
            ->values();
    }

    /**
     * Kelas (grade level) yang diajar untuk mapel tertentu, terurut level.
     *
     * @return Collection<int, GradeLevel>
     */
    public function gradeLevelsForSubject(User $user, ?string $schoolId, ?string $academicYearId, ?string $subjectId): Collection
    {
        if (! $subjectId) {
            return collect();
        }

        $gradeIds = $this->assignments($user, $schoolId, $academicYearId)
            ->where('subject_id', $subjectId)
            ->map(fn (TeachingAssignment $a) => $a->studyGroup?->grade_level_id)
            ->filter()
            ->unique()
            ->values();

        if ($gradeIds->isEmpty()) {
            return collect();
        }

        return GradeLevel::query()
            ->whereIn('id', $gradeIds)
            ->orderBy('level')
            ->get();
    }

    /**
     * Rombel yang diajar untuk mapel tertentu (untuk ditampilkan di form).
     *
     * @return Collection<int, string>
     */
    public function studyGroupNamesForSubject(User $user, ?string $schoolId, ?string $academicYearId, ?string $subjectId): Collection
    {
        return $this->assignments($user, $schoolId, $academicYearId)
            ->where('subject_id', $subjectId)
            ->map(fn (TeachingAssignment $a) => $a->studyGroup?->name)
            ->filter()
            ->unique()
            ->values();
    }

    /** Mapel berada dalam lingkup SK guru. */
    public function allowsSubject(User $user, ?string $schoolId, ?string $academicYearId, ?string $subjectId): bool
    {
        if (! $subjectId) {
            return false;
        }

        return $this->assignments($user, $schoolId, $academicYearId)
            ->contains(fn (TeachingAssignment $a) => (string) $a->subject_id === (string) $subjectId);
    }

    /** Kelas berada dalam lingkup SK guru untuk mapel tersebut. */
    public function allowsGradeLevel(
        User $user,
        ?string $schoolId,
        ?string $academicYearId,
        ?string $subjectId,
        ?string $gradeLevelId
    ): bool {
        if (! $gradeLevelId) {
            return true;
        }

        return $this->gradeLevelsForSubject($user, $schoolId, $academicYearId, $subjectId)
            ->contains(fn (GradeLevel $g) => (string) $g->id === (string) $gradeLevelId);
    }

    /**
     * Ringkasan konteks SK untuk ditampilkan di UI.
     *
     * @return array{
     *     has_assignment: bool,
     *     decrees: array<int, array{number: string, id: string}>,
     *     subject_count: int,
     *     subjects: array<int, array{id: string, name: string, classes: array<int, string>}>
     * }
     */
    public function summary(User $user, ?string $schoolId, ?string $academicYearId): array
    {
        $rows = $this->assignments($user, $schoolId, $academicYearId);

        $decreeLabels = $rows
            ->map(fn (TeachingAssignment $a) => $a->decree)
            ->filter()
            ->unique('id')
            ->map(fn (InstitutionDecree $d) => ['id' => $d->id, 'number' => $d->decree_number])
            ->values()
            ->all();

        $subjects = $rows
            ->groupBy('subject_id')
            ->map(fn (Collection $group) => [
                'id' => (string) $group->first()->subject_id,
                'name' => (string) $group->first()->subject?->name,
                'classes' => $group
                    ->map(fn (TeachingAssignment $a) => $a->studyGroup?->name)
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
            ])
            ->sortBy(fn (array $s) => mb_strtolower($s['name']))
            ->values()
            ->all();

        return [
            'has_assignment' => $subjects !== [],
            'decrees' => $decreeLabels,
            'subject_count' => count($subjects),
            'subjects' => $subjects,
        ];
    }

    /**
     * Mapel yang boleh dipakai pada sebuah bank soal: untuk pemilik bank
     * memakai lingkup SK-nya, sedangkan bank milik orang lain tetap dapat
     * dibaca tanpa memaksa guru memiliki penugasan pada mapel tersebut.
     *
     * @return Collection<int, Subject>
     */
    public function subjectsForBank(User $user, BankSoal $bank, ?string $schoolId, ?string $academicYearId): Collection
    {
        $own = (string) ($bank->owner_user_id ?? $bank->created_by) === (string) $user->id;

        if (! $own) {
            return Subject::query()->whereKey($bank->subject_id)->get();
        }

        return $this->subjects($user, $schoolId, $academicYearId);
    }

    private function schemaReady(): bool
    {
        try {
            return Schema::hasTable('teaching_assignments')
                && Schema::hasColumn('teaching_assignments', 'decree_id');
        } catch (\Throwable) {
            return false;
        }
    }
}
