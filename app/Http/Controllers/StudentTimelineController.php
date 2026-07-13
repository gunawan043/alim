<?php

namespace App\Http\Controllers;

use App\Models\BoardingTimelineEvent;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentTimelineController extends Controller
{
    public function show(Request $request, string $studentId): View
    {
        $student = Student::with(['dormitory', 'room'])->findOrFail($studentId);

        $start = $request->query('start')
            ? Carbon::parse($request->query('start'))->startOfDay()
            : now()->subMonths(3)->startOfDay();
        $end = $request->query('end')
            ? Carbon::parse($request->query('end'))->endOfDay()
            : now()->addMonths(1)->endOfDay();

        $events = BoardingTimelineEvent::where('student_id', $studentId)
            ->whereBetween('event_at', [$start, $end])
            ->orderBy('event_at', 'desc')
            ->limit(200)
            ->get();

        $grouped = $events->groupBy(fn($e) => $e->event_at->format('Y-m-d'));

        $counts = [
            'total' => $events->count(),
            'permits' => $events->whereIn('event_type', ['leave_approved', 'leave_started', 'returned', 'leave_overdue', 'special_permission'])->count(),
            'visits' => $events->where('event_type', 'visit_approved')->count(),
            'violations' => $events->where('event_type', 'violation')->count(),
            'room_moves' => $events->where('event_type', 'room_transfer')->count(),
        ];

        return view('dormitory.students.timeline', [
            'student' => $student,
            'grouped' => $grouped,
            'counts' => $counts,
            'start' => $start,
            'end' => $end,
        ]);
    }
}
