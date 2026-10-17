<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentMedicineInventory;
use App\Models\StudentMedicineLog;
use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentMedicineLogController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $query = StudentMedicineLog::with(['student', 'inventory', 'academicYear', 'administeredBy'])
            ->orderByDesc('log_date');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $schoolGender = $request->attributes->get('schoolGender');
        if ($schoolGender) {
            $query->whereHas('student', fn ($s) => $s->where('gender', $schoolGender === 'putra' ? 'L' : 'P'));
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($sq) => $sq
                ->where('purpose', 'like', "%{$q}%")
                ->orWhereHas('student', fn ($st) => $st->where('name', 'like', "%{$q}%"))
            );
        }

        if ($request->filled('study_group_id')) {
            $query->whereHas('student', fn ($st) => $st
                ->whereHas('studyGroups', fn ($sc) => $sc
                    ->where('study_group_id', $request->study_group_id)
                    ->where('is_active', true)
                )
            );
        }

        if ($request->filled('inventory_id')) {
            $query->where('inventory_id', $request->inventory_id);
        }

        if ($request->filled('month') && $request->month !== '') {
            [$year, $month] = explode('-', $request->month);
            $query->whereRaw('YEAR(log_date) = ?', [$year])
                ->whereRaw('MONTH(log_date) = ?', [$month]);
        } elseif ($activeAy) {
            $query->where('academic_year_id', $activeAy->id);
        }

        $logs = $query->paginate(15)->withQueryString();

        $studyGroups = StudyGroup::with('gradeLevel')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $inventories = StudentMedicineInventory::where('school_id', $schoolId)->orderBy('medicine_name')->get();
        $academicYears = AcademicYear::orderByDesc('name')->get();

        return view('health.medicine-logs.index', compact('logs', 'studyGroups', 'inventories', 'academicYears', 'userId'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $schoolGender = $request->attributes->get('schoolGender');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $studentQuery = Student::with('studyGroups.studyGroup.gradeLevel')
            ->where('status', 'active');

        if ($schoolId) {
            $studentQuery->where('school_id', $schoolId);
        }
        if ($schoolGender) {
            $studentQuery->where('gender', $schoolGender === 'putra' ? 'L' : 'P');
        }

        $students = $studentQuery->orderBy('name')->get();
        $groupedStudents = [];
        foreach ($students as $s) {
            $sg = $s->currentClassHistory?->studyGroup;
            $label = $sg ? $sg->full_name : 'Tanpa Kelas';
            if (! isset($groupedStudents[$label])) {
                $groupedStudents[$label] = [];
            }
            $groupedStudents[$label][] = $s;
        }

        $inventories = StudentMedicineInventory::where('school_id', $schoolId)->orderBy('medicine_name')->get();

        return view('health.medicine-logs.create', compact('groupedStudents', 'activeAy', 'inventories', 'userId'));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'inventory_id' => 'required|exists:student_medicine_inventory,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'log_date' => 'required|date',
            'time_given' => 'nullable',
            'quantity_given' => 'required|numeric|min:0',
            'dosage' => 'nullable|string|max:191',
            'purpose' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['school_id'] = $schoolId;
        $validated['administered_by'] = Auth::id();

        DB::transaction(function () use ($validated) {
            $log = StudentMedicineLog::create($validated);

            // Kurangi stok otomatis
            $inventory = StudentMedicineInventory::find($validated['inventory_id']);
            if ($inventory) {
                $inventory->decrement('current_stock', $validated['quantity_given']);
            }
        });

        return redirect()
            ->route('user.uks.medicine-logs.index', ['userId' => $userId])
            ->with('success', 'Pemberian obat berhasil dicatat.');
    }

    public function show(Request $request, string $userId, string $uuid)
    {
        $log = StudentMedicineLog::with(['student', 'inventory', 'academicYear', 'administeredBy'])->findOrFail($uuid);

        return view('health.medicine-logs.show', compact('log', 'userId'));
    }

    public function destroy(string $userId, string $uuid)
    {
        $log = StudentMedicineLog::findOrFail($uuid);
        $log->delete();

        return redirect()
            ->route('user.uks.medicine-logs.index', ['userId' => $userId])
            ->with('success', 'Catatan obat berhasil dihapus.');
    }
}
