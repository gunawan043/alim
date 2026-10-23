<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GtkCrossUnitAssignment;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CrossUnitAssignmentController extends Controller
{
    public function index(Request $request, string $userId)
    {
        abort_unless($this->canManage(), 403, 'Hanya Personalia dan Super Admin yang dapat mengelola penugasan pondok.');

        $query = GtkCrossUnitAssignment::with(['user', 'homeWorkUnit', 'school', 'academicYear', 'subject'])
            ->orderByDesc('created_at');

        if ($request->filled('school_id')) $query->where('school_id', $request->school_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $assignments = $query->paginate(20)->withQueryString();
        $schools = School::where('is_active', true)->orderBy('name')->get();
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $gtkUsers = User::where('is_active', true)
            ->whereHas('gtkWorkUnits')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('personalia.cross-unit-assignments.index', compact(
            'assignments', 'schools', 'workUnits', 'academicYears', 'subjects', 'gtkUsers', 'userId'
        ));
    }

    public function store(Request $request, string $userId)
    {
        abort_unless($this->canManage(), 403, 'Hanya Personalia dan Super Admin yang dapat mengelola penugasan pondok.');

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'home_work_unit_id' => 'required|exists:work_units,id',
            'school_id' => 'required|exists:schools,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'decree_number' => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:500',
        ]);

        $school = School::findOrFail($data['school_id']);
        if ($school->work_unit_id && $school->work_unit_id === $data['home_work_unit_id']) {
            throw ValidationException::withMessages([
                'home_work_unit_id' => 'Work unit asal harus berbeda dari work unit satuan pendidikan tujuan.',
            ]);
        }

        $exists = GtkCrossUnitAssignment::where('user_id', $data['user_id'])
            ->where('school_id', $data['school_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'user_id' => 'GTK ini sudah memiliki penugasan aktif di satuan pendidikan tersebut.',
            ]);
        }

        GtkCrossUnitAssignment::create(array_merge($data, [
            'status' => 'active',
            'created_by' => auth()->id(),
            'approved_by' => auth()->id(),
        ]));

        return back()->with('success', 'Penugasan pondok berhasil dibuat.');
    }

    public function toggle(Request $request, string $userId, string $id)
    {
        abort_unless($this->canManage(), 403, 'Hanya Personalia dan Super Admin yang dapat mengelola penugasan pondok.');

        $assignment = GtkCrossUnitAssignment::findOrFail($id);
        $assignment->update([
            'status' => $assignment->status === 'active' ? 'inactive' : 'active',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Status penugasan berhasil diperbarui.');
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->hasRole('Personalia')
            || $user->hasRole('Humas Personalia')
            || $user->hasRole('Administrator')
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());
    }
}
