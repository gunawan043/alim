<?php

namespace App\Http\Controllers\Bahasa;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\LanguageMufradatSetoran;
use App\Models\LanguageVocabularyPeriod;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\BahasaAccess;
use App\Services\BahasaFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MufradatController extends Controller
{
    private const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public function __construct(
        private readonly BahasaAccess $access,
        private readonly BahasaFilterService $filters,
    ) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $schoolId = $this->filters->schoolId($request);
        $activeYear = AcademicYear::query()->where('is_active', true)->first();
        $gradeId = $this->filters->gradeLevelId($request);
        $studyGroupId = $this->filters->studyGroupId($request);

        $periods = LanguageVocabularyPeriod::with(['academicYear:id,name', 'school:id,name'])
            ->withCount(['vocabularies', 'setorans'])
            ->when($schoolId, fn ($query) => $query->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $schoolId)))
            ->when($gradeId, fn ($query) => $query->where(fn ($q) => $q->whereNull('grade_level_id')->orWhere('grade_level_id', $gradeId)))
            ->when($studyGroupId, fn ($query) => $query->where(fn ($q) => $q->whereNull('study_group_id')->orWhere('study_group_id', $studyGroupId)))
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->orderByDesc('period_start')
            ->orderByDesc('created_at')
            ->get();

        return view('bahasa.mufradat.index', [
            'userId' => $userId,
            'periods' => $periods,
            'activeYear' => $activeYear,
            'canManage' => $this->access->canManage($request->user()),
            'canRecord' => $this->access->canRecord($request->user()),
            ...$this->filters->options($request),
        ]);
    }

    public function create(Request $request, string $userId)
    {
        $this->authorizeManage($request);

        return view('bahasa.mufradat.form', $this->formData($request, new LanguageVocabularyPeriod));
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeManage($request);
        $validated = $this->validatePeriod($request);
        $activeYear = AcademicYear::query()->where('is_active', true)->first();

        $period = LanguageVocabularyPeriod::create([
            ...$validated,
            'school_id' => $this->filters->schoolId($request),
            'academic_year_id' => $activeYear?->id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('user.bahasa.mufradat.show', ['userId' => $userId, 'period' => $period->id])
            ->with('success', 'Periode mufradat berhasil dibuat.');
    }

    public function show(Request $request, string $userId, string $period)
    {
        $this->authorizeView($request);
        $record = $this->findPeriod($request, $period);

        $record->load([
            'academicYear:id,name',
            'school:id,name',
            'vocabularies',
            'schedules.mustami:id,name',
            'mustamis.mustami:id,name',
            'mustamis.dormitory:id,name',
        ]);

        $setorans = LanguageMufradatSetoran::with(['student:id,name', 'mustami:id,name'])
            ->where('period_id', $record->id)
            ->orderByDesc('setoran_date')
            ->limit(30)
            ->get();

        return view('bahasa.mufradat.show', [
            'userId' => $userId,
            'period' => $record,
            'setorans' => $setorans,
            'days' => self::DAYS,
            'mustamiOptions' => $this->userOptions($request),
            'dormitories' => Dormitory::query()->when($this->schoolId($request), fn ($q, $schoolId) => $q->where('school_id', $schoolId))->orderBy('name')->get(),
            'canManage' => $this->access->canManage($request->user()),
            'canRecord' => $this->access->canRecord($request->user()),
            'canManageStructure' => $this->access->canManageStructure($request->user()),
        ]);
    }

    public function edit(Request $request, string $userId, string $period)
    {
        $this->authorizeManage($request);
        $record = $this->findPeriod($request, $period);

        return view('bahasa.mufradat.form', $this->formData($request, $record));
    }

    public function update(Request $request, string $userId, string $period)
    {
        $this->authorizeManage($request);
        $record = $this->findPeriod($request, $period);
        $record->update($this->validatePeriod($request));

        return redirect()
            ->route('user.bahasa.mufradat.show', ['userId' => $userId, 'period' => $record->id])
            ->with('success', 'Periode mufradat diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $period)
    {
        $this->authorizeManage($request);
        $record = $this->findPeriod($request, $period);
        $record->delete();

        return redirect()
            ->route('user.bahasa.mufradat.index', ['userId' => $userId])
            ->with('success', 'Periode mufradat dihapus.');
    }

    public function storeWord(Request $request, string $userId, string $period)
    {
        $this->authorizeManage($request);
        $record = $this->findPeriod($request, $period);
        $validated = $request->validate([
            'arabic' => ['required', 'string', 'max:180'],
            'meaning' => ['required', 'string', 'max:180'],
            'example_sentence' => ['nullable', 'string', 'max:2000'],
        ]);

        $position = ((int) $record->vocabularies()->max('position')) + 1;
        $record->vocabularies()->create([...$validated, 'position' => $position]);

        return back()->with('success', 'Mufradat ditambahkan.');
    }

    public function destroyWord(Request $request, string $userId, string $period, string $word)
    {
        $this->authorizeManage($request);
        $record = $this->findPeriod($request, $period);
        $item = $record->vocabularies()->findOrFail($word);
        $item->delete();

        return back()->with('success', 'Mufradat dihapus.');
    }

    public function storeSchedule(Request $request, string $userId, string $period)
    {
        $this->authorizeManage($request);
        $record = $this->findPeriod($request, $period);
        $validated = $request->validate([
            'mustami_id' => ['nullable', 'uuid', 'exists:users,id'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'time_start' => ['nullable', 'date_format:H:i'],
            'time_end' => ['nullable', 'date_format:H:i', 'after_or_equal:time_start'],
            'place' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $record->schedules()->create([...$validated, 'is_active' => true]);

        return back()->with('success', 'Jadwal setoran ditambahkan.');
    }

    public function destroySchedule(Request $request, string $userId, string $period, string $schedule)
    {
        $this->authorizeManage($request);
        $record = $this->findPeriod($request, $period);
        $record->schedules()->findOrFail($schedule)->delete();

        return back()->with('success', 'Jadwal setoran dihapus.');
    }

    public function storeMustami(Request $request, string $userId, string $period)
    {
        $this->authorizeManageStructure($request);
        $record = $this->findPeriod($request, $period);
        $validated = $request->validate([
            'mustami_id' => ['required', 'uuid', 'exists:users,id'],
            'scope' => ['nullable', 'string', 'max:150'],
            'dormitory_id' => ['nullable', 'uuid', 'exists:dormitories,id'],
        ]);

        $record->mustamis()->create([...$validated, 'is_active' => true]);

        return back()->with('success', 'Mustami ditambahkan.');
    }

    public function destroyMustami(Request $request, string $userId, string $period, string $assignment)
    {
        $this->authorizeManageStructure($request);
        $record = $this->findPeriod($request, $period);
        $record->mustamis()->findOrFail($assignment)->delete();

        return back()->with('success', 'Mustami dihapus.');
    }

    public function attendance(Request $request, string $userId, string $period)
    {
        $this->authorizeRecord($request);
        $record = $this->findPeriod($request, $period);
        $record->load(['academicYear:id,name', 'school:id,name', 'schedules.mustami:id,name', 'vocabularies']);

        $date = $request->input('date', now()->toDateString());
        $scheduleId = $request->input('schedule_id');
        $studyGroupId = $this->filters->studyGroupId($request);
        $gradeId = $this->filters->gradeLevelId($request);
        $schoolId = $this->filters->schoolId($request);

        $students = Student::query()
            ->where('status', 'active')
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($studyGroupId, fn ($query) => $query->whereHas('classHistories', fn ($q) => $q->where('study_group_id', $studyGroupId)->where('is_active', true)))
            ->when($gradeId, fn ($query) => $query->whereHas('classHistories.studyGroup', fn ($q) => $q->where('grade_level_id', $gradeId)))
            ->with(['currentClassHistory.studyGroup:id,name'])
            ->orderBy('name')
            ->get();

        $existing = LanguageMufradatSetoran::where('period_id', $record->id)
            ->whereDate('setoran_date', $date)
            ->when($scheduleId, fn ($query) => $query->where('schedule_id', $scheduleId))
            ->get()
            ->keyBy('student_id');

        $studyGroups = StudyGroup::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->orderBy('name')
            ->get();

        return view('bahasa.mufradat.attendance', [
            'userId' => $userId,
            'period' => $record,
            'students' => $students,
            'studyGroups' => $studyGroups,
            'date' => $date,
            'scheduleId' => $scheduleId,
            'studyGroupId' => $studyGroupId,
            'existing' => $existing,
            'mustamiOptions' => $this->userOptions($request),
            'canManage' => true,
            ...$this->filters->options($request),
        ]);
    }

    public function storeAttendance(Request $request, string $userId, string $period)
    {
        $this->authorizeRecord($request);
        $record = $this->findPeriod($request, $period);
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'schedule_id' => ['nullable', 'uuid', 'exists:language_mufradat_schedules,id'],
            'mustami_id' => ['nullable', 'uuid', 'exists:users,id'],
            'rows' => ['nullable', 'array'],
            'rows.*.student_id' => ['required_with:rows', 'uuid', 'exists:students,id'],
            'rows.*.present' => ['nullable', 'boolean'],
            'rows.*.total_words' => ['nullable', 'integer', 'min:0'],
            'rows.*.correct_words' => ['nullable', 'integer', 'min:0'],
            'rows.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $totalWords = $record->vocabularies()->count();
        $created = 0;

        DB::transaction(function () use ($record, $validated, $totalWords, $request, &$created) {
            foreach ($validated['rows'] ?? [] as $row) {
                if (empty($row['present'])) {
                    continue;
                }

                $correct = (int) ($row['correct_words'] ?? 0);
                $total = (int) ($row['total_words'] ?? $totalWords);
                $score = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

                LanguageMufradatSetoran::updateOrCreate(
                    [
                        'period_id' => $record->id,
                        'student_id' => $row['student_id'],
                        'setoran_date' => $validated['date'],
                        'schedule_id' => $validated['schedule_id'] ?? null,
                    ],
                    [
                        'mustami_id' => $validated['mustami_id'] ?? null,
                        'total_words' => $total,
                        'correct_words' => $correct,
                        'score' => $score,
                        'status' => $score >= 80 ? 'lulus' : 'ulang',
                        'notes' => $row['notes'] ?? null,
                        'created_by' => $request->user()->id,
                    ]
                );
                $created++;
            }
        });

        return redirect()
            ->route('user.bahasa.mufradat.report', ['userId' => $userId, 'period' => $record->id])
            ->with('success', "{$created} data setoran mufradat disimpan.");
    }

    public function report(Request $request, string $userId, string $period)
    {
        $this->authorizeView($request);
        $record = $this->findPeriod($request, $period);
        $record->load(['academicYear:id,name', 'school:id,name', 'vocabularies', 'mustamis.mustami:id,name']);

        $setorans = LanguageMufradatSetoran::with('student:id,name')
            ->where('period_id', $record->id)
            ->when($this->filters->gradeLevelId($request) || $this->filters->studyGroupId($request), fn ($q) => $this->filters->applyStudentScope($q, $request))
            ->orderBy('setoran_date')
            ->get();

        $rows = $setorans->groupBy('student_id')->map(function ($items) {
            $student = $items->first()->student;
            $totalWords = (int) $items->sum('total_words');
            $correctWords = (int) $items->sum('correct_words');
            $average = (float) $items->avg('score');

            return [
                'student' => $student,
                'attempts' => $items->count(),
                'total_words' => $totalWords,
                'correct_words' => $correctWords,
                'average' => round($average, 2),
                'last_date' => $items->max('setoran_date'),
                'follow_up' => $average < 80 ? 'Pengulangan Hafalan' : 'Tidak Ada',
            ];
        })->sortByDesc('average')->values();

        return view('bahasa.mufradat.report', [
            'userId' => $userId,
            'period' => $record,
            'record' => $record,
            'rows' => $rows,
            'setorans' => $setorans,
            'canRecord' => $this->access->canRecord($request->user()),
            ...$this->filters->options($request),
        ]);
    }

    public function printList(Request $request, string $userId, string $period)
    {
        $this->authorizeView($request);
        $record = $this->findPeriod($request, $period)->load(['vocabularies', 'academicYear:id,name', 'school:id,name']);

        return view('bahasa.mufradat.print-list', compact('period', 'record'));
    }

    public function printAttendance(Request $request, string $userId, string $period)
    {
        $this->authorizeView($request);
        $record = $this->findPeriod($request, $period)->load(['schedules.mustami:id,name']);

        return view('bahasa.mufradat.print-attendance', compact('period', 'record'));
    }

    public function printReport(Request $request, string $userId, string $period)
    {
        $this->authorizeView($request);
        $record = $this->findPeriod($request, $period);

        $setorans = LanguageMufradatSetoran::with('student:id,name')->where('period_id', $record->id)->get();
        $rows = $setorans->groupBy('student_id')->map(function ($items) {
            return [
                'student' => $items->first()->student,
                'total_words' => (int) $items->sum('total_words'),
                'average' => round((float) $items->avg('score'), 2),
                'follow_up' => (float) $items->avg('score') < 80 ? 'Pengulangan Hafalan' : 'Tidak Ada',
            ];
        })->values();

        return view('bahasa.mufradat.print-report', compact('period', 'record', 'rows'));
    }

    private function validatePeriod(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'location_school' => ['nullable', 'string', 'max:180'],
            'location_dorm' => ['nullable', 'string', 'max:180'],
            'grade_level_id' => ['nullable', 'uuid', 'exists:grade_levels,id'],
            'study_group_id' => ['nullable', 'uuid', 'exists:study_groups,id'],
            'min_words' => ['nullable', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]) + ['is_active' => true];
    }

    private function formData(Request $request, LanguageVocabularyPeriod $period): array
    {
        return [
            'userId' => $request->route('userId'),
            'period' => $period,
            'activeYear' => AcademicYear::query()->where('is_active', true)->first(),
            ...$this->filters->options($request),
        ];
    }

    private function userOptions(Request $request): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function findPeriod(Request $request, string $id): LanguageVocabularyPeriod
    {
        $period = LanguageVocabularyPeriod::findOrFail($id);
        $schoolId = $this->schoolId($request);

        if ($schoolId && $period->school_id && (string) $period->school_id !== (string) $schoolId && ! $this->access->isDepartment($request->user()) && ! $this->access->isSuperAdmin($request->user())) {
            abort(403, 'Anda tidak berwenang membuka periode ini.');
        }

        return $period;
    }

    private function schoolId(Request $request): ?string
    {
        return $this->filters->schoolId($request);
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($this->access->canView($request->user()), 403, 'Anda tidak berwenang membuka modul Bahasa.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($this->access->canManage($request->user()), 403, 'Anda tidak berwenang mengelola mufradat.');
    }

    private function authorizeRecord(Request $request): void
    {
        abort_unless($this->access->canRecord($request->user()), 403, 'Anda tidak berwenang mencatat setoran mufradat.');
    }

    private function authorizeManageStructure(Request $request): void
    {
        abort_unless($this->access->canManageStructure($request->user()), 403, 'Hanya Departemen Bahasa yang dapat mengatur mustami.');
    }
}
