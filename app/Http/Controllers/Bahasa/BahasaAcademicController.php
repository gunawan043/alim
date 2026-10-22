<?php

namespace App\Http\Controllers\Bahasa;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\NilaiSumatif;
use App\Models\StudyGroup;
use App\Models\TeacherAdminBook;
use App\Services\BahasaAccess;
use App\Services\BahasaFilterService;
use Illuminate\Http\Request;

/**
 * Analisis nilai bahasa memakai data nilai resmi Satuan Pendidikan
 * (TeacherAdminBook + NilaiSumatif). Modul ini read-only.
 */
class BahasaAcademicController extends Controller
{
    public function __construct(
        private readonly BahasaAccess $access,
        private readonly BahasaFilterService $filters,
    ) {}

    public function nilai(Request $request, string $userId)
    {
        return $this->render($request, $userId, 'nilai');
    }

    public function progress(Request $request, string $userId)
    {
        return $this->render($request, $userId, 'progress');
    }

    public function atRisk(Request $request, string $userId)
    {
        return $this->render($request, $userId, 'at-risk');
    }

    public function leger(Request $request, string $userId)
    {
        return $this->render($request, $userId, 'leger');
    }

    public function exams(Request $request, string $userId)
    {
        return $this->render($request, $userId, $request->input('mode', 'ujian'));
    }

    public function report(Request $request, string $userId)
    {
        return $this->render($request, $userId, 'report');
    }

    private function render(Request $request, string $userId, string $mode)
    {
        abort_unless($this->access->canView($request->user()), 403, 'Anda tidak berwenang membuka modul Bahasa.');

        $schoolId = $this->filters->schoolId($request);
        $academicYear = AcademicYear::query()->where('is_active', true)->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();
        $semester = $request->input('semester', $academicYear?->semester === 'genap' ? 'genap' : 'ganjil');
        $language = in_array($request->input('language'), ['arab', 'inggris'], true)
            ? $request->input('language')
            : 'arab';
        $studyGroupId = $this->filters->studyGroupId($request);
        $gradeLevelId = $this->filters->gradeLevelId($request);
        $threshold = (float) $request->input('threshold', 75);

        $books = TeacherAdminBook::with(['subject:id,name,code', 'studyGroup:id,name'])
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->where('semester', $semester)
            ->where('is_active', true)
            ->whereHas('subject', fn ($query) => $query->where(function ($subject) use ($language) {
                if ($language === 'inggris') {
                    $subject->where('name', 'like', '%Inggris%')
                        ->orWhere('name', 'like', '%English%')
                        ->orWhere('code', 'like', '%ENG%');
                } else {
                    $subject->where('name', 'like', '%Arab%')
                        ->orWhere('code', 'like', '%ARB%');
                }
            }))
            ->when($studyGroupId, fn ($query) => $query->where('study_group_id', $studyGroupId))
            ->when($gradeLevelId, fn ($query) => $query->whereHas('studyGroup', fn ($group) => $group->where('grade_level_id', $gradeLevelId)))
            ->get();

        $scores = NilaiSumatif::with('student:id,name')
            ->whereIn('admin_book_id', $books->pluck('id'))
            ->get();

        $bookMap = $books->keyBy('id');
        $rows = $scores->groupBy('student_id')->map(function ($items) use ($bookMap) {
            $student = $items->first()->student;
            $subjectScores = [];
            $class = null;

            foreach ($items as $item) {
                $book = $bookMap->get($item->admin_book_id);
                $score = $this->effectiveScore($item);
                $subject = $book?->subject?->name ?? 'Bahasa';
                $class ??= $book?->studyGroup?->name;

                if ($score !== null) {
                    $subjectScores[$subject] = max((float) ($subjectScores[$subject] ?? 0), $score);
                }
            }

            if ($subjectScores === []) {
                return null;
            }

            return [
                'student' => $student,
                'class' => $class ?: '-',
                'scores' => $subjectScores,
                'average' => round(collect($subjectScores)->avg(), 2),
                'count' => count($subjectScores),
            ];
        })->filter()->values();

        if ($mode === 'at-risk') {
            $rows = $rows->where('average', '<', $threshold)->values();
        }

        $rows = $rows->sortByDesc('average')->values();
        $subjects = collect($rows)->flatMap(fn ($row) => array_keys($row['scores']))->unique()->sort()->values();
        $classAverages = $rows->groupBy('class')->map(fn ($items) => round((float) $items->avg('average'), 2));

        $studyGroups = StudyGroup::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = [
            'students' => $rows->count(),
            'average' => $rows->isEmpty() ? 0 : round((float) $rows->avg('average'), 2),
            'highest' => $rows->isEmpty() ? 0 : round((float) $rows->max('average'), 2),
            'at_risk' => $rows->where('average', '<', $threshold)->count(),
            'books' => $books->count(),
        ];

        return view('bahasa.academic.index', [
            'userId' => $userId,
            'mode' => $mode,
            'language' => $language,
            'semester' => $semester,
            'threshold' => $threshold,
            'academicYear' => $academicYear,
            'rows' => $rows,
            'subjects' => $subjects,
            'classAverages' => $classAverages,
            'stats' => $stats,
            'studyGroups' => $studyGroups,
            'studyGroupId' => $studyGroupId,
            'gradeLevelId' => $gradeLevelId,
            ...$this->filters->options($request),
        ]);
    }

    private function effectiveScore(NilaiSumatif $score): ?float
    {
        foreach (['nr_final', 'nr_murni', 'rsa', 'raport_sts', 'sts', 'sas'] as $field) {
            if ($score->{$field} !== null) {
                return (float) $score->{$field};
            }
        }

        return null;
    }
}
