<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaranItem;
use App\Models\CapaianPembelajaran;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;

/**
 * TP — Tujuan Pembelajaran. Diturunkan dari CP, dapat dibuat, diubah,
 * diurutkan, dan dikelola guru mapel terkait; menjadi dasar ATP.
 */
class TujuanPembelajaranController extends Controller
{
    public function __construct(private readonly KurikulumAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeAy?->id);
        $academicYear = $academicYears->firstWhere('id', $academicYearId);

        $semester = $request->input('semester', $academicYear?->semester === 'genap' ? 'genap' : 'ganjil');
        if (! in_array($semester, ['ganjil', 'genap'], true)) {
            $semester = 'ganjil';
        }

        $subjectId = $request->input('subject_id');

        $query = TujuanPembelajaran::query()
            ->with(['subject:id,name,code', 'gradeLevel:id,name,fase', 'capaianPembelajaran:id,fase,elemen'])
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->when($request->filled('grade_level_id'), fn ($q) => $q->where('grade_level_id', $request->grade_level_id))
            ->when($request->filled('fase'), fn ($q) => $q->where('fase', $request->fase))
            ->orderBy('subject_id')
            ->orderBy('urutan')
            ->orderBy('kode_tp');

        $tpList = $query->get();

        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $gradeLevels = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('level')
            ->get(['id', 'name', 'fase']);

        // CP untuk form (difilter di sisi klien berdasarkan mapel terpilih).
        $cpOptions = CapaianPembelajaran::query()
            ->active()
            ->forSchool($schoolId)
            ->orderBy('fase')
            ->orderBy('urutan')
            ->get(['id', 'subject_id', 'fase', 'elemen', 'deskripsi']);

        $usedTpIds = AlurTujuanPembelajaranItem::query()
            ->whereIn('tujuan_pembelajaran_id', $tpList->pluck('id'))
            ->pluck('tujuan_pembelajaran_id')
            ->unique()
            ->values()
            ->all();

        // Data CP untuk form (dipakai JS; hindari ekspresi kompleks di Blade).
        $cpOptionsJson = $cpOptions->map(fn ($cp) => [
            'id' => $cp->id,
            'subject_id' => $cp->subject_id,
            'fase' => $cp->fase,
            'elemen' => $cp->elemen,
            'deskripsi' => \Illuminate\Support\Str::limit($cp->deskripsi, 70),
        ])->values();

        $isKurikulumTeam = $this->access->isKurikulumTeam($request->user());
        $taughtSubjectIds = TeachingAssignment::query()
            ->where('teacher_id', $request->user()->id)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('subject_id')
            ->unique()
            ->values()
            ->all();

        $tpUpdateUrlTemplate = route('user.kurikulum.tp.update', ['userId' => $userId, 'id' => '__ID__']);

        return view('kurikulum.tp.index', compact(
            'userId',
            'academicYears',
            'academicYearId',
            'semester',
            'subjectId',
            'tpList',
            'subjects',
            'gradeLevels',
            'cpOptions',
            'cpOptionsJson',
            'usedTpIds',
            'isKurikulumTeam',
            'taughtSubjectIds',
            'tpUpdateUrlTemplate'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'capaian_pembelajaran_id' => 'nullable|exists:capaian_pembelajaran,id',
            'kode_tp' => 'required|string|max:20',
            'deskripsi' => 'required|string|max:5000',
            'elemen' => 'nullable|string|max:100',
            'fase' => 'nullable|string|max:5',
            'alokasi_waktu' => 'nullable|integer|min:1|max:100',
            'urutan' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $this->authorizeManage($request, $validated['subject_id'], $validated['academic_year_id'], $schoolId);

        // CP (bila dipilih) harus satu mapel + menyumbang fase/elemen.
        $cp = null;
        if (! empty($validated['capaian_pembelajaran_id'])) {
            $cp = CapaianPembelajaran::find($validated['capaian_pembelajaran_id']);
            if ($cp && $cp->subject_id !== $validated['subject_id']) {
                return back()->withInput()->with('error', 'CP yang dipilih bukan untuk mata pelajaran ini.');
            }
        }

        $gradeLevel = ! empty($validated['grade_level_id']) ? GradeLevel::find($validated['grade_level_id']) : null;

        $validated['school_id'] = $schoolId;
        $validated['fase'] = $validated['fase'] ?? $cp?->fase ?? $gradeLevel?->fase;
        $validated['elemen'] = $validated['elemen'] ?? $cp?->elemen;
        $validated['alokasi_waktu'] = $validated['alokasi_waktu'] ?? 2;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = $request->user()->id;

        if ($this->kodeTpExists($validated)) {
            return back()->withInput()->with('error', 'Kode TP sudah dipakai untuk mapel, jenjang, tahun ajaran, dan semester ini.');
        }

        TujuanPembelajaran::create($validated);

        return redirect()
            ->route('user.kurikulum.tp.index', ['userId' => $userId, 'subject_id' => $validated['subject_id'], 'academic_year_id' => $validated['academic_year_id'], 'semester' => $validated['semester']])
            ->with('success', 'Tujuan Pembelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, string $userId, string $id)
    {
        $tp = TujuanPembelajaran::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'capaian_pembelajaran_id' => 'nullable|exists:capaian_pembelajaran,id',
            'kode_tp' => 'required|string|max:20',
            'deskripsi' => 'required|string|max:5000',
            'elemen' => 'nullable|string|max:100',
            'fase' => 'nullable|string|max:5',
            'alokasi_waktu' => 'nullable|integer|min:1|max:100',
            'urutan' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $this->authorizeManage($request, $tp->subject_id, $tp->academic_year_id, $schoolId);

        $cp = null;
        if (! empty($validated['capaian_pembelajaran_id'])) {
            $cp = CapaianPembelajaran::find($validated['capaian_pembelajaran_id']);
            if ($cp && $cp->subject_id !== $validated['subject_id']) {
                return back()->withInput()->with('error', 'CP yang dipilih bukan untuk mata pelajaran ini.');
            }
        }

        $gradeLevel = ! empty($validated['grade_level_id']) ? GradeLevel::find($validated['grade_level_id']) : null;

        $validated['fase'] = $validated['fase'] ?? $cp?->fase ?? $gradeLevel?->fase;
        $validated['elemen'] = $validated['elemen'] ?? $cp?->elemen;
        $validated['alokasi_waktu'] = $validated['alokasi_waktu'] ?? 2;
        $validated['is_active'] = $request->boolean('is_active', true);

        if ($this->kodeTpExists($validated, $tp->id)) {
            return back()->withInput()->with('error', 'Kode TP sudah dipakai untuk mapel, jenjang, tahun ajaran, dan semester ini.');
        }

        $tp->update($validated);

        return redirect()
            ->route('user.kurikulum.tp.index', ['userId' => $userId, 'subject_id' => $tp->subject_id])
            ->with('success', 'Tujuan Pembelajaran berhasil diperbarui.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $tp = TujuanPembelajaran::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        $this->authorizeManage($request, $tp->subject_id, $tp->academic_year_id, $schoolId);

        if (AlurTujuanPembelajaranItem::where('tujuan_pembelajaran_id', $tp->id)->exists()) {
            return back()->with('error', 'TP masih dipakai pada ATP. Keluarkan dari ATP terlebih dahulu.');
        }

        $tp->delete();

        return redirect()
            ->route('user.kurikulum.tp.index', ['userId' => $userId])
            ->with('success', 'Tujuan Pembelajaran berhasil dihapus.');
    }

    /**
     * Urutkan TP naik/turun (berurutan dalam mapel + jenjang + TA + semester).
     */
    public function move(Request $request, string $userId, string $id)
    {
        $tp = TujuanPembelajaran::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        $this->authorizeManage($request, $tp->subject_id, $tp->academic_year_id, $schoolId);

        $direction = $request->input('direction') === 'up' ? 'up' : 'down';

        $ids = TujuanPembelajaran::query()
            ->where('subject_id', $tp->subject_id)
            ->where('academic_year_id', $tp->academic_year_id)
            ->where('semester', $tp->semester)
            ->when($tp->grade_level_id, fn ($q) => $q->where('grade_level_id', $tp->grade_level_id), fn ($q) => $q->whereNull('grade_level_id'))
            ->orderBy('urutan')
            ->orderBy('kode_tp')
            ->pluck('id')
            ->all();

        $index = array_search($tp->id, $ids, true);

        if ($index === false) {
            return back();
        }

        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

        if (isset($ids[$swapWith])) {
            [$ids[$index], $ids[$swapWith]] = [$ids[$swapWith], $ids[$index]];

            foreach ($ids as $position => $tpId) {
                TujuanPembelajaran::where('id', $tpId)->update(['urutan' => $position + 1]);
            }
        }

        return back()->with('success', 'Urutan TP diperbarui.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function kodeTpExists(array $data, ?string $exceptId = null): bool
    {
        return TujuanPembelajaran::query()
            ->where('subject_id', $data['subject_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('semester', $data['semester'])
            ->where('kode_tp', $data['kode_tp'])
            ->when($data['grade_level_id'] ?? null, fn ($q) => $q->where('grade_level_id', $data['grade_level_id']), fn ($q) => $q->whereNull('grade_level_id'))
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    private function authorizeManage(Request $request, string $subjectId, string $academicYearId, ?string $schoolId): void
    {
        if (! $this->access->canManageSubject($request->user(), $subjectId, $academicYearId, $schoolId)) {
            abort(403, 'Anda tidak berwenang mengelola TP untuk mata pelajaran ini.');
        }
    }
}
