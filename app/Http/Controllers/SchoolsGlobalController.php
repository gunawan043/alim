<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\SchoolGroupService;
use Illuminate\Http\Request;

class SchoolsGlobalController extends Controller
{
    public function __construct(
        private SchoolGroupService $schoolGroupService
    ) {}

    /**
     * Display all active schools grouped by level + gender.
     * Accessible only by Super Admin / global-view users (enforced by sidebar visibility).
     * userId param is used for route URL generation in views.
     */
    public function index(Request $request, string $userId = null)
    {
        $schools = School::active()
            ->with(['workUnit', 'principalUser', 'city'])
            ->orderBy('school_level')
            ->orderBy('name')
            ->get();

        $grouped = $this->schoolGroupService->build($schools);

        return view('schools-global.index', [
            'schools' => $schools,
            'grouped' => $grouped,
            'totalSchools' => $schools->count(),
            'userId' => $userId ?? auth()->id(),
        ]);
    }
}