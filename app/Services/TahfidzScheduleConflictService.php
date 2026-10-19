<?php

namespace App\Services;

use App\Models\JadwalKbm;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupTeacher;
use App\Models\TahfidzSchedule;
use Carbon\Carbon;

class TahfidzScheduleConflictService
{
    /**
     * @param  array{day_of_week:int,time_start:string,time_end:string,room?:?string,effective_from?:?string,effective_until?:?string}  $data
     * @return array<int, string>
     */
    public function conflicts(string $groupId, array $data, ?string $ignoreScheduleId = null, ?string $academicYearId = null): array
    {
        $group = TahfidzGroup::find($groupId);
        if (! $group) {
            return ['Halaqah tidak ditemukan.'];
        }

        $academicYearId ??= $group->academic_year_id;
        $teacherIds = $this->teacherIds($group);
        $start = $data['time_start'];
        $end = $data['time_end'];
        $from = $data['effective_from'] ?? null;
        $until = $data['effective_until'] ?? null;
        $errors = [];

        $schedules = TahfidzSchedule::with(['group:id,name,teacher_id,coordinator_id'])
            ->where('academic_year_id', $academicYearId)
            ->where('day_of_week', $data['day_of_week'])
            ->where('is_active', true)
            ->when($ignoreScheduleId, fn ($q) => $q->where('id', '!=', $ignoreScheduleId))
            ->get()
            ->filter(fn ($schedule) => $this->timeOverlap($start, $end, (string) $schedule->time_start, (string) $schedule->time_end)
                && $this->dateOverlap($from, $until, $schedule->effective_from, $schedule->effective_until));

        foreach ($schedules as $schedule) {
            $otherTeachers = $this->teacherIds($schedule->group);
            $shared = array_values(array_intersect($teacherIds, $otherTeachers));
            if (! empty($shared)) {
                $names = \App\Models\User::whereIn('id', $shared)->pluck('name')->implode(', ');
                $errors[] = 'Guru '.$names.' sudah mengajar di '.($schedule->group?->name ?? 'halaqah lain').' pada jam yang sama.';
            }
            if (! empty($data['room']) && ! empty($schedule->room) && strcasecmp(trim($data['room']), trim($schedule->room)) === 0) {
                $errors[] = 'Ruang '.$data['room'].' sudah dipakai '.($schedule->group?->name ?? 'halaqah lain').' pada jam yang sama.';
            }
        }

        if (! empty($teacherIds)) {
            $kbmConflicts = JadwalKbm::with(['teacher:id,name', 'subject:id,name', 'studyGroup:id,name'])
                ->where('academic_year_id', $academicYearId)
                ->where('day_of_week', $data['day_of_week'])
                ->where('is_active', true)
                ->whereIn('teacher_id', $teacherIds)
                ->get()
                ->filter(fn ($schedule) => $this->timeOverlap($start, $end, (string) $schedule->start_time, (string) $schedule->end_time));

            foreach ($kbmConflicts as $kbm) {
                $errors[] = 'Guru '.($kbm->teacher?->name ?? '-').' masih memiliki KBM '.($kbm->subject?->name ?? '').' di '.($kbm->studyGroup?->name ?? '').' pada jam yang sama.';
            }
        }

        return array_values(array_unique($errors));
    }

    private function teacherIds(?TahfidzGroup $group): array
    {
        if (! $group) {
            return [];
        }

        $ids = collect([$group->teacher_id, $group->coordinator_id])
            ->filter()
            ->merge(TahfidzGroupTeacher::where('tahfidz_group_id', $group->id)->where('is_active', true)->pluck('user_id'))
            ->unique()
            ->values()
            ->all();

        return $ids;
    }

    private function timeOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        return substr($startA, 0, 5) < substr($endB, 0, 5)
            && substr($endA, 0, 5) > substr($startB, 0, 5);
    }

    private function dateOverlap($fromA, $untilA, $fromB, $untilB): bool
    {
        $startA = $fromA ? Carbon::parse($fromA)->startOfDay() : Carbon::create(1900, 1, 1)->startOfDay();
        $endA = $untilA ? Carbon::parse($untilA)->endOfDay() : Carbon::create(2999, 12, 31)->endOfDay();
        $startB = $fromB ? Carbon::parse($fromB)->startOfDay() : Carbon::create(1900, 1, 1)->startOfDay();
        $endB = $untilB ? Carbon::parse($untilB)->endOfDay() : Carbon::create(2999, 12, 31)->endOfDay();

        return $startA->lte($endB) && $startB->lte($endA);
    }
}
