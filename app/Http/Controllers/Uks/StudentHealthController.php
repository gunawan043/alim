<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentHealthCheckup;
use App\Models\StudentHealthMetric;
use App\Models\StudentHealthPermit;
use App\Models\StudentHealthRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * StudentHealthController — manage student health data in UKS.
 *
 * Access: Only accessible to users with UKS roles (Kepala UKS, Admin UKS/Putri).
 */
class StudentHealthController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role.access:Kepala UKS,Admin UKS,Admin UKS']);
    }

    /**
     * Display listing of student health records.
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $schoolId = $request->attributes->get('schoolContextId');

        $query = Student::where('status', 'active')
            ->with(['user.gtkProfile', 'dormitoryResident.dormitory', 'healthRecord']);

        // Filter by school context
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        // Search
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->where('name', 'like', "%{$q}%")
                ->orWhere('nis', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhereHas('dormitoryResident.dormitory', fn ($d) => $d->where('name', 'like', "%{$q}%"))
            );
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('uks.student-health.index', [
            'students' => $users,
            'isDormitoryUser' => optional(request()->user())->isDormitoryUser() ?? false,
        ]);
    }

    /**
     * Show detailed health profile for a student.
     */
    public function show(string $studentUuid)
    {
        $studentModel = Student::with('user')->findOrFail($studentUuid);

        $student = $studentModel->user ?? $studentModel;

        // Load related health data
        $record = StudentHealthRecord::where('student_id', $studentModel->id)->first();
        $checkups = StudentHealthCheckup::where('student_id', $studentModel->id)
            ->orderByDesc('checkup_date')
            ->limit(10)
            ->with('examBy')
            ->get();
        $permits = StudentHealthPermit::where('student_id', $studentModel->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        $metrics = StudentHealthMetric::where('student_id', $studentModel->id)
            ->orderByDesc('recorded_at')
            ->limit(12)
            ->get();

        return view('uks.student-health.profile', compact(
            'student', 'studentModel', 'record', 'checkups', 'permits', 'metrics'
        ));
    }
}
