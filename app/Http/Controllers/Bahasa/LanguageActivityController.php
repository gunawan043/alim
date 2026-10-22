<?php

namespace App\Http\Controllers\Bahasa;

use App\Http\Controllers\Controller;
use App\Models\LanguageMonthlyActivity;
use App\Services\BahasaAccess;
use App\Services\BahasaFilterService;
use Illuminate\Http\Request;

class LanguageActivityController extends Controller
{
    private const TYPES = [
        'pidato' => 'Lomba Pidato',
        'permainan_kata' => 'Permainan Kata',
        'film' => 'Nonton Film',
        'drama' => 'Lomba Drama',
        'lainnya' => 'Kegiatan Lain',
    ];

    public function __construct(
        private readonly BahasaAccess $access,
        private readonly BahasaFilterService $filters,
    ) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);
        $schoolId = $this->filters->schoolId($request);
        $gradeId = $this->filters->gradeLevelId($request);
        $studyGroupId = $this->filters->studyGroupId($request);

        $activities = LanguageMonthlyActivity::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($gradeId, fn ($q) => $q->where(fn ($inner) => $inner->whereNull('grade_level_id')->orWhere('grade_level_id', $gradeId)))
            ->when($studyGroupId, fn ($q) => $q->where(fn ($inner) => $inner->whereNull('study_group_id')->orWhere('study_group_id', $studyGroupId)))
            ->when($request->filled('month'), function ($q) use ($request) {
                [$year, $month] = array_pad(explode('-', (string) $request->month), 2, null);
                $q->whereYear('period_month', $year)->whereMonth('period_month', $month);
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('period_month')
            ->orderByDesc('activity_date')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => LanguageMonthlyActivity::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'planned' => LanguageMonthlyActivity::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('status', 'planned')->count(),
            'completed' => LanguageMonthlyActivity::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->where('status', 'completed')->count(),
            'participants' => (int) LanguageMonthlyActivity::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->sum('participants_count'),
        ];

        return view('bahasa.activities.index', [
            'userId' => $userId,
            'activities' => $activities,
            'stats' => $stats,
            'types' => self::TYPES,
            'canManage' => $this->access->canManage($request->user()),
            ...$this->filters->options($request),
        ]);
    }

    public function create(Request $request, string $userId)
    {
        $this->authorizeManage($request);

        return view('bahasa.activities.form', [
            'userId' => $userId,
            'activity' => new LanguageMonthlyActivity([
                'period_month' => now()->startOfMonth()->toDateString(),
                'activity_date' => now()->toDateString(),
                'status' => 'planned',
                'activity_type' => 'pidato',
                'executor_type' => 'santri',
            ]),
            'types' => self::TYPES,
            ...$this->filters->options($request),
        ]);
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeManage($request);
        $validated = $this->validateActivity($request);

        $activity = LanguageMonthlyActivity::create([
            ...$validated,
            'school_id' => $this->filters->schoolId($request),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('user.bahasa.activities.show', ['userId' => $userId, 'activity' => $activity->id])
            ->with('success', 'Kegiatan bahasa berhasil dibuat.');
    }

    public function show(Request $request, string $userId, string $activity)
    {
        $this->authorizeView($request);
        $record = $this->findActivity($request, $activity);

        return view('bahasa.activities.show', [
            'userId' => $userId,
            'activity' => $record,
            'types' => self::TYPES,
            'canManage' => $this->access->canManage($request->user()),
        ]);
    }

    public function edit(Request $request, string $userId, string $activity)
    {
        $this->authorizeManage($request);
        $record = $this->findActivity($request, $activity);

        return view('bahasa.activities.form', [
            'userId' => $userId,
            'activity' => $record,
            'types' => self::TYPES,
            ...$this->filters->options($request),
        ]);
    }

    public function update(Request $request, string $userId, string $activity)
    {
        $this->authorizeManage($request);
        $record = $this->findActivity($request, $activity);
        $record->update($this->validateActivity($request));

        return redirect()
            ->route('user.bahasa.activities.show', ['userId' => $userId, 'activity' => $record->id])
            ->with('success', 'Kegiatan bahasa diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $activity)
    {
        $this->authorizeManage($request);
        $this->findActivity($request, $activity)->delete();

        return redirect()
            ->route('user.bahasa.activities.index', ['userId' => $userId])
            ->with('success', 'Kegiatan bahasa dihapus.');
    }

    public function print(Request $request, string $userId, string $activity)
    {
        $this->authorizeView($request);
        $record = $this->findActivity($request, $activity);

        return view('bahasa.activities.print', compact('activity', 'record'));
    }

    private function validateActivity(Request $request): array
    {
        $validated = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'title' => ['required', 'string', 'max:180'],
            'theme' => ['nullable', 'string', 'max:180'],
            'activity_type' => ['required', 'in:pidato,permainan_kata,film,drama,lainnya'],
            'activity_date' => ['nullable', 'date'],
            'time_start' => ['nullable', 'date_format:H:i'],
            'time_end' => ['nullable', 'date_format:H:i', 'after_or_equal:time_start'],
            'place' => ['nullable', 'string', 'max:150'],
            'executor_type' => ['required', 'in:santri,guru,departemen,asrama'],
            'executor_name' => ['nullable', 'string', 'max:180'],
            'mc_name' => ['nullable', 'string', 'max:180'],
            'committee' => ['nullable', 'string', 'max:2000'],
            'participants_count' => ['nullable', 'integer', 'min:0'],
            'participation_notes' => ['nullable', 'string', 'max:2000'],
            'report' => ['nullable', 'string', 'max:4000'],
            'status' => ['required', 'in:planned,completed,cancelled'],
            'grade_level_id' => ['nullable', 'uuid', 'exists:grade_levels,id'],
            'study_group_id' => ['nullable', 'uuid', 'exists:study_groups,id'],
        ]);

        $validated['period_month'] = $validated['period_month'].'-01';

        return $validated;
    }

    private function findActivity(Request $request, string $id): LanguageMonthlyActivity
    {
        $record = LanguageMonthlyActivity::findOrFail($id);
        $schoolId = $this->schoolId($request);

        if ($schoolId && $record->school_id && (string) $record->school_id !== (string) $schoolId && ! $this->access->isDepartment($request->user()) && ! $this->access->isSuperAdmin($request->user())) {
            abort(403, 'Anda tidak berwenang membuka kegiatan ini.');
        }

        return $record;
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
        abort_unless($this->access->canManage($request->user()), 403, 'Anda tidak berwenang mengelola kegiatan bahasa.');
    }
}
