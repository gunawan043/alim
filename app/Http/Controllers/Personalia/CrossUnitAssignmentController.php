<?php

namespace App\Http\Controllers\Personalia;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GtkCrossUnitAssignment;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\NotificationUniversalService;
use Illuminate\Support\Facades\Log;
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

        $stats = [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'inactive' => (clone $query)->where('status', 'inactive')->count(),
            'schools' => (clone $query)->where('status', 'active')->distinct()->count('school_id'),
        ];

        $assignments = $query->paginate(20)->withQueryString();
        $schools = School::where('is_active', true)->orderBy('name')->get();
        $workUnits = WorkUnit::where('is_active', true)->orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $subjects = Subject::where('is_active', true)->orderBy('name')->get();
        $activeAcademicYearId = AcademicYear::active()->value('id');
        $gtkUsers = User::with(['gtkWorkUnits:id,user_id,work_unit_id,is_primary'])
            ->where('is_active', true)
            ->whereHas('gtkWorkUnits')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('personalia.cross-unit-assignments.index', compact(
            'assignments', 'schools', 'workUnits', 'academicYears', 'subjects', 'gtkUsers', 'userId', 'stats', 'activeAcademicYearId'
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

        $assignment = GtkCrossUnitAssignment::create(array_merge($data, [
            'status' => 'active',
            'created_by' => auth()->id(),
            'approved_by' => auth()->id(),
        ]));

        $this->notifyAssignment($assignment, 'created');

        return back()->with('success', 'Penugasan pondok berhasil dibuat.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        abort_unless($this->canManage(), 403, 'Hanya Personalia dan Super Admin yang dapat mengelola penugasan pondok.');

        $assignment = GtkCrossUnitAssignment::findOrFail($id);
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

        $assignment->update($data);
        $this->notifyAssignment($assignment->fresh(), 'updated');

        return back()->with('success', 'Penugasan pondok berhasil diperbarui.');
    }

    public function toggle(Request $request, string $userId, string $id)
    {
        abort_unless($this->canManage(), 403, 'Hanya Personalia dan Super Admin yang dapat mengelola penugasan pondok.');

        $assignment = GtkCrossUnitAssignment::findOrFail($id);
        $assignment->update([
            'status' => $assignment->status === 'active' ? 'inactive' : 'active',
            'approved_by' => auth()->id(),
        ]);
        $this->notifyAssignment($assignment->fresh(), $assignment->status === 'active' ? 'activated' : 'revoked');

        return back()->with('success', 'Status penugasan berhasil diperbarui.');
    }

    private function notifyAssignment(GtkCrossUnitAssignment $assignment, string $event): void
    {
        try {
            $assignment->loadMissing(['user:id,name', 'school:id,name,work_unit_id', 'homeWorkUnit:id,name']);
            $schoolName = $assignment->school?->name ?? 'satuan pendidikan';
            $homeName = $assignment->homeWorkUnit?->name ?? 'work unit asal';
            $title = match ($event) {
                'revoked' => 'Penugasan Pondok Dicabut',
                'activated' => 'Penugasan Pondok Diaktifkan',
                'updated' => 'Penugasan Pondok Diperbarui',
                default => 'Penugasan Pondok Baru',
            };
            $message = "{$assignment->user?->name} ditugaskan dari {$homeName} ke {$schoolName}.";
            $payload = [
                'module' => 'gtk',
                'action' => 'admin_announcement',
                'reference_type' => GtkCrossUnitAssignment::class,
                'reference_id' => $assignment->id,
                'type' => $event === 'revoked' ? 'warning' : 'info',
                'title' => $title,
                'message' => $message,
                'action_url' => null,
                'action_text' => 'Lihat Penugasan',
                'priority' => 'medium',
            ];

            $service = app(NotificationUniversalService::class);
            $service->send($assignment->user_id, $payload);
            if ($assignment->school?->work_unit_id) {
                $service->notifyGtk($assignment->school->work_unit_id, $payload, $assignment->user_id);
            }
        } catch (\Throwable $e) {
            Log::warning('Cross-unit assignment notification failed', [
                'assignment_id' => $assignment->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
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
