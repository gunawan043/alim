<?php

namespace App\Http\Controllers;

use App\Exports\JadwalKbmMatrixExport;
use App\Http\Requests\JadwalKbmGenerateRequest;
use App\Http\Requests\JadwalKbmUpdateRequest;
use App\Models\AcademicYear;
use App\Models\JadwalKbm;
use App\Models\OtherTeacherTask;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TahfidzSchedule;
use App\Models\TeacherClassAttendance;
use App\Models\TeacherScheduleAvailability;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Arsip\RaporPdfService;
use App\Services\JadwalGeneratorService;
use App\Services\Pdf\DocumentPdfService;
use App\Services\SchoolGroupService;
use App\Services\TeacherAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class JadwalKbmController extends Controller
{
    public function index(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $schoolId = $this->resolveSchoolId($request);
        $activeAy = AcademicYear::where('is_active', true)->first();

        $studyGroups = StudyGroup::with(['gradeLevel', 'homeroomTeacher'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderedByGrade()
            ->get();

        $jadwals = JadwalKbm::with(['studyGroup.gradeLevel', 'teacher', 'subject', 'studyGroup'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get()
            ->groupBy('study_group_id');

        // Filter cepat: sudah / belum terjadwal.
        $status = $request->input('status');
        if ($status === 'terjadwal') {
            $studyGroups = $studyGroups->filter(fn ($sg) => ($jadwals[$sg->id] ?? collect())->isNotEmpty())->values();
        } elseif ($status === 'belum') {
            $studyGroups = $studyGroups->filter(fn ($sg) => ($jadwals[$sg->id] ?? collect())->isEmpty())->values();
        }

        $stats = [
            'rombel' => $studyGroups->count(),
            'terjadwal' => $studyGroups->filter(fn ($sg) => ($jadwals[$sg->id] ?? collect())->isNotEmpty())->count(),
            'slot' => (int) $studyGroups->sum(fn ($sg) => ($jadwals[$sg->id] ?? collect())->count()),
            'guru' => $studyGroups->flatMap(fn ($sg) => ($jadwals[$sg->id] ?? collect())->pluck('teacher_id'))->filter()->unique()->count(),
        ];

        $matrixData = $this->buildMatrixData($studyGroups, $jadwals, $schoolId);

        return view('jadwal-kbm.index', compact(
            'studyGroups', 'jadwals', 'activeAy', 'stats', 'status',
            'matrixData',
        ));
    }

    public function generateIndex(Request $request, string $userId)
    {
        $this->authorizeGenerate($request);

        $schoolId = $this->resolveSchoolId($request);

        $studyGroups = StudyGroup::with('gradeLevel', 'homeroomTeacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        // Pilih TA: ikuti pilihan pengguna → TA yang punya penugasan → TA aktif → pertama.
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $selectedAy = $this->resolveAy($request, $studyGroups, $academicYears);

        $semester = $request->input('semester', $selectedAy?->semester ?? 'ganjil');
        if (! in_array($semester, ['ganjil', 'genap'], true)) {
            $semester = 'ganjil';
        }

        // Ringkasan penugasan mengajar (SK guru) per rombel — sumber JP yang akan digenerate.
        $assignmentSummary = collect();
        if ($selectedAy && $studyGroups->isNotEmpty()) {
            $assignmentSummary = TeachingAssignment::query()
                ->where('academic_year_id', $selectedAy->id)
                ->where('status', 'active')
                ->whereIn('study_group_id', $studyGroups->pluck('id'))
                ->selectRaw('study_group_id, COUNT(*) as total_subjects, COALESCE(SUM(weekly_hours), 0) as total_hours')
                ->groupBy('study_group_id')
                ->get()
                ->keyBy('study_group_id');
        }

        // Tugas mengajar tambahan (data pendukung, bukan sumber JP generator).
        $otherTasks = OtherTeacherTask::with(['teacher:id,name', 'studyGroup:id,name'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($selectedAy, fn ($q) => $q->where('academic_year_id', $selectedAy->id))
            ->where('is_active', true)
            ->orderBy('task_name')
            ->get();

        $masterSlotCount = $schoolId
            ? DB::table('class_schedule_slots')->where('school_id', $schoolId)->where('is_active', 1)->count()
            : 0;

        $readyCount = $assignmentSummary->count();

        return view('jadwal-kbm.generate', compact(
            'studyGroups',
            'academicYears',
            'selectedAy',
            'semester',
            'assignmentSummary',
            'otherTasks',
            'masterSlotCount',
            'readyCount'
        ));
    }

    /**
     * Tentukan Tahun Ajaran untuk halaman generate:
     * pilihan pengguna → TA dengan penugasan terbanyak → TA aktif → pertama.
     */
    private function resolveAy(Request $request, $studyGroups, $academicYears): ?AcademicYear
    {
        if ($request->filled('academic_year_id')) {
            $found = $academicYears->firstWhere('id', $request->academic_year_id);
            if ($found) {
                return $found;
            }
        }

        if ($studyGroups->isNotEmpty()) {
            $withAssignments = TeachingAssignment::query()
                ->where('status', 'active')
                ->whereIn('study_group_id', $studyGroups->pluck('id'))
                ->selectRaw('academic_year_id, COUNT(*) as total')
                ->groupBy('academic_year_id')
                ->orderByDesc('total')
                ->pluck('academic_year_id');

            if ($withAssignments->isNotEmpty()) {
                $found = $academicYears->firstWhere('id', $withAssignments->first());
                if ($found) {
                    return $found;
                }
            }
        }

        return $academicYears->firstWhere('is_active', true) ?? $academicYears->first();
    }

    public function generate(JadwalKbmGenerateRequest $request, string $userId, JadwalGeneratorService $generator)
    {
        $data = $request->validated();

        try {
            $results = $generator->generateBulk(
                $data['study_group_ids'],
                $data['academic_year_id'],
                $data['semester'],
                (bool) ($data['overwrite'] ?? false)
            );
        } catch (\Throwable $e) {
            Log::warning('Generate jadwal gagal: '.$e->getMessage(), [
                'study_group_ids' => $data['study_group_ids'],
            ]);

            return back()->with('error', 'Generate jadwal gagal: '.$e->getMessage());
        }

        $report = $this->buildReport($results);

        $redirect = redirect()
            ->route('user.jadwal-kbm.index', ['userId' => $userId])
            ->with('shortage_report', $report)
            ->with('success', "Generate selesai: {$report['total_generated']} slot dari {$report['total_requested']} JP.");

        if ($report['total_missing'] > 0) {
            $redirect->with('warning', "{$report['total_missing']} JP belum mendapatkan slot — lihat laporan kekurangan JP.");
        }

        return $redirect;
    }

    /**
     * Generate per rombel (dipertahankan dari fitur lama).
     */
    public function generateSingle(Request $request, string $userId, string $studyGroupId, JadwalGeneratorService $generator)
    {
        $this->authorizeGenerate($request);

        $schoolId = $this->resolveSchoolId($request);

        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $activeGroups = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->get();

        $academicYears = AcademicYear::orderByDesc('name')->get();
        $selectedAy = $this->resolveAy($request, $activeGroups, $academicYears);
        abort_unless($selectedAy, 422, 'Tahun ajaran tidak ditemukan.');

        $semester = $request->input('semester', $selectedAy->semester ?? 'ganjil');
        $overwrite = $request->boolean('overwrite', true);

        $result = $generator->generateForStudyGroup($studyGroup->id, $selectedAy->id, $semester, $overwrite);
        $report = $this->buildReport(collect([$result]));

        $redirect = redirect()
            ->route('user.jadwal-kbm.show', ['userId' => $userId, 'studyGroupId' => $studyGroupId])
            ->with('shortage_report', $report)
            ->with('success', "Generate {$studyGroup->full_name}: {$result['generated']} slot dari {$result['requested']} JP.");

        if (! empty($result['shortages'])) {
            $redirect->with('warning', 'Sebagian JP belum mendapatkan slot — lihat laporan kekurangan JP.');
        }

        return $redirect;
    }

    /**
     * Pengaturan hari/jam yang tersedia untuk tiap guru.
     */
    public function availability(
        Request $request,
        string $userId,
        JadwalGeneratorService $generator,
        TeacherAvailabilityService $availabilityService,
    ) {
        $this->authorizeGenerate($request);

        $schoolId = $this->resolveSchoolId($request);
        $teachers = $availabilityService->teachersForSchool($schoolId);

        $selectedTeacherId = (string) $request->input('teacher_id', '');
        $selectedTeacher = $teachers->firstWhere('id', $selectedTeacherId) ?? $teachers->first();

        $masterDays = $generator->masterSlotsByDay($schoolId);
        $availability = $selectedTeacher
            ? TeacherScheduleAvailability::where('school_id', $schoolId)
                ->where('teacher_id', $selectedTeacher->id)
                ->first()
            : null;

        // Tanpa baris pengaturan = semua slot mengajar tersedia.
        $selectedSlots = [];

        if ($availability === null) {
            foreach ($masterDays as $day => $slots) {
                foreach ($slots as $slot) {
                    if (! $slot['is_break']) {
                        $selectedSlots[(int) $day][] = (int) $slot['slot'];
                    }
                }
            }
        } else {
            foreach ((array) $availability->available_slots as $day => $slots) {
                $selectedSlots[(int) $day] = array_map('intval', (array) $slots);
            }
        }

        $configuredCount = TeacherScheduleAvailability::where('school_id', $schoolId)->count();
        $days = $this->days();

        return view('jadwal-kbm.availability', compact(
            'userId',
            'teachers',
            'selectedTeacher',
            'selectedSlots',
            'masterDays',
            'days',
            'configuredCount',
            'availability',
        ));
    }

    public function availabilityStore(
        Request $request,
        string $userId,
        JadwalGeneratorService $generator,
        TeacherAvailabilityService $availabilityService,
    ) {
        $this->authorizeGenerate($request);

        $data = $request->validate([
            'teacher_id' => 'required|uuid|exists:users,id',
            'reset' => 'sometimes|boolean',
            'available' => 'nullable|array',
            'available.*' => 'array',
            'available.*.*' => 'integer|min:1|max:30',
        ]);

        $schoolId = $this->resolveSchoolId($request);
        $teacherIds = $availabilityService->teachersForSchool($schoolId)->pluck('id')->map(fn ($id) => (string) $id)->all();

        abort_unless(in_array((string) $data['teacher_id'], $teacherIds, true), 403, 'Guru tidak berada pada sekolah ini.');

        if ($request->boolean('reset')) {
            TeacherScheduleAvailability::where('school_id', $schoolId)
                ->where('teacher_id', $data['teacher_id'])
                ->delete();

            $availabilityService->forget((string) $data['teacher_id'], $schoolId);

            return redirect()
                ->route('user.jadwal-kbm.availability', ['userId' => $userId, 'teacher_id' => $data['teacher_id']])
                ->with('success', 'Pembatasan ketersediaan guru dihapus. Guru kembali tersedia pada semua jam mengajar.');
        }

        $available = [];

        foreach ((array) ($data['available'] ?? []) as $day => $slots) {
            $day = (int) $day;

            if ($day < 1 || $day > 6) {
                continue;
            }

            foreach ((array) $slots as $slot) {
                $slot = (int) $slot;

                if ($generator->isTeachingSlot($schoolId, $day, $slot)) {
                    $available[$day][] = $slot;
                }
            }

            if (isset($available[$day])) {
                $available[$day] = array_values(array_unique($available[$day]));
                sort($available[$day]);
            }
        }

        $allTeachingSlots = [];

        foreach ($generator->masterSlotsByDay($schoolId) as $day => $slots) {
            foreach ($slots as $slot) {
                if (! $slot['is_break']) {
                    $allTeachingSlots[(int) $day][] = (int) $slot['slot'];
                }
            }

            if (isset($allTeachingSlots[(int) $day])) {
                sort($allTeachingSlots[(int) $day]);
            }
        }

        ksort($allTeachingSlots);
        ksort($available);

        // Semua slot dipilih = tidak ada pembatasan; hapus baris agar slot baru
        // pada master jam pelajaran tetap otomatis tersedia.
        if ($available === $allTeachingSlots) {
            TeacherScheduleAvailability::where('school_id', $schoolId)
                ->where('teacher_id', $data['teacher_id'])
                ->delete();
        } else {
            TeacherScheduleAvailability::updateOrCreate(
                ['school_id' => $schoolId, 'teacher_id' => $data['teacher_id']],
                ['available_slots' => $available],
            );
        }

        $availabilityService->forget((string) $data['teacher_id'], $schoolId);

        $redirect = redirect()
            ->route('user.jadwal-kbm.availability', ['userId' => $userId, 'teacher_id' => $data['teacher_id']])
            ->with('success', $available === $allTeachingSlots
                ? 'Semua slot tersedia; pembatasan ketersediaan guru dihapus.'
                : 'Ketersediaan guru berhasil disimpan.');

        if ($available === []) {
            $redirect->with('warning', 'Tidak ada slot yang dipilih. Guru tidak akan ditempatkan pada jadwal sampai ketersediaan diisi kembali.');
        }

        return $redirect;
    }

    /**
     * Export matriks jadwal seluruh rombel ke Excel.
     */
    public function exportExcel(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $schoolId = $this->resolveSchoolId($request);
        $activeAy = AcademicYear::where('is_active', true)->first();

        $studyGroups = StudyGroup::with(['gradeLevel', 'homeroomTeacher', 'school'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        $jadwals = JadwalKbm::with(['teacher', 'subject', 'studyGroup'])
            ->whereIn('study_group_id', $studyGroups->pluck('id'))
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->orderBy('study_group_id')
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get()
            ->groupBy('study_group_id');

        $matrixData = $this->buildMatrixData($studyGroups, $jadwals, $schoolId);
        $school = $studyGroups->first()?->school ?? ($schoolId ? School::find($schoolId) : null);

        return Excel::download(
            new JadwalKbmMatrixExport(compact('studyGroups', 'matrixData', 'school', 'activeAy')),
            'Jadwal-KBM-Semua-Rombel-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    /**
     * Cetak jadwal seluruh rombel dalam satu PDF.
     */
    public function cetakAll(Request $request, string $userId)
    {
        $this->authorizeView($request);

        $schoolId = $this->resolveSchoolId($request);
        $activeAy = AcademicYear::where('is_active', true)->first();

        $studyGroups = StudyGroup::with(['gradeLevel', 'homeroomTeacher', 'school'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        $jadwals = JadwalKbm::with(['teacher', 'subject', 'studyGroup'])
            ->whereIn('study_group_id', $studyGroups->pluck('id'))
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->orderBy('study_group_id')
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get()
            ->groupBy('study_group_id');

        $matrixData = $this->buildMatrixData($studyGroups, $jadwals, $schoolId);

        $school = $studyGroups->first()?->school
            ?? ($schoolId ? School::find($schoolId) : null);
        $pdfMedia = app(RaporPdfService::class);
        $kopBase64 = $pdfMedia->schoolKopBase64($school);
        $ttdKspBase64 = $pdfMedia->imageDataUri($school?->ttd_ksp_path);
        $stampBase64 = $pdfMedia->imageDataUri($school?->stamp_path);

        return app(DocumentPdfService::class)->fromView(
            'jadwal-kbm.cetak-all',
            compact(
                'studyGroups', 'jadwals', 'activeAy', 'matrixData',
                'school', 'kopBase64', 'ttdKspBase64', 'stampBase64',
            ),
            'Jadwal-KBM-Semua-Rombel',
            'landscape',
        );
    }

    public function show(Request $request, string $userId, string $studyGroupId)
    {
        $this->authorizeView($request);

        $schoolId = $this->resolveSchoolId($request);
        $academicYears = AcademicYear::orderByDesc('name')->get();

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        // TA: yang punya jadwal untuk rombel ini → yang punya penugasan → TA aktif.
        $ayWithJadwal = JadwalKbm::where('study_group_id', $studyGroupId)
            ->selectRaw('academic_year_id, COUNT(*) as total')
            ->groupBy('academic_year_id')
            ->orderByDesc('total')
            ->pluck('academic_year_id')
            ->first();

        $activeAy = $ayWithJadwal
            ? ($academicYears->firstWhere('id', $ayWithJadwal) ?? AcademicYear::find($ayWithJadwal))
            : $this->resolveAy($request, collect([$studyGroup]), $academicYears);

        $jadwals = JadwalKbm::with(['teacher', 'subject'])
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $activeAy?->id)
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get()
            ->groupBy('day_of_week');

        // Master slot sekolah (dipakai untuk baris istirahat/kegiatan + waktu).
        $masterSlots = collect();
        if ($schoolId) {
            $masterSlots = DB::table('class_schedule_slots')
                ->where('school_id', $schoolId)
                ->where('is_active', 1)
                ->orderBy('day_of_week')
                ->orderBy('time_start')
                ->orderBy('slot_number')
                ->get(['day_of_week', 'slot_number', 'label', 'time_start', 'time_end', 'is_break'])
                ->groupBy('day_of_week');
        }

        $tahfidzSchedules = TahfidzSchedule::with(['group:id,name,study_group_id,teacher_id,coordinator_id', 'group.teacher:id,name', 'group.coordinator:id,name'])
            ->where('is_active', true)
            ->where('academic_year_id', $activeAy?->id)
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', today()))
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', today()))
            ->whereHas('group', fn ($q) => $q->where('study_group_id', $studyGroupId)->where('is_active', true))
            ->orderBy('day_of_week')->orderBy('time_start')
            ->get()->groupBy('day_of_week');

        $days = $this->days();

        return view('jadwal-kbm.show', compact('studyGroup', 'jadwals', 'days', 'activeAy', 'masterSlots', 'tahfidzSchedules'));
    }

    /**
     * Editor manual seluruh rombel dalam satu matriks.
     */
    public function editAll(Request $request, string $userId)
    {
        $this->authorizeUpdate($request);

        $schoolId = $this->resolveSchoolId($request);
        $activeAy = AcademicYear::where('is_active', true)->first();

        $studyGroups = StudyGroup::with(['gradeLevel', 'homeroomTeacher'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        $jadwals = JadwalKbm::with(['teacher', 'subject', 'studyGroup'])
            ->whereIn('study_group_id', $studyGroups->pluck('id'))
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->orderBy('study_group_id')
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get()
            ->groupBy('study_group_id');

        $matrixData = $this->buildMatrixData($studyGroups, $jadwals, $schoolId);

        $teachers = app(TeacherAvailabilityService::class)->teachersForSchool($schoolId);

        $subjects = Subject::where(function ($q) use ($schoolId) {
            $q->whereNull('school_id');
            if ($schoolId) {
                $q->orWhere('school_id', $schoolId);
            }
        })->orderBy('name')->get(['id', 'name', 'code']);

        // Peta guru default per rombel × mapel dari penugasan mengajar.
        $teacherBySubject = TeachingAssignment::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->where('status', 'active')
            ->get(['study_group_id', 'subject_id', 'teacher_id'])
            ->groupBy('study_group_id')
            ->map(fn (Collection $rows) => $rows->pluck('teacher_id', 'subject_id')->toArray())
            ->toArray();

        return view('jadwal-kbm.edit-all', compact(
            'userId', 'studyGroups', 'jadwals', 'activeAy', 'matrixData', 'teachers', 'subjects', 'teacherBySubject',
        ));
    }

    public function edit(Request $request, string $userId, string $studyGroupId)
    {
        $this->authorizeUpdate($request);

        $schoolId = $this->resolveSchoolId($request);
        $activeAy = AcademicYear::where('is_active', true)->first();

        $studyGroup = StudyGroup::with('gradeLevel')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $jadwals = JadwalKbm::with(['teacher', 'subject'])
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $activeAy?->id)
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get();

        $teachers = app(TeacherAvailabilityService::class)->teachersForSchool($schoolId);

        $subjects = Subject::where(function ($q) use ($schoolId) {
            $q->whereNull('school_id');
            if ($schoolId) {
                $q->orWhere('school_id', $schoolId);
            }
        })->orderBy('name')->get(['id', 'name', 'code']);

        $days = $this->days();
        $maxSlots = JadwalGeneratorService::MAX_PERIODS_PER_DAY;

        return view('jadwal-kbm.edit', compact('studyGroup', 'jadwals', 'teachers', 'subjects', 'days', 'maxSlots', 'activeAy'));
    }

    public function storeEntry(
        Request $request,
        string $userId,
        string $studyGroupId,
        JadwalGeneratorService $generator,
        TeacherAvailabilityService $availability,
    ) {
        $this->authorizeUpdate($request);

        $schoolId = $this->resolveSchoolId($request);
        $activeAy = AcademicYear::where('is_active', true)->first();

        if (! $activeAy) {
            return back()->with('error', 'Tahun ajaran aktif belum ditetapkan.');
        }

        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $data = $request->validate([
            'day_of_week' => 'required|integer|between:1,6',
            'slot_index' => 'required|integer|between:1,'.JadwalGeneratorService::MAX_PERIODS_PER_DAY,
            'subject_id' => 'required|uuid|exists:subjects,id',
            'teacher_id' => 'nullable|uuid|exists:users,id',
            'room' => 'nullable|string|max:50',
        ]);

        $day = (int) $data['day_of_week'];
        $slot = (int) $data['slot_index'];
        $teacherId = $data['teacher_id'] ?? null;

        if (! $generator->isTeachingSlot($studyGroup->school_id, $day, $slot)) {
            return back()->with('warning', 'Slot tersebut adalah jam istirahat atau di luar master jam pelajaran.');
        }

        $warnings = [];

        if ($teacherId && ! $availability->isAllowed((string) $teacherId, $studyGroup->school_id, $day, $slot)) {
            $warnings[] = 'Guru tidak tersedia pada hari/jam tersebut.';
        }

        $teacherConflict = $teacherId
            ? JadwalKbm::where('teacher_id', $teacherId)
                ->where('day_of_week', $day)
                ->where('slot_index', $slot)
                ->where('academic_year_id', $activeAy->id)
                ->where('is_active', true)
                ->exists()
            : false;

        $groupConflict = JadwalKbm::where('study_group_id', $studyGroup->id)
            ->where('day_of_week', $day)
            ->where('slot_index', $slot)
            ->where('academic_year_id', $activeAy->id)
            ->where('is_active', true)
            ->exists();

        if ($teacherConflict) {
            $warnings[] = 'Guru sudah mengajar pada hari dan jam tersebut.';
        }

        if ($groupConflict) {
            $warnings[] = 'Rombel sudah memiliki pelajaran pada hari dan jam tersebut.';
        }

        $times = $generator->resolveSlotTimesPublic($slot, $day, $studyGroup->school_id);

        JadwalKbm::create([
            'school_id' => $studyGroup->school_id,
            'academic_year_id' => $activeAy->id,
            'study_group_id' => $studyGroup->id,
            'subject_id' => $data['subject_id'],
            'teacher_id' => $teacherId,
            'day_of_week' => $day,
            'slot_index' => $slot,
            'start_time' => $times['start'],
            'end_time' => $times['end'],
            'room' => $data['room'] ?? $studyGroup->room,
            'is_active' => true,
        ]);

        $redirectRoute = $request->boolean('matrix')
            ? route('user.jadwal-kbm.edit-all', ['userId' => $userId])
            : route('user.jadwal-kbm.edit', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]);

        $response = redirect($redirectRoute)->with('success', 'Entri jadwal berhasil ditambahkan.');

        if ($warnings !== []) {
            $response->with('warning', implode(' ', $warnings).' Data tetap disimpan dan ditandai pada matriks.');
        }

        return $response;
    }

    public function destroyEntry(Request $request, string $userId, string $studyGroupId, string $entryId)
    {
        $this->authorizeUpdate($request);

        $schoolId = $this->resolveSchoolId($request);
        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $entry = JadwalKbm::where('study_group_id', $studyGroup->id)
            ->where('id', $entryId)
            ->firstOrFail();

        $entry->delete();

        $redirectRoute = $request->boolean('matrix')
            ? route('user.jadwal-kbm.edit-all', ['userId' => $userId])
            : route('user.jadwal-kbm.edit', ['userId' => $userId, 'studyGroupId' => $studyGroup->id]);

        return redirect($redirectRoute)
            ->with('success', 'Entri jadwal berhasil dihapus.');
    }

    public function updateEntry(
        Request $request,
        string $userId,
        string $entryId,
        JadwalGeneratorService $generator,
        TeacherAvailabilityService $availability,
    ) {
        $this->authorizeUpdate($request);

        $schoolId = $this->resolveSchoolId($request);
        $entry = JadwalKbm::with(['studyGroup', 'subject', 'teacher'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($entryId);

        $data = $request->validate([
            'day_of_week' => 'required|integer|between:1,6',
            'slot_index' => 'required|integer|between:1,'.JadwalGeneratorService::MAX_PERIODS_PER_DAY,
            'subject_id' => 'required|uuid|exists:subjects,id',
            'teacher_id' => 'nullable|uuid|exists:users,id',
            'room' => 'nullable|string|max:50',
        ]);

        $day = (int) $data['day_of_week'];
        $slot = (int) $data['slot_index'];
        $teacherId = $data['teacher_id'] ?? null;
        $studyGroup = $entry->studyGroup;

        abort_unless($studyGroup, 404, 'Rombel jadwal tidak ditemukan.');

        if (! $generator->isTeachingSlot($entry->school_id, $day, $slot)) {
            return back()->with('warning', 'Slot tersebut adalah jam istirahat atau di luar master jam pelajaran.');
        }

        $warnings = [];

        if ($teacherId && ! $availability->isAllowed((string) $teacherId, $entry->school_id, $day, $slot)) {
            $warnings[] = 'Guru tidak tersedia pada hari/jam tersebut.';
        }

        $teacherConflict = $teacherId
            ? JadwalKbm::where('teacher_id', $teacherId)
                ->where('day_of_week', $day)
                ->where('slot_index', $slot)
                ->where('academic_year_id', $entry->academic_year_id)
                ->where('is_active', true)
                ->where('id', '!=', $entry->id)
                ->exists()
            : false;

        $groupConflict = JadwalKbm::where('study_group_id', $studyGroup->id)
            ->where('day_of_week', $day)
            ->where('slot_index', $slot)
            ->where('academic_year_id', $entry->academic_year_id)
            ->where('is_active', true)
            ->where('id', '!=', $entry->id)
            ->exists();

        if ($teacherConflict) {
            $warnings[] = 'Guru sudah mengajar pada hari dan jam tersebut.';
        }

        if ($groupConflict) {
            $warnings[] = 'Rombel sudah memiliki pelajaran pada hari dan jam tersebut.';
        }

        $times = $generator->resolveSlotTimesPublic($slot, $day, $entry->school_id);

        $entry->update([
            'day_of_week' => $day,
            'slot_index' => $slot,
            'start_time' => $times['start'],
            'end_time' => $times['end'],
            'teacher_id' => $teacherId,
            'subject_id' => $data['subject_id'],
            'room' => $data['room'] ?? $entry->room,
        ]);

        $response = redirect()
            ->route($request->boolean('matrix') ? 'user.jadwal-kbm.edit-all' : 'user.jadwal-kbm.edit', $request->boolean('matrix')
                ? ['userId' => $userId]
                : ['userId' => $userId, 'studyGroupId' => $studyGroup->id])
            ->with('success', 'Entri jadwal berhasil diperbarui.');

        if ($warnings !== []) {
            $response->with('warning', implode(' ', $warnings).' Data tetap disimpan dan ditandai pada matriks.');
        }

        return $response;
    }

    public function update(JadwalKbmUpdateRequest $request, string $userId, string $studyGroupId)
    {
        $data = $request->validated();
        $schoolId = $this->resolveSchoolId($request);

        $studyGroup = StudyGroup::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $generator = app(JadwalGeneratorService::class);
        $conflicts = [];

        DB::transaction(function () use ($data, $studyGroup, $generator, &$conflicts) {
            foreach ($data['entries'] as $entry) {
                $jadwal = JadwalKbm::where('study_group_id', $studyGroup->id)
                    ->where('id', $entry['id'])
                    ->firstOrFail();

                $day = (int) $entry['day_of_week'];
                $slot = (int) $entry['slot_index'];

                // Validasi slot terhadap master slot sekolah (termasuk is_break).
                if (! $generator->isTeachingSlot($studyGroup->school_id, $day, $slot)) {
                    $conflicts[] = [
                        'jadwal_id' => $jadwal->id,
                        'reason' => 'Slot tidak tersedia (jam istirahat atau di luar master slot sekolah)',
                    ];

                    continue;
                }

                if ($entry['teacher_id']
                    && ! app(TeacherAvailabilityService::class)->isAllowed(
                        (string) $entry['teacher_id'],
                        $studyGroup->school_id,
                        $day,
                        $slot,
                    )) {
                    $conflicts[] = [
                        'jadwal_id' => $jadwal->id,
                        'reason' => 'Guru tidak tersedia pada hari/jam tersebut menurut pengaturan ketersediaan',
                    ];
                }

                $teacherConflict = $entry['teacher_id']
                    ? JadwalKbm::where('teacher_id', $entry['teacher_id'])
                        ->where('day_of_week', $day)
                        ->where('slot_index', $slot)
                        ->where('academic_year_id', $jadwal->academic_year_id)
                        ->where('is_active', true)
                        ->where('id', '!=', $jadwal->id)
                        ->exists()
                    : false;

                $sgConflict = JadwalKbm::where('study_group_id', $studyGroup->id)
                    ->where('day_of_week', $day)
                    ->where('slot_index', $slot)
                    ->where('academic_year_id', $jadwal->academic_year_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $jadwal->id)
                    ->exists();

                if ($teacherConflict) {
                    $conflicts[] = [
                        'jadwal_id' => $jadwal->id,
                        'reason' => 'Guru sudah mengajar di slot ini',
                    ];
                }

                if ($sgConflict) {
                    $conflicts[] = [
                        'jadwal_id' => $jadwal->id,
                        'reason' => 'Rombel sudah ada pelajaran di slot ini',
                    ];
                }

                $times = $generator->resolveSlotTimesPublic($slot, $day, $studyGroup->school_id);

                $jadwal->update([
                    'day_of_week' => $day,
                    'slot_index' => $slot,
                    'start_time' => $times['start'],
                    'end_time' => $times['end'],
                    'teacher_id' => $entry['teacher_id'] ?? null,
                    'subject_id' => $entry['subject_id'],
                    'room' => $entry['room'] ?? $jadwal->room,
                ]);
            }
        });

        $response = redirect()
            ->route('user.jadwal-kbm.show', ['userId' => $userId, 'studyGroupId' => $studyGroupId])
            ->with('success', 'Jadwal berhasil diperbarui');

        if (! empty($conflicts)) {
            $response
                ->with('conflict_warnings', $conflicts)
                ->with('warning', count($conflicts).' entri disimpan dengan konflik jadwal. Data tetap tersimpan dan ditandai pada matriks.');
        }

        return $response;
    }

    public function cetak(Request $request, string $userId, string $studyGroupId)
    {
        $this->authorizeView($request);

        $schoolId = $this->resolveSchoolId($request);
        $activeAy = AcademicYear::where('is_active', true)->first();

        $studyGroup = StudyGroup::with('gradeLevel', 'homeroomTeacher')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($studyGroupId);

        $jadwals = JadwalKbm::with(['teacher', 'subject'])
            ->where('study_group_id', $studyGroupId)
            ->where('academic_year_id', $activeAy?->id)
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get()
            ->groupBy('day_of_week');

        $tahfidzSchedules = TahfidzSchedule::with(['group:id,name,study_group_id', 'group.teacher:id,name', 'group.coordinator:id,name'])
            ->where('is_active', true)
            ->where('academic_year_id', $activeAy?->id)
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', today()))
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', today()))
            ->whereHas('group', fn ($q) => $q->where('study_group_id', $studyGroupId)->where('is_active', true))
            ->orderBy('day_of_week')->orderBy('time_start')->get()->groupBy('day_of_week');

        $days = $this->days();

        return app(DocumentPdfService::class)->fromView(
            'jadwal-kbm.cetak',
            compact('studyGroup', 'jadwals', 'days', 'activeAy', 'tahfidzSchedules'),
            'Jadwal-KBM-'.($studyGroup->full_name ?? $studyGroup->name),
            'landscape',
        );
    }

    public function forTeacher(Request $request, string $userId, string $teacherId)
    {
        $this->authorizeView($request);

        $schoolId = $this->resolveSchoolId($request);
        $activeAy = AcademicYear::where('is_active', true)->first();

        $authUser = auth()->user();
        if ($authUser && $authUser->id !== $teacherId && ! canPermission('jadwalkbm.publish') && ! canPermission('jadwal-kbm-all-access')) {
            abort(403, 'Anda hanya dapat melihat jadwal mengajar sendiri.');
        }

        $teacher = User::findOrFail($teacherId);

        $jadwals = JadwalKbm::with(['studyGroup.gradeLevel', 'subject'])
            ->where('teacher_id', $teacherId)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($activeAy, fn ($q) => $q->where('academic_year_id', $activeAy->id))
            ->orderBy('day_of_week')
            ->orderBy('slot_index')
            ->get()
            ->groupBy('day_of_week');

        $tahfidzSchedules = TahfidzSchedule::with(['group:id,name,study_group_id,teacher_id,coordinator_id', 'group.studyGroup:id,name'])
            ->where('is_active', true)
            ->where('academic_year_id', $activeAy?->id)
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', today()))
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', today()))
            ->whereHas('group', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)->orWhere('coordinator_id', $teacherId)
                    ->orWhereHas('teachers', fn ($t) => $t->where('user_id', $teacherId)->where('is_active', true));
            })
            ->orderBy('day_of_week')->orderBy('time_start')->get()->groupBy('day_of_week');

        $days = $this->days();

        // Status kehadiran guru pada jadwal hari ini.
        $today = today();
        $todayDay = (int) $today->format('w') === 0 ? 7 : (int) $today->format('w');
        $todayJadwals = $jadwals->get($todayDay, collect());
        $attendanceToday = $todayJadwals->isEmpty()
            ? collect()
            : TeacherClassAttendance::where('teacher_id', $teacherId)
                ->whereDate('attendance_date', $today)
                ->whereIn('jadwal_kbm_id', $todayJadwals->pluck('id'))
                ->get()
                ->keyBy('jadwal_kbm_id');

        $todayCheckedIn = $attendanceToday->filter(fn ($att) => $att->actual_time_in !== null)->count();
        $todayCheckedOut = $attendanceToday->filter(fn ($att) => $att->actual_time_out !== null)->count();
        $todayLate = $attendanceToday->filter(fn ($att) => $att->status_masuk === 'terlambat')->count();

        $attendanceSummary = [
            'total' => $todayJadwals->count(),
            'checked_in' => $todayCheckedIn,
            'checked_out' => $todayCheckedOut,
            'late' => $todayLate,
            'pending' => max(0, $todayJadwals->count() - $todayCheckedIn),
        ];

        return view('jadwal-kbm.teacher', compact(
            'teacher', 'jadwals', 'days', 'activeAy', 'tahfidzSchedules',
            'todayDay', 'attendanceToday', 'attendanceSummary'
        ));
    }

    /**
     * Susun laporan hasil generate (termasuk kekurangan JP dan sisa konflik).
     *
     * @param  Collection<int, array<string, mixed>>  $results
     * @return array<string, mixed>
     */
    private function buildReport(Collection $results): array
    {
        $groups = [];
        $totalGenerated = 0;
        $totalRequested = 0;
        $totalMissing = 0;
        $allConflicts = [];

        foreach ($results as $result) {
            $missing = (int) collect($result['shortages'] ?? [])->sum('missing');

            $groups[] = [
                'name' => $result['study_group_name'] ?? $result['study_group_id'] ?? '-',
                'generated' => (int) ($result['generated'] ?? 0),
                'requested' => (int) ($result['requested'] ?? 0),
                'missing' => $missing,
                'shortages' => $result['shortages'] ?? [],
                'conflicts' => $result['conflicts'] ?? [],
                'has_assignments' => (bool) ($result['has_assignments'] ?? true),
            ];

            $totalGenerated += (int) ($result['generated'] ?? 0);
            $totalRequested += (int) ($result['requested'] ?? 0);
            $totalMissing += $missing;

            foreach ($result['conflicts'] ?? [] as $conflict) {
                $allConflicts[] = ($result['study_group_name'] ?? '-').": {$conflict}";
            }
        }

        return [
            'groups' => $groups,
            'total_generated' => $totalGenerated,
            'total_requested' => $totalRequested,
            'total_missing' => $totalMissing,
            'conflicts' => $allConflicts,
        ];
    }

    /**
     * Susun data matriks jadwal: slot per hari, rombel, dan beban guru.
     *
     * @param  Collection<int, StudyGroup>  $studyGroups
     * @param  Collection<string, Collection<int, JadwalKbm>>  $jadwalGroups
     * @return array<string, mixed>
     */
    private function buildMatrixData(Collection $studyGroups, Collection $jadwalGroups, ?string $schoolId): array
    {
        $days = $this->days();
        $masterDays = app(JadwalGeneratorService::class)->masterSlotsByDay($schoolId);
        $groupIds = $studyGroups->pluck('id')->map(fn ($id) => (string) $id)->all();
        $flat = collect($jadwalGroups)
            ->flatten(1)
            ->filter(fn ($row) => $row instanceof JadwalKbm && in_array((string) $row->study_group_id, $groupIds, true));

        $indexed = $flat->keyBy(fn (JadwalKbm $row) => ((int) $row->day_of_week).'|'.((int) $row->slot_index).'|'.$row->study_group_id);
        $matrix = [];

        foreach ($days as $day => $dayName) {
            foreach (($masterDays[$day] ?? []) as $slot) {
                foreach ($studyGroups as $studyGroup) {
                    $entry = $indexed->get($day.'|'.$slot['slot'].'|'.$studyGroup->id);

                    if ($entry) {
                        $matrix[$day][$slot['slot']][$studyGroup->id] = $entry;
                    }
                }
            }
        }

        $teacherLoads = $flat
            ->filter(fn (JadwalKbm $row) => $row->teacher_id && $row->teacher)
            ->groupBy('teacher_id')
            ->map(function (Collection $rows) {
                $teacher = $rows->first()->teacher;
                $subjects = $rows->groupBy('subject_id')->map(function (Collection $subjectRows) {
                    return [
                        'name' => $subjectRows->first()->subject?->name ?? '-',
                        'classes' => $subjectRows->pluck('studyGroup')->filter()->pluck('name')->unique()->values()->all(),
                        'hours' => $subjectRows->count(),
                    ];
                })->values();

                return [
                    'teacher' => $teacher,
                    'subjects' => $subjects,
                    'total_hours' => $rows->count(),
                ];
            })
            ->sortBy(fn (array $row) => $row['teacher']?->name)
            ->values()
            ->map(function (array $row, int $index) {
                $row['no'] = $index + 1;
                $row['code'] = $this->teacherCode($index);

                return $row;
            });

        $teacherCodes = $teacherLoads->mapWithKeys(fn (array $row) => [
            (string) $row['teacher']->id => $row['code'],
        ]);

        $subjectCodes = $flat
            ->filter(fn (JadwalKbm $row) => $row->subject)
            ->groupBy('subject_id')
            ->map(function (Collection $rows) {
                $subject = $rows->first()->subject;

                return [
                    'code' => $subject?->code ?: '-',
                    'name' => $subject?->name ?? '-',
                ];
            })
            ->sortBy('code')
            ->values();

        $teacherSlotGroups = $flat
            ->filter(fn (JadwalKbm $row) => $row->teacher_id)
            ->groupBy(fn (JadwalKbm $row) => $row->teacher_id.'|'.$row->day_of_week.'|'.$row->slot_index)
            ->filter(fn (Collection $rows) => $rows->count() > 1);

        $groupSlotGroups = $flat
            ->groupBy(fn (JadwalKbm $row) => $row->study_group_id.'|'.$row->day_of_week.'|'.$row->slot_index)
            ->filter(fn (Collection $rows) => $rows->count() > 1);

        $conflictIds = $teacherSlotGroups
            ->flatMap(fn (Collection $rows) => $rows->pluck('id'))
            ->merge($groupSlotGroups->flatMap(fn (Collection $rows) => $rows->pluck('id')))
            ->unique()
            ->flip();

        $availability = app(TeacherAvailabilityService::class);
        $unavailableIds = $flat
            ->filter(fn (JadwalKbm $row) => $row->teacher_id && ! $availability->isAllowed(
                (string) $row->teacher_id,
                $schoolId,
                (int) $row->day_of_week,
                (int) $row->slot_index,
            ))
            ->pluck('id')
            ->flip();

        return compact(
            'days', 'masterDays', 'matrix', 'teacherLoads', 'teacherCodes', 'subjectCodes',
            'conflictIds', 'unavailableIds',
        );
    }

    private function teacherCode(int $index): string
    {
        $index++;
        $code = '';

        while ($index > 0) {
            $index--;
            $code = chr(65 + ($index % 26)).$code;
            $index = intdiv($index, 26);
        }

        return $code;
    }

    /**
     * School context dari middleware, dengan fallback ke sekolah user.
     * Mencegah halaman jadwal memakai grid fallback ketika atribut context
     * tidak terpasang pada request.
     */
    private function resolveSchoolId(Request $request): ?string
    {
        $schoolId = $request->attributes->get('schoolContextId');

        if ($schoolId) {
            return (string) $schoolId;
        }

        if (! $request->attributes->get('isGlobalView') && $request->user()) {
            return SchoolGroupService::getUserSchool($request->user())?->id;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function days(): array
    {
        return [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (
            canPermission('jadwalkbm.read')
            || canPermission('jadwal_kbm_view')
            || canPermission('jadwal_kbm_manage')
            || canPermission('jadwal-kbm-all-access')
        ), 403, 'Anda tidak memiliki akses ke jadwal pelajaran.');
    }

    private function authorizeGenerate(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (
            canPermission('jadwalkbm.write')
            || canPermission('jadwalkbm.publish')
            || canPermission('jadwal_kbm_generate')
            || canPermission('jadwal_kbm_manage')
            || canPermission('jadwal-kbm-generate-all-access')
        ), 403, 'Anda tidak memiliki akses untuk generate jadwal.');
    }

    private function authorizeUpdate(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (
            canPermission('jadwalkbm.write')
            || canPermission('jadwal_kbm_update')
            || canPermission('jadwal_kbm_manage')
            || canPermission('jadwal-kbm-update-all-access')
        ), 403, 'Anda tidak memiliki akses untuk mengubah jadwal.');
    }
}
