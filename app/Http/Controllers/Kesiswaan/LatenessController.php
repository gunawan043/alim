<?php

namespace App\Http\Controllers\Kesiswaan;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AdminPresensiHarian;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Catat & rekap keterlambatan santri.
 *
 * Menulis ke `admin_presensi_harian` (tabel yang sama dengan Absensi Harian)
 * dengan status `terlambat`, sehingga rekap absensi otomatis ikut terisi.
 * Kolom `source` menandai bahwa data berasal dari pos kesiswaan.
 */
class LatenessController extends Controller
{
    private const SOURCE = 'kesiswaan';

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $activeYear = AcademicYear::where('is_active', true)->first();

        $date = $request->input('date') ?: now()->toDateString();
        $date = Carbon::parse($date)->toDateString();
        $search = trim((string) $request->input('search'));

        $records = AdminPresensiHarian::query()
            ->with(['student:id,name,nis,nisn', 'studyGroup:id,name'])
            ->where('status', 'terlambat')
            ->whereDate('attendance_date', $date)
            ->when($schoolId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('school_id', $schoolId)))
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('student', function ($s) use ($search) {
                    $s->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at')
            ->paginate(20)
            ->withQueryString();

        // Kandidat santri untuk form: dibatasi 50 agar sidebar select tetap ringan.
        $candidates = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'nis', 'nisn']);

        $stats = [
            'today' => AdminPresensiHarian::where('status', 'terlambat')
                ->whereDate('attendance_date', $date)
                ->when($schoolId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('school_id', $schoolId)))
                ->count(),
            'week' => AdminPresensiHarian::where('status', 'terlambat')
                ->whereBetween('attendance_date', [Carbon::parse($date)->startOfWeek()->toDateString(), Carbon::parse($date)->endOfWeek()->toDateString()])
                ->when($schoolId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('school_id', $schoolId)))
                ->count(),
            'month' => AdminPresensiHarian::where('status', 'terlambat')
                ->whereBetween('attendance_date', [Carbon::parse($date)->startOfMonth()->toDateString(), Carbon::parse($date)->endOfMonth()->toDateString()])
                ->when($schoolId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('school_id', $schoolId)))
                ->count(),
        ];

        // Rekap 30 hari terakhir per santri.
        $recap = AdminPresensiHarian::query()
            ->select('student_id', DB::raw('count(*) as total'))
            ->where('status', 'terlambat')
            ->whereBetween('attendance_date', [Carbon::parse($date)->subDays(29)->toDateString(), $date])
            ->when($schoolId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('school_id', $schoolId)))
            ->groupBy('student_id')
            ->orderByDesc('total')
            ->limit(10)
            ->with('student:id,name,nis')
            ->get();

        return view('kesiswaan.lateness.index', [
            'userId' => $userId,
            'date' => $date,
            'search' => $search,
            'records' => $records,
            'candidates' => $candidates,
            'stats' => $stats,
            'recap' => $recap,
            'activeYear' => $activeYear,
        ]);
    }

    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'attendance_date' => 'required|date',
            'arrival_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:255',
        ]);

        $activeYear = AcademicYear::where('is_active', true)->first();

        if (! $activeYear) {
            return back()->with('error', 'Tidak ada tahun ajaran aktif. Aktifkan tahun ajaran terlebih dahulu.');
        }

        $student = Student::with('currentClassHistory.studyGroup')->findOrFail($validated['student_id']);
        $group = $student->currentClassHistory?->studyGroup;

        if (! $group) {
            return back()->with('error', 'Santri '.$student->name.' belum memiliki rombel aktif.');
        }

        $date = Carbon::parse($validated['attendance_date']);

        AdminPresensiHarian::updateOrCreate(
            [
                'study_group_id' => $group->id,
                'academic_year_id' => $activeYear->id,
                'semester' => $date->month >= 7 ? 'ganjil' : 'genap',
                'attendance_date' => $date->toDateString(),
                'student_id' => $student->id,
            ],
            [
                'status' => 'terlambat',
                'notes' => $validated['notes'] ?? ('Datang pukul '.$validated['arrival_time']),
                'source' => self::SOURCE,
                'source_ref' => 'lateness',
            ]
        );

        return redirect()
            ->route('user.kesiswaan.lateness.index', ['userId' => $userId, 'date' => $date->toDateString()])
            ->with('success', 'Keterlambatan '.$student->name.' tercatat.');
    }

    public function destroy(Request $request, string $userId, string $recordId)
    {
        $record = AdminPresensiHarian::where('status', 'terlambat')->findOrFail($recordId);
        $record->delete();

        return redirect()
            ->route('user.kesiswaan.lateness.index', ['userId' => $userId])
            ->with('success', 'Catatan keterlambatan dihapus.');
    }
}
