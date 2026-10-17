<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\DormitoryPermit;
use App\Models\DormitoryVisitLog;
use App\Models\Student;
use App\Services\Boarding\LeaveWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class WaliSantriPortalController extends Controller
{
    public function __construct(
        protected LeaveWorkflowService $leave,
    ) {}

    /**
     * Find students linked to this guardian.
     */
    private function linkedStudents(string $guardianUserId)
    {
        $user = auth()->user();

        $nikFilter = array_filter([$user->nik ?? null]);

        return Student::with(['dormitory', 'room'])
            ->where(function ($q) use ($user, $nikFilter) {
                if (! empty($user->id)) {
                    $q->orWhere('user_id', $user->id);
                }
                foreach ($nikFilter as $nik) {
                    $q->orWhere('father_nik', $nik)
                        ->orWhere('mother_nik', $nik)
                        ->orWhere('guardian_nik', $nik);
                }
            })
            ->get();
    }

    public function index(Request $request): View
    {
        $students = $this->linkedStudents(auth()->id());

        $selectedId = $request->query('student_id');
        $selected = $students->firstWhere('id', $selectedId) ?? $students->first();

        $permits = $selected
            ? DormitoryPermit::where('student_id', $selected->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
            : collect();

        $visits = $selected
            ? DormitoryVisitLog::where('student_id', $selected->id)
                ->orderBy('expected_arrival_datetime', 'desc')
                ->limit(10)
                ->get()
            : collect();

        $policy = $selected
            ? $selected->dormitory?->policy()
                ->where('is_active', true)
                ->orderBy('effective_from', 'desc')
                ->first()
            : null;

        return view('dormitory.wali.dashboard', [
            'students' => $students,
            'selected' => $selected,
            'permits' => $permits,
            'visits' => $visits,
            'policy' => $policy,
            'today' => now(),
        ]);
    }

    public function requestPermit(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'expected_return_date' => ['required', 'date', 'after_or_equal:departure_date'],
            'destination' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:500'],
            'companion' => ['nullable', 'string', 'max:255'],
        ]);

        $student = Student::where('user_id', auth()->id())
            ->orWhere('father_nik', auth()->user()->nik ?? '')
            ->orWhere('mother_nik', auth()->user()->nik ?? '')
            ->orWhere('guardian_nik', auth()->user()->nik ?? '')
            ->findOrFail($data['student_id']);

        try {
            $permit = $this->leave->submit(
                array_merge($data, [
                    'permit_type' => 'wali_request',
                    'departure_datetime' => $data['departure_date'],
                    'expected_return_at' => $data['expected_return_date'],
                    'companion_name' => $data['companion'] ?? null,
                    'submitted_via' => 'wali_portal',
                    'created_by' => auth()->id(),
                ]),
                $student->dormitory_id,
                $student->academic_year_id ?? AcademicYear::active()?->id,
            );
        } catch (\Throwable $e) {
            Log::error('WaliSantri.request_permit_failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Gagal mengajukan izin: '.$e->getMessage()]);
        }

        return back()->with('success', 'Pengajuan izin berhasil dikirim. Menunggu persetujuan.');
    }
}
