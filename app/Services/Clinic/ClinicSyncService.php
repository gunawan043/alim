<?php

namespace App\Services\Clinic;

use App\Models\IntegrationEventLog;
use Illuminate\Support\Facades\DB;

/**
 * Clinic Integration Sync Service.
 *
 * Handles the Boarding ↔ Clinic data handoff for medical permits.
 * Boarding initiates the permit workflow; Clinic takes over clinical
 * records (diagnosis, treatment, prescription).
 *
 * ⚠️ This service must NEVER write to the boarding permit table.
 * Conversely, boarding must never read from clinic tables.
 * The integration layer handles cross-reading via DTOs.
 */
class ClinicSyncService
{
    /**
     * Open a clinical visit in the clinic module for a hospitalized student.
     */
    public function openVisitFromBoardingPermit(
        string $studentId,
        string $boardingPermitId,
        string $permitType,
        ?string $startDate,
        ?string $endDate,
        ?string $notes,
    ): string {
        $visitId = (string) \Illuminate\Support\Str::uuid();

        // Here we'd interact with the Clinic's Visit model:
        // ClinicVisit::create([...]) — but we keep it generic via
        // integration log so no hard coupling.
        // The Clinic module is responsible for its own models and
        // tables. If the Clinic models exist, create them here.

        DB::transaction(function () use ($visitId, $studentId, $boardingPermitId, $permitType, $startDate, $endDate, $notes) {
            // 1. Register clinic visit (only if Clinic module is loaded)
            if (class_exists(\App\Models\ClinicVisit::class)) {
                \App\Models\ClinicVisit::create([
                    'id' => $visitId,
                    'student_id' => $studentId,
                    'source' => 'boarding_permit',
                    'source_id' => $boardingPermitId,
                    'permit_type' => $permitType,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'notes' => $notes,
                    'status' => 'active',
                ]);
            }

            // 2. Auto-create MedicalFollowup for hospitalized cases
            if ($permitType === 'medical' && class_exists(\App\Models\MedicalFollowup::class)) {
                \App\Models\MedicalFollowup::create([
                    'visit_id' => $visitId,
                    'boardings_permit_id' => $boardingPermitId,
                    'type' => 'monitoring_hospitalization',
                    'status' => 'open',
                    'assigned_to' => null,
                    'followup_notes' => 'Auto-generated from boarding health permit approval.',
                ]);
            }
        });

        return $visitId;
    }

    /**
     * Mark a clinic visit as discharged when the boarding permit is discharged.
     */
    public function dischargeVisitFromBoardingPermit(
        string $studentId,
        string $boardingPermitId,
        ?string $dischargeNotes,
    ): void {
        if (! class_exists(\App\Models\ClinicVisit::class)) {
            return; // Clinic module not loaded — no-op
        }

        $visit = \App\Models\ClinicVisit::where('student_id', $studentId)
            ->where('source_id', $boardingPermitId)
            ->where('status', 'active')
            ->first();

        if ($visit) {
            $visit->update([
                'status' => 'discharged',
                'discharge_date' => now(),
                'notes' => $dischargeNotes,
            ]);
        }
    }
}