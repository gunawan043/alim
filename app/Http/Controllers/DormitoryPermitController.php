<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryResident;
use App\Models\StudentMahrom;
use App\Models\StudentHealthPermit;
use App\Models\AcademicYear;
use App\Services\DormitoryService;
use Illuminate\Http\Request;

class DormitoryPermitController extends Controller
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
            $query->where(fn($sq) => $sq
                ->whereHas('student', fn($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        $permits = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'pending'  => DormitoryPermit::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('status', 'pending')->count(),
            'approved' => DormitoryPermit::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('status', 'approved')->count(),
            'overdue'  => DormitoryPermit::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('status', 'overdue')->count(),
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

    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'student_id'              => 'required|exists:students,id',
            'room_id'                 => 'required|exists:dormitory_rooms,id',
            'permit_type'             => 'required|in:pulang,keluar_kota,berobat,keperluan_keluarga, Lainnya',
            'destination'             => 'nullable|string|max:191',
            'purpose'                 => 'nullable|string',
            'departure_datetime'      => 'required|date',
            'expected_return_datetime'=> 'required|date|after:departure_datetime',
            'mahrom_id'               => 'nullable|exists:student_mahroms,id',
            'companion_name'          => 'nullable|string|max:191',
            'companion_relation'      => 'nullable|string|max:100',
            'companion_phone'         => 'nullable|string|max:20',
            'companion_is_mahrom'     => 'boolean',
            'notes'                   => 'nullable|string',
        ]);

        // Jika permit_type = sakit, wajib ada StudentHealthPermit yg sudah approved
        if ($data['permit_type'] === 'sakit') {
            $healthPermit = StudentHealthPermit::where('student_id', $data['student_id'])
                ->where('status', 'approved')
                ->where('dormitory_id', $asramaUuid)
                ->whereDate('start_date', '<=', $data['departure_datetime'])
                ->whereDate('end_date', '>=', $data['departure_datetime'])
                ->first();

            if (!$healthPermit) {
                return back()->withInput()->withErrors([
                    'permit_type' => 'Izin sakit hanya bisa diajukan jika ada keterangan sakit dari UKS yang sudah disetujui.'
                ]);
            }
        }

        // Cek mahrom validity
        $companionIsMahrom = false;
        if (!empty($data['mahrom_id'])) {
            $mahrom = StudentMahrom::where('id', $data['mahrom_id'])
                ->where('student_id', $data['student_id'])
                ->where('is_active', true)
                ->first();

            if ($mahrom) {
                $companionIsMahrom = true;
                $data['companion_name'] = $mahrom->name;
                $data['companion_relation'] = $mahrom->relationship_text;
                $data['companion_phone'] = $mahrom->phone;
            }
        }
        $data['companion_is_mahrom'] = $companionIsMahrom;

        $data['dormitory_id'] = $asramaUuid;
        $data['academic_year_id'] = $activeYear->id;
        $data['status'] = 'pending';
        $data['created_by'] = auth()->id();

        DormitoryPermit::create($data);

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

    public function approve(Request $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $permit = DormitoryPermit::where('dormitory_id', $asramaUuid)->findOrFail($permitUuid);

        $data = $request->validate([
            'approval_note' => 'nullable|string',
        ]);

        $permit->update([
            'status'       => 'approved',
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
            'approval_note'=> $data['approval_note'] ?? null,
        ]);

        // Kirim notifikasi ke wali
        $this->service->notifyMahromOnPermitApproval($permit);

        return back()->with('success', 'Izin berhasil disetujui dan notifikasi ke wali telah dikirim.');
    }

    public function reject(Request $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $permit = DormitoryPermit::where('dormitory_id', $asramaUuid)->findOrFail($permitUuid);

        $data = $request->validate([
            'approval_note' => 'nullable|string',
        ]);

        $permit->update([
            'status'       => 'rejected',
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
            'approval_note'=> $data['approval_note'] ?? null,
        ]);

        return back()->with('success', 'Izin ditolak.');
    }

    public function returnRecord(Request $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $permit = DormitoryPermit::where('dormitory_id', $asramaUuid)->findOrFail($permitUuid);

        $data = $request->validate([
            'actual_return_datetime' => 'required|date',
        ]);

        $permit->update([
            'actual_return_datetime' => $data['actual_return_datetime'],
            'status' => 'returned',
        ]);

        // Catat bahwa permit selesai (bukan overdue)
        return back()->with('success', 'Kepulangan berhasil dicatat.');
    }
}