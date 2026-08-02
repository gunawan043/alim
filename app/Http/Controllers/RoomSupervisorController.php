<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\StoreRoomSupervisorRequest;
use App\Http\Requests\Dormitory\UpdateRoomSupervisorRequest;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\RoomSupervisor;
use App\Models\User;
use App\Services\Asrama\RoomSupervisorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomSupervisorController extends Controller
{
    public function __construct(private readonly RoomSupervisorService $service) {}

    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYearId = AcademicYear::where('is_active', true)->value('id');

        $query = RoomSupervisor::with(['user', 'room.wing', 'academicYear', 'decree'])
            ->where('dormitory_id', $asramaUuid);

        if ($activeYearId) {
            $query->where('academic_year_id', $activeYearId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'active');
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->whereHas('user', fn ($u) => $u
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                )->orWhereHas('room', fn ($r) => $r
                    ->where('code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                );
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $supervisors = $query->orderByDesc('start_date')->paginate(15)->withQueryString();

        $stats = [
            'total_active' => RoomSupervisor::where('dormitory_id', $asramaUuid)
                ->where('status', 'active')
                ->when($activeYearId, fn ($q) => $q->where('academic_year_id', $activeYearId))
                ->count(),
            'total_rooms' => DormitoryRoom::where('dormitory_id', $asramaUuid)->count(),
            'rooms_with_supervisor' => DormitoryRoom::where('dormitory_id', $asramaUuid)
                ->whereHas('supervisors', fn ($q) => $q
                    ->where('status', 'active')
                    ->when($activeYearId, fn ($qq) => $qq->where('academic_year_id', $activeYearId))
                )
                ->count(),
        ];

        return view('dormitory.room-supervisors.index', compact(
            'dormitory', 'supervisors', 'userId', 'stats'
        ));
    }

    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $rooms = DormitoryRoom::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $gtkUsers = $this->gtkCandidates($dormitory);

        $academicYears = AcademicYear::orderByDesc('start_date')->limit(5)->get();

        return view('dormitory.room-supervisors.create', compact(
            'dormitory', 'rooms', 'gtkUsers', 'academicYears', 'userId'
        ));
    }

    public function store(StoreRoomSupervisorRequest $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $data = $request->validated();

        try {
            $this->service->assign(
                userId: $data['user_id'],
                roomId: $data['room_id'],
                academicYearId: $data['academic_year_id'] ?? null,
                startDate: $data['start_date'] ?? null,
                decreeId: $data['decree_id'] ?? null,
                notes: $data['notes'] ?? null,
                actorId: Auth::id(),
            );
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('user.asrama.room-supervisors.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Wali Kamar berhasil ditetapkan.');
    }

    public function show(string $userId, string $asramaUuid, string $supervisorUuid)
    {
        $supervisor = RoomSupervisor::with(['user', 'room.wing', 'academicYear', 'decree', 'dormitory'])
            ->where('dormitory_id', $asramaUuid)
            ->findOrFail($supervisorUuid);

        $room = $supervisor->room;
        $dormitory = $supervisor->dormitory;

        $residents = collect();
        $stats = [
            'total_residents' => 0,
            'on_permit' => 0,
            'in_dormitory' => 0,
        ];

        if ($room) {
            $residents = DormitoryResident::with('student')
                ->where('room_id', $room->id)
                ->where('is_active', true)
                ->get();

            $studentIds = $residents->pluck('student_id')->filter()->all();
            $onPermitIds = DormitoryPermit::whereIn('student_id', $studentIds)
                ->whereIn('status', ['approved', 'overdue'])
                ->whereNull('actual_return_datetime')
                ->pluck('student_id')
                ->all();

            $stats['total_residents'] = $residents->count();
            $stats['on_permit'] = count($onPermitIds);
            $stats['in_dormitory'] = $stats['total_residents'] - $stats['on_permit'];
        }

        return view('dormitory.room-supervisors.show', compact(
            'supervisor', 'room', 'dormitory', 'residents', 'stats', 'userId'
        ));
    }

    public function edit(string $userId, string $asramaUuid, string $supervisorUuid)
    {
        $supervisor = RoomSupervisor::where('dormitory_id', $asramaUuid)
            ->findOrFail($supervisorUuid);
        $dormitory = Dormitory::findOrFail($asramaUuid);

        $rooms = DormitoryRoom::where('dormitory_id', $asramaUuid)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        $gtkUsers = $this->gtkCandidates($dormitory);
        $academicYears = AcademicYear::orderByDesc('start_date')->limit(5)->get();

        return view('dormitory.room-supervisors.edit', compact(
            'supervisor', 'dormitory', 'rooms', 'gtkUsers', 'academicYears', 'userId'
        ));
    }

    public function update(UpdateRoomSupervisorRequest $request, string $userId, string $asramaUuid, string $supervisorUuid)
    {
        $supervisor = RoomSupervisor::where('dormitory_id', $asramaUuid)
            ->findOrFail($supervisorUuid);

        try {
            $this->service->update($supervisor, $request->validated(), Auth::id());
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('user.asrama.room-supervisors.show', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid,
                'supervisorUuid' => $supervisor->id,
            ])
            ->with('success', 'Data Wali Kamar berhasil diperbarui.');
    }

    public function destroy(string $userId, string $asramaUuid, string $supervisorUuid)
    {
        $supervisor = RoomSupervisor::where('dormitory_id', $asramaUuid)
            ->findOrFail($supervisorUuid);

        $supervisor->delete();

        return redirect()
            ->route('user.asrama.room-supervisors.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', 'Wali Kamar berhasil dihapus.');
    }

    public function endAssignmentAction(string $userId, string $asramaUuid, string $supervisorUuid)
    {
        $supervisor = RoomSupervisor::where('dormitory_id', $asramaUuid)
            ->findOrFail($supervisorUuid);

        $this->service->endAssignment($supervisor, now()->toDateString(), 'Ditandai selesai oleh admin', Auth::id());

        return redirect()
            ->route('user.asrama.room-supervisors.show', [
                'userId' => $userId,
                'asramaUuid' => $asramaUuid,
                'supervisorUuid' => $supervisor->id,
            ])
            ->with('success', 'Penugasan Wali Kamar telah diakhiri.');
    }

    public function supervisorProfile(Request $request, string $userId, string $supervisorUserUuid)
    {
        $supervisorUser = User::with([
            'roomSupervisors.room.wing',
            'roomSupervisors.dormitory',
            'roomSupervisors.academicYear',
            'employment',
        ])->findOrFail($supervisorUserUuid);

        $activeYearId = AcademicYear::where('is_active', true)->value('id');

        $activeAssignments = $supervisorUser->roomSupervisors()
            ->with(['room.wing', 'dormitory', 'academicYear'])
            ->where('status', 'active')
            ->when($activeYearId, fn ($q) => $q->where('academic_year_id', $activeYearId))
            ->get();

        $roomsGrouped = $activeAssignments->groupBy('dormitory_id');
        $supervisedRoomIds = $activeAssignments->pluck('room_id')->all();

        $residentsByRoom = DormitoryResident::with('student')
            ->whereIn('room_id', $supervisedRoomIds)
            ->where('is_active', true)
            ->get()
            ->groupBy('room_id');

        $stats = [
            'rooms_count' => $activeAssignments->count(),
            'students_count' => $residentsByRoom->flatten()->count(),
            'dormitories_count' => $roomsGrouped->count(),
        ];

        return view('dormitory.room-supervisors.profile', compact(
            'supervisorUser', 'activeAssignments', 'residentsByRoom', 'stats', 'userId'
        ));
    }

    private function gtkCandidates(?Dormitory $dormitory = null)
    {
        return User::query()
            ->when($dormitory?->work_unit_id, fn ($q) => $q->whereHas('workUnits', fn ($qq) => $qq->where('gtk_work_unit.work_unit_id', $dormitory->work_unit_id)))
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email']);
    }
}