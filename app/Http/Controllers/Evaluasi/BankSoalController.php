<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Concerns\AuthorizesAcademicScope;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Soal;
use App\Models\Subject;
use App\Models\User;
use App\Services\Evaluasi\TeachingScopeService;
use App\Services\KurikulumAccess;
use App\Services\SubjectGroupResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankSoalController extends Controller
{
    use AuthorizesAcademicScope;

    public function __construct(private readonly TeachingScopeService $teachingScope) {}

    /**
     * Mapel yang boleh dipilih untuk membuat/mengubah bank soal.
     * Hanya mapel yang diamanahkan SK Pembagian Tugas aktif.
     */
    private function scopedSubjects(Request $request, ?string $academicYearId = null)
    {
        return $this->teachingScope->subjects(
            $request->user(),
            $request->attributes->get('schoolContextId'),
            $academicYearId
        );
    }

    /**
     * Konteks SK untuk ditampilkan di form: nomor SK, mapel, dan rombel.
     */
    private function scopeSummary(Request $request): array
    {
        return $this->teachingScope->summary(
            $request->user(),
            $request->attributes->get('schoolContextId'),
            null
        );
    }

    /**
     * Tolak mapel/kelas yang tidak ada pada SK aktif guru.
     *
     * @throws ValidationException
     */
    private function assertWithinDecree(Request $request, ?string $subjectId, ?string $gradeLevelId): void
    {
        $user = $request->user();
        $schoolId = $request->attributes->get('schoolContextId');

        if (! $this->teachingScope->allowsSubject($user, $schoolId, null, $subjectId)) {
            throw ValidationException::withMessages([
                'subject_id' => 'Mata pelajaran ini tidak ada pada SK Pembagian Tugas aktif Anda. '
                    .'Minta Waka menambahkannya ke SK terlebih dahulu, atau pilih mapel yang memang Anda ajar.',
            ]);
        }

        if (! $this->teachingScope->allowsGradeLevel($user, $schoolId, null, $subjectId, $gradeLevelId)) {
            throw ValidationException::withMessages([
                'grade_level_id' => 'Kelas ini tidak Anda ajar untuk mata pelajaran tersebut menurut SK aktif.',
            ]);
        }
    }

    /**
     * Apakah user termasuk pengelola yang tidak dibatasi SK
     * (tim kurikulum / super admin)? Dipakai hanya untuk menandai di UI.
     */
    private function bypassesDecree(Request $request): bool
    {
        $user = $request->user();

        return app(KurikulumAccess::class)->canAccessAllBankSoal($user)
            || $user->hasRole('Super Admin');
    }

    /**
     * Display a paginated list of BankSoal for the current school context.
     */
    /**
     * Akses baca bank: tim/TU, owner, atau bank accessible (publik/internal).
     */
    private function authorizeBankView(Request $request, BankSoal $bank): void
    {
        $user = $request->user();

        if (app(KurikulumAccess::class)->canAccessAllBankSoal($user)) {
            return;
        }

        $accessible = BankSoal::query()
            ->accessibleBy($user->id, $request->attributes->get('schoolContextId'))
            ->whereKey($bank->id)
            ->exists();

        if (! $accessible) {
            abort(403, 'Anda tidak berwenang mengakses bank soal ini.');
        }
    }

    /**
     * Kelola bank: owner/tim kurikulum, atau guru pada rumpun kewenangannya
     * dengan bank yang accessible.
     */
    private function authorizeBankManage(Request $request, BankSoal $bank): void
    {
        $user = $request->user();

        if ($bank->owner_user_id === $user->id || $bank->created_by === $user->id) {
            return;
        }

        $access = app(KurikulumAccess::class);

        if ($access->isKurikulumTeam($user)) {
            return;
        }

        // TU/Kepala TU boleh mengelola bank internal pada satuan pendidikannya.
        // Bank lintas sekolah/terpusat tetap tidak otomatis dapat diedit.
        if ($access->isTataUsaha($user)
            && $bank->shared_scope === 'internal_school'
            && $bank->school_id
            && (string) $bank->school_id === (string) $request->attributes->get('schoolContextId')) {
            return;
        }

        $resolver = app(SubjectGroupResolver::class);
        $bankGroup = $bank->subject ? $resolver->groupForSubject($bank->subject) : null;
        $accessible = BankSoal::query()
            ->accessibleBy($user->id, $request->attributes->get('schoolContextId'))
            ->whereKey($bank->id)
            ->exists();

        if ($bankGroup && $accessible && $resolver->groupsForTeacher($user)->contains('id', $bankGroup->id)) {
            return;
        }

        abort(403, 'Anda tidak berwenang mengelola bank soal ini.');
    }

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        // Scope daftar = SATU sumber dengan guard detail (accessibleBy):
        // milik sendiri, publik/central/public_pool, internal satuan, dan rumpun kewenangan.
        $baseQuery = BankSoal::query()->accessibleBy($userId, $schoolId);

        $query = (clone $baseQuery)
            ->withCount(['soal' => fn ($q) => $q->where('status', 'approved')])
            ->with(['school', 'subject', 'owner', 'creator']);

        if ($request->filled('search')) {
            $query->where('nama', 'like', "%{$request->search}%");
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('jenis_soal')) {
            $query->where('jenis_soal', $request->jenis_soal);
        }
        if ($request->filled('shared_scope')) {
            $query->where('shared_scope', $request->shared_scope);
        }
        if ($request->has('is_public') && $request->input('is_public') !== '') {
            $query->where('is_public', (bool) $request->boolean('is_public'));
        }
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        $banks = $query->orderByDesc('updated_at')->paginate(15)->withQueryString();

        $subjects = $this->scopedSubjects($request);
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $activeYear = $academicYears->firstWhere('is_active', true);
        $scope = $this->scopeSummary($request);

        $statistics = [
            'total' => (clone $baseQuery)->count(),
            'soal' => DB::table('soal')
                ->whereIn('bank_soal_id', (clone $baseQuery)->select('id'))
                ->whereNull('deleted_at')
                ->where('status', 'approved')
                ->count(),
            'publik' => (clone $baseQuery)->where(function ($q) {
                $q->where('is_public', true)->orWhere('shared_scope', 'public_pool');
            })->count(),
            'tahun_ajaran_ini' => $activeYear
                ? (clone $baseQuery)->where('academic_year_id', $activeYear->id)->count()
                : 0,
        ];

        return view('evalusi.bank-soal.index', compact(
            'banks', 'subjects', 'academicYears', 'statistics', 'activeYear', 'scope', 'userId'
        ));
    }

    /**
     * Show the form for creating a new BankSoal.
     */
    public function create(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        // SK menentukan mapel yang boleh dipilih; tidak ada daftar mapel penuh.
        $subjects = $this->scopedSubjects($request);
        $gradeLevels = $this->teachingScope->assignments($request->user(), $schoolId, null)
            ->map(fn ($a) => $a->studyGroup?->grade_level_id)
            ->filter()
            ->unique()
            ->pipe(fn ($ids) => GradeLevel::query()
                ->whereIn('id', $ids)
                ->orderBy('level')
                ->get());

        $scope = $this->scopeSummary($request);
        $bypassDecree = $this->bypassesDecree($request);

        // Suggest current user as owner
        $currentUser = User::where('id', $userId)->first();

        // Available shared_scope options
        $sharedScopes = [
            'private' => 'Privat (hanya pemilik)',
            'internal_school' => 'Internal Sekolah',
            'public_pool' => 'Kolom Publik (global)',
        ];

        // Jenis soal options
        $jenisSoal = [
            'pilihan_ganda' => 'Pilihan Ganda',
            'multiple_choice_complex' => 'Multiple Choice Complex',
            'benar_salah' => 'Benar/Salah',
            'menjodohkan' => 'Menjodohkan',
            'isian_singkat' => 'Isian Singkat',
            'uraian' => 'Uraian',
            'campuran' => 'Campuran',
        ];

        // Tingkat kesulitan
        $tingkatKesulitan = [
            'mudah' => 'Mudah',
            'sedang' => 'Sedang',
            'sulit' => 'Sulit',
            'campuran' => 'Campuran',
        ];

        return view('evalusi.bank-soal.create', compact(
            'subjects',
            'currentUser',
            'sharedScopes',
            'jenisSoal',
            'tingkatKesulitan',
            'gradeLevels',
            'scope',
            'bypassDecree',
            'userId'
        ));
    }

    /**
     * Store a newly created BankSoal.
     */
    public function store(Request $request, string $userId)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:2000',
            'subject_id' => 'required|exists:subjects,id',
            'fase' => 'nullable|string|max:5',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'jenis_soal' => 'required|in:pilihan_ganda,multiple_choice_complex,benar_salah,menjodohkan,isian_singkat,uraian,campuran',
            'bahasa' => 'nullable|in:latin,arab,gabungan',
            'tingkat_kesulitan_target' => 'required|in:mudah,sedang,sulit,campuran',
            'is_public' => 'nullable|boolean',
            'shared_scope' => 'required|in:private,internal_school,public_pool',
            'allow_cross_teacher_clone' => 'nullable|boolean',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');

        $this->assertWithinDecree($request, $validated['subject_id'], $validated['grade_level_id'] ?? null);

        $validated['school_id'] = $schoolId;
        $validated['owner_user_id'] = $userId;
        $validated['created_by'] = $userId;
        $validated['total_soal'] = 0;

        $bank = BankSoal::create($validated);

        // Attach Tujuan Pembelajaran if provided
        if ($request->filled('tp_ids') && is_array($request->tp_ids)) {
            $bank->tujuanPembelajaran()->sync($request->tp_ids);
        }

        return redirect()
            ->route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id])
            ->with('success', 'Bank Soal berhasil dibuat.');
    }

    /**
     * Display a single BankSoal with its Soal list.
     */
    public function show(Request $request, string $userId, string $id)
    {
        $bank = BankSoal::with(['school', 'subject', 'owner', 'creator', 'tujuanPembelajaran',
            'soal' => fn ($q) => $q->with('tujuanPembelajaran')
                ->orderBy('created_at')
                ->select('id', 'bank_soal_id', 'tipe_soal', 'bahasa', 'pertanyaan',
                    'status', 'bobot_default', 'level_kognitif', 'level_kognitif_rumpun',
                    'bloom_confidence', 'created_at')])
            ->findOrFail($id);

        $this->authorizeBankView($request, $bank);

        $scope = $this->scopeSummary($request);

        return view('evalusi.bank-soal.show', compact('bank', 'scope', 'userId'));
    }

    /**
     * Show the edit form for an existing BankSoal.
     */
    public function edit(Request $request, string $userId, string $id)
    {
        $bank = BankSoal::with([
            'subject:id,name',
            'soal' => fn ($q) => $q->orderBy('created_at')->select(
                'id', 'bank_soal_id', 'tipe_soal', 'pertanyaan', 'status',
                'tingkat_kesulitan_estimasi', 'level_kognitif', 'level_kognitif_rumpun', 'created_at'
            ),
        ])->findOrFail($id);

        $this->authorizeBankManage($request, $bank);

        $schoolId = request()->attributes->get('schoolContextId');

        // Bank lama bisa saja memakai mapel di luar SK saat ini; mapel tersebut
        // tetap ditampilkan agar tidak hilang diam-diam saat disimpan ulang.
        $subjects = $this->scopedSubjects($request)
            ->when($bank->subject && ! $this->teachingScope->allowsSubject(
                $request->user(), $schoolId, null, $bank->subject_id
            ), fn ($rows) => $rows->push($bank->subject));

        $gradeLevels = GradeLevel::where(fn ($q) => $q->where('school_id', $schoolId)->orWhereNull('school_id'))->orderBy('level')->get();

        $scope = $this->scopeSummary($request);
        $bypassDecree = $this->bypassesDecree($request);

        $sharedScopes = [
            'private' => 'Privat (hanya pemilik)',
            'internal_school' => 'Internal Sekolah',
            'public_pool' => 'Kolom Publik (global)',
        ];

        $jenisSoal = [
            'pilihan_ganda' => 'Pilihan Ganda',
            'multiple_choice_complex' => 'Multiple Choice Complex',
            'benar_salah' => 'Benar/Salah',
            'menjodohkan' => 'Menjodohkan',
            'isian_singkat' => 'Isian Singkat',
            'uraian' => 'Uraian',
            'campuran' => 'Campuran',
        ];

        $tingkatKesulitan = [
            'mudah' => 'Mudah',
            'sedang' => 'Sedang',
            'sulit' => 'Sulit',
            'campuran' => 'Campuran',
        ];

        return view('evalusi.bank-soal.edit', compact(
            'bank',
            'subjects',
            'sharedScopes',
            'jenisSoal',
            'tingkatKesulitan',
            'gradeLevels',
            'scope',
            'bypassDecree',
            'userId'
        ));
    }

    /**
     * Update an existing BankSoal.
     */
    public function update(Request $request, string $userId, string $id)
    {
        $bank = BankSoal::findOrFail($id);

        $this->authorizeBankManage($request, $bank);

        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:2000',
            'subject_id' => 'required|exists:subjects,id',
            'fase' => 'nullable|string|max:5',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'jenis_soal' => 'required|in:pilihan_ganda,multiple_choice_complex,benar_salah,menjodohkan,isian_singkat,uraian,campuran',
            'bahasa' => 'nullable|in:latin,arab,gabungan',
            'tingkat_kesulitan_target' => 'required|in:mudah,sedang,sulit,campuran',
            'is_public' => 'nullable|boolean',
            'shared_scope' => 'required|in:private,internal_school,public_pool',
            'allow_cross_teacher_clone' => 'nullable|boolean',
        ]);

        // Mapel boleh diganti, tetapi hanya ke mapel yang ada di SK aktif.
        $this->assertWithinDecree($request, $validated['subject_id'], $validated['grade_level_id'] ?? null);

        $bank->update($validated);

        // Sync Tujuan Pembelajaran
        if ($request->filled('tp_ids') && is_array($request->tp_ids)) {
            $bank->tujuanPembelajaran()->sync($request->tp_ids);
        } else {
            $bank->tujuanPembelajaran()->detach();
        }

        return redirect()
            ->route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id])
            ->with('success', 'Bank Soal berhasil diperbarui.');
    }

    /**
     * Remove the specified BankSoal.
     */
    public function destroy(Request $request, string $userId, string $id)
    {
        $bank = BankSoal::findOrFail($id);

        $this->authorizeBankManage($request, $bank);

        $bank->delete();

        return redirect()
            ->route('user.bank-soal.index', ['userId' => $userId])
            ->with('success', 'Bank Soal berhasil dihapus.');
    }

    /**
     * API: Return Soal list within a BankSoal (JSON).
     */
    public function soalList(Request $request, string $userId, string $bankId)
    {
        $bank = BankSoal::findOrFail($bankId);

        $this->authorizeBankView($request, $bank);

        $soals = Soal::where('bank_soal_id', $bankId)
            ->orderBy('created_at')
            ->select('id', 'bank_soal_id', 'tipe_soal', 'pertanyaan', 'status', 'bobot_default')
            ->get();

        return response()->json($soals);
    }

    /**
     * API: Clone a BankSoal to another subject or reuse in same school.
     */
    public function clone(Request $request, string $userId, string $id)
    {
        $source = BankSoal::findOrFail($id);

        $this->authorizeBankManage($request, $source);

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'nama_baru' => 'required|string|max:150',
        ]);

        $this->assertWithinDecree($request, $validated['subject_id'], null);

        $schoolId = $request->attributes->get('schoolContextId');

        $cloned = BankSoal::create([
            'school_id' => $schoolId,
            'subject_id' => $validated['subject_id'],
            'fase' => $source->fase,
            'nama' => $validated['nama_baru'],
            'deskripsi' => $source->deskripsi,
            'jenis_soal' => $source->jenis_soal,
            'tingkat_kesulitan_target' => $source->tingkat_kesulitan_target,
            'is_public' => false,
            'shared_scope' => 'private',
            'owner_user_id' => $userId,
            'created_by' => $userId,
            'total_soal' => 0,
        ]);

        return redirect()
            ->route('user.bank-soal.show', ['userId' => $userId, 'id' => $cloned->id])
            ->with('success', 'Bank Soal berhasil di-cloning.');
    }
}
