<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\StoreAttendanceRequest;
use App\Http\Requests\Dormitory\VerifyAttendanceRequest;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryAttendance;
use App\Models\DormitoryAttendanceRecap;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DormitoryAttendanceController extends Controller
{
    /**
     * GET /{userId}/asrama/{asramaUuid}/absensi
     */
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $query = DormitoryAttendance::with(['student', 'room', 'recordedBy', 'verifiedBy'])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id);

        if ($request->filled('attendance_date')) {
            $query->where('attendance_date', $request->attendance_date);
        } else {
            $query->where('attendance_date', now()->toDateString());
        }

        if ($request->filled('session')) {
            $query->where('session', $request->session);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        $records = $query->orderBy('session')->paginate(25)->withQueryString();
        $rooms = DormitoryRoom::where('dormitory_id', $asramaUuid)->where('is_active', true)->orderBy('code')->get();

        $selectedDate = $request->filled('attendance_date') ? $request->attendance_date : now()->toDateString();
        $selectedSession = $request->filled('session') ? $request->session : null;
        $statsSession = $selectedSession ?: 'malam';

        $stats = DormitoryAttendance::selectRaw('status, COUNT(*) as cnt')
            ->where('dormitory_id', $asramaUuid)
            ->where('attendance_date', $selectedDate)
            ->where('session', $statsSession)
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $stats = [
            'hadir' => $stats['hadir'] ?? 0,
            'izin' => $stats['izin'] ?? 0,
            'sakit' => $stats['sakit'] ?? 0,
            'alpa' => $stats['alpa'] ?? 0,
            'pulang' => $stats['pulang'] ?? 0,
        ];

        return view('dormitory.attendance.index', [
            'dormitory' => $dormitory,
            'rooms' => $rooms,
            'userId' => $userId,
            'stats' => $stats,
            'activeYear' => $activeYear,
            'selectedDate' => $selectedDate,
            'selectedSession' => $selectedSession,
            'attendanceRecords' => $records,
        ]);
    }

    /**
     * GET /{userId}/asrama/{asramaUuid}/absensi/catat
     */
    public function create(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $date = $request->filled('attendance_date') ? $request->attendance_date : now()->toDateString();
        $session = $request->filled('session') ? $request->session : 'malam';

        // Ambil semua resident aktif
        $residents = DormitoryResident::with(['student', 'room'])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear->id)
            ->where('is_active', true)
            ->orderBy('room_id')
            ->get();

        // Group residents by room for the view
        $residentsByRoom = $residents->groupBy(fn ($r) => $r->room?->name ?? 'Tanpa Kamar');

        // Load existing attendance for this date/session
        $existing = DormitoryAttendance::where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear->id)
            ->where('attendance_date', $date)
            ->where('session', $session)
            ->pluck('status', 'student_id')
            ->toArray();

        $existingCount = count($existing);

        $rooms = DormitoryRoom::where('dormitory_id', $asramaUuid)->where('is_active', true)->orderBy('code')->get();

        return view('dormitory.attendance.create', [
            'dormitory' => $dormitory,
            'rooms' => $rooms,
            'userId' => $userId,
            'activeYear' => $activeYear,
            'selectedDate' => $date,
            'selectedSession' => $session,
            'residentsByRoom' => $residentsByRoom,
            'existing' => $existing,
            'existingCount' => $existingCount,
        ]);
    }

    /**
     * POST batch absensi
     */
    public function store(StoreAttendanceRequest $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $data = $request->validated();
        $recordedBy = auth()->id();
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($data, $asramaUuid, $activeYear, $recordedBy, &$created, &$updated) {
            foreach ($data['attendances'] as $item) {
                $existing = DormitoryAttendance::where('student_id', $item['student_id'])
                    ->where('room_id', $item['room_id'])
                    ->where('attendance_date', $data['attendance_date'])
                    ->where('session', $data['session'])
                    ->first();

                $fields = [
                    'dormitory_id' => $asramaUuid,
                    'academic_year_id' => $activeYear->id,
                    'recorded_by' => $recordedBy,
                    'status' => $item['status'],
                    'notes' => $item['notes'] ?? null,
                ];

                if ($existing) {
                    $existing->update($fields);
                    $updated++;
                } else {
                    $fields['student_id'] = $item['student_id'];
                    DormitoryAttendance::create($fields);
                    $created++;
                }
            }
        });

        $msg = "Absensi berhasil disimpan ({$created} baru, {$updated} diperbarui).";

        return redirect()->route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with('success', $msg);
    }

    /**
     * POST verifikasi absensi
     */
    public function verify(VerifyAttendanceRequest $request, string $userId, string $asramaUuid)
    {
        $record = DormitoryAttendance::where('dormitory_id', $asramaUuid)->findOrFail($request->record_id);
        $record->update([
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Absensi berhasil diverifikasi.');
    }

    /**
     * GET rekap bulanan
     */
    public function recap(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $month = $request->filled('month') ? (int) $request->month : now()->month;
        $year = $request->filled('year') ? (int) $request->year : now()->year;

        $recaps = DormitoryAttendanceRecap::with(['student', 'room'])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->where('recap_month', $month)
            ->where('recap_year', $year)
            ->orderBy('student_id')
            ->paginate(20);

        return view('dormitory.attendance.recap', compact(
            'dormitory', 'recaps', 'userId', 'activeYear', 'month', 'year'
        ));
    }
}
