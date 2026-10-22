<?php

declare(strict_types=1);

namespace Tests\Feature\Uks;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentMedicineInventory;
use App\Models\Uks\UksPatient;
use App\Models\Uks\UksPrescription;
use App\Models\Uks\UksReferral;
use App\Models\UksBed;
use App\Services\Uks\UksClinicService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class UksClinicWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function school(): School
    {
        return School::factory()->create();
    }

    private function student(School $school): Student
    {
        return Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'nisn' => (string) random_int(1000000000, 9999999999),
            'name' => 'Santri Uji Klinik',
            'gender' => 'L',
            'status' => 'active',
        ]);
    }

    private function patient(School $school, Student $student): UksPatient
    {
        return app(UksClinicService::class)->register($student, [
            'chief_complaint' => 'Demam sejak kemarin',
            'triage_level' => UksPatient::TRIAGE_P2,
        ], $school->id);
    }

    public function test_clinic_schema_is_ready(): void
    {
        foreach ([
            'medical_record_no',
            'queue_number',
            'queue_date',
            'queue_status',
            'triage_level',
            'care_location',
            'location_notes',
            'location_updated_at',
            'arrived_at',
            'completed_at',
            'allergies',
            'follow_up_date',
        ] as $column) {
            $this->assertTrue(\Schema::hasColumn('uks_patients', $column), "Missing uks_patients.{$column}");
        }

        foreach ([
            'subjective',
            'objective',
            'assessment',
            'plan',
            'physical_exam',
            'icd10_code',
            'icd10_name',
            'disposition',
            'follow_up_date',
        ] as $column) {
            $this->assertTrue(\Schema::hasColumn('uks_treatments', $column), "Missing uks_treatments.{$column}");
        }

        $this->assertTrue(\Schema::hasTable('uks_prescriptions'));
        $this->assertTrue(\Schema::hasTable('uks_prescription_items'));
        $this->assertTrue(\Schema::hasTable('uks_referrals'));
        $this->assertTrue(\Schema::hasTable('uks_patient_location_logs'));
    }

    public function test_registration_creates_queue_number_medical_record_and_triage(): void
    {
        $school = $this->school();
        $student = $this->student($school);

        $patient = $this->patient($school, $student);

        $this->assertSame($school->id, $patient->school_id);
        $this->assertSame($student->id, $patient->student_id);
        $this->assertNotNull($patient->queue_number);
        $this->assertStringStartsWith('L-', $patient->queue_number);
        $this->assertNotNull($patient->medical_record_no);
        $this->assertSame(UksPatient::QUEUE_WAITING, $patient->queue_status);
        $this->assertSame(UksPatient::STATUS_WAITING, $patient->status);
        $this->assertSame(UksPatient::TRIAGE_P2, $patient->triage_level);
        $this->assertSame(UksPatient::LOCATION_UKS, $patient->care_location);
        $this->assertNotNull($patient->arrived_at);
        $this->assertNotNull($patient->admitted_at);
        $this->assertDatabaseHas('uks_patient_location_logs', [
            'patient_id' => $patient->id,
            'to_location' => UksPatient::LOCATION_UKS,
        ]);
    }

    public function test_care_location_can_be_changed_and_is_logged(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $service = app(UksClinicService::class);
        $patient = $this->patient($school, $student);

        $service->updateCareLocation($patient, UksPatient::LOCATION_ASRAMA, 'Dipindah untuk istirahat di asrama');

        $patient->refresh();
        $this->assertSame(UksPatient::LOCATION_ASRAMA, $patient->care_location);
        $this->assertSame('Dipindah untuk istirahat di asrama', $patient->location_notes);
        $this->assertDatabaseHas('uks_patient_location_logs', [
            'patient_id' => $patient->id,
            'from_location' => UksPatient::LOCATION_UKS,
            'to_location' => UksPatient::LOCATION_ASRAMA,
        ]);
    }

    public function test_status_changes_update_care_location_automatically(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $service = app(UksClinicService::class);
        $patient = $this->patient($school, $student);

        $service->changeStatus($patient, UksPatient::STATUS_TREATED, 'Ditangani');
        $service->changeStatus($patient, UksPatient::STATUS_OBSERVATION, 'Observasi');
        $service->changeStatus($patient, UksPatient::STATUS_RESTING_UKS, 'Istirahat');
        $service->changeStatus($patient, UksPatient::STATUS_RETURN_SCHOOL, 'Kembali ke sekolah');

        $this->assertSame(UksPatient::LOCATION_SEKOLAH, $patient->refresh()->care_location);

        $secondStudent = $this->student($school);
        $second = $this->patient($school, $secondStudent);
        $service->changeStatus($second, UksPatient::STATUS_COMPLETED, 'Selesai');

        $this->assertSame(UksPatient::LOCATION_RUMAH, $second->refresh()->care_location);
    }

    public function test_second_registration_for_same_student_keeps_medical_record_number(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $service = app(UksClinicService::class);

        $first = $service->register($student, ['chief_complaint' => 'Pusing'], $school->id);
        $second = $service->register($student, ['chief_complaint' => 'Batuk'], $school->id);

        $this->assertSame($first->medical_record_no, $second->medical_record_no);
        $this->assertNotSame($first->queue_number, $second->queue_number);
    }

    public function test_status_transition_writes_history_and_completes_queue(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $service = app(UksClinicService::class);
        $patient = $this->patient($school, $student);

        $service->changeStatus($patient, UksPatient::STATUS_TREATED, 'Mulai ditangani');

        $this->assertSame(UksPatient::STATUS_TREATED, $patient->refresh()->status);
        $this->assertDatabaseHas('uks_status_histories', [
            'patient_id' => $patient->id,
            'from_status' => UksPatient::STATUS_WAITING,
            'to_status' => UksPatient::STATUS_TREATED,
        ]);

        $service->changeStatus($patient, UksPatient::STATUS_COMPLETED, 'Selesai');

        $patient->refresh();
        $this->assertSame(UksPatient::STATUS_COMPLETED, $patient->status);
        $this->assertSame(UksPatient::QUEUE_DONE, $patient->queue_status);
        $this->assertNotNull($patient->discharged_at);
        $this->assertNotNull($patient->completed_at);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $service = app(UksClinicService::class);
        $patient = $this->patient($school, $student);

        $service->changeStatus($patient, UksPatient::STATUS_COMPLETED, 'Selesai');

        $this->expectException(ValidationException::class);

        $service->changeStatus($patient->refresh(), UksPatient::STATUS_TREATED, 'Tidak boleh');
    }

    public function test_prescription_can_be_dispensed_and_reduces_stock(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $service = app(UksClinicService::class);
        $patient = $this->patient($school, $student);

        $inventory = StudentMedicineInventory::create([
            'school_id' => $school->id,
            'medicine_name' => 'Paracetamol',
            'category' => 'obat_dalam',
            'unit' => 'tablet',
            'current_stock' => 10,
            'min_stock_alert' => 2,
        ]);

        $prescription = $service->createPrescription($patient, [
            'items' => [[
                'inventory_id' => $inventory->id,
                'dosage' => '500 mg',
                'frequency' => '3x1',
                'quantity' => 3,
                'instructions' => 'Sesudah makan',
            ]],
        ]);

        $this->assertSame(UksPrescription::STATUS_NEW, $prescription->status);
        $this->assertSame(10.0, (float) $inventory->refresh()->current_stock);
        $this->assertSame(UksPatient::QUEUE_PHARMACY, $patient->refresh()->queue_status);

        $service->dispensePrescription($prescription, [], null);

        $this->assertSame(UksPrescription::STATUS_DISPENSED, $prescription->refresh()->status);
        $this->assertSame(7.0, (float) $inventory->refresh()->current_stock);
        $this->assertDatabaseHas('uks_medication_administrations', [
            'patient_id' => $patient->id,
            'medicine_name' => 'Paracetamol',
        ]);
        $this->assertSame(UksPatient::QUEUE_DONE, $patient->refresh()->queue_status);
    }

    public function test_referral_creates_record_and_moves_patient_to_referral_status(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $service = app(UksClinicService::class);
        $patient = $this->patient($school, $student);

        $referral = $service->createReferral($patient, [
            'facility_name' => 'Klinik Sehat',
            'facility_type' => 'klinik',
            'urgency' => 'segera',
            'reason' => 'Butuh pemeriksaan dokter',
        ]);

        $this->assertInstanceOf(UksReferral::class, $referral);
        $this->assertDatabaseHas('uks_referrals', [
            'patient_id' => $patient->id,
            'facility_name' => 'Klinik Sehat',
            'status' => UksReferral::STATUS_REFERRED,
        ]);

        $patient->refresh();
        $this->assertSame(UksPatient::STATUS_REFERRAL_CLINIC, $patient->status);
        $this->assertTrue($patient->referred_to_faskes);
        $this->assertSame(UksPatient::QUEUE_DONE, $patient->queue_status);
        $this->assertSame(UksPatient::LOCATION_FASKES, $patient->care_location);
    }

    public function test_referral_can_be_marked_returned(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $service = app(UksClinicService::class);
        $patient = $this->patient($school, $student);

        $referral = $service->createReferral($patient, [
            'facility_name' => 'Puskesmas',
            'facility_type' => 'puskesmas',
            'urgency' => 'rutin',
            'reason' => 'Observasi lanjutan',
        ]);

        $service->markReferralReturned($referral, [
            'return_status' => UksPatient::STATUS_RETURN_DORM,
            'outcome' => 'Sudah diperiksa, kembali ke asrama',
        ]);

        $this->assertSame(UksReferral::STATUS_RETURNED, $referral->refresh()->status);
        $this->assertSame(UksPatient::STATUS_RETURN_DORM, $patient->refresh()->status);
    }

    public function test_bed_assignment_and_release_updates_occupancy(): void
    {
        $school = $this->school();
        $student = $this->student($school);
        $service = app(UksClinicService::class);
        $patient = $this->patient($school, $student);

        $bed = UksBed::create([
            'gender' => 'L',
            'building_or_room' => 'UKS Putra',
            'building' => 'UKS Putra',
            'room' => 'Ruang A',
            'bed_number' => 'A-01',
            'status' => 'tersedia',
        ]);

        $service->assignBed($patient, $bed->id, 'Observasi', null);

        $bed->refresh();
        $this->assertSame('dipakai', $bed->status);
        $this->assertNotNull($bed->currentAssignment);
        $this->assertTrue($patient->refresh()->in_bed);
        $this->assertSame(UksPatient::STATUS_INPATIENT, $patient->status);
        $this->assertSame(UksPatient::LOCATION_UKS, $patient->care_location);

        $service->releaseBed($patient->refresh(), null, true, UksPatient::LOCATION_ASRAMA, 'Kembali ke asrama');

        $bed->refresh();
        $this->assertSame('tersedia', $bed->status);
        $this->assertNull($bed->currentAssignment);
        $this->assertFalse($patient->refresh()->in_bed);
        $this->assertSame(UksPatient::LOCATION_ASRAMA, $patient->care_location);
    }
}
