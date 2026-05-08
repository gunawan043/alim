<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryVisitLog;
use App\Models\DormitoryResident;
use App\Models\StudentMahrom;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class DormitoryVisitLogController extends Controller
{
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
            $query->where(fn($sq) => $sq
                ->where('visitor_name', 'like', "%{$q}%")
                ->orWhereHas('student', fn($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        $visits = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'pending'   => DormitoryVisitLog::where('dormitory_id', $asramaUuid)->where('status', 'pending')->count(),
            'approved'  => DormitoryVisitLog::where('dormitory_id', $asramaUuid)->where('status', 'approved')->count(),
            'arrived'  => DormitoryVisitLog::where('dormitory_id', $asramaUuid)->where('status', 'arrived')->count(),
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

    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $data = $request->validate([
            'student_id'                   => 'required|exists:students,id',
            'room_id'                      => 'nullable|exists:dormitory_rooms,id',
            'visitor_name'                 => 'required|string|max:191',
            'visitor_id_number'           => 'nullable|string|max:30',
            'visitor_phone'               => 'nullable|string|max:20',
            'visitor_relationship'        => 'required|in:mahrom,wali,keluarga,Pihak pondok, Lainnya',
            'purpose'                      => 'required|in:menjenguk,bawa_bantuan,pertemuan_wali,antar_jemput, Lainnya',
            'expected_arrival_datetime'   => 'required|date',
            'expected_meet_duration_minutes' => 'nullable|integer|min:15',
            'notes'                       => 'nullable|string',
        ]);

        $data['dormitory_id'] = $asramaUuid;
        $data['created_by'] = auth()->id();

        DormitoryVisitLog::create($data);

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

    public function approve(Request $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $visit = DormitoryVisitLog::where('dormitory_id', $asramaUuid)->findOrFail($visitUuid);

        $visit->update([
            'status'       => 'approved',
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
            'approval_note'=> $request->approval_note,
        ]);

        return back()->with('success', 'Kunjungan disetujui.');
    }

    public function reject(Request $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $visit = DormitoryVisitLog::where('dormitory_id', $asramaUuid)->findOrFail($visitUuid);

        $visit->update([
            'status'       => 'rejected',
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
            'approval_note'=> $request->approval_note,
        ]);

        return back()->with('success', 'Kunjungan ditolak.');
    }

    public function checkIn(Request $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $visit = DormitoryVisitLog::where('dormitory_id', $asramaUuid)->findOrFail($visitUuid);

        $visit->update([
            'status'             => 'arrived',
            'actual_arrival_datetime' => now(),
        ]);

        return back()->with('success', 'Check-in kunjungan berhasil.');
    }

    public function checkOut(Request $request, string $userId, string $asramaUuid, string $visitUuid)
    {
        $visit = DormitoryVisitLog::where('dormitory_id', $asramaUuid)->findOrFail($visitUuid);

        $visit->update([
            'status'            => 'checked_out',
            'departure_datetime' => now(),
        ]);

        return back()->with('success', 'Check-out kunjungan berhasil.');
    }
}
