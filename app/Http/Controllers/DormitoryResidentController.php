<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\DormitoryResident;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\StudentMahrom;
use Illuminate\Http\Request;

class DormitoryResidentController extends Controller
{
    /**
     * GET /{userId}/asrama/{asramaUuid}/penghuni
     */
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = DormitoryResident::with(['student', 'room', 'room.wing'])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id);

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->whereHas('student', fn($st) => $st
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('nisn', 'like', "%{$q}%")
                )
            );
        }

        $residents = $query->orderByDesc('is_active')->orderBy('bed_number')->paginate(20)->withQueryString();
        $rooms = DormitoryRoom::where('dormitory_id', $asramaUuid)->where('is_active', true)->orderBy('code')->get();

        $stats = [
            'total'       => DormitoryResident::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->count(),
            'active'      => DormitoryResident::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('is_active', true)->count(),
            'checked_out' => DormitoryResident::where('dormitory_id', $asramaUuid)->where('academic_year_id', $activeYear?->id)->where('is_active', false)->count(),
        ];

        return view('dormitory.residents.index', compact(
            'dormitory', 'residents', 'rooms', 'userId', 'stats', 'activeYear'
        ));
    }

    /**
     * GET /{userId}/asrama/{asramaUuid}/penghuni/tambah
     */
    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();
        $rooms = DormitoryRoom::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->withCount(['residents as current_occupancy' => fn($q) => $q->where('is_active', true)])
            ->orderBy('code')->get();

        return view('dormitory.residents.create', compact('dormitory', 'rooms', 'userId', 'activeYear'));
    }

    /**
     * POST /{userId}/asrama/{asramaUuid}/penghuni
     */
    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'student_id'      => 'required|exists:students,id',
            'room_id'         => 'required|exists:dormitory_rooms,id',
            'bed_number'      => 'nullable|integer|min:1',
            'check_in_date'   => 'required|date',
            'notes'           => 'nullable|string',
        ]);

        // Cek apakah student sudah resident aktif di tahun ajaran ini
        $existing = DormitoryResident::where('student_id', $data['student_id'])
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            return back()->withInput()->withErrors([
                'student_id' => 'Santri ini sudah tercatat sebagai penghuni aktif di asrama lain pada tahun ajaran ini.'
            ]);
        }

        // Cek kapasitas kamar
        $room = DormitoryRoom::find($data['room_id']);
        $currentOccupancy = DormitoryResident::where('room_id', $data['room_id'])
            ->where('is_active', true)
            ->count();

        if ($currentOccupancy >= $room->capacity) {
            return back()->withInput()->withErrors([
                'room_id' => 'Kamar ini sudah penuh (kapasitas: ' . $room->capacity . ' orang).'
            ]);
        }

        $data['dormitory_id'] = $asramaUuid;
        $data['academic_year_id'] = $activeYear->id;
        $data['is_active'] = true;

        DormitoryResident::create($data);

        return redirect()->route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Penghuni berhasil ditambahkan.');
    }

    /**
     * GET /{userId}/asrama/{asramaUuid}/penghuni/{residentUuid}
     */
    public function show(Request $request, string $userId, string $asramaUuid, string $residentUuid)
    {
        $resident = DormitoryResident::with(['student.mahroms', 'room.wing', 'dormitory'])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($residentUuid);

        $activeYear = AcademicYear::where('is_active', true)->first();

        return view('dormitory.residents.show', compact('resident', 'dormitory', 'userId', 'activeYear'));
    }

    /**
     * POST check-out penghuni
     */
    public function checkout(Request $request, string $userId, string $asramaUuid, string $residentUuid)
    {
        $resident = DormitoryResident::where('dormitory_id', $asramaUuid)->findOrFail($residentUuid);

        $data = $request->validate([
            'check_out_date'  => 'required|date',
            'check_out_reason' => 'required|in:lulus,pindah_kamar,keluar,sakit,lainnya',
            'notes'           => 'nullable|string',
        ]);

        $resident->update([
            'is_active'       => false,
            'check_out_date'  => $data['check_out_date'],
            'check_out_reason'=> $data['check_out_reason'],
            'notes'           => $data['notes'] ?? $resident->notes,
        ]);

        return back()->with('success', 'Penghuni berhasil di-check out.');
    }

    /**
     * AJAX: cari student untuk dihuni
     */
    public function findStudent(Request $request)
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) return response()->json([]);

        $students = Student::where('status', 'active')
            ->where(fn($sq) => $sq
                ->where('name', 'like', "%{$q}%")
                ->orWhere('nisn', 'like', "%{$q}%")
                ->orWhere('nik', 'like', "%{$q}%")
            )
            ->limit(20)
            ->get(['id', 'name', 'nisn', 'gender', 'birth_place', 'birth_date']);

        return response()->json(['results' => $students->map(fn($s) => [
            'id'          => $s->id,
            'name'        => $s->name,
            'nisn'        => $s->nisn,
            'gender'      => $s->gender,
            'gender_text' => $s->gender_text,
            'birth_place' => $s->birth_place,
            'birth_date'  => $s->birth_date?->format('d/m/Y'),
        ])]);
    }
}
