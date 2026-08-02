<?php

namespace App\Http\Controllers\Akademik;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\SubjectKktp;
use Illuminate\Http\Request;

class KktpController extends Controller
{
    /**
     * Index: list KKTP per mapel — filter TA, Semester, Tingkat
     * Akses: Admin TU / Waka / Kepsek
     */
    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $academicYears = AcademicYear::where('is_active', true)->orderByDesc('name')->get();
        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : $academicYears->first()?->id;

        $selectedSemester = $request->filled('semester') ? $request->semester : 'ganjil';

        $gradeLevels = GradeLevel::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('level')->get();

        $selectedGlId = $request->filled('grade_level_id') ? $request->grade_level_id : null;

        // Ambil semua mapel (subjects) — untuk lookup nama
        $subjects = Subject::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get();

        // Ambil data KKTP yang sudah ada
        $kktpQuery = SubjectKktp::with('subject', 'gradeLevel')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($selectedAyId, fn ($q) => $q->where('academic_year_id', $selectedAyId))
            ->when($selectedSemester, fn ($q) => $q->where('semester', $selectedSemester))
            ->when($selectedGlId, fn ($q) => $q->where('grade_level_id', $selectedGlId))
            ->orderBy('semester')
            ->orderBy('subject_id');

        // Jika belum ada filter tingkat, grouping per mapel
        $kktpList = $selectedGlId
            ? $kktpQuery->get()
            : $kktpQuery->get()->groupBy('subject_id');

        // Ambil subject_ids yang sudah punya KKTP
        $usedSubjectIds = SubjectKktp::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($selectedAyId, fn ($q) => $q->where('academic_year_id', $selectedAyId))
            ->when($selectedSemester, fn ($q) => $q->where('semester', $selectedSemester))
            ->pluck('subject_id')
            ->unique()
            ->toArray();

        // Mapel yang belum ada KKTP
        $unusedSubjects = $subjects->filter(fn ($s) => ! in_array($s->id, $usedSubjectIds))->values();

        // Mapel yang sudah ada KKTP
        $usedSubjects = $subjects->filter(fn ($s) => in_array($s->id, $usedSubjectIds))->values();

        return view('akademik.kktp.index', compact(
            'userId', 'academicYears', 'gradeLevels', 'subjects',
            'selectedAyId', 'selectedSemester', 'selectedGlId',
            'kktpList', 'usedSubjects', 'unusedSubjects'
        ));
    }

    /**
     * Store / Update KKTP — bulk per mapel
     * Body: kktp[subject_id][kkm_score] = 75, dst
     */
    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $request->validate([
            'academic_year_id' => 'required',
            'semester' => 'required|in:ganjil,genap',
            'grade_level_id' => 'required',
            'kktp' => 'required|array',
        ]);

        $ayId = $request->academic_year_id;
        $sem = $request->semester;
        $glId = $request->grade_level_id;

        foreach ($request->kktp as $subjectId => $data) {
            SubjectKktp::updateOrCreate(
                [
                    'subject_id' => $subjectId,
                    'school_id' => $schoolId,
                    'grade_level_id' => $glId,
                    'academic_year_id' => $ayId,
                    'semester' => $sem,
                ],
                [
                    'kktp_score' => $data['kktp_score'] ?? null,
                    'kkm_score' => $data['kkm_score'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $userId,
                ]
            );
        }

        return redirect()->back()->with('success', 'KKM/KKTP berhasil disimpan.');
    }

    /**
     * Auto-generate KKTP untuk mapel tertentu:copy dari tahun ajaran sebelumnya
     */
    public function generate(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $request->validate([
            'academic_year_id' => 'required',
            'semester' => 'required|in:ganjil,genap',
            'grade_level_id' => 'required',
        ]);

        $glId = $request->grade_level_id;
        $ayId = $request->academic_year_id;
        $sem = $request->semester;

        // Ambil KKTP dari semester sebelumnya (jika ganjil→genap, atau genap→ganjil dari tahun sebelumnya)
        $prevSem = $sem === 'ganjil' ? 'genap' : 'ganjil';
        $prevKktp = SubjectKktp::where('school_id', $schoolId)
            ->where('grade_level_id', $glId)
            ->where('semester', $prevSem)
            ->get()
            ->keyBy('subject_id');

        $created = 0;
        foreach ($prevKktp as $subjectId => $prev) {
            $exists = SubjectKktp::where('subject_id', $subjectId)
                ->where('school_id', $schoolId)
                ->where('grade_level_id', $glId)
                ->where('academic_year_id', $ayId)
                ->where('semester', $sem)
                ->exists();

            if (! $exists) {
                SubjectKktp::create([
                    'subject_id' => $subjectId,
                    'school_id' => $schoolId,
                    'grade_level_id' => $glId,
                    'academic_year_id' => $ayId,
                    'semester' => $sem,
                    'kktp_score' => $prev->kktp_score,
                    'kkm_score' => $prev->kkm_score,
                    'notes' => $prev->notes,
                    'created_by' => $userId,
                ]);
                $created++;
            }
        }

        return redirect()->back()->with('success', "KKTP berhasil di-generate: {$created} mapel.");
    }
}
