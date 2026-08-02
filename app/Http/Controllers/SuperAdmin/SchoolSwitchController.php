<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\School;
use Illuminate\Http\Request;

/**
 * SchoolSwitchController
 *
 * Handles Super Admin school context switching via session.
 * When SA picks a school from the sidebar switcher, data across
 * school-scoped modules (GTK, Santri, Akademik, dll) becomes
 * filtered to that school. Picking "Semua Sekolah" clears the filter.
 */
class SchoolSwitchController extends Controller
{
    /**
     * Switch the active school context for Super Admin.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch(Request $request)
    {
        $user = auth()->user();

        // Only Super Admin (or user with view_global_school_data permission)
        if (! $user || ! canPermission('view_global_school_data')) {
            abort(403);
        }

        $schoolId = $request->input('school_id');

        if ($schoolId === 'all' || $schoolId === null) {
            // Reset to global view
            $request->session()->forget('sa_school_id');
            $request->session()->put('sa_school_scoped', false);
        } else {
            // Validate school exists
            $school = School::where('id', $schoolId)->active()->first();
            if (! $school) {
                return back()->with('error', 'Sekolah tidak ditemukan.');
            }
            $request->session()->put('sa_school_id', $school->id);
            $request->session()->put('sa_school_name', $school->name);
            $request->session()->put('sa_school_scoped', true);
        }

        // Redirect back to previous page, or root
        $redirectTo = $request->input('redirect_to', route('root'));

        return redirect($redirectTo);
    }

    /**
     * API: Get all schools grouped (for dropdown population).
     * Returns JSON list of schools.
     */
    public function apiSchools(Request $request)
    {
        $schools = School::active()
            ->orderBy('school_level')
            ->orderBy('name')
            ->get(['id', 'name', 'school_level', 'school_gender'])
            ->groupBy('school_level')
            ->map(function ($group, $level) {
                return [
                    'level' => $level,
                    'label' => match ($level) {
                        'sd' => 'SD IT',
                        'smp' => 'SMP IT',
                        'sma' => 'SMA IT / MA',
                        'smk' => 'SMA IT / MA',
                        default => strtoupper($level),
                    },
                    'schools' => $group->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                        'gender' => $s->school_gender,
                    ])->values(),
                ];
            })
            ->values();

        return response()->json($schools);
    }

    public function apiDormitories(Request $request)
    {
        $schoolId = $request->query('school_id');
        $q = Dormitory::query();
        if ($schoolId) {
            $q->where('school_id', $schoolId);
        }

        return response()->json(
            $q->orderBy('name')->get(['id', 'name', 'school_id'])->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'school_id' => $d->school_id,
            ])->values()
        );
    }

    public function apiWings(Request $request)
    {
        $dormitoryId = $request->query('dormitory_id');
        if (! $dormitoryId) {
            return response()->json([]);
        }
        $wings = \Illuminate\Support\Facades\DB::table('dormitory_wings')
            ->where('dormitory_id', $dormitoryId)
            ->orderBy('name')
            ->get(['id', 'name', 'dormitory_id']);

        return response()->json($wings);
    }

    public function apiRooms(Request $request)
    {
        $wingId = $request->query('wing_id');
        if (! $wingId) {
            return response()->json([]);
        }
        $rooms = \Illuminate\Support\Facades\DB::table('dormitory_rooms')
            ->where('wing_id', $wingId)
            ->orderBy('room_number')
            ->get(['id', 'room_number as name', 'wing_id', 'capacity']);

        return response()->json($rooms);
    }

    public function apiAcademicYears(Request $request)
    {
        $schoolId = $request->query('school_id');
        $q = AcademicYear::query();
        if ($schoolId) {
            $q->where('school_id', $schoolId);
        }

        return response()->json(
            $q->orderByDesc('is_active')
                ->orderByDesc('start_date')
                ->get(['id', 'name', 'semester', 'is_active'])
        );
    }
}
