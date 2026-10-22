<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\Student;
use App\Models\Uks\UksPatient;
use App\Models\UksCareEvent;
use App\Models\UksMedicationLog;
use App\Services\Uks\UksClinicService;
use App\Services\Uks\UksMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * UKS Patient Controller — CRUD for UksPatient (Pendaftaran Pasien / RPM-UKS).
 */
class PatientController extends Controller
{
    public function __construct(private readonly UksClinicService $clinic) {}

    // ── Status labels for display ──────────────────────────────

    private const STATUS_LABELS = [
        'menunggu_pemeriksaan' => 'Menunggu Pemeriksaan',
        'sedang_ditangani' => 'Sedang Ditangani',
        'observasi' => 'Observasi',
        'rawat_uks' => 'Rawat UKS',
        'istirahat_di_uks' => 'Istirahat di UKS',
        'kembali_ke_asrama' => 'Kembali ke Asrama',
        'kembali_ke_sekolah' => 'Kembali ke Sekolah',
        'dijemput_wali' => 'Dijemput Wali',
        'pulang' => 'Pulang',
        'dirujuk_ke_klinik' => 'Dirujuk ke Klinik',
        'dirujuk_ke_rumah_sakit' => 'Dirujuk ke RS',
        'selesai' => 'Selesai',
    ];

    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $roles = $currentUser->getRoleNames();
        $schoolId = $request->attributes->get('schoolContextId');
        $activeAy = AcademicYear::where('is_active', true)->first();

        $genderFilter = $this->getGenderFilter($roles);

        $query = UksPatient::with(['student', 'admittedBy', 'currentBedAssignment.bed', 'locationUpdatedBy'])
            ->orderByDesc('admitted_at')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($genderFilter) {
            $query->whereHas('student', fn ($sq) => $sq->where('gender', $genderFilter));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif ($request->boolean('active_only')) {
            $query->whereIn('status', [
                UksPatient::STATUS_WAITING,
                UksPatient::STATUS_TREATED,
                UksPatient::STATUS_OBSERVATION,
                UksPatient::STATUS_INPATIENT,
                UksPatient::STATUS_RESTING_UKS,
            ]);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('queue_number', 'like', "%{$q}%")
                    ->orWhere('medical_record_no', 'like', "%{$q}%")
                    ->orWhereHas('student', fn ($s) => $s->where('name', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('month')) {
            [$year, $month] = explode('-', $request->month);
            $query->whereYear('admitted_at', (int) $year)->whereMonth('admitted_at', (int) $month);
        }

        $patients = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->whereIn('status', [
                UksPatient::STATUS_WAITING,
                UksPatient::STATUS_TREATED,
                UksPatient::STATUS_OBSERVATION,
                UksPatient::STATUS_INPATIENT,
                UksPatient::STATUS_RESTING_UKS,
            ])->count(),
            'referrals' => (clone $query)->where('referred_to_faskes', true)->count(),
            'today' => (clone $query)->today()->count(),
            'observation' => (clone $query)->where('status', UksPatient::STATUS_OBSERVATION)->count(),
            'inpatient' => (clone $query)->where('status', UksPatient::STATUS_INPATIENT)->count(),
            'waiting' => (clone $query)->where('queue_status', UksPatient::QUEUE_WAITING)->count(),
            'pharmacy' => (clone $query)->where('queue_status', UksPatient::QUEUE_PHARMACY)->count(),
        ];

        return view('uks.patients.index', compact('patients', 'stats', 'activeAy'));
    }

    public function create(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $students = Student::where('status', 'active')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get();

        $dormitories = Dormitory::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get();

        $activeAy = AcademicYear::where('is_active', true)->first();

        return view('uks.patients.create', compact('students', 'dormitories', 'activeAy'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'school_id' => 'nullable|uuid|exists:schools,id',
            'academic_year_id' => 'nullable|uuid|exists:academic_years,id',
            'dormitory_id' => 'nullable|uuid|exists:dormitories,id',
            'patient_type' => 'required|in:rawat,pulang,balik',
            'triage_level' => 'nullable|in:P1,P2,P3',
            'chief_complaint' => 'nullable|string|max:2000',
            'symptoms' => 'nullable|array',
            'symptoms.*' => 'string|max:100',
            'blood_pressure' => 'nullable|string|max:20',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'pulse' => 'nullable|integer|min:20|max:300',
            'respiration_rate' => 'nullable|integer|min:5|max:80',
            'oxygen_saturation' => 'nullable|integer|min:50|max:100',
            'height' => 'nullable|numeric|min:20|max:300',
            'weight' => 'nullable|numeric|min:1|max:500',
            'allergies' => 'nullable|string|max:2000',
            'diagnosis' => 'nullable|string|max:2000',
            'treatment' => 'nullable|string|max:5000',
            'medicine_given' => 'nullable|string|max:255',
            'medication_details' => 'nullable|string|max:1000',
            'referred_to_faskes' => 'sometimes|boolean',
            'referral_reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);

        $student = Student::query()->findOrFail($validated['student_id']);
        $schoolId = $validated['school_id'] ?? $request->attributes->get('schoolContextId');

        $patient = $this->clinic->register($student, $validated, $schoolId, Auth::id());

        if (UksMode::isSimple()) {
            // Mode UKS sederhana: tidak ada antrean, pemeriksaan awal
            // langsung dimulai oleh petugas UKS.
            $this->clinic->startExamination($patient, Auth::id());
        }

        return redirect()
            ->route('user.uks.clinic.workspace', ['userId' => Auth::id(), 'uuid' => $patient->id])
            ->with('success', 'Pasien berhasil didaftarkan. Nomor antrean: '.$patient->queue_number.'.');
    }

    public function show(string $userId, string $uuid)
    {
        $patient = UksPatient::with([
            'student.healthRecord',
            'student.activeDormitoryResident.dormitory',
            'admittedBy',
            'dischargedBy',
            'dormitory',
            'treatments.performedBy',
            'latestEncounter.performedBy',
            'prescriptions.items.inventory',
            'prescriptions.prescribedBy',
            'prescriptions.dispensedBy',
            'referrals.facility',
            'medicationLogs',
            'medicationAdministrations.administeredBy',
            'careEvents' => fn ($q) => $q->orderBy('happened_at')->with('performedBy'),
            'currentBedAssignment.bed',
            'locationUpdatedBy',
            'locationLogs' => fn ($q) => $q->with(['recordedBy', 'bed'])->orderByDesc('recorded_at')->limit(20),
        ])
            ->findOrFail($uuid);

        $actions = $this->getAvailableActionsForStatus($patient->status);

        // Riwayat medis lintas episode: semua care-event milik pasien dengan student yang sama
        $pastCareEvents = UksCareEvent::with('performedBy')
            ->whereHas('patient', fn ($q) => $q->where('student_id', $patient->student_id)
                ->where('id', '!=', $patient->id))
            ->orderBy('happened_at')
            ->get();

        // Gabungkan event dari episode ini + episode sebelumnya (timeline utuh per-santri)
        $allCareEvents = $patient->careEvents->merge($pastCareEvents)->sortBy('happened_at')->values();

        // Daftar kunjungan UKS sebelumnya (untuk kartu "Riwayat Kunjungan")
        $previousPatients = UksPatient::with(['admittedBy'])
            ->where('student_id', $patient->student_id)
            ->where('id', '!=', $patient->id)
            ->orderBy('admitted_at', 'desc')
            ->get();

        return view('uks.patients.show', compact('patient', 'actions', 'allCareEvents', 'previousPatients'));
    }

    /**
     * Update patient status (change_status endpoint).
     */
    public function changeStatus(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'new_status' => 'required|string|in:'.implode(',', array_keys(UksPatient::$statusLabels)),
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->clinic->changeStatus($patient, $validated['new_status'], $validated['notes'] ?? null, Auth::id());

        return redirect()->route('user.uks.patients.show', ['userId' => Auth::id(), 'uuid' => $patient->id])
            ->with('success', "Status pasien berhasil diubah ke '".(UksPatient::$statusLabels[$validated['new_status']] ?? $validated['new_status'])."'.");
    }

    /**
     * Discharge a patient — selesai atau rujuk.
     */
    public function discharge(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'action' => 'required|in:selesai,dirujuk_ke_klinik,dirujuk_ke_rumah_sakit',
            'notes' => 'nullable|string|max:2000',
        ]);

        $this->clinic->changeStatus(
            $patient,
            $validated['action'],
            $validated['notes'] ?? null,
            Auth::id()
        );

        return redirect()
            ->route('user.uks.patients.show', ['userId' => Auth::id(), 'uuid' => $patient->id])
            ->with('success', 'Status keluar pasien berhasil dicatat.');
    }

    /**
     * Update patient record (edit diagnosis, treatment, etc.).
     */
    public function update(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'diagnosis' => 'nullable|string|max:2000',
            'treatment' => 'nullable|string|max:5000',
            'medicine_given' => 'nullable|string|max:255',
            'medication_details' => 'nullable|string|max:1000',
            'chief_complaint' => 'nullable|string|max:2000',
            'allergies' => 'nullable|string|max:2000',
            'follow_up_date' => 'nullable|date',
            'symptoms' => 'nullable|array',
            'symptoms.*' => 'string|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);

        $validated['symptoms'] = collect($validated['symptoms'] ?? [])->filter()->values()->all() ?: null;

        $patient->update($validated);

        return redirect()->route('user.uks.patients.show', ['userId' => Auth::id(), 'uuid' => $patient->id])
            ->with('success', 'Data pasien berhasil diperbarui.');
    }

    /**
     * Mark patient as returning (RAWAT → BALIK KEMBALI / PULANG).
     */
    public function markReturn(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'return_type' => 'required|in:pulang,balik',
            'returned_at' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $targetStatus = $validated['return_type'] === 'pulang'
            ? UksPatient::STATUS_LEAVING
            : UksPatient::STATUS_RETURN_DORM;

        if (! in_array($targetStatus, $this->clinic->allowedTransitions($patient->status), true)) {
            $targetStatus = UksPatient::STATUS_COMPLETED;
        }

        $this->clinic->changeStatus($patient, $targetStatus, $validated['notes'] ?? null, Auth::id());

        $patient->update(['patient_type' => $validated['return_type']]);

        return redirect()->route('user.uks.patients.show', ['userId' => Auth::id(), 'uuid' => $patient->id])
            ->with('success', "Pasien ditandai sebagai '{$validated['return_type']}' pada {$validated['returned_at']}.");
    }

    /**
     * Administer medication to a patient.
     */
    public function administerMedication(Request $request, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'medicine_name' => 'required|string|max:255',
            'dosage' => 'nullable|string|max:100',
            'route' => 'nullable|in:oral,topikal,injeksi,inhaled,other',
            'given_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $log = UksMedicationLog::create([
            'patient_id' => $patient->id,
            'administered_by' => Auth::id(),
            'medicine_name' => $validated['medicine_name'],
            'dosage' => $validated['dosage'] ?? null,
            'route' => $validated['route'] ?? 'oral',
            'given_at' => $validated['given_at'] ?? now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $patient->medicine_given = $validated['medicine_name'];
        $patient->save();

        UksCareEvent::create([
            'patient_id' => $patient->id,
            'performed_by' => Auth::id(),
            'happened_at' => $log->given_at,
            'event_type' => UksCareEvent::TYPE_PEMBERIAN_OBAT,
            'event_title' => 'Pemberian Obat: '.$validated['medicine_name'],
            'description' => ($validated['dosage'] ?? '—').' via '.($validated['route'] ?? 'oral'),
        ]);

        return redirect()->route('user.uks.patients.show', ['userId' => Auth::id(), 'uuid' => $patient->id])
            ->with('success', 'Obat berhasil dicatat.');
    }

    /**
     * Record a new treatment/vitals check for a patient.
     */
    public function recordTreatment(Request $request, string $uuid)
    {
        $patient = UksPatient::with('student')->findOrFail($uuid);

        $validated = $request->validate([
            'chief_complaint' => 'nullable|string|max:2000',
            'subjective' => 'nullable|string|max:5000',
            'symptoms' => 'nullable|array',
            'symptoms.*' => 'string|max:200',
            'blood_pressure' => 'nullable|string|max:20',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'pulse' => 'nullable|integer|min:20|max:300',
            'respiration_rate' => 'nullable|integer|min:5|max:80',
            'height' => 'nullable|numeric|min:20|max:300',
            'weight' => 'nullable|numeric|min:1|max:500',
            'oxygen_saturation' => 'nullable|integer|min:50|max:100',
            'physical_exam' => 'nullable|string|max:5000',
            'diagnosis' => 'nullable|string|max:2000',
            'assessment' => 'nullable|string|max:5000',
            'icd10_code' => 'nullable|string|max:10',
            'icd10_name' => 'nullable|string|max:191',
            'treatment' => 'nullable|string|max:5000',
            'plan' => 'nullable|string|max:5000',
            'disposition' => 'nullable|in:pulang,kontrol,observasi,rawat_uks,rujuk',
            'follow_up_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $this->clinic->recordEncounter($patient, $validated, Auth::id());

        return redirect()->route('user.uks.patients.show', ['userId' => Auth::id(), 'uuid' => $patient->id])
            ->with('success', 'Data perawatan berhasil dicatat.');
    }

    /**
     * Assign bed to patient.
     */
    public function assignBed(Request $request, string $uuid)
    {
        $patient = UksPatient::with('student')->findOrFail($uuid);

        $validated = $request->validate([
            'bed_id' => 'required|uuid|exists:uks_beds,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $bed = $this->clinic->assignBed($patient, $validated['bed_id'], $validated['reason'] ?? null, Auth::id());

        return redirect()->route('user.uks.patients.show', ['userId' => Auth::id(), 'uuid' => $patient->id])
            ->with('success', 'Santri berhasil ditempatkan di bed '.$bed->bed_number.'.');
    }

    /**
     * Release patient from bed (stand up bed).
     */
    public function releaseBed(Request $request, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'care_location' => 'nullable|in:'.implode(',', array_keys(UksPatient::$careLocationLabels)),
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->clinic->releaseBed(
            $patient,
            Auth::id(),
            true,
            $validated['care_location'] ?? null,
            $validated['notes'] ?? null
        );

        return redirect()->route('user.uks.patients.show', ['userId' => Auth::id(), 'uuid' => $patient->id])
            ->with('success', 'Pasien berhasil dikeluarkan dari bed UKS.');
    }

    /**
     * DataTables AJAX endpoint for patients index page.
     */
    public function datatable(Request $request)
    {
        $currentUser = auth()->user();
        $roles = $currentUser->getRoleNames();
        $schoolId = $request->attributes->get('schoolContextId');
        $genderFilter = $this->getGenderFilter($roles);

        $query = UksPatient::with(['student', 'admittedBy'])
            ->orderByDesc('admitted_at')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($genderFilter) {
            $query->whereHas('student', fn ($sq) => $sq->where('gender', $genderFilter));
        }

        if ($request->filled('filter_status')) {
            $query->where('status', $request->filter_status);
        }

        if ($request->filled('search.value')) {
            $searchTerm = $request->input('search.value');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('chief_complaint', 'like', "%{$searchTerm}%")
                    ->orWhereHas('student', fn ($s) => $s->where('name', 'like', "%{$searchTerm}%"));
            });
        }

        $orderCol = $request->input('order.0.column');
        $orderDir = $request->input('order.0.dir', 'desc');
        if (isset($orderCol) && (int) $orderCol === 1) {
            $query->leftJoin('students', 'uks_patients.student_id', '=', 'students.id')
                ->orderBy('students.name', $orderDir)
                ->addSelect('uks_patients.*');
        } else {
            $query->orderByDesc('admitted_at');
        }

        $totalRows = (clone $query)->count();
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $patients = $query->skip($start)->take($length)->get();

        $data = $patients->map(function ($patient) {
            $student = $patient->student;
            $actionButtons = '';

            if ($patient->isActive()) {
                $actionButtons = sprintf(
                    '<div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-success btn-xs change-status-btn" data-patient-id="%s" title="Ubah Status">
                            <i class="ti ti-check"></i>
                        </button>
                        <form method="POST" action="%s/discharge" style="display:inline;">
                            %s
                            <button type="submit" class="btn btn-warning btn-xs discharge-btn" title="Pulangkan/Rujuk">
                                <i class="ti ti-moon-stars"></i>
                            </button>
                        </form>
                    </div>',
                    $patient->id,
                    route('user.uks.patients.discharge', ['userId' => auth()->id(), 'uuid' => $patient->id]),
                    csrf_field()
                );
            } else {
                $actionButtons = sprintf(
                    '<span class="badge bg-%s">%s</span>',
                    $patient->status === 'selesai' ? 'success' : 'info',
                    ucfirst($patient->status)
                );
            }

            return [
                '#DTRowIndex' => $start + 1,
                'student_name' => $student ? htmlspecialchars($student->name, ENT_QUOTES, 'UTF-8') : '-',
                'dormitory' => $student?->activeDormitoryResident?->dormitory?->name
                    ? htmlspecialchars($student->activeDormitoryResident->dormitory->name, ENT_QUOTES, 'UTF-8')
                    : '-',
                'patient_type' => ucfirst($patient->patient_type ?? '-'),
                'chief_complaint' => htmlspecialchars($patient->chief_complaint ?? '-', ENT_QUOTES, 'UTF-8'),
                'vitals' => $patient->vitals ? json_encode($patient->vitals) : '-',
                'admitted_at' => $patient->admitted_at ? $patient->admitted_at->format('d M Y H:i') : '-',
                'admitted_by' => $patient->admittedBy ? $patient->admittedBy->name : '-',
                'status' => sprintf(
                    '<span class="badge bg-%s">%s</span>',
                    match ($patient->status) {
                        UksPatient::STATUS_WAITING, UksPatient::STATUS_TREATED => 'warning',
                        UksPatient::STATUS_OBSERVATION, UksPatient::STATUS_INPATIENT => 'primary',
                        UksPatient::STATUS_RESTING_UKS => 'info',
                        UksPatient::STATUS_RETURN_DORM, UksPatient::STATUS_RETURN_SCHOOL,
                        UksPatient::STATUS_LEAVING,
                        UksPatient::STATUS_COMPLETED => 'success',
                        UksPatient::STATUS_PICKED_UP => 'secondary',
                        UksPatient::STATUS_REFERRAL_CLINIC,
                        UksPatient::STATUS_REFERRAL_HOSPITAL => 'danger',
                        default => 'light',
                    },
                    self::STATUS_LABELS[$patient->status] ?? ucfirst($patient->status)
                ),
                'actions' => $actionButtons,
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => $totalRows,
            'recordsFiltered' => $totalRows,
            'data' => $data->toArray(),
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────

    private function getGenderFilter($roles): ?string
    {
        $currentUser = auth()->user();
        $primaryWorkUnit = $currentUser->primaryWorkUnit;
        if ($primaryWorkUnit) {
            if (str_contains($primaryWorkUnit->name ?? '', 'Putra')) {
                return 'L';
            } elseif (str_contains($primaryWorkUnit->name ?? '', 'Putri')) {
                return 'P';
            }
        }

        return null;
    }

    /**
     * Get action buttons available for a given patient status.
     */
    private function getAvailableActionsForStatus(string $status): array
    {
        $labels = [
            UksPatient::STATUS_WAITING => ['Menunggu Pemeriksaan', 'warning', 'ri-time-line'],
            UksPatient::STATUS_TREATED => ['Sedang Ditangani', 'info', 'ri-stethoscope-line'],
            UksPatient::STATUS_OBSERVATION => ['Observasi', 'primary', 'ri-eye-line'],
            UksPatient::STATUS_INPATIENT => ['Rawat UKS', 'primary', 'ri-hospital-line'],
            UksPatient::STATUS_RESTING_UKS => ['Istirahat di UKS', 'secondary', 'ri-sleep-line'],
            UksPatient::STATUS_RETURN_DORM => ['Kembali ke Asrama', 'success', 'ri-home-heart-line'],
            UksPatient::STATUS_RETURN_SCHOOL => ['Kembali ke Sekolah', 'success', 'ri-school-line'],
            UksPatient::STATUS_PICKED_UP => ['Dijemput Wali', 'warning', 'ri-user-heart-line'],
            UksPatient::STATUS_LEAVING => ['Pulang', 'dark', 'ri-logout-box-r-line'],
            UksPatient::STATUS_REFERRAL_CLINIC => ['Rujuk ke Klinik', 'danger', 'ri-hospital-line'],
            UksPatient::STATUS_REFERRAL_HOSPITAL => ['Rujuk ke RS', 'danger', 'ri-hospital-line'],
            UksPatient::STATUS_COMPLETED => ['Selesai', 'success', 'ri-check-line'],
        ];

        $actions = [];

        foreach ($this->clinic->allowedTransitions($status) as $nextStatus) {
            [$label, $color, $icon] = $labels[$nextStatus] ?? [ucfirst(str_replace('_', ' ', $nextStatus)), 'secondary', 'ri-arrow-right-line'];

            $actions[] = [
                'label' => $label,
                'status' => $nextStatus,
                'color' => $color,
                'icon' => $icon,
                'confirm' => 'Ubah status pasien menjadi '.$label.'?',
            ];
        }

        return $actions;
    }
}
