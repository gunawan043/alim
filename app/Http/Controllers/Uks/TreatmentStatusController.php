<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\Uks\UksPatient;
use App\Models\Uks\UksTreatmentNote;
use App\Models\UksBed;
use App\Services\Uks\UksClinicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * TreatmentStatusController — Modul Status Perawatan UKS (pelengkap).
 *
 * Menambahkan kemampuan:
 *   - Melihat & mengubah status perawatan (via delegasi ke flow yang ada)
 *   - Mencatat histori perubahan status eksplisit (UksStatusHistory)
 *   - Mencatat catatan perkembangan selama rawat (UksTreatmentNote)
 *   - Mengelola bed assignment dengan Gedung → Ruangan → Bed
 *
 * Catatan: modul ini TIDAK mengubah alur pemeriksaan / registrasi pasien
 * yang sudah ada; hanya menambahkan fitur di atasnya.
 */
class TreatmentStatusController extends Controller
{
    public function __construct(private readonly UksClinicService $clinic) {}

    public const STATUS_LABELS = [
        UksPatient::STATUS_WAITING => 'Menunggu Pemeriksaan',
        UksPatient::STATUS_TREATED => 'Sedang Ditangani',
        UksPatient::STATUS_OBSERVATION => 'Observasi',
        UksPatient::STATUS_INPATIENT => 'Sedang Dirawat di UKS',
        UksPatient::STATUS_RESTING_UKS => 'Istirahat di UKS',
        UksPatient::STATUS_RETURN_DORM => 'Kembali ke Asrama',
        UksPatient::STATUS_RETURN_SCHOOL => 'Kembali ke Sekolah',
        UksPatient::STATUS_PICKED_UP => 'Dijemput Wali',
        UksPatient::STATUS_LEAVING => 'Pulang',
        UksPatient::STATUS_REFERRAL_CLINIC => 'Dirujuk ke Klinik',
        UksPatient::STATUS_REFERRAL_HOSPITAL => 'Dirujuk ke Rumah Sakit',
        UksPatient::STATUS_COMPLETED => 'Selesai',
    ];

    /** Statuses that indicate a "Sedang Dirawat" patient (occupied bed). */
    public const OCCUPIED_STATUSES = [
        UksPatient::STATUS_INPATIENT,
        UksPatient::STATUS_RESTING_UKS,
    ];

    /**
     * Tampilkan panel Status Perawatan untuk satu pasien.
     */
    public function show(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::with([
            'student',
            'treatmentNotes.recordedBy',
            'medicationAdministrations.administeredBy',
            'statusHistories.changedBy',
            'currentBedAssignment.bed',
            'bedAssignments.bed',
        ])->findOrFail($uuid);

        $availableBeds = $this->getAvailableBeds($patient);

        return view('uks.treatment-status.show', [
            'patient' => $patient,
            'statusLabels' => self::STATUS_LABELS,
            'availableBeds' => $availableBeds,
            'allowedTransitions' => $this->clinic->allowedTransitions($patient->status),
        ]);
    }

    /**
     * Ubah status perawatan pasien + tulis histori eksplisit.
     */
    public function updateStatus(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'new_status' => 'required|string|in:'.implode(',', array_keys(self::STATUS_LABELS)),
            'reason' => 'nullable|string|max:500',
        ]);

        $this->clinic->changeStatus($patient, $validated['new_status'], $validated['reason'] ?? null, Auth::id());

        return redirect()
            ->route('user.uks.treatment-status.show', ['userId' => $userId, 'uuid' => $patient->id])
            ->with('success', 'Status perawatan diperbarui.');
    }

    /**
     * Tambah catatan perkembangan selama rawat.
     */
    public function storeNote(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'recorded_at' => 'nullable|date',
            'note' => 'required|string|max:5000',
        ]);

        $note = UksTreatmentNote::create([
            'patient_id' => $patient->id,
            'recorded_by' => Auth::id(),
            'recorded_at' => $validated['recorded_at'] ?? now(),
            'note' => $validated['note'],
        ]);

        return redirect()
            ->route('user.uks.treatment-status.show', ['userId' => $userId, 'uuid' => $patient->id])
            ->with('success', 'Catatan perkembangan berhasil ditambahkan.');
    }

    /**
     * Assign pasien ke bed dengan konteks Gedung → Ruangan → Bed.
     */
    public function assignBed(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::with('student')->findOrFail($uuid);

        $validated = $request->validate([
            'bed_id' => 'required|uuid|exists:uks_beds,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $bed = $this->clinic->assignBed($patient, $validated['bed_id'], $validated['reason'] ?? null, Auth::id());

        return redirect()
            ->route('user.uks.treatment-status.show', ['userId' => $userId, 'uuid' => $patient->id])
            ->with('success', sprintf(
                'Pasien ditempatkan di %s / %s / %s.',
                $bed->building ?? 'UKS',
                $bed->room ?? '-',
                $bed->bed_number
            ));
    }

    /**
     * Bebaskan pasien dari bed.
     */
    public function releaseBed(Request $request, string $userId, string $uuid)
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

        return back()->with('success', 'Bed dikosongkan dan lokasi perawatan diperbarui.');
    }

    /**
     * Daftar bangunan/ruangan/bed yang tersedia untuk gender pasien & school context.
     */
    private function getAvailableBeds(UksPatient $patient)
    {
        $gender = $patient->student?->gender;

        return UksBed::with('currentAssignment.patient')
            ->when($gender, fn ($q) => $q->byGender($gender))
            ->get()
            ->groupBy(fn ($bed) => $bed->building ?? 'Tanpa Gedung');
    }
}
