<?php

namespace App\Http\Controllers\Dormitory;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryNightActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Pendampingan Kegiatan Malam — jadwal kegiatan malam asrama beserta petugas.
 */
class NightActivityController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $activeYear = AcademicYear::where('is_active', true)->first();

        $date = Carbon::parse($request->input('date') ?: now()->toDateString())->toDateString();

        $dormitories = Dormitory::query()
            ->where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $activities = DormitoryNightActivity::query()
            ->with('dormitory:id,name')
            ->whereBetween('activity_date', [$date, Carbon::parse($date)->addDays(7)->toDateString()])
            ->when($schoolId, fn ($q) => $q->whereHas('dormitory', fn ($d) => $d->where('school_id', $schoolId)))
            ->orderBy('activity_date')
            ->orderBy('start_time')
            ->get();

        return view('dormitory.night-activities.index', [
            'userId' => $userId,
            'date' => $date,
            'dormitories' => $dormitories,
            'activities' => $activities,
            'activeYear' => $activeYear,
            'statusOptions' => DormitoryNightActivity::STATUS_OPTIONS,
        ]);
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'dormitory_id' => 'required|exists:dormitories,id',
            'activity_date' => 'required|date',
            'name' => 'required|string|max:120',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'location' => 'nullable|string|max:120',
            'person_in_charge' => 'nullable|string|max:120',
            'student_count' => 'nullable|integer|min:0|max:2000',
            'notes' => 'nullable|string|max:255',
            'status' => 'required|in:'.implode(',', array_keys(DormitoryNightActivity::STATUS_OPTIONS)),
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();

        DormitoryNightActivity::create([
            'dormitory_id' => $validated['dormitory_id'],
            'academic_year_id' => $activeYear?->id,
            'activity_date' => $validated['activity_date'],
            'name' => $validated['name'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'] ?? null,
            'location' => $validated['location'] ?? null,
            'person_in_charge' => $validated['person_in_charge'] ?? null,
            'student_count' => $validated['student_count'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
            'created_by' => $userId,
        ]);

        return redirect()
            ->route('user.night-activities.index', ['userId' => $userId, 'date' => $validated['activity_date']])
            ->with('success', 'Kegiatan malam ditambahkan.');
    }

    public function updateStatus(Request $request, string $userId, string $activityId)
    {
        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', array_keys(DormitoryNightActivity::STATUS_OPTIONS)),
        ]);

        DormitoryNightActivity::findOrFail($activityId)->update(['status' => $validated['status']]);

        return redirect()
            ->route('user.night-activities.index', ['userId' => $userId])
            ->with('success', 'Status kegiatan diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $activityId)
    {
        DormitoryNightActivity::findOrFail($activityId)->delete();

        return redirect()
            ->route('user.night-activities.index', ['userId' => $userId])
            ->with('success', 'Kegiatan malam dihapus.');
    }
}
