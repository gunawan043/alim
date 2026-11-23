<?php

namespace App\Http\Controllers\Evaluasi;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Soal;
use App\Models\Subject;
use App\Models\TujuanPembelajaran;
use App\Services\Documents\DocumentVisualService;
use App\Services\Evaluasi\DocxQuestionParser;
use App\Services\Evaluasi\QuickSoalService;
use App\Services\Evaluasi\TeachingScopeService;
use App\Services\Pdf\DocumentPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Jalur cepat asesmen: Soal Saya, Cari Soal, impor Word, dan cetak langsung.
 */
class SoalSayaController extends Controller
{
    public function __construct(
        private readonly QuickSoalService $quickSoal,
        private readonly DocxQuestionParser $docxParser,
        private readonly TeachingScopeService $teachingScope,
    ) {}

    public function index(Request $request, string $userId)
    {
        $school = $this->school($request);
        $query = Soal::with(['bankSoal.subject:id,name', 'bankSoal.gradeLevel:id,name', 'options', 'creator:id,name'])
            ->where('dibuat_oleh', $userId)
            ->when($request->filled('q'), fn ($q) => $q->where('pertanyaan', 'like', '%'.$request->q.'%'))
            ->when($request->filled('jenis_asesmen'), fn ($q) => $q->where('jenis_asesmen', $request->jenis_asesmen))
            ->when($request->filled('tipe_soal'), fn ($q) => $q->where('tipe_soal', $request->tipe_soal))
            ->when($request->filled('subject_id'), fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('subject_id', $request->subject_id)))
            ->orderByDesc('created_at');

        $questions = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Soal::where('dibuat_oleh', $userId)->count(),
            'draft' => Soal::where('dibuat_oleh', $userId)->where('workflow_status', Soal::WORKFLOW_DRAFT)->count(),
            'review' => Soal::where('dibuat_oleh', $userId)->whereIn('workflow_status', [Soal::WORKFLOW_REVIEW, Soal::WORKFLOW_REVISI])->count(),
            'approved' => Soal::where('dibuat_oleh', $userId)->where('workflow_status', Soal::WORKFLOW_APPROVED)->count(),
            'formal' => Soal::where('dibuat_oleh', $userId)->whereIn('jenis_asesmen', [Soal::JENIS_ASESMEN_STS, Soal::JENIS_ASESMEN_SAS])->count(),
        ];

        $subjects = $this->subjects($school);
        $scope = $this->scopeSummary($school);

        return view('evalusi.soal-saya.index', compact('userId', 'school', 'questions', 'stats', 'subjects', 'scope'));
    }

    public function create(Request $request, string $userId)
    {
        $school = $this->school($request);
        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('name')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $selectedAyId = $request->input('academic_year_id', $activeAy?->id);
        $semester = $request->input('semester', $academicYears->firstWhere('id', $selectedAyId)?->semester ?? 'ganjil');

        $subjects = $this->subjects($school);
        $grades = $this->grades($school);

        $selectedSubjectId = $request->input('subject_id', $subjects->first()?->id);
        $tps = TujuanPembelajaran::query()
            ->where('school_id', $school->id)
            ->when($selectedSubjectId, fn ($q) => $q->where('subject_id', $selectedSubjectId))
            ->when($selectedAyId, fn ($q) => $q->where('academic_year_id', $selectedAyId))
            ->where('semester', in_array($semester, ['ganjil', 'genap'], true) ? $semester : 'ganjil')
            ->orderBy('urutan')
            ->limit(500)
            ->get(['id', 'kode_tp', 'deskripsi']);

        $scope = $this->scopeSummary($school);

        return view('evalusi.soal-saya.create', compact(
            'userId', 'school', 'academicYears', 'selectedAyId', 'semester',
            'subjects', 'grades', 'tps', 'selectedSubjectId', 'scope'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $school = $this->school($request);

        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'jenis_asesmen' => 'required|in:harian,formatif,sts,sas',
            'tipe_soal' => 'required|in:pg,bs,jodoh,isian,uraian',
            'bahasa' => 'nullable|in:latin,arab,gabungan',
            'pertanyaan' => 'required|string|max:10000',
            'pembahasan' => 'nullable|string|max:10000',
            'materi' => 'nullable|string|max:150',
            'tp_id' => 'nullable|exists:tujuan_pembelajaran,id',
            'tingkat_kesulitan_estimasi' => 'required|in:mudah,sedang,sulit',
            'waktu_estimasi_menit' => 'nullable|integer|min:1|max:120',
            'tags' => 'nullable|string|max:255',
            'correct_label' => 'nullable|string|max:2',
            'options' => 'nullable|array',
            'options.*.label' => 'nullable|string|max:2',
            'options.*.teks_opsi' => 'nullable|string|max:1000',
        ]);

        $this->assertWithinDecree($school, $data['subject_id'], $data['grade_level_id'] ?? null);

        $subject = Subject::where(fn ($q) => $q->where('school_id', $school->id)->orWhereNull('school_id'))
            ->findOrFail($data['subject_id']);
        $grade = $request->filled('grade_level_id') ? GradeLevel::find($request->grade_level_id) : null;
        $ay = AcademicYear::findOrFail($data['academic_year_id']);

        $options = collect($request->input('options', []))
            ->filter(fn ($option) => trim((string) ($option['teks_opsi'] ?? '')) !== '')
            ->values()
            ->all();

        if ($data['tipe_soal'] === 'pg' && count($options) < 2) {
            return back()->withInput()->with('error', 'Soal pilihan ganda minimal memiliki 2 opsi.');
        }

        if ($data['tipe_soal'] === 'pg' && $request->filled('correct_label')) {
            $labels = array_map(fn ($option) => strtoupper((string) ($option['label'] ?? '')), $options);
            if (! in_array(strtoupper($request->correct_label), $labels, true)) {
                return back()->withInput()->with('error', 'Kunci jawaban tidak ada di opsi.');
            }
        }

        $result = $this->quickSoal->create(
            $request->user(),
            $school,
            $subject,
            $grade,
            $ay,
            $data['semester'],
            $data,
            $options,
            $request->input('correct_label')
        );

        $message = 'Soal berhasil disimpan di Soal Saya.';
        if (in_array($data['jenis_asesmen'], [Soal::JENIS_ASESMEN_STS, Soal::JENIS_ASESMEN_SAS], true)) {
            $message .= $result['soal']->isValidated()
                ? ' Soal sudah tervalidasi.'
                : ' Soal menunggu validasi guru serumpun.';
        }

        return redirect()
            ->route('user.soal-saya.index', ['userId' => $userId])
            ->with('success', $message)
            ->with('warning', $result['warning']);
    }

    public function search(Request $request, string $userId)
    {
        // Menu lama "Cari Soal" kini diarahkan ke mesin pencari terpadu
        // (Soal, RPM, Bank Soal, Paket Soal, AI, dan Internet).
        return redirect()->route('user.pencarian.index', [
            'userId' => $userId,
            'q' => $request->input('q'),
            'source' => 'db',
            'type' => 'all',
        ]);
    }

    public function clone(Request $request, string $userId, string $id)
    {
        $school = $this->school($request);
        $source = Soal::with(['bankSoal.subject', 'bankSoal.gradeLevel', 'bankSoal.academicYear', 'options'])->findOrFail($id);

        $accessible = BankSoal::accessibleBy($userId, $school->id)->whereKey($source->bank_soal_id)->exists();
        if (! $accessible) {
            abort(403, 'Soal ini tidak dapat diambil.');
        }

        $result = $this->quickSoal->clone($request->user(), $school, $source);

        return redirect()
            ->route('user.soal-saya.index', ['userId' => $userId])
            ->with('success', 'Soal berhasil disalin ke Soal Saya.')
            ->with('warning', $result['warning']);
    }

    public function importForm(Request $request, string $userId)
    {
        $school = $this->school($request);
        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('name')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
        $selectedAyId = $request->input('academic_year_id', $activeAy?->id);
        $semester = $request->input('semester', $academicYears->firstWhere('id', $selectedAyId)?->semester ?? 'ganjil');
        $subjects = $this->subjects($school);
        $grades = $this->grades($school);
        $scope = $this->scopeSummary($school);

        return view('evalusi.soal-saya.import', compact(
            'userId', 'school', 'academicYears', 'selectedAyId', 'semester', 'subjects', 'grades', 'scope'
        ));
    }

    public function importStore(Request $request, string $userId)
    {
        $school = $this->school($request);

        $data = $request->validate([
            'file' => 'required|file|mimes:docx|max:10240',
            'subject_id' => 'required|exists:subjects,id',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'jenis_asesmen' => 'required|in:harian,formatif,sts,sas',
        ], [
            'file.mimes' => 'File harus berformat .docx (Word modern). File .doc lama belum didukung.',
        ]);

        $this->assertWithinDecree($school, $data['subject_id'], $data['grade_level_id'] ?? null);

        $subject = Subject::where(fn ($q) => $q->where('school_id', $school->id)->orWhereNull('school_id'))
            ->findOrFail($data['subject_id']);
        $grade = $request->filled('grade_level_id') ? GradeLevel::find($request->grade_level_id) : null;
        $ay = AcademicYear::findOrFail($data['academic_year_id']);

        try {
            $parsed = $this->docxParser->parse($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal membaca Word: '.$e->getMessage());
        }

        $created = 0;
        $warnings = $parsed['warnings'] ?? [];

        foreach ($parsed['questions'] as $index => $question) {
            try {
                $result = $this->quickSoal->create(
                    $request->user(),
                    $school,
                    $subject,
                    $grade,
                    $ay,
                    $data['semester'],
                    [
                        'pertanyaan' => $question['pertanyaan'],
                        'pembahasan' => $question['pembahasan'],
                        'tipe_soal' => $question['tipe_soal'],
                        'jenis_asesmen' => $data['jenis_asesmen'],
                        'tingkat_kesulitan_estimasi' => 'sedang',
                        'waktu_estimasi_menit' => 2,
                        'bobot_default' => 1,
                    ],
                    $question['options'] ?? [],
                    $question['correct_label'] ?? null
                );

                $created++;
                if ($result['warning']) {
                    $warnings[] = 'Soal '.($question['number'] ?? $index + 1).': '.$result['warning'];
                }
            } catch (\Throwable $e) {
                $warnings[] = 'Soal '.($question['number'] ?? $index + 1).' gagal disimpan: '.$e->getMessage();
            }
        }

        if ($created === 0) {
            return back()->withInput()->with('error', 'Tidak ada soal yang berhasil diimpor.')->with('import_warnings', $warnings);
        }

        return redirect()
            ->route('user.soal-saya.index', ['userId' => $userId])
            ->with('success', "{$created} soal berhasil diimpor ke Soal Saya.")
            ->with('import_warnings', $warnings);
    }

    public function print(Request $request, string $userId)
    {
        $school = $this->school($request);
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
            'with_key' => 'nullable|boolean',
            'kop' => 'nullable|boolean',
            'judul' => 'nullable|string|max:150',
        ]);

        $bankIds = BankSoal::accessibleBy($userId, $school->id)->pluck('id');
        $questions = Soal::with(['bankSoal.subject:id,name', 'bankSoal.gradeLevel:id,name', 'options'])
            ->whereIn('id', $data['ids'])
            ->whereIn('bank_soal_id', $bankIds)
            ->get()
            ->sortBy(fn ($q) => array_search($q->id, $data['ids'], true))
            ->values();

        if ($questions->count() !== count(array_unique($data['ids']))) {
            return back()->with('error', 'Sebagian soal tidak ditemukan atau tidak dapat diakses.');
        }

        $blocked = $questions
            ->filter(fn (Soal $soal) => $soal->requiresValidation() && ! $soal->isValidated())
            ->map(fn (Soal $soal) => mb_substr(strip_tags($soal->pertanyaan), 0, 60))
            ->values();

        if ($blocked->isNotEmpty()) {
            return back()->with('error', 'Soal STS/SAS berikut belum tervalidasi mayoritas guru serumpun: '.$blocked->implode('; '));
        }

        $withKey = $request->boolean('with_key');
        $kop = $request->boolean('kop')
            ? app(DocumentVisualService::class)->imageDataUri($school->kop_path)
            : null;

        $html = view('evalusi.soal-saya.print', [
            'school' => $school,
            'questions' => $questions,
            'withKey' => $withKey,
            'kop' => $kop,
            'judul' => $data['judul'] ?? 'NASKAH SOAL',
        ])->render();

        $pdf = app(DocumentPdfService::class)->renderHtml($html, 'portrait', 'A4');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="soal-'.now()->format('Ymd-His').'.pdf"',
        ]);
    }

    private function school(Request $request): School
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $school = $schoolId ? School::find($schoolId) : null;

        abort_if(! $school, 422, 'Konteks satuan pendidikan tidak ditemukan.');

        return $school;
    }

    private function subjects(School $school): Collection
    {
        $request = request();

        return $this->teachingScope->subjects(
            $request->user(),
            $school->id,
            null
        );
    }

    /**
     * Kelas yang boleh dipilih: hanya kelas yang diajar menurut SK aktif.
     * Bila mapel dipilih, dipersempit lagi ke kelas untuk mapel tersebut.
     */
    private function grades(School $school, ?string $subjectId = null): Collection
    {
        $request = request();

        if ($subjectId) {
            return $this->teachingScope->gradeLevelsForSubject($request->user(), $school->id, null, $subjectId);
        }

        $gradeIds = $this->teachingScope->assignments($request->user(), $school->id, null)
            ->map(fn ($a) => $a->studyGroup?->grade_level_id)
            ->filter()
            ->unique();

        return GradeLevel::query()->whereIn('id', $gradeIds)->orderBy('level')->get();
    }

    /**
     * Tolak mapel/kelas di luar SK aktif.
     *
     * @throws ValidationException
     */
    private function assertWithinDecree(School $school, ?string $subjectId, ?string $gradeLevelId): void
    {
        $user = request()->user();

        if (! $this->teachingScope->allowsSubject($user, $school->id, null, $subjectId)) {
            throw ValidationException::withMessages([
                'subject_id' => 'Mata pelajaran ini tidak ada pada SK Pembagian Tugas aktif Anda. '
                    .'Minta Waka menambahkannya ke SK, atau pilih mapel yang memang Anda ajar.',
            ]);
        }

        if (! $this->teachingScope->allowsGradeLevel($user, $school->id, null, $subjectId, $gradeLevelId)) {
            throw ValidationException::withMessages([
                'grade_level_id' => 'Kelas ini tidak Anda ajar untuk mata pelajaran tersebut menurut SK aktif.',
            ]);
        }
    }

    private function scopeSummary(School $school): array
    {
        return $this->teachingScope->summary(request()->user(), $school->id, null);
    }
}
