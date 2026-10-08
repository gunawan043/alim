<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\GradeLevel;
use App\Models\PerangkatPembelajaran;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;

/**
 * Perangkat Pembelajaran — fondasi perencanaan pembelajaran yang disiapkan
 * dari ATP, kelas/mapel/guru pada tahun ajaran & semester yang sama.
 *
 * Desain pembelajarannya dirancang mengikuti prinsip Pembelajaran Mendalam
 * (berkesadaran, bermakna, menggembirakan) melalui bagian-bagian tetap:
 * pertanyaan pemantik → pemahaman bermakna → pengalaman memahami,
 * mengaplikasi, merefleksi → konteks nyata → asesmen → diferensiasi.
 */
class PerangkatPembelajaranController extends Controller
{
    public function __construct(private readonly KurikulumAccess $access) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = $request->user();

        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('created_at')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $academicYearId = $request->input('academic_year_id', $activeAy?->id);
        $academicYear = $academicYears->firstWhere('id', $academicYearId);

        $semester = $request->input('semester', $academicYear?->semester === 'genap' ? 'genap' : 'ganjil');
        if (! in_array($semester, ['ganjil', 'genap'], true)) {
            $semester = 'ganjil';
        }

        $perangkatList = PerangkatPembelajaran::query()
            ->with(['subject:id,name,code', 'gradeLevel:id,name', 'studyGroup:id,name', 'atp:id,total_jp,status', 'teacher:id,name'])
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->filled('study_group_id'), fn ($q) => $q->where('study_group_id', $request->study_group_id))
            ->orderByDesc('created_at')
            ->get();

        $isKurikulumTeam = $this->access->isKurikulumTeam($user);

        // ATP yang boleh dipakai user: semua (tim kurikulum) atau mapel yang diampu.
        $atpQuery = AlurTujuanPembelajaran::query()
            ->with(['subject:id,name,code', 'gradeLevel:id,name'])
            ->bySchool($schoolId)
            ->byAcademicYear($academicYearId)
            ->bySemester($semester)
            ->orderBy('subject_id');

        if (! $isKurikulumTeam) {
            $taughtSubjectIds = TeachingAssignment::query()
                ->where('teacher_id', $user->id)
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'active')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->pluck('subject_id')
                ->unique()
                ->values()
                ->all();

            $atpQuery->whereIn('subject_id', $taughtSubjectIds ?: ['-']);
        }

        $atpOptions = $atpQuery->get();

        $studyGroups = StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('academic_year_id', $academicYearId)
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level_id']);

        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('kurikulum.perangkat.index', compact(
            'userId',
            'academicYears',
            'academicYearId',
            'semester',
            'perangkatList',
            'atpOptions',
            'studyGroups',
            'subjects',
            'isKurikulumTeam'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $validated = $request->validate([
            'atp_id' => 'required|exists:alur_tujuan_pembelajaran,id',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'judul' => 'required|string|max:255',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $atp = AlurTujuanPembelajaran::with('subject')->findOrFail($validated['atp_id']);
        $this->authorizeManage($request, $atp->subject_id, $atp->academic_year_id, $schoolId);

        PerangkatPembelajaran::create([
            'school_id' => $atp->school_id,
            'academic_year_id' => $atp->academic_year_id,
            'semester' => $atp->semester,
            'subject_id' => $atp->subject_id,
            'grade_level_id' => $atp->grade_level_id,
            'study_group_id' => $validated['study_group_id'] ?? null,
            'atp_id' => $atp->id,
            'teacher_id' => $request->user()->id,
            'judul' => $validated['judul'],
            'status' => PerangkatPembelajaran::STATUS_DRAFT,
            'desain' => PerangkatPembelajaran::defaultDesain(),
            'catatan' => $validated['catatan'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('user.kurikulum.perangkat.index', ['userId' => $userId, 'subject_id' => $atp->subject_id])
            ->with('success', 'Perangkat pembelajaran berhasil dibuat dari ATP '.($atp->subject?->name ?? '').'.');
    }

    public function show(Request $request, string $userId, string $id)
    {
        $perangkat = PerangkatPembelajaran::with([
            'subject', 'gradeLevel', 'studyGroup', 'teacher', 'creator',
            'atp.items.tujuanPembelajaran',
        ])->findOrFail($id);

        $schoolId = $request->attributes->get('schoolContextId');
        if ($schoolId && $perangkat->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $studyGroups = StudyGroup::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('academic_year_id', $perangkat->academic_year_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('kurikulum.perangkat.show', compact('userId', 'perangkat', 'studyGroups'));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $perangkat = PerangkatPembelajaran::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        $this->authorizeManage($request, $perangkat->subject_id, $perangkat->academic_year_id, $schoolId);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'study_group_id' => 'nullable|exists:study_groups,id',
            'status' => 'required|in:draft,final',
            'catatan' => 'nullable|string|max:2000',
            'desain' => 'nullable|array',
            'desain.*' => 'nullable|string|max:5000',
        ]);

        // Hanya bagian desain yang dikenal yang disimpan (struktur Pembelajaran Mendalam).
        $desain = PerangkatPembelajaran::defaultDesain();
        foreach (array_keys($desain) as $key) {
            $value = $validated['desain'][$key] ?? null;
            $desain[$key] = filled($value) ? (string) $value : null;
        }

        $perangkat->update([
            'judul' => $validated['judul'],
            'study_group_id' => $validated['study_group_id'] ?? null,
            'status' => $validated['status'],
            'catatan' => $validated['catatan'] ?? null,
            'desain' => $desain,
        ]);

        return back()->with('success', 'Perangkat pembelajaran berhasil disimpan.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $perangkat = PerangkatPembelajaran::findOrFail($id);
        $schoolId = $request->attributes->get('schoolContextId');
        $this->authorizeManage($request, $perangkat->subject_id, $perangkat->academic_year_id, $schoolId);

        $perangkat->delete();

        return redirect()
            ->route('user.kurikulum.perangkat.index', ['userId' => $userId])
            ->with('success', 'Perangkat pembelajaran berhasil dihapus.');
    }

    private function authorizeManage(Request $request, string $subjectId, string $academicYearId, ?string $schoolId): void
    {
        if (! $this->access->canManageSubject($request->user(), $subjectId, $academicYearId, $schoolId)) {
            abort(403, 'Anda tidak berwenang mengelola perangkat untuk mata pelajaran ini.');
        }
    }
}
