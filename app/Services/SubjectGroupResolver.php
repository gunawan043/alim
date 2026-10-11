<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GtkAdditionalTask;
use App\Models\OtherTeacherTask;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Rumpun Mata Pelajaran — sumber data RESMI.
 *
 * Otoritas rumpun hanya dari master data:
 *  - `subjects.subject_group_id` (dipetakan admin melalui UI Master Rumpun).
 *  - `subject_groups` (daftar rumpun resmi + nama tugas tambahan koordinator).
 *
 * `patterns` pada subject_groups HANYA saran pemetaan di UI/command
 * (bukan penentu akses — tidak ada keputusan runtime berbasis regex).
 *
 * Koordinator rumpun ditetapkan melalui tugas tambahan per satuan pendidikan:
 *  - Utama : `other_teacher_tasks` (school_id, academic_year_id, is_active).
 *  - Legacy: `gtk_additional_tasks` (SK; sekolah via decree/kepegawaian).
 *
 * Kolaborasi mapel yang sama LINTAS SATUAN PENDIDIKAN difasilitasi sebagai
 * keanggotaan turunan dari TeachingAssignment (tanpa koordinator lintas satuan
 * dan tanpa jenis tugas tambahan baru).
 */
class SubjectGroupResolver
{
    /** @var array<string, array<int, string>> alias nama tugas koordinator per kode rumpun (termasuk legacy). */
    private const COORDINATOR_ALIASES = [
        'umum' => ['Koordinator Guru Umum'],
        'agama' => ['Koordinator Guru Agama', 'Koordinator Guru Hadits'],
        'bahasa' => ['Koordinator Guru Bahasa', 'Koordinator Guru Bahasa Arab'],
        'tahfidz' => ['Koordinator Guru Tahfidz'],
    ];

    private ?Collection $groupsCache = null;

    /** @return Collection<int, SubjectGroup> */
    public function groups(): Collection
    {
        return $this->groupsCache ??= SubjectGroup::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function groupByCode(string $code): ?SubjectGroup
    {
        return $this->groups()->firstWhere('code', $code);
    }

    /**
     * Rumpun resmi sebuah mapel — HANYA dari pemetaan master
     * (`subjects.subject_group_id`). Tidak ada fallback regex.
     */
    public function groupForSubject(Subject|string|null $subject): ?SubjectGroup
    {
        if (! $subject instanceof Subject) {
            $subject = $subject ? Subject::find($subject) : null;
        }

        if (! $subject || ! $subject->subject_group_id) {
            return null;
        }

        return $this->groups()->firstWhere('id', $subject->subject_group_id)
            ?? SubjectGroup::find($subject->subject_group_id);
    }

    // ─────────────────────────────────────────────────────────────
    // SARAN PEMETAAN (patterns) — untuk UI & alat admin, bukan akses.
    // ─────────────────────────────────────────────────────────────

    public function suggestByName(?string $subjectName): ?SubjectGroup
    {
        $normalized = mb_strtolower(trim((string) $subjectName));

        if ($normalized === '') {
            return null;
        }

        foreach ($this->groups() as $group) {
            foreach (($group->patterns ?? []) as $pattern) {
                $pattern = mb_strtolower(trim((string) $pattern));

                if ($pattern === '') {
                    continue;
                }

                if ($pattern === '*' || str_contains($normalized, $pattern)) {
                    return $group;
                }
            }
        }

        return null;
    }

    /**
     * Terapkan saran pola sebagai pemetaan master (aksi admin eksplisit).
     */
    public function applySuggestion(Subject $subject, bool $force = false): ?SubjectGroup
    {
        if (! $force && $subject->subject_group_id) {
            return $this->groupForSubject($subject);
        }

        $suggested = $this->suggestByName($subject->name);

        if ($suggested && (string) $subject->subject_group_id !== (string) $suggested->id) {
            $subject->forceFill(['subject_group_id' => $suggested->id])->saveQuietly();
        }

        return $this->groupForSubject($subject);
    }

    // ─────────────────────────────────────────────────────────────
    // KEANGGOTAAN
    // ─────────────────────────────────────────────────────────────

    /**
     * Rumpun yang menjadi kewenangan guru (dari tugas mengajar aktif).
     *
     * @return Collection<int, SubjectGroup>
     */
    public function groupsForTeacher(User|string $user): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;

        $groupIds = TeachingAssignment::query()
            ->where('teacher_id', $userId)
            ->where('status', 'active')
            ->with('subject:id,subject_group_id')
            ->get()
            ->pluck('subject.subject_group_id')
            ->filter()
            ->unique()
            ->values();

        return $this->groups()->whereIn('id', $groupIds)->values();
    }

    /**
     * Semua guru yang mengajar mapel dalam rumpun (assignment aktif).
     *
     * @return array<int, string>
     */
    public function teacherIdsForGroup(SubjectGroup|string $group): array
    {
        $subjectIds = $this->subjectIdsInGroup($group);

        if ($subjectIds === []) {
            return [];
        }

        return TeachingAssignment::query()
            ->whereIn('subject_id', $subjectIds)
            ->where('status', 'active')
            ->pluck('teacher_id')
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    public function subjectIdsInGroup(SubjectGroup|string $group): array
    {
        $groupId = $group instanceof SubjectGroup ? $group->id : $group;

        return Subject::query()->where('subject_group_id', $groupId)->pluck('id')->all();
    }

    /**
     * Guru pengampu mapel yang SAMA lintas satuan pendidikan (kolaborasi
     * RPM/soal) — keanggotaan turunan, bukan koordinator lintas satuan.
     *
     * @return array<int, string>
     */
    public function teacherIdsForSubjectAcrossSchools(Subject|string $subject): array
    {
        if (! $subject instanceof Subject) {
            $subject = Subject::find($subject);
        }

        if (! $subject) {
            return [];
        }

        $subjectIds = Subject::query()
            ->where('id', $subject->id)
            ->orWhere('name', $subject->name)
            ->pluck('id')
            ->all();

        return TeachingAssignment::query()
            ->whereIn('subject_id', $subjectIds)
            ->where('status', 'active')
            ->pluck('teacher_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Daftar kelompok guru per mapel lintas satuan pendidikan (untuk UI).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function crossSchoolSubjectGroups(?string $academicYearId = null, int $limit = 60): Collection
    {
        $assignments = TeachingAssignment::query()
            ->with([
                'subject:id,name',
                'teacher:id,name',
                'studyGroup:id,school_id',
                'studyGroup.school:id,name',
            ])
            ->where('status', 'active')
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->get()
            ->filter(fn (TeachingAssignment $a) => $a->subject && $a->teacher);

        return $assignments
            ->groupBy(fn (TeachingAssignment $a) => mb_strtolower(trim((string) $a->subject->name)))
            ->map(function (Collection $rows) {
                $teachers = $rows->map(fn (TeachingAssignment $a) => [
                    'id' => $a->teacher->id,
                    'name' => $a->teacher->name,
                    'school_id' => $a->studyGroup?->school_id,
                    'school' => $a->studyGroup?->school?->name,
                ])->unique('id')->values();

                return [
                    'subject' => $rows->first()->subject->name,
                    'teachers' => $teachers,
                    'teacher_count' => $teachers->count(),
                    'school_count' => $teachers->pluck('school_id')->filter()->unique()->count(),
                ];
            })
            ->sortByDesc('teacher_count')
            ->take($limit)
            ->values();
    }

    // ─────────────────────────────────────────────────────────────
    // KOORDINATOR (tugas tambahan, per satuan pendidikan)
    // ─────────────────────────────────────────────────────────────

    /**
     * Nama tugas resmi + alias legacy untuk sebuah rumpun.
     *
     * @return array<int, string>
     */
    public function coordinatorTaskNames(SubjectGroup $group): array
    {
        return collect(self::COORDINATOR_ALIASES[$group->code] ?? [])
            ->push($group->coordinator_task_name)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Penetapan koordinator rumpun (official + legacy) beserta satuan pendidikan.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function coordinatorAssignmentsForGroup(SubjectGroup $group, ?string $schoolId = null): Collection
    {
        $taskNames = $this->coordinatorTaskNames($group);
        $lowerNames = array_map(fn ($n) => mb_strtolower($n), $taskNames);

        // Utama: tugas tambahan resmi (school-scoped).
        $official = OtherTeacherTask::query()
            ->with(['teacher:id,name', 'school:id,name'])
            ->where('is_active', true)
            ->whereIn('task_name', $taskNames)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get()
            ->map(fn (OtherTeacherTask $task) => [
                'task_id' => $task->id,
                'source' => 'other_teacher_tasks',
                'user_id' => $task->teacher_id,
                'name' => $task->teacher?->name,
                'school_id' => $task->school_id,
                'school' => $task->school?->name,
                'task_name' => $task->task_name,
                'can_deactivate' => true,
            ]);

        // Legacy: tugas tambahan berbasis SK (gtk_additional_tasks).
        $legacyTasks = GtkAdditionalTask::query()
            ->with('user:id,name')
            ->get()
            ->filter(fn (GtkAdditionalTask $task) => in_array(mb_strtolower(trim((string) $task->nama_tugas)), $lowerNames, true));

        $legacy = $legacyTasks->map(function (GtkAdditionalTask $task) {
            $schoolId = $task->decree?->school_id
                ?? \App\Models\GtkEmployment::query()->where('user_id', $task->user_id)->value('school_id');
            $schoolName = $schoolId ? \App\Models\School::query()->whereKey($schoolId)->value('name') : null;

            return [
                'task_id' => $task->id,
                'source' => 'gtk_additional_tasks',
                'user_id' => $task->user_id,
                'name' => $task->user?->name,
                'school_id' => $schoolId,
                'school' => $schoolName,
                'task_name' => $task->nama_tugas,
                'can_deactivate' => false,
            ];
        })->when($schoolId, fn (Collection $rows) => $rows->where('school_id', $schoolId));

        return $official->concat($legacy)
            ->unique(fn ($row) => ($row['user_id'] ?? '').'|'.($row['school_id'] ?? ''))
            ->values();
    }

    /**
     * Rumpun yang dikoordinasikan user (lintas satuan — untuk permission bag;
     * keputusan akses tetap per konteks satuan saat dipakai).
     *
     * @return Collection<int, SubjectGroup>
     */
    public function coordinatorGroupsForUser(User|string $user): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->groups()
            ->filter(fn (SubjectGroup $group) => in_array(
                (string) $userId,
                array_map('strval', $this->coordinatorUserIdsForGroup($group)),
                true
            ))
            ->values();
    }

    /**
     * @return array<int, string>
     */
    public function coordinatorUserIdsForGroup(SubjectGroup|string $group, ?string $schoolId = null): array
    {
        $group = $group instanceof SubjectGroup ? $group : SubjectGroup::find($group);

        if (! $group) {
            return [];
        }

        return $this->coordinatorAssignmentsForGroup($group, $schoolId)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function isCoordinatorOf(User|string $user, SubjectGroup|string $group, ?string $schoolId = null): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return in_array((string) $userId, array_map('strval', $this->coordinatorUserIdsForGroup($group, $schoolId)), true);
    }
}
