<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GtkProfile;
use App\Models\Supervisi;
use App\Models\SupervisiInstrumen;
use App\Models\SupervisiPengaturan;
use App\Models\SupervisiSk;
use App\Services\Sarpras\SupervisiWorkflowService;
use App\Models\SupervisiPenilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupervisiController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $query = Supervisi::with([
            'gtk:id,user_id',
            'gtk.user:id,name',
            'gtk.latestEmployment:user_id,nupy',
            'observer:id,user_id',
            'observer.user:id,name',
            'observer.latestEmployment:user_id,nupy',
            'academicYear:id,name',
        ]);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        if ($request->filled('supervisor_id')) {
            $query->where('observer_id', $request->supervisor_id);
        }
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }
        if ($request->filled('jenis_supervisi')) {
            $query->where('jenis_supervisi', $request->jenis_supervisi);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('gtk_name', 'LIKE', "%{$search}%")
                    ->orWhere('observer_name', 'LIKE', "%{$search}%")
                    ->orWhere('mata_pelajaran', 'LIKE', "%{$search}%");
            });
        }

        $supervisiList = $query->orderBy('tanggal_supervisi', 'desc')->paginate(15)->withQueryString();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        $baseQuery = Supervisi::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $statistics = [
            'total' => (clone $baseQuery)->count(),
            'terjadwal' => (clone $baseQuery)->where('status', Supervisi::STATUS_TERJADWAL)->count(),
            'selesai' => (clone $baseQuery)->where('status', Supervisi::STATUS_SELESAI)->count(),
            'avg_nilai' => (float) ((clone $baseQuery)->whereNotNull('nilai_akhir')->avg('nilai_akhir') ?? 0),
        ];

        $supervisorIds = (clone $baseQuery)->whereNotNull('observer_id')->distinct()->pluck('observer_id');
        $supervisors = GtkProfile::query()
            ->with('user:id,name')
            ->whereIn('id', $supervisorIds)
            ->get()
            ->sortBy(fn ($gtk) => mb_strtolower((string) $gtk->user?->name))
            ->values();

        $workflowYearId = $request->input('academic_year_id', AcademicYear::where('is_active', true)->value('id'));
        $workflowSemester = (int) $request->input('semester', 1);
        $workflow = $workflowYearId
            ? app(SupervisiWorkflowService::class)->status($schoolId, $workflowYearId, $workflowSemester)
            : null;

        $activeSkForIndex = SupervisiSk::with(['anggota.gtk.user'])
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $workflowYearId)
            ->where('semester', $workflowSemester)
            ->first();
        $supervisorGtks = $activeSkForIndex
            ? $activeSkForIndex->anggota->pluck('gtk')->filter()->unique('id')->values()
            : collect();
        $supervisorIdsInSk = $supervisorGtks->pluck('id')->all();

        $pengaturanSupervisor = SupervisiPengaturan::with([
            'academicYear:id,name',
            'supervisor.user:id,name',
            'supervisee.user:id,name',
        ])
            ->where('school_id', $schoolId)
            ->when($workflowYearId, fn ($q) => $q->where('academic_year_id', $workflowYearId))
            ->where('semester', $workflowSemester)
            ->when($supervisorIdsInSk === [], fn ($q) => $q->whereRaw('1 = 0'))
            ->when($supervisorIdsInSk !== [], fn ($q) => $q->whereIn('supervisor_gtk_id', $supervisorIdsInSk))
            ->orderByDesc('is_active')
            ->orderBy('supervisor_gtk_id')
            ->get();

        $gtks = $this->gtkOptionsForSchool($schoolId);

        return view('waka.supervisi.index', compact(
            'supervisiList', 'academicYears', 'statistics', 'supervisors', 'workflow',
            'workflowYearId', 'workflowSemester', 'pengaturanSupervisor', 'gtks', 'supervisorGtks'
        ));
    }

    public function create(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $formOptions = $this->formOptions($schoolId, $activeYearId, (int) $request->input('semester', 1));
        $activeSk = SupervisiSk::where('school_id', $schoolId)
            ->where('academic_year_id', $activeYearId)
            ->where('semester', (int) $request->input('semester', 1))
            ->first();

        $observerId = $request->input('observer_id');
        $gtkId = $request->input('gtk_id');
        if ($observerId && $gtkId) {
            $formOptions['selectedPair'] = collect($formOptions['assignmentOptions'])
                ->first(fn ($pair) => $pair['observer_id'] === $observerId && $pair['gtk_id'] === $gtkId);
        }

        return view('waka.supervisi.create', array_merge(
            compact('academicYears', 'activeYearId', 'activeSk'),
            $formOptions
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gtk_id' => 'required|uuid|exists:gtk_profiles,id',
            'observer_id' => 'required|uuid|exists:gtk_profiles,id|different:gtk_id',
            'gtk_name' => 'nullable|string|max:255',
            'observer_name' => 'nullable|string|max:255',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:1,2',
            'mata_pelajaran' => 'nullable|string|max:255',
            'tanggal_supervisi' => 'required|date',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i|after:jam_mulai',
            'jenis_supervisi' => 'required|in:perangkat_pembelajaran,proses_pembelajaran,penilaian,lainnya',
            'tujuan' => 'nullable|string',
            'status' => 'required|in:terjadwal,berlangsung,selesai,dibatalkan',
            'instrumen_id' => 'required|uuid|exists:supervisi_instrumen,id',
            'jadwal_kbm_id' => 'required|uuid|exists:jadwal_kbms,id',
            'redirect_to' => 'nullable|in:penilaian',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');
        if (! $schoolId) {
            return back()->withInput()->with('error', 'Konteks satuan pendidikan tidak ditemukan.');
        }
        $validated['school_id'] = $schoolId;
        $validated['supervisi_sk_id'] = $this->resolveSk($validated);
        $validated = array_merge($validated, $this->resolveTeacherSchedule($validated, $schoolId));

        $this->validateSupervisorPair($validated);
        $this->assertNoScheduleConflict($validated);

        // Kolom denormalisasi `gtk_name`/`observer_name` diisi dari relasi bila kosong,
        // karena gtk_profiles tidak menyimpan nama (nama ada di tabel users).
        if (empty($validated['gtk_name']) && ! empty($validated['gtk_id'])) {
            $validated['gtk_name'] = GtkProfile::with('user')->find($validated['gtk_id'])?->user?->name;
        }
        if (empty($validated['observer_name']) && ! empty($validated['observer_id'])) {
            $validated['observer_name'] = GtkProfile::with('user')->find($validated['observer_id'])?->user?->name;
        }

        $redirectTo = $validated['redirect_to'] ?? null;
        unset($validated['redirect_to']);

        $supervisi = Supervisi::create($validated);

        if ($redirectTo === 'penilaian') {
            return redirect()
                ->route('waka.supervisi.penilaian', $supervisi->id)
                ->with('success', 'Jadwal supervisi dibuat. Pilih instrumen lalu isi penilaian.');
        }

        return redirect()->route('waka.supervisi.index')
            ->with('success', 'Data supervisi berhasil ditambahkan.');
    }

    /**
     * Daftar GTK pada satuan pendidikan (gtk_profiles tidak punya school_id —
     * filter lewat gtk_employments.school_id), urut nama user.
     */
    /**
     * Form penilaian supervisi: item instrumen (dengan poin) → skor A–D + Ya/Tidak,
     * jumlah per bagian, jumlah keseluruhan, dan ketercapaian.
     */
    /**
     * Jadwal mengajar guru pada tanggal tertentu, dipakai form supervisi.
     */
    public function teacherSchedule(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $validated = $request->validate([
            'gtk_profile_id' => 'required|uuid|exists:gtk_profiles,id',
            'tanggal' => 'required|date',
            'academic_year_id' => 'nullable|uuid|exists:academic_years,id',
        ]);

        $profile = GtkProfile::findOrFail($validated['gtk_profile_id']);
        $this->ensureGtkBelongsToSchool((string) $schoolId, $profile->id);

        $date = \Carbon\Carbon::parse($validated['tanggal']);
        $yearId = $validated['academic_year_id'] ?? AcademicYear::where('is_active', true)->value('id');

        $schedules = \App\Models\JadwalKbm::query()
            ->with(['studyGroup:id,name', 'subject:id,name'])
            ->where('school_id', $schoolId)
            ->where('teacher_id', $profile->user_id)
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->where('day_of_week', $date->dayOfWeekIso)
            ->where('is_active', true)
            ->orderBy('slot_index')
            ->orderBy('start_time')
            ->get()
            ->map(fn (\App\Models\JadwalKbm $schedule) => [
                'id' => $schedule->id,
                'slot_index' => $schedule->slot_index,
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'subject' => $schedule->subject?->name ?? '-',
                'study_group' => $schedule->studyGroup?->name ?? '-',
                'room' => $schedule->room,
                'label' => 'Jam '.($schedule->slot_index ?: '-').' · '.substr((string) $schedule->start_time, 0, 5).'–'.substr((string) $schedule->end_time, 0, 5).' · '.($schedule->subject?->name ?? '-').' · '.($schedule->studyGroup?->name ?? '-'),
            ]);

        return response()->json([
            'date' => $date->toDateString(),
            'day' => $date->locale('id')->translatedFormat('l'),
            'schedules' => $schedules,
        ]);
    }

    /**
     * Cetak jadwal supervisi per semester.
     */
    public function schedulePrint(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $yearId = $request->input('academic_year_id', AcademicYear::where('is_active', true)->value('id'));
        $semester = $request->input('semester');

        $rows = Supervisi::with(['gtk.user:id,name', 'observer.user:id,name', 'studyGroup:id,name', 'subject:id,name'])
            ->where('school_id', $schoolId)
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->when($semester, fn ($q) => $q->where('semester', $semester))
            ->orderBy('tanggal_supervisi')
            ->orderBy('slot_index')
            ->get();

        $school = \App\Models\School::find($schoolId);
        $academicYear = $yearId ? AcademicYear::find($yearId) : null;

        return app(\App\Services\Pdf\DocumentPdfService::class)->fromView(
            'waka.supervisi.jadwal-cetak',
            compact('rows', 'school', 'academicYear', 'semester'),
            'Jadwal-Supervisi-'.($academicYear?->name ?? 'Semua-Tahun').'-Semester-'.($semester ?: 'Semua'),
            'landscape',
        );
    }

    public function penilaian(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::with(['gtk.user', 'observer.user', 'academicYear', 'instrumen.bagian.items.subItems'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        // Instrumen mengikuti jadwal. Query hanya dipakai untuk data lama
        // yang belum memiliki instrumen.
        $instrumen = $supervisi->instrumen_id
            ? $supervisi->instrumen
            : ($request->filled('instrumen_id')
                ? SupervisiInstrumen::with(['bagian.items.subItems'])->find($request->input('instrumen_id'))
                : null);

        $instrumenOptions = SupervisiInstrumen::query()
            ->where('is_active', true)
            ->where(function ($q) use ($schoolId) {
                $q->whereNull('school_id');
                if ($schoolId) {
                    $q->orWhere('school_id', $schoolId);
                }
            })
            ->orderByRaw('school_id IS NULL')
            ->orderBy('nama')
            ->get();

        $penilaian = $supervisi->penilaian()->get()->keyBy('item_id');

        return view('waka.supervisi.penilaian', compact('supervisi', 'instrumen', 'instrumenOptions', 'penilaian'));
    }

    public function penilaianStore(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        $validated = $request->validate([
            'instrumen_id' => 'nullable|uuid|exists:supervisi_instrumen,id',
            'catatan_temuan' => 'nullable|string',
            'rekomendasi' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',
            'skor' => 'nullable|array',
            'skor.*' => 'nullable|integer|min:0|max:4',
            'kondisi' => 'nullable|array',
            'kondisi.*' => 'nullable|in:ya,tidak',
            'item_catatan' => 'nullable|array',
            'item_catatan.*' => 'nullable|string|max:500',
        ]);

        if ($supervisi->instrumen_id && ! empty($validated['instrumen_id'])
            && (string) $supervisi->instrumen_id !== (string) $validated['instrumen_id']) {
            throw ValidationException::withMessages([
                'instrumen_id' => 'Instrumen tidak boleh diubah setelah jadwal dibuat.',
            ]);
        }

        $instrumenId = $supervisi->instrumen_id ?: ($validated['instrumen_id'] ?? null);
        if (! $instrumenId) {
            throw ValidationException::withMessages([
                'instrumen_id' => 'Pilih instrumen penilaian terlebih dahulu.',
            ]);
        }

        $instrumen = SupervisiInstrumen::with(['bagian.items.subItems'])->findOrFail($instrumenId);

        $items = $instrumen->bagian
            ->flatMap(fn ($bagian) => $bagian->items)
            ->flatMap(fn ($item) => collect([$item])->merge($item->subItems));

        $skorInput = $validated['skor'] ?? [];
        $kondisiInput = $validated['kondisi'] ?? [];
        $catatanInput = $validated['item_catatan'] ?? [];

        $total = 0.0;
        $maks = 0.0;

        DB::transaction(function () use ($supervisi, $instrumen, $items, $skorInput, $kondisiInput, $catatanInput, &$total, &$maks) {
            foreach ($items as $item) {
                $skor = $skorInput[$item->id] ?? null;
                $kondisi = $kondisiInput[$item->id] ?? null;
                $catatan = $catatanInput[$item->id] ?? null;

                SupervisiPenilaian::updateOrCreate(
                    ['supervisi_id' => $supervisi->id, 'item_id' => $item->id],
                    [
                        'skor' => ($skor === '' || $skor === null) ? null : (int) $skor,
                        'kondisi' => $kondisi === null ? null : $kondisi === 'ya',
                        'catatan' => $catatan ?: null,
                    ],
                );

                if ($skor !== null && $skor !== '') {
                    $total += (int) $skor;
                    $maks += (float) $item->bobot;
                }
            }

            $nilai = $maks > 0 ? round($total / $maks * 100, 2) : null;

            $supervisi->update([
                'instrumen_id' => $instrumen->id,
                'skor_total' => $total,
                'skor_maks' => $maks,
                'nilai_akhir' => $nilai,
                'ketercapaian' => $this->ketercapaianFor($nilai),
                'catatan_temuan' => $validated['catatan_temuan'] ?? null,
                'rekomendasi' => $validated['rekomendasi'] ?? null,
                'tindak_lanjut' => $validated['tindak_lanjut'] ?? null,
            ]);
        });

        $fresh = $supervisi->fresh();

        return redirect()
            ->route('waka.supervisi.show', $supervisi->id)
            ->with('success', 'Penilaian supervisi tersimpan. Nilai: '.number_format((float) $fresh->nilai_akhir, 2).' ('.($fresh->ketercapaian ?? '-').').');
    }

    /**
     * Cetak instrumen supervisi terisi (format lembar).
     */
    public function cetak(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::with(['gtk.user', 'observer.user', 'academicYear', 'instrumen.bagian.items.subItems', 'school'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $penilaian = $supervisi->penilaian()->get()->keyBy('item_id');
        $school = $supervisi->school;

        return app(\App\Services\Pdf\DocumentPdfService::class)->fromView(
            'waka.supervisi.cetak',
            compact('supervisi', 'penilaian', 'school'),
            'Instrumen-Supervisi-'.($supervisi->gtk?->user?->name ?? $supervisi->gtk_name ?? 'Guru'),
        );
    }

    /**
     * Ketercapaian: 86–100 Amat baik · 70–85 Baik · 55–69 Cukup · <55 Kurang.
     */
    private function ketercapaianFor(?float $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        return match (true) {
            $nilai >= 86 => 'Amat baik',
            $nilai >= 70 => 'Baik',
            $nilai >= 55 => 'Cukup',
            default => 'Kurang',
        };
    }

    /**
     * Opsi form: supervisor dan guru dibatasi oleh pengaturan supervisor.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(?string $schoolId, ?string $yearId = null, ?int $semester = 1): array
    {
        $yearId = $yearId ?: AcademicYear::where('is_active', true)->value('id');

        $sk = SupervisiSk::with('anggota')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $yearId)
            ->where('semester', $semester)
            ->first();
        $skMemberIds = $sk?->anggota->pluck('gtk_profile_id')->all() ?? [];

        $settings = SupervisiPengaturan::query()
            ->with([
                'supervisor.user:id,name',
                'supervisor.latestEmployment:id,user_id,nupy,jabatan',
                'supervisee.user:id,name',
                'supervisee.latestEmployment:id,user_id,nupy,jabatan',
            ])
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->where('semester', $semester)
            ->when($skMemberIds === [], fn ($q) => $q->whereRaw('1 = 0'))
            ->when($skMemberIds !== [], fn ($q) => $q->whereIn('supervisor_gtk_id', $skMemberIds))
            ->get();

        $assignmentOptions = $settings
            ->map(function (SupervisiPengaturan $setting) {
                return [
                    'value' => $setting->supervisor_gtk_id.'|'.$setting->supervisee_gtk_id,
                    'observer_id' => $setting->supervisor_gtk_id,
                    'gtk_id' => $setting->supervisee_gtk_id,
                    'supervisor_name' => $setting->supervisor?->name ?? '-',
                    'supervisee_name' => $setting->supervisee?->name ?? '-',
                    'label' => ($setting->supervisor?->name ?? '-').' → '.($setting->supervisee?->name ?? '-'),
                ];
            })
            ->unique('value')
            ->values()
            ->all();

        $instrumenOptions = SupervisiInstrumen::query()
            ->where('is_active', true)
            ->where(function ($q) use ($schoolId) {
                $q->whereNull('school_id');
                if ($schoolId) {
                    $q->orWhere('school_id', $schoolId);
                }
            })
            ->orderByRaw('school_id IS NULL')
            ->orderBy('nama')
            ->get();

        return [
            'assignmentOptions' => $assignmentOptions,
            'hasSettings' => $assignmentOptions !== [],
            'selectedPair' => null,
            'selectedScheduleId' => null,
            'instrumenOptions' => $instrumenOptions,
        ];
    }

    /**
     * Pastikan pasangan supervisor → guru memang diatur untuk tahun ajaran ini.
     */
    protected function validateSupervisorPair(array $data): void
    {
        $this->ensureGtkBelongsToSchool($data['school_id'], $data['observer_id']);
        $this->ensureGtkBelongsToSchool($data['school_id'], $data['gtk_id']);
        $this->ensureInstrumenAllowed($data['school_id'], $data['instrumen_id'] ?? null);

        $settingsQuery = SupervisiPengaturan::query()
            ->where('school_id', $data['school_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('is_active', true);

        if (! (clone $settingsQuery)->exists()) {
            throw ValidationException::withMessages([
                'observer_id' => 'Belum ada pengaturan supervisor untuk tahun ajaran ini. Atur pasangan supervisor dan guru terlebih dahulu.',
            ]);
        }

        $pairExists = (clone $settingsQuery)
            ->where('supervisor_gtk_id', $data['observer_id'])
            ->where('supervisee_gtk_id', $data['gtk_id'])
            ->exists();

        if (! $pairExists) {
            throw ValidationException::withMessages([
                'gtk_id' => 'Pasangan supervisor dan guru ini belum diatur. Buka Pengaturan Supervisor terlebih dahulu.',
            ]);
        }
    }

    /**
     * Cegah supervisor/guru memiliki dua jadwal pada rentang waktu yang sama.
     */
    protected function assertNoScheduleConflict(array $data, ?string $ignoreId = null): void
    {
        $start = $data['jam_mulai'] ?? null;
        $end = $data['jam_selesai'] ?? null;

        if (! $start || ! $end) {
            return;
        }

        $conflict = Supervisi::query()
            ->where('school_id', $data['school_id'])
            ->whereDate('tanggal_supervisi', $data['tanggal_supervisi'])
            ->where('status', '!=', Supervisi::STATUS_DIBATALKAN)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($data) {
                $q->where('observer_id', $data['observer_id'])
                    ->orWhere('gtk_id', $data['gtk_id']);
            })
            ->where(function ($q) use ($start, $end) {
                $q->where('jam_mulai', '<', $end)
                    ->where('jam_selesai', '>', $start);
            })
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'tanggal_supervisi' => 'Jadwal bentrok: supervisor atau guru yang dipilih sudah memiliki supervisi pada jam tersebut.',
            ]);
        }
    }

    protected function resolveSk(array $data): string
    {
        $sk = SupervisiSk::query()
            ->where('school_id', $data['school_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('semester', $data['semester'])
            ->first();

        if (! $sk) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'SK tim supervisi belum ada untuk tahun ajaran dan semester ini. Buat SK terlebih dahulu di menu Tim & SK.',
            ]);
        }

        return $sk->id;
    }

    protected function resolveTeacherSchedule(array $data, string $schoolId): array
    {
        $schedule = \App\Models\JadwalKbm::with(['subject:id,name'])
            ->where('school_id', $schoolId)
            ->findOrFail($data['jadwal_kbm_id']);

        $profile = GtkProfile::find($data['gtk_id']);
        if (! $profile || (string) $schedule->teacher_id !== (string) $profile->user_id) {
            throw ValidationException::withMessages([
                'jadwal_kbm_id' => 'Jadwal mengajar tidak sesuai dengan guru yang dipilih.',
            ]);
        }

        if ((string) $schedule->academic_year_id !== (string) $data['academic_year_id']) {
            throw ValidationException::withMessages([
                'jadwal_kbm_id' => 'Jadwal mengajar bukan untuk tahun ajaran yang dipilih.',
            ]);
        }

        $date = \Carbon\Carbon::parse($data['tanggal_supervisi']);
        if ((int) $schedule->day_of_week !== (int) $date->dayOfWeekIso) {
            throw ValidationException::withMessages([
                'jadwal_kbm_id' => 'Jadwal mengajar tidak sesuai dengan hari pada tanggal supervisi.',
            ]);
        }

        return [
            'jadwal_kbm_id' => $schedule->id,
            'study_group_id' => $schedule->study_group_id,
            'subject_id' => $schedule->subject_id,
            'slot_index' => $schedule->slot_index,
            'mata_pelajaran' => $schedule->subject?->name,
            'jam_mulai' => substr((string) $schedule->start_time, 0, 5),
            'jam_selesai' => substr((string) $schedule->end_time, 0, 5),
        ];
    }

    protected function ensureInstrumenAllowed(string $schoolId, ?string $instrumenId): void
    {
        if (! $instrumenId) {
            return;
        }

        $exists = SupervisiInstrumen::query()
            ->whereKey($instrumenId)
            ->where(function ($q) use ($schoolId) {
                $q->whereNull('school_id')->orWhere('school_id', $schoolId);
            })
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'instrumen_id' => 'Instrumen tidak tersedia untuk satuan pendidikan ini.',
            ]);
        }
    }

    protected function ensureGtkBelongsToSchool(string $schoolId, string $gtkProfileId): void
    {
        $userIds = GtkProfile::query()->whereKey($gtkProfileId)->pluck('user_id');
        $belongsToSchool = app(\App\Models\GtkEmployment::class)::query()
            ->where('school_id', $schoolId)
            ->whereIn('user_id', $userIds)
            ->exists()
            || app(\App\Models\GtkWorkUnit::class)::query()
                ->whereIn('user_id', $userIds)
                ->whereHas('workUnit.school', fn ($q) => $q->where('id', $schoolId))
                ->exists();

        if (! $belongsToSchool) {
            throw ValidationException::withMessages([
                'gtk_id' => 'GTK yang dipilih tidak terdaftar pada satuan pendidikan ini.',
            ]);
        }
    }

    protected function gtkOptionsForSchool(?string $schoolId)
    {
        if (! $schoolId) {
            return collect();
        }

        // Filter langsung via gtk_employments (gtk_profiles tidak punya school_id).
        // NUPY diambil lewat accessor `latest_nupy` (lazy) agar nilainya benar.
        $userIds = \App\Models\GtkEmployment::query()
            ->where('school_id', $schoolId)
            ->pluck('user_id')
            ->merge(
                \App\Models\GtkWorkUnit::query()
                    ->whereHas('workUnit.school', fn ($q) => $q->where('id', $schoolId))
                    ->pluck('user_id')
            )
            ->unique();

        return GtkProfile::query()
            ->with('user:id,name')
            ->whereIn('user_id', $userIds)
            ->get()
            ->sortBy(fn ($gtk) => mb_strtolower((string) $gtk->user?->name))
            ->values();
    }

    public function show(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = Supervisi::with([
            'gtk:id,user_id',
            'gtk.user:id,name',
            'gtk.latestEmployment:user_id,nupy',
            'observer:id,user_id',
            'observer.user:id,name',
            'observer.latestEmployment:user_id,nupy',
            'academicYear:id,name',
        ])->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $supervisi = $query->findOrFail($id);

        return view('waka.supervisi.show', compact('supervisi'));
    }

    public function edit(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $query = Supervisi::when($schoolId, fn ($q) => $q->where('school_id', $schoolId));
        $supervisi = $query->findOrFail($id);

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYearId = $supervisi->academic_year_id ?: AcademicYear::where('is_active', true)->value('id');
        $formOptions = $this->formOptions($schoolId, $activeYearId, (int) $supervisi->semester);
        $activeSk = $supervisi->sk ?: SupervisiSk::where('school_id', $schoolId)
            ->where('academic_year_id', $activeYearId)
            ->where('semester', $supervisi->semester)
            ->first();

        // Pasangan yang sedang diedit tetap ditampilkan meskipun pengaturannya
        // sudah dinonaktifkan/diubah.
        if ($supervisi->observer_id && $supervisi->gtk_id) {
            $currentPair = [
                'value' => $supervisi->observer_id.'|'.$supervisi->gtk_id,
                'observer_id' => $supervisi->observer_id,
                'gtk_id' => $supervisi->gtk_id,
                'supervisor_name' => $supervisi->observer?->name ?? $supervisi->observer_name ?? '-',
                'supervisee_name' => $supervisi->gtk?->name ?? $supervisi->gtk_name ?? '-',
                'label' => ($supervisi->observer?->name ?? $supervisi->observer_name ?? '-').' → '.($supervisi->gtk?->name ?? $supervisi->gtk_name ?? '-'),
            ];

            if (! collect($formOptions['assignmentOptions'])->contains('value', $currentPair['value'])) {
                $formOptions['assignmentOptions'][] = $currentPair;
            }

            $formOptions['selectedPair'] = $currentPair;
        }

        if ($supervisi->instrumen_id && ! $formOptions['instrumenOptions']->contains('id', $supervisi->instrumen_id)) {
            $currentInstrumen = SupervisiInstrumen::find($supervisi->instrumen_id);
            if ($currentInstrumen) {
                $formOptions['instrumenOptions']->push($currentInstrumen);
            }
        }

        $hasPenilaian = $supervisi->penilaian()->exists();
        $formOptions['selectedScheduleId'] = $supervisi->jadwal_kbm_id;

        return view('waka.supervisi.edit', array_merge(
            compact('supervisi', 'academicYears', 'activeYearId', 'hasPenilaian', 'activeSk'),
            $formOptions
        ));
    }

    public function update(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);

        $validated = $request->validate([
            'gtk_id' => 'required|uuid|exists:gtk_profiles,id',
            'observer_id' => 'required|uuid|exists:gtk_profiles,id|different:gtk_id',
            'gtk_name' => 'nullable|string|max:255',
            'observer_name' => 'nullable|string|max:255',
            'academic_year_id' => 'required|exists:academic_years,id',
            'semester' => 'required|in:1,2',
            'mata_pelajaran' => 'nullable|string|max:255',
            'tanggal_supervisi' => 'required|date',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i|after:jam_mulai',
            'jenis_supervisi' => 'required|in:perangkat_pembelajaran,proses_pembelajaran,penilaian,lainnya',
            'tujuan' => 'nullable|string',
            'status' => 'required|in:terjadwal,berlangsung,selesai,dibatalkan',
            'instrumen_id' => 'required|uuid|exists:supervisi_instrumen,id',
            'jadwal_kbm_id' => 'required|uuid|exists:jadwal_kbms,id',
        ]);

        $validated['school_id'] = $schoolId;
        $validated['supervisi_sk_id'] = $this->resolveSk($validated);
        $validated = array_merge($validated, $this->resolveTeacherSchedule($validated, $schoolId));

        if ($supervisi->instrumen_id
            && $supervisi->penilaian()->exists()
            && (string) $supervisi->instrumen_id !== (string) $validated['instrumen_id']) {
            throw ValidationException::withMessages([
                'instrumen_id' => 'Instrumen tidak dapat diubah karena penilaian sudah diisi.',
            ]);
        }

        if ($supervisi->penilaian()->exists()
            && ((string) $supervisi->jadwal_kbm_id !== (string) $validated['jadwal_kbm_id']
                || (string) $supervisi->tanggal_supervisi?->toDateString() !== (string) $validated['tanggal_supervisi'])) {
            throw ValidationException::withMessages([
                'jadwal_kbm_id' => 'Jadwal tidak dapat diubah karena penilaian sudah diisi.',
            ]);
        }

        $this->validateSupervisorPair($validated);
        $this->assertNoScheduleConflict($validated, $supervisi->id);

        if (empty($validated['gtk_name']) && ! empty($validated['gtk_id'])) {
            $validated['gtk_name'] = GtkProfile::with('user')->find($validated['gtk_id'])?->user?->name;
        }
        if (empty($validated['observer_name']) && ! empty($validated['observer_id'])) {
            $validated['observer_name'] = GtkProfile::with('user')->find($validated['observer_id'])?->user?->name;
        }

        $supervisi->update($validated);

        return redirect()->route('waka.supervisi.show', $supervisi->id)
            ->with('success', 'Data supervisi berhasil diperbarui.');
    }

    public function markDistributed(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::where('school_id', $schoolId)->findOrFail($id);
        $supervisi->update([
            'distributed_at' => now(),
            'distributed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Jadwal dan instrumen ditandai sudah didistribusikan.');
    }

    public function reportToWadir(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::where('school_id', $schoolId)->findOrFail($id);
        $supervisi->update(['reported_to_wadir_at' => now()]);

        return back()->with('success', 'Hasil supervisi ditandai sudah dilaporkan ke Wadir Akademik dan Pengasuhan.');
    }

    public function saveReflection(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'reflection_notes' => 'required|string|max:5000',
        ]);

        $supervisi->update(['reflection_notes' => $validated['reflection_notes']]);

        return back()->with('success', 'Catatan refleksi KSP disimpan.');
    }

    public function destroy(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $supervisi = Supervisi::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->findOrFail($id);
        $supervisi->delete();

        return redirect()->route('waka.supervisi.index')
            ->with('success', 'Data supervisi berhasil dihapus.');
    }
}
