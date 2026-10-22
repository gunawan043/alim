<?php

namespace App\Http\Controllers\Bahasa;

use App\Http\Controllers\Controller;
use App\Models\LanguageDailyLog;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\BahasaAccess;
use App\Services\BahasaFilterService;
use Illuminate\Http\Request;

class LanguageDailyLogController extends Controller
{
    public function __construct(
        private readonly BahasaAccess $access,
        private readonly BahasaFilterService $filters,
    ) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $schoolId = $this->filters->schoolId($request);
        $type = in_array($request->input('type'), ['percakapan', 'jasus'], true)
            ? $request->input('type')
            : 'percakapan';

        $logs = LanguageDailyLog::with(['mustami:id,name', 'creator:id,name'])
            ->where('log_type', $type)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($this->filters->gradeLevelId($request), fn ($q) => $q->where(fn ($inner) => $inner->whereNull('grade_level_id')->orWhere('grade_level_id', $this->filters->gradeLevelId($request))))
            ->when($this->filters->studyGroupId($request), fn ($q) => $q->where(fn ($inner) => $inner->whereNull('study_group_id')->orWhere('study_group_id', $this->filters->studyGroupId($request))))
            ->when($request->filled('month'), function ($q) use ($request) {
                [$year, $month] = array_pad(explode('-', (string) $request->month), 2, null);
                $q->whereYear('log_date', $year)->whereMonth('log_date', $month);
            })
            ->when($request->filled('search'), fn ($q) => $q->where(function ($sub) use ($request) {
                $sub->where('theme', 'like', '%'.$request->search.'%')
                    ->orWhere('findings', 'like', '%'.$request->search.'%')
                    ->orWhere('class_name', 'like', '%'.$request->search.'%');
            }))
            ->orderByDesc('log_date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'percakapan' => LanguageDailyLog::where('log_type', 'percakapan')->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'jasus' => LanguageDailyLog::where('log_type', 'jasus')->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'month' => LanguageDailyLog::where('log_type', $type)->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->whereYear('log_date', now()->year)->whereMonth('log_date', now()->month)->count(),
            'findings' => LanguageDailyLog::where('log_type', $type)->whereNotNull('findings')->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
        ];

        return view('bahasa.daily.index', [
            'userId' => $userId,
            'type' => $type,
            'logs' => $logs,
            'stats' => $stats,
            'mustamis' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'classes' => StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->orderBy('name')->get(['id', 'name']),
            'canRecord' => $this->access->canRecord($request->user()),
            'canManage' => $this->access->canManage($request->user()),
            ...$this->filters->options($request),
        ]);
    }

    public function store(Request $request, string $userId)
    {
        $this->authorizeRecord($request);
        $validated = $request->validate([
            'log_type' => ['required', 'in:percakapan,jasus'],
            'log_date' => ['required', 'date'],
            'theme' => ['nullable', 'string', 'max:180'],
            'location' => ['nullable', 'string', 'max:150'],
            'class_name' => ['nullable', 'string', 'max:100'],
            'mustami_id' => ['nullable', 'uuid', 'exists:users,id'],
            'participants_count' => ['nullable', 'integer', 'min:0'],
            'findings' => ['nullable', 'string', 'max:2000'],
            'follow_up' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:selesai,perlu_tindak_lanjut'],
            'grade_level_id' => ['nullable', 'uuid', 'exists:grade_levels,id'],
            'study_group_id' => ['nullable', 'uuid', 'exists:study_groups,id'],
        ]);

        LanguageDailyLog::create([
            ...$validated,
            'school_id' => $this->filters->schoolId($request),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('user.bahasa.daily.index', ['userId' => $userId, 'type' => $validated['log_type']])
            ->with('success', $validated['log_type'] === 'jasus' ? 'Monitoring Jasus dicatat.' : 'Percakapan harian dicatat.');
    }

    public function destroy(Request $request, string $userId, string $log)
    {
        $this->authorizeManage($request);
        $record = LanguageDailyLog::findOrFail($log);
        $type = $record->log_type;
        $record->delete();

        return redirect()
            ->route('user.bahasa.daily.index', ['userId' => $userId, 'type' => $type])
            ->with('success', 'Catatan harian dihapus.');
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($this->access->canView($request->user()), 403, 'Anda tidak berwenang membuka modul Bahasa.');
    }

    private function authorizeRecord(Request $request): void
    {
        abort_unless($this->access->canRecord($request->user()), 403, 'Anda tidak berwenang mencatat aktivitas bahasa.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($this->access->canManage($request->user()), 403, 'Anda tidak berwenang menghapus catatan bahasa.');
    }
}
