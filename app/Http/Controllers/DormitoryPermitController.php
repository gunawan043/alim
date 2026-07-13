<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\ApprovePermitRequest;
use App\Http\Requests\Dormitory\RecordReturnRequest;
use App\Http\Requests\Dormitory\RejectPermitRequest;
use App\Http\Requests\Dormitory\StorePermitRequest;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Services\Boarding\LeaveWorkflowService;
use App\Services\DormitoryService;
use Illuminate\Http\Request;

class DormitoryPermitController extends Controller
{
    protected DormitoryService $service;

    public function __construct(
        DormitoryService $service,
        private readonly LeaveWorkflowService $leave,
    ) {
        $this->service = $service;
    }

    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = DormitoryPermit::with(['student', 'room', 'mahrom', 'approvedBy'])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('permit_type')) {
            $query->where('permit_type', $request->permit_type);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('departure_datetime', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->whereHas('student', fn ($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        $permits = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'pending' => DormitoryPermit::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('status', 'pending')->count(),
            'approved' => DormitoryPermit::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('status', 'approved')->count(),
            'overdue' => DormitoryPermit::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('status', 'overdue')->count(),
        ];

        return view('dormitory.permits.index', compact(
            'dormitory', 'permits', 'userId', 'stats', 'activeYear'
        ));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        // Ambil resident aktif
        $residents = \App\Models\DormitoryResident::with('student.mahroms')
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->where('is_active', true)
            ->orderBy('room_id')
            ->get();

        return view('dormitory.permits.create', compact('dormitory', 'residents', 'userId', 'activeYear'));
    }

    public function store(StorePermitRequest $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $data = $request->validated();

        // Jika permit_type = sakit, wajib ada StudentHealthPermit yg sudah approved
        if ($data['permit_type'] === 'sakit') {
            $healthPermit = StudentHealthPermit::where('student_id', $data['student_id'])
                ->where('status', 'approved')
                ->where('dormitory_id', $asramaUuid)
                ->whereDate('start_date', '<=', $data['departure_datetime'])
                ->whereDate('end_date', '>=', $data['departure_datetime'])
                ->first();

            if (! $healthPermit) {
                return back()->withInput()->withErrors([
                    'permit_type' => 'Izin sakit hanya bisa diajukan jika ada keterangan sakit dari UKS yang sudah disetujui.',
                ]);
            }
        }

        // Submit through the workflow service — it handles policy check,
        // rules engine, timeline, and quota update atomically.
        try {
            $permit = $this->leave->submit(
                data: $data,
                dormitoryId: $asramaUuid,
                activeYearId: $activeYear->id,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors([
                'permit_type' => $e->getMessage(),
            ]);
        }

        return redirect()->route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Permintaan izin berhasil diajukan.');
    }

    public function show(Request $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $permit = DormitoryPermit::with(['student.mahroms', 'room', 'mahrom', 'approvedBy', 'creator'])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($permitUuid);

        return view('dormitory.permits.show', compact('dormitory', 'permit', 'userId'));
    }

    public function approve(ApprovePermitRequest $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $data = $request->validated();

        // Delegate to the workflow service. It re-runs the rules engine,
        // transitions the student status to ON_LEAVE, and emits the timeline event.
        $this->leave->approve(
            permitId: $permitUuid,
            dormitoryId: $asramaUuid,
            note: $data['approval_note'] ?? null,
        );

        return back()->with('success', 'Izin berhasil disetujui dan notifikasi ke wali telah dikirim.');
    }

    public function reject(RejectPermitRequest $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $data = $request->validated();

        $this->leave->reject(
            permitId: $permitUuid,
            dormitoryId: $asramaUuid,
            note: $data['approval_note'] ?? null,
        );

        return back()->with('success', 'Izin ditolak.');
    }

    public function returnRecord(RecordReturnRequest $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $data = $request->validated();

        $this->leave->recordReturn(
            permitId: $permitUuid,
            dormitoryId: $asramaUuid,
            actualReturnDatetime: $data['actual_return_datetime'],
        );

        // Catat bahwa permit selesai (bukan overdue)
        return back()->with('success', 'Kepulangan berhasil dicatat.');
    }
}
