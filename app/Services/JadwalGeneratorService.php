<?php

namespace App\Services;

use App\Models\JadwalKbm;
use App\Models\StudyGroup;
use App\Models\TeachingAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class JadwalGeneratorService
{
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
     * @return Collection<int, array<string, mixed>>
     */
    public function generateBulk(array $studyGroupIds, string $academicYearId, string $semester, bool $overwrite = false): Collection
    {
        $results = collect();

        foreach ($studyGroupIds as $sgId) {
            try {
                $results->push($this->generateForStudyGroup($sgId, $academicYearId, $semester, $overwrite));
            } catch (\Throwable $e) {
                $sg = StudyGroup::find($sgId);
                $results->push([
                    'study_group_id' => $sgId,
                    'study_group_name' => $sg->full_name ?? $sg?->name ?? $sgId,
                    'generated' => 0,
                    'requested' => 0,
                    'has_assignments' => false,
                    'shortages' => [],
                    'conflicts' => [$e->getMessage()],
                ]);
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

        $assignments = TeachingAssignment::with(['subject', 'teacher'])
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->get();

        if ($overwrite) {
            JadwalKbm::where('study_group_id', $studyGroupId)
                ->where('academic_year_id', $academicYearId)
                ->delete();
        }

        $summary = [
            'study_group_id' => $studyGroupId,
            'study_group_name' => $studyGroup->full_name ?? $studyGroup->name,
            'generated' => 0,
            'requested' => 0,
            'has_assignments' => $assignments->isNotEmpty(),
            'shortages' => [],
            'conflicts' => [],
        ];

        if ($assignments->isEmpty()) {
            $summary['conflicts'][] = 'Tidak ada assignment (SK guru) aktif untuk rombel ini.';

            return $summary;
        }

        $plans = $this->resolvePlans($assignments, $studyGroup);
        $masterDays = $this->masterSlotsByDay($studyGroup->school_id);
        $summary['requested'] = (int) array_sum(array_column($plans, 'hours'));

        $generated = 0;

        foreach ($plans as $plan) {
            $placed = $this->placePlan($studyGroup, $academicYearId, $plan, $masterDays);
            $generated += $placed;

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

        $summary['generated'] = $generated;
        $summary['conflicts'] = $this->verifyConflicts([$studyGroupId], $academicYearId);

        return $summary;
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

        return $this->fallbackSlotsByDay();
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
     * Daftar rencana mapel × guru × JP dari assignment, diurutkan JP terbesar dulu.
     *
     * @return array<int, array{subject_id:string, subject_name:string, teacher_id:?string, teacher_name:string, hours:int}>
     */
    protected function resolvePlans(Collection $assignments, StudyGroup $studyGroup): array
    {
        $plans = [];

        foreach ($assignments as $assignment) {
            if (! $assignment->subject) {
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
            ];
        }

        usort($plans, fn ($a, $b) => $b['hours'] <=> $a['hours']);

        return $plans;
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
     * Strategi: sebar merata antar hari (maks. ceil(JP/2) per hari, maks. JP untuk 1-2 JP),
     * lalu longgarkan bila slot terbatas.
     */
    protected function placePlan(StudyGroup $studyGroup, string $academicYearId, array $plan, array $masterDays): int
    {
        $hours = (int) $plan['hours'];
        $remaining = $hours;
        $placed = 0;
        $maxPerDay = $hours <= 2 ? $hours : (int) ceil($hours / 2);
        $perDay = [];
        $lastSlotPerDay = [];
        $guard = 0;

        while ($remaining > 0 && $guard++ < 300) {
            $placedThisRound = false;

            foreach (self::DAYS_OF_WEEK as $day) {
                if ($remaining <= 0) {
                    break;
                }

                if (($perDay[$day] ?? 0) >= $maxPerDay) {
                    continue;
                }

                $slot = $this->findFreeSlotOnDay(
                    $studyGroup->id,
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
                $perDay[$day] = ($perDay[$day] ?? 0) + 1;
                $lastSlotPerDay[$day] = $slot['slot_index'];
                $placedThisRound = true;
            }

            if (! $placedThisRound) {
                // Longgarkan batas per hari: cari slot apa pun yang bebas.
                foreach (self::DAYS_OF_WEEK as $day) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $slot = $this->findFreeSlotOnDay(
                        $studyGroup->id,
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
                    break;
                }
            }

            if (! $placedThisRound) {
                break; // Tidak ada slot tersisa sama sekali.
            }
        }

        return $placed;
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
