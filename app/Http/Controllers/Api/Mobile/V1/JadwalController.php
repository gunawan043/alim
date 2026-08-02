<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\JadwalKbm;
use App\Models\StudentClassHistory;
use App\Models\WaliSantri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JadwalController extends Controller
{
    // ── GET /api/mobile/v1/jadwal ───────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $date = $request->query('date', now()->format('Y-m-d'));
        $parsed = \DateTime::createFromFormat('Y-m-d', $date);
        if ($parsed === false) {
            $parsed = now();
            $date = $parsed->format('Y-m-d');
        }

        $studentIds = WaliSantri::where('user_id', $user->id)
            ->active()
            ->pluck('student_id');

        $studyGroups = StudentClassHistory::whereIn('student_id', $studentIds)
            ->where('is_active', true)
            ->pluck('study_group_id')
            ->unique();

        $dayOfWeek = (int) $parsed->format('w'); // 0=Sunday, 1=Monday...

        $query = JadwalKbm::with(['studyGroup:id,name', 'subject:id,name', 'teacher:id,name'])
            ->whereIn('study_group_id', $studyGroups)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->whereNotNull('start_time')
            ->orderBy('slot_index')
            ->orderBy('start_time');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $records = $query->get()->map(fn ($j) => [
            'id' => $j->id,
            'hari' => $j->hari,
            'hari_label' => $j->hari,
            'slot_index' => $j->slot_index,
            'start_time' => $j->start_time,
            'end_time' => $j->end_time,
            'room' => $j->room,
            'study_group' => $j->studyGroup ? [
                'id' => $j->studyGroup->id,
                'name' => $j->studyGroup->name,
            ] : null,
            'subject' => $j->subject ? [
                'id' => $j->subject->id,
                'name' => $j->subject->name,
            ] : null,
            'teacher' => $j->teacher ? [
                'id' => $j->teacher->id,
                'name' => $j->teacher->name,
            ] : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'jadwal' => $records,
                'total' => $records->count(),
            ],
        ]);
    }

    // ── GET /api/mobile/v1/jadwal/week ─────────────────────────────────

    public function week(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $weekStart = $request->query('week_start', now()->startOfWeek()->format('Y-m-d'));

        $studentIds = WaliSantri::where('user_id', $user->id)
            ->active()
            ->pluck('student_id');

        $studyGroups = StudentClassHistory::whereIn('student_id', $studentIds)
            ->where('is_active', true)
            ->pluck('study_group_id')
            ->unique();

        $parsedStart = \DateTime::createFromFormat('Y-m-d', $weekStart);
        $start = $parsedStart ? $parsedStart->format('Y-m-d') : now()->startOfWeek()->format('Y-m-d');
        $end = (clone $parsedStart)->modify('+6 days')->format('Y-m-d') ?? '';

        $query = JadwalKbm::with(['studyGroup:id,name', 'subject:id,name', 'teacher:id,name'])
            ->whereIn('study_group_id', $studyGroups)
            ->where('is_active', true);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $records = $query
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get();

        $byDay = $records->groupBy('day_of_week');

        $days = [];
        $current = new \DateTime($start);
        for ($i = 0; $i < 7; $i++) {
            $dateStr = $current->format('Y-m-d');
            $dayOfWeek = (int) $current->format('w');

            $dayRecords = $byDay->get($dayOfWeek, collect());

            $days[] = [
                'date' => $dateStr,
                'day_of_week' => $dayOfWeek,
                'day_label' => $this->dayLabel($dayOfWeek),
                'jadwal' => $dayRecords->map(fn ($j) => [
                    'id' => $j->id,
                    'start_time' => $j->start_time,
                    'end_time' => $j->end_time,
                    'room' => $j->room,
                    'subject' => $j->subject ? ['id' => $j->subject->id, 'name' => $j->subject->name] : null,
                    'teacher' => $j->teacher ? ['id' => $j->teacher->id, 'name' => $j->teacher->name] : null,
                    'study_group' => $j->studyGroup ? ['id' => $j->studyGroup->id, 'name' => $j->studyGroup->name] : null,
                ])->values(),
            ];

            $current->modify('+1 day');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'week_start' => $start,
                'days' => $days,
            ],
        ]);
    }

    private function dayLabel(int $day): string
    {
        return match ($day) {
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => '?',
        };
    }
}
