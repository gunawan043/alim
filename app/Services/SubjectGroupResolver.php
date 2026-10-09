<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GtkAdditionalTask;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Tahap 1 — Rumpun Mata Pelajaran.
 *
 * Sumber kebenaran:
 *  - Master rumpun & pola nama mapel  : tabel `subject_groups`.
 *  - Keanggotaan mapel                : `subjects.subject_group_id` (resmi, queryable).
 *  - Keanggotaan guru                 : TeachingAssignment aktif → mapel → rumpun.
 *  - Koordinator rumpun               : tugas tambahan (gtk_additional_tasks.nama_tugas).
 */
class SubjectGroupResolver
{
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

    /**
     * Tentukan rumpun dari nama mapel menggunakan pola master (urutan sort_order).
     */
    public function resolveByName(?string $subjectName): ?SubjectGroup
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
     * Tetapkan atribut subject_group_id pada model (tanpa menyimpan).
     * Nilai yang sudah diisi admin tidak ditimpa kecuali $force.
     */
    public function applyToSubject(Subject $subject, bool $force = false): void
    {
        if (! $force && $subject->subject_group_id) {
            return;
        }

        $group = $this->resolveByName($subject->name);

        if ($group) {
            $subject->subject_group_id = $group->id;
        }
    }

    /**
     * Tetapkan & simpan pemetaan mapel → rumpun.
     */
    public function syncSubject(Subject $subject, bool $force = false): ?SubjectGroup
    {
        $this->applyToSubject($subject, $force);

        if ($subject->isDirty('subject_group_id')) {
            $subject->saveQuietly();
        }

        return $subject->subject_group_id
            ? $this->groups()->firstWhere('id', $subject->subject_group_id)
            : null;
    }

    public function groupForSubject(Subject|string|null $subject): ?SubjectGroup
    {
        if (! $subject instanceof Subject) {
            $subject = $subject ? Subject::find($subject) : null;
        }

        if (! $subject) {
            return null;
        }

        if ($subject->subject_group_id) {
            return $this->groups()->firstWhere('id', $subject->subject_group_id)
                ?? SubjectGroup::find($subject->subject_group_id);
        }

        return $this->resolveByName($subject->name);
    }

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
     * Rumpun yang dikoordinasikan user (tugas tambahan resmi).
     *
     * @return Collection<int, SubjectGroup>
     */
    public function coordinatorGroupsForUser(User|string $user): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;

        $taskNames = GtkAdditionalTask::query()
            ->where('user_id', $userId)
            ->pluck('nama_tugas')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->filter()
            ->unique();

        if ($taskNames->isEmpty()) {
            return collect();
        }

        return $this->groups()
            ->filter(function (SubjectGroup $group) use ($taskNames) {
                return $group->coordinator_task_name
                    && $taskNames->contains(mb_strtolower(trim($group->coordinator_task_name)));
            })
            ->values();
    }

    public function isCoordinatorOf(User|string $user, SubjectGroup|string $group): bool
    {
        $groupId = $group instanceof SubjectGroup ? $group->id : $group;

        return $this->coordinatorGroupsForUser($user)->contains('id', $groupId);
    }

    /** @return array<int, string> */
    public function subjectIdsInGroup(SubjectGroup|string $group): array
    {
        $groupId = $group instanceof SubjectGroup ? $group->id : $group;

        return Subject::query()->where('subject_group_id', $groupId)->pluck('id')->all();
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

    /**
     * User pemegang tugas tambahan koordinator rumpun.
     *
     * @return array<int, string>
     */
    public function coordinatorUserIdsForGroup(SubjectGroup|string $group): array
    {
        $group = $group instanceof SubjectGroup ? $group : SubjectGroup::find($group);

        if (! $group?->coordinator_task_name) {
            return [];
        }

        return GtkAdditionalTask::query()
            ->whereRaw('LOWER(nama_tugas) = ?', [mb_strtolower(trim($group->coordinator_task_name))])
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();
    }
}
