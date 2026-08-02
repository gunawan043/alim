<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\Student;
use App\Models\Uks\UksPatient;
use App\Models\UksBed;
use App\Models\UksBedAssignment;
use App\Models\UksCareEvent;
use App\Models\UksMedicationLog;
use App\Models\UksTreatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * UKS Patient Controller — CRUD for UksPatient (Pendaftaran Pasien / RPM-UKS).
 */
class PatientController extends Controller
{
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

        $query = UksPatient::with(['student', 'admittedBy'])
            ->orderByDesc('admitted_at')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($genderFilter) {
            $query->whereHas('student', fn ($sq) => $sq->where('gender', $genderFilter));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif ($request->boolean('active_only')) {
            $query->active();
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->whereHas('student', fn ($s) => $s->where('name', 'like', "%{$q}%"));
        }

        if ($request->filled('month')) {
            [$year, $month] = explode('-', $request->month);
            $query->whereRaw("DATE_FORMAT(admitted_at, '%Y-%m') = ?", ["{$year}-{$month}"]);
        }

        $patients = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->active()->count(),
            'referrals' => (clone $query)->where('referred_to_faskes', true)->count(),
            'today' => (clone $query)->today()->count(),
            'observation' => (clone $query)->where('status', UksPatient::STATUS_OBSERVATION)->count(),
            'inpatient' => (clone $query)->where('status', UksPatient::STATUS_INPATIENT)->count(),
        ];

        return view('uks.patients.index', compact('patients', 'stats', 'activeAy'));
    }

    public function create(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $students = Student::whereHas('activeDormitoryResident')
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
            'chief_complaint' => 'nullable|string|max:1000',
            'symptoms' => 'nullable|array',
            'symptoms.*' => 'string|max:100',
            'blood_pressure' => 'nullable|string|max:20',
            'temperature' => 'nullable|numeric|min:34|max:45',
            'pulse' => 'nullable|integer|min:30|max:250',
            'height' => 'nullable|numeric|min:30|max:300',
            'weight' => 'nullable|numeric|min:2|max:300',
            'diagnosis' => 'nullable|string|max:500',
            'treatment' => 'nullable|string|max:1000',
            'medicine_given' => 'nullable|string|max:255',
            'medication_details' => 'nullable|string|max:1000',
            'bed_number' => 'nullable|string|max:20',
            'in_bed' => 'sometimes|boolean',
            'referred_to_faskes' => 'sometimes|boolean',
            'referral_reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);

        $vitals = collect([
            'blood_pressure' => $validated['blood_pressure'] ?? null,
            'temperature' => $validated['temperature'] ?? null,
            'pulse' => $validated['pulse'] ?? null,
            'height' => $validated['height'] ?? null,
            'weight' => $validated['weight'] ?? null,
        ])->filter()->toJson();

        $symptoms = collect($validated['symptoms'] ?? [])->filter()->values()->toJson();

        // Default status
        $validated['status'] = UksPatient::STATUS_WAITING;
        $validated['admitted_at'] = now();
        $validated['admitted_by'] = Auth::id();
        $validated['symptoms'] = $symptoms;

        // Bed tracking
        if (! empty($validated['bed_number']) || (isset($validated['in_bed']) && $validated['in_bed'])) {
            $validated['in_bed'] = true;
            $validated['taken_bed_at'] = now();
            $validated['left_bed_at'] = null;
        }

        if ($validated['referred_to_faskes'] === 1 || $validated['referred_to_faskes'] === '1') {
            $validated['referred_to_faskes'] = true;
        } else {
            $validated['referred_to_faskes'] = false;
        }

        unset($validated['blood_pressure'], $validated['temperature'],
            $validated['pulse'], $validated['height'], $validated['weight']);

        $patient = UksPatient::create(array_merge($validated, ['vitals' => $vitals]));

        // Log care event
        UksCareEvent::create([
            'patient_id' => $patient->id,
            'performed_by' => Auth::id(),
            'happened_at' => now(),
            'event_type' => 'masuk',
            'event_title' => 'Masuk UKS',
            'description' => 'Pendaftaran pasien baru dengan keluhan: '.($validated['chief_complaint'] ?? '-'),
        ]);

        // Redirect to index without filters so the newly-created patient appears immediately
        return redirect()->route('user.uks.patients.index', ['userId' => Auth::id()])
            ->with('success', 'Pasien berhasil didaftarkan.');
    }

    public function show(string $userId, string $uuid)
    {
        $patient = UksPatient::with([
            'student',
            'admittedBy',
            'dormitory',
            'treatments',
            'medicationLogs',
            'careEvents' => fn ($q) => $q->orderBy('happened_at')->with('performedBy'),
            'currentBedAssignment.bed',
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
        $oldStatus = $patient->status;
        $newStatus = $request->input('new_status');

        $allowedTransitions = [
            UksPatient::STATUS_WAITING => [
                UksPatient::STATUS_TREATED,
                UksPatient::STATUS_OBSERVATION,
                UksPatient::STATUS_INPATIENT,
                UksPatient::STATUS_COMPLETED,
            ],
            UksPatient::STATUS_TREATED => [
                UksPatient::STATUS_OBSERVATION,
                UksPatient::STATUS_INPATIENT,
                UksPatient::STATUS_REFERRAL_CLINIC,
                UksPatient::STATUS_REFERRAL_HOSPITAL,
                UksPatient::STATUS_COMPLETED,
            ],
            UksPatient::STATUS_OBSERVATION => [
                UksPatient::STATUS_INPATIENT,
                UksPatient::STATUS_TREATED,
                UksPatient::STATUS_REFERRAL_CLINIC,
                UksPatient::STATUS_REFERRAL_HOSPITAL,
                UksPatient::STATUS_COMPLETED,
            ],
            UksPatient::STATUS_INPATIENT => [
                UksPatient::STATUS_OBSERVATION,
                UksPatient::STATUS_TREATED,
                UksPatient::STATUS_REFERRAL_CLINIC,
                UksPatient::STATUS_REFERRAL_HOSPITAL,
                UksPatient::STATUS_COMPLETED,
                UksPatient::STATUS_RESTING_UKS,
            ],
            UksPatient::STATUS_RESTING_UKS => [
                UksPatient::STATUS_TREATED,
                UksPatient::STATUS_OBSERVATION,
                UksPatient::STATUS_RETURN_DORM,
                UksPatient::STATUS_RETURN_SCHOOL,
                UksPatient::STATUS_PICKED_UP,
                UksPatient::STATUS_LEAVING,
            ],
            UksPatient::STATUS_RETURN_DORM => [],
            UksPatient::STATUS_RETURN_SCHOOL => [],
            UksPatient::STATUS_PICKED_UP => [],
            UksPatient::STATUS_LEAVING => [],
            UksPatient::STATUS_REFERRAL_CLINIC => [],
            UksPatient::STATUS_REFERRAL_HOSPITAL => [],
            UksPatient::STATUS_COMPLETED => [],
        ];

        if (! in_array($newStatus, $allowedTransitions[$oldStatus] ?? [])) {
            return back()->withErrors([
                'status' => "Tidak dapat mengubah status dari '{$oldStatus}' ke '{$newStatus}'.",
            ]);
        }

        $patient->status = $newStatus;

        $isTerminal = in_array($newStatus, [
            UksPatient::STATUS_COMPLETED,
            UksPatient::STATUS_RETURN_DORM,
            UksPatient::STATUS_RETURN_SCHOOL,
            UksPatient::STATUS_PICKED_UP,
            UksPatient::STATUS_LEAVING,
        ], true);

        $patient->discharged_at = $isTerminal ? now() : null;
        $patient->discharged_by = in_array($newStatus, [
            UksPatient::STATUS_REFERRAL_CLINIC,
            UksPatient::STATUS_REFERRAL_HOSPITAL,
        ], true) ? auth()->id() : null;
        $patient->notes = ($patient->notes ? $patient->notes."\n" : '').$request->input('notes', '');

        if (in_array($newStatus, [UksPatient::STATUS_REFERRAL_CLINIC, UksPatient::STATUS_REFERRAL_HOSPITAL])) {
            $patient->referred_to_faskes = true;
        }

        $patient->save();

        // Bed assignment is auto-managed: occupied during rawat_uks OR istirahat_di_uks;
        // cleared when leaving the room (terminal statuses except istirahat).
        $bedStatuses = [UksPatient::STATUS_INPATIENT, UksPatient::STATUS_RESTING_UKS];

        if (in_array($newStatus, $bedStatuses, true) && ! $patient->in_bed) {
            $patient->in_bed = true;
            $patient->taken_bed_at = now();
            $patient->save();
        } elseif (! in_array($newStatus, $bedStatuses, true) && $patient->in_bed) {
            $patient->in_bed = false;
            $patient->left_bed_at = now();
            $patient->save();
        }

        // Log care event
        UksCareEvent::create([
            'patient_id' => $patient->id,
            'performed_by' => Auth::id(),
            'happened_at' => now(),
            'event_type' => $this->eventTypeForStatus($newStatus),
            'event_title' => self::STATUS_LABELS[$newStatus] ?? $newStatus,
            'description' => "Status diubah dari {$oldStatus} ke {$newStatus}",
        ]);

        return redirect()->route('user.uks.patients.show', ['userId' => Auth::id(), 'uuid' => $patient->id])
            ->with('success', "Status pasien berhasil diubah ke '".(self::STATUS_LABELS[$newStatus] ?? $newStatus)."'.");
    }

    /**
     * Discharge a patient — set status to 'selesai'.
     */
    public function discharge(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        if (! $patient->isActive()) {
            return back()->withErrors(['status' => 'Pasien ini sudah tidak aktif.']);
        }

        $validated = $request->validate([
            'action' => 'required|in:selesai,dirujuk',
            'discharged_at' => 'nullable|date|after_or_equal:'.($patient->admitted_at ? $patient->admitted_at->format('Y-m-d\TH:i') : ''),
            'notes' => 'nullable|string|max:2000',
        ]);

        $patient->status = $validated['action'];
        $patient->discharged_at = $validated['discharged_at'] ?? now();
        $patient->discharged_by = auth()->id();
        $patient->notes = ($patient->notes ? $patient->notes."\n" : '').$validated['notes'];

        if ($validated['action'] === 'dirujuk') {
            $patient->referred_to_faskes = true;
            $patient->save();
        } else {
            $patient->save();
        }

        $redirectRoute = $validated['action'] === 'dirujuk'
            ? 'user.uks.facility-referrals.create'
            : 'user.uks.patients.index';

        return redirect()->route($redirectRoute, ['userId' => auth()->user()->id])
            ->with('success', "Pasien berhasil {$validated['action']}.");
    }

    /**
     * Update patient record (edit diagnosis, treatment, etc.).
     */
    public function update(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'diagnosis' => 'nullable|string|max:500',
            'treatment' => 'nullable|string|max:1000',
            'medicine_given' => 'nullable|string|max:255',
            'medication_details' => 'nullable|string|max:1000',
            'chief_complaint' => 'nullable|string|max:1000',
            'symptoms' => 'nullable|array',
            'symptoms.*' => 'string|max:100',
            'bed_number' => 'nullable|string|max:20',
            'in_bed' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

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

        if (! $patient->isActive()) {
            return back()->withErrors(['status' => 'Hanya pasien aktif yang bisa ditandai kembali.']);
        }

        $validated = $request->validate([
            'return_type' => 'required|in:pulang,balik',
            'returned_at' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $patient->patient_type = $validated['return_type'];
        $patient->status = UksPatient::STATUS_COMPLETED;
        $patient->discharged_at = $validated['returned_at'];
        $patient->discharged_by = auth()->id();
        $patient->notes = ($patient->notes ? $patient->notes."\n" : '').$validated['notes'];
        $patient->save();

        UksCareEvent::create([
            'patient_id' => $patient->id,
            'performed_by' => Auth::id(),
            'happened_at' => $validated['returned_at'],
            'event_type' => 'pulang',
            'event_title' => 'Pulang - '.ucfirst($validated['return_type']),
            'description' => $validated['notes'],
        ]);

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
            'notes' => $validated['notes'],
        ]);

        // Also update patient's medicine_given field for quick display
        $patient->medicine_given = $validated['medicine_name'];
        $patient->save();

        UksCareEvent::create([
            'patient_id' => $patient->id,
            'performed_by' => Auth::id(),
            'happened_at' => $log->given_at,
            'event_type' => 'pemberian_obat',
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
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'chief_complaint' => 'nullable|string|max:1000',
            'symptoms' => 'nullable|array',
            'symptoms.*' => 'string|max:200',
            'blood_pressure' => 'nullable|string|max:20',
            'temperature' => 'nullable|numeric|min:34|max:45',
            'pulse' => 'nullable|integer|min:30|max:250',
            'height' => 'nullable|numeric|min:30|max:300',
            'weight' => 'nullable|numeric|min:2|max:300',
            'oxygen_saturation' => 'nullable|integer|min:70|max:100',
            'diagnosis' => 'nullable|string|max:500',
            'treatment' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
        ]);

        // Build vitals array
        $vitals = collect([
            'blood_pressure' => $validated['blood_pressure'] ?? null,
            'temperature' => $validated['temperature'] ?? null,
            'pulse' => $validated['pulse'] ?? null,
            'height' => $validated['height'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'oxygen_saturation' => $validated['oxygen_saturation'] ?? null,
        ])->filter()->toJson();

        $symptoms = collect($validated['symptoms'] ?? [])->filter()->values()->toJson();

        $treatment = UksTreatment::create([
            'patient_id' => $patient->id,
            'performed_by' => Auth::id(),
            'chief_complaint' => $validated['chief_complaint'] ?? $patient->chief_complaint,
            'symptoms' => $symptoms,
            'vitals' => $vitals,
            'diagnosis' => $validated['diagnosis'] ?? $patient->diagnosis,
            'treatment' => $validated['treatment'],
            'notes' => $validated['notes'],
        ]);

        // Update patient with latest info if not already filled
        if (! $patient->diagnosis && $validated['diagnosis']) {
            $patient->diagnosis = $validated['diagnosis'];
            $patient->chief_complaint = $validated['chief_complaint'] ?? $patient->chief_complaint;
            $patient->save();
        }

        $eventTitle = $validated['diagnosis']
            ? "Diagnosa: {$validated['diagnosis']}"
            : 'Pemeriksaan ulang';

        UksCareEvent::create([
            'patient_id' => $patient->id,
            'performed_by' => Auth::id(),
            'happened_at' => now(),
            'event_type' => 'pemeriksaan_ulang',
            'event_title' => $eventTitle,
            'description' => $validated['notes'],
        ]);

        return redirect()->route('user.uks.patients.show', ['userId' => Auth::id(), 'uuid' => $patient->id])
            ->with('success', 'Data perawatan berhasil dicatat.');
    }

    /**
     * Assign bed to patient.
     */
    public function assignBed(Request $request, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'bed_id' => 'required|uuid|exists:uks_beds,id',
            'reason' => 'nullable|string|max:500',
        ]);

        UksBedAssignment::assign($validated['bed_id'], $patient->id, $validated['reason'] ?? null);

        $patient->update([
            'bed_number' => $validated['bed_id'] ? UksBed::find($validated['bed_id'])->bed_number : null,
            'in_bed' => true,
            'taken_bed_at' => now(),
            'status' => UksPatient::STATUS_INPATIENT,
        ]);

        $bed = UksBed::find($validated['bed_id']);
        $identifier = $bed ? $bed->identifier : 'Unknown';

        UksCareEvent::create([
            'patient_id' => $patient->id,
            'performed_by' => Auth::id(),
            'happened_at' => now(),
            'event_type' => 'istirahat',
            'event_title' => 'Penempatan Bed: '.$identifier,
            'description' => $validated['reason'] ?? null,
        ]);

        return redirect()->route('user.uks.patients.show', ['userId' => Auth::id(), 'uuid' => $patient->id])
            ->with('success', 'Santri berhasil ditempatkan di ranjang '.$identifier);
    }

    /**
     * Release patient from bed (stand up bed).
     */
    public function releaseBed(Request $request, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $assignment = UksBedAssignment::where('patient_id', $patient->id)
            ->where('status', 'assigned')
            ->latest('assigned_at')
            ->first();

        if ($assignment) {
            $assignment->update([
                'status' => 'released',
                'released_at' => now(),
            ]);
        }

        $patient->update([
            'in_bed' => false,
            'left_bed_at' => now(),
        ]);

        UksCareEvent::create([
            'patient_id' => $patient->id,
            'performed_by' => Auth::id(),
            'happened_at' => now(),
            'event_type' => 'istirahat',
            'event_title' => 'Keluar dari Ranjang',
            'description' => 'Sanutri turun dari ranjang UKS',
        ]);

        return redirect()->route('user.uks.patients.show', ['userId' => Auth::id(), 'uuid' => $patient->id])
            ->with('success', 'Pesawat berhasil dikeluarkan dari ranjang');
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
                    route('user.uks.patients.discharge', $patient->id),
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
                '#DTRowIndex' => $start + $patient->id,
                'student_name' => $student ? htmlspecialchars($student->name, ENT_QUOTES, 'UTF-8') : '-',
                'dormitory' => $student?->dormitory ? htmlspecialchars($student->dormitory->name, ENT_QUOTES, 'UTF-8') : '-',
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
        if ($roles->contains('Admin UKS')) {
            return 'L';
        }
        if ($roles->contains('Admin UKS')) {
            return 'P';
        }

        return null;
    }

    /**
     * Map a status value to a care event type.
     */
    private function eventTypeForStatus(string $status): string
    {
        return match ($status) {
            UksPatient::STATUS_INPATIENT,
            UksPatient::STATUS_RESTING_UKS => 'istirahat',
            UksPatient::STATUS_RETURN_DORM => 'kembali_asrama',
            UksPatient::STATUS_RETURN_SCHOOL => 'kembali_sekolah',
            UksPatient::STATUS_PICKED_UP => 'jemput_wali',
            UksPatient::STATUS_LEAVING,
            UksPatient::STATUS_COMPLETED => 'pulang',
            UksPatient::STATUS_REFERRAL_CLINIC,
            UksPatient::STATUS_REFERRAL_HOSPITAL => 'dirujuk',
            default => 'pemeriksaan',
        };
    }

    /**
     * Get action buttons available for a given patient status.
     */
    private function getAvailableActionsForStatus(string $status): array
    {
        return match ($status) {
            // Active / inpatient — full menu
            UksPatient::STATUS_WAITING,
            UksPatient::STATUS_TREATED,
            UksPatient::STATUS_OBSERVATION,
            UksPatient::STATUS_INPATIENT => [
                [
                    'label' => 'Rawat Lanjut / Obserbasi',
                    'status' => UksPatient::STATUS_TREATED,
                    'color' => 'info',
                    'icon' => 'ri-capsule-line',
                    'confirm' => 'Yakin ingin melanjutkan perawatan pasien?',
                ],
                [
                    'label' => 'Rawat UKS',
                    'status' => UksPatient::STATUS_INPATIENT,
                    'color' => 'info',
                    'icon' => 'ri-stethoscope-line',
                    'confirm' => 'Pindahkan pasien ke rawat inap di UKS?',
                ],
                [
                    'label' => 'Istirahat di UKS',
                    'status' => UksPatient::STATUS_RESTING_UKS,
                    'color' => 'secondary',
                    'icon' => 'ri-sleep-line',
                    'confirm' => 'Pindahkan pasien ke ranjang istirahat (tanpa perawatan aktif)?',
                ],
                [
                    'label' => 'Rujuk ke Klinik',
                    'status' => UksPatient::STATUS_REFERRAL_CLINIC,
                    'color' => 'danger',
                    'icon' => 'ri-hospital-line',
                    'confirm' => 'Rujuk pasien ke klinik/faskes?',
                ],
                [
                    'label' => 'Rujuk ke RS',
                    'status' => UksPatient::STATUS_REFERRAL_HOSPITAL,
                    'color' => 'danger',
                    'icon' => 'ri-hospital-line',
                    'confirm' => 'Rujuk pasien ke rumah sakit?',
                ],
                [
                    'label' => 'Selesai',
                    'status' => UksPatient::STATUS_COMPLETED,
                    'color' => 'success',
                    'icon' => 'ri-check-line',
                    'confirm' => 'Tandai pasien selesai (pulang normal)?',
                ],
            ],

            // Resting — can return to care or leave
            UksPatient::STATUS_RESTING_UKS => [
                [
                    'label' => 'Teruskan Perawatan',
                    'status' => UksPatient::STATUS_TREATED,
                    'color' => 'info',
                    'icon' => 'ri-capsule-line',
                    'confirm' => 'Kembalikan pasien ke perawatan aktif?',
                ],
                [
                    'label' => 'Kembali ke Asrama',
                    'status' => UksPatient::STATUS_RETURN_DORM,
                    'color' => 'primary',
                    'icon' => 'ri-home-heart-line',
                    'confirm' => 'Pasien kembali ke asrama?',
                ],
                [
                    'label' => 'Kembali ke Sekolah',
                    'status' => UksPatient::STATUS_RETURN_SCHOOL,
                    'color' => 'primary',
                    'icon' => 'ri-school-line',
                    'confirm' => 'Pasien kembali ke sekolah (non-asrama)?',
                ],
                [
                    'label' => 'Dijemput Wali',
                    'status' => UksPatient::STATUS_PICKED_UP,
                    'color' => 'warning',
                    'icon' => 'ri-user-heart-line',
                    'confirm' => 'Pasien dijemput wali/orang tua?',
                ],
                [
                    'label' => 'Pulang',
                    'status' => UksPatient::STATUS_LEAVING,
                    'color' => 'dark',
                    'icon' => 'ri-logout-box-r-line',
                    'confirm' => 'Pasien pulang biasa?',
                ],
                [
                    'label' => 'Selesai',
                    'status' => UksPatient::STATUS_COMPLETED,
                    'color' => 'success',
                    'icon' => 'ri-check-line',
                    'confirm' => 'Tandai pasien selesai?',
                ],
            ],

            // Terminal — no actions available
            default => [],
        };
    }
}
