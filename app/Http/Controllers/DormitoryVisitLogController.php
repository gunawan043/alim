<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\ApproveVisitLogRequest;
use App\Http\Requests\Dormitory\RejectVisitLogRequest;
use App\Http\Requests\Dormitory\StoreVisitRequest;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryVisitLog;
use App\Services\Boarding\VisitWorkflowService;
use Illuminate\Http\Request;

class DormitoryVisitLogController extends Controller
{
    public function __construct(
        private readonly VisitWorkflowService $visit,
    ) {}

    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $query = DormitoryVisitLog::with(['student', 'room', 'approvedBy'])
            ->where('dormitory_id', $asramaUuid);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('expected_arrival_datetime', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->where('visitor_name', 'like', "%{$q}%")
                ->orWhereHas('student', fn ($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        $visits = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'pending' => DormitoryVisitLog::where('dormitory_id', $asramaUuid)->where('status', 'pending')->count(),
            'approved' => DormitoryVisitLog::where('dormitory_id', $asramaUuid)->where('status', 'approved')->count(),
            'arrived' => DormitoryVisitLog::where('dormitory_id', $asramaUuid)->where('status', 'arrived')->count(),
            'checked_out' => DormitoryVisitLog::where('dormitory_id', $asramaUuid)->where('status', 'checked_out')->count(),
        ];

        return view('dormitory.visits.index', compact('dormitory', 'visits', 'userId', 'stats'));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        // Find active year — prefer the one that has residents, otherwise any active year
        $activeYear = AcademicYear::where('is_active', true)->first();
        $yearId = $activeYear?->id;

        // Build query: filter by year only if residents with that year exist
        $residentsQuery = DormitoryResident::with('student.mahroms')
            ->where('dormitory_id', $asramaUuid)
            ->where('is_active', true);

        if ($yearId) {
            $hasResidentsInYear = $residentsQuery->clone()->where('academic_year_id', $yearId)->exists();
            if ($hasResidentsInYear) {
                $residentsQuery->where('academic_year_id', $yearId);
            }
            // else: skip year filter — show all active residents in this dormitory
        }

        $residents = $residentsQuery->get();

        // flatten to students collection for the select dropdown
        $students = $residents->pluck('student')->filter()->values();

        return view('dormitory.visits.create', compact('dormitory', 'students', 'userId'));
    }

    public function store(StoreVisitRequest $request, string $userId, string $asramaUuid)
    {
        $data = $request->validated();

        $this->visit->submit(
            data: $data,
            dormitoryId: $asramaUuid,
        );

        return redirect()->route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Permintaan kunjungan berhasil diajukan.');
    }

    public function show(Request $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $visit = DormitoryVisitLog::with(['student', 'room', 'approvedBy', 'creator'])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($visitUuid);

        return view('dormitory.visits.show', compact('dormitory', 'visit', 'userId'));
    }

    public function approve(ApproveVisitLogRequest $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $this->visit->approve(
            visitId: $visitUuid,
            dormitoryId: $asramaUuid,
            note: $request->input('approval_note'),
        );

        return back()->with('success', 'Kunjungan disetujui.');
    }

    public function reject(RejectVisitLogRequest $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $this->visit->reject(
            visitId: $visitUuid,
            dormitoryId: $asramaUuid,
            note: $request->input('reject_reason'),
        );

        return back()->with('success', 'Kunjungan ditolak.');
    }

    public function checkIn(Request $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $this->visit->checkIn(
            visitId: $visitUuid,
            dormitoryId: $asramaUuid,
        );

        return back()->with('success', 'Check-in kunjungan berhasil.');
    }

    public function checkOut(Request $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $this->visit->checkOut(
            visitId: $visitUuid,
            dormitoryId: $asramaUuid,
        );

        return back()->with('success', 'Check-out kunjungan berhasil.');
    }
}
