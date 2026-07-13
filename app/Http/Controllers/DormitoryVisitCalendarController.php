<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryVisitLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DormitoryVisitCalendarController extends Controller
{
    public function index(Request $request): View
    {
        [$start, $end] = $this->resolveRange($request);

        $visits = DormitoryVisitLog::with(['student', 'dormitory', 'room'])
            ->whereBetween('expected_arrival_datetime', [$start, $end])
            ->whereIn('status', ['approved', 'arrived', 'checked_out', 'pending'])
            ->orderBy('expected_arrival_datetime')
            ->get();

        $stats = [
            'total' => $visits->count(),
            'arrived' => $visits->whereIn('status', ['arrived', 'checked_out'])->count(),
            'upcoming' => $visits->where('status', 'approved')
                ->filter(fn($v) => $v->expected_arrival_datetime->isFuture())
                ->count(),
            'no_show' => $visits->where('status', 'no_show')->count(),
        ];

        $grouped = $visits->groupBy(fn($v) => $v->expected_arrival_datetime->format('Y-m-d'));

        $dormitories = Dormitory::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('dormitory.calendars.visits', [
            'start' => $start,
            'end' => $end,
            'grouped' => $grouped,
            'stats' => $stats,
            'dormitories' => $dormitories,
            'selectedDorm' => $request->query('dormitory_id'),
        ]);
    }

    public function show(string $id): View
    {
        $visit = DormitoryVisitLog::with(['student', 'dormitory', 'room'])->findOrFail($id);
        return view('dormitory.calendars.visit-detail', compact('visit'));
    }

    public function checkIn(Request $request, string $id)
    {
        $visit = DormitoryVisitLog::findOrFail($id);
        $visit->update([
            'check_in_at' => now(),
            'status' => 'arrived',
        ]);
        return back()->with('success', 'Check-in berhasil.');
    }

    public function checkOut(Request $request, string $id)
    {
        $visit = DormitoryVisitLog::findOrFail($id);
        $visit->update([
            'check_out_at' => now(),
            'departure_datetime' => now(),
            'status' => 'checked_out',
        ]);
        return back()->with('success', 'Check-out berhasil.');
    }

    private function resolveRange(Request $request): array
    {
        $start = $request->query('start')
            ? Carbon::parse($request->query('start'))->startOfDay()
            : now()->startOfWeek();
        $end = $request->query('end')
            ? Carbon::parse($request->query('end'))->endOfDay()
            : now()->endOfWeek()->addDays(7);
        return [$start, $end];
    }
}
