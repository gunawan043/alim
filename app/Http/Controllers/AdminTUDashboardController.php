<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;

class AdminTUDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $schoolId = request()->attributes->get('schoolContextId');

        // Schools
        $schools = School::where('is_active', true)->count();
        $activeSchools = School::where('is_active', true)->where('status', 'aktif')->count();

        // Work Units
        $workUnits = WorkUnit::where('is_active', true)->count();

        // GTK Overview
        $totalGtk = User::where('is_active', true)->whereHas('employment')->count();
        $pendingRequests = 0; // Placeholder for any pending admin requests

        // Quick stats
        $quickStats = [
            'pendingDocuments' => 0,  // Placeholder
            'archivedRecords' => 0,   // Placeholder
            'completedTasks' => 12,   // Placeholder
        ];

        return view('admin-tu.dashboard', compact(
            'user',
            'schools',
            'activeSchools',
            'workUnits',
            'totalGtk',
            'pendingRequests',
            'quickStats'
        ));
    }
}
