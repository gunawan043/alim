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

    public function show(Request $request, string $userId, string $id)
    {
        $gradeLevel = GradeLevel::with('school')->findOrFail($id);
        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        // Periode yang dilihat: pilihan pengguna, selain itu TA aktif.
        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $selectedAy = $academicYears->firstWhere('id', $request->input('ay_filter')) ?? $activeAy;
        $isArchive = $activeAy && $selectedAy && (string) $selectedAy->id !== (string) $activeAy->id;
        $semester = $selectedAy?->semester === 'genap' ? 'genap' : 'ganjil';

        // ARSIP PERIODE: struktur versi TA terpilih + baris lama (NULL) sebagai fallback.
        // Baris shadow nonaktif (mapel dihapus untuk TA ini) menutupi baris lama,
        // lalu dibuang dari tampilan.
        $gradeLevelSubjects = GradeLevelSubject::with('subject')
            ->where('grade_level_id', $id)
            ->forAcademicYear($selectedAy?->id)
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

        // KKTP per mapel untuk semester aktif
        $kktpMap = SubjectKktp::where('grade_level_id', $id)
            ->when($selectedAy, fn ($q) => $q->where('academic_year_id', $selectedAy->id))
            ->where('semester', $semester)
            ->get()
            ->keyBy('subject_id');

        // Rombel tingkat ini — jurusan diisi dari sini.
        $studyGroups = StudyGroup::with('homeroomTeacher')
            ->where('grade_level_id', $id)
            ->where('school_id', $gradeLevel->school_id)
            ->when($selectedAy, fn ($q) => $q->where('academic_year_id', $selectedAy->id))
            ->orderBy('name')
            ->get();

        return view('grade-levels.show', compact(
            'gradeLevel', 'userId', 'gradeLevelSubjects', 'availableSubjects',
            'kktpMap', 'activeAy', 'selectedAy', 'semester', 'academicYears',
            'studyGroups', 'isArchive'
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
            // Kosong = mapel umum (semua rombel). Nilai jurusan = hanya
            // rombel dengan jurusan itu yang menerimanya.
            'track' => 'nullable|in:ipa,bahasa,ips',
        ]);

        $weeklyHours = (int) ($validated['allocation_hours'] ?? 0);
        // Penjurusan hanya untuk Kelas 11 & 12; tingkat lain selalu mapel umum.
        $track = $gradeLevel->raporSubjectsPerStudyGroup() ? ($validated['track'] ?: null) : null;
        $activeAy = $this->periodFromRequest($request);

        $existing = GradeLevelSubject::query()
            ->where('grade_level_id', $id)
            ->where('subject_id', $validated['subject_id'])
            ->where(fn ($q) => $q->where('track', $track)->orWhereNull('track'))
            ->forAcademicYear($activeAy?->id)
            ->orderByAcademicYearFirst()
            ->first();

        if ($existing) {
            // Baris versi TA berjalan yang pernah dinonaktifkan → aktifkan kembali (idempoten).
            if ($existing->academic_year_id && ! $existing->is_active) {
                $existing->update(['is_active' => true, 'allocation_hours' => $weeklyHours, 'track' => $track]);

                $propagated = $this->propagateSubjectToStudyGroups($gradeLevel, $existing->subject_id, $weeklyHours, $track);

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
            'track' => $track,
        ]);

        // Propagasi: mapel tingkat → rombel aktif pada tingkat yang sama
        // (dan jurusannya), agar langsung muncul di Buku Nilai, Jadwal,
        // Plotting, dan Leger.
        $propagated = $this->propagateSubjectToStudyGroups($gradeLevel, $validated['subject_id'], $weeklyHours, $track);

        $scope = $track ? ' ke rombel '.strtoupper($track) : '';

        return redirect()->route('user.grade-levels.show', ['userId' => $userId, 'id' => $id])
            ->with('success', $propagated > 0
                ? "Mapel berhasil ditambahkan ke tingkat ini{$scope} dan {$propagated} rombel aktif."
                : 'Mapel berhasil ditambahkan.');
    }

    /**
     * Periode yang sedang diatur: TA pilihan pengguna, selain itu TA aktif.
     */
    protected function periodFromRequest(Request $request): ?AcademicYear
    {
        if ($request->filled('ay_filter')) {
            $selected = AcademicYear::find($request->input('ay_filter'));

            if ($selected) {
                return $selected;
            }
        }

        return AcademicYear::where('is_active', true)->first();
    }

    /**
     * Sinkronkan mapel tingkat ke rombel aktif pada tingkat yang sama.
     * Mapel bertanda jurusan hanya masuk ke rombel dengan jurusan itu;
     * mapel umum masuk ke semua rombel. Baris yang pernah di-soft-delete
     * dihidupkan kembali; jam mengajar yang sudah diatur tidak ditimpa.
     */
    protected function propagateSubjectToStudyGroups(GradeLevel $gradeLevel, string $subjectId, int $weeklyHours = 0, ?string $track = null): int
    {
        $academicYearId = $this->periodFromRequest(request())?->id;

        $groups = StudyGroup::query()
            ->where('grade_level_id', $gradeLevel->id)
            ->where('school_id', $gradeLevel->school_id)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->where('is_active', true)
            ->get();

        $provisioner = app(\App\Services\Academic\RombelSubjectProvisioner::class);
        $row = new GradeLevelSubject(['track' => $track]);

        $count = 0;

        foreach ($groups as $group) {
            if (! $provisioner->appliesTo($row, $group)) {
                continue;
            }

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

    /**
     * Simpan penyesuaian JP per mapel untuk satu tingkat.
     * JP disimpan pada grade_level_subjects dan disebar ke mapel rombel
     * (study_group_subjects) agar leger/jadwal memakai angka yang sama.
     */
    public function updateSubjectHours(Request $request, string $userId, string $id)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'hours' => 'required|array',
            'hours.*' => 'nullable|integer|min:0|max:40',
        ]);

        $updated = 0;

        foreach ($validated['hours'] as $gradeLevelSubjectId => $hours) {
            $row = GradeLevelSubject::query()
                ->where('grade_level_id', $gradeLevel->id)
                ->whereKey($gradeLevelSubjectId)
                ->first();

            if (! $row) {
                continue;
            }

            // Satu sumber: JP tingkat disimpan lalu disebar ke rombel yang
            // menerima mapel itu (mengikuti jurusan).
            app(\App\Services\SubjectHoursSynchronizer::class)->applyGradeLevelHours(
                $row->subject_id,
                $row->grade_level_id,
                $row->academic_year_id,
                (int) $hours,
            );

            $updated++;
        }

        return back()->with('success', "JP berhasil diperbarui untuk {$updated} mapel.");
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

            // Nonaktifkan mapel pada rombel tingkat ini (riwayat nilai tidak dihapus).
            // Mapel umum tidak boleh ikut mati bila masih ada baris jurusan lain
            // dengan mapel yang sama pada tingkat ini.
            $masihAdaBarisLain = GradeLevelSubject::query()
                ->where('grade_level_id', $id)
                ->where('subject_id', $subjectId)
                ->where('is_active', true)
                ->whereKeyNot($gls->id)
                ->exists();

            if (! $masihAdaBarisLain) {
                $groupIds = StudyGroup::where('grade_level_id', $id)
                    ->where('school_id', $gradeLevel->school_id)
                    ->pluck('id');

                StudyGroupSubject::whereIn('study_group_id', $groupIds)
                    ->where('subject_id', $subjectId)
                    ->update(['is_active' => false]);
            }
        }

        return redirect()->route('user.grade-levels.show', ['userId' => $userId, 'id' => $id])
            ->with('success', 'Mapel berhasil dihapus.');
    }

    /**
     * Tetapkan jurusan satu rombel pada tingkat ini.
     *
     * Perubahan jurusan langsung menyusun ulang mapel rombel sesuai Peta:
     * mapel jurusan lama dilepas, mapel jurusan baru diaktifkan. Riwayat
     * nilai tidak dihapus.
     */
    public function updateRombelPeminatan(Request $request, string $userId, string $id, string $groupId)
    {
        $gradeLevel = GradeLevel::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $gradeLevel->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        // Penjurusan hanya ada di Kelas 11 & 12.
        abort_unless(
            $gradeLevel->raporSubjectsPerStudyGroup(),
            422,
            'Jurusan hanya berlaku untuk Kelas 11 dan 12.'
        );

        $studyGroup = StudyGroup::query()
            ->where('grade_level_id', $gradeLevel->id)
            ->where('school_id', $gradeLevel->school_id)
            ->findOrFail($groupId);

        $validated = $request->validate([
            'peminatan' => 'nullable|in:ipa,ips,bahasa',
        ]);

        $studyGroup->update(['peminatan' => $validated['peminatan'] ?: null]);

        $hasil = app(\App\Services\Academic\RombelSubjectProvisioner::class)->provision($studyGroup);

        return back()->with(
            'success',
            "Jurusan {$studyGroup->name} tersimpan"
            .($studyGroup->peminatanLabel() ? ' ('.strtoupper($studyGroup->peminatanLabel()).')' : '')
            .". {$hasil['assignments']} mapel diselaraskan."
        );
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
