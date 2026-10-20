<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Concerns\AssertsPeriodLock;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AiGeneratedContent;
use App\Models\AiGeneratedContentRevision;
use App\Models\AuditLog;
use App\Models\BankSoal;
use App\Models\CurriculumProject;
use App\Models\GradeLevel;
use App\Models\PerangkatPembelajaran;
use App\Models\Soal;
use App\Models\SoalOption;
use App\Models\Subject;
use App\Services\Ai\AiContentGenerator;
use App\Services\Ai\AiProviderService;
use App\Services\Evaluasi\ContentHashEngine;
use App\Services\CurriculumProfileService;
use App\Services\KurikulumAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * AI Assistant — draft RPM & soal.
 *
 * Hasil disimpan sebagai draft terpisah (tidak langsung masuk Bank Soal/RPM),
 * ditinjau/disetujui oleh tim kurikulum. Provider diatur di Pengaturan AI.
 */
class AiContentController extends Controller
{
    use AssertsPeriodLock;

    public function __construct(
        private readonly KurikulumAccess $access,
        private readonly CurriculumProfileService $profiles,
    ) {}

    public function index(Request $request, string $userId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = $request->user();

        $academicYears = AcademicYear::orderByDesc('start_date')->orderByDesc('name')->get();
        $activeAy = $academicYears->firstWhere('is_active', true) ?? $academicYears->first();

        $taughtSubjectIds = $this->access->taughtSubjectIds($user, $schoolId, $activeAy?->id);

        $subjects = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->when($taughtSubjectIds !== null, fn ($q) => $q->whereIn('id', $taughtSubjectIds ?: ['-']))
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar']);

        $gradeLevels = GradeLevel::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('level')
            ->get(['id', 'name', 'fase', 'level']);

        $history = AiGeneratedContent::query()
            ->with(['subject:id,name', 'gradeLevel:id,name', 'creator:id,name'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $this->access->isKurikulumTeam($user), fn ($q) => $q->where('created_by', $user?->id))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $aiConfigured = app(AiProviderService::class)->isConfigured();
        $isTeam = $this->access->isKurikulumTeam($user);
        $curriculumProfile = $this->profiles->resolve($schoolId, $activeAy?->id);
        $curriculumLabels = $this->profiles->labels($curriculumProfile);

        return view('kurikulum.ai.index', compact(
            'userId',
            'academicYears',
            'activeAy',
            'subjects',
            'gradeLevels',
            'history',
            'aiConfigured',
            'isTeam',
            'curriculumProfile',
            'curriculumLabels'
        ));
    }

    public function generate(Request $request, string $userId, AiContentGenerator $generator)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $user = $request->user();

        $validated = $request->validate([
            'type' => 'required|in:rpm,soal,projek',
            'project_type' => 'nullable|in:p5,p5ra',
            'subject_id' => [
                'required',
                Rule::exists('subjects', 'id')->where(function ($query) use ($schoolId) {
                    if ($schoolId) {
                        $query->where('school_id', $schoolId);
                    }
                }),
            ],
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:ganjil,genap',
            'topik' => 'nullable|string|max:2000',
            'jumlah_soal' => 'nullable|integer|min:1|max:50',
            'bentuk_soal' => 'nullable|in:pg,essay,campuran',
            'level_kognitif' => 'nullable|in:C1,C2,C3,C4,C5,C6,campuran',
            'jumlah_pertemuan' => 'nullable|integer|min:1|max:12',
            'bahasa' => 'nullable|in:id,ar',
            'instruksi' => 'nullable|string|max:2000',
        ]);

        if (! app(AiProviderService::class)->isConfigured()) {
            return back()->withInput()->with('error', 'Provider AI belum dikonfigurasi. Hubungi Super Admin untuk mengisi API key di Pengaturan AI.');
        }

        abort_unless(
            $this->access->canAuthorSubject($user, $validated['subject_id'], $validated['academic_year_id'], $schoolId),
            403,
            'Anda hanya dapat membuat draft AI untuk mata pelajaran yang diampu.'
        );

        $subject = Subject::find($validated['subject_id']);
        $grade = ! empty($validated['grade_level_id']) ? GradeLevel::find($validated['grade_level_id']) : null;
        $academicYear = AcademicYear::find($validated['academic_year_id']);

        $curriculumProfile = $this->profiles->resolve($schoolId, $validated['academic_year_id']);
        $curriculumLabels = $this->profiles->labels($curriculumProfile);
        $projectType = $validated['project_type'] ?? ($curriculumProfile?->isKemenag() ? 'p5ra' : 'p5');

        $context = [
            'subject' => $subject?->name,
            'grade' => $grade?->name,
            'fase' => $grade?->fase,
            'academic_year' => $academicYear?->name,
            'semester' => $validated['semester'],
            'topik' => $validated['topik'] ?? null,
            'jumlah_soal' => $validated['jumlah_soal'] ?? 5,
            'bentuk_soal' => $validated['bentuk_soal'] ?? 'campuran',
            'level_kognitif' => $validated['level_kognitif'] ?? null,
            'jumlah_pertemuan' => $validated['jumlah_pertemuan'] ?? 2,
            'bahasa' => $validated['bahasa'] ?? 'id',
            'instruksi' => $validated['instruksi'] ?? null,
            'project_type' => $projectType,
            'curriculum' => [
                'code' => $curriculumProfile?->code,
                'name' => $curriculumProfile?->name,
                'authority' => $curriculumProfile?->authority,
                'labels' => $curriculumLabels,
                'values' => $curriculumProfile?->values() ?? [],
                'kokurikuler' => $curriculumProfile?->configValue('labels.kokurikuler_label'),
            ],
        ];

        $result = $generator->generate($validated['type'], $context);

        if (! $result['ok']) {
            return back()->withInput()->with('error', 'Gagal membuat draft AI: '.($result['error'] ?? 'tidak diketahui'));
        }

        $title = match ($validated['type']) {
            'soal' => 'Soal AI',
            'projek' => 'Projek '.strtoupper($projectType).' AI',
            default => ($curriculumLabels['perangkat_short'] ?? 'RPM').' AI',
        };
        $title .= ' — '.($subject?->name ?? 'Mapel')
            .($grade ? ' '.$grade->name : '')
            .' — '.now()->format('d M Y H:i');

        $record = AiGeneratedContent::create([
            'school_id' => $schoolId,
            'academic_year_id' => $validated['academic_year_id'],
            'curriculum_profile_id' => $curriculumProfile?->id,
            'semester' => $validated['semester'],
            'bahasa' => $validated['bahasa'] ?? 'id',
            'type' => $validated['type'],
            'project_type' => $validated['type'] === 'projek' ? $projectType : null,
            'subject_id' => $validated['subject_id'],
            'grade_level_id' => $validated['grade_level_id'] ?? null,
            'title' => $title,
            'topik' => $validated['topik'] ?? null,
            'content' => $result['content'],
            'raw_content' => $result['raw'],
            'status' => 'draft',
            'provider' => $result['provider'],
            'model' => $result['model'],
            'created_by' => $user?->id,
        ]);

        AiGeneratedContentRevision::create([
            'ai_generated_content_id' => $record->id,
            'content' => $record->content,
            'changed_by' => $user?->id,
            'note' => 'Draft awal AI',
        ]);

        return redirect()
            ->route('user.kurikulum.ai.show', ['userId' => $userId, 'id' => $record->id])
            ->with('success', 'Draft AI berhasil dibuat. Tinjau sebelum disetujui/dipakai.');
    }

    public function show(Request $request, string $userId, string $id)
    {
        $record = AiGeneratedContent::with(['subject:id,name', 'gradeLevel:id,name', 'creator:id,name', 'approver:id,name'])
            ->findOrFail($id);

        $this->authorizeSchool($request, $record);

        $isTeam = $this->access->isKurikulumTeam($request->user());

        abort_unless(
            $isTeam || (string) $record->created_by === (string) $request->user()?->id,
            403,
            'Anda tidak berwenang melihat draft ini.'
        );

        $curriculumProfile = $record->curriculum_profile_id
            ? \App\Models\CurriculumProfile::find($record->curriculum_profile_id)
            : $this->profiles->resolve($record->school_id, $record->academic_year_id);
        $curriculumLabels = $this->profiles->labels($curriculumProfile);
        $canSaveProject = $record->type === 'projek'
            && $record->isApproved()
            && ! $record->saved_project_id
            && is_array($record->content)
            && ! empty($record->content);

        return view('kurikulum.ai.show', compact('userId', 'record', 'isTeam', 'curriculumProfile', 'curriculumLabels', 'canSaveProject'));
    }

    public function edit(Request $request, string $userId, string $id)
    {
        $record = AiGeneratedContent::findOrFail($id);
        $this->authorizeSchool($request, $record);
        $this->authorizeManage($request, $record);

        if ($record->type === 'soal') {
            return redirect()
                ->route('user.kurikulum.ai.show', ['userId' => $userId, 'id' => $record->id])
                ->with('error', 'Gunakan editor butir soal pada halaman detail draft.');
        }
        abort_unless($record->status === 'draft' && ! $record->saved_at, 403, 'Hanya draft yang belum disimpan yang dapat diedit.');

        $profile = $record->curriculum_profile_id
            ? \App\Models\CurriculumProfile::find($record->curriculum_profile_id)
            : $this->profiles->resolve($record->school_id, $record->academic_year_id);
        $labels = $this->profiles->labels($profile);
        $values = $profile?->values() ?? [];
        $content = is_array($record->content) ? $record->content : [];

        return view('kurikulum.ai.edit', compact('userId', 'record', 'content', 'profile', 'labels', 'values'));
    }

    public function updateDraft(Request $request, string $userId, string $id)
    {
        $record = AiGeneratedContent::findOrFail($id);
        $this->authorizeSchool($request, $record);
        $this->authorizeManage($request, $record);
        abort_unless(in_array($record->type, ['rpm', 'projek'], true), 404, 'Draft ini tidak mendukung editor ini.');
        abort_unless($record->status === 'draft' && ! $record->saved_at, 403, 'Hanya draft yang belum disimpan yang dapat diedit.');

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'capaian' => 'nullable|string|max:5000',
            'tujuan_text' => 'nullable|string|max:5000',
            'media_text' => 'nullable|string|max:5000',
            'refleksi' => 'nullable|string|max:5000',
            'asesmen_formatif' => 'nullable|string|max:5000',
            'asesmen_sumatif' => 'nullable|string|max:5000',
            'langkah_json' => 'nullable|string',
            'tema' => 'nullable|string|max:500',
            'deskripsi' => 'nullable|string|max:5000',
            'dimensions' => 'nullable|array',
            'dimensions.*' => 'string|max:100',
            'activities_json' => 'nullable|string',
            'kbc_tema' => 'nullable|string|max:500',
            'kbc_materi' => 'nullable|string|max:5000',
            'kbc_pilar' => 'nullable|string|max:1000',
            'kbc_indikator' => 'nullable|string|max:5000',
            'kbc_refleksi' => 'nullable|string|max:5000',
            'kbc_asesmen' => 'nullable|string|max:5000',
        ]);

        $content = is_array($record->content) ? $record->content : [];
        $lines = fn (?string $value) => collect(preg_split('/\r\n|\r|\n/', (string) $value))->map(fn ($line) => trim($line))->filter()->values()->all();

        $content['judul'] = $validated['judul'];

        if ($record->type === 'rpm') {
            $content['capaian'] = $validated['capaian'] ?? null;
            $content['tujuan'] = $lines($validated['tujuan_text'] ?? null);
            $content['media'] = $lines($validated['media_text'] ?? null);
            $content['refleksi'] = $validated['refleksi'] ?? null;
            $content['asesmen'] = array_merge(
                is_array($content['asesmen'] ?? null) ? $content['asesmen'] : [],
                [
                    'formatif' => $validated['asesmen_formatif'] ?? null,
                    'sumatif' => $validated['asesmen_sumatif'] ?? null,
                ]
            );

            if (filled($validated['langkah_json'] ?? null)) {
                $decoded = json_decode((string) $validated['langkah_json'], true);
                if (! is_array($decoded)) {
                    return back()->withInput()->with('error', 'JSON langkah pembelajaran tidak valid.');
                }
                $content['langkah'] = $decoded;
            }

            $kbc = array_filter([
                'tema' => $validated['kbc_tema'] ?? null,
                'materi_insersi' => $validated['kbc_materi'] ?? null,
                'pilar' => filled($validated['kbc_pilar'] ?? null) ? $lines($validated['kbc_pilar']) : null,
                'indikator' => $validated['kbc_indikator'] ?? null,
                'refleksi' => $validated['kbc_refleksi'] ?? null,
                'asesmen' => $validated['kbc_asesmen'] ?? null,
            ], fn ($value) => filled($value));
            if ($kbc !== []) {
                $content['kbc'] = $kbc;
            }
        } else {
            $content['tema'] = $validated['tema'] ?? null;
            $content['deskripsi'] = $validated['deskripsi'] ?? null;
            $content['dimensi'] = array_values($validated['dimensions'] ?? []);
            if (filled($validated['activities_json'] ?? null)) {
                $decoded = json_decode((string) $validated['activities_json'], true);
                if (! is_array($decoded)) {
                    return back()->withInput()->with('error', 'JSON aktivitas projek tidak valid.');
                }
                $content['aktivitas'] = $decoded;
            }
        }

        AiGeneratedContentRevision::create([
            'ai_generated_content_id' => $record->id,
            'content' => $record->content,
            'changed_by' => $request->user()?->id,
            'note' => 'Edit draft '.$record->type,
        ]);
        $record->forceFill(['content' => $content])->save();

        return redirect()
            ->route('user.kurikulum.ai.show', ['userId' => $userId, 'id' => $record->id])
            ->with('success', 'Draft AI berhasil diperbarui. Tinjau kembali sebelum disetujui.');
    }

    /**
     * Simpan perubahan butir soal pada draft (sebelum disetujui/disimpan ke bank).
     */
    public function updateItems(Request $request, string $userId, string $id)
    {
        $record = AiGeneratedContent::findOrFail($id);
        $this->authorizeSchool($request, $record);
        $this->authorizeManage($request, $record);

        abort_unless($record->type === 'soal', 404, 'Draft ini bukan soal.');
        abort_unless($record->status === 'draft' && ! $record->saved_at, 403, 'Hanya draft soal yang belum disimpan yang dapat diubah.');

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.pertanyaan' => 'required|string|max:5000',
            'items.*.bentuk' => 'required|in:pg,essay',
            'items.*.pilihan' => 'nullable|array',
            'items.*.pilihan.*' => 'nullable|string|max:1000',
            'items.*.jawaban' => 'nullable|string|max:2000',
            'items.*.pembahasan' => 'nullable|string|max:5000',
            'items.*.level' => 'nullable|string|max:10',
            'items.*.indikator' => 'nullable|string|max:500',
        ]);

        $items = collect($validated['items'])->map(function (array $item) {
            $options = [];

            foreach (['A', 'B', 'C', 'D'] as $label) {
                $text = trim((string) data_get($item, 'pilihan.'.$label, ''));

                if ($text !== '') {
                    $options[$label] = $text;
                }
            }

            return [
                'pertanyaan' => trim($item['pertanyaan']),
                'bentuk' => $item['bentuk'],
                'pilihan' => $options,
                'jawaban' => $item['jawaban'] ?? null,
                'pembahasan' => $item['pembahasan'] ?? null,
                'level' => $item['level'] ?? null,
                'indikator' => $item['indikator'] ?? null,
            ];
        })->values()->all();

        AiGeneratedContentRevision::create([
            'ai_generated_content_id' => $record->id,
            'content' => $record->content,
            'changed_by' => $request->user()?->id,
            'note' => 'Edit butir soal',
        ]);
        $record->update(['content' => ['items' => $items]]);

        return back()->with('success', 'Butir soal diperbarui.');
    }

    /**
     * Simpan draft soal yang sudah disetujui ke Bank Soal resmi.
     */
    public function saveToBank(Request $request, string $userId, string $id)
    {
        $record = AiGeneratedContent::with(['subject', 'gradeLevel', 'school'])->findOrFail($id);
        $this->authorizeSchool($request, $record);
        $this->authorizeManage($request, $record);

        abort_unless($record->type === 'soal', 404, 'Draft ini bukan soal.');
        abort_unless($record->isApproved(), 403, 'Setujui draft terlebih dahulu sebelum menyimpan ke Bank Soal.');
        abort_if($record->saved_bank_id, 409, 'Draft ini sudah disimpan ke Bank Soal.');

        $content = is_array($record->content) ? $record->content : [];
        $items = collect($content['items'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): array {
                abort_if(blank($item['pertanyaan'] ?? null), 422, 'Draft memiliki butir soal tanpa pertanyaan.');

                $bentuk = $item['bentuk'] ?? 'pg';
                abort_unless(in_array($bentuk, ['pg', 'essay'], true), 422, 'Bentuk soal pada draft tidak valid.');

                if ($bentuk === 'pg') {
                    $options = (array) ($item['pilihan'] ?? []);
                    abort_if(count($options) < 2, 422, 'Soal pilihan ganda minimal memiliki 2 opsi jawaban.');

                    $answer = strtoupper(trim((string) ($item['jawaban'] ?? '')));

                    if (! isset($options[$answer])) {
                        $matched = array_search(
                            trim((string) ($item['jawaban'] ?? '')),
                            array_map(static fn ($option) => (string) $option, $options),
                            true
                        );
                        abort_if($matched === false, 422, 'Jawaban soal pilihan ganda tidak cocok dengan opsi yang tersedia.');
                        $answer = $matched;
                    }

                    $item['jawaban'] = $answer;
                }

                return $item;
            })
            ->values()
            ->all();

        abort_if($items === [], 422, 'Draft tidak memiliki butir soal yang valid.');

        $schoolId = $record->school_id ?: $request->attributes->get('schoolContextId');
        abort_unless($schoolId && $record->subject_id, 422, 'Konteks sekolah/mapel draft tidak lengkap.');
        abort_unless(
            $this->access->canAuthorSubject($request->user(), $record->subject_id, (string) $record->academic_year_id, $schoolId),
            403,
            'Anda hanya dapat menyimpan draft untuk mata pelajaran yang diampu.'
        );

        $bank = DB::transaction(function () use ($record, $items, $schoolId, $request) {
            $onlyPg = collect($items)->every(fn ($item) => ($item['bentuk'] ?? 'pg') === 'pg');
            $onlyEssay = collect($items)->every(fn ($item) => ($item['bentuk'] ?? 'pg') === 'essay');

            $jenisSoal = $onlyPg ? 'pilihan_ganda' : ($onlyEssay ? 'uraian' : 'campuran');
            $bahasa = $record->bahasa === 'ar' ? Soal::BAHASA_ARAB : Soal::BAHASA_LATIN;
            $namaBank = 'AI — '.($record->subject?->name ?? 'Mapel').($record->gradeLevel ? ' '.$record->gradeLevel->name : '').' — '.now()->format('d M Y H:i');

            $bank = BankSoal::create([
                'school_id' => $schoolId,
                'subject_id' => $record->subject_id,
                'fase' => $record->gradeLevel?->fase,
                'jenjang' => $record->school?->school_level ? strtoupper((string) $record->school->school_level) : null,
                'grade_level_id' => $record->grade_level_id,
                'academic_year_id' => $record->academic_year_id,
                'semester' => $record->semester,
                'nama' => mb_substr($namaBank, 0, 150),
                'deskripsi' => 'Dibuat dari draft AI '.$record->id.($record->topik ? ' · '.$record->topik : ''),
                'jenis_soal' => $jenisSoal,
                'tingkat_kesulitan_target' => 'campuran',
                'is_public' => false,
                'shared_scope' => 'private',
                'is_central' => false,
                'owner_user_id' => $record->created_by ?: $request->user()?->id,
                'allow_cross_teacher_clone' => false,
                'total_soal' => 0,
                'created_by' => $request->user()?->id,
            ]);

            $engine = app(ContentHashEngine::class);

            foreach ($items as $item) {
                $tipe = ($item['bentuk'] ?? 'pg') === 'essay' ? 'uraian' : 'pg';
                $options = (array) ($item['pilihan'] ?? []);
                $answer = strtoupper(trim((string) ($item['jawaban'] ?? '')));
                $correctTexts = $tipe === 'pg'
                    ? [$options[$answer]]
                    : [(string) ($item['jawaban'] ?? '')];

                $soal = new Soal;
                $soal->fill([
                    'bank_soal_id' => $bank->id,
                    'materi' => $record->topik ? mb_substr((string) $record->topik, 0, 150) : null,
                    'tipe_soal' => $tipe,
                    'bahasa' => $bahasa,
                    'pertanyaan' => (string) ($item['pertanyaan'] ?? ''),
                    'pembahasan' => $item['pembahasan'] ?? null,
                    'bobot_default' => 1,
                    'tingkat_kesulitan_estimasi' => $this->difficultyFromLevel($item['level'] ?? null),
                    'waktu_estimasi_menit' => $tipe === 'pg' ? 2 : 5,
                    'tags' => ['ai'],
                    'status' => 'draft',
                    'workflow_status' => Soal::WORKFLOW_DRAFT,
                    'dibuat_oleh' => $request->user()?->id,
                ]);
                $soal->content_hash = $engine->hashFromSoal($soal->pertanyaan, $correctTexts);
                $soal->shingles_hash = $engine->shinglesFromSoal($soal->pertanyaan);
                $soal->save();

                if ($tipe === 'pg') {
                    foreach ($options as $label => $text) {
                        SoalOption::create([
                            'soal_id' => $soal->id,
                            'label' => (string) $label,
                            'teks_opsi' => (string) $text,
                            'is_correct' => (string) $label === $answer,
                            'urutan' => array_search($label, ['A', 'B', 'C', 'D'], true) + 1,
                        ]);
                    }
                }
            }

            $bank->forceFill(['total_soal' => count($items)])->save();

            $record->forceFill([
                'saved_bank_id' => $bank->id,
                'saved_at' => now(),
            ])->save();

            return $bank;
        });

        return redirect()
            ->route('user.bank-soal.show', ['userId' => $userId, 'id' => $bank->id])
            ->with('success', 'Draft AI disimpan ke Bank Soal sebagai soal berstatus draft ('.$bank->total_soal.' butir).');
    }

    /**
     * Simpan draft RPM yang sudah disetujui sebagai Perangkat Pembelajaran.
     */
    public function saveToRpm(Request $request, string $userId, string $id)
    {
        $record = AiGeneratedContent::with(['subject', 'gradeLevel', 'school'])->findOrFail($id);
        $this->authorizeSchool($request, $record);
        $this->authorizeManage($request, $record);

        abort_unless($record->type === 'rpm', 404, 'Draft ini bukan RPM.');
        abort_unless($record->isApproved(), 403, 'Setujui draft terlebih dahulu sebelum menyimpan sebagai RPM.');
        abort_if($record->saved_perangkat_id, 409, 'Draft ini sudah disimpan sebagai RPM.');

        $content = is_array($record->content) ? $record->content : [];
        abort_if(empty($content), 422, 'Draft RPM tidak memiliki konten terstruktur.');
        abort_unless($record->school_id && $record->subject_id && $record->academic_year_id, 422, 'Konteks draft RPM tidak lengkap.');

        abort_unless(
            $this->access->canAuthorSubject($request->user(), $record->subject_id, $record->academic_year_id, $record->school_id),
            403,
            'Anda hanya dapat menyimpan RPM untuk mata pelajaran yang diampu.'
        );
        $this->assertPeriodNotLocked($record->academic_year_id, 'Dokumen RPM/Perangkat');

        $existing = PerangkatPembelajaran::query()
            ->where('school_id', $record->school_id)
            ->where('academic_year_id', $record->academic_year_id)
            ->where('semester', $record->semester)
            ->where('subject_id', $record->subject_id)
            ->where('grade_level_id', $record->grade_level_id)
            ->where('is_current', true)
            ->first();

        if ($existing && ($existing->source ?? 'manual') === 'manual') {
            return back()->with('error', 'Perangkat manual sudah ada untuk konteks ini. AI tidak akan menimpa dokumen manual. Buka perangkat yang ada untuk mereview atau membuat versi baru secara manual.');
        }

        if ($existing && ($existing->source ?? 'manual') === 'ai'
            && (string) $existing->ai_generated_content_id !== (string) $record->id) {
            return back()->with('error', 'Sudah ada perangkat dari draft AI lain untuk konteks ini. Tinjau perangkat tersebut terlebih dahulu; AI tidak akan membuat dokumen paralel.');
        }

        $profile = $record->curriculum_profile_id
            ? \App\Models\CurriculumProfile::find($record->curriculum_profile_id)
            : $this->profiles->resolve($record->school_id, $record->academic_year_id);
        $labels = $this->profiles->labels($profile);

        $desain = PerangkatPembelajaran::defaultDesain();
        $asesmen = is_array($content['asesmen'] ?? null) ? $content['asesmen'] : [];
        $langkah = is_array($content['langkah'] ?? null) ? $content['langkah'] : [];
        $judul = $this->toText($content['judul'] ?? null) ?? $record->title;

        if (isset($langkah['pertemuan']) || isset($langkah['pembuka'])) {
            $langkah = [$langkah];
        }

        $desain['topik'] = $record->topik ?: $this->toText($content['judul'] ?? null);
        $desain['identifikasi_materi'] = $this->toText($content['capaian'] ?? null);
        $desain['asesmen_formatif'] = $this->toText($asesmen['formatif'] ?? null);
        $desain['asesmen_sumatif'] = $this->toText($asesmen['sumatif'] ?? null);
        $desain['media_sumber'] = $this->toText($content['media'] ?? null);
        $desain['kegiatan_awal'] = $this->toText(collect($langkah)->pluck('pembuka')->filter()->all());
        $desain['pengalaman_memahami'] = $this->toText(collect($langkah)->map(fn ($step) => 'Pertemuan '.($step['pertemuan'] ?? '').': '.($step['inti'] ?? ''))->filter()->all());
        $desain['pengalaman_mengaplikasi'] = $this->toText(collect($langkah)->pluck('inti')->filter()->all());
        $desain['pengalaman_refleksi'] = $this->toText(collect($langkah)->pluck('penutup')->filter()->all());
        $desain['penutup'] = $this->toText(collect($langkah)->pluck('penutup')->filter()->all());

        $kbc = is_array($content['kbc'] ?? null) ? $content['kbc'] : [];
        if (! empty($kbc)) {
            $desain['tema_kbc'] = $this->toText($kbc['tema'] ?? null);
            $desain['materi_insersi'] = $this->toText($kbc['materi_insersi'] ?? null);
            $desain['pilar_kbc'] = $this->toText($kbc['pilar'] ?? null);
            $desain['indikator_kbc'] = $this->toText($kbc['indikator'] ?? null);
            $desain['refleksi_kbc'] = $this->toText($kbc['refleksi'] ?? null);
            $desain['asesmen_kbc'] = $this->toText($kbc['asesmen'] ?? null);
        }

        $perangkat = DB::transaction(function () use ($record, $existing, $desain, $judul, $request, $profile) {
            if ($existing) {
                $existing->forceFill(['is_current' => false])->saveQuietly();
            }

            $perangkat = PerangkatPembelajaran::create([
                'school_id' => $record->school_id,
                'academic_year_id' => $record->academic_year_id,
                'curriculum_profile_id' => $profile?->id ?? $record->curriculum_profile_id,
                'semester' => $record->semester,
                'subject_id' => $record->subject_id,
                'grade_level_id' => $record->grade_level_id,
                'teacher_id' => $record->created_by ?: $request->user()?->id,
                'judul' => mb_substr($judul, 0, 191),
                'tipe' => PerangkatPembelajaran::TIPE_UMUM,
                'source' => 'ai',
                'ai_generated_content_id' => $record->id,
                'status' => PerangkatPembelajaran::STATUS_DRAFT,
                'workflow_status' => PerangkatPembelajaran::WORKFLOW_DRAFT,
                'root_id' => $existing?->root_id ?: $existing?->id,
                'parent_id' => $existing?->id,
                'version' => $existing ? ((int) $existing->version + 1) : 1,
                'is_current' => true,
                'desain' => $desain,
                'catatan' => 'Dibuat dari draft AI '.$record->id.'.',
                'created_by' => $request->user()?->id,
            ]);

            $record->forceFill([
                'saved_perangkat_id' => $perangkat->id,
                'saved_at' => now(),
            ])->save();

            return $perangkat;
        });

        return redirect()
            ->route('user.kurikulum.perangkat.show', ['userId' => $userId, 'id' => $perangkat->id])
            ->with('success', 'Draft AI disimpan sebagai '.($labels['perangkat_label'] ?? 'RPM').' (status draft).');
    }

    public function saveToProject(Request $request, string $userId, string $id)
    {
        $record = AiGeneratedContent::with(['subject', 'gradeLevel', 'school'])->findOrFail($id);
        $this->authorizeSchool($request, $record);
        $this->authorizeManage($request, $record);

        abort_unless($record->type === 'projek', 404, 'Draft ini bukan projek.');
        abort_unless($record->isApproved(), 403, 'Setujui draft terlebih dahulu sebelum menyimpan sebagai projek.');
        abort_if($record->saved_project_id, 409, 'Draft ini sudah disimpan sebagai projek.');
        abort_unless($record->school_id && $record->academic_year_id, 422, 'Konteks draft projek tidak lengkap.');

        $content = is_array($record->content) ? $record->content : [];
        abort_if(empty($content), 422, 'Draft projek tidak memiliki konten terstruktur.');
        $this->assertPeriodNotLocked($record->academic_year_id, 'Projek Kokurikuler');

        $profile = $record->curriculum_profile_id
            ? \App\Models\CurriculumProfile::find($record->curriculum_profile_id)
            : $this->profiles->resolve($record->school_id, $record->academic_year_id);
        $profileValues = $profile?->values() ?? [];
        $projectType = in_array($record->project_type, ['p5', 'p5ra'], true)
            ? $record->project_type
            : ($profile?->isKemenag() ? 'p5ra' : 'p5');

        $dimensions = collect($content['dimensi'] ?? [])
            ->map(function ($value) use ($profileValues) {
                $value = (string) $value;
                if (isset($profileValues[$value])) {
                    return $value;
                }
                $needle = mb_strtolower($value);
                foreach ($profileValues as $key => $label) {
                    if (mb_strtolower((string) $label) === $needle || mb_strtolower((string) $key) === $needle) {
                        return $key;
                    }
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $activities = collect($content['aktivitas'] ?? [])
            ->filter(fn ($activity) => is_array($activity) && (filled($activity['judul'] ?? null) || filled($activity['deskripsi'] ?? null)))
            ->map(fn ($activity) => [
                'judul' => trim((string) ($activity['judul'] ?? 'Aktivitas')),
                'deskripsi' => trim((string) ($activity['deskripsi'] ?? '')),
                'tanggal' => $activity['tanggal'] ?? null,
            ])
            ->values()
            ->all();

        $project = DB::transaction(function () use ($record, $content, $projectType, $profile, $dimensions, $activities, $request) {
            $project = CurriculumProject::create([
                'school_id' => $record->school_id,
                'academic_year_id' => $record->academic_year_id,
                'semester' => $record->semester ?: 'ganjil',
                'curriculum_profile_id' => $profile?->id,
                'type' => $projectType,
                'source' => 'ai',
                'ai_generated_content_id' => $record->id,
                'title' => mb_substr($this->toText($content['judul'] ?? null) ?? $record->title, 0, 191),
                'theme' => $this->toText($content['tema'] ?? null),
                'description' => $this->toText($content['deskripsi'] ?? null),
                'dimensions' => $dimensions,
                'grade_level_ids' => $record->grade_level_id ? [$record->grade_level_id] : null,
                'activities' => $activities,
                'status' => CurriculumProject::STATUS_DRAFT,
                'coordinator_id' => $record->created_by ?: $request->user()?->id,
                'created_by' => $request->user()?->id,
            ]);

            $record->forceFill([
                'saved_project_id' => $project->id,
                'saved_at' => now(),
            ])->save();

            return $project;
        });

        return redirect()
            ->route('user.kurikulum.proyek.show', ['userId' => $userId, 'id' => $project->id])
            ->with('success', 'Draft AI disimpan sebagai projek (status draft). Tinjau sebelum dijalankan.');
    }

    private function difficultyFromLevel(?string $level): string
    {
        return match (strtoupper((string) $level)) {
            'C1', 'C2' => 'mudah',
            'C5', 'C6' => 'sulit',
            default => 'sedang',
        };
    }

    private function toText(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = implode('
', array_map(static fn ($item) => is_array($item) ? implode(' — ', $item) : (string) $item, $value));
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function authorizeManage(Request $request, AiGeneratedContent $record): void
    {
        abort_unless(
            $this->access->isKurikulumTeam($request->user())
            || (string) $record->created_by === (string) $request->user()?->id,
            403,
            'Anda tidak berwenang mengelola draft ini.'
        );
    }

    public function approve(Request $request, string $userId, string $id)
    {
        $record = AiGeneratedContent::findOrFail($id);
        $this->authorizeSchool($request, $record);

        abort_unless($this->access->isKurikulumTeam($request->user()), 403, 'Hanya tim kurikulum yang dapat menyetujui draft AI.');

        if ($record->isApproved()) {
            return back()->with('error', 'Draft ini sudah disetujui.');
        }

        $record->forceFill([
            'status' => 'approved',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ])->save();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'AI_CONTENT_APPROVED',
            'table_name' => $record->getTable(),
            'record_id' => $record->id,
            'record_type' => AiGeneratedContent::class,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        return back()->with('success', 'Draft AI disetujui. Dapat disalin/dipakai pada modul terkait.');
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $record = AiGeneratedContent::findOrFail($id);
        $this->authorizeSchool($request, $record);

        abort_unless(
            $this->access->isKurikulumTeam($request->user()) || (string) $record->created_by === (string) $request->user()?->id,
            403,
            'Anda tidak berwenang menghapus draft ini.'
        );

        abort_if($record->saved_at, 403, 'Draft yang sudah disimpan ke Bank Soal/RPM tidak dapat dihapus.');

        $record->delete();

        return redirect()
            ->route('user.kurikulum.ai.index', ['userId' => $userId])
            ->with('success', 'Draft AI dihapus.');
    }

    private function authorizeSchool(Request $request, AiGeneratedContent $record): void
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId && $record->school_id && (string) $record->school_id !== (string) $schoolId) {
            abort(403, 'Draft AI bukan milik satuan pendidikan Anda.');
        }
    }
}
