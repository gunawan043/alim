<?php

declare(strict_types=1);

namespace App\Services\Uks;

use App\Models\AcademicYear;
use App\Models\FacilityReferral;
use App\Models\Student;
use App\Models\StudentHealthRecord;
use App\Models\StudentMedicineInventory;
use App\Models\StudentMedicineLog;
use App\Models\Uks\UksMedicationAdministration;
use App\Models\Uks\UksPatient;
use App\Models\Uks\UksPatientLocationLog;
use App\Models\Uks\UksPrescription;
use App\Models\Uks\UksPrescriptionItem;
use App\Models\Uks\UksReferral;
use App\Models\Uks\UksStatusHistory;
use App\Models\UksBed;
use App\Models\UksBedAssignment;
use App\Models\UksCareEvent;
use App\Models\UksMedicationLog;
use App\Models\UksTreatment;
use App\Services\AcademicAttendanceSyncService;
use App\Services\Notifications\GuardianWhatsAppNotifier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * UksClinicService — alur klinik UKS: pendaftaran/antrean, triase,
 * pemeriksaan SOAP, resep/farmasi, rujukan, dan rawat inap.
 *
 * Semua perubahan status pasien dilakukan di sini agar histori status,
 * timeline perawatan, bed, presensi akademik, dan notifikasi wali konsisten.
 */
final class UksClinicService
{
    /** Transisi status klinis yang diizinkan. */
    public const TRANSITIONS = [
        UksPatient::STATUS_WAITING => [
            UksPatient::STATUS_TREATED,
            UksPatient::STATUS_OBSERVATION,
            UksPatient::STATUS_INPATIENT,
            UksPatient::STATUS_RESTING_UKS,
            UksPatient::STATUS_REFERRAL_CLINIC,
            UksPatient::STATUS_REFERRAL_HOSPITAL,
            UksPatient::STATUS_COMPLETED,
        ],
        UksPatient::STATUS_TREATED => [
            UksPatient::STATUS_OBSERVATION,
            UksPatient::STATUS_INPATIENT,
            UksPatient::STATUS_RESTING_UKS,
            UksPatient::STATUS_REFERRAL_CLINIC,
            UksPatient::STATUS_REFERRAL_HOSPITAL,
            UksPatient::STATUS_COMPLETED,
        ],
        UksPatient::STATUS_OBSERVATION => [
            UksPatient::STATUS_TREATED,
            UksPatient::STATUS_INPATIENT,
            UksPatient::STATUS_RESTING_UKS,
            UksPatient::STATUS_REFERRAL_CLINIC,
            UksPatient::STATUS_REFERRAL_HOSPITAL,
            UksPatient::STATUS_COMPLETED,
        ],
        UksPatient::STATUS_INPATIENT => [
            UksPatient::STATUS_TREATED,
            UksPatient::STATUS_OBSERVATION,
            UksPatient::STATUS_RESTING_UKS,
            UksPatient::STATUS_REFERRAL_CLINIC,
            UksPatient::STATUS_REFERRAL_HOSPITAL,
            UksPatient::STATUS_COMPLETED,
        ],
        UksPatient::STATUS_RESTING_UKS => [
            UksPatient::STATUS_TREATED,
            UksPatient::STATUS_OBSERVATION,
            UksPatient::STATUS_INPATIENT,
            UksPatient::STATUS_REFERRAL_CLINIC,
            UksPatient::STATUS_REFERRAL_HOSPITAL,
            UksPatient::STATUS_RETURN_DORM,
            UksPatient::STATUS_RETURN_SCHOOL,
            UksPatient::STATUS_PICKED_UP,
            UksPatient::STATUS_LEAVING,
            UksPatient::STATUS_COMPLETED,
        ],
        UksPatient::STATUS_REFERRAL_CLINIC => [
            UksPatient::STATUS_RETURN_DORM,
            UksPatient::STATUS_RETURN_SCHOOL,
            UksPatient::STATUS_PICKED_UP,
            UksPatient::STATUS_LEAVING,
            UksPatient::STATUS_COMPLETED,
        ],
        UksPatient::STATUS_REFERRAL_HOSPITAL => [
            UksPatient::STATUS_RETURN_DORM,
            UksPatient::STATUS_RETURN_SCHOOL,
            UksPatient::STATUS_PICKED_UP,
            UksPatient::STATUS_LEAVING,
            UksPatient::STATUS_COMPLETED,
        ],
        UksPatient::STATUS_RETURN_DORM => [],
        UksPatient::STATUS_RETURN_SCHOOL => [],
        UksPatient::STATUS_PICKED_UP => [],
        UksPatient::STATUS_LEAVING => [],
        UksPatient::STATUS_COMPLETED => [],
    ];

    public const CARE_STATUSES = [
        UksPatient::STATUS_WAITING,
        UksPatient::STATUS_TREATED,
        UksPatient::STATUS_OBSERVATION,
        UksPatient::STATUS_INPATIENT,
        UksPatient::STATUS_RESTING_UKS,
        UksPatient::STATUS_REFERRAL_CLINIC,
        UksPatient::STATUS_REFERRAL_HOSPITAL,
    ];

    public const TERMINAL_STATUSES = [
        UksPatient::STATUS_RETURN_DORM,
        UksPatient::STATUS_RETURN_SCHOOL,
        UksPatient::STATUS_PICKED_UP,
        UksPatient::STATUS_LEAVING,
        UksPatient::STATUS_COMPLETED,
    ];

    /** Status yang memicu notifikasi wali saat pertama kali masuk perawatan. */
    public const NOTIFIABLE_STATUSES = [
        UksPatient::STATUS_TREATED,
        UksPatient::STATUS_OBSERVATION,
        UksPatient::STATUS_INPATIENT,
        UksPatient::STATUS_RESTING_UKS,
        UksPatient::STATUS_REFERRAL_CLINIC,
        UksPatient::STATUS_REFERRAL_HOSPITAL,
    ];

    /** Kompatibilitas data lama sebelum enum status diperluas. */
    public const LEGACY_STATUS_MAP = [
        'aktif' => UksPatient::STATUS_TREATED,
        'selesai' => UksPatient::STATUS_COMPLETED,
        'dirujuk' => UksPatient::STATUS_REFERRAL_CLINIC,
    ];

    public function allowedTransitions(string $status): array
    {
        $status = self::LEGACY_STATUS_MAP[$status] ?? $status;

        return self::TRANSITIONS[$status] ?? [];
    }

    /**
     * Pendaftaran pasien baru + nomor antrean + nomor rekam medis.
     */
    public function register(Student $student, array $data, ?string $schoolId = null, ?string $actorId = null): UksPatient
    {
        return DB::transaction(function () use ($student, $data, $schoolId, $actorId): UksPatient {
            $schoolId = $data['school_id'] ?? $schoolId ?? $student->school_id;
            $academicYearId = $data['academic_year_id']
                ?? AcademicYear::query()->where('is_active', true)->value('id');
            $dormitoryId = $data['dormitory_id']
                ?? optional($student->activeDormitoryResident)->dormitory_id;

            $healthRecord = $student->healthRecord;
            $vitals = $this->buildVitals($data, $healthRecord);
            $symptoms = $this->normalizeSymptoms($data['symptoms'] ?? []);

            $triage = in_array($data['triage_level'] ?? null, array_keys(UksPatient::$triageLabels), true)
                ? $data['triage_level']
                : UksPatient::TRIAGE_P3;

            $patient = UksPatient::create([
                'student_id' => $student->id,
                'school_id' => $schoolId,
                'academic_year_id' => $academicYearId,
                'dormitory_id' => $dormitoryId,
                'medical_record_no' => $this->medicalRecordNumber($student, $schoolId),
                'queue_number' => UksMode::isClinic()
                    ? $this->nextQueueNumber($schoolId, now(), $student->gender)
                    : null,
                'queue_date' => now()->toDateString(),
                'queue_status' => UksPatient::QUEUE_WAITING,
                'triage_level' => $triage,
                'care_location' => UksPatient::LOCATION_UKS,
                'location_notes' => 'Pendaftaran pasien',
                'location_updated_at' => now(),
                'location_updated_by' => $actorId,
                'arrived_at' => now(),
                'admitted_at' => now(),
                'admitted_by' => $actorId,
                'allergies' => ! empty($data['allergies']) ? $data['allergies'] : $healthRecord?->allergies,
                'patient_type' => $data['patient_type'] ?? 'rawat',
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'symptoms' => $symptoms ?: null,
                'vitals' => $vitals ?: null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'treatment' => $data['treatment'] ?? null,
                'medicine_given' => $data['medicine_given'] ?? null,
                'medication_details' => $data['medication_details'] ?? null,
                'referred_to_faskes' => (bool) ($data['referred_to_faskes'] ?? false),
                'referral_reason' => $data['referral_reason'] ?? null,
                'status' => UksPatient::STATUS_WAITING,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->careEvent(
                $patient,
                UksCareEvent::TYPE_MASUK,
                'Masuk UKS',
                'Pendaftaran pasien baru. Keluhan: '.($data['chief_complaint'] ?? '-'),
                $actorId
            );

            $this->recordLocationLog(
                $patient,
                null,
                UksPatient::LOCATION_UKS,
                'Pendaftaran pasien',
                $actorId
            );

            return $patient->refresh();
        });
    }

    /**
     * Pemeriksaan klinis dengan format SOAP.
     */
    public function recordEncounter(UksPatient $patient, array $data, ?string $actorId = null, bool $hasPrescription = false): UksTreatment
    {
        return DB::transaction(function () use ($patient, $data, $actorId, $hasPrescription): UksTreatment {
            $vitals = $this->buildVitals($data, $patient->student?->healthRecord);
            $symptoms = $this->normalizeSymptoms($data['symptoms'] ?? []);
            $disposition = $data['disposition'] ?? null;

            $treatment = UksTreatment::create([
                'patient_id' => $patient->id,
                'performed_by' => $actorId,
                'chief_complaint' => $data['chief_complaint'] ?? $patient->chief_complaint,
                'subjective' => $data['subjective'] ?? $data['chief_complaint'] ?? null,
                'objective' => $data['objective'] ?? null,
                'assessment' => $data['assessment'] ?? $data['diagnosis'] ?? null,
                'plan' => $data['plan'] ?? $data['treatment'] ?? null,
                'physical_exam' => $data['physical_exam'] ?? null,
                'symptoms' => $symptoms ?: null,
                'vitals' => $vitals ?: null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'icd10_code' => $data['icd10_code'] ?? null,
                'icd10_name' => $data['icd10_name'] ?? null,
                'treatment' => $data['treatment'] ?? null,
                'disposition' => $disposition ?: null,
                'follow_up_date' => ! empty($data['follow_up_date']) ? $data['follow_up_date'] : null,
                'notes' => $data['notes'] ?? null,
            ]);

            $patient->fill(array_filter([
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'treatment' => $data['treatment'] ?? null,
                'allergies' => $data['allergies'] ?? null,
                'follow_up_date' => $data['follow_up_date'] ?? null,
                'vitals' => $vitals ?: null,
            ], static fn ($value) => $value !== null && $value !== '' && $value !== []));
            $patient->save();

            if ($disposition === 'rawat_uks' && $patient->status !== UksPatient::STATUS_INPATIENT) {
                $this->changeStatus($patient, UksPatient::STATUS_INPATIENT, 'Disposisi rawat inap UKS', $actorId);
            } elseif ($disposition === 'observasi' && $patient->status !== UksPatient::STATUS_OBSERVATION) {
                $this->changeStatus($patient, UksPatient::STATUS_OBSERVATION, 'Disposisi observasi', $actorId);
            } elseif (in_array($disposition, ['pulang', 'kontrol'], true) && ! $hasPrescription) {
                $this->changeStatus($patient, UksPatient::STATUS_COMPLETED, 'Disposisi pulang/kontrol', $actorId);
            } elseif ($patient->status === UksPatient::STATUS_WAITING) {
                $this->changeStatus($patient, UksPatient::STATUS_TREATED, 'Pemeriksaan dimulai', $actorId);
            }

            if ($hasPrescription) {
                $this->setQueueStatus($patient, UksPatient::QUEUE_PHARMACY);
            } elseif ($patient->status === UksPatient::STATUS_COMPLETED) {
                $this->setQueueStatus($patient, UksPatient::QUEUE_DONE);
            } elseif (in_array($patient->status, [UksPatient::STATUS_INPATIENT, UksPatient::STATUS_OBSERVATION, UksPatient::STATUS_RESTING_UKS], true)) {
                $this->setQueueStatus($patient, UksPatient::QUEUE_DONE);
            } else {
                $this->setQueueStatus($patient, UksPatient::QUEUE_IN_PROGRESS);
            }

            $this->careEvent(
                $patient,
                UksCareEvent::TYPE_PEMERIKSAAN_ULANG,
                ($data['diagnosis'] ?? null) ? 'Diagnosis: '.$data['diagnosis'] : 'Pemeriksaan klinis',
                $data['plan'] ?? $data['treatment'] ?? $data['notes'] ?? null,
                $actorId
            );

            return $treatment->load('prescriptions');
        });
    }

    /**
     * Buat resep baru (bisa lebih dari satu obat).
     */
    public function createPrescription(UksPatient $patient, array $data, ?string $actorId = null): UksPrescription
    {
        return DB::transaction(function () use ($patient, $data, $actorId): UksPrescription {
            $items = collect($data['items'] ?? [])
                ->filter(fn ($item) => ! empty($item['inventory_id']) || ! empty($item['medicine_name']))
                ->values();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Minimal satu obat harus diisi pada resep.',
                ]);
            }

            $prescription = UksPrescription::create([
                'patient_id' => $patient->id,
                'treatment_id' => $data['treatment_id'] ?? $patient->latestEncounter?->id,
                'school_id' => $patient->school_id,
                'prescribed_by' => $actorId,
                'prescription_no' => $this->nextPrescriptionNumber($patient->school_id),
                'prescribed_at' => now(),
                'status' => UksPrescription::STATUS_NEW,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $inventory = ! empty($item['inventory_id'])
                    ? StudentMedicineInventory::query()->find($item['inventory_id'])
                    : null;

                UksPrescriptionItem::create([
                    'prescription_id' => $prescription->id,
                    'inventory_id' => $inventory?->id,
                    'medicine_name' => $item['medicine_name'] ?? $inventory?->medicine_name ?? 'Obat',
                    'dosage' => $item['dosage'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'route' => ! empty($item['route']) ? $item['route'] : 'oral',
                    'duration_days' => ! empty($item['duration_days']) ? $item['duration_days'] : null,
                    'quantity' => isset($item['quantity']) && $item['quantity'] !== '' ? $item['quantity'] : 1,
                    'instructions' => $item['instructions'] ?? null,
                ]);
            }

            if (in_array($patient->queue_status, [
                UksPatient::QUEUE_WAITING,
                UksPatient::QUEUE_CALLED,
                UksPatient::QUEUE_IN_PROGRESS,
                UksPatient::QUEUE_PHARMACY,
            ], true)) {
                $this->setQueueStatus($patient, UksPatient::QUEUE_PHARMACY);
            }

            $this->careEvent(
                $patient,
                'resep',
                'Resep dibuat',
                $prescription->prescription_no.' · '.$items->count().' item',
                $actorId
            );

            return $prescription->load('items.inventory');
        });
    }

    /**
     * Serahkan obat dari farmasi dan potong stok.
     *
     * @param  array<string,float|int>  $quantities  item_id => jumlah yang diserahkan
     */
    public function dispensePrescription(UksPrescription $prescription, array $quantities = [], ?string $actorId = null): UksPrescription
    {
        return DB::transaction(function () use ($prescription, $quantities, $actorId): UksPrescription {
            $prescription->loadMissing(['items.inventory', 'patient.student']);

            if ($prescription->status === UksPrescription::STATUS_DISPENSED) {
                throw ValidationException::withMessages(['status' => 'Resep ini sudah diserahkan.']);
            }

            if ($prescription->status === UksPrescription::STATUS_CANCELLED) {
                throw ValidationException::withMessages(['status' => 'Resep ini sudah dibatalkan.']);
            }

            foreach ($prescription->items as $item) {
                $remaining = (float) $item->quantity - (float) $item->dispensed_quantity;
                $quantity = isset($quantities[$item->id])
                    ? (float) $quantities[$item->id]
                    : $remaining;

                if ($quantity <= 0) {
                    continue;
                }

                if ($quantity > $remaining + 0.001) {
                    throw ValidationException::withMessages([
                        'items' => 'Jumlah obat '.$item->medicine_name.' melebihi sisa resep.',
                    ]);
                }

                $inventory = $item->inventory;

                if ($inventory && (float) $inventory->current_stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => 'Stok '.$item->medicine_name.' tidak cukup. Sisa stok: '.$inventory->current_stock.' '.$inventory->unit,
                    ]);
                }

                if ($inventory) {
                    $inventory->decrement('current_stock', $quantity);
                }

                $item->dispensed_quantity = (float) $item->dispensed_quantity + $quantity;
                $item->save();

                UksMedicationAdministration::create([
                    'patient_id' => $prescription->patient_id,
                    'administered_by' => $actorId,
                    'medicine_name' => $item->medicine_name,
                    'dosage' => $item->dosage,
                    'quantity' => (int) max(1, round($quantity)),
                    'given_at' => now(),
                    'notes' => $item->instructions,
                ]);

                UksMedicationLog::create([
                    'patient_id' => $prescription->patient_id,
                    'administered_by' => $actorId,
                    'medicine_name' => $item->medicine_name,
                    'dosage' => $item->dosage,
                    'route' => $item->route ?: 'oral',
                    'given_at' => now(),
                    'notes' => $item->instructions,
                ]);

                $this->writeInventoryLog($prescription, $item, $inventory, $quantity, $actorId);
            }

            $allDispensed = $prescription->items->every(
                fn (UksPrescriptionItem $item): bool => (float) $item->dispensed_quantity + 0.001 >= (float) $item->quantity
            );

            $prescription->update([
                'status' => $allDispensed ? UksPrescription::STATUS_DISPENSED : UksPrescription::STATUS_PREPARED,
                'dispensed_by' => $allDispensed ? $actorId : $prescription->dispensed_by,
                'dispensed_at' => $allDispensed ? now() : $prescription->dispensed_at,
            ]);

            $patient = $prescription->patient;

            if ($patient) {
                $medicineNames = $prescription->items->pluck('medicine_name')->filter()->unique()->implode(', ');
                $patient->medicine_given = trim(($patient->medicine_given ? $patient->medicine_given.', ' : '').$medicineNames);
                $patient->save();

                $hasPending = $patient->prescriptions()->pending()->exists();

                if ($allDispensed && ! $hasPending && $patient->queue_status === UksPatient::QUEUE_PHARMACY) {
                    $this->setQueueStatus($patient, UksPatient::QUEUE_DONE);

                    $latest = $patient->latestEncounter;
                    if ($patient->status === UksPatient::STATUS_TREATED
                        && $latest
                        && in_array($latest->disposition, ['pulang', 'kontrol'], true)) {
                        $this->changeStatus($patient, UksPatient::STATUS_COMPLETED, 'Obat telah diserahkan', $actorId);
                    }
                }

                $this->careEvent(
                    $patient,
                    UksCareEvent::TYPE_PEMBERIAN_OBAT,
                    ($allDispensed ? 'Resep diserahkan: ' : 'Resep disiapkan sebagian: ').$prescription->prescription_no,
                    $medicineNames,
                    $actorId
                );
            }

            return $prescription->fresh(['items.inventory', 'patient']);
        });
    }

    public function cancelPrescription(UksPrescription $prescription, ?string $reason = null, ?string $actorId = null): UksPrescription
    {
        if ($prescription->status === UksPrescription::STATUS_DISPENSED) {
            throw ValidationException::withMessages(['status' => 'Resep yang sudah diserahkan tidak dapat dibatalkan.']);
        }

        $prescription->update([
            'status' => UksPrescription::STATUS_CANCELLED,
            'notes' => trim(($prescription->notes ? $prescription->notes."\n" : '').($reason ? 'Dibatalkan: '.$reason : 'Dibatalkan')),
        ]);

        $this->careEvent(
            $prescription->patient,
            'resep_batal',
            'Resep dibatalkan: '.$prescription->prescription_no,
            $reason,
            $actorId
        );

        $patient = $prescription->patient;

        if ($patient && $patient->queue_status === UksPatient::QUEUE_PHARMACY) {
            $hasPending = $patient->prescriptions()->pending()->exists();

            if (! $hasPending) {
                $this->setQueueStatus($patient, UksPatient::QUEUE_DONE);
            }
        }

        return $prescription->fresh();
    }

    /**
     * Rujuk pasien ke faskes dan catat surat rujukan.
     */
    public function createReferral(UksPatient $patient, array $data, ?string $actorId = null): UksReferral
    {
        return DB::transaction(function () use ($patient, $data, $actorId): UksReferral {
            $facility = ! empty($data['facility_id'])
                ? FacilityReferral::query()->find($data['facility_id'])
                : null;

            $facilityType = ! empty($data['facility_type'])
                ? $data['facility_type']
                : ($facility?->facility_type ?? 'klinik');

            $referral = UksReferral::create([
                'patient_id' => $patient->id,
                'school_id' => $patient->school_id,
                'facility_id' => $facility?->id,
                'referral_no' => $this->nextReferralNumber($patient->school_id),
                'facility_name' => ! empty($data['facility_name'])
                    ? $data['facility_name']
                    : ($facility?->facility_name ?? 'Fasilitas Kesehatan'),
                'facility_type' => $facilityType,
                'referred_at' => ! empty($data['referred_at']) ? $data['referred_at'] : now(),
                'urgency' => ! empty($data['urgency']) ? $data['urgency'] : 'rutin',
                'diagnosis' => $data['diagnosis'] ?? $patient->diagnosis,
                'reason' => $data['reason'],
                'transport' => $data['transport'] ?? null,
                'accompanied_by' => $data['accompanied_by'] ?? null,
                'status' => UksReferral::STATUS_REFERRED,
                'referred_by' => $actorId,
            ]);

            $patient->referred_to_faskes = true;
            $patient->referral_reason = $data['reason'];
            $patient->save();

            $targetStatus = $facilityType === 'rumah_sakit'
                ? UksPatient::STATUS_REFERRAL_HOSPITAL
                : UksPatient::STATUS_REFERRAL_CLINIC;

            if (in_array($targetStatus, $this->allowedTransitions($patient->status), true)) {
                $this->changeStatus($patient, $targetStatus, $data['reason'], $actorId);
            } else {
                $oldStatus = $patient->status;
                $patient->status = $targetStatus;
                $patient->discharged_at ??= now();
                $patient->discharged_by = $actorId;
                $patient->queue_status = UksPatient::QUEUE_DONE;
                $patient->completed_at ??= now();
                $patient->save();

                UksStatusHistory::create([
                    'patient_id' => $patient->id,
                    'changed_by' => $actorId,
                    'from_status' => $oldStatus,
                    'to_status' => $targetStatus,
                    'changed_at' => now(),
                    'reason' => $data['reason'],
                ]);

                $this->applyCareLocation($patient, UksPatient::LOCATION_FASKES, $data['reason'], $actorId, null, false);

                $this->careEvent($patient, UksCareEvent::TYPE_DIRUJUK, 'Dirujuk ke '.$referral->facility_name, $data['reason'], $actorId);
            }

            return $referral->load(['facility', 'patient.student']);
        });
    }

    public function markReferralReturned(UksReferral $referral, array $data, ?string $actorId = null): UksReferral
    {
        return DB::transaction(function () use ($referral, $data, $actorId): UksReferral {
            $referral->update([
                'status' => UksReferral::STATUS_RETURNED,
                'returned_at' => now(),
                'outcome' => $data['outcome'] ?? null,
            ]);

            $patient = $referral->patient;
            $returnStatus = $data['return_status'] ?? UksPatient::STATUS_RETURN_DORM;

            if ($patient && in_array($returnStatus, $this->allowedTransitions($patient->status), true)) {
                $this->changeStatus($patient, $returnStatus, $data['outcome'] ?? 'Kembali dari rujukan', $actorId);
            }

            return $referral->fresh(['patient']);
        });
    }

    /**
     * Ubah status klinis dengan histori, timeline, presensi, dan notifikasi.
     */
    public function changeStatus(
        UksPatient $patient,
        string $newStatus,
        ?string $reason = null,
        ?string $actorId = null,
        bool $syncAttendance = true
    ): UksPatient {
        $oldStatus = $patient->status;

        if (isset(self::LEGACY_STATUS_MAP[$oldStatus])) {
            $oldStatus = self::LEGACY_STATUS_MAP[$oldStatus];
            $patient->status = $oldStatus;
        }

        if ($oldStatus === $newStatus) {
            if ($patient->isDirty('status')) {
                $patient->save();
            }

            return $patient;
        }

        if (! in_array($newStatus, $this->allowedTransitions($oldStatus), true)) {
            throw ValidationException::withMessages([
                'status' => "Tidak dapat mengubah status dari '{$oldStatus}' ke '{$newStatus}'.",
            ]);
        }

        $patient->status = $newStatus;

        $isTerminal = in_array($newStatus, self::TERMINAL_STATUSES, true);
        $isReferral = in_array($newStatus, [
            UksPatient::STATUS_REFERRAL_CLINIC,
            UksPatient::STATUS_REFERRAL_HOSPITAL,
        ], true);

        if ($isTerminal || $isReferral) {
            $patient->discharged_at ??= now();
            $patient->discharged_by = $actorId;
            $patient->queue_status = UksPatient::QUEUE_DONE;
            $patient->completed_at ??= now();
        }

        if ($isReferral) {
            $patient->referred_to_faskes = true;
        }

        if ($newStatus === UksPatient::STATUS_COMPLETED) {
            $patient->queue_status = UksPatient::QUEUE_DONE;
            $patient->completed_at ??= now();
        }

        if (($isTerminal || $isReferral) && $patient->in_bed) {
            $this->releaseBed($patient, $actorId, false, null, null, false);
        }

        $patient->save();

        $this->syncCareLocationForStatus($patient, $newStatus, $actorId);

        UksStatusHistory::create([
            'patient_id' => $patient->id,
            'changed_by' => $actorId,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_at' => now(),
            'reason' => $reason,
        ]);

        $this->careEvent(
            $patient,
            $this->eventTypeForStatus($newStatus),
            UksPatient::$statusLabels[$newStatus] ?? ucfirst(str_replace('_', ' ', $newStatus)),
            $reason,
            $actorId
        );

        if ($syncAttendance && $patient->student_id) {
            $this->syncAttendance($patient, $oldStatus, $newStatus);
        }

        if ($patient->student && ! in_array($oldStatus, self::NOTIFIABLE_STATUSES, true) && in_array($newStatus, self::NOTIFIABLE_STATUSES, true)) {
            try {
                app(GuardianWhatsAppNotifier::class)
                    ->notifySick($patient->student, $reason, 'uks');
            } catch (\Throwable $e) {
                Log::warning('UKS WA notif gagal: '.$e->getMessage());
            }
        }

        return $patient->refresh();
    }

    public function callPatient(UksPatient $patient, ?string $actorId = null): UksPatient
    {
        if (! in_array($patient->queue_status, [UksPatient::QUEUE_WAITING, UksPatient::QUEUE_CALLED], true)) {
            throw ValidationException::withMessages(['queue' => 'Pasien tidak sedang menunggu antrean.']);
        }

        $patient->update([
            'queue_status' => UksPatient::QUEUE_CALLED,
            'called_at' => $patient->called_at ?? now(),
        ]);

        $this->careEvent($patient, 'panggilan', 'Pasien dipanggil', 'Nomor antrean '.$patient->queue_number, $actorId);

        return $patient->refresh();
    }

    public function startExamination(UksPatient $patient, ?string $actorId = null): UksPatient
    {
        if (! in_array($patient->queue_status, [UksPatient::QUEUE_WAITING, UksPatient::QUEUE_CALLED], true)) {
            throw ValidationException::withMessages(['queue' => 'Pasien tidak dapat memulai pemeriksaan dari status antrean ini.']);
        }

        $patient->update([
            'queue_status' => UksPatient::QUEUE_IN_PROGRESS,
            'screened_at' => $patient->screened_at ?? now(),
        ]);

        if ($patient->status === UksPatient::STATUS_WAITING) {
            $this->changeStatus($patient, UksPatient::STATUS_TREATED, 'Pemeriksaan dimulai', $actorId);
        }

        $this->careEvent($patient, UksCareEvent::TYPE_PEMERIKSAAN, 'Pemeriksaan dimulai', null, $actorId);

        return $patient->refresh();
    }

    public function finishQueue(UksPatient $patient, ?string $actorId = null): UksPatient
    {
        if ($patient->prescriptions()->pending()->exists()) {
            throw ValidationException::withMessages([
                'queue' => 'Masih ada resep yang belum diserahkan. Selesaikan di farmasi terlebih dahulu.',
            ]);
        }

        $this->setQueueStatus($patient, UksPatient::QUEUE_DONE);

        if (in_array($patient->status, [UksPatient::STATUS_WAITING, UksPatient::STATUS_TREATED], true)) {
            $this->changeStatus($patient, UksPatient::STATUS_COMPLETED, 'Pelayanan selesai', $actorId);
        }

        return $patient->refresh();
    }

    public function cancelQueue(UksPatient $patient, ?string $reason = null, ?string $actorId = null): UksPatient
    {
        if (in_array($patient->status, [UksPatient::STATUS_WAITING, UksPatient::STATUS_TREATED], true)) {
            $this->changeStatus($patient, UksPatient::STATUS_COMPLETED, $reason ?: 'Antrean dibatalkan', $actorId);
        }

        $this->setQueueStatus($patient, UksPatient::QUEUE_CANCELLED);

        $this->careEvent($patient, 'batal', 'Antrean dibatalkan', $reason, $actorId);

        return $patient->refresh();
    }

    public function assignBed(UksPatient $patient, string $bedId, ?string $reason = null, ?string $actorId = null): UksBed
    {
        return DB::transaction(function () use ($patient, $bedId, $reason, $actorId): UksBed {
            $bed = UksBed::query()->findOrFail($bedId);

            if (in_array($patient->status, [
                ...self::TERMINAL_STATUSES,
                UksPatient::STATUS_REFERRAL_CLINIC,
                UksPatient::STATUS_REFERRAL_HOSPITAL,
            ], true)) {
                throw ValidationException::withMessages(['bed_id' => 'Pasien sudah selesai/dirujuk; buat kunjungan baru sebelum menempatkan bed.']);
            }

            if ($bed->status === 'perbaikan') {
                throw ValidationException::withMessages(['bed_id' => 'Bed ini sedang dalam perbaikan.']);
            }

            $current = $bed->currentAssignment;

            if ($current && $current->patient_id !== $patient->id) {
                throw ValidationException::withMessages(['bed_id' => 'Bed ini sudah ditempati pasien lain.']);
            }

            if ($patient->student && $bed->gender && $patient->student->gender !== $bed->gender) {
                throw ValidationException::withMessages(['bed_id' => 'Bed ini tidak sesuai gender pasien.']);
            }

            $previousAssignment = UksBedAssignment::query()
                ->where('patient_id', $patient->id)
                ->where('status', 'assigned')
                ->where('bed_id', '!=', $bed->id)
                ->first();

            if ($previousAssignment) {
                $previousBed = $previousAssignment->bed;

                if ($previousBed && $previousBed->status === 'dipakai') {
                    $previousBed->update(['status' => 'tersedia']);
                }
            }

            UksBedAssignment::assign($bed->id, $patient->id, $reason);
            $bed->update(['status' => 'dipakai']);

            $patient->update([
                'bed_number' => $bed->bed_number,
                'in_bed' => true,
                'taken_bed_at' => now(),
            ]);

            $label = trim(($bed->building ?? '').' '.($bed->room ?? '').' '.$bed->bed_number);

            $this->applyCareLocation(
                $patient,
                UksPatient::LOCATION_UKS,
                'Penempatan bed: '.$label.($reason ? ' · '.$reason : ''),
                $actorId,
                $bed->id
            );

            if ($patient->status !== UksPatient::STATUS_INPATIENT
                && in_array(UksPatient::STATUS_INPATIENT, $this->allowedTransitions($patient->status), true)) {
                $this->changeStatus($patient, UksPatient::STATUS_INPATIENT, $reason ?: 'Penempatan bed UKS', $actorId);
            }

            $this->careEvent($patient, UksCareEvent::TYPE_ISTIRAHAT, 'Penempatan bed: '.$label, $reason, $actorId);

            return $bed;
        });
    }

    public function releaseBed(
        UksPatient $patient,
        ?string $actorId = null,
        bool $logEvent = true,
        ?string $newLocation = null,
        ?string $notes = null,
        bool $applyLocation = true
    ): void {
        $assignment = UksBedAssignment::query()
            ->where('patient_id', $patient->id)
            ->where('status', 'assigned')
            ->latest('assigned_at')
            ->first();

        if ($assignment) {
            $assignment->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

            $bed = $assignment->bed;

            if ($bed && $bed->status === 'dipakai') {
                $bed->update(['status' => 'tersedia']);
            }
        }

        $patient->update([
            'in_bed' => false,
            'left_bed_at' => now(),
        ]);

        if ($applyLocation) {
            $this->applyCareLocation(
                $patient,
                $newLocation ?: UksPatient::LOCATION_ASRAMA,
                $notes ?: 'Keluar dari bed UKS',
                $actorId,
                null,
                false
            );
        }

        if ($logEvent) {
            $this->careEvent($patient, UksCareEvent::TYPE_ISTIRAHAT, 'Keluar dari bed UKS', $notes, $actorId);
        }
    }

    /**
     * Ubah lokasi perawatan santri (asrama, UKS, sekolah, rumah, faskes, lainnya).
     */
    public function updateCareLocation(
        UksPatient $patient,
        string $location,
        ?string $notes = null,
        ?string $actorId = null,
        ?string $bedId = null
    ): UksPatient {
        if (! array_key_exists($location, UksPatient::$careLocationLabels)) {
            throw ValidationException::withMessages(['care_location' => 'Lokasi perawatan tidak valid.']);
        }

        if ($location !== UksPatient::LOCATION_UKS && $patient->in_bed) {
            $this->releaseBed($patient, $actorId, false, null, null, false);
        }

        $this->applyCareLocation($patient->refresh(), $location, $notes, $actorId, $bedId, true);

        return $patient->refresh();
    }

    private function syncCareLocationForStatus(UksPatient $patient, string $status, ?string $actorId): void
    {
        $target = match ($status) {
            UksPatient::STATUS_RETURN_DORM => UksPatient::LOCATION_ASRAMA,
            UksPatient::STATUS_RETURN_SCHOOL => UksPatient::LOCATION_SEKOLAH,
            UksPatient::STATUS_PICKED_UP,
            UksPatient::STATUS_LEAVING,
            UksPatient::STATUS_COMPLETED => UksPatient::LOCATION_RUMAH,
            UksPatient::STATUS_REFERRAL_CLINIC,
            UksPatient::STATUS_REFERRAL_HOSPITAL => UksPatient::LOCATION_FASKES,
            UksPatient::STATUS_INPATIENT,
            UksPatient::STATUS_RESTING_UKS => UksPatient::LOCATION_UKS,
            default => null,
        };

        if ($target === null) {
            if ($patient->care_location !== null) {
                return;
            }

            $target = UksPatient::LOCATION_UKS;
        }

        if ($patient->care_location === $target) {
            return;
        }

        $this->applyCareLocation(
            $patient,
            $target,
            'Mengikuti status: '.(UksPatient::$statusLabels[$status] ?? $status),
            $actorId,
            null,
            false
        );
    }

    private function applyCareLocation(
        UksPatient $patient,
        string $to,
        ?string $notes = null,
        ?string $actorId = null,
        ?string $bedId = null,
        bool $logEvent = true
    ): void {
        if (! array_key_exists($to, UksPatient::$careLocationLabels)) {
            throw ValidationException::withMessages(['care_location' => 'Lokasi perawatan tidak valid.']);
        }

        $from = $patient->care_location;
        $changed = $from !== $to;

        if ($changed || $bedId !== null || ($notes !== null && $notes !== $patient->location_notes)) {
            $patient->care_location = $to;
            $patient->location_notes = $notes;
            $patient->location_updated_at = now();
            $patient->location_updated_by = $actorId;
            $patient->save();
        }

        if ($changed || $bedId !== null) {
            $this->recordLocationLog($patient, $from, $to, $notes, $actorId, $bedId);

            if ($logEvent) {
                $this->careEvent(
                    $patient,
                    'lokasi',
                    'Lokasi perawatan: '.(UksPatient::$careLocationLabels[$to] ?? $to),
                    $notes,
                    $actorId
                );
            }
        }
    }

    private function recordLocationLog(
        UksPatient $patient,
        ?string $from,
        string $to,
        ?string $notes = null,
        ?string $actorId = null,
        ?string $bedId = null
    ): UksPatientLocationLog {
        return UksPatientLocationLog::create([
            'patient_id' => $patient->id,
            'school_id' => $patient->school_id,
            'bed_id' => $bedId,
            'from_location' => $from,
            'to_location' => $to,
            'notes' => $notes,
            'recorded_by' => $actorId,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Nomor rekam medis konsisten per santri.
     */
    public function medicalRecordNumber(Student $student, ?string $schoolId = null): string
    {
        $existing = UksPatient::query()
            ->where('student_id', $student->id)
            ->whereNotNull('medical_record_no')
            ->value('medical_record_no');

        if ($existing) {
            return $existing;
        }

        $prefix = 'RM-'.now()->format('y');
        $count = UksPatient::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->where('medical_record_no', 'like', $prefix.'%')
            ->distinct()
            ->count('medical_record_no');

        return $prefix.'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function nextQueueNumber(?string $schoolId, Carbon $date, ?string $gender = null): string
    {
        $count = UksPatient::query()
            ->whereDate('queue_date', $date->toDateString())
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->whereNotNull('queue_number')
            ->count();

        $prefix = match ($gender) {
            'L' => 'L',
            'P' => 'P',
            default => 'U',
        };

        return $prefix.'-'.str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function setQueueStatus(UksPatient $patient, string $status): void
    {
        $patient->queue_status = $status;

        if ($status === UksPatient::QUEUE_DONE) {
            $patient->completed_at ??= now();
        }

        $patient->save();
    }

    private function buildVitals(array $data, ?StudentHealthRecord $healthRecord = null): array
    {
        $vitals = [];

        foreach (['blood_pressure', 'temperature', 'pulse', 'respiration_rate', 'oxygen_saturation', 'height', 'weight'] as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                $vitals[$key] = $data[$key];
            }
        }

        // Lengkapi tinggi/berat dari data kesehatan santri (hasil import)
        // bila petugas tidak mengisinya saat pendaftaran/pemeriksaan.
        if (! isset($vitals['height']) && $healthRecord?->height_cm) {
            $vitals['height'] = $healthRecord->height_cm;
        }
        if (! isset($vitals['weight']) && $healthRecord?->weight_kg) {
            $vitals['weight'] = $healthRecord->weight_kg;
        }

        if (isset($vitals['height'], $vitals['weight']) && (float) $vitals['height'] > 0) {
            $heightMeter = (float) $vitals['height'] / 100;
            $vitals['bmi'] = round((float) $vitals['weight'] / ($heightMeter * $heightMeter), 2);
        }

        return $vitals;
    }

    private function normalizeSymptoms(mixed $symptoms): array
    {
        if (is_string($symptoms)) {
            $symptoms = array_filter(array_map('trim', explode(',', $symptoms)));
        }

        if (! is_array($symptoms)) {
            return [];
        }

        return collect($symptoms)->filter()->values()->all();
    }

    private function syncAttendance(UksPatient $patient, string $oldStatus, string $newStatus): void
    {
        try {
            $sync = app(AcademicAttendanceSyncService::class);

            if (in_array($newStatus, self::CARE_STATUSES, true)) {
                $sync->markAsAbsent(
                    $patient->student_id,
                    $patient->admitted_at ?? now(),
                    now(),
                    AcademicAttendanceSyncService::SOURCE_UPS,
                    $patient->id,
                    'sakit'
                );
            } elseif (in_array($newStatus, self::TERMINAL_STATUSES, true) && in_array($oldStatus, self::CARE_STATUSES, true)) {
                $sync->markAsPresent(
                    $patient->student_id,
                    now(),
                    AcademicAttendanceSyncService::SOURCE_RETURN,
                    $patient->id
                );
            }
        } catch (\Throwable $e) {
            Log::warning('UKS attendance sync gagal: '.$e->getMessage());
        }
    }

    private function eventTypeForStatus(string $status): string
    {
        return match ($status) {
            UksPatient::STATUS_INPATIENT,
            UksPatient::STATUS_RESTING_UKS => UksCareEvent::TYPE_ISTIRAHAT,
            UksPatient::STATUS_RETURN_DORM => UksCareEvent::TYPE_KEMBALI_ASRAMA,
            UksPatient::STATUS_RETURN_SCHOOL => UksCareEvent::TYPE_KEMBALI_SEKOLAH,
            UksPatient::STATUS_PICKED_UP => UksCareEvent::TYPE_JEMPUT_WALI,
            UksPatient::STATUS_LEAVING,
            UksPatient::STATUS_COMPLETED => UksCareEvent::TYPE_PULANG,
            UksPatient::STATUS_REFERRAL_CLINIC,
            UksPatient::STATUS_REFERRAL_HOSPITAL => UksCareEvent::TYPE_DIRUJUK,
            UksPatient::STATUS_OBSERVATION => UksCareEvent::TYPE_PEMERIKSAAN_ULANG,
            default => UksCareEvent::TYPE_PEMERIKSAAN,
        };
    }

    private function careEvent(
        UksPatient $patient,
        string $type,
        string $title,
        ?string $description = null,
        ?string $actorId = null
    ): UksCareEvent {
        return UksCareEvent::create([
            'patient_id' => $patient->id,
            'performed_by' => $actorId,
            'happened_at' => now(),
            'event_type' => $type,
            'event_title' => $title,
            'description' => $description,
        ]);
    }

    private function writeInventoryLog(
        UksPrescription $prescription,
        UksPrescriptionItem $item,
        ?StudentMedicineInventory $inventory,
        float $quantity,
        ?string $actorId
    ): void {
        if (! $inventory) {
            return;
        }

        $patient = $prescription->patient;
        $student = $patient?->student;

        if (! $student) {
            return;
        }

        $schoolId = $patient->school_id ?? $student->school_id;
        $academicYearId = $patient->academic_year_id
            ?? AcademicYear::query()->where('is_active', true)->value('id');

        if (! $schoolId || ! $academicYearId) {
            return;
        }

        try {
            StudentMedicineLog::create([
                'student_id' => $student->id,
                'school_id' => $schoolId,
                'inventory_id' => $inventory->id,
                'academic_year_id' => $academicYearId,
                'log_date' => now()->toDateString(),
                'time_given' => now()->format('H:i:s'),
                'quantity_given' => $quantity,
                'dosage' => $item->dosage,
                'purpose' => $patient->diagnosis ?? $patient->chief_complaint,
                'administered_by' => $actorId,
                'notes' => 'Resep '.$prescription->prescription_no,
            ]);
        } catch (\Throwable $e) {
            Log::warning('UKS medicine log gagal: '.$e->getMessage());
        }
    }

    private function nextPrescriptionNumber(?string $schoolId): string
    {
        $count = UksPrescription::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return 'RX/'.now()->format('Ymd').'/'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    private function nextReferralNumber(?string $schoolId): string
    {
        $count = UksReferral::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return 'RUJ/'.now()->format('Ymd').'/'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
