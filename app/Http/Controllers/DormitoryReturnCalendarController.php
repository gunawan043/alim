<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DormitoryReturnCalendarController extends Controller
{
    public function index(Request $request): View
    {
        [$start, $end] = $this->resolveRange($request);

        $permits = DormitoryPermit::with(['student', 'room', 'dormitory'])
            ->whereBetween('expected_return_datetime', [$start, $end])
            ->whereIn('status', ['approved', 'returned', 'overdue'])
            ->orderBy('expected_return_datetime')
            ->get();

        $stats = [
            'total' => $permits->count(),
            'on_time' => $permits->where('status', 'returned')->filter(
                fn($p) => $p->actual_return_datetime && $p->actual_return_datetime <= $p->expected_return_datetime
            )->count(),
            'overdue' => $permits->where('status', 'overdue')->count(),
            'pending' => $permits->where('status', 'approved')->filter(
                fn($p) => $p->expected_return_datetime->isPast()
            )->count(),
        ];

        $grouped = $permits->groupBy(fn($p) => $p->expected_return_datetime->format('Y-m-d'));

        $dormitories = Dormitory::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('dormitory.calendars.returns', [
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
        $permit = DormitoryPermit::with(['student', 'dormitory', 'room'])
            ->findOrFail($id);
        return view('dormitory.calendars.return-detail', compact('permit'));
    }

    public function markReturned(Request $request, string $id)
    {
        $permit = DormitoryPermit::findOrFail($id);

        $validated = $request->validate([
            'actual_return_datetime' => 'required|date',
            'note' => 'nullable|string|max:500',
        ]);

        $permit->update([
            'actual_return_datetime' => $validated['actual_return_datetime'],
            'status' => 'returned',
        ]);

        return back()->with('success', 'Kepulangan berhasil dicatat.');
    }

    private function resolveRange(Request $request): array
    {
        $start = $request->query('start')
            ? Carbon::parse($request->query('start'))->startOfDay()
            : now()->startOfMonth();
        $end = $request->query('end')
            ? Carbon::parse($request->query('end'))->endOfDay()
            : now()->endOfMonth()->addDays(14);
        return [$start, $end];
    }
}
