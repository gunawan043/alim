<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\Uks\UkShiftAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * SchedulingController — Manage UKS shift assignments.
 *
 * Implements comprehensive shift scheduling with 24/7 coverage:
 * - Morning (Pagi: 06:00–12:00), Afternoon (Siang: 12:00–18:00), Evening (Malam: 18:00–06:00)
 * - Full Day shifts
 * - Overlap checking for multiple staff on same shift/date
 * - Export to CSV report
 */
class SchedulingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role.access:UKS']);
    }

    /**
     * Show the shift schedule dashboard.
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $schoolId = $request->attributes->get('schoolContextId');
        $viewDate = $request->filled('date') ? \Carbon\Carbon::parse($request->date) : \Carbon\Carbon::today();

        $query = UkShiftAssignment::where('shift_date', $viewDate->format('Y-m-d'))
            ->with('assignedTo')
            ->orderBy('start_time', 'asc');

        // Filter by school if provided (only if user has school context)
        if ($schoolId) {
            $query->whereHas('assignedTo', fn ($qt) => $qt->where('school_id', $schoolId));
        }

        $assignments = $query->get();

        // Build a grouped view by time
        $scheduleByTime = [
            'pagi' => [], // 06:00-12:00
            'siang' => [], // 12:00-18:00
            'malam' => [], // 18:00-06:00 (next day)
            'full' => [], // Full day
        ];

        foreach ($assignments as $assign) {
            $type = $assign->shift_type;
            if (array_key_exists($type, $scheduleByTime)) {
                $scheduleByTime[$type][] = $assign;
            }
        }

        // Fetch eligible staff: those with UKS head role
        $uksStaff = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['uks_kepala', 'uks_admin_putra', 'uks_admin_putri']))
            ->orderBy('name')
            ->get();

        return view('uks.scheduling.index', compact(
            'scheduleByTime', 'viewDate', 'assignments', 'uksStaff'
        ));
    }

    /**
     * Display detailed weekly schedule.
     */
    public function show(string $uuid)
    {
        $assignment = UkShiftAssignment::with(['assignedTo', 'createdBy'])->findOrFail($uuid);

        return view('uks.scheduling.view', compact('assignment'));
    }

    /**
     * Store a new shift assignment.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
            'shift_date' => 'required|date|after_or_equal:tomorrow',
            'shift_type' => 'required|in:pagi,siang,malam,full_day',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check for overlapping shifts
        $existing = UkShiftAssignment::where('assigned_to_id', $request->assigned_to)
            ->where('shift_date', $request->shift_date)
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            return back()->with('error', 'Anggota ini sudah dijadwalkan pada tanggal tersebut.');
        }

        $data = [
            'assigned_to_id' => $request->assigned_to,
            'created_by_id' => Auth::id(),
            'shift_date' => $request->shift_date,
            'shift_type' => $request->shift_type,
            'start_time' => $this->resolveStartTime($request->shift_type, $request->start_time),
            'end_time' => $this->resolveEndTime($request->shift_type, $request->end_time),
            'notes' => $request->notes,
        ];

        UkShiftAssignment::create($data);

        return redirect()->route('user.uks.scheduling.index')->with('success', 'Shift jadwal berhasil disimpan.');
    }

    /**
     * Update an existing shift assignment.
     */
    public function update(Request $request, string $uuid)
    {
        $assignment = UkShiftAssignment::findOrFail($uuid);

        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
            'shift_date' => 'required|date|after_or_equal:tomorrow',
            'shift_type' => 'required|in:pagi,siang,malam,full_day',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check for duplicate (except the current record)
        $duplicate = UkShiftAssignment::where('assigned_to_id', $request->assigned_to)
            ->where('shift_date', $request->shift_date)
            ->where('id', '!=', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if ($duplicate) {
            return back()->with('error', 'Anggota ini sudah dijadwalkan pada tanggal tersebut.');
        }

        $assignment->update([
            'assigned_to_id' => $request->assigned_to,
            'shift_date' => $request->shift_date,
            'shift_type' => $request->shift_type,
            'start_time' => $this->resolveStartTime($request->shift_type, $request->start_time),
            'end_time' => $this->resolveEndTime($request->shift_type, $request->end_time),
            'notes' => $request->notes,
        ]);

        return redirect()->route('user.uks.scheduling.index')->with('success', 'Shift jadwal berhasil diperbarui.');
    }

    /**
     * Delete a shift assignment.
     */
    public function destroy(string $uuid)
    {
        $assignment = UkShiftAssignment::findOrFail($uuid);
        $assignment->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['message' => 'Shift jadwal berhasil dihapus.']);
        }

        return redirect()->route('user.uks.scheduling.index')->with('success', 'Shift jadwal berhasil dihapus.');
    }

    /**
     * Export schedule to CSV.
     */
    public function export(Request $request)
    {
        $fromDate = $request->filled('from') ? \Carbon\Carbon::parse($request->from)->startOfDay() : \Carbon\Carbon::create(2024, 1, 1)->startOfDay();
        $toDate = $request->filled('to') ? \Carbon\Carbon::parse($request->to)->endOfDay() : \Carbon\Carbon::now()->endOfDay();

        $assignments = UkShiftAssignment::where('shift_date', '>=', $fromDate->format('Y-m-d'))
            ->where('shift_date', '<=', $toDate->format('Y-m-d'))
            ->whereNull('deleted_at')
            ->with('assignedTo')
            ->orderBy('shift_date')
            ->orderBy('shift_type')
            ->get();

        $headers = [
            'ID', 'Nama Anggota', 'Tanggal', 'Tipe Shift', 'Mulai', 'Selesai', 'Catatan', 'Dibuat Oleh', 'Tanggal Dibuat',
        ];

        $filename = 'uk-shift-schedule-'.now()->format('Ymd_His').'.csv';

        $callback = function () use ($assignments, $headers) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);

            foreach ($assignments as $assign) {
                $row = [
                    $assign->id,
                    $assign->assignedTo?->name ?? '',
                    date('d/m/Y', strtotime($assign->shift_date)),
                    ucfirst($assign->shift_type),
                    $assign->start_time ?: '',
                    $assign->end_time ?: '',
                    $assign->notes ?: '',
                    $assign->createdBy?->name ?? '',
                    $assign->created_at,
                ];
                fputcsv($output, $row);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Resolve default start time based on shift type.
     */
    private function resolveStartTime(string $shiftType, ?string $manualTime): ?string
    {
        if ($manualTime) {
            return $manualTime;
        }
        $defaults = ['pagi' => '06:00:00', 'siang' => '12:00:00', 'malam' => '18:00:00', 'full_day' => '06:00:00'];

        return $defaults[$shiftType] ?? null;
    }

    /**
     * Resolve default end time based on shift type.
     */
    private function resolveEndTime(string $shiftType, ?string $manualTime): ?string
    {
        if ($manualTime) {
            return $manualTime;
        }
        $defaults = [
            'pagi' => '12:00:00',
            'siang' => '18:00:00',
            'malam' => '06:00:00', // overnight
            'full_day' => '18:00:00',
        ];

        return $defaults[$shiftType] ?? null;
    }
}
