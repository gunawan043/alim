<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\WorkUnit;
use Illuminate\Http\Request;

class SchoolUnitController extends Controller
{
    /**
     * Display a specific school within a work unit context.
     * URL: /{roleId}/satuan-kerja/{workUnitId}/schools/{schoolId}
     */
    public function show(Request $request, string $workUnitId, string $schoolId)
    {
        $workUnit = WorkUnit::findOrFail($workUnitId);
        $school = School::with([
            'workUnit',
            'province',
            'city',
            'district',
            'village',
            'principalUser',
        ])->findOrFail($schoolId);

        // Ensure the school belongs to the given work unit
        if ($school->work_unit_id !== $workUnitId) {
            abort(404);
        }

        $userId = $request->route('userId');

        return view('school-unit.show', compact('school', 'workUnit', 'userId'));
    }

    /**
     * Edit a specific school within a work unit context.
     */
    public function edit(Request $request, string $workUnitId, string $schoolId)
    {
        $workUnit = WorkUnit::findOrFail($workUnitId);
        $school = School::findOrFail($schoolId);

        if ($school->work_unit_id !== $workUnitId) {
            abort(404);
        }

        $userId = $request->route('userId');
        $workUnits = WorkUnit::where('type', 'Unit Akademik')->orderBy('name')->get();
        $provinces = \App\Models\Province::orderBy('name')->get();
        $principals = \App\Models\User::whereHas('employment')
            ->whereHas('gtkWorkUnits.workUnit', fn ($q) => $q->where('type', 'Unsur Pimpinan'))
            ->with(['gtkWorkUnits.workUnit' => fn ($q) => $q->where('type', 'Unsur Pimpinan')])
            ->orderBy('name')
            ->get();

        return view('school-unit.edit', compact('school', 'workUnit', 'userId', 'workUnits', 'provinces', 'principals'));
    }
}
