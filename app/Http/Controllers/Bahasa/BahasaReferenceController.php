<?php

namespace App\Http\Controllers\Bahasa;

use App\Http\Controllers\Controller;
use App\Models\GtkEmployment;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Services\BahasaAccess;
use App\Services\BahasaFilterService;
use Illuminate\Http\Request;

/**
 * Referensi khusus Bahasa: hanya guru bahasa dan mata pelajaran bahasa.
 * Tidak memakai daftar GTK/mapel umum agar tidak menampilkan data lain.
 */
class BahasaReferenceController extends Controller
{
    public function __construct(
        private readonly BahasaAccess $access,
        private readonly BahasaFilterService $filters,
    ) {}

    public function teachers(Request $request, string $userId)
    {
        $this->authorizeView($request);
        $schoolId = $this->filters->schoolId($request);
        $language = in_array($request->input('language'), ['arab', 'inggris'], true) ? $request->input('language') : null;
        $gradeId = $this->filters->gradeLevelId($request);
        $studyGroupId = $this->filters->studyGroupId($request);

        $subjectPattern = $this->subjectPattern($language);

        $query = GtkEmployment::with(['user:id,name,email,is_active', 'school:id,name'])
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where(function ($q) {
                $q->where('jabatan', 'like', '%Bahasa Arab%')
                    ->orWhere('jabatan', 'like', '%Bahasa Inggris%')
                    ->orWhere('jabatan', 'like', '%Guru Bahasa%')
                    ->orWhereIn('user_id', TeachingAssignment::query()
                        ->where('status', 'active')
                        ->whereHas('subject', fn ($subject) => $subject->where(function ($inner) {
                            $inner->where('name', 'like', '%Bahasa Arab%')
                                ->orWhere('name', 'like', '%Bahasa Inggris%')
                                ->orWhere('name', 'like', '%English%')
                                ->orWhere('code', 'like', '%ARB%')
                                ->orWhere('code', 'like', '%ENG%');
                        }))
                        ->select('teacher_id'));
            })
            ->when($subjectPattern, function ($q) use ($subjectPattern) {
                $q->where(function ($inner) use ($subjectPattern) {
                    $inner->where('jabatan', 'like', $subjectPattern)
                        ->orWhereIn('user_id', TeachingAssignment::query()
                            ->where('status', 'active')
                            ->whereHas('subject', fn ($subject) => $subject->where(function ($s) use ($subjectPattern) {
                                $s->where('name', 'like', $subjectPattern)
                                    ->orWhere('code', 'like', $subjectPattern);
                            }))
                            ->select('teacher_id'));
                });
            })
            ->when($studyGroupId, fn ($q) => $q->whereIn('user_id', TeachingAssignment::query()->where('study_group_id', $studyGroupId)->select('teacher_id')))
            ->when($gradeId, fn ($q) => $q->whereIn('user_id', TeachingAssignment::query()->whereHas('studyGroup', fn ($group) => $group->where('grade_level_id', $gradeId))->select('teacher_id')))
            ->when($request->filled('search'), fn ($q) => $q->whereHas('user', fn ($user) => $user->where('name', 'like', '%'.$request->search.'%')))
            ->orderBy('jabatan')
            ->orderBy('nupy');

        $teachers = $query->get()->unique('user_id')->values();
        $assignments = TeachingAssignment::with('subject:id,name')
            ->whereIn('teacher_id', $teachers->pluck('user_id'))
            ->where('status', 'active')
            ->get()
            ->groupBy('teacher_id');

        $stats = [
            'total' => $teachers->count(),
            'arab' => $teachers->filter(fn ($teacher) => str_contains(strtolower((string) $teacher->jabatan), 'arab'))->count(),
            'inggris' => $teachers->filter(fn ($teacher) => str_contains(strtolower((string) $teacher->jabatan), 'inggris'))->count(),
            'schools' => $teachers->pluck('school_id')->filter()->unique()->count(),
        ];

        return view('bahasa.reference.teachers', [
            'userId' => $userId,
            'teachers' => $teachers,
            'assignments' => $assignments,
            'stats' => $stats,
            'language' => $language,
            ...$this->filters->options($request),
        ]);
    }

    public function subjects(Request $request, string $userId)
    {
        $this->authorizeView($request);
        $schoolId = $this->filters->schoolId($request);
        $language = in_array($request->input('language'), ['arab', 'inggris'], true) ? $request->input('language') : null;
        $gradeId = $this->filters->gradeLevelId($request);
        $studyGroupId = $this->filters->studyGroupId($request);

        $query = Subject::with('school:id,name')
            ->withCount('adminBooks')
            ->where(function ($q) {
                $q->where('name', 'like', '%Bahasa Arab%')
                    ->orWhere('name', 'like', '%Bahasa Inggris%')
                    ->orWhere('name', 'like', '%English%')
                    ->orWhere('code', 'like', '%ARB%')
                    ->orWhere('code', 'like', '%ENG%');
            })
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($language === 'arab', fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', '%Arab%')->orWhere('code', 'like', '%ARB%')))
            ->when($language === 'inggris', fn ($q) => $q->where(fn ($inner) => $inner->where('name', 'like', '%Inggris%')->orWhere('name', 'like', '%English%')->orWhere('code', 'like', '%ENG%')))
            ->when($gradeId, fn ($q) => $q->whereHas('gradeLevelSubjects', fn ($g) => $g->where('grade_level_id', $gradeId)))
            ->when($studyGroupId, fn ($q) => $q->whereHas('adminBooks', fn ($book) => $book->where('study_group_id', $studyGroupId)))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('name');

        $subjects = $query->get();
        $stats = [
            'total' => $subjects->count(),
            'arab' => $subjects->filter(fn ($subject) => str_contains(strtolower($subject->name), 'arab') || str_contains(strtoupper((string) $subject->code), 'ARB'))->count(),
            'inggris' => $subjects->filter(fn ($subject) => str_contains(strtolower($subject->name), 'inggris') || str_contains(strtolower($subject->name), 'english') || str_contains(strtoupper((string) $subject->code), 'ENG'))->count(),
            'jp' => (int) $subjects->sum('credit_hours'),
        ];

        return view('bahasa.reference.subjects', [
            'userId' => $userId,
            'subjects' => $subjects,
            'stats' => $stats,
            'language' => $language,
            ...$this->filters->options($request),
        ]);
    }

    private function subjectPattern(?string $language): ?string
    {
        return match ($language) {
            'arab' => '%Arab%',
            'inggris' => '%Inggris%',
            default => null,
        };
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($this->access->canView($request->user()), 403, 'Anda tidak berwenang membuka referensi Bahasa.');
    }
}
