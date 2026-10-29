<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskType;
use App\Models\AdditionalTaskTypeWorkUnitHour;
use App\Models\GtkAdditionalTask;
use App\Models\GtkCrossUnitAssignment;
use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\InstitutionDecree;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\StructuralAssignment;
use App\Models\StructuralPosition;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Documents\DocumentSigningService;
use App\Services\Documents\DocumentVisualService;
use App\Services\Documents\SchoolLeaderService;
use App\Services\InstitutionDecreeMatrixService;
use App\Services\InstitutionDecreePayloadService;
use App\Services\Pdf\DocumentPdfService;
use App\Services\SchoolGroupService;
use App\Exports\InstitutionDecreeTemplateExport;
use App\Imports\InstitutionDecreeImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstitutionDecreeController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $query = InstitutionDecree::with(['academicYear', 'signer', 'school']);

        $schoolId = $request->attributes->get('schoolContextId');
        $currentUser = auth()->user();
        $isGlobal = canPermission('institution-decree-all-access');

        if ($isGlobal) {
            if ($request->filled('school_id')) {
                $query->where('school_id', $request->school_id);
            }
        } elseif ($schoolId) {
            $query->where('school_id', $schoolId);
        } else {
            $query->whereNull('school_id');
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('decree_type')) {
            $query->where('decree_type', 'like', "%{$request->decree_type}%");
        }
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('decree_number', 'like', "%{$request->search}%")
                ->orWhere('title', 'like', "%{$request->search}%"));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $decrees = $query->orderByDesc('issued_date')->paginate(15)->withQueryString();
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $schools = School::orderBy('name')->get();

        return view('institution-decrees.index', compact('decrees', 'academicYears', 'schools', 'userId'));
    }

    public function create(Request $request, string $userId)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');
        $currentUser = auth()->user();
        $isGlobal = canPermission('institution-decree-all-access');

        // Super Admin: school from query param, others from session
        $selectedSchoolId = $isGlobal
            ? ($request->filled('school_id') ? $request->school_id : null)
            : $schoolContextId;

        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : (AcademicYear::active()->first()?->id);

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $activeYear = AcademicYear::active()->first();
        $signers = User::whereIn('id', usersHavingPermission('decree.signer.certifiable'))->orderBy('name')->get();
        $schools = School::orderBy('name')->get();

        // Matrix data — only load if school is selected
        $teachers = collect();
        $subjects = collect();
        $studyGroups = collect();

        $crossUnitAssignments = collect();
        if ($selectedSchoolId) {
            // GTK internal sekolah + GTK yang ditugaskan resmi oleh Personalia/Pondok.
            $crossUnitAssignments = GtkCrossUnitAssignment::with(['subject:id,name', 'homeWorkUnit:id,name'])
                ->where('school_id', $selectedSchoolId)
                ->when($selectedAyId, fn ($q) => $q->where('academic_year_id', $selectedAyId))
                ->active()
                ->get();

            $schoolTeacherIds = SchoolGroupService::userIdsForSchool($selectedSchoolId);
            $teacherIds = collect($schoolTeacherIds)
                ->merge($crossUnitAssignments->pluck('user_id'))
                ->unique()
                ->values();

            $nonTeachingIds = usersHavingPermission('general_staff.ineligible');
            $teachers = User::query()
                ->when(! empty($nonTeachingIds), fn ($q) => $q->whereNotIn('users.id', $nonTeachingIds))
                ->whereIn('users.id', $teacherIds)
                ->orderBy('name')->get();

            $subjects = Subject::where('is_active', true)
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->orderBy('name')->get();

            if ($selectedAyId) {
                $studyGroups = StudyGroup::with('gradeLevel')
                    ->where('school_id', $selectedSchoolId)
                    ->where('academic_year_id', $selectedAyId)
                    ->where('is_active', true)
                    ->get()
                    ->sortBy(fn ($sg) => [$sg->gradeLevel->level ?? 0, $sg->name])
                    ->values();
            }
        }

        $selectedSchool = $selectedSchoolId ? School::find($selectedSchoolId) : null;
        $crossUnitByTeacher = $crossUnitAssignments->keyBy('user_id');

        $currentAssignments = [];
        $currentTasks = [];
        $currentPositions = [];
        $currentPositionIds = [];
        $positionOptions = collect();
        $jpDefaults = [];
        $taskTypeOptions = collect();
        if ($teachers->isNotEmpty()) {
            $teacherIds = $teachers->pluck('id');
            $workUnitId = $selectedSchool?->work_unit_id;

            $jpDefaults = StudyGroupSubject::query()
                ->whereIn('study_group_id', $studyGroups->pluck('id'))
                ->whereIn('subject_id', $subjects->pluck('id'))
                ->where('is_active', true)
                ->get()
                ->mapWithKeys(fn ($row) => [$row->subject_id.'|'.$row->study_group_id => (float) $row->weekly_hours])
                ->all();

            $teachingRows = TeachingAssignment::with(['subject:id,name', 'studyGroup:id,name'])
                ->whereIn('teacher_id', $teacherIds)
                ->where('status', 'active')
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->when($selectedAyId, fn ($q) => $q->where('academic_year_id', $selectedAyId))
                ->get();

            $structuralRows = StructuralAssignment::with('position:id,name')
                ->whereIn('user_id', $teacherIds)
                ->where('status', 'active')
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->when($selectedAyId, fn ($q) => $q->where('academic_year_id', $selectedAyId))
                ->get()
                ->keyBy('user_id');

            $employmentRows = GtkEmployment::with('jabatanRel:id,name')
                ->whereIn('user_id', $teacherIds)
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->orderByDesc('academic_year_id')
                ->get()
                ->groupBy('user_id');

            $workUnitRows = GtkWorkUnit::with('position:id,name')
                ->whereIn('user_id', $teacherIds)
                ->get()
                ->groupBy('user_id');

            // Jabatan pada satuan kerja: utamakan relasi position_id, teks hanya cadangan.
            $workUnitPositionNames = GtkWorkUnit::with('position:id,name')
                ->when($workUnitId, fn ($q) => $q->where('work_unit_id', $workUnitId))
                ->get()
                ->map(fn ($row) => $row->position?->name ?? $row->jabatan)
                ->filter()
                ->map(fn ($name) => mb_strtolower(trim($name)))
                ->unique();

            // Daftar jabatan harus lengkap untuk Satuan Pendidikan, bukan hanya
            // jabatan yang kebetulan sudah dipakai pada satu work unit.
            $positionOptions = StructuralPosition::active()
                ->whereHas('jenisGtk', fn ($q) => $q->where('nama', 'like', '%Satuan Pendidikan%'))
                ->orderBy('urutan')
                ->orderBy('name')
                ->get(['id', 'name', 'urutan'])
                ->values();

            if ($positionOptions->isEmpty()) {
                $positionOptions = StructuralPosition::active()
                    ->orderBy('urutan')
                    ->orderBy('name')
                    ->get(['id', 'name', 'urutan']);
            }

            $positionOptionsByName = $positionOptions->keyBy(fn ($position) => mb_strtolower($position->name));

            $currentPositions = [];
            $currentPositionIds = [];
            foreach ($teachers as $teacher) {
                $structural = $structuralRows->get($teacher->id);
                $employment = $employmentRows->get($teacher->id)?->first();
                $externalAssignment = $crossUnitByTeacher->get($teacher->id);
                $homeWorkUnitId = $externalAssignment?->home_work_unit_id;
                $workUnitRow = $workUnitRows->get($teacher->id)?->firstWhere('work_unit_id', $homeWorkUnitId ?: $workUnitId);
                $workUnitPosition = $workUnitRow?->position?->name ?? $workUnitRow?->jabatan;
                $positionName = $workUnitPosition
                    ?? $structural?->position?->name
                    ?? $employment?->jabatanRel?->name
                    ?? $employment?->jabatan
                    ?? 'GTK';
                $currentPositions[$teacher->id] = $positionName;
                $currentPositionIds[$teacher->id] = $workUnitRow?->position_id
                    ?? $structural?->position_id
                    ?? $employment?->jabatan_id
                    ?? $positionOptionsByName->get(mb_strtolower($positionName))?->id;
            }

            $additionalRows = GtkAdditionalTask::with('additionalTaskType:id,nama,kode')
                ->whereIn('user_id', $teacherIds)
                ->when($workUnitId, fn ($q) => $q->where('work_unit_id', $workUnitId))
                ->get();

            $otherRows = OtherTeacherTask::with(['additionalTaskType:id,nama,kode', 'studyGroup:id,name'])
                ->whereIn('teacher_id', $teacherIds)
                ->whereNull('decree_id')
                ->where('is_active', true)
                ->when($selectedSchoolId, fn ($q) => $q->where('school_id', $selectedSchoolId))
                ->when($workUnitId, fn ($q) => $q->where(fn ($unit) => $unit
                    ->where('work_unit_id', $workUnitId)
                    ->orWhereNull('work_unit_id')
                    ->orWhere('work_unit_id', '')))
                ->get();

            foreach ($teachers as $teacher) {
                $currentAssignments[$teacher->id] = $teachingRows
                    ->where('teacher_id', $teacher->id)
                    ->map(fn ($row) => [
                        'subject_id' => $row->subject_id,
                        'subject' => $row->subject?->name ?? '-',
                        'study_group_id' => $row->study_group_id,
                        'rombel' => $row->studyGroup?->name ?? '-',
                        'hours' => (int) $row->weekly_hours,
                    ])->values()->all();

                $currentTasks[$teacher->id] = collect($additionalRows->where('user_id', $teacher->id)
                    ->map(fn ($row) => [
                        'name' => $row->additionalTaskType?->nama ?? $row->nama_tugas,
                        'hours' => (int) $row->hours_per_week,
                        'code' => 'additional',
                        'study_group_id' => null,
                    ])->values()->all())
                    ->merge($otherRows->where('teacher_id', $teacher->id)->map(fn ($row) => [
                        'name' => $row->additionalTaskType?->nama ?? $row->task_name,
                        'hours' => (int) $row->weekly_hours,
                        'code' => $row->additionalTaskType?->kode ?? $row->task_code,
                        'study_group_id' => $row->study_group_id,
                        'rombel' => $row->studyGroup?->name,
                    ])->values()->all())
                    ->values()->all();
            }

            $taskHourMap = AdditionalTaskTypeWorkUnitHour::where('work_unit_id', $workUnitId)
                ->pluck('weekly_hours', 'additional_task_type_id');

            // SK Pembagian Tugas Mengajar hanya memakai jenis tugas milik
            // Tenaga Kependidikan Satuan Pendidikan. Tugas unit lain (UKS,
            // keasramaan, keuangan, dsb.) tidak boleh muncul di sini.
            $taskTypeOptions = AdditionalTaskType::active()
                ->whereHas('jenisGtk', fn ($q) => $q->where('nama', 'like', '%Satuan Pendidikan%'))
                ->orderBy('urutan')
                ->orderBy('nama')
                ->get(['id', 'nama', 'kode', 'jenis_gtk_id']);

            if ($taskTypeOptions->isEmpty()) {
                // Fallback aman: hanya jenis GTK para guru/staf di unit ini,
                // bukan seluruh master lintas unit.
                $jenisGtkIds = $employmentRows->flatten()->pluck('jenis_gtk_id')->filter()->unique();
                $taskTypeOptions = AdditionalTaskType::active()
                    ->when($jenisGtkIds->isNotEmpty(), fn ($q) => $q->whereIn('jenis_gtk_id', $jenisGtkIds))
                    ->orderBy('urutan')
                    ->orderBy('nama')
                    ->get(['id', 'nama', 'kode', 'jenis_gtk_id']);
            }

            $taskTypeOptions = $taskTypeOptions->map(fn ($type) => [
                'id' => $type->id,
                'nama' => $type->nama,
                'kode' => $type->kode,
                'hours' => (float) ($taskHourMap[$type->id] ?? ($type->kode === 'wali-kelas' ? 12 : 3)),
            ])->values();

            $positionOrder = $positionOptions->mapWithKeys(
                fn ($position) => [mb_strtolower($position->name) => (int) $position->urutan]
            );
            $teachers = $teachers->sortBy(function ($teacher) use ($currentPositions, $positionOrder) {
                $position = $currentPositions[$teacher->id] ?? '';

                return sprintf(
                    '%d|%03d|%s',
                    $this->matrixPositionRank($position),
                    $positionOrder->get(mb_strtolower(trim($position)), 999),
                    mb_strtolower($teacher->name)
                );
            })->values();
        }

        // For non-global users, auto-select school from session and lock decree_type
        $canSelectSchool = $isGlobal;

        return view('institution-decrees.create', compact(
            'userId', 'schools', 'academicYears', 'activeYear', 'signers',
            'selectedSchoolId', 'selectedAyId', 'selectedSchool',
            'teachers', 'subjects', 'studyGroups', 'canSelectSchool', 'currentAssignments', 'currentTasks', 'currentPositions', 'currentPositionIds', 'positionOptions', 'jpDefaults', 'taskTypeOptions', 'crossUnitByTeacher'
        ));
    }

    public function store(Request $request, string $userId)
    {
        $schoolContextId = $request->attributes->get('schoolContextId');
        $currentUser = auth()->user();
        $isGlobal = canPermission('institution-decree-all-access');

        $selectedSchoolId = $isGlobal
            ? ($request->filled('school_id') ? $request->school_id : $schoolContextId)
            : $schoolContextId;

        $data = $request->validate([
            'decree_number' => 'required|string|max:100',
            'decree_type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'academic_year_id' => 'required|exists:academic_years,id',
            'issued_date' => 'required|date',
            'effective_date' => 'required|date|after_or_equal:issued_date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'signed_by' => 'nullable|exists:users,id',
            'signed_position' => 'nullable|string|max:100',
            'status' => 'required|in:draft,active,archived',
        ]);

        $exists = InstitutionDecree::where('decree_number', $data['decree_number'])->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Nomor SK sudah terdaftar.');
        }

        $data['school_id'] = $selectedSchoolId;

        $decree = DB::transaction(function () use ($data, $request, $selectedSchoolId) {
            $decree = InstitutionDecree::create($data);

            if ($data['decree_type'] === 'SK Pembagian Tugas') {
                $this->_saveMatrixFromJson($decree, $request, $selectedSchoolId);
                $this->_savePositionsFromJson($request, $selectedSchoolId);
            }

            return $decree;
        });

        // Bekukan isi matriks saat SK diterbitkan agar cetak ulang konsisten.
        app(InstitutionDecreeMatrixService::class)->refreshSnapshot($decree->fresh());

        return redirect()->route('user.institution-decrees.show', [
            'userId' => $userId, 'id' => $decree->id,
        ])->with('success', 'Surat Keputusan berhasil disimpan.');
    }

    private function _savePositionsFromJson(Request $request, ?string $schoolId): void
    {
        $raw = $request->input('positions_json');
        $positions = $raw ? json_decode($raw, true) : [];

        app(InstitutionDecreePayloadService::class)
            ->savePositions(is_array($positions) ? $positions : [], $schoolId);
    }

    private function _saveMatrixFromJson($decree, $request, $schoolId)
    {
        $assignments = json_decode((string) $request->input('assignments_json'), true);
        $tasks = json_decode((string) $request->input('other_tasks_json'), true);

        app(InstitutionDecreePayloadService::class)->saveMatrix(
            $decree,
            is_array($assignments) ? $assignments : [],
            is_array($tasks) ? $tasks : [],
            $schoolId
        );
    }

    /**
     * Data matriks pembagian tugas (dipakai halaman detail & cetak SK).
     *
     * Sumbernya snapshot yang dibekukan saat SK diterbitkan, sehingga cetak ulang
     * tidak berubah ketika data master diperbarui.
     */
    private function buildDecreeMatrixData(InstitutionDecree $decree, bool $preferSnapshot = true): array
    {
        return app(InstitutionDecreeMatrixService::class)->renderData($decree, $preferSnapshot);
    }

    public function show(Request $request, string $userId, string $id)
    {
        $decree = InstitutionDecree::with(['academicYear', 'school', 'signer', 'teachingAssignments'])->findOrFail($id);
        $this->assertDecreeAccess($request, $decree);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $decree,
                'matrix' => $this->buildDecreeMatrixData($decree, ! $request->boolean('live')),
            ]);
        }

        try {
            return view('institution-decrees.show', array_merge(
                [
                    'decree' => $decree,
                    'userId' => $userId,
                    'printSettings' => $this->resolvePrintSettings($decree),
                ],
                $this->buildDecreeMatrixData($decree, ! $request->boolean('live'))
            ));
        } catch (\Throwable $e) {
            Log::error('InstitutionDecree show failed', [
                'decree_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Gagal memuat Surat Keputusan.');
        }
    }

    public function print(Request $request, string $userId, string $id)
    {
        $decree = InstitutionDecree::with(['academicYear', 'school', 'signer', 'teachingAssignments'])->findOrFail($id);
        $this->assertDecreeAccess($request, $decree);

        try {
            $matrixService = app(InstitutionDecreeMatrixService::class);

            // SK lama tanpa snapshot dibekukan saat pertama kali dicetak.
            if ($matrixService->decodeSnapshot($decree) === null) {
                $matrixService->refreshSnapshot($decree);
                $decree->refresh();
            }

            $printSettings = $this->resolvePrintSettings($decree);
            $verification = $this->issueVerification($decree);
            $documentMedia = app(DocumentVisualService::class)->viewData($decree->school, 'sk');
            $documentMedia = array_merge($documentMedia, [
                'show_ttd_ksp' => $printSettings['show_ttd_ksp'] && ($documentMedia['show_ttd_ksp'] ?? true),
                'show_stamp' => $printSettings['show_stamp'] && ($documentMedia['show_stamp'] ?? true),
                'ttd_width' => $printSettings['ttd_width'],
                'ttd_height' => $printSettings['ttd_height'],
                'ttd_margin' => $printSettings['ttd_margin'],
                'stamp_width' => $printSettings['stamp_width'],
                'stamp_height' => $printSettings['stamp_height'],
                'stamp_margin' => $printSettings['stamp_margin'],
                'kop' => $printSettings['show_kop']
                    ? app(DocumentVisualService::class)->imageDataUri($decree->school?->kop_path)
                    : null,
            ]);

            $leader = app(SchoolLeaderService::class);
            $principal = $decree->signer
                ? $leader->forUser($decree->signer, $decree->school, $decree->signer->name, $decree->signer->nupy ?? null)
                : $leader->forSchool($decree->school);
            $html = view('institution-decrees.print', array_merge(
                [
                    'decree' => $decree,
                    'userId' => $userId,
                    'verification' => $verification,
                    'documentMedia' => $documentMedia,
                    'principal' => $principal,
                    'printSettings' => $printSettings,
                ],
                $matrixService->renderData($decree, true)
            ))->render();

            $paper = match (strtolower((string) $printSettings['paper_size'])) {
                'folio' => 'folio',
                'letter' => 'letter',
                default => 'A4',
            };
            $orientation = $printSettings['orientation'] === 'portrait' ? 'portrait' : 'landscape';

            $pdf = app(DocumentPdfService::class)->renderHtml($html, $orientation, $paper);

            if ($verification) {
                app(DocumentSigningService::class)->finalize($verification['document'], $pdf);
            }

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="SK-'.$decree->decree_number.'.pdf"',
            ]);
        } catch (\Throwable $e) {
            Log::error('InstitutionDecree print failed', [
                'decree_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Gagal mencetak Surat Keputusan.');
        }
    }

    public function updatePrintSettings(Request $request, string $userId, string $id)
    {
        $decree = InstitutionDecree::findOrFail($id);
        $this->assertDecreeAccess($request, $decree);

        $request->validate([
            'paper_size' => 'required|in:a4,folio,letter',
            'orientation' => 'required|in:landscape,portrait',
            'margin_top' => 'required|numeric|min:0|max:50',
            'margin_right' => 'required|numeric|min:0|max:50',
            'margin_bottom' => 'required|numeric|min:0|max:50',
            'margin_left' => 'required|numeric|min:0|max:50',
            'title_font_size' => 'required|numeric|min:6|max:30',
            'info_font_size' => 'required|numeric|min:5|max:24',
            'table_font_size' => 'required|numeric|min:5|max:20',
            'line_height' => 'required|numeric|min:1|max:2.5',
            'show_kop' => 'nullable|boolean',
            'show_ttd_ksp' => 'nullable|boolean',
            'show_stamp' => 'nullable|boolean',
            'ttd_width' => 'required|numeric|min:5|max:100',
            'ttd_height' => 'required|numeric|min:5|max:60',
            'ttd_margin' => 'nullable|numeric|min:0|max:30',
            'stamp_width' => 'required|numeric|min:5|max:100',
            'stamp_height' => 'required|numeric|min:5|max:100',
            'stamp_margin' => 'nullable|numeric|min:0|max:30',
            'city' => 'nullable|string|max:100',
        ]);

        $settings = [
            'paper_size' => $request->input('paper_size'),
            'orientation' => $request->input('orientation'),
            'margin_top' => (float) $request->input('margin_top'),
            'margin_right' => (float) $request->input('margin_right'),
            'margin_bottom' => (float) $request->input('margin_bottom'),
            'margin_left' => (float) $request->input('margin_left'),
            'title_font_size' => (float) $request->input('title_font_size'),
            'info_font_size' => (float) $request->input('info_font_size'),
            'table_font_size' => (float) $request->input('table_font_size'),
            'line_height' => (float) $request->input('line_height'),
            'show_kop' => $request->boolean('show_kop'),
            'show_ttd_ksp' => $request->boolean('show_ttd_ksp'),
            'show_stamp' => $request->boolean('show_stamp'),
            'ttd_width' => (float) $request->input('ttd_width'),
            'ttd_height' => (float) $request->input('ttd_height'),
            'ttd_margin' => (float) $request->input('ttd_margin', 2),
            'stamp_width' => (float) $request->input('stamp_width'),
            'stamp_height' => (float) $request->input('stamp_height'),
            'stamp_margin' => (float) $request->input('stamp_margin', 2),
            'city' => $request->input('city'),
        ];

        $decree->update(['print_settings' => $settings]);

        return back()->with('success', 'Pengaturan cetak SK berhasil disimpan.');
    }

    private function defaultPrintSettings(): array
    {
        return [
            'paper_size' => 'a4',
            'orientation' => 'landscape',
            'margin_top' => 15.0,
            'margin_right' => 15.0,
            'margin_bottom' => 15.0,
            'margin_left' => 20.0,
            'title_font_size' => 13.0,
            'info_font_size' => 10.0,
            'table_font_size' => 9.0,
            'line_height' => 1.25,
            'show_kop' => false,
            'show_ttd_ksp' => true,
            'show_stamp' => true,
            'ttd_width' => 30.0,
            'ttd_height' => 15.0,
            'ttd_margin' => 2.0,
            'stamp_width' => 30.0,
            'stamp_height' => 30.0,
            'stamp_margin' => 2.0,
            'city' => '',
        ];
    }

    private function resolvePrintSettings(InstitutionDecree $decree): array
    {
        return array_merge(
            $this->defaultPrintSettings(),
            is_array($decree->print_settings) ? $decree->print_settings : []
        );
    }

    /**
     * Daftarkan SK ke registry verifikasi publik.
     */
    private function issueVerification(InstitutionDecree $decree): ?array
    {
        try {
            $mode = app(DocumentVisualService::class)->modeForPrinting($decree->school, 'sk');

            if ($mode === 'visual') {
                return null;
            }

            $signers = [];

            if ($decree->signer?->name) {
                $leader = app(SchoolLeaderService::class)->forUser(
                    $decree->signer,
                    $decree->school,
                    $decree->signer->name,
                    $decree->signer->nupy ?? null,
                );
                $signers[] = [
                    'user_id' => $decree->signer->id,
                    'name' => $leader['display_name'],
                    'role' => $decree->signed_position ?: 'Penandatangan SK',
                ];
            }

            return app(DocumentSigningService::class)->issue([
                'document_type' => 'sk',
                'document_number' => $decree->decree_number,
                'title' => $decree->title,
                'school_id' => $decree->school_id,
                'academic_year_id' => $decree->academic_year_id,
                'subject_type' => 'institution_decree',
                'subject_id' => $decree->id,
                'issued_by' => auth()->id(),
                'validation_mode' => $mode,
                'metadata' => [
                    'signed_position' => $decree->signed_position,
                ],
            ], $signers);
        } catch (\Throwable $e) {
            Log::warning('Gagal menerbitkan registry SK: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Halaman Edit SK memakai format & editor yang sama dengan halaman Buat SK.
     */
    public function edit(Request $request, string $userId, string $id)
    {
        $decree = InstitutionDecree::with(['academicYear', 'school', 'signer'])->findOrFail($id);
        $this->assertDecreeAccess($request, $decree);

        $editRequest = Request::create($request->url(), 'GET', [
            'school_id' => $decree->school_id,
            'academic_year_id' => $decree->academic_year_id,
        ]);
        $editRequest->attributes->set('schoolContextId', $request->attributes->get('schoolContextId'));

        $view = $this->create($editRequest, $userId);
        $data = $view->getData();

        $currentAssignments = $decree->teachingAssignments()
            ->with(['subject:id,name', 'studyGroup:id,name'])
            ->where('status', 'active')
            ->get()
            ->groupBy('teacher_id')
            ->map(fn ($rows) => $rows->map(fn ($assignment) => [
                'subject_id' => $assignment->subject_id,
                'subject' => $assignment->subject?->name,
                'study_group_id' => $assignment->study_group_id,
                'rombel' => $assignment->studyGroup?->name,
                'hours' => (int) $assignment->weekly_hours,
            ])->values()->all())
            ->all();

        $currentTasks = OtherTeacherTask::with(['additionalTaskType:id,nama,kode', 'studyGroup:id,name'])
            ->where('decree_id', $decree->id)
            ->where('is_active', true)
            ->get()
            ->groupBy('teacher_id')
            ->map(fn ($rows) => $rows->map(fn ($task) => [
                'name' => $task->additionalTaskType?->nama ?? $task->task_name,
                'hours' => (int) $task->weekly_hours,
                'code' => $task->additionalTaskType?->kode ?? $task->task_code,
                'study_group_id' => $task->study_group_id,
                'rombel' => $task->studyGroup?->name,
            ])->values()->all())
            ->all();

        // Pastikan GTK yang sudah terhubung ke SK tetap ada di roster editor,
        // termasuk guru lintas lembaga dan guru yang hanya memegang tugas.
        $linkedIds = collect(array_keys($currentAssignments))
            ->merge(array_keys($currentTasks))
            ->unique();

        $teachers = $data['teachers'];
        $missingTeachers = User::whereIn('id', $linkedIds)
            ->whereNotIn('id', $teachers->pluck('id'))
            ->orderBy('name')
            ->get();

        if ($missingTeachers->isNotEmpty()) {
            $teachers = $teachers->merge($missingTeachers)->sortBy('name')->values();

            foreach ($missingTeachers as $teacher) {
                $data['currentPositions'][$teacher->id] ??= 'GTK';
                $data['currentPositionIds'][$teacher->id] ??= null;
            }
        }

        return $view->with(array_merge($data, [
            'decree' => $decree,
            'isEdit' => true,
            'canSelectSchool' => false,
            'selectedSchool' => $decree->school,
            'selectedSchoolId' => $decree->school_id,
            'selectedAyId' => $decree->academic_year_id,
            'teachers' => $teachers,
            'currentAssignments' => $currentAssignments,
            'currentTasks' => $currentTasks,
            'printSettings' => $this->resolvePrintSettings($decree),
        ]));
    }

    public function update(Request $request, string $userId, string $id)
    {
        $decree = InstitutionDecree::findOrFail($id);
        $this->assertDecreeAccess($request, $decree);

        $data = $request->validate([
            'decree_number' => 'required|string|max:100|unique:institution_decrees,decree_number,'.$decree->id,
            'decree_type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'academic_year_id' => 'required|exists:academic_years,id',
            'issued_date' => 'required|date',
            'effective_date' => 'required|date|after_or_equal:issued_date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'signed_by' => 'nullable|exists:users,id',
            'signed_position' => 'nullable|string|max:100',
            'status' => 'required|in:draft,active,archived',
        ]);

        // Rombel & penugasan terikat tahun ajaran. Mencegah matriks lama
        // menjadi yatim ketika TA diubah tanpa mengirim ulang matriks.
        if ((string) $decree->academic_year_id !== (string) $data['academic_year_id']
            && $decree->teachingAssignments()->exists()
            && ! $request->has('assignments_json')) {
            return back()->withInput()->with(
                'error',
                'Tahun ajaran tidak dapat diubah langsung karena SK sudah memiliki matriks. Ubah lewat Edit Matriks atau kosongkan penugasan terlebih dahulu.'
            );
        }

        try {
            DB::transaction(function () use ($decree, $data, $request) {
                $decree->update($data);

                $hasAssignments = $request->has('assignments_json');
                $hasTasks = $request->has('other_tasks_json');

                if ($hasAssignments || $hasTasks) {
                    $assignments = $hasAssignments
                        ? (json_decode((string) $request->input('assignments_json'), true) ?: [])
                        : null;
                    $tasks = $hasTasks
                        ? (json_decode((string) $request->input('other_tasks_json'), true) ?: [])
                        : null;

                    app(InstitutionDecreeMatrixService::class)->sync(
                        $decree,
                        $assignments,
                        $tasks,
                        $decree->school_id,
                        true
                    );
                }

                if ($request->has('positions_json')) {
                    $this->_savePositionsFromJson($request, $decree->school_id);
                }
            });

            // Metadata bisa mengubah TA/sekolah; segarkan snapshot agar konsisten.
            app(InstitutionDecreeMatrixService::class)->refreshSnapshot($decree->fresh());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Surat Keputusan berhasil diperbarui.',
                    'data' => $decree->fresh(),
                ]);
            }

            return redirect()->route('user.institution-decrees.show', [
                'userId' => $userId, 'id' => $decree->id,
            ])->with('success', 'Surat Keputusan & matriks berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('InstitutionDecree update failed', [
                'decree_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal memperbarui Surat Keputusan.'], 500);
            }

            return back()->withInput()->with('error', 'Gagal memperbarui Surat Keputusan: '.$e->getMessage());
        }
    }

    public function destroy(Request $request, string $userId, string $id)
    {
        $decree = InstitutionDecree::findOrFail($id);
        $this->assertDecreeAccess($request, $decree);

        try {
            DB::transaction(function () use ($decree) {
                OtherTeacherTask::where('decree_id', $decree->id)->delete();
                TeachingAssignment::where('decree_id', $decree->id)->delete();
                $decree->delete();
            });

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Surat Keputusan berhasil dihapus.']);
            }

            return redirect()->route('user.institution-decrees.index', [
                'userId' => $userId,
            ])->with('success', 'Surat Keputusan berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('InstitutionDecree destroy failed', [
                'decree_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal menghapus Surat Keputusan.'], 500);
            }

            return back()->with('error', 'Gagal menghapus Surat Keputusan.');
        }
    }

    /**
     * Peringkat jabatan pada matriks SK: Kepala -> Wakil -> Koordinator -> Guru/Staf -> Tata Usaha.
     */
    private function matrixPositionRank(?string $position): int
    {
        $name = mb_strtolower(trim((string) $position));

        if ($name === '') {
            return 5;
        }

        if (str_contains($name, 'tata usaha') || preg_match('/\btu\b/', $name) === 1) {
            return 9;
        }

        if (str_starts_with($name, 'kepala')) {
            return 1;
        }

        if (str_starts_with($name, 'wakil')) {
            return 2;
        }

        if (str_starts_with($name, 'koordinator')) {
            return 3;
        }

        if (str_starts_with($name, 'staf') || str_starts_with($name, 'staff')) {
            return 6;
        }

        return 4;
    }

    // ─────────────────────────────────────────────────────────────
    // IMPORT SK & MATRIKS
    // ─────────────────────────────────────────────────────────────

    public function importForm(Request $request, string $userId)
    {
        $isGlobal = canPermission('institution-decree-all-access');
        $schoolContextId = $request->attributes->get('schoolContextId');

        $selectedSchoolId = $isGlobal
            ? ($request->filled('school_id') ? $request->school_id : $schoolContextId)
            : $schoolContextId;

        $schools = $isGlobal
            ? School::orderBy('name')->get()
            : School::whereKey($schoolContextId)->get();

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $selectedAyId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : AcademicYear::active()->first()?->id;

        return view('institution-decrees.import', compact(
            'userId', 'schools', 'academicYears', 'selectedSchoolId', 'selectedAyId', 'isGlobal'
        ));
    }

    public function importTemplate(Request $request, string $userId)
    {
        $isGlobal = canPermission('institution-decree-all-access');
        $schoolContextId = $request->attributes->get('schoolContextId');

        $schoolId = $isGlobal
            ? ($request->filled('school_id') ? $request->school_id : $schoolContextId)
            : $schoolContextId;

        abort_if(! $schoolId, 422, 'Pilih satuan pendidikan terlebih dahulu.');
        $school = School::findOrFail($schoolId);
        $this->assertSchoolAccess($request, $schoolId);

        $ayId = $request->filled('academic_year_id') ? $request->academic_year_id : null;
        $ay = $ayId ? AcademicYear::find($ayId) : AcademicYear::active()->first();

        return (new InstitutionDecreeTemplateExport($school, $ay))->download();
    }

    public function importStore(Request $request, string $userId)
    {
        $isGlobal = canPermission('institution-decree-all-access');
        $schoolContextId = $request->attributes->get('schoolContextId');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'school_id' => $isGlobal ? 'required|exists:schools,id' : 'nullable',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'update_existing' => 'nullable|boolean',
        ], [
            'file.required' => 'File Excel wajib diunggah.',
            'file.mimes' => 'File harus berformat .xlsx, .xls, atau .csv.',
        ]);

        $schoolId = $isGlobal ? $request->input('school_id') : $schoolContextId;

        abort_if(! $schoolId, 422, 'Tidak dapat menentukan satuan pendidikan.');
        $this->assertSchoolAccess($request, $schoolId);

        $school = School::findOrFail($schoolId);
        $ay = $request->filled('academic_year_id')
            ? AcademicYear::find($request->academic_year_id)
            : AcademicYear::active()->first();

        try {
            $result = (new InstitutionDecreeImport(
                $school,
                $ay,
                $request->boolean('update_existing'),
                $userId
            ))->import($request->file('file'));
        } catch (\Throwable $e) {
            Log::error('InstitutionDecree import failed', [
                'school_id' => $schoolId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('user.institution-decrees.import-form', ['userId' => $userId])
                ->with('error', 'Gagal membaca file: '.$e->getMessage());
        }

        $created = $result['created'] ?? 0;
        $updated = $result['updated'] ?? 0;
        $errors = $result['errors'] ?? [];
        $warnings = $result['warnings'] ?? [];
        $assignmentCount = $result['assignments'] ?? 0;
        $taskCount = $result['tasks'] ?? 0;

        $message = "Import selesai: {$created} SK baru";
        if ($updated > 0) {
            $message .= ", {$updated} SK diperbarui";
        }
        $message .= " ({$assignmentCount} penugasan, {$taskCount} tugas tambahan).";

        if ($created === 0 && $updated === 0) {
            return redirect()
                ->route('user.institution-decrees.import-form', ['userId' => $userId])
                ->with('error', 'Tidak ada SK yang berhasil diimport.')
                ->with('import_errors', $errors)
                ->with('import_warnings', $warnings);
        }

        return redirect()
            ->route('user.institution-decrees.index', ['userId' => $userId])
            ->with('success', $message)
            ->with('import_errors', $errors)
            ->with('import_warnings', $warnings);
    }

    private function assertSchoolAccess(Request $request, string $schoolId): void
    {
        if (canPermission('institution-decree-all-access')) {
            return;
        }

        $contextId = $request->attributes->get('schoolContextId');

        if ($contextId === null || (string) $contextId !== (string) $schoolId) {
            abort(403, 'Akses ditolak.');
        }
    }

    private function assertDecreeAccess(Request $request, InstitutionDecree $decree): void
    {
        if (canPermission('institution-decree-all-access')) {
            return;
        }

        $contextId = $request->attributes->get('schoolContextId');

        if ($contextId === null) {
            abort_if($decree->school_id !== null, 403, 'Akses ditolak.');

            return;
        }

        abort_if((string) $decree->school_id !== (string) $contextId, 403, 'Akses ditolak.');
    }
}
