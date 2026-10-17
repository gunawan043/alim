<?php

namespace App\Services\Dormitory;

use App\Models\AcademicYear;
use App\Models\DormitoryActivity;
use App\Models\DormitoryActivitySchedule;
use Illuminate\Support\Carbon;

/**
 * Membentuk sesi kegiatan dari jadwal rutin (harian / malam tertentu).
 */
class DormitoryActivityGenerator
{
    public function generateForDate(Carbon $date, ?string $dormitoryId = null): int
    {
        $academicYearId = AcademicYear::where('is_active', true)->value('id');
        $created = 0;

        $schedules = DormitoryActivitySchedule::query()
            ->where('is_active', true)
            ->when($dormitoryId, fn ($q) => $q->where('dormitory_id', $dormitoryId))
            ->whereDate('start_date', '<=', $date->toDateString())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date->toDateString()))
            ->get();

        foreach ($schedules as $schedule) {
            if (! $schedule->runsOn($date)) {
                continue;
            }

            $activity = DormitoryActivity::query()
                ->where('schedule_id', $schedule->id)
                ->whereDate('activity_date', $date->toDateString())
                ->first();

            if (! $activity) {
                DormitoryActivity::create([
                    'schedule_id' => $schedule->id,
                    'activity_date' => $date->toDateString(),
                    'dormitory_id' => $schedule->dormitory_id,
                    'academic_year_id' => $academicYearId,
                    'name' => $schedule->name,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'location' => $schedule->location,
                    'description' => $schedule->description,
                    'target_type' => $schedule->target_type,
                    'target_study_group_ids' => $schedule->target_study_group_ids,
                    'status' => 'open',
                    'created_by' => $schedule->created_by,
                ]);
                $created++;
            }
        }

        return $created;
    }

    public function generateRange(Carbon $from, int $days = 7, ?string $dormitoryId = null): int
    {
        $created = 0;
        for ($i = 0; $i < $days; $i++) {
            $created += $this->generateForDate($from->copy()->addDays($i), $dormitoryId);
        }

        return $created;
    }
}
