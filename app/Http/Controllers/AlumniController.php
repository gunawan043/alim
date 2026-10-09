<?php

namespace App\Http\Controllers;

use App\Exports\AlumniExport;
use App\Models\Alumni;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\Request;
use Dompdf\Dompdf;
use Maatwebsite\Excel\Facades\Excel;

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

        // Sinkronisasi pengaman (opsional via ?sync=1) dijalankan sebelum query
        // agar statistik & hasil halaman langsung akurat.
        if ($request->boolean('sync')) {
            $created = $this->syncMissingGraduates($schoolContextId);
            session()->flash('success', "Sinkronisasi selesai: {$created} data alumni baru dibuat.");
        }

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
            $query->whereHas('student', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('nisn', 'like', "%{$search}%")
                ->orWhere('nik', 'like', "%{$search}%")
            );
        }

        // Available graduation years
        $graduationYears = Alumni::query()
            ->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId))
            ->selectRaw('DISTINCT graduation_year')
            ->orderByDesc('graduation_year')
            ->pluck('graduation_year');

        // Stats (COUNT murah dari clone query terfilter)
        $totalAlumni = (clone $query)->count();
        $tracerFilled = (clone $query)->filledTracer()->count();
        $tracerPending = (clone $query)->pendingTracer()->count();
        $tracerVerified = (clone $query)->where('tracer_status', 'verified')->count();

        // Paginated results
        $perPage = min(100, max(5, (int) $request->get('per_page', 15)));
        $alumni = $query->orderByDesc('graduation_year')
            ->orderBy('student_id')
            ->paginate($perPage)
            ->withQueryString();

        return view('alumni.index', compact(
            'alumni', 'schools', 'graduationYears',
            'totalAlumni', 'tracerFilled', 'tracerPending', 'tracerVerified',
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
            ->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId))
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
            ->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId))
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
            ->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId))
            ->findOrFail($alumniUuid);

        $validated = $request->validate([
            // Continuer
            'continuing_study_status' => 'required|in:belum,sedang,sudah',
            'higher_education_institution' => 'nullable|string|max:255',
            'study_program' => 'nullable|string|max:255',
            'higher_education_city' => 'nullable|string|max:100',
            'higher_education_year_start' => 'nullable|integer|min:1990|max:2100',
            'further_study_institution' => 'nullable|string|max:255',
            'further_study_program' => 'nullable|string|max:255',
            // Working
            'working_status' => 'required|in:belum,sedang,sudah',
            'occupation' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:20',
            'company_city' => 'nullable|string|max:100',
            'monthly_income' => 'nullable|numeric|min:0',
            'working_year_start' => 'nullable|integer|min:1990|max:2100',
            // Contact & Notes
            'is_contactable' => 'nullable|boolean',
            'achievements' => 'nullable|string|max:1000',
            'tracer_notes' => 'nullable|string|max:1000',
        ]);

        // Jangan turunkan status yang sudah diverifikasi.
        if ($alumni->tracer_status !== 'verified') {
            $validated['tracer_status'] = 'filled';
        }
        $validated['tracer_filled_at'] = $alumni->tracer_filled_at ?? now();
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
            ->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId))
            ->findOrFail($alumniUuid);

        if ($alumni->tracer_status !== 'filled') {
            return back()->with('error', 'Tracer study harus diisi terlebih dahulu.');
        }

        $alumni->update(['tracer_status' => 'verified']);

        return back()->with('success', 'Tracer study berhasil diverifikasi.');
    }

    /**
     * Verifikasi massal tracer study terpilih (status filled → verified).
     */
    public function bulkVerify(Request $request)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');

        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        $count = Alumni::query()
            ->when($schoolContextId, fn ($q) => $q->bySchool($schoolContextId))
            ->whereIn('id', $data['ids'])
            ->where('tracer_status', 'filled')
            ->update(['tracer_status' => 'verified']);

        return back()->with($count > 0 ? 'success' : 'error', "{$count} tracer study diverifikasi.");
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
        return Excel::download(
            new AlumniExport($alumni),
            'data-alumni-'.date('Y-m-d').'.xlsx'
        );
    }

    private function exportPdf($alumni)
    {
        $html = view('alumni.export-pdf', [
            'alumni' => $alumni,
            'date' => now()->locale('id')->translatedFormat('d F Y'),
        ])->render();

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="data-alumni-'.date('Y-m-d').'.pdf"',
        ]);
    }

    /**
     * Statistics dashboard.
     */
    public function statistics(Request $request)
    {
        $userId = $request->route('userId') ?? auth()->id();
        $schoolContextId = $request->attributes->get('schoolContextId');

        $baseQuery = Alumni::query()->when($schoolContextId, fn ($q) => $q->where('school_id', $schoolContextId));

        // Total per year
        $byYear = (clone $baseQuery)
            ->selectRaw('graduation_year, COUNT(*) as total')
            ->groupBy('graduation_year')
            ->orderByDesc('graduation_year')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'year' => $r->graduation_year,
                'total' => $r->total,
            ]);

        // Tracer completion rate
        $tracerStats = (clone $baseQuery)
            ->selectRaw('tracer_status, COUNT(*) as count')
            ->groupBy('tracer_status')
            ->pluck('count', 'tracer_status');

        $totalTracer = $tracerStats->sum();
        $tracerVerified = $tracerStats->get('verified', 0);
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
            'byYear', 'tracerStats', 'tracerFilledPct', 'tracerVerified',
            'studyStats', 'workingStats', 'bySchool',
            'totalTracer', 'userId', 'schoolContextId',
        ));
    }

    /**
     * Auto-sync: ensure all graduates have alumni records.
     * Called on index() as safety net.
     */
    public function syncMissingGraduates(?string $schoolContextId = null): int
    {
        $graduatesQuery = Student::where('status', 'graduate')
            ->whereNotNull('graduation_year');

        if ($schoolContextId) {
            $graduatesQuery->where('school_id', $schoolContextId);
        }

        $graduates = $graduatesQuery->get();

        $created = 0;
        foreach ($graduates as $student) {
            $alumni = Alumni::firstOrCreate(
                ['student_id' => $student->id],
                [
                    'school_id' => $student->school_id,
                    'graduation_year' => $student->graduation_year,
                    'graduation_certificate_number' => $student->certificate_number,
                    'graduation_date' => $student->graduation_date,
                    'tracer_status' => 'pending',
                ]
            );

            if ($alumni->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }
}
