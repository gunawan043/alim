<?php

namespace App\Http\Controllers\Kesiswaan;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\KesiswaanDutySchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Piket Kesiswaan — penugasan petugas per tanggal dan shift.
 */
class DutyScheduleController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $activeYear = AcademicYear::where('is_active', true)->first();

        $from = Carbon::parse($request->input('from') ?: now()->startOfWeek()->toDateString())->toDateString();
        $to = Carbon::parse($request->input('to') ?: now()->endOfWeek()->toDateString())->toDateString();

        $duties = KesiswaanDutySchedule::query()
            ->with(['user:id,name'])
            ->whereBetween('duty_date', [$from, $to])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('duty_date')
            ->orderBy('shift')
            ->get();

        $staff = User::query()
            ->when($schoolId, fn ($q) => $q->whereHas('employment', fn ($e) => $e->where('school_id', $schoolId)))
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name']);

        $todayDuties = $duties->filter(fn ($d) => $d->duty_date->isToday());

        return view('kesiswaan.duties.index', [
            'userId' => $userId,
            'from' => $from,
            'to' => $to,
            'duties' => $duties,
            'staff' => $staff,
            'todayDuties' => $todayDuties,
            'activeYear' => $activeYear,
            'shifts' => KesiswaanDutySchedule::SHIFTS,
        ]);
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'duty_date' => 'required|date',
            'shift' => 'required|in:'.implode(',', array_keys(KesiswaanDutySchedule::SHIFTS)),
            'location' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:255',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');
        $activeYear = AcademicYear::where('is_active', true)->first();

        $duplicate = KesiswaanDutySchedule::where('user_id', $validated['user_id'])
            ->whereDate('duty_date', $validated['duty_date'])
            ->where('shift', $validated['shift'])
            ->exists();

        if ($duplicate) {
            return back()->with('error', 'Petugas ini sudah dijadwalkan pada shift tersebut.');
        }

        KesiswaanDutySchedule::create([
            'school_id' => $schoolId,
            'academic_year_id' => $activeYear?->id,
            'user_id' => $validated['user_id'],
            'duty_date' => $validated['duty_date'],
            'shift' => $validated['shift'],
            'location' => $validated['location'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $userId,
        ]);

        return redirect()
            ->route('user.kesiswaan.duties.index', ['userId' => $userId])
            ->with('success', 'Jadwal piket ditambahkan.');
    }

    public function destroy(Request $request, string $userId, string $dutyId)
    {
        KesiswaanDutySchedule::findOrFail($dutyId)->delete();

        return redirect()
            ->route('user.kesiswaan.duties.index', ['userId' => $userId])
            ->with('success', 'Jadwal piket dihapus.');
    }
}
