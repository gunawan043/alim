<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\DormitoryRoomMove;
use App\Models\DormitoryResident;
use App\Models\AcademicYear;
use App\Models\DormitoryActivityLog;
use Illuminate\Http\Request;

class DormitoryRoomMoveController extends Controller
{
    /**
     * GET /{userId}/asrama/{asramaUuid}/mutasi-kamar
     */
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = DormitoryRoomMove::with(['student', 'fromRoom', 'toRoom', 'approvedBy'])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id);

        if ($request->filled('status')) {
            $query->where('approval_status', $request->status);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($sq) => $sq
                ->whereHas('student', fn($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('move_date', [$request->start_date, $request->end_date]);
        }

        $roomMoves = $query->orderByDesc('move_date')->paginate(20)->withQueryString();

        $stats = [
            'pending'   => DormitoryRoomMove::where('dormitory_id', $asramaUuid)->where('approval_status', 'pending')->count(),
            'approved'  => DormitoryRoomMove::where('dormitory_id', $asramaUuid)->where('approval_status', 'approved')->count(),
            'rejected'  => DormitoryRoomMove::where('dormitory_id', $asramaUuid)->where('approval_status', 'rejected')->count(),
        ];

        return view('dormitory.room-moves.index', [
            'dormitory' => $dormitory,
            'roomMoves' => $roomMoves,
            'userId'    => $userId,
            'stats'     => $stats,
            'activeYear'=> $activeYear,
        ]);
    }

    /**
     * GET /{userId}/asrama/{asramaUuid}/mutasi-kamar/aju
     */
    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $residents = DormitoryResident::with(['student', 'room'])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->where('is_active', true)
            ->orderBy('room_id')
            ->get();

        $rooms = DormitoryRoom::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('dormitory.room-moves.create', compact('dormitory', 'residents', 'rooms', 'userId', 'activeYear'));
    }

    /**
     * POST /{userId}/asrama/{asramaUuid}/mutasi-kamar
     */
    public function store(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'student_id'       => 'required|exists:students,id',
            'from_room_id'    => 'required|exists:dormitory_rooms,id',
            'to_room_id'      => 'required|exists:dormitory_rooms,id|different:from_room_id',
            'move_date'       => 'required|date',
            'reason'          => 'nullable|string',
            'move_type'      => 'nullable|in:reguler,disciplinary,medical,upgrade,other',
            'notes'          => 'nullable|string',
        ]);

        // Cek apakah student adalah resident aktif
        $resident = DormitoryResident::where('student_id', $data['student_id'])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true)
            ->first();

        if (!$resident) {
            return back()->withInput()->withErrors(['student_id' => 'Santri ini bukan penghuni aktif asrama.']);
        }

        // Cek kamar tujuan masih ada kapasitas
        $toRoom = DormitoryRoom::find($data['to_room_id']);
        $currentOccupancy = DormitoryResident::where('room_id', $data['to_room_id'])
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true)
            ->count();

        if ($currentOccupancy >= $toRoom->capacity) {
            return back()->withInput()->withErrors(['to_room_id' => 'Kamar tujuan sudah penuh (kapasitas: ' . $toRoom->capacity . ').']);
        }

        // Jadikan pending terlebih dahulu (auto-approve jika dari kepala asrama)
        $data['dormitory_id'] = $asramaUuid;
        $data['academic_year_id'] = $activeYear->id;
        $data['approval_status'] = 'pending';
        $data['move_type'] = $data['move_type'] ?? 'reguler';

        $roomMove = DormitoryRoomMove::create($data);

        return redirect()->route('user.asrama.room-moves.show', [
            'userId' => $userId, 'asramaUuid' => $asramaUuid, 'moveUuid' => $roomMove->id
        ])->with('success', 'Permintaan mutasi kamar berhasil diajukan.');
    }

    /**
     * GET /{userId}/asrama/{asramaUuid}/mutasi-kamar/{moveUuid}
     */
    public function show(Request $request, string $userId, string $asramaUuid, string $moveUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $move = DormitoryRoomMove::with(['student', 'fromRoom', 'toRoom', 'approvedBy', 'dormitory'])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($moveUuid);

        return view('dormitory.room-moves.show', compact('dormitory', 'move', 'userId'));
    }

    /**
     * POST approve mutasi kamar
     */
    public function approve(Request $request, string $userId, string $asramaUuid, string $moveUuid)
    {
        $move = DormitoryRoomMove::where('dormitory_id', $asramaUuid)->findOrFail($moveUuid);
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $move->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        // Update resident ke kamar baru
        DormitoryResident::where('student_id', $move->student_id)
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true)
            ->update(['room_id' => $move->to_room_id]);

        return back()->with('success', 'Mutasi kamar disetujui dan kamar minimalis telah diperbarui.');
    }

    /**
     * POST reject mutasi kamar
     */
    public function reject(Request $request, string $userId, string $asramaUuid, string $moveUuid)
    {
        $move = DormitoryRoomMove::where('dormitory_id', $asramaUuid)->findOrFail($moveUuid);

        $move->update([
            'approval_status' => 'rejected',
            'approved_by' => auth()->id(),
            'notes' => $request->notes ?? $move->notes,
        ]);

        return back()->with('success', 'Mutasi kamar ditolak.');
    }
}
