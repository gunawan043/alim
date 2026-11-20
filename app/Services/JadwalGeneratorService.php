<?php

namespace App\Services;

use App\Models\JadwalKbm;
use App\Models\GradeLevelSubject;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\User;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JadwalGeneratorService
{
    public function __construct(private TeacherAvailabilityService $availability) {}

    /** Catatan penanda baris blok tahfidz (bukan guru tunggal). */
    public const TAHFIDZ_NOTE = 'TIM TAHFIDZ';

    /** Catatan penanda mapel kurikulum yang belum punya guru di SK. */
    public const NO_TEACHER_NOTE = 'GURU BELUM DITETAPKAN';

    /** @var array<string, bool> "teacherId|day|slot" yang terkunci blok tahfidz */
    private array $tahfidzBusy = [];

    /** @var array<string, bool> subject_id => true untuk mapel tahfidz */
    private array $tahfidzSubjectIds = [];

    /** Apakah daftar mapel tahfidz ditentukan manual lewat setting (bukan deteksi nama). */
    private bool $tahfidzOverride = false;

    /** @var array<string, array<string, mixed>> konteks tim tahfidz per tingkat */
    private array $tahfidzTeams = [];

    /** @var array<string, array{days: array<int, array{day:int, slots:array<int,int>}>, error:?string}> */
    private array $tahfidzWindows = [];

    /** @var array<int, bool> hari yang sudah dipakai blok tahfidz tingkat lain */
    private array $tahfidzUsedDays = [];

    /** Jumlah slot maksimum pada grid fallback (jika sekolah belum punya master slot). */
    public const MAX_PERIODS_PER_DAY = 12;

    public const DAYS_OF_WEEK = [1, 2, 3, 4, 5, 6];

    /** Parameter grid fallback — hanya dipakai bila class_schedule_slots kosong. */
    public const DEFAULT_PERIOD_MINUTES = 45;

    public const DEFAULT_START_HOUR = 7;

    public const DEFAULT_BREAK_AFTER = 4;

    public const DEFAULT_BREAK_MINUTES = 30;

    public const DEFAULT_WEEKLY_HOURS = 2;

    /**
     * Generate jadwal untuk beberapa rombel.
     *
     * Blok TIM TAHFIDZ ditempatkan lebih dulu untuk seluruh rombel, baru mapel
     * lain, supaya slot tahfidz tidak didahului mapel dan guru tim tidak bentrok.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function generateBulk(array $studyGroupIds, string $academicYearId, string $semester, bool $overwrite = false): Collection
    {
        $this->resetTahfidzContext();
        $this->loadTahfidzContext($academicYearId, null);

        $groups = StudyGroup::with('gradeLevel')
            ->whereIn('id', $studyGroupIds)
            ->get()
            ->keyBy('id');

        $assignments = [];
        $blocks = [];

        // Fase 0 — muat SK dan kosongkan jadwal lama lebih dulu, agar jendela
        // blok tahfidz dihitung tanpa terganggu baris rombel lain di tingkat yang sama.
        foreach ($studyGroupIds as $sgId) {
            $group = $groups->get($sgId);

            if (! $group) {
                continue;
            }

            try {
                $assignments[$sgId] = $this->activeAssignments($sgId, $academicYearId);

                if ($overwrite) {
                    $this->clearSchedule($sgId, $academicYearId);
                }
            } catch (\Throwable $e) {
                $assignments[$sgId] = collect();
            }
        }

        // Fase 1 — blok TIM TAHFIDZ untuk semua rombel.
        // Blok tingkat lain yang tidak ikut dihapus tetap dikunci lebih dulu.
        $this->seedTahfidzBusyFromExisting(
            $academicYearId,
            $this->siblingStudyGroupIds($studyGroupIds, $academicYearId)
        );

        foreach ($studyGroupIds as $sgId) {
            $group = $groups->get($sgId);

            if (! $group) {
                continue;
            }

            try {
                $blocks[$sgId] = $this->placeTahfidzBlock(
                    $group,
                    $academicYearId,
                    $this->tahfidzAssignments($assignments[$sgId]),
                    $this->curriculumTahfidzPlan($group)
                );
            } catch (\Throwable $e) {
                $blocks[$sgId] = ['total' => 0, 'placed' => 0, 'error' => $e->getMessage()];
            }
        }

        // Fase 2 — mapel lain per rombel.
        $results = collect();

        foreach ($studyGroupIds as $sgId) {
            $group = $groups->get($sgId);

            if (! $group) {
                $results->push($this->emptySummary($sgId, null, 'Rombel tidak ditemukan.'));

                continue;
            }

            try {
                $results->push($this->buildSummary(
                    $group,
                    $academicYearId,
                    $assignments[$sgId] ?? $this->activeAssignments($sgId, $academicYearId),
                    $blocks[$sgId] ?? null
                ));
            } catch (\Throwable $e) {
                $results->push($this->emptySummary($sgId, $group, $e->getMessage()));
            }
        }

        return $results;
    }

    /**
     * Generate jadwal untuk satu rombel berdasarkan assignment (SK guru) aktif.
     * JP mengikuti teaching_assignments.weekly_hours dengan fallback berjenjang.
     * Slot mengikuti master class_schedule_slots sekolah (termasuk is_break).
     *
     * @return array<string, mixed> Ringkasan: generated, requested, shortages, conflicts
     */
    public function generateForStudyGroup(string $studyGroupId, string $academicYearId, string $semester, bool $overwrite = false): array
    {
        $studyGroup = StudyGroup::with('gradeLevel')->findOrFail($studyGroupId);

        $this->resetTahfidzContext();
        $this->loadTahfidzContext($academicYearId, $studyGroup->school_id);

        $assignments = $this->activeAssignments($studyGroupId, $academicYearId);

        if ($overwrite) {
            $this->clearSchedule($studyGroupId, $academicYearId);
        }

        $this->seedTahfidzBusyFromExisting(
            $academicYearId,
            $this->siblingStudyGroupIds([$studyGroupId], $academicYearId)
        );

        $block = $this->placeTahfidzBlock(
            $studyGroup,
            $academicYearId,
            $this->tahfidzAssignments($assignments),
            $this->curriculumTahfidzPlan($studyGroup)
        );

        return $this->buildSummary($studyGroup, $academicYearId, $assignments, $block);
    }

    /**
     * @return Collection<int, TeachingAssignment>
     */
    private function activeAssignments(string $studyGroupId, string $academicYearId): Collection
    {
        return TeachingAssignment::with(['subject.subjectGroup', 'teacher'])
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->get();
    }

    private function tahfidzAssignments(Collection $assignments): Collection
    {
        return $assignments->filter(fn ($a) => $this->isTahfidzSubject($a->subject_id))->values();
    }

    private function regularAssignments(Collection $assignments): Collection
    {
        return $assignments->reject(fn ($a) => $this->isTahfidzSubject($a->subject_id))->values();
    }

    private function clearSchedule(string $studyGroupId, string $academicYearId): void
    {
        JadwalKbm::where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $academicYearId)
            ->delete();
    }

    /**
     * Bersihkan cache konteks tahfidz sebelum satu proses generate.
     */
    private function resetTahfidzContext(): void
    {
        $this->tahfidzBusy = [];
        $this->tahfidzSubjectIds = [];
        $this->tahfidzOverride = false;
        $this->tahfidzTeams = [];
        $this->tahfidzWindows = [];
        $this->tahfidzUsedDays = [];
    }

    /**
     * Muat daftar mapel tahfidz dan tim guru per tingkat untuk satu tahun ajaran.
     *
     * Tim per tingkat dipakai agar seluruh rombel dalam satu tingkat memperoleh
     * hari & slot blok yang sama — syarat halaqah lintas rombel.
     */
    private function loadTahfidzContext(string $academicYearId, ?string $schoolId): void
    {
        $override = setting('tahfidz.subject_ids');

        if (is_string($override) && trim($override) !== '') {
            $this->tahfidzOverride = true;

            foreach (preg_split('/[\s,;]+/', $override) ?: [] as $subjectId) {
                if ($subjectId !== '') {
                    $this->tahfidzSubjectIds[$subjectId] = true;
                }
            }
        }

        if (empty($this->tahfidzSubjectIds)) {
            $query = Subject::query()->where('is_active', true);

            if ($schoolId) {
                $query->where('school_id', $schoolId);
            }

            foreach ($query->with('subjectGroup')->get() as $subject) {
                if ($this->subjectLooksLikeTahfidz($subject)) {
                    $this->tahfidzSubjectIds[$subject->id] = true;
                }
            }
        }

        if (empty($this->tahfidzSubjectIds)) {
            return;
        }

        $assignments = TeachingAssignment::query()
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->whereIn('subject_id', array_keys($this->tahfidzSubjectIds))
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with([
                'studyGroup:id,school_id,grade_level_id,name',
                'subject:id,name,code,subject_group_id',
            ])
            ->get();

        foreach ($assignments as $assignment) {
            $group = $assignment->studyGroup;

            if (! $group) {
                continue;
            }

            $key = $group->grade_level_id ?: 'rombel:'.$group->id;

            $this->tahfidzTeams[$key] ??= [
                'teachers' => [],
                'subject_id' => null,
                'rombel_totals' => [],
                'school_id' => $group->school_id,
                'grade_level_id' => $group->grade_level_id,
            ];

            if ($assignment->teacher_id) {
                $this->tahfidzTeams[$key]['teachers'][$assignment->teacher_id] = $assignment->teacher_id;
            }

            $this->tahfidzTeams[$key]['subject_id'] ??= $assignment->subject_id;

            $hours = $this->resolveWeeklyHours($assignment, $group);
            $previous = $this->tahfidzTeams[$key]['rombel_totals'][$group->id] ?? 0;
            $this->tahfidzTeams[$key]['rombel_totals'][$group->id] = max($previous, $hours);
        }
    }

    /**
     * ID seluruh rombel satu tingkat, termasuk yang tidak ikut di-generate.
     *
     * Dipakai saat menyemai kunci blok tahfidz: rombel sekelas tidak boleh
     * dianggap "blok tingkat lain", karena blok satu tingkat harus memakai
     * hari & slot yang sama agar halaqah lintas rombel tetap berjalan.
     *
     * @param  array<int, string>  $studyGroupIds
     * @return array<int, string>
     */
    private function siblingStudyGroupIds(array $studyGroupIds, string $academicYearId): array
    {
        $gradeIds = StudyGroup::query()
            ->whereIn('id', $studyGroupIds)
            ->whereNotNull('grade_level_id')
            ->pluck('grade_level_id')
            ->unique()
            ->values();

        if ($gradeIds->isEmpty()) {
            return $studyGroupIds;
        }

        $siblings = StudyGroup::query()
            ->whereIn('grade_level_id', $gradeIds)
            ->where('academic_year_id', $academicYearId)
            ->pluck('id')
            ->all();

        return array_values(array_unique(array_merge($studyGroupIds, $siblings)));
    }

    /**
     * Kunci guru tim pada blok tahfidz yang sudah ada di database.
     *
     * Dipakai saat generate sebagian rombel: blok tingkat lain yang tidak ikut
     * dihapus harus tetap dianggap sibuk agar tidak terjadi bentrok guru.
     *
     * @param  array<int, string>  $excludeStudyGroupIds
     */
    private function seedTahfidzBusyFromExisting(string $academicYearId, array $excludeStudyGroupIds = []): void
    {
        $rows = JadwalKbm::query()
            ->where('academic_year_id', $academicYearId)
            ->whereNull('teacher_id')
            ->where('notes', self::TAHFIDZ_NOTE)
            ->where('is_active', true)
            ->when($excludeStudyGroupIds, fn ($q) => $q->whereNotIn('study_group_id', $excludeStudyGroupIds))
            ->get(['study_group_id', 'day_of_week', 'slot_index']);

        if ($rows->isEmpty()) {
            return;
        }

        $gradeByGroup = StudyGroup::query()
            ->whereIn('id', $rows->pluck('study_group_id')->unique()->values())
            ->pluck('grade_level_id', 'id');

        foreach ($rows as $row) {
            $gradeId = $gradeByGroup[$row->study_group_id] ?? null;
            $key = $gradeId ?: 'rombel:'.$row->study_group_id;
            $teachers = array_keys($this->tahfidzTeams[$key]['teachers'] ?? []);

            foreach ($teachers as $teacherId) {
                $this->tahfidzBusy[$teacherId.'|'.$row->day_of_week.'|'.$row->slot_index] = true;
            }

            $this->tahfidzUsedDays[(int) $row->day_of_week] = true;
        }
    }

    /**
     * Apakah mapel ini termasuk rumpun tahfidz?
     * Sumber utama: `subject_groups.name` mengandung "tahfidz"/"tahfiz".
     * Setting `tahfidz.subject_ids` (dipisah koma) menimpa deteksi ini.
     */
    private function isTahfidzSubject(?string $subjectId): bool
    {
        if (! $subjectId) {
            return false;
        }

        if (array_key_exists($subjectId, $this->tahfidzSubjectIds)) {
            return $this->tahfidzSubjectIds[$subjectId];
        }

        if ($this->tahfidzOverride) {
            return $this->tahfidzSubjectIds[$subjectId] = false;
        }

        $subject = Subject::with('subjectGroup')->find($subjectId);

        return $this->tahfidzSubjectIds[$subjectId] = $subject
            ? $this->subjectLooksLikeTahfidz($subject)
            : false;
    }

    private function subjectLooksLikeTahfidz(Subject $subject): bool
    {
        $group = mb_strtolower($subject->subjectGroup?->name ?? '');
        $name = mb_strtolower($subject->name ?? '');
        $code = mb_strtolower($subject->code ?? '');

        return str_contains($group, 'tahfidz')
            || str_contains($group, 'tahfiz')
            || str_contains($name, 'tahfidz')
            || str_contains($name, 'tahfiz')
            || $code === 'thf';
    }

    /**
     * Tempatkan blok TIM TAHFIDZ untuk satu rombel.
     *
     * Blok ditulis satu baris per slot dengan `teacher_id = NULL` dan
     * `notes = 'TIM TAHFIDZ'`, lalu seluruh guru tim ditandai sibuk pada slot
     * tersebut agar mapel lain tidak menempati jam yang sama.
     *
     * @param  Collection<int, TeachingAssignment>  $tahfidzAssignments
     * @return array<string, mixed>
     */
    private function placeTahfidzBlock(
        StudyGroup $group,
        string $academicYearId,
        Collection $tahfidzAssignments,
        ?array $curriculumPlan = null,
    ): array {
        $result = [
            'total' => 0,
            'placed' => 0,
            'days' => [],
            'team' => [],
            'subject_id' => null,
            'status' => 'kosong',
            'message' => null,
        ];

        $curriculumHours = (int) ($curriculumPlan['hours'] ?? 0);
        $curriculumSubjectId = $curriculumPlan['subject_id'] ?? null;

        if ($tahfidzAssignments->isEmpty() && $curriculumHours <= 0) {
            $result['message'] = 'Rombel ini tidak punya mapel tahfidz di kurikulum maupun SK.';

            return $result;
        }

        $key = $group->grade_level_id ?: 'rombel:'.$group->id;
        $context = $this->tahfidzTeams[$key] ?? null;

        // Mode satu rombel / tanpa SK: konteks tingkat belum dimuat, susun dari kurikulum + SK bila ada.
        if (! $context) {
            $teachers = [];

            foreach ($tahfidzAssignments as $assignment) {
                if ($assignment->teacher_id) {
                    $teachers[$assignment->teacher_id] = $assignment->teacher_id;
                }
            }

            $fallbackHours = $tahfidzAssignments->max(fn ($a) => $this->resolveWeeklyHours($a, $group)) ?: 0;

            $context = [
                'teachers' => $teachers,
                'subject_id' => $curriculumSubjectId ?? $tahfidzAssignments->first()?->subject_id,
                'rombel_totals' => [$group->id => $curriculumHours > 0 ? $curriculumHours : (int) $fallbackHours],
                'school_id' => $group->school_id,
                'grade_level_id' => $group->grade_level_id,
            ];
        }

        // Pastikan guru rombel ini ikut terkunci meski konteks berasal dari rombel lain.
        foreach ($tahfidzAssignments as $assignment) {
            if ($assignment->teacher_id) {
                $context['teachers'][$assignment->teacher_id] = $assignment->teacher_id;
            }
        }

        $this->tahfidzTeams[$key] = $context;

        $total = $curriculumHours > 0
            ? $curriculumHours
            : (int) ($context['rombel_totals'][$group->id]
                ?? $tahfidzAssignments->max(fn ($a) => $this->resolveWeeklyHours($a, $group)));

        $result['total'] = $total;
        $result['subject_id'] = $curriculumSubjectId ?? $context['subject_id'] ?? $tahfidzAssignments->first()?->subject_id;
        $result['team'] = array_values($context['teachers']);

        if ($total <= 0) {
            $result['message'] = 'JP tahfidz pada SK guru masih 0. Isi JP mapel tahfidz di SK penugasan.';

            return $result;
        }

        $window = $this->tahfidzWindow($key, $group, $context, $total, $academicYearId);

        if ($window['error'] !== null) {
            $result['status'] = 'gagal';
            $result['message'] = $window['error'];

            return $result;
        }

        $placed = 0;
        $days = [];

        foreach ($window['days'] as $plan) {
            $written = 0;

            foreach ($plan['slots'] as $slotIndex) {
                if ($this->isStudyGroupBusy($group->id, $plan['day'], $slotIndex, $academicYearId)) {
                    continue;
                }

                $times = $this->resolveSlotTimesPublic($slotIndex, $plan['day'], $group->school_id);

                JadwalKbm::create([
                    'school_id' => $group->school_id,
                    'academic_year_id' => $academicYearId,
                    'study_group_id' => $group->id,
                    'subject_id' => $result['subject_id'],
                    'teacher_id' => null,
                    'day_of_week' => $plan['day'],
                    'slot_index' => $slotIndex,
                    'start_time' => $times['start'],
                    'end_time' => $times['end'],
                    'room' => $group->room,
                    'is_active' => true,
                    'notes' => self::TAHFIDZ_NOTE,
                ]);

                $placed++;
                $written++;
            }

            if ($written > 0) {
                $days[] = ['day' => $plan['day'], 'jp' => $written];
            }
        }

        $result['placed'] = $placed;
        $result['days'] = $days;
        $result['status'] = $placed >= $total
            ? 'penuh'
            : ($placed > 0 ? 'sebagian' : 'gagal');

        if ($placed < $total) {
            $result['message'] = "Blok tahfidz hanya tertempat {$placed} dari {$total} JP.";
        }

        return $result;
    }

    /**
     * Hitung hari & slot blok tahfidz untuk satu tingkat.
     * Hasil di-cache agar semua rombel dalam tingkat memakai jendela yang sama.
     *
     * @param  array<string, mixed>  $context
     * @return array{days: array<int, array{day:int, slots:array<int,int>}>, error:?string}
     */
    private function tahfidzWindow(string $key, StudyGroup $group, array $context, int $total, string $academicYearId): array
    {
        if (isset($this->tahfidzWindows[$key])) {
            return $this->tahfidzWindows[$key];
        }

        $schoolId = $context['school_id'] ?? $group->school_id;
        $masterDays = $this->masterSlotsByDay($schoolId);
        $teachers = array_values($context['teachers'] ?? []);

        if (empty($masterDays)) {
            return $this->tahfidzWindows[$key] = [
                'days' => [],
                'error' => 'Master jam pelajaran belum diatur. Atur dulu di halaman Jam Pelajaran sebelum generate.',
            ];
        }

        $perDay = max(1, (int) $this->tahfidzSetting('jp_per_day', $schoolId, 4));
        $startSlot = max(1, (int) $this->tahfidzSetting('start_slot', $schoolId, 1));
        $preferredDays = $this->tahfidzPreferredDays($schoolId);

        $dayCount = max(1, (int) ceil($total / $perDay));
        $base = intdiv($total, $dayCount);
        $rest = $total % $dayCount;

        $plans = [];
        $availableDays = $preferredDays;
        $applied = [];
        $usedDays = [];

        for ($i = 0; $i < $dayCount; $i++) {
            $need = $base + ($i < $rest ? 1 : 0);

            if ($need <= 0) {
                continue;
            }

            $found = null;

            // Satu hari dipakai sekali agar JP per hari tidak melebihi batas.
            // Hari yang belum dipakai tingkat lain dicoba lebih dulu supaya blok
            // tahfidz tetap di jam pagi dan tidak menumpuk di hari yang sama.
            $passes = [
                array_filter($availableDays, fn ($day) => empty($this->tahfidzUsedDays[$day])),
                $availableDays,
            ];

            foreach ($passes as $candidates) {
                foreach ($candidates as $position => $day) {
                    $slots = $this->findTahfidzRun(
                        $masterDays[$day] ?? [],
                        $day,
                        $startSlot,
                        $need,
                        $teachers,
                        $schoolId,
                        $academicYearId
                    );

                    if ($slots === null) {
                        continue;
                    }

                    $found = ['day' => $day, 'slots' => $slots];
                    unset($availableDays[$position]);

                    break 2;
                }
            }

            if ($found === null) {
                // Kembalikan kunci yang sempat dipasang agar percobaan lain bersih.
                foreach ($applied as $lock) {
                    unset($this->tahfidzBusy[$lock]);
                }

                foreach ($usedDays as $day) {
                    unset($this->tahfidzUsedDays[$day]);
                }

                return $this->tahfidzWindows[$key] = [
                    'days' => [],
                    'error' => "Slot tahfidz tidak cukup: butuh {$total} JP per pekan dengan pembagian maksimal "
                        ."{$perDay} JP/hari. Tambahkan hari yang dipakai atau naikkan JP maksimal per hari "
                        .'di halaman Jam Pelajaran.',
                ];
            }

            foreach ($found['slots'] as $slotIndex) {
                foreach ($teachers as $teacherId) {
                    $lock = $teacherId.'|'.$found['day'].'|'.$slotIndex;
                    $this->tahfidzBusy[$lock] = true;
                    $applied[] = $lock;
                }
            }

            $this->tahfidzUsedDays[$found['day']] = true;
            $usedDays[] = $found['day'];
            $plans[] = $found;
        }

        return $this->tahfidzWindows[$key] = ['days' => $plans, 'error' => null];
    }

    /**
     * Cari deretan slot mengajar berurutan yang bebas untuk seluruh guru tim.
     *
     * @param  array<int, array{slot:int, start:string, end:string, is_break:bool}>  $slots
     * @param  array<int, string>  $teacherIds
     * @return array<int, int>|null
     */
    private function findTahfidzRun(
        array $slots,
        int $day,
        int $startSlot,
        int $need,
        array $teacherIds,
        string $schoolId,
        string $academicYearId
    ): ?array {
        $teaching = array_values(array_filter($slots, fn ($s) => ! $s['is_break']));

        if (count($teaching) < $need) {
            return null;
        }

        usort($teaching, fn ($a, $b) => $a['slot'] <=> $b['slot']);

        $startIndex = 0;

        foreach ($teaching as $index => $slot) {
            if ($slot['slot'] >= $startSlot) {
                $startIndex = $index;

                break;
            }
        }

        foreach (array_unique([$startIndex, 0]) as $from) {
            for ($i = $from; $i + $need <= count($teaching); $i++) {
                $run = array_slice($teaching, $i, $need);

                if ($this->tahfidzRunIsFree($run, $day, $teacherIds, $schoolId, $academicYearId)) {
                    return array_map(fn ($slot) => $slot['slot'], $run);
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{slot:int, start:string, end:string, is_break:bool}>  $run
     * @param  array<int, string>  $teacherIds
     */
    private function tahfidzRunIsFree(array $run, int $day, array $teacherIds, string $schoolId, string $academicYearId): bool
    {
        foreach ($run as $slot) {
            foreach ($teacherIds as $teacherId) {
                if (! $this->availability->isAllowed($teacherId, $schoolId, $day, $slot['slot'])) {
                    return false;
                }

                if (isset($this->tahfidzBusy[$teacherId.'|'.$day.'|'.$slot['slot']])) {
                    return false;
                }

                if ($this->isTeacherBusy($teacherId, $day, $slot['slot'], $academicYearId)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Baca setting tahfidz: kunci per sekolah lebih dulu, lalu global.
     */
    private function tahfidzSetting(string $key, ?string $schoolId, mixed $default): mixed
    {
        if ($schoolId) {
            $scoped = setting("tahfidz.{$key}.{$schoolId}");

            if ($scoped !== null && $scoped !== '') {
                return $scoped;
            }
        }

        $value = setting("tahfidz.{$key}");

        return $value === null || $value === '' ? $default : $value;
    }

    /**
     * @return array<int, int>
     */
    private function tahfidzPreferredDays(string $schoolId): array
    {
        $raw = $this->tahfidzSetting('preferred_days', $schoolId, '');
        $days = [];

        if (is_string($raw) && trim($raw) !== '') {
            foreach (preg_split('/[\s,;]+/', $raw) ?: [] as $value) {
                if (is_numeric($value) && (int) $value >= 1 && (int) $value <= 7) {
                    $days[] = (int) $value;
                }
            }
        }

        $days = array_values(array_unique($days));

        return empty($days) ? self::DAYS_OF_WEEK : $days;
    }

    /**
     * @param  array<string, mixed>|null  $block
     * @return array<string, mixed>
     */
    private function buildSummary(StudyGroup $studyGroup, string $academicYearId, Collection $assignments, ?array $block): array
    {
        $summary = [
            'study_group_id' => $studyGroup->id,
            'study_group_name' => $studyGroup->full_name ?? $studyGroup->name,
            'generated' => 0,
            'requested' => 0,
            'has_assignments' => $assignments->isNotEmpty(),
            'shortages' => [],
            'warnings' => [],
            'conflicts' => [],
            'tahfidz' => $block,
        ];

        $curriculum = $this->curriculumSubjects($studyGroup);

        if ($assignments->isEmpty() && $curriculum === []) {
            $summary['conflicts'][] = 'Tidak ada assignment (SK guru) aktif untuk rombel ini.';

            return $summary;
        }

        $plans = $this->resolvePlans($this->regularAssignments($assignments), $studyGroup, $curriculum);
        $masterDays = $this->masterSlotsByDay($studyGroup->school_id);
        $summary['requested'] = (int) array_sum(array_column($plans, 'hours')) + (int) ($block['total'] ?? 0);

        $generated = (int) ($block['placed'] ?? 0);

        foreach ($plans as $plan) {
            $placed = $this->placePlan($studyGroup, $academicYearId, $plan, $masterDays);
            $generated += $placed;

            if (! empty($plan['no_teacher'])) {
                $summary['warnings'][] = "{$plan['subject_name']} dijadwalkan {$plan['hours']} JP tanpa guru karena belum ada di SK penugasan.";
            }

            if ($placed < $plan['hours']) {
                $summary['shortages'][] = [
                    'subject' => $plan['subject_name'],
                    'teacher' => $plan['teacher_name'],
                    'requested' => $plan['hours'],
                    'placed' => $placed,
                    'missing' => $plan['hours'] - $placed,
                ];
            }
        }

        $this->balanceStudyGroupDays($studyGroup, $academicYearId, $masterDays);

        $summary['generated'] = $generated;
        $summary['conflicts'] = $this->verifyConflicts([$studyGroup->id], $academicYearId);

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySummary(string $studyGroupId, ?StudyGroup $group, string $error): array
    {
        return [
            'study_group_id' => $studyGroupId,
            'study_group_name' => $group?->full_name ?? $group?->name ?? $studyGroupId,
            'generated' => 0,
            'requested' => 0,
            'has_assignments' => false,
            'shortages' => [],
            'warnings' => [],
            'conflicts' => [$error],
            'tahfidz' => null,
        ];
    }

    /**
     * Master slot per hari dari class_schedule_slots.
     * Fallback ke grid lama (07:00, 45 menit, istirahat setelah slot 4) bila
     * sekolah belum mendefinisikan master slot.
     *
     * @return array<int, array<int, array{slot:int, start:string, end:string, is_break:bool}>>
     */
    public function masterSlotsByDay(?string $schoolId): array
    {
        if ($schoolId) {
            $rows = DB::table('class_schedule_slots')
                ->where('school_id', $schoolId)
                ->where('is_active', 1)
                ->orderBy('day_of_week')
                ->orderBy('time_start')
                ->orderBy('slot_number')
                ->get(['day_of_week', 'slot_number', 'time_start', 'time_end', 'is_break']);

            if ($rows->isNotEmpty()) {
                $days = [];

                foreach ($rows as $row) {
                    $days[(int) $row->day_of_week][] = [
                        'slot' => (int) $row->slot_number,
                        'start' => $this->normalizeTime($row->time_start),
                        'end' => $this->normalizeTime($row->time_end),
                        'is_break' => (bool) $row->is_break,
                    ];
                }

                return $days;
            }
        }

        // Tanpa master slot, jadwal tidak boleh dibuat dari grid "kira-kira".
        // Generate akan melaporkan kekurangan JP sampai Jam Pelajaran diatur.
        return [];
    }

    /**
     * Grid fallback: 6 hari × 12 slot, 45 menit/slot, jeda 30 menit setelah slot 4.
     *
     * @return array<int, array<int, array{slot:int, start:string, end:string, is_break:bool}>>
     */
    protected function fallbackSlotsByDay(): array
    {
        $days = [];

        foreach (self::DAYS_OF_WEEK as $day) {
            for ($slot = 1; $slot <= self::MAX_PERIODS_PER_DAY; $slot++) {
                $times = $this->legacySlotTimes($slot);
                $days[$day][] = [
                    'slot' => $slot,
                    'start' => $times['start'],
                    'end' => $times['end'],
                    'is_break' => false,
                ];
            }
        }

        return $days;
    }

    /**
     * Resolve jam mulai/selesai sebuah slot pada hari tertentu.
     * Memakai master slot sekolah bila tersedia; jika tidak, grid fallback.
     *
     * @return array{start:string, end:string, is_break:bool}
     */
    public function resolveSlotTimesPublic(int $slot, int $day, ?string $schoolId = null): array
    {
        $days = $this->masterSlotsByDay($schoolId);

        foreach ($days[$day] ?? [] as $master) {
            if ($master['slot'] === $slot) {
                return [
                    'start' => $master['start'],
                    'end' => $master['end'],
                    'is_break' => $master['is_break'],
                ];
            }
        }

        $times = $this->legacySlotTimes($slot);

        return ['start' => $times['start'], 'end' => $times['end'], 'is_break' => false];
    }

    /**
     * Apakah slot benar-benar tersedia untuk mengajar (bukan istirahat / di luar master)?
     */
    public function isTeachingSlot(?string $schoolId, int $day, int $slot): bool
    {
        $days = $this->masterSlotsByDay($schoolId);

        foreach ($days[$day] ?? [] as $master) {
            if ($master['slot'] === $slot) {
                return ! $master['is_break'];
            }
        }

        return false;
    }

    /**
     * Deteksi bentrok guru/rombel pada sekumpulan rombel dalam satu tahun ajaran.
     * Dipakai sebagai verifikasi setelah generate (harus selalu kosong).
     *
     * @return array<int, string>
     */
    public function verifyConflicts(array $studyGroupIds, string $academicYearId): array
    {
        if (empty($studyGroupIds)) {
            return [];
        }

        $conflicts = [];

        $teacherClashes = JadwalKbm::query()
            ->select('teacher_id', 'day_of_week', 'slot_index', DB::raw('COUNT(*) as total'))
            ->whereIn('study_group_id', $studyGroupIds)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->whereNotNull('teacher_id')
            ->groupBy('teacher_id', 'day_of_week', 'slot_index')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($teacherClashes as $clash) {
            $conflicts[] = "Guru bentrok pada hari ke-{$clash->day_of_week} slot {$clash->slot_index} ({$clash->total} kelas): {$clash->teacher_id}";
        }

        $groupClashes = JadwalKbm::query()
            ->select('study_group_id', 'day_of_week', 'slot_index', DB::raw('COUNT(*) as total'))
            ->whereIn('study_group_id', $studyGroupIds)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->groupBy('study_group_id', 'day_of_week', 'slot_index')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groupClashes as $clash) {
            $conflicts[] = "Rombel bentrok pada hari ke-{$clash->day_of_week} slot {$clash->slot_index} ({$clash->total} pelajaran): {$clash->study_group_id}";
        }

        return $conflicts;
    }

    /**
     * Daftar rencana mapel × guru × JP dari kurikulum + assignment, diurutkan JP terbesar dulu.
     *
     * Kurikulum (study_group_subjects / grade_level_subjects) menjadi sumber
     * daftar mapel dan JP. Jika mapel belum ada di SK, tetap dijadwalkan dengan
     * guru kosong agar JP kurikulum tidak hilang, lalu dilaporkan sebagai warning.
     *
     * @param  array<string, array{subject:Subject,hours:int}>|null  $curriculum
     * @return array<int, array<string, mixed>>
     */
    protected function resolvePlans(Collection $assignments, StudyGroup $studyGroup, ?array $curriculum = null): array
    {
        $curriculum ??= $this->curriculumSubjects($studyGroup);
        $plans = [];
        $assignmentsBySubject = $assignments->groupBy('subject_id');

        foreach ($curriculum as $subjectId => $row) {
            if ($this->isTahfidzSubject($subjectId)) {
                continue;
            }

            /** @var Subject $subject */
            $subject = $row['subject'];
            $curriculumHours = (int) ($row['hours'] ?? 0);
            $subjectAssignments = $assignmentsBySubject->get($subjectId, collect());

            if ($subjectAssignments->isNotEmpty()) {
                $assignment = $subjectAssignments
                    ->sortByDesc(fn (TeachingAssignment $a) => $this->resolveWeeklyHours($a, $studyGroup))
                    ->first();

                $hours = $curriculumHours > 0
                    ? $curriculumHours
                    : $this->resolveWeeklyHours($assignment, $studyGroup);

                $key = $subjectId.'|'.($assignment->teacher_id ?? 'none');
                $plans[$key] = [
                    'subject_id' => $subjectId,
                    'subject_name' => $subject->name,
                    'teacher_id' => $assignment->teacher_id,
                    'teacher_name' => $assignment->teacher?->name ?? '-',
                    'hours' => $hours,
                    'no_teacher' => false,
                ];

                continue;
            }

            $hours = $curriculumHours > 0
                ? $curriculumHours
                : app(TeachingHoursResolver::class)->resolve($studyGroup, $subject, null);

            if ($hours <= 0) {
                continue;
            }

            $teacherId = $row['teacher_id'] ?? null;
            $teacher = $teacherId ? User::find($teacherId) : null;
            $key = $subjectId.'|'.($teacher?->id ?? 'none');

            $plans[$key] = [
                'subject_id' => $subjectId,
                'subject_name' => $subject->name,
                'teacher_id' => $teacher?->id,
                'teacher_name' => $teacher?->name ?? 'Guru belum ditetapkan',
                'hours' => $hours,
                'no_teacher' => ! $teacher,
                'notes' => $teacher ? null : self::NO_TEACHER_NOTE,
            ];
        }

        // Assignment yang tidak tercatat di kurikulum tetap dihormati (data lama).
        foreach ($assignments as $assignment) {
            if (! $assignment->subject || isset($curriculum[$assignment->subject_id])) {
                continue;
            }

            $key = $assignment->subject_id.'|'.($assignment->teacher_id ?? 'none');
            $hours = $this->resolveWeeklyHours($assignment, $studyGroup);

            if (isset($plans[$key])) {
                $plans[$key]['hours'] = max($plans[$key]['hours'], $hours);

                continue;
            }

            $plans[$key] = [
                'subject_id' => $assignment->subject_id,
                'subject_name' => $assignment->subject->name,
                'teacher_id' => $assignment->teacher_id,
                'teacher_name' => $assignment->teacher?->name ?? '-',
                'hours' => $hours,
                'no_teacher' => false,
            ];
        }

        usort($plans, fn ($a, $b) => $b['hours'] <=> $a['hours']);

        return $plans;
    }

    /**
     * JP dan subject Tahfidz dari kurikulum rombel (bukan dari SK).
     *
     * @return array{subject_id:string,hours:int}|null
     */
    private function curriculumTahfidzPlan(StudyGroup $studyGroup): ?array
    {
        foreach ($this->curriculumSubjects($studyGroup) as $subjectId => $row) {
            if ($this->isTahfidzSubject($subjectId)) {
                $hours = (int) ($row['hours'] ?? 0);

                if ($hours > 0) {
                    return ['subject_id' => (string) $subjectId, 'hours' => $hours];
                }
            }
        }

        return null;
    }

    /**
     * Daftar mapel dan JP dari kurikulum rombel.
     * Prioritas: study_group_subjects → grade_level_subjects.
     *
     * @return array<string, array{subject:Subject,hours:int}>
     */
    private function curriculumSubjects(StudyGroup $studyGroup): array
    {
        $rows = StudyGroupSubject::with('subject')
            ->where('study_group_id', $studyGroup->id)
            ->where('is_active', true)
            ->get();

        if ($rows->isEmpty() && $studyGroup->grade_level_id) {
            $rows = GradeLevelSubject::with('subject')
                ->where('grade_level_id', $studyGroup->grade_level_id)
                ->where('is_active', true)
                ->forAcademicYear($studyGroup->academic_year_id)
                ->orderByAcademicYearFirst()
                ->get()
                ->groupBy('subject_id')
                ->map(fn (Collection $group) => $group->first())
                ->values();
        }

        $subjects = [];

        foreach ($rows as $row) {
            $subject = $row->subject ?? null;

            if (! $subject) {
                continue;
            }

            $hours = (int) ($row->weekly_hours ?? $row->allocation_hours ?? 0);
            $subjects[(string) $subject->id] = [
                'subject' => $subject,
                'hours' => $hours,
                'teacher_id' => $row->teacher_id ?? null,
            ];
        }

        return $subjects;
    }

    /**
     * Sumber JP otoritatif: teaching_assignments.weekly_hours.
     * Fallback berjenjang ditangani `TeachingHoursResolver` (satu aturan
     * untuk Jadwal KBM, Pekan Efektif/JP Efektif, ATP, dan Perangkat).
     */
    protected function resolveWeeklyHours(TeachingAssignment $assignment, StudyGroup $studyGroup): int
    {
        return app(TeachingHoursResolver::class)->resolve(
            $studyGroup,
            $assignment->subject,
            (int) $assignment->weekly_hours
        );
    }

    /**
     * Tempatkan satu rencana mapel ke slot-slot bebas.
     * Strategi: bagi JP ke seluruh hari mengajar secara merata (dengan rotasi
     * awal per mapel/rombel), isi kuota tiap hari, lalu distribusikan sisa
     * bila ada bentrok guru atau slot penuh.
     */
    protected function placePlan(StudyGroup $studyGroup, string $academicYearId, array $plan, array $masterDays): int
    {
        $hours = max(0, (int) $plan['hours']);

        if ($hours === 0) {
            return 0;
        }

        $days = $this->teachingDays($masterDays);

        if ($days === []) {
            return 0;
        }

        $quotas = $this->distributeQuota($hours, $days, $studyGroup, $plan);
        $remaining = $hours;
        $placed = 0;
        $lastSlotPerDay = [];

        // Fase 1 — isi kuota per hari. Kuota disebar merata sejak awal agar
        // mapel 2–3 JP tidak menumpuk di Senin–Rabu dan meninggalkan Jumat/Sabtu kosong.
        foreach ($days as $day) {
            $quota = (int) ($quotas[$day] ?? 0);

            for ($i = 0; $i < $quota && $remaining > 0; $i++) {
                $slot = $this->findFreeSlotOnDay(
                    $studyGroup->id,
                    $studyGroup->school_id,
                    $plan['teacher_id'],
                    $academicYearId,
                    $day,
                    $masterDays[$day] ?? [],
                    $lastSlotPerDay[$day] ?? null
                );

                if ($slot === null) {
                    break;
                }

                $this->createJadwal($studyGroup, $academicYearId, $plan, $slot);
                $placed++;
                $remaining--;
                $lastSlotPerDay[$day] = $slot['slot_index'];
            }
        }

        // Fase 2 — sisa kuota yang gagal (bentrok guru/slot penuh) dicarikan
        // slot di hari lain secara bergilir.
        $guard = 0;

        while ($remaining > 0 && $guard++ < 300) {
            $placedThisRound = false;

            foreach ($days as $day) {
                if ($remaining <= 0) {
                    break;
                }

                $slot = $this->findFreeSlotOnDay(
                    $studyGroup->id,
                    $studyGroup->school_id,
                    $plan['teacher_id'],
                    $academicYearId,
                    $day,
                    $masterDays[$day] ?? [],
                    $lastSlotPerDay[$day] ?? null
                );

                if ($slot === null) {
                    continue;
                }

                $this->createJadwal($studyGroup, $academicYearId, $plan, $slot);
                $placed++;
                $remaining--;
                $lastSlotPerDay[$day] = $slot['slot_index'];
                $placedThisRound = true;
            }

            if (! $placedThisRound) {
                break;
            }
        }

        return $placed;
    }

    /**
     * Hari yang benar-benar memiliki jam mengajar (bukan hanya istirahat).
     *
     * @param  array<int, array<int, array{slot:int, start:string, end:string, is_break:bool}>>  $masterDays
     * @return array<int, int>
     */
    private function teachingDays(array $masterDays): array
    {
        $days = [];

        foreach ($masterDays as $day => $slots) {
            foreach ($slots as $slot) {
                if (! ($slot['is_break'] ?? false)) {
                    $days[] = (int) $day;

                    break;
                }
            }
        }

        sort($days);

        return array_values(array_unique($days));
    }

    /**
     * Bagi JP satu mapel ke hari-hari mengajar secara merata.
     * Contoh 3 JP / 6 hari = 1 JP pada 3 hari yang berbeda, bukan Senin–Rabu.
     *
     * @param  array<int, int>  $days
     * @param  array<string, mixed>  $plan
     * @return array<int, int>
     */
    private function distributeQuota(int $hours, array $days, StudyGroup $studyGroup, array $plan): array
    {
        $dayCount = count($days);
        $base = intdiv($hours, $dayCount);
        $extra = $hours % $dayCount;

        $quotas = array_fill_keys($days, $base);

        if ($extra > 0) {
            $seed = $studyGroup->id.'|'.$plan['subject_id'].'|'.($plan['teacher_id'] ?? '');
            $offset = abs((int) crc32($seed)) % $dayCount;

            for ($i = 0; $i < $extra; $i++) {
                $day = $days[($offset + $i) % $dayCount];
                $quotas[$day]++;
            }
        }

        return $quotas;
    }

    /**
     * Cari slot bebas pertama pada satu hari (hormati is_break).
     * Bila ada slot terakhir mapel ini di hari tersebut, prioritaskan slot setelahnya
     * agar jam pelajaran berurutan.
     *
     * @param  array<int, array{slot:int, start:string, end:string, is_break:bool}>  $slots
     * @return array{day:int, slot_index:int, start_time:string, end_time:string}|null
     */
    protected function findFreeSlotOnDay(
        string $studyGroupId,
        ?string $schoolId,
        ?string $teacherId,
        string $academicYearId,
        int $day,
        array $slots,
        ?int $preferredAfter
    ): ?array {
        $teaching = array_values(array_filter($slots, fn ($s) => ! $s['is_break']));

        if (empty($teaching)) {
            return null;
        }

        usort($teaching, function ($a, $b) use ($preferredAfter) {
            $rankA = ($preferredAfter !== null && $a['slot'] > $preferredAfter) ? 0 : 1;
            $rankB = ($preferredAfter !== null && $b['slot'] > $preferredAfter) ? 0 : 1;

            return [$rankA, $a['slot']] <=> [$rankB, $b['slot']];
        });

        foreach ($teaching as $slot) {
            if (! $this->availability->isAllowed($teacherId, $schoolId, $day, $slot['slot'])) {
                continue;
            }

            // Guru tim tahfidz terkunci pada jam blok (baris jadwalnya tanpa teacher_id).
            if ($teacherId && isset($this->tahfidzBusy[$teacherId.'|'.$day.'|'.$slot['slot']])) {
                continue;
            }

            if ($this->isTeacherBusy($teacherId, $day, $slot['slot'], $academicYearId)) {
                continue;
            }

            if ($this->isStudyGroupBusy($studyGroupId, $day, $slot['slot'], $academicYearId)) {
                continue;
            }

            return [
                'day' => $day,
                'slot_index' => $slot['slot'],
                'start_time' => $slot['start'],
                'end_time' => $slot['end'],
            ];
        }

        return null;
    }

    /**
     * Ratakan jumlah JP antar hari setelah semua mapel ditempatkan.
     * Hanya memindahkan entri biasa (bukan blok Tahfidz) dan tetap menghormati
     * bentrok guru/rombel serta ketersediaan guru.
     *
     * @param  array<int, array<int, array{slot:int, start:string, end:string, is_break:bool}>>  $masterDays
     */
    private function balanceStudyGroupDays(StudyGroup $studyGroup, string $academicYearId, array $masterDays): void
    {
        $days = $this->teachingDays($masterDays);

        if (count($days) < 2) {
            return;
        }

        for ($iteration = 0; $iteration < 200; $iteration++) {
            $loads = JadwalKbm::query()
                ->where('study_group_id', $studyGroup->id)
                ->where('academic_year_id', $academicYearId)
                ->where('is_active', true)
                ->selectRaw('day_of_week, COUNT(*) as total')
                ->groupBy('day_of_week')
                ->pluck('total', 'day_of_week')
                ->toArray();

            foreach ($days as $day) {
                $loads[$day] = (int) ($loads[$day] ?? 0);
            }

            arsort($loads);
            $highDay = (int) array_key_first($loads);
            $highCount = (int) $loads[$highDay];
            asort($loads);
            $lowDay = (int) array_key_first($loads);
            $lowCount = (int) $loads[$lowDay];

            if ($highCount - $lowCount <= 1) {
                break;
            }

            $moved = false;

            $entries = JadwalKbm::query()
                ->where('study_group_id', $studyGroup->id)
                ->where('academic_year_id', $academicYearId)
                ->where('day_of_week', $highDay)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('notes')->orWhere('notes', '!=', self::TAHFIDZ_NOTE);
                })
                ->orderByDesc('slot_index')
                ->get();

            foreach ($entries as $entry) {
                $slot = $this->findFreeSlotOnDay(
                    $studyGroup->id,
                    $studyGroup->school_id,
                    $entry->teacher_id,
                    $academicYearId,
                    $lowDay,
                    $masterDays[$lowDay] ?? [],
                    null
                );

                if ($slot === null) {
                    continue;
                }

                $entry->update([
                    'day_of_week' => $lowDay,
                    'slot_index' => $slot['slot_index'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                ]);

                $moved = true;

                break;
            }

            if (! $moved) {
                break;
            }
        }

        $this->compactStudyGroupDays($studyGroup, $academicYearId, $masterDays);
    }

    /**
     * Rapatkan entri ke slot paling awal pada hari yang sama tanpa mengubah
     * jumlah JP per hari. Dipakai setelah balancing untuk menutup lubang.
     *
     * @param  array<int, array<int, array{slot:int, start:string, end:string, is_break:bool}>>  $masterDays
     */
    private function compactStudyGroupDays(StudyGroup $studyGroup, string $academicYearId, array $masterDays): void
    {
        foreach ($this->teachingDays($masterDays) as $day) {
            for ($iteration = 0; $iteration < 30; $iteration++) {
                $entries = JadwalKbm::query()
                    ->where('study_group_id', $studyGroup->id)
                    ->where('academic_year_id', $academicYearId)
                    ->where('day_of_week', $day)
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('notes')->orWhere('notes', '!=', self::TAHFIDZ_NOTE);
                    })
                    ->orderByDesc('slot_index')
                    ->get();

                $moved = false;

                foreach ($entries as $entry) {
                    $slot = $this->findFreeSlotOnDay(
                        $studyGroup->id,
                        $studyGroup->school_id,
                        $entry->teacher_id,
                        $academicYearId,
                        $day,
                        $masterDays[$day] ?? [],
                        null
                    );

                    if ($slot === null || $slot['slot_index'] >= (int) $entry->slot_index) {
                        continue;
                    }

                    $entry->update([
                        'slot_index' => $slot['slot_index'],
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                    ]);

                    $moved = true;

                    break;
                }

                if (! $moved) {
                    break;
                }
            }
        }
    }

    protected function createJadwal(StudyGroup $studyGroup, string $academicYearId, array $plan, array $slot): JadwalKbm
    {
        return JadwalKbm::create([
            'school_id' => $studyGroup->school_id,
            'academic_year_id' => $academicYearId,
            'study_group_id' => $studyGroup->id,
            'subject_id' => $plan['subject_id'],
            'teacher_id' => $plan['teacher_id'],
            'day_of_week' => $slot['day'],
            'slot_index' => $slot['slot_index'],
            'start_time' => $slot['start_time'],
            'end_time' => $slot['end_time'],
            'room' => $studyGroup->room,
            'is_active' => true,
            'notes' => $plan['notes'] ?? null,
        ]);
    }

    protected function isTeacherBusy(?string $teacherId, int $day, int $slot, string $academicYearId): bool
    {
        if (! $teacherId) {
            return false;
        }

        return JadwalKbm::where('teacher_id', $teacherId)
            ->where('day_of_week', $day)
            ->where('slot_index', $slot)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->exists();
    }

    protected function isStudyGroupBusy(string $studyGroupId, int $day, int $slot, string $academicYearId): bool
    {
        return JadwalKbm::where('study_group_id', $studyGroupId)
            ->where('day_of_week', $day)
            ->where('slot_index', $slot)
            ->where('academic_year_id', $academicYearId)
            ->where('is_active', true)
            ->exists();
    }

    protected function normalizeTime(?string $time): string
    {
        if (! $time) {
            return '00:00:00';
        }

        if (strlen($time) === 5) {
            return $time.':00';
        }

        return $time;
    }

    /**
     * Jam grid fallback lama — hanya untuk sekolah tanpa master slot.
     *
     * @return array{start:string, end:string}
     */
    protected function legacySlotTimes(int $slot): array
    {
        $startMinutes = (self::DEFAULT_START_HOUR * 60) + (($slot - 1) * self::DEFAULT_PERIOD_MINUTES);

        if ($slot > self::DEFAULT_BREAK_AFTER) {
            $startMinutes += self::DEFAULT_BREAK_MINUTES;
        }

        $endMinutes = $startMinutes + self::DEFAULT_PERIOD_MINUTES;

        return [
            'start' => sprintf('%02d:%02d:00', intdiv($startMinutes, 60), $startMinutes % 60),
            'end' => sprintf('%02d:%02d:00', intdiv($endMinutes, 60), $endMinutes % 60),
        ];
    }
}
