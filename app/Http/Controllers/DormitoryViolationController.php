<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryViolation;
use App\Models\DormitoryResident;
use App\Models\AcademicYear;
use App\Services\DormitoryService;
use Illuminate\Http\Request;

class DormitoryViolationController extends Controller
{
    protected DormitoryService $service;

    public function __construct(DormitoryService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = DormitoryViolation::with(['student', 'room', 'recordedBy'])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->where('violation_type', 'like', "%{$q}%")
                ->orWhereHas('student', fn($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        if ($request->filled('violation_category')) {
            $query->where('violation_category', $request->violation_category);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('violation_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $violations = $query->orderByDesc('violation_date')->paginate(20)->withQueryString();

        $stats = [
            'total'  => DormitoryViolation::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->count(),
            'ringan' => DormitoryViolation::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('violation_category', 'ringan')->count(),
            'sedang' => DormitoryViolation::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('violation_category', 'sedang')->count(),
            'berat'  => DormitoryViolation::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('violation_category', 'berat')->count(),
        ];

        return view('dormitory.violations.index', compact(
            'dormitory', 'violations', 'userId', 'stats', 'activeYear'
        ));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $residents = DormitoryResident::with('student')
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->where('is_active', true)
            ->orderBy('room_id')
            ->get();

        return view('dormitory.violations.create', compact('dormitory', 'residents', 'userId', 'activeYear'));
    }

    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'student_id'          => 'required|exists:students,id',
            'room_id'             => 'required|exists:dormitory_rooms,id',
            'violation_date'      => 'required|date',
            'violation_category'  => 'required|in:ringan,sedang,berat',
            'violation_type'      => 'required|string|max:100',
            'description'          => 'nullable|string',
            'points'              => 'nullable|integer|min:0',
            'action_taken'        => 'nullable|string',
            'follow_up'           => 'nullable|string',
            'witness_id'          => 'nullable|exists:users,id',
            'notes'               => 'nullable|string',
        ]);

        $data['dormitory_id'] = $asramaUuid;
        $data['academic_year_id'] = $activeYear->id;
        $data['recorded_by'] = auth()->id();

        $violation = DormitoryViolation::create($data);

        // Kirim notifikasi ke wali
        $this->service->notifyMahromOnViolation($violation);

        return redirect()->route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Pelanggaran berhasil dicatat dan notifikasi ke wali telah dikirim.');
    }

    public function show(Request $request, string $userId, string $asramaUuid, string $violationUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $violation = DormitoryViolation::with(['student', 'room', 'recordedBy', 'witness'])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($violationUuid);

        return view('dormitory.violations.show', compact('dormitory', 'violation', 'userId'));
    }

    public function notifyParent(Request $request, string $userId, string $asramaUuid, string $violationUuid)
    {
        $violation = DormitoryViolation::where('dormitory_id', $asramaUuid)->findOrFail($violationUuid);

        if ($violation->parent_notified_at) {
            return back()->with('info', 'Notifikasi sudah pernah dikirim.');
        }

        $violation->update(['parent_notified_at' => now()]);

        // Kirim notifikasi via service
        $this->service->notifyMahromOnViolation($violation);

        return back()->with('success', 'Notifikasi ke wali berhasil dikirim.');
    }
}
