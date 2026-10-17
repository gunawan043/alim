<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Concerns\AuthorizesAcademicScope;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\KisiKisiItem;
use App\Models\KisiKisiSoal;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\TujuanPembelajaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KisiKisiController extends Controller
{
    use AuthorizesAcademicScope;

    /**
     * List kisi-kisi (filters by school context).
     */
    /**
     * Konteks otomatis kisi-kisi:
     * - Mapel & tingkat mengikuti penugasan guru (TeachingAssignment aktif).
     * - Tahun ajaran default = TA aktif; semester default = semester berjalan.
     * - Kurikulum/TU/KSP/Waka melihat seluruh mapel & tingkat satuan pendidikan.
     *
     * @return array<string, mixed>
     */
    protected function kisiContext(Request $request): array
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        $activeYear = AcademicYear::where('is_active', true)->orderByDesc('name')->first();
        $activeAyId = $activeYear?->id;
        $activeSemester = $activeYear?->semester ?: 'ganjil';

        $isPrivileged = app(\App\Services\KurikulumAccess::class)->canAccessAllBankSoal($user);

        $subjectIds = null;   // null = semua mapel sekolah
        $gradeLevelIds = null;

        if (! $isPrivileged) {
            $assignments = TeachingAssignment::query()
                ->where('teacher_id', $user->id)
                ->where('status', 'active')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($activeAyId, fn ($q) => $q->where('academic_year_id', $activeAyId))
                ->with('studyGroup:id,grade_level_id')
                ->get();

            $subjectIds = $assignments->pluck('subject_id')->filter()->unique()->values()->all();
            $gradeLevelIds = $assignments->pluck('studyGroup.grade_level_id')->filter()->unique()->values()->all();
        }

        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where(fn ($qq) => $qq->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->when($subjectIds !== null, fn ($q) => $q->whereIn('id', $subjectIds ?: ['-']))
            ->orderBy('name')
            ->get();

        $gradeLevels = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where(fn ($qq) => $qq->where('school_id', $schoolId)->orWhereNull('school_id')))
            ->when($gradeLevelIds !== null, fn ($q) => $q->whereIn('id', $gradeLevelIds ?: ['-']))
            ->orderBy('level')
            ->get();

        // TP untuk dropdown item — hanya mapel yang terlihat pada konteks.
        $tps = TujuanPembelajaran::query()
            ->whereIn('subject_id', $subjects->pluck('id')->all() ?: ['-'])
            ->orderBy('urutan')
            ->orderBy('kode_tp')
            ->get(['id', 'subject_id', 'grade_level_id', 'kode_tp', 'deskripsi', 'semester', 'academic_year_id']);

        return compact(
            'user', 'schoolId', 'activeYear', 'activeAyId', 'activeSemester',
            'isPrivileged', 'subjectIds', 'gradeLevelIds', 'subjects', 'gradeLevels', 'tps'
        );
    }

    /**
     * Validasi backend: guru hanya boleh memakai mapel yang menjadi penugasannya.
     * KSP/Waka/Kurikulum/TU (dan super admin) melihat seluruh mapel satuan.
     */
    protected function assertSubjectAccess(Request $request, string $subjectId): void
    {
        $ctx = $this->kisiContext($request);

        if ($ctx['isPrivileged'] || $ctx['subjectIds'] === null) {
            return;
        }

        if (! in_array($subjectId, $ctx['subjectIds'], true)) {
            abort(422, 'Mapel ini bukan bagian dari penugasan Anda pada tahun ajaran aktif.');
        }
    }

    /**
     * Normalisasi level kognitif C6 ke nilai enum yang tersimpan di database
     * (kolom enum lama memakai `C6_mencita`).
     */
    protected function normalizeLevelKognitif(?string $level): ?string
    {
        return $level === 'C6_mencipta' ? 'C6_mencita' : $level;
    }

    /**
     * Guard kisi-kisi: batas satuan pendidikan; perubahan hanya penyusun
     * atau tim kurikulum.
     */
    private function authorizeKisi(Request $request, KisiKisiSoal $kisi, bool $write = true): void
    {
        $this->ensureSchoolScope($request, $kisi->school_id, 'Kisi-kisi tidak ditemukan pada satuan pendidikan Anda.');

        if (! $write) {
            return;
        }

        $user = $request->user();

        if (app(\App\Services\KurikulumAccess::class)->isKurikulumTeam($user)) {
            return;
        }

        if ((string) $kisi->created_by === (string) $user->id) {
            return;
        }

        abort(403, 'Anda tidak berwenang mengubah kisi-kisi ini.');
    }

    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $baseQuery = KisiKisiSoal::where('school_id', $schoolId);

        $query = (clone $baseQuery)->with(['subject', 'gradeLevel', 'academicYear', 'items']);

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('jenis_ujian')) {
            $query->where('jenis_ujian', $request->jenis_ujian);
        }
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        $kisis = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        $ctx = $this->kisiContext($request);
        $subjects = $ctx['subjects'];
        $gradeLevels = $ctx['gradeLevels'];
        $years = AcademicYear::orderByDesc('name')->get();

        $activeYear = $ctx['activeYear'];

        $statistics = [
            'total' => (clone $baseQuery)->count(),
            'aktif' => (clone $baseQuery)->where('is_active', true)->count(),
            'tahun_ajaran_ini' => $activeYear ? (clone $baseQuery)->where('academic_year_id', $activeYear->id)->count() : 0,
            'jenis_ujian' => (clone $baseQuery)->distinct()->count('jenis_ujian'),
        ];

        return view('evalusi.kisi-kisi.index', compact('kisis', 'subjects', 'gradeLevels', 'years', 'statistics', 'activeYear'));
    }

    /**
     * Show create form.
     */
    public function create(string $userId, Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $ctx = $this->kisiContext($request);
        $subjects = $ctx['subjects'];
        $gradeLevels = $ctx['gradeLevels'];
        $years = AcademicYear::orderByDesc('name')->get();
        $banks = BankSoal::where('school_id', $schoolId)->with('subject')->get();
        $tps = $ctx['tps'];
        $defaultAyId = $ctx['activeAyId'];
        $defaultSemester = $ctx['activeSemester'];
        $isPrivileged = $ctx['isPrivileged'];

        $jenisUjian = [
            'sts' => 'STK / SAS (Sumatif Tengah Semester)',
            'sas' => 'Sumatif Akhir Semester',
            'ulangan_harian' => 'Ulangan Harian',
            'try_out' => 'Try Out',
            'latihan' => 'Latihan',
        ];

        return view('evalusi.kisi-kisi.create', compact(
            'subjects', 'gradeLevels', 'years', 'banks', 'jenisUjian',
            'tps', 'defaultAyId', 'defaultSemester', 'isPrivileged'
        ));
    }

    /**
     * Store kisi-kisi and its items.
     */
    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'jenis_ujian' => 'required|in:sts,sas,ulangan_harian,try_out,latihan',
            'judul' => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:2000',
            'tingkat_sekolah' => 'required|in:sd,smp,sma',
            'peminatan' => 'nullable|in:ipa,ips,bahasa',
            'total_soal_target' => 'required|integer|min:0',
            'total_bobot_target' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.tp_id' => 'required|exists:tujuan_pembelajaran,id',
            'items.*.level_kognitif' => 'required|in:C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencita,C6_mencipta',
            'items.*.materi' => 'nullable|string|max:255',
            'items.*.bentuk_soal' => 'nullable|in:pg,mcc,bs,jodoh,isian,uraian',
            'items.*.nomor_soal' => 'nullable|string|max:40',
            'items.*.jumlah_soal' => 'required|integer|min:1',
            'items.*.bobot_per_soal' => 'required|numeric|min:0',
        ]);

        // Validasi backend: mapel harus dalam penugasan guru (kecuali tim kurikulum/TU/KSP/Waka).
        $this->assertSubjectAccess($request, $validated['subject_id']);

        return DB::transaction(function () use ($validated, $userId, $request) {
            $schoolId = $request->attributes->get('schoolContextId');

            $kisi = new KisiKisiSoal;
            $kisi->fill(array_filter($validated, fn ($key) => ! in_array($key, ['items']), ARRAY_FILTER_USE_KEY));
            $kisi->subject_id = $validated['subject_id'];
            $kisi->school_id = $schoolId;
            $kisi->created_by = $userId;
            $kisi->save();

            foreach ($validated['items'] as $idx => $item) {
                $item['kisi_kisi_soal_id'] = $kisi->id;
                $item['level_kognitif'] = $this->normalizeLevelKognitif($item['level_kognitif'] ?? null);
                KisiKisiItem::create($item);
            }

            return redirect()->route('user.kisi-kisi-soal.show', ['userId' => $userId, 'kisiUuid' => $kisi->id])->with('success', 'Kisi-kisi berhasil dibuat.');
        });
    }

    /**
     * Show kisi-kisi detail.
     */
    public function show(Request $request, string $userId, string $id)
    {
        $kisi = KisiKisiSoal::with(['subject', 'gradeLevel', 'academicYear',
            'items.tujuanPembelajaran', 'items.kisiKisi'])
            ->findOrFail($id);

        $this->authorizeKisi($request, $kisi);

        return view('evalusi.kisi-kisi.show', compact('kisi'));
    }

    /**
     * Cetak kisi-kisi (dokumen resmi, siap A4).
     */
    public function cetak(Request $request, string $userId, string $id)
    {
        $kisi = KisiKisiSoal::with([
            'subject', 'gradeLevel', 'academicYear',
            'items.tujuanPembelajaran', 'items.kisiKisi',
        ])->findOrFail($id);

        // Cetak = akses baca.
        $this->authorizeKisi($request, $kisi, false);

        $school = \App\Models\School::find($kisi->school_id);
        $penyusun = \App\Models\User::find($kisi->created_by)?->name;

        return view('evalusi.kisi-kisi.cetak', compact('kisi', 'school', 'userId', 'penyusun'));
    }

    /**
     * Edit form.
     */
    public function edit(Request $request, string $userId, string $id)
    {
        $kisi = KisiKisiSoal::with(['subject', 'gradeLevel', 'academicYear', 'items.tujuanPembelajaran'])
            ->findOrFail($id);

        $this->authorizeKisi($request, $kisi);

        $ctx = $this->kisiContext($request);
        $subjects = $ctx['subjects'];
        $gradeLevels = $ctx['gradeLevels'];
        $years = AcademicYear::orderByDesc('name')->get();
        $banks = BankSoal::where('school_id', $ctx['schoolId'])->with('subject')->get();
        $tps = $ctx['tps'];
        $defaultAyId = $ctx['activeAyId'];
        $defaultSemester = $ctx['activeSemester'];
        $isPrivileged = $ctx['isPrivileged'];

        $jenisUjian = [
            'sts' => 'STK / SAS', 'sas' => 'SAK',
            'ulangan_harian' => 'Ulangan Harian', 'try_out' => 'Try Out', 'latihan' => 'Latihan',
        ];

        return view('evalusi.kisi-kisi.edit', compact(
            'kisi', 'subjects', 'gradeLevels', 'years', 'banks', 'jenisUjian',
            'tps', 'defaultAyId', 'defaultSemester', 'isPrivileged'
        ));
    }

    /**
     * Update kisi-kisi and items.
     */
    public function update(Request $request, string $userId, string $id)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'semester' => 'required|in:ganjil,genap',
            'jenis_ujian' => 'required|in:sts,sas,ulangan_harian,try_out,latihan',
            'judul' => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:2000',
            'total_soal_target' => 'required|integer|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:kisi_kisi_soal_items,id',
            'items.*.tp_id' => 'required|exists:tujuan_pembelajaran,id',
            'items.*.level_kognitif' => 'required|in:C1_mengingat,C2_memahami,C3_menerapkan,C4_menganalisis,C5_mengevaluasi,C6_mencita,C6_mencipta',
            'items.*.materi' => 'nullable|string|max:255',
            'items.*.bentuk_soal' => 'nullable|in:pg,mcc,bs,jodoh,isian,uraian',
            'items.*.nomor_soal' => 'nullable|string|max:40',
            'items.*.jumlah_soal' => 'required|integer|min:1',
            'items.*.bobot_per_soal' => 'required|numeric|min:0',
        ]);

        $this->assertSubjectAccess($request, $validated['subject_id']);

        return DB::transaction(function () use ($validated, $id, $request) {
            $kisi = KisiKisiSoal::findOrFail($id);

        $this->authorizeKisi($request, $kisi);
            $kisi->fill(array_filter($validated, fn ($key) => ! in_array($key, ['items']), ARRAY_FILTER_USE_KEY));
            $kisi->save();

            // Upsert or delete items
            $existingIds = [];
            foreach ($validated['items'] as $idx => $item) {
                $itemId = $item['id'] ?? null;
                if ($itemId) {
                    $record = KisiKisiItem::findOrFail($itemId);
                    $record->update([
                        'tp_id' => $item['tp_id'],
                        'level_kognitif' => $this->normalizeLevelKognitif($item['level_kognitif']),
                        'materi' => $item['materi'] ?? null,
                        'bentuk_soal' => $item['bentuk_soal'] ?? null,
                        'nomor_soal' => $item['nomor_soal'] ?? null,
                        'jumlah_soal' => $item['jumlah_soal'],
                        'bobot_per_soal' => $item['bobot_per_soal'],
                    ]);
                    $existingIds[] = $itemId;
                } else {
                    KisiKisiItem::create([
                        'kisi_kisi_soal_id' => $id,
                        'tp_id' => $item['tp_id'],
                        'level_kognitif' => $this->normalizeLevelKognitif($item['level_kognitif']),
                        'materi' => $item['materi'] ?? null,
                        'bentuk_soal' => $item['bentuk_soal'] ?? null,
                        'nomor_soal' => $item['nomor_soal'] ?? null,
                        'jumlah_soal' => $item['jumlah_soal'],
                        'bobot_per_soal' => $item['bobot_per_soal'],
                    ]);
                }
            }

            // Delete items that weren't in the update
            KisiKisiItem::where('kisi_kisi_soal_id', $id)
                ->whereNotIn('id', $existingIds)
                ->delete();

            return redirect()->back()->with('success', 'Kisi-kisi berhasil diperbarui.');
        });
    }

    /**
     * Delete kisi-kisi.
     */
    public function destroy(Request $request, string $userId, string $id)
    {
        $kisi = KisiKisiSoal::findOrFail($id);

        $this->authorizeKisi($request, $kisi);
        $kisi->delete();

        return redirect()->route('user.kisi-kisi-soal.index', ['userId' => $userId])->with('success', 'Kisi-kisi dihapus.');
    }
}
