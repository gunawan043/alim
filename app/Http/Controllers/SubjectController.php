<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\User;
use App\Services\AcademicPeriodService;
use App\Services\KurikulumAccess;
use App\Services\PekanEfektifService;
use App\Services\SubjectHoursSynchronizer;
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
            // JP yang sudah diatur di Peta Kurikulum tidak boleh ditimpa oleh
            // nilai bawaan mapel; hanya baris baru / kosong yang diisi.
            $row = GradeLevelSubject::firstOrNew([
                'academic_year_id' => $activeAy?->id,
                'grade_level_id' => $grade->id,
                'subject_id' => $subject->id,
                'track' => null,
            ]);

            if (! $row->exists || (int) $row->allocation_hours <= 0) {
                $row->allocation_hours = (int) ($subject->credit_hours ?: 2);
            }

            $row->is_active = true;
            $row->save();

            $synced++;
        }

        return $synced;
    }

    /**
     * Sebarkan JP master ke tingkat & rombel yang BELUM punya JP.
     *
     * JP master di halaman Edit Mapel adalah NILAI BAWAAN, bukan ketetapan.
     * Satu mapel boleh berbeda JP antar tingkat (mis. Matematika 3 JP di
     * kelas 7 dan 4 JP di kelas 9), dan perbedaan itu diatur di Peta
     * Kurikulum. Karena itu tingkat yang sudah punya alokasi sendiri TIDAK
     * boleh ditimpa — menimpanya akan menghapus perbedaan tersebut diam-diam.
     *
     * Yang diisi hanya tingkat dengan alokasi kosong (0 / null), sehingga
     * perubahan JP master tidak pernah merusak Peta Kurikulum.
     *
     * @return array{diisi: int, dipertahankan: int}
     */
    private function propagateHoursToActiveYear(Subject $subject): array
    {
        $activeAyId = AcademicYear::where('is_active', true)->value('id');
        if (! $activeAyId) {
            return ['diisi' => 0, 'dipertahankan' => 0];
        }

        $hours = (int) ($subject->credit_hours ?: 2);

        $jumlahTingkat = GradeLevelSubject::query()
            ->where('academic_year_id', $activeAyId)
            ->where('subject_id', $subject->id)
            ->count();

        if ($jumlahTingkat === 0) {
            return ['diisi' => 0, 'dipertahankan' => 0];
        }

        // Jalur sinkron yang sama dengan Peta Kurikulum & JP per Tingkat:
        // hanya tingkat yang belum punya JP yang diisi.
        $hasil = app(SubjectHoursSynchronizer::class)
            ->fillEmptyGradeLevels($subject->id, $activeAyId, $hours);

        return [
            'diisi' => $hasil['tingkat'],
            'dipertahankan' => $jumlahTingkat - $hasil['tingkat'],
        ];
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

        // JP per tingkat: sumber yang sama dengan Peta Kurikulum, supaya
        // pengaturan bisa dilakukan dari menu Mapel tanpa menggandakan mapel.
        $academicYear = AcademicYear::where('is_active', true)->first();

        // Minggu efektif dari Kalender → Pekan Efektif, sumber yang sama
        // dengan Peta Kurikulum.
        $semesterGanjil = $academicYear?->semester !== 'genap';
        $ringkasan = ($schoolId && $academicYear)
            ? app(PekanEfektifService::class)->summary($schoolId, $academicYear->id, $semesterGanjil ? 1 : 2)
            : null;
        $mingguEfektif = (int) ($ringkasan['minggu_efektif'] ?? 0);

        $gradeHours = GradeLevelSubject::query()
            ->where('subject_id', $subject->id)
            ->when($academicYear, fn ($q) => $q->forAcademicYear($academicYear->id)->orderByAcademicYearFirst())
            ->with('gradeLevel')
            ->get()
            ->unique('grade_level_id')
            ->filter(fn (GradeLevelSubject $row) => $row->gradeLevel !== null)
            ->sortBy(fn (GradeLevelSubject $row) => $row->gradeLevel->level ?? 0)
            ->map(fn (GradeLevelSubject $row) => [
                'grade_level_id' => $row->grade_level_id,
                'grade' => $row->gradeLevel->name,
                'level' => $row->gradeLevel->level,
                'fase' => $row->gradeLevel->fase,
                'hours' => (int) $row->allocation_hours,
                'rombel' => (int) StudyGroup::query()
                    ->where('grade_level_id', $row->grade_level_id)
                    ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
                    ->where('is_active', true)
                    ->count(),
            ])
            ->map(fn (array $row) => $row + ['jp_efektif' => $row['hours'] * $mingguEfektif])
            ->values();

        $canEditGradeHours = $this->canEditGradeHours(request()->user());

        return view('subjects.show', compact(
            'subject',
            'userId',
            'academicYear',
            'gradeHours',
            'canEditGradeHours'
        ));
    }

    /**
     * Simpan JP per tingkat dari menu Mapel.
     *
     * Menulis ke grade_level_subjects lewat SubjectHoursSynchronizer, jalur
     * yang sama dengan Peta Kurikulum, agar rombel dan penugasan guru ikut
     * menyesuaikan.
     *
     * Format: jp[grade_level_id] = jumlah JP per minggu.
     */
    public function saveGradeHours(Request $request, string $userId, string $id)
    {
        $subject = Subject::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $subject->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        abort_unless(
            $this->canEditGradeHours($request->user()),
            403,
            'Hanya tim kurikulum yang dapat mengatur JP per tingkat.'
        );

        $data = $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'jp' => 'required|array',
            'jp.*' => 'nullable|integer|min:0|max:40',
        ]);

        $academicYearId = $data['academic_year_id'] ?? AcademicYear::where('is_active', true)->value('id');

        if (! $academicYearId) {
            return back()->with('error', 'Tahun ajaran aktif belum ditentukan.');
        }

        app(AcademicPeriodService::class)->assertNotLocked($academicYearId, 'JP per tingkat');

        $synchronizer = app(SubjectHoursSynchronizer::class);
        $rombel = 0;
        $guru = 0;

        foreach ($data['jp'] as $gradeLevelId => $hours) {
            $hasil = $synchronizer->applyGradeLevelHours(
                $subject->id,
                (string) $gradeLevelId,
                $academicYearId,
                (int) $hours,
            );

            $rombel += $hasil['rombel'];
            $guru += $hasil['guru'];
        }

        $pesan = 'JP per tingkat tersimpan.';
        if ($rombel > 0 || $guru > 0) {
            $pesan .= " Tersinkron ke {$rombel} rombel dan {$guru} penugasan guru.";
        }

        return back()->with('success', $pesan);
    }

    /**
     * Hak mengatur JP per tingkat. Disamakan dengan editor Peta Kurikulum.
     */
    private function canEditGradeHours(?User $user): bool
    {
        return $user !== null && app(KurikulumAccess::class)->isKurikulumEditor($user);
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

        // JP master adalah nilai bawaan. Hanya tingkat tanpa JP yang diisi;
        // JP yang sudah diatur di Peta Kurikulum dibiarkan apa adanya.
        $hasil = $hoursChanged ? $this->propagateHoursToActiveYear($subject) : ['diisi' => 0, 'dipertahankan' => 0];

        $message = 'Mata pelajaran berhasil diperbarui.';
        if ($hoursChanged) {
            if ($hasil['diisi'] > 0) {
                $message .= " JP {$data['credit_hours']} diisi ke {$hasil['diisi']} tingkat yang belum punya JP.";
            }

            if ($hasil['dipertahankan'] > 0) {
                $message .= " JP {$hasil['dipertahankan']} tingkat lain tidak diubah karena sudah diatur di Peta Kurikulum.";
            }

            if ($hasil['diisi'] === 0 && $hasil['dipertahankan'] === 0) {
                $message .= ' Mapel belum terpasang di tingkat mana pun — JP akan dipakai saat diterapkan.';
            }
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
