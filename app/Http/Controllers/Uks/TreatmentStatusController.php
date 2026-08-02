<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Models\Uks\UksPatient;
use App\Models\Uks\UksStatusHistory;
use App\Models\Uks\UksTreatmentNote;
use App\Models\UksBed;
use App\Models\UksBedAssignment;
use App\Models\UksCareEvent;
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

        $oldStatus = $patient->status;
        $newStatus = $validated['new_status'];

        if ($oldStatus === $newStatus) {
            return back()->withErrors(['status' => 'Status tidak berubah.']);
        }

        // ── Perbarui status utama
        $patient->status = $newStatus;
        $patient->save();

        // ── Tulis histori perubahan status eksplisit
        UksStatusHistory::create([
            'patient_id' => $patient->id,
            'changed_by' => Auth::id(),
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_at' => now(),
            'reason' => $validated['reason'] ?? null,
        ]);

        // ── Otomatis bebaskan bed saat status bukan lagi rawat/istirahat
        if (
            in_array($newStatus, [
                UksPatient::STATUS_RETURN_DORM,
                UksPatient::STATUS_LEAVING,
                UksPatient::STATUS_COMPLETED,
                UksPatient::STATUS_REFERRAL_CLINIC,
                UksPatient::STATUS_REFERRAL_HOSPITAL,
            ], true) && $patient->in_bed
        ) {
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

            $patient->in_bed = false;
            $patient->left_bed_at = now();
            $patient->save();
        }

        // ── Tulis care-event agar konsisten dengan timeline existing
        UksCareEvent::create([
            'patient_id' => $patient->id,
            'performed_by' => Auth::id(),
            'happened_at' => now(),
            'event_type' => 'pemeriksaan_ulang',
            'event_title' => 'Perubahan Status Perawatan',
            'description' => sprintf(
                '%s → %s%s',
                self::STATUS_LABELS[$oldStatus] ?? $oldStatus,
                self::STATUS_LABELS[$newStatus] ?? $newStatus,
                $validated['reason'] ? '. '.$validated['reason'] : ''
            ),
        ]);

        return redirect()
            ->route('user.uks.treatment-status.show', ['uuid' => $patient->id])
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
            ->route('user.uks.treatment-status.show', ['uuid' => $patient->id])
            ->with('success', 'Catatan perkembangan berhasil ditambahkan.');
    }

    /**
     * Assign pasien ke bed dengan konteks Gedung → Ruangan → Bed.
     */
    public function assignBed(Request $request, string $userId, string $uuid)
    {
        $patient = UksPatient::findOrFail($uuid);

        $validated = $request->validate([
            'bed_id' => 'required|uuid|exists:uks_beds,id',
            'reason' => 'nullable|string|max:500',
        ]);

        UksBedAssignment::assign($validated['bed_id'], $patient->id, $validated['reason'] ?? null);

        // Otomatis naikkan status ke rawat_uks bila belum rawat
        $bed = UksBed::findOrFail($validated['bed_id']);
        $patient->update([
            'bed_number' => $bed->bed_number,
            'in_bed' => true,
            'taken_bed_at' => now(),
            'status' => $patient->needsBed() ? $patient->status : UksPatient::STATUS_INPATIENT,
        ]);

        // Tulis histori status jika berubah
        if ($patient->wasChanged('status')) {
            UksStatusHistory::create([
                'patient_id' => $patient->id,
                'changed_by' => Auth::id(),
                'from_status' => $patient->getOriginal('status'),
                'to_status' => $patient->status,
                'changed_at' => now(),
                'reason' => 'Penempatan Bed',
            ]);
        }

        return redirect()
            ->route('user.uks.treatment-status.show', ['uuid' => $patient->id])
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

        if ($patient->in_bed) {
            $patient->in_bed = false;
            $patient->left_bed_at = now();
            $patient->save();
        }

        return redirect()
            ->route('user.uks.treatment-status.show', ['uuid' => $patient->id])
            ->with('success', 'Bed dikosongkan.');
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
