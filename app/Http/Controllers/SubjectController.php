<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Support\SubjectArabicName;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = Subject::with('school');

        $activeAyId = AcademicYear::where('is_active', true)->value('id');
        $query->withCount([
            'gradeLevelSubjects as grade_level_count' => fn ($q) => $q->when(
                $activeAyId,
                fn ($qq) => $qq->where('academic_year_id', $activeAyId)
            ),
        ]);

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        } elseif ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%");
            });
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        $subjects = $query->orderBy('school_id')->orderBy('name')->get();

        // Kelompok mapel disimpan eksplisit di kolom `kelompok`.
        $grouped = $subjects->groupBy(fn ($s) => in_array($s->kelompok, ['agama', 'bahasa', 'umum'], true)
            ? $s->kelompok
            : 'umum');

        $kelompokLabels = [
            'agama' => ['label' => 'Mapel Agama',      'icon' => 'ri-moon-line',        'color' => 'primary'],
            'bahasa' => ['label' => 'Mapel Bahasa',     'icon' => 'ri-quill-pen-line',   'color' => 'success'],
            'umum' => ['label' => 'Mapel Umum',        'icon' => 'ri-global-line',      'color' => 'warning'],
        ];

        $schools = School::orderBy('name')->get();

        $stats = [
            'total' => $subjects->count(),
            'aktif' => $subjects->where('is_active', true)->count(),
            'nonaktif' => $subjects->where('is_active', false)->count(),
            'total_jp' => (int) $subjects->sum('credit_hours'),
        ];

        return view('subjects.index', compact('subjects', 'schools', 'userId', 'grouped', 'kelompokLabels', 'stats'));
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

        return view('subjects.create', compact('schools', 'userId', 'schoolContext'));
    }

    /**
     * Form tambah MAPEL PEMADATAN (diatur satuan pendidikan sendiri).
     */
    public function createPemadatan(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $grades = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('level')
            ->get(['id', 'level', 'name', 'fase']);

        return view('subjects.pemadatan', compact('userId', 'grades', 'schoolId'));
    }

    /**
     * Simpan mapel pemadatan + alokasi jam per tingkat (bebas diatur sekolah).
     */
    public function storePemadatan(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $data = $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'name_ar' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'allocations' => 'nullable|array',
            'allocations.*' => 'nullable|integer|min:0|max:40',
        ]);

        $subject = Subject::updateOrCreate(
            ['school_id' => $schoolId, 'code' => strtoupper($data['code'])],
            [
                'name' => $data['name'],
                'name_ar' => filled($data['name_ar'] ?? null)
                    ? $data['name_ar']
                    : SubjectArabicName::resolve($data['name']),
                'category' => 'lokal',
                'source' => 'pemadatan',
                'description' => $data['description'] ?? null,
                'is_active' => true,
            ]
        );

        $activeAy = AcademicYear::where('is_active', true)->first();

        foreach (($data['allocations'] ?? []) as $gradeLevelId => $hours) {
            $hours = (int) $hours;

            if ($hours <= 0) {
                $existing = GradeLevelSubject::where('grade_level_id', $gradeLevelId)
                    ->where('subject_id', $subject->id)
                    ->orderByAcademicYearFirst()
                    ->first();

                $hasOtherPeriods = $activeAy
                    && AcademicYear::where('id', '!=', $activeAy->id)->exists();

                if ($existing?->academic_year_id || ! $activeAy || ! $hasOtherPeriods) {
                    $existing?->delete();
                } elseif ($existing) {
                    // Baris lama lintas periode → sembunyikan hanya untuk TA aktif.
                    GradeLevelSubject::updateOrCreate(
                        [
                            'academic_year_id' => $activeAy->id,
                            'grade_level_id' => $gradeLevelId,
                            'subject_id' => $subject->id,
                            'track' => $existing->track,
                        ],
                        ['allocation_hours' => $existing->allocation_hours, 'is_active' => false]
                    );
                }

                continue;
            }

            GradeLevelSubject::updateOrCreate(
                [
                    'academic_year_id' => $activeAy?->id,
                    'grade_level_id' => $gradeLevelId,
                    'subject_id' => $subject->id,
                    'track' => null,
                ],
                ['allocation_hours' => $hours, 'is_active' => true]
            );
        }

        return redirect()->route('user.subjects.index', ['userId' => $userId])
            ->with('success', 'Mapel pemadatan "'.$subject->name.'" berhasil disimpan.');
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $rules = [
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'name_ar' => 'nullable|string|max:100',
            'category' => 'required|in:nasional,lokal,muatan_lokal',
            'kelompok' => 'required|in:agama,bahasa,umum',
            'credit_hours' => 'required|integer|min:1|max:20',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
        if (! $schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);
        $data['name_ar'] = filled($data['name_ar'] ?? null)
            ? $data['name_ar']
            : SubjectArabicName::resolve($data['name']);

        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $exists = Subject::where('school_id', $data['school_id'])
            ->where('code', $data['code'])
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Kode mata pelajaran ini sudah ada untuk sekolah tersebut.');
        }

        $subject = Subject::create($data);

        // Mapel master langsung diterapkan ke seluruh tingkat aktif pada
        // satuan pendidikan ini, lalu observer menyusulkan mapel rombel dan
        // buku nilai. Tanpa ini, mapel baru hanya ada di master dan tidak
        // pernah muncul di leger.
        $syncedGrades = $this->syncSubjectToGrades($subject);

        return redirect()->route('user.subjects.show', ['userId' => $userId, 'id' => $subject->id])
            ->with('success', 'Mata pelajaran berhasil disimpan dan diterapkan ke '.$syncedGrades.' tingkat aktif.');
    }

    /**
     * Terapkan mapel master ke tingkat aktif (grade_level_subjects).
     * Observer GradeLevelSubject akan menyusulkan mapel rombel + buku nilai.
     */
    private function syncSubjectToGrades(Subject $subject, ?array $gradeLevelIds = null): int
    {
        $activeAy = AcademicYear::where('is_active', true)->first();

        $grades = GradeLevel::query()
            ->where('school_id', $subject->school_id)
            ->where('is_active', true)
            ->when($gradeLevelIds !== null, fn ($q) => $q->whereIn('id', $gradeLevelIds))
            ->get();

        $synced = 0;

        foreach ($grades as $grade) {
            GradeLevelSubject::updateOrCreate(
                [
                    'academic_year_id' => $activeAy?->id,
                    'grade_level_id' => $grade->id,
                    'subject_id' => $subject->id,
                    'track' => null,
                ],
                [
                    'allocation_hours' => (int) ($subject->credit_hours ?: 2),
                    'is_active' => true,
                ]
            );

            $synced++;
        }

        return $synced;
    }

    /**
     * Teruskan perubahan JP master ke alokasi tingkat aktif
     * (grade_level_subjects), lalu ke rombel (study_group_subjects).
     * Tanpa ini, JP di halaman Edit Mapel hanya mengubah master dan tidak
     * pernah sampai ke jadwal / buku nilai / pekan efektif.
     */
    private function propagateHoursToActiveYear(Subject $subject): int
    {
        $activeAyId = AcademicYear::where('is_active', true)->value('id');
        if (! $activeAyId) {
            return 0;
        }

        $hours = (int) ($subject->credit_hours ?: 2);

        $gradeRows = GradeLevelSubject::query()
            ->where('academic_year_id', $activeAyId)
            ->where('subject_id', $subject->id)
            ->get();

        if ($gradeRows->isEmpty()) {
            return 0;
        }

        $gradeLevelIds = $gradeRows->pluck('grade_level_id');

        GradeLevelSubject::whereIn('id', $gradeRows->pluck('id'))->update(['allocation_hours' => $hours]);

        $groupIds = StudyGroup::query()
            ->whereIn('grade_level_id', $gradeLevelIds)
            ->where('school_id', $subject->school_id)
            ->where('is_active', true)
            ->pluck('id');

        if ($groupIds->isNotEmpty()) {
            StudyGroupSubject::query()
                ->whereIn('study_group_id', $groupIds)
                ->where('subject_id', $subject->id)
                ->update(['weekly_hours' => $hours]);
        }

        return $gradeRows->count();
    }

    public function syncGrades(Request $request, string $userId, string $id)
    {
        $subject = Subject::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $subject->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $synced = $this->syncSubjectToGrades($subject);

        return back()->with('success', "Mapel diterapkan ke {$synced} tingkat aktif. Buku nilai/leger akan menyusul otomatis.");
    }

    public function show(string $userId, string $id)
    {
        $subject = Subject::with('school')->findOrFail($id);
        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId && $subject->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        return view('subjects.show', compact('subject', 'userId'));
    }

    public function edit(string $userId, string $id)
    {
        $subject = Subject::findOrFail($id);
        $schoolId = request()->attributes->get('schoolContextId');
        if ($schoolId && $subject->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }
        $schools = School::orderBy('name')->get();
        $schoolContext = $schoolId ? School::find($schoolId) : null;

        return view('subjects.edit', compact('subject', 'schools', 'userId', 'schoolContext'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        // Mapel adalah data milik satuan pendidikan (per school_id): setiap
        // sekolah mengatur JP, kategori, dan kelompoknya sendiri tanpa saling
        // memengaruhi sekolah lain.
        $subject = Subject::findOrFail($id);

        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $subject->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $rules = [
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'name_ar' => 'nullable|string|max:100',
            'category' => 'required|in:nasional,lokal,muatan_lokal',
            'kelompok' => 'required|in:agama,bahasa,umum',
            'credit_hours' => 'required|integer|min:1|max:20',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
        if (! $schoolId) {
            $rules['school_id'] = 'required|exists:schools,id';
        }

        $data = $request->validate($rules);
        $data['name_ar'] = filled($data['name_ar'] ?? null)
            ? $data['name_ar']
            : SubjectArabicName::resolve($data['name']);

        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        $exists = Subject::where('school_id', $data['school_id'])
            ->where('code', $data['code'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Kode mata pelajaran ini sudah ada untuk sekolah tersebut.');
        }

        $hoursChanged = (int) $subject->credit_hours !== (int) $data['credit_hours'];

        $subject->update($data);

        // JP master yang berubah harus ikut ke tingkat & rombel tahun ajaran aktif.
        $syncedTingkat = $hoursChanged ? $this->propagateHoursToActiveYear($subject) : 0;

        $message = 'Mata pelajaran berhasil diperbarui.';
        if ($hoursChanged) {
            $message .= $syncedTingkat > 0
                ? " JP {$data['credit_hours']} diterapkan ke {$syncedTingkat} tingkat pada tahun ajaran aktif."
                : ' Mapel belum terpasang di tingkat mana pun — JP akan dipakai saat diterapkan.';
        }

        return redirect()->route('user.subjects.show', ['userId' => $userId, 'id' => $subject->id])
            ->with('success', $message);
    }

    /**
     * Simpan cepat nama mapel Indonesia + Arab dari halaman daftar mapel.
     */
    public function updateNames(Request $request, string $userId, string $id)
    {
        $subject = Subject::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $subject->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'name_ar' => 'nullable|string|max:100',
        ]);

        $validated['name_ar'] = filled($validated['name_ar'] ?? null)
            ? $validated['name_ar']
            : SubjectArabicName::resolve($validated['name']);

        $subject->update($validated);

        return back()->with('success', 'Nama mapel Indonesia dan Arab berhasil disimpan.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $subject = Subject::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $subject->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $subject->delete();

        return redirect()->route('user.subjects.index', ['userId' => $userId])
            ->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
