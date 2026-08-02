<?php

declare(strict_types=1);

namespace Tests\Feature\Uks;

use App\Models\Uks\UksMedicationAdministration;
use App\Models\Uks\UksPatient;
use App\Models\Uks\UksStatusHistory;
use App\Models\Uks\UksTreatmentNote;
use App\Models\UksBed;
use App\Models\UksCareEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the UKS Treatment Status module — verifikasi pelengkap
 * (catatan perkembangan, pemberian obat, histori status) tanpa mengubah
 * alur pemeriksaan existing.
 */
final class UksTreatmentStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a UksPatient bare minimum (tidak membuat students/dormitories penuh).
     */
    private function makePatient(array $attrs = []): UksPatient
    {
        return UksPatient::create(array_merge([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'student_id' => (string) \Illuminate\Support\Str::uuid(),
            'status' => UksPatient::STATUS_INPATIENT,
            'patient_type' => 'rawat',
            'admitted_at' => now()->subHour(),
        ], $attrs));
    }

    public function test_treatment_note_relationship_works(): void
    {
        $patient = $this->makePatient();

        UksTreatmentNote::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'patient_id' => $patient->id,
            'recorded_at' => now(),
            'note' => 'Suhu tubuh 37.8°C',
        ]);

        $this->assertCount(1, $patient->refresh()->treatmentNotes);
        $this->assertSame('Suhu tubuh 37.8°C', $patient->treatmentNotes->first()->note);
    }

    public function test_medication_administration_records_quantity_and_given_at(): void
    {
        $patient = $this->makePatient();

        $admin = UksMedicationAdministration::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'patient_id' => $patient->id,
            'medicine_name' => 'Paracetamol',
            'dosage' => '500 mg',
            'quantity' => 2,
            'given_at' => now(),
        ]);

        $this->assertSame(2, $admin->quantity);
        $this->assertSame('Paracetamol', $admin->medicine_name);
        $this->assertCount(1, $patient->refresh()->medicationAdministrations);
    }

    public function test_status_history_records_status_transition(): void
    {
        $patient = $this->makePatient(['status' => UksPatient::STATUS_OBSERVATION]);

        $hist = UksStatusHistory::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'patient_id' => $patient->id,
            'from_status' => UksPatient::STATUS_TREATED,
            'to_status' => UksPatient::STATUS_OBSERVATION,
            'changed_at' => now(),
            'reason' => 'Kondisi membaik',
        ]);

        $this->assertSame(UksPatient::STATUS_OBSERVATION, $hist->to_status);
        $this->assertCount(1, $patient->refresh()->statusHistories);
    }

    public function test_patient_can_have_multiple_treatment_notes(): void
    {
        $patient = $this->makePatient();

        foreach (['08:00', '09:00', '10:30'] as $idx => $hour) {
            UksTreatmentNote::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'patient_id' => $patient->id,
                'recorded_at' => now()->setTime((int) explode(':', $hour)[0], (int) explode(':', $hour)[1]),
                'note' => "Catatan jam $hour",
            ]);
        }

        $this->assertCount(3, $patient->refresh()->treatmentNotes);
    }

    public function test_medication_quantities_default_to_one(): void
    {
        $patient = $this->makePatient();

        $admin = UksMedicationAdministration::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'patient_id' => $patient->id,
            'medicine_name' => 'Vitamin C',
            'given_at' => now(),
        ]);

        $this->assertSame(1, $admin->quantity);
    }

    public function test_new_status_constants_used_in_feature_match_required_list(): void
    {
        // Spec list:
        $required = [
            UksPatient::STATUS_INPATIENT,        // Sedang Dirawat di UKS
            UksPatient::STATUS_OBSERVATION,      // Observasi
            UksPatient::STATUS_RESTING_UKS,      // Istirahat di UKS
            UksPatient::STATUS_RETURN_DORM,      // Kembali ke Asrama
            UksPatient::STATUS_RETURN_SCHOOL,    // Kembali ke Sekolah
            UksPatient::STATUS_PICKED_UP,        // Dijemput Wali
            UksPatient::STATUS_REFERRAL_CLINIC,  // Dirujuk ke Klinik
            UksPatient::STATUS_REFERRAL_HOSPITAL, // Dirujuk ke Rumah Sakit
            UksPatient::STATUS_LEAVING,          // Pulang
            UksPatient::STATUS_COMPLETED,        // Selesai
        ];

        foreach ($required as $status) {
            $this->assertNotEmpty($status, 'Status constant must be defined');
        }
    }

    public function test_controller_status_labels_match_patient_status(): void
    {
        $ref = new \ReflectionClass(\App\Http\Controllers\Uks\TreatmentStatusController::class);
        $constants = $ref->getConstants();

        // Ensure STATUS_LABELS map exists & has labels for the patient statuses
        $this->assertArrayHasKey('STATUS_LABELS', $constants);
        $labels = $constants['STATUS_LABELS'];

        $required = [
            UksPatient::STATUS_INPATIENT,
            UksPatient::STATUS_OBSERVATION,
            UksPatient::STATUS_RESTING_UKS,
            UksPatient::STATUS_RETURN_DORM,
            UksPatient::STATUS_RETURN_SCHOOL,
            UksPatient::STATUS_PICKED_UP,
            UksPatient::STATUS_LEAVING,
            UksPatient::STATUS_REFERRAL_CLINIC,
            UksPatient::STATUS_REFERRAL_HOSPITAL,
            UksPatient::STATUS_COMPLETED,
        ];

        foreach ($required as $status) {
            $this->assertArrayHasKey($status, $labels, "Missing label for {$status}");
            $this->assertNotEmpty($labels[$status]);
        }
    }

    public function test_new_migrations_tables_exist_in_schema(): void
    {
        $this->assertTrue(\Schema::hasTable('uks_treatment_notes'));
        $this->assertTrue(\Schema::hasTable('uks_medication_administrations'));
        $this->assertTrue(\Schema::hasTable('uks_status_histories'));
    }

    public function test_uks_beds_got_building_and_room_columns(): void
    {
        $this->assertTrue(\Schema::hasColumn('uks_beds', 'building'));
        $this->assertTrue(\Schema::hasColumn('uks_beds', 'room'));
    }

    public function test_uks_bed_fillable_includes_new_columns(): void
    {
        $bed = new UksBed;
        $this->assertContains('building', $bed->getFillable());
        $this->assertContains('room', $bed->getFillable());
    }

    public function test_care_event_still_records_status_change_via_existing_flow(): void
    {
        // Sanity check: UksCareEvent is the legacy time-line and still functions.
        $patient = $this->makePatient();

        UksCareEvent::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'patient_id' => $patient->id,
            'happened_at' => now(),
            'event_type' => UksCareEvent::TYPE_PEMBERIAN_OBAT,
            'event_title' => 'Pemberian Obat: Paracetamol',
            'description' => '500 mg',
        ]);

        $this->assertCount(1, $patient->refresh()->careEvents);
    }
}
