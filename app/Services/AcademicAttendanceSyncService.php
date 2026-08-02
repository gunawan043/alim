<?php

namespace App\Services;

use App\Models\StudentAttendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Academic Attendance Sync Service.
 *
 * This is the SINGLE writer for student attendance derived from
 * boarding events (leave, hospitalization, etc). All other attendance
 * writes (manual check-in, daily scan, etc) remain untouched.
 */
class AcademicAttendanceSyncService
{
    /**
     * Mark a student as absent for a date range due to boarding leave.
     *
     * Skips days where a non-alpha attendance record already exists (hadir,
     * sakit, izin) to prevent overwriting legitimate attendance with alpha.
     */
    public function markAsAbsent(
        string $studentId,
        string|int|\Carbon\Carbon $startDate,
        string|int|\Carbon\Carbon $endDate,
        string $source = 'boarding',
        ?string $sourceId = null,
    ): void {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Collect existing non-alpha statuses to skip protection
        $existingRecords = StudentAttendance::where('student_id', $studentId)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereNotIn('status', ['alpha'])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->flip(); // fast lookup

        DB::transaction(function () use ($studentId, $start, $end, $source, $sourceId, $existingRecords) {
            $current = Carbon::copy($start);

            while ($current->lte($end)) {
                $dateKey = $current->format('Y-m-d');

                // Skip if already marked hadir/sakit/izin
                if ($existingRecords->has($dateKey)) {
                    $current->addDay();

                    continue;
                }

                StudentAttendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'date' => $dateKey,
                    ],
                    [
                        'status' => 'alpha',
                        'source' => $source,
                        'source_ref' => $sourceId,
                    ],
                );

                $current->addDay();
            }
        });
    }

    /**
     * Mark a student as present on a specific date (e.g. return from leave).
     */
    public function markAsPresent(
        string $studentId,
        string|int|\Carbon\Carbon $date,
        string $source = 'boarding',
        ?string $sourceId = null,
    ): void {
        StudentAttendance::updateOrCreate(
            [
                'student_id' => $studentId,
                'date' => Carbon::parse($date)->format('Y-m-d'),
            ],
            [
                'status' => 'hadir',
                'source' => $source,
                'source_ref' => $sourceId,
            ],
        );
    }
}
