<?php

namespace App\Http\Controllers;

use App\Exports\StudentAchievementTemplateExport;
use App\Imports\StudentAchievementImport;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class StudentAchievementController extends Controller
{
    private const PER_PAGE = 15;

    // ─── Helpers ───────────────────────────────────────────────────────────

    private function getSchoolContextId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId');
    }

    private function getTypeFromRequest(Request $request): string
    {
        $type = $request->get('type', 'akademik');
        if ($type === 'quran') return 'hafalan_quran';
        if ($type === 'hadits') return 'hafalan_hadits';
        return $type;
    }

    // ─── Index / List ─────────────────────────────────────────────────────

    public function index(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $schoolId = $this->getSchoolContextId($request);
        $achievementType = $this->getTypeFromRequest($request);
        $hafalanCategory = null;
        if ($achievementType === 'hafalan_quran') $hafalanCategory = 'quran';
        if ($achievementType === 'hafalan_hadits') $hafalanCategory = 'hadits';

        $query = StudentAchievement::with(['student', 'academicYear', 'coach', 'creator'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('achievement_type', $achievementType)
            ->when($achievementType === 'hafalan_quran' || $achievementType === 'hafalan_hadits', fn($q) =>
                $q->where('achievement_type', 'hafalan')
                  ->where('hafalan_category', $hafalanCategory)
            );

        // Filters
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
        if ($request->filled('study_group_id')) {
            $query->whereHas('student.classHistories', fn($q) => $q
                ->where('study_group_id', $request->study_group_id)
                ->where('is_active', true)
            );
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->whereHas('student', fn($sq) => $sq
                    ->where('name', 'like', "%{$s}%")
                    ->orWhere('nisn', 'like', "%{$s}%")
                )
                ->orWhere('event_name', 'like', "%{$s}%")
                ->orWhere('organizer', 'like', "%{$s}%")
            );
        }

        $achievements = $query->orderByDesc('event_date')
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Dropdowns
        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $studyGroups = $schoolId
            ? StudyGroup::where('school_id', $schoolId)->where('is_active', true)->with('gradeLevel')->orderBy('name')->get()
            : collect();

        $typeLabel = match ($achievementType) {
            'akademik' => 'Prestasi Akademik',
            'hafalan_quran' => 'Hafalan Qur\'an',
            'hafalan_hadits' => 'Hafalan Hadits',
            default => 'Prestasi',
        };

        return view('student-achievement.index', compact(
            'achievements', 'academicYears', 'studyGroups',
            'userId', 'schoolId', 'achievementType', 'typeLabel',
        ));
    }

    // ─── Create ──────────────────────────────────────────────────────────

    public function create(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $schoolId = $this->getSchoolContextId($request);
        $achievementType = $this->getTypeFromRequest($request);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();

        $coachIds = usersHavingPermission('student_teacher.readable');
        $coaches = User::whereIn('id', $coachIds)
            ->orderBy('name')->get(['id', 'name']);

        $typeLabel = match ($achievementType) {
            'akademik' => 'Prestasi Akademik',
            'hafalan_quran' => "Hafalan Qur'an",
            'hafalan_hadits' => 'Hafalan Hadits',
            default => 'Prestasi',
        };

        // All active students grouped by class — preloaded for Select2
        $studentQuery = Student::with(['currentClassHistory.studyGroup'])->where('status', 'active');
        if ($schoolId) $studentQuery->where('school_id', $schoolId);
        $allStudents = $studentQuery->orderBy('name')->get();
        $groupedStudents = [];
        foreach ($allStudents as $s) {
            $sg = $s->currentClassHistory?->studyGroup;
            $label = $sg ? $sg->full_name : 'Tanpa Kelas';
            if (!isset($groupedStudents[$label])) $groupedStudents[$label] = [];
            $groupedStudents[$label][] = $s;
        }

        return view('student-achievement.create', compact(
            'userId', 'schoolId', 'achievementType', 'typeLabel', 'academicYears', 'activeYear', 'coaches', 'groupedStudents',
        ));
    }

    public function store(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $schoolId = $this->getSchoolContextId($request);
        $achievementType = $this->getTypeFromRequest($request);
        $hafalanCategory = null;
        if ($achievementType === 'hafalan_quran') $hafalanCategory = 'quran';
        if ($achievementType === 'hafalan_hadits') $hafalanCategory = 'hadits';

        $validated = $request->validate([
            'student_id'       => 'required|uuid|exists:students,id',
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'event_name'       => 'required|string|max:191',
            'organizer'        => 'nullable|string|max:191',
            'level'            => 'required|in:internal,kecamatan,kabupaten_kota,provinsi,nasional,internasional',
            'position'         => 'required|in:juara_1,juara_2,juara_3,harapan_1,harapan_2,harapan_3,peserta,mumtaz_murtafi,lainnya',
            'position_detail'  => 'nullable|string|max:100',
            'event_date'       => 'required|date',
            'event_location'   => 'nullable|string|max:191',
            'coach_id'         => 'nullable|uuid|exists:users,id',
            'certificate'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes'            => 'nullable|string|max:500',
        ]);

        $data = [
            'student_id'       => $validated['student_id'],
            'school_id'        => $schoolId,
            'academic_year_id' => $validated['academic_year_id'],
            'achievement_type' => $achievementType === 'hafalan_quran' || $achievementType === 'hafalan_hadits' ? 'hafalan' : $achievementType,
            'hafalan_category' => $hafalanCategory,
            'event_name'       => $validated['event_name'],
            'organizer'        => $validated['organizer'] ?? null,
            'level'            => $validated['level'],
            'position'         => $validated['position'],
            'position_detail'  => $validated['position_detail'] ?? null,
            'event_date'       => $validated['event_date'],
            'event_location'   => $validated['event_location'] ?? null,
            'coach_id'         => $validated['coach_id'] ?? null,
            'notes'            => $validated['notes'] ?? null,
            'created_by'       => $userId,
        ];

        if ($request->hasFile('certificate')) {
            $path = $request->file('certificate')->store('student-achievements/certificates/' . date('Y/m'), 'public');
            $data['certificate_path'] = $path;
        }

        $achievement = StudentAchievement::create($data);

        $redirectType = $achievementType === 'hafalan_quran' ? 'quran' : ($achievementType === 'hafalan_hadits' ? 'hadits' : $achievementType);
        return redirect()
            ->route('user.student-achievement.show', ['userId' => $userId, 'id' => $achievement->id, 'type' => $redirectType])
            ->with('success', 'Data prestasi berhasil disimpan.');
    }

    // ─── Show ────────────────────────────────────────────────────────────

    public function show(Request $request, string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $schoolId = $this->getSchoolContextId($request);

        $achievement = StudentAchievement::with(['student', 'academicYear', 'coach', 'creator', 'verifiedByUser'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        return view('student-achievement.show', compact('achievement', 'userId'));
    }

    // ─── Edit ───────────────────────────────────────────────────────────

    public function edit(Request $request, string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $schoolId = $this->getSchoolContextId($request);

        $achievement = StudentAchievement::with(['student'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $coachIds = usersHavingPermission('student_teacher.readable');
        $coaches = User::whereIn('id', $coachIds)
            ->orderBy('name')->get(['id', 'name']);

        return view('student-achievement.edit', compact(
            'achievement', 'userId', 'schoolId', 'academicYears', 'coaches',
        ));
    }

    public function update(Request $request, string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $schoolId = $this->getSchoolContextId($request);

        $achievement = StudentAchievement::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        $validated = $request->validate([
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'event_name'       => 'required|string|max:191',
            'organizer'        => 'nullable|string|max:191',
            'level'            => 'required|in:internal,kecamatan,kabupaten_kota,provinsi,nasional,internasional',
            'position'         => 'required|in:juara_1,juara_2,juara_3,harapan_1,harapan_2,harapan_3,peserta,mumtaz_murtafi,lainnya',
            'position_detail'  => 'nullable|string|max:100',
            'event_date'       => 'required|date',
            'event_location'   => 'nullable|string|max:191',
            'coach_id'         => 'nullable|uuid|exists:users,id',
            'certificate'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes'            => 'nullable|string|max:500',
        ]);

        $data = collect($validated)->except(['certificate'])->toArray();

        if ($request->hasFile('certificate')) {
            // Delete old
            if ($achievement->certificate_path && Storage::exists($achievement->certificate_path)) {
                Storage::delete($achievement->certificate_path);
            }
            $path = $request->file('certificate')->store('student-achievements/certificates/' . date('Y/m'), 'public');
            $data['certificate_path'] = $path;
        }

        $achievement->update($data);

        $typeSlug = $achievement->hafalan_category === 'quran' ? 'quran'
            : ($achievement->hafalan_category === 'hadits' ? 'hadits'
            : $achievement->achievement_type);

        return redirect()
            ->route('user.student-achievement.show', ['userId' => $userId, 'id' => $achievement->id, 'type' => $typeSlug])
            ->with('success', 'Data prestasi berhasil diperbarui.');
    }

    // ─── Delete ──────────────────────────────────────────────────────────

    public function destroy(Request $request, string $userId, string $id)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $schoolId = $this->getSchoolContextId($request);

        $achievement = StudentAchievement::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->findOrFail($id);
        $achievement->delete();

        $typeSlug = $request->get('type', 'akademik');
        if ($typeSlug === 'quran') $typeSlug = 'hafalan_quran';
        if ($typeSlug === 'hadits') $typeSlug = 'hafalan_hadits';

        return redirect()
            ->route('user.student-achievement.index', ['userId' => $userId, 'type' => $typeSlug])
            ->with('success', 'Data prestasi berhasil dihapus.');
    }

    // ─── Import ─────────────────────────────────────────────────────────

    public function importForm(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $schoolId = $this->getSchoolContextId($request);
        $achievementType = $this->getTypeFromRequest($request);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();

        $studentCount = $schoolId
            ? Student::where('school_id', $schoolId)->where('status', 'active')->count()
            : 0;

        $typeLabel = match ($achievementType) {
            'akademik' => 'Prestasi Akademik',
            'hafalan_quran' => 'Hafalan Qur\'an',
            'hafalan_hadits' => 'Hafalan Hadits',
            default => 'Prestasi',
        };

        return view('student-achievement.import', compact(
            'userId', 'schoolId', 'achievementType', 'typeLabel',
            'academicYears', 'activeYear', 'studentCount',
        ));
    }

    public function importProcess(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $schoolId = $this->getSchoolContextId($request);
        if (!$schoolId) {
            return back()->with('error', 'Tidak dapat menentukan satuan pendidikan.')->withInput();
        }

        $achievementType = $this->getTypeFromRequest($request);
        $hafalanCategory = null;
        if ($achievementType === 'hafalan_quran') { $hafalanCategory = 'quran'; $achievementType = 'hafalan'; }
        if ($achievementType === 'hafalan_hadits') { $hafalanCategory = 'hadits'; $achievementType = 'hafalan'; }

        $request->validate([
            'file'     => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'images.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'file.required' => 'File Excel wajib diupload.',
            'file.mimes'     => 'File harus berformat .xlsx, .xls, atau .csv.',
        ]);

        // Collect uploaded images
        $uploadedFiles = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $uploadedFiles[$file->getClientOriginalName()] = $file;
            }
        }

        $academicYearId = $request->get('academic_year_id');
        if (!$academicYearId) {
            $activeYear = AcademicYear::where('is_active', true)->first();
            $academicYearId = $activeYear?->id;
        }

        $import = new StudentAchievementImport(
            $userId,
            $schoolId,
            $achievementType,
            $hafalanCategory,
            $academicYearId,
            $uploadedFiles
        );

        Excel::import($import, $request->file('file'));

        $errors = $import->getErrors();
        $created = $import->getSuccessCount();
        $typeSlug = $request->get('type', 'akademik');

        if ($created > 0 && empty($errors)) {
            return redirect()
                ->route('user.student-achievement.index', ['userId' => $userId, 'type' => $typeSlug])
                ->with('success', "Berhasil mengimport {$created} data prestasi.");
        }

        if ($created > 0 && !empty($errors)) {
            return redirect()
                ->route('user.student-achievement.index', ['userId' => $userId, 'type' => $typeSlug])
                ->with('success', "Berhasil mengimport {$created} data prestasi.")
                ->with('import_errors', $errors);
        }

        return redirect()
            ->route('user.student-achievement.import-form', ['userId' => $userId, 'type' => $typeSlug])
            ->with('error', 'Gagal mengimport data. Periksa format file dan data Anda.')
            ->with('import_errors', $errors);
    }

    // ─── Download Template ──────────────────────────────────────────────

    public function downloadTemplate(Request $request, string $userId)
    {
        abort_unless(auth()->user() && auth()->user()->id === $userId, 403);

        $achievementType = $this->getTypeFromRequest($request);

        $typeLabel = match ($achievementType) {
            'akademik'       => 'Prestasi Akademik',
            'hafalan_quran'  => 'Hafalan Qur\'an',
            'hafalan_hadits' => 'Hafalan Hadits',
            default          => 'Prestasi',
        };

        $schoolId = $this->getSchoolContextId($request);

        $filename = "template_import_{$achievementType}_" . date('Ymd') . ".xlsx";

        return (new StudentAchievementTemplateExport($typeLabel, $achievementType, $schoolId))
            ->download($filename);
    }

    // ─── Find Student (AJAX) ────────────────────────────────────────────
    public function findStudent(Request $request, string $userId)
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) return response()->json([]);

        $schoolId = $this->getSchoolContextId($request);

        $students = Student::where('status', 'active')
            ->when($schoolId, fn($q2) => $q2->where('school_id', $schoolId))
            ->where(fn($sq) => $sq
                ->where('name', 'like', "%{$q}%")
                ->orWhere('nisn', 'like', "%{$q}%")
            )
            ->limit(20)
            ->get(['id', 'name', 'nisn', 'gender', 'birth_place', 'birth_date', 'address']);

        return response()->json($students->map(fn($s) => [
            'id'          => $s->id,
            'name'        => $s->name,
            'nisn'        => $s->nisn,
            'gender'      => $s->gender,
            'gender_text' => $s->gender_text,
            'birth_place' => $s->birth_place,
            'birth_date'  => $s->birth_date?->format('d/m/Y'),
            'address'     => $s->address,
        ]));
    }
}