<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Services\Asrama\AcademicIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademicIntegrationController extends Controller
{
    public function __construct(
        protected AcademicIntegrationService $service,
    ) {}

    public function index(Request $request): View
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        $filters = $request->only(['q', 'dormitory_id', 'status']);

        $students = Student::with(['dormitory', 'room'])
            ->whereNotNull('dormitory_id')
            ->when($filters['q'] ?? null, fn($q, $v) => $q->where('name', 'like', "%{$v}%")->orWhere('nis', 'like', "%{$v}%"))
            ->when($filters['dormitory_id'] ?? null, fn($q, $v) => $q->where('dormitory_id', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $dormitories = DB::table('dormitories')->where('is_active', true)->orderBy('name')->get();

        $recentChanges = DB::table('boarding_timeline_events')
            ->where('source_system', 'academic')
            ->where('event_at', '>=', now()->subDays(30))
            ->orderBy('event_at', 'desc')
            ->limit(10)
            ->get();

        return view('dormitory.academic.index', [
            'students' => $students,
            'filters' => $filters,
            'dormitories' => $dormitories,
            'academicYear' => $academicYear,
            'recentChanges' => $recentChanges,
        ]);
    }

    public function sync(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string'],
            'new_status' => ['required', Rule::in(['graduate', 'inactive', 'dropped', 'transfer_out', 'transfer'])],
            'reason'     => ['nullable', 'string', 'max:500'],
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();

        $event = $this->service->syncFromAcademicStatus(
            studentId: $data['student_id'],
            newStatus: $data['new_status'],
            reason: $data['reason'] ?? null,
            academicYearId: $activeYear?->id,
            actorId: auth()->id(),
        );

        return back()->with('success', "Sinkronisasi berhasil: status diubah ke {$data['new_status']}.");
    }
}