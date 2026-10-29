<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskType;
use App\Models\GtkCrossUnitAssignment;
use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\InstitutionDecree;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\StructuralAssignment;
use App\Models\StructuralPosition;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\SubjectKktp;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sumber tunggal untuk isi matriks SK Pembagian Tugas:
 *  - menyusun data tampilan (live maupun snapshot),
 *  - menyimpan/menyinkronkan matriks (penugasan, tugas tambahan, buku admin),
 *  - membekukan snapshot saat SK diterbitkan.
 *
 * Dipakai bersama oleh InstitutionDecreeController, TeachingAssignmentController,
 * dan proses import agar hasilnya selalu sama.
 */
class InstitutionDecreeMatrixService
{
    /**
     * Data untuk halaman detail/cetak. Default memakai snapshot agar cetak ulang
     * tidak berubah ketika data master diperbarui.
     */
    public function renderData(InstitutionDecree $decree, bool $preferSnapshot = true): array
    {
        if ($preferSnapshot) {
            $snapshot = $this->decodeSnapshot($decree);
            if ($snapshot !== null) {
                return $this->toViewData($snapshot, true, $decree->matrix_snapshot_at);
            }
        }

        return $this->toViewData($this->buildSnapshot($decree), false, null);
    }

    /**
     * Susun snapshot dari data terkini, lalu simpan ke SK.
     */
    public function refreshSnapshot(InstitutionDecree $decree): array
    {
        $snapshot = $this->buildSnapshot($decree);
        $snapshot['captured_at'] = now()->toIso8601String();

        $decree->forceFill([
            'matrix_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            'matrix_snapshot_at' => now(),
        ])->save();

        return $snapshot;
    }

    /**
     * Snapshot yang sudah tersimpan (null bila SK lama/belum pernah dibekukan).
     */
    public function decodeSnapshot(InstitutionDecree $decree): ?array
    {
        $raw = $decree->matrix_snapshot;

        if (blank($raw)) {
            return null;
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) && isset($decoded['rows']) ? $decoded : null;
    }

    /**
     * Bangun snapshot matriks dari kondisi database saat ini.
     */
    public function buildSnapshot(InstitutionDecree $decree): array
    {
        $decree->loadMissing(['academicYear', 'school', 'signer', 'teachingAssignments']);

        $school = $decree->school;
        $schoolId = $decree->school_id;
        $ayId = $decree->academic_year_id;

        $empty = [
            'version' => 1,
            'decree_id' => $decree->id,
            'decree_number' => $decree->decree_number,
            'school_id' => $schoolId,
            'school_name' => $school?->name,
            'academic_year_id' => $ayId,
            'academic_year' => $decree->academicYear?->name,
            'study_groups' => [],
            'grades' => [],
            'rows' => [],
        ];

        if (! $schoolId || ! $ayId) {
            return $empty;
        }

        $crossUnits = GtkCrossUnitAssignment::with('homeWorkUnit:id,name')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $ayId)
            ->active()
            ->get()
            ->keyBy('user_id');

        $tasks = OtherTeacherTask::with(['additionalTaskType:id,nama,kode', 'studyGroup:id,name'])
            ->where('academic_year_id', $ayId)
            ->where('school_id', $schoolId)
            ->where(fn ($q) => $q->where('decree_id', $decree->id)->orWhereNull('decree_id'))
            ->where('is_active', true)
            ->get();

        $assignedTeacherIds = $decree->teachingAssignments
            ->where('status', 'active')
            ->pluck('teacher_id');

        $teacherIds = collect(SchoolGroupService::userIdsForSchool($schoolId))
            ->merge($crossUnits->keys())
            ->merge($tasks->pluck('teacher_id'))
            ->merge($assignedTeacherIds)
            ->unique()
            ->values();

        $nonTeachingIds = usersHavingPermission('general_staff.ineligible');

        $teachers = User::query()
            ->when(! empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds))
            ->whereIn('users.id', $teacherIds)
            ->orderBy('name')
            ->get();

        $subjects = Subject::query()
            ->where(fn ($q) => $q->where('school_id', $schoolId)->orWhereNull('school_id'))
            ->orderBy('name')
            ->get();

        $studyGroups = StudyGroup::with('gradeLevel:id,name,level')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $ayId)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($sg) => [$sg->gradeLevel->level ?? 0, $sg->name])
            ->values();

        $activeAssignments = $decree->teachingAssignments->where('status', 'active');

        $matrix = [];
        foreach ($activeAssignments as $assignment) {
            $matrix[$assignment->teacher_id][$assignment->subject_id][$assignment->study_group_id] = (int) $assignment->weekly_hours;
        }

        $teacherPositions = $this->resolveTeacherPositions($teachers, $crossUnits, $decree, $school);

        [$positionOptions, $positionOrder] = $this->positionOrdering();

        $orderedTeachers = $teachers->sortBy(function ($teacher) use ($teacherPositions, $positionOrder) {
            $position = $teacherPositions[$teacher->id] ?? '';

            return sprintf(
                '%d|%03d|%s',
                $this->matrixPositionRank($position),
                $positionOrder->get(mb_strtolower(trim($position)), 999),
                mb_strtolower($teacher->name)
            );
        })->values();

        $rows = [];
        foreach ($orderedTeachers as $teacher) {
            $teacherAssignments = $activeAssignments->where('teacher_id', $teacher->id);
            $teacherTasks = $tasks->where('teacher_id', $teacher->id);

            // Semua GTK pada roster tetap ditampilkan, termasuk Kepala/Staf Tata
            // Usaha yang tidak mengajar. Ini menyamakan detail & cetak dengan
            // halaman Buat SK yang memang menampilkan seluruh roster.
            $subjectRows = [];
            $sebaran = 0;

            foreach ($teacherAssignments->groupBy('subject_id') as $subjectId => $subjectAssignments) {
                $hours = [];
                $rowTotal = 0;

                foreach ($studyGroups as $sg) {
                    $value = $matrix[$teacher->id][$subjectId][$sg->id] ?? null;
                    $hours[$sg->id] = $value;

                    if ($value) {
                        $rowTotal += $value;
                    }
                }

                $sebaran += $rowTotal;

                $subjectRows[] = [
                    'subject_id' => $subjectId,
                    'subject_name' => $subjects->firstWhere('id', $subjectId)?->name ?? '-',
                    'is_coordinator' => (bool) ($subjectAssignments->first()?->is_coordinator),
                    'hours' => $hours,
                    'total' => $rowTotal,
                ];
            }

            $taskRows = $teacherTasks->map(fn ($task) => [
                'id' => $task->id,
                'name' => $task->additionalTaskType?->nama ?? $task->task_name,
                'task_code' => $task->additionalTaskType?->kode ?? $task->task_code,
                'study_group_id' => $task->study_group_id,
                'study_group_name' => $task->studyGroup?->name,
                'weekly_hours' => (int) $task->weekly_hours,
            ])->values()->all();

            $taskHours = (int) $teacherTasks->sum('weekly_hours');
            $cross = $crossUnits->get($teacher->id);

            $rows[] = [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'teacher_role' => $teacher->getRoleNames()->first(),
                'position' => $teacherPositions[$teacher->id] ?? 'GTK',
                'external' => (bool) $cross,
                'home_work_unit' => $cross?->homeWorkUnit?->name,
                'subjects' => $subjectRows,
                'tasks' => $taskRows,
                'task_hours' => $taskHours,
                'sebaran' => $sebaran,
                'total_hours' => $sebaran + $taskHours,
            ];
        }

        $grades = $studyGroups
            ->groupBy(fn ($sg) => $sg->gradeLevel->level ?? 0)
            ->sortKeys()
            ->map(fn ($groups, $level) => [
                'level' => (int) $level,
                'name' => $groups->first()->gradeLevel->name ?? "Kelas {$level}",
                'groups' => $groups->map(fn ($sg) => ['id' => $sg->id, 'name' => $sg->name])->values()->all(),
            ])
            ->values()
            ->all();

        return array_merge($empty, [
            'study_groups' => $studyGroups->map(fn ($sg) => [
                'id' => $sg->id,
                'name' => $sg->name,
                'grade_name' => $sg->gradeLevel->name ?? null,
                'level' => $sg->gradeLevel->level ?? 0,
            ])->values()->all(),
            'grades' => $grades,
            'rows' => $rows,
        ]);
    }

    /**
     * Ubah snapshot menjadi variabel view yang dipakai halaman detail & cetak.
     */
    public function toViewData(array $snapshot, bool $isSnapshot = true, $snapshotAt = null): array
    {
        $studyGroups = collect($snapshot['study_groups'] ?? [])->map(fn ($sg) => (array) $sg);

        return [
            'matrixRows' => $snapshot['rows'] ?? [],
            'sortedGrades' => $snapshot['grades'] ?? [],
            'studyGroups' => $studyGroups,
            'matrixIsSnapshot' => $isSnapshot,
            'matrixSnapshotAt' => $snapshotAt,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // PENYIMPANAN MATRIKS
    // ─────────────────────────────────────────────────────────────

    /**
     * Sinkronkan matriks SK dengan payload editor/import.
     *
     * Payload assignments: teacher_id => subject_id => study_group_id => jam
     * (jam boleh berupa array ['hours' => 3, 'is_coordinator' => true]).
     * Payload tasks: daftar baris tugas tambahan.
     */
    public function sync(
        InstitutionDecree $decree,
        ?array $assignments,
        ?array $tasks = [],
        ?string $schoolId = null,
        bool $replaceTasks = true
    ): array {
        $schoolId = $decree->school_id ?: $schoolId;

        if (! $schoolId || ! $decree->academic_year_id) {
            throw new \InvalidArgumentException('Sekolah dan tahun ajaran SK wajib terisi.');
        }

        return DB::transaction(function () use ($decree, $assignments, $tasks, $schoolId, $replaceTasks) {
            $result = $assignments === null
                ? [
                    'assignments_created' => 0,
                    'assignments_updated' => 0,
                    'assignments_deleted' => 0,
                    'assignments_skipped' => 0,
                    'books_created' => 0,
                    'messages' => [],
                ]
                : $this->syncAssignments($decree, $assignments, $schoolId);

            $taskResult = $tasks === null
                ? [
                    'tasks_created' => 0,
                    'tasks_updated' => 0,
                    'tasks_deleted' => 0,
                    'tasks_skipped' => 0,
                    'messages' => [],
                ]
                : $this->syncTasks($decree, $tasks, $schoolId, $replaceTasks);

            return array_merge($result, $taskResult);
        });
    }

    private function syncAssignments(InstitutionDecree $decree, array $assignments, string $schoolId): array
    {
        $ayId = $decree->academic_year_id;
        $messages = [];
        $skipped = 0;

        $teacherIds = collect($assignments)->keys()->filter()->unique()->values();
        $validTeachers = User::whereIn('id', $teacherIds)->pluck('id')->map(fn ($id) => (string) $id)->flip();

        $subjectIds = collect($assignments)->flatMap(fn ($subjects) => array_keys((array) $subjects))->unique()->values();
        $validSubjects = Subject::whereIn('id', $subjectIds)
            ->where(fn ($q) => $q->where('school_id', $schoolId)->orWhereNull('school_id'))
            ->get()
            ->keyBy(fn ($subject) => (string) $subject->id);

        $groupIds = collect($assignments)
            ->flatMap(fn ($subjects) => collect($subjects)->flatMap(fn ($groups) => array_keys((array) $groups)))
            ->unique()
            ->values();
        $validGroups = StudyGroup::whereIn('id', $groupIds)
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $ayId)
            ->get()
            ->keyBy(fn ($group) => (string) $group->id);

        $crossUnits = GtkCrossUnitAssignment::where('school_id', $schoolId)
            ->where('academic_year_id', $ayId)
            ->active()
            ->get()
            ->keyBy('user_id');

        $desired = [];
        $claimedPairs = [];

        foreach ($assignments as $teacherId => $subjects) {
            if (! $validTeachers->has((string) $teacherId)) {
                $skipped++;
                $messages[] = "GTK tidak ditemukan: {$teacherId}";

                continue;
            }

            foreach ((array) $subjects as $subjectId => $groups) {
                $subject = $validSubjects->get((string) $subjectId);

                if (! $subject) {
                    $skipped++;
                    $messages[] = "Mata pelajaran tidak ditemukan: {$subjectId}";

                    continue;
                }

                foreach ((array) $groups as $groupId => $value) {
                    $hours = is_array($value) ? (int) ($value['hours'] ?? 0) : (int) $value;
                    $isCoordinator = is_array($value) ? (bool) ($value['is_coordinator'] ?? false) : false;

                    if ($hours <= 0) {
                        continue;
                    }

                    if (! $validGroups->has((string) $groupId)) {
                        $skipped++;
                        $messages[] = "Rombel tidak ditemukan pada tahun ajaran SK: {$groupId}";

                        continue;
                    }

                    $pairKey = $subjectId.'|'.$groupId;
                    if (isset($claimedPairs[$pairKey]) && ! $subject->allowsMultipleTeachers()) {
                        $skipped++;
                        $messages[] = 'Mapel '.$subject->name.' pada rombel '.$validGroups->get((string) $groupId)->name.' sudah dipegang GTK lain.';

                        continue;
                    }
                    $claimedPairs[$pairKey] = $teacherId;

                    $desired["{$teacherId}|{$subjectId}|{$groupId}"] = [
                        'teacher_id' => $teacherId,
                        'subject_id' => $subjectId,
                        'study_group_id' => $groupId,
                        'weekly_hours' => $hours,
                        'is_coordinator' => $isCoordinator,
                    ];
                }
            }
        }

        $existing = TeachingAssignment::where('decree_id', $decree->id)->lockForUpdate()->get()
            ->keyBy(fn ($row) => "{$row->teacher_id}|{$row->subject_id}|{$row->study_group_id}");

        $deleted = 0;
        $removedRows = [];
        foreach ($existing as $key => $row) {
            if (! isset($desired[$key])) {
                $removedRows[] = $row->only(['teacher_id', 'subject_id', 'study_group_id']);
                $row->delete();
                $deleted++;
            }
        }

        // Buku admin hanya dinonaktifkan bila tidak ada penugasan aktif lain
        // (mis. masih dipakai SK lain) untuk guru + mapel + rombel yang sama.
        $semester = AcademicYear::find($ayId)?->semester ?? 'ganjil';
        foreach ($removedRows as $removed) {
            $stillActive = TeachingAssignment::where('teacher_id', $removed['teacher_id'])
                ->where('subject_id', $removed['subject_id'])
                ->where('study_group_id', $removed['study_group_id'])
                ->where('academic_year_id', $ayId)
                ->where('status', 'active')
                ->exists();

            if (! $stillActive) {
                TeacherAdminBook::where('teacher_id', $removed['teacher_id'])
                    ->where('subject_id', $removed['subject_id'])
                    ->where('study_group_id', $removed['study_group_id'])
                    ->where('academic_year_id', $ayId)
                    ->where('semester', $semester)
                    ->update(['is_active' => false]);
            }
        }

        $created = 0;
        $updated = 0;
        $booksCreated = 0;
        $kktpCache = [];

        foreach ($desired as $key => $row) {
            $model = $existing->get($key);
            $payload = [
                'decree_id' => $decree->id,
                'teacher_id' => $row['teacher_id'],
                'subject_id' => $row['subject_id'],
                'study_group_id' => $row['study_group_id'],
                'school_id' => $schoolId,
                'academic_year_id' => $ayId,
                'weekly_hours' => $row['weekly_hours'],
                'role' => $model?->role ?? 'guru_mapel',
                'status' => 'active',
                'assignment_source' => $crossUnits->has($row['teacher_id']) ? 'pondok' : 'unit',
                'gtk_cross_unit_assignment_id' => $crossUnits->get($row['teacher_id'])?->id,
                'is_coordinator' => $row['is_coordinator'] || (bool) $model?->is_coordinator,
            ];

            if ($model) {
                $model->update($payload);
                $updated++;
            } else {
                TeachingAssignment::create($payload);
                $created++;
            }

            // Buku admin harus ada untuk setiap penugasan aktif, termasuk saat SK baru dibuat.
            $group = $validGroups->get((string) $row['study_group_id']);
            $gradeLevelId = $group?->grade_level_id;
            $kktpKey = $row['subject_id'].'|'.$gradeLevelId;
            if (! array_key_exists($kktpKey, $kktpCache)) {
                $kktpCache[$kktpKey] = $gradeLevelId
                    ? SubjectKktp::where('subject_id', $row['subject_id'])
                        ->where('grade_level_id', $gradeLevelId)
                        ->where('academic_year_id', $ayId)
                        ->where('semester', $semester)
                        ->first()
                    : null;
            }

            $book = TeacherAdminBook::updateOrCreate(
                [
                    'teacher_id' => $row['teacher_id'],
                    'subject_id' => $row['subject_id'],
                    'study_group_id' => $row['study_group_id'],
                    'academic_year_id' => $ayId,
                    'semester' => $semester,
                ],
                [
                    'school_id' => $schoolId,
                    'kktp_id' => $kktpCache[$kktpKey]?->id,
                    'is_active' => true,
                ]
            );

            if ($book->wasRecentlyCreated) {
                $booksCreated++;
            }
        }

        return [
            'assignments_created' => $created,
            'assignments_updated' => $updated,
            'assignments_deleted' => $deleted,
            'assignments_skipped' => $skipped,
            'books_created' => $booksCreated,
            'messages' => $messages,
        ];
    }

    private function syncTasks(InstitutionDecree $decree, array $tasks, string $schoolId, bool $replace): array
    {
        $ayId = $decree->academic_year_id;
        $workUnitId = School::find($schoolId)?->work_unit_id;
        $messages = [];
        $skipped = 0;

        // Baris milik SK ini boleh dihapus saat tidak lagi ada di payload.
        $managed = OtherTeacherTask::where('school_id', $schoolId)
            ->where('academic_year_id', $ayId)
            ->where('decree_id', $decree->id)
            ->lockForUpdate()
            ->get();

        // Baris lama (belum terikat SK) ikut dikenali agar tidak terduplikasi;
        // ketika disentuh, baris tersebut dipindahkan ke SK ini.
        $legacy = OtherTeacherTask::where('school_id', $schoolId)
            ->where('academic_year_id', $ayId)
            ->whereNull('decree_id')
            ->get();

        $candidates = $managed->merge($legacy);
        $used = [];
        $created = 0;
        $updated = 0;

        foreach ($tasks as $task) {
            $teacherId = $task['teacher_id'] ?? null;
            $name = trim((string) ($task['task_name'] ?? ''));

            if (! $teacherId || $name === '') {
                $skipped++;

                continue;
            }

            if (! User::whereKey($teacherId)->exists()) {
                $skipped++;
                $messages[] = "GTK tugas tambahan tidak ditemukan: {$teacherId}";

                continue;
            }

            $studyGroupId = $task['study_group_id'] ?? null;
            if ($studyGroupId) {
                $validGroup = StudyGroup::whereKey($studyGroupId)
                    ->where('school_id', $schoolId)
                    ->where('academic_year_id', $ayId)
                    ->exists();

                if (! $validGroup) {
                    $skipped++;
                    $messages[] = "Rombel tugas tambahan tidak ditemukan: {$studyGroupId}";
                    $studyGroupId = null;
                }
            }

            $type = AdditionalTaskType::resolve(
                $task['additional_task_type_id'] ?? null,
                $name,
                $task['task_code'] ?? null
            );

            $code = $type?->kode
                ?: ($task['task_code'] ?? null)
                ?: 'custom-'.Str::slug($name);
            $code = Str::limit((string) $code, 50, '');

            $payload = [
                'teacher_id' => $teacherId,
                'school_id' => $schoolId,
                'work_unit_id' => $workUnitId,
                'academic_year_id' => $ayId,
                'decree_id' => $decree->id,
                'additional_task_type_id' => $type?->id,
                'study_group_id' => $studyGroupId,
                'task_name' => $type?->nama ?? $name,
                'task_code' => $code,
                'weekly_hours' => max(0, (int) ($task['weekly_hours'] ?? 0)),
                'is_active' => true,
            ];

            $model = null;
            if (! empty($task['id'])) {
                $model = $candidates->firstWhere('id', $task['id']);
            }

            if (! $model) {
                $model = $candidates->first(fn ($row) => $row->teacher_id === $teacherId
                    && (string) $row->task_code === (string) $code
                    && (string) ($row->study_group_id ?? '') === (string) ($studyGroupId ?? ''));
            }

            if ($model) {
                $model->update($payload);
                $used[$model->id] = true;
                $updated++;
            } else {
                $model = OtherTeacherTask::create($payload);
                $used[$model->id] = true;
                $created++;
            }
        }

        $deleted = 0;
        if ($replace) {
            foreach ($managed as $row) {
                if (! isset($used[$row->id])) {
                    $row->delete();
                    $deleted++;
                }
            }
        }

        return [
            'tasks_created' => $created,
            'tasks_updated' => $updated,
            'tasks_deleted' => $deleted,
            'tasks_skipped' => $skipped,
            'messages' => $messages,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // DATA EDITOR (halaman Edit SK & Matriks)
    // ─────────────────────────────────────────────────────────────

    public function editorData(InstitutionDecree $decree): array
    {
        $decree->loadMissing(['academicYear', 'school']);

        $teacherIds = app(\App\Services\TeacherRosterService::class)->idsForSchool($decree->school_id);
        $assignedTeacherIds = $decree->teachingAssignments()->pluck('teacher_id')->unique()->all();
        $taskTeacherIds = OtherTeacherTask::where('academic_year_id', $decree->academic_year_id)
            ->when($decree->school_id, fn ($q) => $q->where('school_id', $decree->school_id))
            ->where(fn ($q) => $q->where('decree_id', $decree->id)->orWhereNull('decree_id'))
            ->pluck('teacher_id')
            ->unique()
            ->all();

        $crossUnitIds = GtkCrossUnitAssignment::where('school_id', $decree->school_id)
            ->where('academic_year_id', $decree->academic_year_id)
            ->active()
            ->pluck('user_id')
            ->all();

        $teachers = User::where(function ($q) use ($teacherIds, $assignedTeacherIds, $taskTeacherIds) {
            $q->whereIn('id', $teacherIds)
                ->orWhereIn('id', $assignedTeacherIds)
                ->orWhereIn('id', $taskTeacherIds);
        })
            ->when($decree->school_id, fn ($q) => $q->where(function ($q) use ($decree, $crossUnitIds, $assignedTeacherIds, $taskTeacherIds) {
                $q->whereHas('employments', fn ($e) => $e->where('school_id', $decree->school_id))
                    ->orWhereIn('id', array_unique(array_merge($crossUnitIds, $assignedTeacherIds, $taskTeacherIds)));
            }))
            ->orderBy('name')
            ->get();

        $subjects = Subject::query()
            ->when($decree->school_id, fn ($q) => $q->where(fn ($s) => $s->where('school_id', $decree->school_id)->orWhereNull('school_id')))
            ->orderBy('name')
            ->get();

        $studyGroups = collect();
        if ($decree->school_id && $decree->academic_year_id) {
            $studyGroups = StudyGroup::with('gradeLevel:id,name,level')
                ->where('school_id', $decree->school_id)
                ->where('academic_year_id', $decree->academic_year_id)
                ->where('is_active', true)
                ->get()
                ->sortBy(fn ($sg) => [$sg->gradeLevel->level ?? 0, $sg->name])
                ->values();
        }

        $existing = $decree->teachingAssignments()
            ->where('status', 'active')
            ->get()
            ->keyBy(fn ($a) => "{$a->teacher_id}|{$a->subject_id}|{$a->study_group_id}");

        $otherTasks = OtherTeacherTask::with('additionalTaskType:id,nama,kode')
            ->where('academic_year_id', $decree->academic_year_id)
            ->when($decree->school_id, fn ($q) => $q->where('school_id', $decree->school_id))
            ->where(fn ($q) => $q->where('decree_id', $decree->id)->orWhereNull('decree_id'))
            ->where('is_active', true)
            ->get()
            ->map(fn ($task) => [
                'id' => $task->id,
                'teacher_id' => $task->teacher_id,
                'task_name' => $task->additionalTaskType?->nama ?? $task->task_name,
                'task_code' => $task->additionalTaskType?->kode ?? $task->task_code,
                'weekly_hours' => (int) $task->weekly_hours,
                'study_group_id' => $task->study_group_id,
            ])
            ->values()
            ->all();

        $teacherMeta = $teachers->map(fn ($teacher) => [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'role' => $teacher->getRoleNames()->first(),
            'position' => $this->resolveTeacherPositions(collect([$teacher]), collect(), $decree, $decree->school)[$teacher->id] ?? 'GTK',
        ])->values()->all();

        return [
            'decree' => $decree,
            'teachers' => $teachers,
            'teacherMeta' => $teacherMeta,
            'subjects' => $subjects,
            'studyGroups' => $studyGroups,
            'existing' => $existing,
            'otherTasks' => $otherTasks,
            'academicYears' => AcademicYear::orderByDesc('name')->get(),
            'signers' => User::whereIn('id', usersHavingPermission('decree.signer.certifiable'))->orderBy('name')->get(),
            'schools' => School::orderBy('name')->get(),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER
    // ─────────────────────────────────────────────────────────────

    private function resolveTeacherPositions(Collection $teachers, Collection $crossUnits, InstitutionDecree $decree, ?School $school): array
    {
        $ids = $teachers->pluck('id');
        if ($ids->isEmpty()) {
            return [];
        }

        $workUnitId = $school?->work_unit_id;

        $structuralRows = StructuralAssignment::with('position:id,name')
            ->whereIn('user_id', $ids)
            ->where('status', 'active')
            ->when($decree->school_id, fn ($q) => $q->where('school_id', $decree->school_id))
            ->when($decree->academic_year_id, fn ($q) => $q->where('academic_year_id', $decree->academic_year_id))
            ->get()
            ->keyBy('user_id');

        $employmentRows = GtkEmployment::with('jabatanRel:id,name')
            ->whereIn('user_id', $ids)
            ->when($decree->school_id, fn ($q) => $q->where('school_id', $decree->school_id))
            ->orderByDesc('academic_year_id')
            ->get()
            ->groupBy('user_id');

        $workUnitRows = GtkWorkUnit::with('position:id,name')
            ->whereIn('user_id', $ids)
            ->get()
            ->groupBy('user_id');

        $positions = [];
        foreach ($teachers as $teacher) {
            $structural = $structuralRows->get($teacher->id);
            $employment = $employmentRows->get($teacher->id)?->first();
            $homeWorkUnitId = $crossUnits->get($teacher->id)?->home_work_unit_id;
            $targetWorkUnitId = $homeWorkUnitId ?: $workUnitId;
            $workUnitRow = $workUnitRows->get($teacher->id)?->firstWhere('work_unit_id', $targetWorkUnitId);
            $workUnitPosition = $workUnitRow?->position?->name ?? $workUnitRow?->jabatan;

            $positions[$teacher->id] = $workUnitPosition
                ?? $structural?->position?->name
                ?? $employment?->jabatanRel?->name
                ?? $employment?->jabatan
                ?? 'GTK';
        }

        return $positions;
    }

    private function positionOrdering(): array
    {
        $positions = StructuralPosition::active()
            ->whereHas('jenisGtk', fn ($q) => $q->where('nama', 'like', '%Satuan Pendidikan%'))
            ->orderBy('urutan')
            ->orderBy('name')
            ->get(['id', 'name', 'urutan'])
            ->values();

        if ($positions->isEmpty()) {
            $positions = StructuralPosition::active()
                ->orderBy('urutan')
                ->orderBy('name')
                ->get(['id', 'name', 'urutan']);
        }

        $order = $positions->mapWithKeys(fn ($position) => [mb_strtolower($position->name) => (int) $position->urutan]);

        return [$positions, $order];
    }

    private function matrixPositionRank(?string $position): int
    {
        $name = mb_strtolower(trim((string) $position));

        if ($name === '') {
            return 5;
        }

        if (str_contains($name, 'tata usaha') || preg_match('/\btu\b/', $name) === 1) {
            return 9;
        }

        if (str_starts_with($name, 'kepala')) {
            return 1;
        }

        if (str_starts_with($name, 'wakil')) {
            return 2;
        }

        if (str_starts_with($name, 'koordinator')) {
            return 3;
        }

        if (str_starts_with($name, 'staf') || str_starts_with($name, 'staff')) {
            return 6;
        }

        return 4;
    }
}
