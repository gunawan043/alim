<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\SubjectKktp;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GradeLevelController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = GradeLevel::with('school');

        $schoolId = $request->attributes->get('schoolContextId');
        $isGlobalView = $request->attributes->get('isGlobalView') === true;
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        $stats = [
            'total' => (clone $query)->count(),
            'aktif' => (clone $query)->where('is_active', true)->count(),
            'nonaktif' => (clone $query)->where('is_active', false)->count(),
            'fase' => (clone $query)->whereNotNull('fase')->distinct()->count('fase'),
        ];

        $gradeLevels = $query->orderBy('school_id')->orderBy('level')->paginate(15)->withQueryString();
        $schools = School::orderBy('name')->get();

        return view('grade-levels.index', compact('gradeLevels', 'schools', 'stats', 'userId', 'isGlobalView'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $schools = School::where('id', $schoolId)->get();
        } else {
            $schools = School::orderBy('name')->get();
        }
        $schoolContext = $schoolId ? School::find($schoolId) : null;

        return view('grade-levels.create', compact('schools', 'userId', 'schoolContext'));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $rules = [
            'level' => 'required|integer|min:1|max:15',
            'name' => 'required|string|max:50',
            'code' => 'nullable|string|max:20',
            'fase' => 'nullable|string|max:5',
            'is_active' => 'boolean',
        ];
        if (! $schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);

        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $exists = GradeLevel::where('school_id', $data['school_id'])
            ->where('level', $data['level'])
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Tingkat kelas ini sudah ada untuk sekolah tersebut.');
        }

        $gradeLevel = GradeLevel::create($data);

        return redirect()->route('user.grade-levels.show', ['userId' => $userId, 'id' => $gradeLevel->id])
            ->with('success', 'Tingkat kelas berhasil disimpan.');
    }

    public function show(string $userId, string $id)
    {
        $gradeLevel = GradeLevel::with('school')->findOrFail($id);
        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $activeAy = AcademicYear::where('is_active', true)->first();
        $semester = $activeAy?->semester ?? 'ganjil';

        // ARSIP PERIODE: struktur versi TA aktif + baris lama (NULL) sebagai fallback.
        // Baris shadow nonaktif (mapel dihapus untuk TA ini) menutupi baris lama,
        // lalu dibuang dari tampilan.
        $gradeLevelSubjects = GradeLevelSubject::with('subject')
            ->where('grade_level_id', $id)
            ->forAcademicYear($activeAy?->id)
            ->orderByAcademicYearFirst()
            ->orderBy('subject_id')
            ->get()
            ->unique(fn ($r) => $r->subject_id.'|'.($r->track ?? ''))
            ->filter(fn ($r) => $r->is_active)
            ->values();

        $availableSubjects = Subject::where('is_active', true)
            ->when($gradeLevel->school_id, fn ($q) => $q->where('school_id', $gradeLevel->school_id))
            ->orderBy('name')
            ->get();

        // Ambil KKTP per mapel untuk semester aktif
        $kktpMap = SubjectKktp::where('grade_level_id', $id)
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->where('semester', $semester)
            ->get()
            ->keyBy('subject_id');

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        return view('grade-levels.show', compact(
            'gradeLevel', 'userId', 'gradeLevelSubjects', 'availableSubjects',
            'kktpMap', 'activeAy', 'semester', 'academicYears'
        ));
    }

    public function addSubject(Request $request, string $userId, string $id)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403);
        }

        // Validasi backend: mapel wajib milik satuan pendidikan yang sama & aktif
        // (dropdown memang hanya menampilkan ini — jangan biarkan dipalsukan dari klien).
        $validated = $request->validate([
            'subject_id' => [
                'required',
                Rule::exists('subjects', 'id')
                    ->where(fn ($q) => $q->where('school_id', $gradeLevel->school_id)->where('is_active', true)),
            ],
            'allocation_hours' => 'nullable|integer|min:0|max:40',
        ]);

        $weeklyHours = (int) ($validated['allocation_hours'] ?? 0);
        $activeAy = AcademicYear::where('is_active', true)->first();

        $existing = GradeLevelSubject::query()
            ->where('grade_level_id', $id)
            ->where('subject_id', $validated['subject_id'])
            ->forAcademicYear($activeAy?->id)
            ->orderByAcademicYearFirst()
            ->first();

        if ($existing) {
            // Baris versi TA berjalan yang pernah dinonaktifkan → aktifkan kembali (idempoten).
            if ($existing->academic_year_id && ! $existing->is_active) {
                $existing->update(['is_active' => true, 'allocation_hours' => $weeklyHours]);

                $propagated = $this->propagateSubjectToStudyGroups($gradeLevel, $existing->subject_id, $weeklyHours);

                return redirect()->route('user.grade-levels.show', ['userId' => $userId, 'id' => $id])
                    ->with('success', "Mapel diaktifkan kembali untuk TA berjalan dan {$propagated} rombel aktif.");
            }

            return back()->with('error', 'Mapel ini sudah ditambahkan ke tingkat ini.');
        }

        GradeLevelSubject::create([
            'id' => (string) Str::uuid(),
            'academic_year_id' => $activeAy?->id,
            'grade_level_id' => $id,
            'subject_id' => $validated['subject_id'],
            'allocation_hours' => $weeklyHours,
        ]);

        // Propagasi: mapel tingkat → seluruh rombel aktif pada tingkat yang sama,
        // agar langsung muncul di Buku Nilai, Jadwal, Plotting, dan Leger.
        $propagated = $this->propagateSubjectToStudyGroups($gradeLevel, $validated['subject_id'], $weeklyHours);

        return redirect()->route('user.grade-levels.show', ['userId' => $userId, 'id' => $id])
            ->with('success', $propagated > 0
                ? "Mapel berhasil ditambahkan ke tingkat ini dan {$propagated} rombel aktif."
                : 'Mapel berhasil ditambahkan.');
    }

    /**
     * Sinkronkan mapel tingkat ke seluruh rombel aktif pada tingkat yang sama.
     * Baris yang pernah di-soft-delete dihidupkan kembali; jam mengajar yang
     * sudah diatur di rombel tidak ditimpa.
     */
    protected function propagateSubjectToStudyGroups(GradeLevel $gradeLevel, string $subjectId, int $weeklyHours = 0): int
    {
        $groups = StudyGroup::query()
            ->where('grade_level_id', $gradeLevel->id)
            ->where('school_id', $gradeLevel->school_id)
            ->where('is_active', true)
            ->get(['id']);

        $count = 0;

        foreach ($groups as $group) {
            $existing = StudyGroupSubject::withTrashed()
                ->where('study_group_id', $group->id)
                ->where('subject_id', $subjectId)
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                if (! $existing->is_active) {
                    $existing->forceFill(['is_active' => true])->save();
                }
                $count++;

                continue;
            }

            StudyGroupSubject::create([
                'study_group_id' => $group->id,
                'subject_id' => $subjectId,
                'weekly_hours' => $weeklyHours,
                'is_active' => true,
            ]);
            $count++;
        }

        return $count;
    }

    public function removeSubject(Request $request, string $userId, string $id)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403);
        }

        $gls = GradeLevelSubject::where('grade_level_id', $id)
            ->where('id', $request->input('subject_id'))
            ->first();

        if ($gls) {
            $subjectId = $gls->subject_id;
            $activeAy = AcademicYear::where('is_active', true)->first();

            // Ada periode lain? Hanya bila ada, baris lama (lintas periode) perlu
            // dipertahankan untuk arsip dan cukup disembunyikan lewat baris versi
            // periode (shadow nonaktif). Pada sistem satu TA perilaku lama tetap:
            // baris dihapus.
            $hasOtherPeriods = $activeAy
                && AcademicYear::where('id', '!=', $activeAy->id)->exists();

            if ($gls->academic_year_id || ! $activeAy || ! $hasOtherPeriods) {
                $gls->delete();
            } else {
                GradeLevelSubject::updateOrCreate(
                    [
                        'academic_year_id' => $activeAy->id,
                        'grade_level_id' => $id,
                        'subject_id' => $subjectId,
                        'track' => $gls->track,
                    ],
                    [
                        'allocation_hours' => $gls->allocation_hours,
                        'is_active' => false,
                    ]
                );
            }

            // Nonaktifkan mapel pada rombel-rombel tingkat ini (riwayat nilai tidak dihapus).
            $groupIds = StudyGroup::where('grade_level_id', $id)
                ->where('school_id', $gradeLevel->school_id)
                ->pluck('id');

            StudyGroupSubject::whereIn('study_group_id', $groupIds)
                ->where('subject_id', $subjectId)
                ->update(['is_active' => false]);
        }

        return redirect()->route('user.grade-levels.show', ['userId' => $userId, 'id' => $id])
            ->with('success', 'Mapel berhasil dihapus.');
    }

    public function saveKktp(Request $request, string $userId, string $id)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403);
        }

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'kktp' => 'nullable|array',
            'kktp.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $kktpData = $validated['kktp'] ?? [];

        foreach ($kktpData as $subjectId => $score) {
            if ($score === null || $score === '') {
                continue;
            }

            SubjectKktp::updateOrCreate(
                [
                    'subject_id' => $subjectId,
                    'grade_level_id' => $id,
                    'academic_year_id' => $validated['academic_year_id'],
                    'semester' => $validated['semester'],
                ],
                [
                    // Satu nilai KKTP: tulis ke kedua kolom (otoritatif + mirror).
                    'kktp_score' => $score,
                    'kkm_score' => $score,
                    'school_id' => $gradeLevel->school_id,
                    'created_by' => $request->user()?->id,
                ]
            );
        }

        return back()->with('success', 'KKTP berhasil disimpan.');
    }

    public function edit(string $userId, string $id)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }
        $schools = School::orderBy('name')->get();
        $schoolContext = $schoolId ? School::find($schoolId) : null;

        return view('grade-levels.edit', compact('gradeLevel', 'schools', 'userId', 'schoolContext'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $rules = [
            'level' => 'required|integer|min:1|max:15',
            'name' => 'required|string|max:50',
            'code' => 'nullable|string|max:20',
            'fase' => 'nullable|string|max:5',
            'is_active' => 'boolean',
        ];
        if (! $schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);

        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $exists = GradeLevel::where('school_id', $data['school_id'])
            ->where('level', $data['level'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Tingkat kelas ini sudah ada untuk sekolah tersebut.');
        }

        $gradeLevel->update($data);

        return redirect()->route('user.grade-levels.show', ['userId' => $userId, 'id' => $gradeLevel->id])
            ->with('success', 'Tingkat kelas berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $gradeLevel->delete();

        return redirect()->route('user.grade-levels.index', ['userId' => $userId])
            ->with('success', 'Tingkat kelas berhasil dihapus.');
    }
}
