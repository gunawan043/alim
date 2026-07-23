<?php

namespace App\Http\Controllers;

use App\Http\Requests\Dormitory\RecordReturnRequest;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Services\Boarding\LeaveWorkflowService;
use Illuminate\Http\Request;

class DormitoryReturnController extends Controller
{
    public function __construct(
        private readonly LeaveWorkflowService $leave,
    ) {}

    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();

        $filter = $request->input('filter', 'pending'); // pending | today | all

        $query = DormitoryPermit::with(['student', 'room', 'mahrom', 'approvedBy'])
            ->where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id);

        if ($filter === 'pending') {
            // Izin yang sudah disetujui & belum dicatat kepulangannya,
            // termasuk yang terlambat (overdue).
            $query->whereIn('status', ['approved', 'overdue'])
                ->whereNull('actual_return_datetime');
        } elseif ($filter === 'today') {
            // Santri yang dicatat kembali hari ini.
            $query->where('status', 'returned')
                ->whereDate('actual_return_datetime', today());
        } elseif ($filter === 'overdue') {
            $query->where('status', 'overdue');
        } else {
            // 'all' → tampilkan semua izin kepulangan (status apapun) tahun ini
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->whereHas('student', fn ($st) => $st->where('name', 'like', "%{$q}%"));
        }

        $permits = $query->orderByRaw('COALESCE(expected_return_datetime, departure_datetime) ASC')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'pending' => DormitoryPermit::where('dormitory_id', $asramaUuid)
                ->where('academic_year_id', $activeYear?->id)
                ->whereIn('status', ['approved', 'overdue'])
                ->whereNull('actual_return_datetime')
                ->count(),
            'today_returned' => DormitoryPermit::where('dormitory_id', $asramaUuid)
                ->where('academic_year_id', $activeYear?->id)
                ->where('status', 'returned')
                ->whereDate('actual_return_datetime', today())
                ->count(),
            'overdue' => DormitoryPermit::where('dormitory_id', $asramaUuid)
                ->where('academic_year_id', $activeYear?->id)
                ->where('status', 'overdue')
                ->count(),
        ];

        return view('dormitory.returns.index', compact(
            'dormitory', 'permits', 'userId', 'stats', 'activeYear', 'filter'
        ));
    }

    public function record(RecordReturnRequest $request, string $userId, string $asramaUuid, string $permitUuid)
    {
        $data = $request->validated();

        $this->leave->recordReturn(
            permitId: $permitUuid,
            dormitoryId: $asramaUuid,
            actualReturnDatetime: $data['actual_return_datetime'],
        );

        return back()->with('success', 'Kepulangan ' . ($data['student_name'] ?? 'santri') . ' berhasil dicatat.');
    }

    /**
     * Riwayat izin kepulangan untuk satu siswa.
     */
    public function history(Request $request, string $userId, string $asramaUuid, string $studentUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $student = \App\Models\Student::with('mahroms')->findOrFail($studentUuid);

        $permits = DormitoryPermit::with(['room', 'approvedBy'])
            ->where('student_id', $studentUuid)
            ->where('dormitory_id', $asramaUuid)
            ->orderByDesc('departure_datetime')
            ->limit(50)
            ->get();

        // Statistik per siswa
        $stats = [
            'total_permits' => $permits->count(),
            'return_on_time' => $permits->where('status', 'returned')->filter(function ($p) {
                return $p->actual_return_datetime && $p->expected_return_datetime
                    && $p->actual_return_datetime->lte($p->expected_return_datetime);
            })->count(),
            'late_returns' => $permits->where('status', 'overdue')->count(),
            'pending' => $permits->where('status', 'pending')->count(),
        ];
        $stats['on_time_pct'] = $stats['total_permits'] > 0
            ? round(($stats['return_on_time'] / $stats['total_permits']) * 100)
            : 100;

        return view('dormitory.returns.history', compact('dormitory', 'student', 'permits', 'stats', 'userId'));
    }

    /**
     * Dashboard statistik kepulangan per asrama (bulan ini).
     */
    public function statistics(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = Dormitory::findOrFail($asramaUuid);
        $activeYear = AcademicYear::where('is_active', true)->first();
        $monthStart = now()->startOfMonth();

        $base = DormitoryPermit::where('dormitory_id', $asramaUuid)
            ->where('academic_year_id', $activeYear?->id)
            ->where('departure_datetime', '>=', $monthStart);

        $stats = [
            'total_this_month' => (clone $base)->count(),
            'by_type' => (clone $base)->selectRaw('permit_type, COUNT(*) as total')->groupBy('permit_type')->pluck('total', 'permit_type'),
            'overdue_count' => (clone $base)->where('status', 'overdue')->count(),
            'returned_count' => (clone $base)->where('status', 'returned')->count(),
            'pending_count' => (clone $base)->where('status', 'pending')->count(),
            'top_students' => (clone $base)
                ->selectRaw('student_id, COUNT(*) as total')
                ->groupBy('student_id')
                ->orderByDesc('total')
                ->limit(5)
                ->with('student')
                ->get(),
        ];

        return view('dormitory.returns.statistics', compact('dormitory', 'stats', 'userId'));
    }
}
