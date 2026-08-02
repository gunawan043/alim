<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\CheckoutResidentRequest;
use App\Http\Requests\Dormitory\StoreResidentRequest;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryResident;
use App\Services\StudentLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DormitoryResidentController extends Controller
{
    private StudentLookupService $lookup;

    public function __construct(StudentLookupService $lookup)
    {
        $this->lookup = $lookup;
    }

    /**
     * GET /{userId}/asrama/{asramaUuid}/daftar-santri
     *
     * Hanya menampilkan santri yang MEMILIKI relasi aktif sebagai penghuni
     * asrama ini. Akademik = SSOT; Asrama hanya mengelola penempatan.
     */
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        // Query dimulai dari DormitoryResident (penempatan aktif), lalu
        // membaca identitas melalui relationship ke Student (SSOT).
        $query = DormitoryResident::with([
            'student:id,user_id,school_id,nisn,nis,name,gender,birth_place,birth_date,phone,mobile_phone,email,photo_path,status,entry_date',
            'student.currentClassHistory.studyGroup.gradeLevel',
            'student.primaryMahrom',
            'room',
            'room.wing',
        ])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->where('is_active', true);

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->whereHas('student', fn ($st) => $st
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('nisn', 'like', "%{$q}%")
                )
            );
        }

        $residents = $query->orderByDesc('is_active')->orderBy('bed_number')->paginate(20)->withQueryString();
        $rooms = \App\Models\DormitoryRoom::where('dormitory_id', $asramaUuid)->where('is_active', true)->orderBy('code')->get();

        $residentIds = $residents->pluck('student_id')->all();

        $activePermits = DormitoryPermit::whereIn('student_id', $residentIds)
            ->whereIn('status', ['approved', 'overdue'])
            ->whereNull('actual_return_datetime')
            ->get()
            ->keyBy('student_id');

        $stats = [
            'total' => DormitoryResident::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('is_active', true)->count(),
            'active' => DormitoryResident::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('is_active', true)->count(),
            'on_permit' => $activePermits->count(),
            'in_dormitory' => max(0, DormitoryResident::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('is_active', true)->whereNotIn('student_id', $activePermits->keys()->all())->count()),
            'occupied_rooms' => \App\Models\DormitoryRoom::where('dormitory_id', $asramaUuid)->where('is_active', true)->whereHas('residents', function ($q) use ($asramaUuid, $activeYear) {
                $q->where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('is_active', true);
            })->count(),
            'total_rooms' => \App\Models\DormitoryRoom::where('dormitory_id', $asramaUuid)->where('is_active', true)->count(),
        ];

        return view('dormitory.residents.index', compact(
            'dormitory', 'residents', 'rooms', 'userId', 'stats', 'activeYear', 'activePermits'
        ));
    }

    /**
     * GET /{userId}/asrama/{asramaUuid}/penghuni/tambah
     * Route name: user.asrama.residents.create
     *
     * Halaman "Tempatkan Santri" — bukan membuat Student baru.
     * Petugas hanya mencari, memilih, dan menetapkan penempatan (DormitoryResident).
     */
    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();
        $rooms = \App\Models\DormitoryRoom::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->withCount(['residents as current_occupancy' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('code')->get();

        return view('dormitory.residents.create', compact('dormitory', 'rooms', 'userId', 'activeYear'));
    }

    /**
     * POST /{userId}/asrama/{asramaUuid}/tempatkan-santri
     *
     * Simpan data penempatan (DormitoryResident).
     * TIDAK membuat/mengubah Student — Student adalah milik Modul Akademik.
     */
    public function store(StoreResidentRequest $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $data = $request->validated();

        // Final duplicate guard (service validated, but re-check in transaction)
        $existing = DormitoryResident::where('student_id', $data['student_id'])
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            return back()->withInput()->withErrors([
                'student_id' => 'Santri ini sudah tercatat sebagai penghuni aktif di asrama lain pada tahun ajaran ini.',
            ]);
        }

        $room = \App\Models\DormitoryRoom::find($data['room_id']);

        if ($room) {
            $currentOccupancy = DormitoryResident::where('room_id', $data['room_id'])
                ->where('is_active', true)
                ->count();

            if ($currentOccupancy >= $room->capacity) {
                return back()->withInput()->withErrors([
                    'room_id' => 'Kamar ini sudah penuh (kapasitas: '.$room->capacity.' orang).',
                ]);
            }
        }

        DB::transaction(function () use ($data, $asramaUuid, $activeYear) {
            $data['dormitory_id'] = $asramaUuid;
            $data['academic_year_id'] = $activeYear->id;
            $data['is_active'] = true;

            DormitoryResident::create($data);
        });

        $this->lookup->invalidateCache($data['student_id']);

        return redirect()->route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Penempatan Santri berhasil disimpan.');
    }

    /**
     * GET /{userId}/asrama/{asramaUuid}/penghuni/{residentUuid}
     * Shows resident details with read-only Academic profile panel.
     */
    public function show(Request $request, string $userId, string $asramaUuid, string $residentUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $resident = DormitoryResident::with([
            'student.mahroms',
            'room.wing',
            'dormitory',
            'academicYear',
        ])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($residentUuid);

        // Get full read-only Academic profile
        $academicProfile = $this->lookup->getProfile($resident->student_id);

        // Get all other active assignments for this student
        $otherAssignments = $this->lookup->getAllActiveAssignments($resident->student_id)
            ->where('id', '!=', $resident->id)
            ->values();

        $activeYear = AcademicYear::where('is_active', true)->first();

        // Get violations for this student in the current academic year
        $violations = $resident->student?->violationPoints()
            ->where('academic_year_id', $activeYear?->id)
            ->get();

        // Get dormitory permits for this student in the current academic year
        $permits = $resident->student
            ? \App\Models\DormitoryPermit::where('student_id', $resident->student_id)
                ->where('academic_year_id', $activeYear?->id)
                ->where('dormitory_id', $asramaUuid)
                ->orderByDesc('departure_datetime')
                ->get()
            : collect();

        // Get other active residents in the same room (for show tab "Santri Lain di Kamar Ini")
        $roomResidents = $resident->room_id
            ? DormitoryResident::with(['student'])
                ->where('dormitory_id', $asramaUuid)
                ->where('room_id', $resident->room_id)
                ->where('is_active', true)
                ->where('academic_year_id', $activeYear?->id)
                ->orderBy('bed_number')
                ->get()
            : collect();

        return view('dormitory.residents.show', compact(
            'resident', 'dormitory', 'userId', 'activeYear',
            'academicProfile', 'otherAssignments', 'violations', 'permits', 'roomResidents'
        ));
    }

    /**
     * POST check-out penghuni
     */
    public function checkout(CheckoutResidentRequest $request, string $userId, string $asramaUuid, string $residentUuid)
    {
        $resident = DormitoryResident::where('dormitory_id', $asramaUuid)->findOrFail($residentUuid);

        $data = $request->validated();

        $resident->update([
            'is_active' => false,
            'check_out_date' => $data['check_out_date'],
            'check_out_reason' => $data['check_out_reason'],
            'notes' => $data['notes'] ?? $resident->notes,
        ]);

        $this->lookup->invalidateCache($resident->student_id);

        return back()->with('success', 'Penghuni berhasil dikeluarkan.');
    }

    /**
     * AJAX: cari student untuk dihuni
     * Enhanced with service-based filtering and dormitory assignment info.
     */
    public function findStudent(Request $request)
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        // Take dormitory ID from query param (sent by JS) or route parameter as fallback
        $dormitoryId = $request->get('dormitory_id') ?? $request->route('asramaUuid');

        $academicYear = AcademicYear::where('is_active', true)->first();

        $students = $this->lookup->search(
            query: $q,
            dormitoryId: $dormitoryId,
            academicYearId: $academicYear?->id,
            limit: 20
        );

        return response()->json([
            'results' => $students->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'nisn' => $s->nisn,
                    'nis' => $s->nis,
                    'gender' => $s->gender,
                    'gender_text' => $s->gender_text,
                    'birth_place' => $s->birth_place,
                    'birth_date' => $s->birth_date,
                    'is_assigned' => $s->is_assigned,
                    'assigned_dormitory' => $s->assigned_dormitory,
                    'assigned_room' => $s->assigned_room,
                    'room_id' => $s->room_id ?? null,
                    'room_name' => $s->room_name ?? null,
                ];
            }),
        ]);
    }
}
