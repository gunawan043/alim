<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\Student;
use App\Models\School;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AlumniController extends Controller
{
    /**
     * Display a listing of alumni.
     */
    public function index(Request $request)
    {
        $userId = $request->route('userId') ?? auth()->id();
        $schoolContextId = $request->attributes->get('schoolContextId');

        // Schools for filter
        $schools = $schoolContextId
            ? School::where('id', $schoolContextId)->get()
            : School::orderBy('name')->get();

        // Build query
        $query = Alumni::with(['student', 'school']);

        if ($schoolContextId) {
            $query->bySchool($schoolContextId);
        } elseif ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        if ($request->filled('graduation_year')) {
            $query->byYear($request->graduation_year);
        }

        if ($request->filled('tracer_status')) {
            $query->where('tracer_status', $request->tracer_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('nisn', 'like', "%{$search}%")
                ->orWhere('nik', 'like', "%{$search}%")
            );
        }

        // Available graduation years
        $graduationYears = Alumni::query()
            ->when($schoolContextId, fn($q) => $q->where('school_id', $schoolContextId))
            ->selectRaw('DISTINCT graduation_year')
            ->orderByDesc('graduation_year')
            ->pluck('graduation_year');

        // Stats
        $totalAlumni = (clone $query)->count();
        $tracerFilled = (clone $query)->filledTracer()->count();
        $tracerPending = (clone $query)->pendingTracer()->count();

        // Paginated results
        $alumni = $query->orderByDesc('graduation_year')
            ->orderBy('graduation_year')
            ->orderBy('graduation_year')
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        // Also sync any missing graduates → alumni on each index load (safety net)
        if ($request->get('sync', false)) {
            $this->syncMissingGraduates($schoolContextId);
        }

        return view('alumni.index', compact(
            'alumni', 'schools', 'graduationYears',
            'totalAlumni', 'tracerFilled', 'tracerPending',
            'userId', 'schoolContextId',
        ));
    }

    /**
     * Display the specified alumni.
     */
    public function show(Request $request, string $userId, string $alumniUuid)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');

        $alumni = Alumni::with(['student', 'school'])
            ->when($schoolContextId, fn($q) => $q->where('school_id', $schoolContextId))
            ->findOrFail($alumniUuid);

        return view('alumni.show', compact('alumni', 'userId'));
    }

    /**
     * Show the form for editing tracer study.
     */
    public function edit(Request $request, string $userId, string $alumniUuid)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');

        $alumni = Alumni::with(['student', 'school'])
            ->when($schoolContextId, fn($q) => $q->where('school_id', $schoolContextId))
            ->findOrFail($alumniUuid);

        return view('alumni.edit', compact('alumni', 'userId'));
    }

    /**
     * Update tracer study data.
     */
    public function update(Request $request, string $userId, string $alumniUuid)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');

        $alumni = Alumni::with(['student', 'school'])
            ->when($schoolContextId, fn($q) => $q->where('school_id', $schoolContextId))
            ->findOrFail($alumniUuid);

        $validated = $request->validate([
            // Continuer
            'continuing_study_status'    => 'required|in:belum,sedang,sudah',
            'higher_education_institution' => 'nullable|string|max:255',
            'study_program'               => 'nullable|string|max:255',
            'higher_education_city'       => 'nullable|string|max:100',
            'higher_education_year_start' => 'nullable|integer|min:1990|max:2100',
            'further_study_institution'   => 'nullable|string|max:255',
            'further_study_program'       => 'nullable|string|max:255',
            // Working
            'working_status'       => 'required|in:belum,sedang,sudah',
            'occupation'          => 'nullable|string|max:255',
            'company_name'        => 'nullable|string|max:255',
            'company_address'    => 'nullable|string|max:500',
            'company_phone'      => 'nullable|string|max:20',
            'company_city'       => 'nullable|string|max:100',
            'monthly_income'     => 'nullable|numeric|min:0',
            'working_year_start' => 'nullable|integer|min:1990|max:2100',
            // Contact & Notes
            'is_contactable'    => 'nullable|boolean',
            'achievements'     => 'nullable|string|max:1000',
            'tracer_notes'     => 'nullable|string|max:1000',
        ]);

        $validated['tracer_status'] = 'filled';
        $validated['tracer_filled_at'] = now();
        $validated['is_contactable'] = $request->boolean('is_contactable');

        $alumni->update($validated);

        return redirect()
            ->route('user.alumni.show', ['userId' => $userId, 'alumniUuid' => $alumni->id])
            ->with('success', 'Data tracer study berhasil disimpan.');
    }

    /**
     * Verify tracer study (mark as verified).
     */
    public function verify(Request $request, string $userId, string $alumniUuid)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');

        $alumni = Alumni::with(['student', 'school'])
            ->when($schoolContextId, fn($q) => $q->where('school_id', $schoolContextId))
            ->findOrFail($alumniUuid);

        if ($alumni->tracer_status !== 'filled') {
            return back()->with('error', 'Tracer study harus diisi terlebih dahulu.');
        }

        $alumni->update(['tracer_status' => 'verified']);

        return back()->with('success', 'Tracer study berhasil diverifikasi.');
    }

    /**
     * Export alumni data.
     */
    public function export(Request $request)
    {
        $userId = $request->route('userId') ?? auth()->id();
        $schoolContextId = $request->attributes->get('schoolContextId');
        $format = $request->get('format', 'xlsx');

        $query = Alumni::with(['student', 'school']);

        if ($schoolContextId) {
            $query->bySchool($schoolContextId);
        } elseif ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        if ($request->filled('graduation_year')) {
            $query->byYear($request->graduation_year);
        }

        $alumni = $query->orderByDesc('graduation_year')->get();

        if ($format === 'pdf') {
            return $this->exportPdf($alumni);
        }

        return $this->exportExcel($alumni);
    }

    private function exportExcel($alumni)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AlumniExport($alumni),
            'data-alumni-' . date('Y-m-d') . '.xlsx'
        );
    }

    private function exportPdf($alumni)
    {
        $pdf = \PDF::loadView('alumni.export-pdf', [
            'alumni' => $alumni,
            'date' => now()->locale('id')->translatedFormat('d F Y'),
        ]);

        return $pdf->download('data-alumni-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Statistics dashboard.
     */
    public function statistics(Request $request)
    {
        $userId = $request->route('userId') ?? auth()->id();
        $schoolContextId = $request->attributes->get('schoolContextId');

        $baseQuery = Alumni::query()->when($schoolContextId, fn($q) => $q->where('school_id', $schoolContextId));

        // Total per year
        $byYear = (clone $baseQuery)
            ->selectRaw('graduation_year, COUNT(*) as total')
            ->groupBy('graduation_year')
            ->orderByDesc('graduation_year')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'year' => $r->graduation_year,
                'total' => $r->total,
            ]);

        // Tracer completion rate
        $tracerStats = (clone $baseQuery)
            ->selectRaw('tracer_status, COUNT(*) as count')
            ->groupBy('tracer_status')
            ->pluck('count', 'tracer_status');

        $totalTracer = $tracerStats->sum();
        $tracerFilledPct = $totalTracer > 0 ? round(($tracerStats->get('filled', 0) + $tracerStats->get('verified', 0)) / $totalTracer * 100) : 0;

        // Study continuation
        $studyStats = (clone $baseQuery)
            ->selectRaw('continuing_study_status, COUNT(*) as count')
            ->groupBy('continuing_study_status')
            ->pluck('count', 'continuing_study_status');

        // Working status
        $workingStats = (clone $baseQuery)
            ->selectRaw('working_status, COUNT(*) as count')
            ->groupBy('working_status')
            ->pluck('count', 'working_status');

        // Schools breakdown
        $bySchool = (clone $baseQuery)
            ->join('schools', 'alumni.school_id', '=', 'schools.id')
            ->selectRaw('schools.name as school_name, COUNT(*) as total')
            ->groupBy('schools.id', 'schools.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('alumni.statistics', compact(
            'byYear', 'tracerStats', 'tracerFilledPct',
            'studyStats', 'workingStats', 'bySchool',
            'totalTracer', 'userId', 'schoolContextId',
        ));
    }

    /**
     * Auto-sync: ensure all graduates have alumni records.
     * Called on index() as safety net.
     */
    public function syncMissingGraduates(?string $schoolContextId = null): void
    {
        $graduatesQuery = Student::where('status', 'graduate')
            ->whereNotNull('graduation_year');

        if ($schoolContextId) {
            $graduatesQuery->where('school_id', $schoolContextId);
        }

        $graduates = $graduatesQuery->get();

        foreach ($graduates as $student) {
            Alumni::firstOrCreate(
                ['student_id' => $student->id],
                [
                    'school_id' => $student->school_id,
                    'graduation_year' => $student->graduation_year,
                    'graduation_certificate_number' => $student->certificate_number,
                    'graduation_date' => $student->graduation_date,
                    'tracer_status' => 'pending',
                ]
            );
        }
    }
}
