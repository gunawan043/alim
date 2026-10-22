<?php

declare(strict_types=1);

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\FacilityReferral;
use App\Models\StudentMedicineInventory;
use App\Models\Uks\UksPatient;
use App\Models\Uks\UksPrescription;
use App\Models\Uks\UksReferral;
use App\Services\Pdf\DocumentPdfService;
use App\Services\Uks\UksClinicService;
use App\Services\Uks\UksMode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ClinicController — alur pelayanan klinik UKS:
 * antrean & triase, lembar pemeriksaan SOAP, resep, dan rujukan.
 */
class ClinicController extends Controller
{
    public function __construct(private readonly UksClinicService $clinic) {}

    public function queue(Request $request)
    {
        if (UksMode::isSimple()) {
            return redirect()
                ->route('user.uks.patients.index', ['userId' => $request->route('userId')])
                ->with('info', 'Mode UKS sederhana tidak memakai antrean. Silakan daftarkan pasien langsung.');
        }

        $request->validate(['date' => 'nullable|date']);

        $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();
        $schoolId = $request->attributes->get('schoolContextId');
        $genderFilter = $this->genderFilter();

        $base = UksPatient::query()
            ->with(['student.activeDormitoryResident.dormitory', 'admittedBy'])
            ->where(function ($query) use ($date) {
                $query->whereDate('queue_date', $date->toDateString())
                    ->orWhere(function ($legacy) use ($date) {
                        $legacy->whereNull('queue_date')->whereDate('admitted_at', $date->toDateString());
                    });
            })
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($genderFilter, fn ($query) => $query->whereHas('student', fn ($student) => $student->where('gender', $genderFilter)));

        $stats = [
            'waiting' => (clone $base)->where('queue_status', UksPatient::QUEUE_WAITING)->count(),
            'called' => (clone $base)->where('queue_status', UksPatient::QUEUE_CALLED)->count(),
            'in_progress' => (clone $base)->where('queue_status', UksPatient::QUEUE_IN_PROGRESS)->count(),
            'pharmacy' => (clone $base)->where('queue_status', UksPatient::QUEUE_PHARMACY)->count(),
            'done' => (clone $base)->where('queue_status', UksPatient::QUEUE_DONE)->count(),
            'p1' => (clone $base)->where('triage_level', UksPatient::TRIAGE_P1)
                ->whereIn('queue_status', [UksPatient::QUEUE_WAITING, UksPatient::QUEUE_CALLED, UksPatient::QUEUE_IN_PROGRESS, UksPatient::QUEUE_PHARMACY])
                ->count(),
        ];

        $query = clone $base;
        $query->when($request->filled('queue_status'), fn ($q) => $q->where('queue_status', $request->queue_status))
            ->when($request->filled('triage'), fn ($q) => $q->where('triage_level', $request->triage))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('queue_number', 'like', "%{$search}%")
                        ->orWhere('medical_record_no', 'like', "%{$search}%")
                        ->orWhereHas('student', fn ($student) => $student->where('name', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%")
                            ->orWhere('nisn', 'like', "%{$search}%"));
                });
            });

        $patients = $query
            ->orderByRaw("CASE triage_level WHEN 'P1' THEN 1 WHEN 'P2' THEN 2 ELSE 3 END")
            ->orderByRaw('CASE queue_status WHEN \'pemeriksaan\' THEN 1 WHEN \'dipanggil\' THEN 2 WHEN \'menunggu\' THEN 3 WHEN \'farmasi\' THEN 4 ELSE 5 END')
            ->orderBy('queue_number')
            ->paginate(20)
            ->withQueryString();

        return view('uks.clinic.queue', [
            'patients' => $patients,
            'stats' => $stats,
            'date' => $date,
            'genderFilter' => $genderFilter,
        ]);
    }

    public function workspace(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::with([
            'student.healthRecord',
            'student.activeDormitoryResident.dormitory',
            'admittedBy',
            'dischargedBy',
            'currentBedAssignment.bed',
            'encounters.performedBy',
            'prescriptions.items.inventory',
            'prescriptions.prescribedBy',
            'prescriptions.dispensedBy',
            'referrals.facility',
            'referrals.referredBy',
            'statusHistories.changedBy',
            'careEvents.performedBy',
            'treatmentNotes.recordedBy',
            'medicationAdministrations.administeredBy',
        ])->findOrFail($uuid);

        $schoolId = $request->attributes->get('schoolContextId') ?? $patient->school_id;

        $inventory = StudentMedicineInventory::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->where('current_stock', '>', 0)
            ->orderBy('medicine_name')
            ->get();

        $facilities = FacilityReferral::query()
            ->active()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->orderBy('facility_name')
            ->get();

        $latestEncounter = $patient->encounters->first();
        $previousVisits = UksPatient::query()
            ->where('student_id', $patient->student_id)
            ->where('id', '!=', $patient->id)
            ->orderByDesc('admitted_at')
            ->limit(10)
            ->get();

        $activePrescriptions = $patient->prescriptions
            ->filter(fn (UksPrescription $prescription) => $prescription->is_pending)
            ->values();

        return view('uks.clinic.workspace', [
            'patient' => $patient,
            'latestEncounter' => $latestEncounter,
            'inventory' => $inventory,
            'facilities' => $facilities,
            'previousVisits' => $previousVisits,
            'activePrescriptions' => $activePrescriptions,
            'allowedTransitions' => $this->clinic->allowedTransitions($patient->status),
        ]);
    }

    public function storeEncounter(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::with('student')->findOrFail($uuid);

        $validated = $request->validate($this->encounterRules() + [
            'items' => 'nullable|array',
            'items.*.inventory_id' => 'nullable|uuid|exists:student_medicine_inventory,id',
            'items.*.medicine_name' => 'nullable|string|max:191',
            'items.*.dosage' => 'nullable|string|max:100',
            'items.*.frequency' => 'nullable|string|max:100',
            'items.*.route' => 'nullable|in:oral,topikal,injeksi,inhaled,rectal,other',
            'items.*.duration_days' => 'nullable|integer|min:1|max:365',
            'items.*.quantity' => 'nullable|numeric|min:0.01|max:10000',
            'items.*.instructions' => 'nullable|string|max:1000',
        ]);

        $items = collect($validated['items'] ?? [])
            ->filter(fn ($item) => ! empty($item['inventory_id']) || ! empty($item['medicine_name']))
            ->values()
            ->all();

        $hasPrescription = count($items) > 0;

        DB::transaction(function () use ($patient, $validated, $items, $hasPrescription): void {
            $this->clinic->recordEncounter($patient, $validated, Auth::id(), $hasPrescription);

            if ($hasPrescription) {
                $this->clinic->createPrescription($patient, [
                    'items' => $items,
                    'notes' => $validated['notes'] ?? null,
                ], Auth::id());
            }
        });

        return redirect()
            ->route('user.uks.clinic.workspace', ['userId' => $userId, 'uuid' => $patient->id])
            ->with('success', $hasPrescription
                ? 'Pemeriksaan tersimpan. Resep dikirim ke farmasi.'
                : 'Pemeriksaan klinis berhasil disimpan.');
    }

    public function storePrescription(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.inventory_id' => 'nullable|uuid|exists:student_medicine_inventory,id',
            'items.*.medicine_name' => 'nullable|string|max:191',
            'items.*.dosage' => 'nullable|string|max:100',
            'items.*.frequency' => 'nullable|string|max:100',
            'items.*.route' => 'nullable|in:oral,topikal,injeksi,inhaled,rectal,other',
            'items.*.duration_days' => 'nullable|integer|min:1|max:365',
            'items.*.quantity' => 'nullable|numeric|min:0.01|max:10000',
            'items.*.instructions' => 'nullable|string|max:1000',
        ]);

        $this->clinic->createPrescription($patient, $validated, Auth::id());

        return redirect()
            ->route('user.uks.clinic.workspace', ['userId' => $userId, 'uuid' => $patient->id])
            ->with('success', 'Resep berhasil dibuat dan masuk ke antrean farmasi.');
    }

    public function storeReferral(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'facility_id' => 'nullable|uuid|exists:facility_referrals,id',
            'facility_name' => 'nullable|string|max:191|required_without:facility_id',
            'facility_type' => 'nullable|in:klinik,puskesmas,rumah_sakit,dokter_praktik,rs_psychologist,posyandu',
            'referred_at' => 'nullable|date',
            'urgency' => 'nullable|in:rutin,segera,darurat',
            'diagnosis' => 'nullable|string|max:2000',
            'reason' => 'required|string|max:2000',
            'transport' => 'nullable|string|max:50',
            'accompanied_by' => 'nullable|string|max:191',
        ]);

        $this->clinic->createReferral($patient, $validated, Auth::id());

        return redirect()
            ->route('user.uks.clinic.workspace', ['userId' => $userId, 'uuid' => $patient->id])
            ->with('success', 'Rujukan pasien berhasil dicatat.');
    }

    public function referralsIndex(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');

        $base = UksReferral::query()
            ->with(['patient.student', 'facility', 'referredBy'])
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($sub) use ($search) {
                    $sub->where('facility_name', 'like', "%{$search}%")
                        ->orWhere('referral_no', 'like', "%{$search}%")
                        ->orWhereHas('patient.student', fn ($student) => $student->where('name', 'like', "%{$search}%"));
                });
            });

        $stats = [
            'total' => (clone $base)->count(),
            'referred' => (clone $base)->where('status', UksReferral::STATUS_REFERRED)->count(),
            'returned' => (clone $base)->where('status', UksReferral::STATUS_RETURNED)->count(),
            'this_month' => (clone $base)->whereYear('referred_at', now()->year)->whereMonth('referred_at', now()->month)->count(),
        ];

        $referrals = $base->orderByDesc('referred_at')->paginate(20)->withQueryString();

        return view('uks.referrals.index', [
            'referrals' => $referrals,
            'stats' => $stats,
        ]);
    }

    public function markReferralReturned(Request $request, string $userId, string $uuid)
    {
        $referral = UksReferral::with('patient')->findOrFail($uuid);

        $validated = $request->validate([
            'return_status' => 'required|in:kembali_ke_asrama,kembali_ke_sekolah,dijemput_wali,pulang,selesai',
            'outcome' => 'nullable|string|max:2000',
        ]);

        $this->clinic->markReferralReturned($referral, $validated, Auth::id());

        return redirect()
            ->route('user.uks.referrals.index', ['userId' => $userId])
            ->with('success', 'Status kepulangan pasien dari rujukan berhasil dicatat.');
    }

    public function call(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);
        $this->clinic->callPatient($patient, Auth::id());

        return back()->with('success', 'Pasien dipanggil.');
    }

    public function start(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);
        $this->clinic->startExamination($patient, Auth::id());

        return redirect()
            ->route('user.uks.clinic.workspace', ['userId' => $userId, 'uuid' => $patient->id])
            ->with('success', 'Pemeriksaan dimulai.');
    }

    public function finish(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);
        $this->clinic->finishQueue($patient, Auth::id());

        return back()->with('success', 'Pelayanan pasien ditandai selesai.');
    }

    public function cancel(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);
        $this->clinic->cancelQueue($patient, $request->input('reason'), Auth::id());

        return back()->with('success', 'Antrean pasien dibatalkan.');
    }

    public function printReferral(string $userId, string $uuid)
    {
        $referral = UksReferral::with(['patient.student', 'patient.school', 'facility', 'referredBy'])
            ->findOrFail($uuid);

        return app(DocumentPdfService::class)->fromView(
            'uks.referrals.print',
            compact('referral'),
            'Surat-Rujukan-'.$referral->referral_no,
        );
    }

    public function printPrescription(string $userId, string $uuid)
    {
        $prescription = UksPrescription::with(['patient.student', 'patient.school', 'items.inventory', 'prescribedBy'])
            ->findOrFail($uuid);

        return app(DocumentPdfService::class)->fromView(
            'uks.prescriptions.print',
            compact('prescription'),
            'Resep-'.$prescription->prescription_no,
        );
    }

    private function encounterRules(): array
    {
        return [
            'chief_complaint' => 'nullable|string|max:2000',
            'subjective' => 'nullable|string|max:5000',
            'objective' => 'nullable|string|max:5000',
            'physical_exam' => 'nullable|string|max:5000',
            'blood_pressure' => 'nullable|string|max:20',
            'temperature' => 'nullable|numeric|between:30,45',
            'pulse' => 'nullable|integer|between:20,300',
            'respiration_rate' => 'nullable|integer|between:5,80',
            'oxygen_saturation' => 'nullable|integer|between:50,100',
            'height' => 'nullable|numeric|between:20,300',
            'weight' => 'nullable|numeric|between:1,500',
            'diagnosis' => 'nullable|string|max:2000',
            'assessment' => 'nullable|string|max:5000',
            'icd10_code' => 'nullable|string|max:10',
            'icd10_name' => 'nullable|string|max:191',
            'treatment' => 'nullable|string|max:5000',
            'plan' => 'nullable|string|max:5000',
            'disposition' => 'nullable|in:pulang,kontrol,observasi,rawat_uks,rujuk',
            'follow_up_date' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
            'allergies' => 'nullable|string|max:2000',
        ];
    }

    private function genderFilter(): ?string
    {
        $workUnit = auth()->user()?->primaryWorkUnit;
        $name = $workUnit?->name ?? '';

        if (str_contains($name, 'Putra')) {
            return 'L';
        }

        if (str_contains($name, 'Putri')) {
            return 'P';
        }

        return null;
    }
}
