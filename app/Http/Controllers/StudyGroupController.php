<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\SchoolGroupService;
use App\Services\StudyGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StudyGroupController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $activeAcademicYearId = $activeAcademicYear?->id;
        $schoolId = $request->attributes->get('schoolContextId');

        $query = StudyGroup::with(['school', 'academicYear', 'gradeLevel', 'homeroomTeacher'])
            ->where('is_active', true);

        // Default: hanya rombel pada TAHUN AJARAN aktif, bukan sekadar
        // semua rombel yang kebetulan bersemester sama pada TA lama.
        if (! $request->boolean('semua_ta') && ! $request->filled('academic_year_id')) {
            if ($activeAcademicYearId) {
                $query->where('academic_year_id', $activeAcademicYearId);
            } elseif ($activeAcademicYear) {
                $query->whereHas('academicYear', fn ($q) => $q->where('semester', $activeAcademicYear->semester));
            }
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Statistik ringkas (mengikuti filter konteks/TA, bukan pencarian).
        $statsGroupIds = (clone $query)->pluck('id');
        $activeAcademicYearId = AcademicYear::where('is_active', true)->value('id');

        // Hitungan santri per rombel mengikuti TA ROMBEL MASING-MASING (arsip),
        // bukan selalu TA aktif — berlaku juga pada mode "Semua Tahun Ajaran".
        // TA aktif: keanggotaan berjalan; TA arsip: seluruh penempatan periode tsb.
        $countsByGroup = StudentClassHistory::query()
            ->whereIn('student_class_histories.study_group_id', $statsGroupIds)
            ->join('study_groups as sg_period', 'sg_period.id', '=', 'student_class_histories.study_group_id')
            ->whereColumn('student_class_histories.academic_year_id', 'sg_period.academic_year_id')
            ->when(
                $activeAcademicYearId,
                function ($q) use ($activeAcademicYearId) {
                    $q->where(function ($qq) use ($activeAcademicYearId) {
                        $qq->where(function ($qqq) use ($activeAcademicYearId) {
                            $qqq->where('sg_period.academic_year_id', $activeAcademicYearId)
                                ->where('student_class_histories.is_active', true);
                        })->orWhere('sg_period.academic_year_id', '!=', $activeAcademicYearId);
                    });
                },
                fn ($q) => $q->where('student_class_histories.is_active', true)
            )
            ->groupBy('student_class_histories.study_group_id')
            ->selectRaw('student_class_histories.study_group_id as sg_id, COUNT(*) as total')
            ->pluck('total', 'sg_id');

        $stats = [
            'total' => $statsGroupIds->count(),
            'santri' => (int) $countsByGroup->sum(),
            'wali' => (clone $query)->whereNotNull('homeroom_teacher_id')->count(),
            'kapasitas' => (int) (clone $query)->sum('capacity'),
        ];

        $studyGroups = $query->orderedByGrade()->paginate(15)->withQueryString();

        // Load student counts per study group (TA rombel masing-masing).
        $sgIds = $studyGroups->pluck('id');
        $counts = $countsByGroup->only($sgIds->all());

        $studyGroups->getCollection()->transform(function ($sg) use ($counts) {
            $sg->studentCount = $counts[$sg->id] ?? 0;

            return $sg;
        });

        $schools = School::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $isGlobalView = $request->attributes->get('isGlobalView') === true;

        return view('study-groups.index', compact('studyGroups', 'schools', 'academicYears', 'stats', 'userId', 'isGlobalView'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId) {
            $schools = School::where('id', $schoolId)->get();
            $gradeLevels = GradeLevel::where('school_id', $schoolId)->orderBy('level')->get();
            $teacherIds = SchoolGroupService::userIdsForSchool($schoolId);
            $nonTeachingIds = usersHavingPermission('general_staff.ineligible');
            $teachers = User::query()
                ->when(! empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds))
                ->when(! empty($teacherIds), fn ($q) => $q->whereIn('users.id', $teacherIds))
                ->orderBy('name')->get();
        } else {
            $schools = School::orderBy('name')->get();
            $gradeLevels = collect();
            $teachers = collect();
        }
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        $schoolContext = $schoolId ? School::find($schoolId) : null;

        return view('study-groups.create', compact(
            'schools', 'academicYears', 'gradeLevels', 'teachers', 'userId', 'schoolContext'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $rules = [
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:50',
            'code' => 'nullable|string|max:20',
            'capacity' => 'nullable|integer|min:1|max:200',
            'room' => 'nullable|string|max:50',
            'curriculum_type' => 'nullable|in:merdeka,2013,ktsp',
            'shift' => 'nullable|in:pagi,siang',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
        if (! $schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);

        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $exists = StudyGroup::where('school_id', $data['school_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('grade_level_id', $data['grade_level_id'] ?? null)
            ->where('name', $data['name'])
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Rombel dengan nama ini sudah ada pada sekolah, tahun ajaran, dan tingkat tersebut.');
        }

        $studyGroup = StudyGroup::create($data);

        return redirect()->route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id])
            ->with('success', 'Rombel berhasil disimpan.');
    }

    public function show(string $userId, string $id)
    {
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        $studyGroup = StudyGroup::with([
            'school', 'academicYear', 'gradeLevel', 'homeroomTeacher',
        ])->findOrFail($id);

        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $activeHistories = $studyGroup->studentClassHistories()
            ->with('student:id,name,nisn,nis,gender')
            ->where('academic_year_id', $studyGroup->academic_year_id)
            ->when(
                ! $activeAcademicYear || (string) $studyGroup->academic_year_id === (string) $activeAcademicYear->id,
                fn ($q) => $q->where('is_active', true)
            )
            ->orderByDesc('is_active')
            ->orderBy('attendance_number')
            ->get();

        $isArchiveGroup = $activeAcademicYear
            && (string) $studyGroup->academic_year_id !== (string) $activeAcademicYear->id;

        return view('study-groups.show', compact('studyGroup', 'userId', 'activeHistories', 'activeAcademicYear', 'isArchiveGroup'));
    }

    public function edit(string $userId, string $id)
    {
        $studyGroup = StudyGroup::findOrFail($id);
        $schoolId = request()->attributes->get('schoolContextId');

        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $schools = School::orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $gradeLevels = GradeLevel::where('school_id', $studyGroup->school_id)->orderBy('level')->get();
        $teacherIds = SchoolGroupService::userIdsForSchool($studyGroup->school_id);
        $nonTeachingIds = usersHavingPermission('general_staff.ineligible');
        $teachers = User::query()
            ->when(! empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds))
            ->when(! empty($teacherIds), fn ($q) => $q->whereIn('users.id', $teacherIds))
            ->orderBy('name')->get();
        $schoolContext = $schoolId ? School::find($schoolId) : null;

        return view('study-groups.edit', compact(
            'studyGroup', 'schools', 'academicYears', 'gradeLevels', 'teachers', 'userId', 'schoolContext'
        ));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $studyGroup = StudyGroup::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $rules = [
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:50',
            'code' => 'nullable|string|max:20',
            'capacity' => 'nullable|integer|min:1|max:200',
            'room' => 'nullable|string|max:50',
            'curriculum_type' => 'nullable|in:merdeka,2013,ktsp',
            'shift' => 'nullable|in:pagi,siang',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
        if (! $schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);

        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $exists = StudyGroup::where('school_id', $data['school_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('grade_level_id', $data['grade_level_id'] ?? null)
            ->where('name', $data['name'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Rombel dengan nama ini sudah ada pada sekolah, tahun ajaran, dan tingkat tersebut.');
        }

        $studyGroup->update($data);

        return redirect()->route('user.study-groups.show', ['userId' => $userId, 'id' => $studyGroup->id])
            ->with('success', 'Rombel berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $studyGroup = StudyGroup::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $studyGroup->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        try {
            app(StudyGroupService::class)->deleteSafely($studyGroup);
        } catch (\Throwable $e) {
            Log::warning('Hapus rombel gagal: '.$e->getMessage(), [
                'study_group_id' => $id,
            ]);

            return redirect()->route('user.study-groups.index', ['userId' => $userId])
                ->with('error', 'Rombel tidak dapat dihapus karena masih terkait data lain (jadwal/riwayat). Periksa kembali.');
        }

        return redirect()->route('user.study-groups.index', ['userId' => $userId])
            ->with('success', 'Rombel berhasil dihapus.');
    }
}
