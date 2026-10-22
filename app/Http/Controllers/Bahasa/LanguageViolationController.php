<?php

namespace App\Http\Controllers\Bahasa;

use App\Http\Controllers\Controller;
use App\Models\Dormitory;
use App\Models\LanguageCoaching;
use App\Models\LanguageViolation;
use App\Models\Student;
use App\Services\BahasaAccess;
use App\Services\BahasaFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LanguageViolationController extends Controller
{
    public function __construct(
        private readonly BahasaAccess $access,
        private readonly BahasaFilterService $filters,
    ) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $schoolId = $this->filters->schoolId($request);

        $query = LanguageViolation::with(['student:id,name', 'reporter:id,name'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($this->filters->gradeLevelId($request) || $this->filters->studyGroupId($request), fn ($q) => $this->filters->applyStudentScope($q, $request))
            ->when($request->filled('month'), function ($q) use ($request) {
                [$year, $month] = array_pad(explode('-', (string) $request->month), 2, null);
                $q->whereYear('violation_date', $year)->whereMonth('violation_date', $month);
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('student', fn ($student) => $student->where('name', 'like', '%'.$request->search.'%'));
            });

        $violations = $query->orderByDesc('violation_date')->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'total' => LanguageViolation::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'month' => LanguageViolation::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->whereYear('violation_date', now()->year)->whereMonth('violation_date', now()->month)->count(),
            'points' => (int) LanguageViolation::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->whereYear('violation_date', now()->year)->whereMonth('violation_date', now()->month)->sum('points'),
            'coached' => LanguageViolation::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->whereIn('status', ['coached', 'closed'])->count(),
        ];

        return view('bahasa.violations.index', [
            'userId' => $userId,
            'violations' => $violations,
            'stats' => $stats,
            'canManage' => $this->access->canManage($request->user()),
            'canRecord' => $this->access->canRecord($request->user()),
            'students' => $this->students($request),
            'dormitories' => $this->dormitories($request),
            ...$this->filters->options($request),
        ]);
    }

    public function create(Request $request, string $userId)
    {
        $this->authorizeRecord($request);

        return view('bahasa.violations.form', [
            'userId' => $userId,
            'violation' => new LanguageViolation(['violation_date' => now()->toDateString(), 'points' => 1]),
            'students' => $this->students($request),
            'dormitories' => $this->dormitories($request),
        ]);
    }

    public function report(Request $request, string $userId)
    {
        $this->authorizeView($request);
        $schoolId = $this->filters->schoolId($request);
        $threshold = max(1, (int) $request->input('threshold', 100));

        $violations = LanguageViolation::with(['student:id,name', 'student.currentClassHistory.studyGroup:id,name'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($this->filters->gradeLevelId($request) || $this->filters->studyGroupId($request), fn ($q) => $this->filters->applyStudentScope($q, $request))
            ->orderBy('violation_date')
            ->get();

        $rows = $violations->groupBy('student_id')->map(function ($items) {
            $student = $items->first()->student;

            return [
                'student' => $student,
                'class_name' => $items->first()->class_name ?: $student?->currentClassHistory?->studyGroup?->name,
                'count' => $items->count(),
                'points' => (int) $items->sum('points'),
                'last_date' => $items->max('violation_date'),
                'coached' => $items->whereIn('status', ['coached', 'closed'])->count(),
            ];
        })->sortByDesc('points')->values();

        return view('bahasa.violations.report', [
            'userId' => $userId,
            'rows' => $rows,
            'threshold' => $threshold,
            'schoolId' => $schoolId,
            ...$this->filters->options($request),
        ]);
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeRecord($request);
        $validated = $this->validateViolation($request);
        $student = Student::findOrFail($validated['student_id']);

        $violation = LanguageViolation::create([
            ...$validated,
            'school_id' => $this->filters->schoolId($request) ?: $student->school_id,
            'class_name' => $validated['class_name'] ?? $student->currentClassHistory?->studyGroup?->name,
            'reported_by' => $request->user()->id,
            'status' => 'recorded',
        ]);

        return redirect()
            ->route('user.bahasa.violations.show', ['userId' => $userId, 'violation' => $violation->id])
            ->with('success', 'Pelanggaran bahasa dicatat.');
    }

    public function show(Request $request, string $userId, string $violation)
    {
        $this->authorizeView($request);
        $record = $this->findViolation($request, $violation);
        $record->load(['student:id,name,gender', 'reporter:id,name', 'dormitory:id,name', 'coachings.coach:id,name']);

        return view('bahasa.violations.show', [
            'userId' => $userId,
            'violation' => $record,
            'canManage' => $this->access->canManage($request->user()),
            'canRecord' => $this->access->canRecord($request->user()),
        ]);
    }

    public function edit(Request $request, string $userId, string $violation)
    {
        $this->authorizeManage($request);
        $record = $this->findViolation($request, $violation);

        return view('bahasa.violations.form', [
            'userId' => $userId,
            'violation' => $record,
            'students' => $this->students($request),
            'dormitories' => $this->dormitories($request),
        ]);
    }

    public function update(Request $request, string $userId, string $violation)
    {
        $this->authorizeManage($request);
        $record = $this->findViolation($request, $violation);
        $record->update($this->validateViolation($request));

        return redirect()
            ->route('user.bahasa.violations.show', ['userId' => $userId, 'violation' => $record->id])
            ->with('success', 'Pelanggaran bahasa diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $violation)
    {
        $this->authorizeManage($request);
        $this->findViolation($request, $violation)->delete();

        return redirect()
            ->route('user.bahasa.violations.index', ['userId' => $userId])
            ->with('success', 'Pelanggaran bahasa dihapus.');
    }

    public function storeCoaching(Request $request, string $userId, string $violation)
    {
        $this->authorizeRecord($request);
        $record = $this->findViolation($request, $violation);

        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:2000'],
            'coaching_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'follow_up' => ['nullable', 'string', 'max:2000'],
            'pledge_signed' => ['boolean'],
        ]);

        DB::transaction(function () use ($record, $validated, $request) {
            LanguageCoaching::create([
                ...$validated,
                'violation_id' => $record->id,
                'coach_id' => $request->user()->id,
            ]);
            $record->forceFill(['status' => 'coached'])->save();
        });

        return back()->with('success', 'Pembinaan bahasa dicatat.');
    }

    public function printPledge(Request $request, string $userId, string $violation)
    {
        $this->authorizeView($request);
        $record = $this->findViolation($request, $violation)->load('student:id,name');

        return view('bahasa.violations.print-pledge', compact('violation', 'record'));
    }

    public function printCoaching(Request $request, string $userId, string $violation)
    {
        $this->authorizeView($request);
        $record = $this->findViolation($request, $violation)->load(['student:id,name', 'coachings.coach:id,name']);

        return view('bahasa.violations.print-coaching', compact('violation', 'record'));
    }

    private function validateViolation(Request $request): array
    {
        return $request->validate([
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'violation_date' => ['required', 'date'],
            'place' => ['nullable', 'string', 'max:150'],
            'class_name' => ['nullable', 'string', 'max:100'],
            'dormitory_id' => ['nullable', 'uuid', 'exists:dormitories,id'],
            'violation_type' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'points' => ['required', 'integer', 'min:1', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function students(Request $request): Collection
    {
        return Student::query()
            ->where('status', 'active')
            ->when($this->schoolId($request), fn ($q, $schoolId) => $q->where('school_id', $schoolId))
            ->when($this->filters->gradeLevelId($request) || $this->filters->studyGroupId($request), fn ($q) => $this->filters->applyStudentScope($q, $request, ''))
            ->with('currentClassHistory.studyGroup:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'school_id']);
    }

    private function dormitories(Request $request): Collection
    {
        return Dormitory::query()
            ->when($this->schoolId($request), fn ($q, $schoolId) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function findViolation(Request $request, string $id): LanguageViolation
    {
        $record = LanguageViolation::findOrFail($id);
        $schoolId = $this->schoolId($request);

        if ($schoolId && $record->school_id && (string) $record->school_id !== (string) $schoolId && ! $this->access->isDepartment($request->user()) && ! $this->access->isSuperAdmin($request->user())) {
            abort(403, 'Anda tidak berwenang membuka data ini.');
        }

        return $record;
    }

    private function schoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId');
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($this->access->canView($request->user()), 403, 'Anda tidak berwenang membuka modul Bahasa.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($this->access->canManage($request->user()), 403, 'Anda tidak berwenang mengelola pelanggaran bahasa.');
    }

    private function authorizeRecord(Request $request): void
    {
        abort_unless($this->access->canRecord($request->user()), 403, 'Anda tidak berwenang mencatat pelanggaran bahasa.');
    }
}
