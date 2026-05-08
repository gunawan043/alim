<?php

namespace App\Http\Controllers;

use App\Models\SanitationInspection;
use App\Models\AcademicYear;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SanitationInspectionController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $activeAy = AcademicYear::where('is_active', true)->first();

        $query = SanitationInspection::with(['inspectedBy', 'academicYear'])
            ->orderByDesc('inspection_date');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $schoolGender = $request->attributes->get('schoolGender');
        if ($schoolGender) {
            // Sanitasi tidak filter by gender student, tapi by school_gender context
            $request->attributes->set('sanitationGenderScope', $schoolGender);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('findings', 'like', "%{$q}%")
                ->orWhere('location_type', 'like', "%{$q}%")
            );
        }

        if ($request->filled('location_type')) {
            $query->where('location_type', $request->location_type);
        }

        if ($request->filled('month') && $request->month !== '') {
            [$year, $month] = explode('-', $request->month);
            $query->whereRaw('YEAR(inspection_date) = ?', [$year])
                  ->whereRaw('MONTH(inspection_date) = ?', [$month]);
        } elseif ($activeAy) {
            $query->where('academic_year_id', $activeAy->id);
        }

        if ($request->filled('pending_followup')) {
            $query->pendingFollowUp();
        }

        $inspections = $query->paginate(15)->withQueryString();

        return view('health.sanitation-inspections.index', compact('inspections', 'activeAy', 'userId'));
    }

    public function dashboard(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $academicYearId = $request->get('academic_year_id');

        $baseQuery = SanitationInspection::where('school_id', $schoolId);

        if ($academicYearId) {
            $baseQuery->where('academic_year_id', $academicYearId);
        }

        // Rata-rata skor per lokasi
        $scoreByLocation = (clone $baseQuery)
            ->selectRaw('location_type, avg(score) as avg_score, count(*) as total')
            ->groupBy('location_type')
            ->orderByDesc('avg_score')
            ->get();

        // Tren skor bulanan
        $monthlyTrend = (clone $baseQuery)
            ->selectRaw("DATE_FORMAT(inspection_date, '%Y-%m') as month, avg(score) as avg_score")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Inspection count
        $totalInspection = (clone $baseQuery)->count();
        $avgScore = (clone $baseQuery)->avg('score') ?? 0;
        $pendingFollowUp = (clone $baseQuery)->pendingFollowUp()->count();

        $academicYears = AcademicYear::orderByDesc('name')->get();

        return view('health.sanitation-inspections.dashboard', compact(
            'scoreByLocation', 'monthlyTrend',
            'totalInspection', 'avgScore', 'pendingFollowUp',
            'academicYears', 'academicYearId', 'userId'
        ));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $staff = User::orderBy('name')->get();

        return view('health.sanitation-inspections.create', compact('academicYears', 'staff', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'inspection_date' => 'required|date',
            'inspected_by' => 'required|exists:users,id',
            'location_type' => 'required|string|max:50',
            'location_id' => 'nullable|exists:dormitories,id',
            'score' => 'required|integer|min:0|max:100',
            'findings' => 'nullable|string',
            'photo_path' => 'nullable|string|max:255',
            'recommendations' => 'nullable|string',
            'follow_up_deadline' => 'nullable|date',
            'is_passed' => 'nullable|boolean',
        ]);

        $validated['school_id'] = $schoolId;
        $validated['created_by'] = Auth::id();
        $validated['is_passed'] = $request->boolean('is_passed');

        SanitationInspection::create($validated);

        return redirect()
            ->route('user.uks.sanitation-inspections.index', ['userId' => $userId])
            ->with('success', 'Inspeksi sanitasi berhasil disimpan.');
    }

    public function show(Request $request, string $userId, string $uuid)
    {
        $inspection = SanitationInspection::with([
            'inspectedBy', 'academicYear', 'creator',
        ])->findOrFail($uuid);

        return view('health.sanitation-inspections.show', compact('inspection', 'userId'));
    }

    public function edit(Request $request, string $userId, string $uuid)
    {
        $inspection = SanitationInspection::findOrFail($uuid);

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $staff = User::orderBy('name')->get();

        return view('health.sanitation-inspections.edit', compact('inspection', 'academicYears', 'staff', 'userId'));
    }

    public function update(Request $request, string $userId, string $uuid)
    {
        $inspection = SanitationInspection::findOrFail($uuid);

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'inspection_date' => 'required|date',
            'inspected_by' => 'required|exists:users,id',
            'location_type' => 'required|string|max:50',
            'location_id' => 'nullable|exists:dormitories,id',
            'score' => 'required|integer|min:0|max:100',
            'findings' => 'nullable|string',
            'photo_path' => 'nullable|string|max:255',
            'recommendations' => 'nullable|string',
            'follow_up_deadline' => 'nullable|date',
            'is_passed' => 'nullable|boolean',
        ]);

        $validated['is_passed'] = $request->boolean('is_passed');
        $inspection->update($validated);

        return redirect()
            ->route('user.uks.sanitation-inspections.show', ['userId' => $userId, 'uuid' => $uuid])
            ->with('success', 'Inspeksi sanitasi berhasil diperbarui.');
    }

    public function markComplete(string $userId, string $uuid)
    {
        $inspection = SanitationInspection::findOrFail($uuid);
        $inspection->update(['follow_up_completed_at' => now()]);

        return redirect()
            ->route('user.uks.sanitation-inspections.show', ['userId' => $userId, 'uuid' => $uuid])
            ->with('success', 'Tindak lanjut ditandai selesai.');
    }

    public function destroy(string $userId, string $uuid)
    {
        $inspection = SanitationInspection::findOrFail($uuid);
        $inspection->delete();

        return redirect()
            ->route('user.uks.sanitation-inspections.index', ['userId' => $userId])
            ->with('success', 'Inspeksi sanitasi berhasil dihapus.');
    }
}