<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryStaffAssignment;
use App\Models\Role;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DormitoryPermitScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_button_goes_to_scanner_and_scan_status_flow_is_reversible(): void
    {
        $dormitory = Dormitory::factory()->create(['is_active' => true]);
        $dormitory->school?->update(['boarding_type' => 'boarding']);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $wing = DormitoryWing::create([
            'dormitory_id' => $dormitory->id,
            'code' => 'W-01',
            'name' => 'Sayap 01',
            'gender' => 'putra',
        ]);
        $room = DormitoryRoom::create([
            'dormitory_id' => $dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'K-01',
            'name' => 'Kamar 01',
            'gender' => 'putra',
            'capacity' => 10,
            'is_active' => true,
        ]);
        $student = Student::create([
            'school_id' => $dormitory->school_id,
            'nisn' => '9999200001',
            'name' => 'Santri Scan Uji',
            'gender' => 'L',
            'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Asrama', 'guard_name' => 'web']));
        DormitoryStaffAssignment::create([
            'user_id' => $admin->id,
            'dormitory_id' => $dormitory->id,
            'start_date' => now()->subDay(),
            'status' => 'active',
        ]);

        $permit = DormitoryPermit::create([
            'student_id' => $student->id,
            'dormitory_id' => $dormitory->id,
            'room_id' => $room->id,
            'academic_year_id' => $year->id,
            'permit_type' => 'pulang',
            'destination' => 'Rumah',
            'purpose' => 'Pulang bulanan',
            'departure_datetime' => now()->addHour(),
            'expected_return_datetime' => now()->addDay(),
            'status' => DormitoryPermit::STATUS_APPROVED,
            'created_by' => $admin->id,
        ]);
        $token = $permit->getOrCreateScanToken();

        $this->actingAs($admin)->withSession(['sa_school_id' => $dormitory->school_id]);

        $this->get(route('user.asrama.permits.index', [
            'userId' => $admin->id,
            'asramaUuid' => $dormitory->id,
        ]))->assertOk()->assertSee(route('user.asrama.permits.scan', [
            'userId' => $admin->id,
            'asramaUuid' => $dormitory->id,
        ]));

        $this->postJson(route('user.asrama.permits.scan.store', [
            'userId' => $admin->id,
            'asramaUuid' => $dormitory->id,
        ]), [
            'scan_url' => json_encode($permit->qrPayload()),
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(DormitoryPermit::STATUS_PICKED_UP, $permit->fresh()->status);
        $this->assertNotNull($permit->fresh()->pickup_scanned_at);

        $this->postJson(route('user.asrama.permits.scan.store', [
            'userId' => $admin->id,
            'asramaUuid' => $dormitory->id,
        ]), [
            'scan_url' => json_encode($permit->qrPayload()),
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(DormitoryPermit::STATUS_RETURNED, $permit->fresh()->status);
        $this->assertNotNull($permit->fresh()->return_scanned_at);
    }

    public function test_permit_card_is_returned_as_pdf_without_preview_view(): void
    {
        $dormitory = Dormitory::factory()->create(['is_active' => true]);
        $dormitory->school?->update(['boarding_type' => 'boarding']);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $wing = DormitoryWing::create([
            'dormitory_id' => $dormitory->id,
            'code' => 'W-02',
            'name' => 'Sayap 02',
            'gender' => 'putra',
        ]);
        $room = DormitoryRoom::create([
            'dormitory_id' => $dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'K-02',
            'name' => 'Kamar 02',
            'gender' => 'putra',
            'capacity' => 10,
            'is_active' => true,
        ]);
        $student = Student::create([
            'school_id' => $dormitory->school_id,
            'nisn' => '9999200002',
            'name' => 'Santri Kartu Uji',
            'gender' => 'L',
            'status' => 'active',
        ]);
        $admin = User::factory()->create(['is_system_admin' => true, 'is_active' => true]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Asrama', 'guard_name' => 'web']));
        DormitoryStaffAssignment::create([
            'user_id' => $admin->id,
            'dormitory_id' => $dormitory->id,
            'start_date' => now()->subDay(),
            'status' => 'active',
        ]);
        $permit = DormitoryPermit::create([
            'student_id' => $student->id,
            'dormitory_id' => $dormitory->id,
            'room_id' => $room->id,
            'academic_year_id' => $year->id,
            'permit_type' => 'pulang',
            'destination' => 'Rumah',
            'purpose' => 'Pulang bulanan',
            'departure_datetime' => now()->addHour(),
            'expected_return_datetime' => now()->addDay(),
            'status' => DormitoryPermit::STATUS_APPROVED,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->withSession(['sa_school_id' => $dormitory->school_id]);

        $this->get(route('user.asrama.permits.card', [
            'userId' => $admin->id,
            'asramaUuid' => $dormitory->id,
            'permitUuid' => $permit->id,
        ]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
    public function test_visit_card_scan_token_moves_visit_to_arrived_then_checked_out(): void
    {
        $dormitory = Dormitory::factory()->create(['is_active' => true]);
        $dormitory->school?->update(['boarding_type' => 'boarding']);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $wing = DormitoryWing::create([
            'dormitory_id' => $dormitory->id,
            'code' => 'W-03',
            'name' => 'Sayap 03',
            'gender' => 'putra',
        ]);
        $room = DormitoryRoom::create([
            'dormitory_id' => $dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'K-03',
            'name' => 'Kamar 03',
            'gender' => 'putra',
            'capacity' => 10,
            'is_active' => true,
        ]);
        $student = Student::create([
            'school_id' => $dormitory->school_id,
            'nisn' => '9999200003',
            'name' => 'Santri Kunjungan Uji',
            'gender' => 'L',
            'status' => 'active',
        ]);
        $admin = User::factory()->create(['is_system_admin' => true, 'is_active' => true]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Asrama', 'guard_name' => 'web']));
        DormitoryStaffAssignment::create([
            'user_id' => $admin->id,
            'dormitory_id' => $dormitory->id,
            'start_date' => now()->subDay(),
            'status' => 'active',
        ]);

        $visit = \App\Models\DormitoryVisitLog::create([
            'dormitory_id' => $dormitory->id,
            'room_id' => $room->id,
            'student_id' => $student->id,
            'visitor_name' => 'Wali Uji',
            'visitor_relationship' => 'wali',
            'purpose' => 'menjenguk',
            'expected_arrival_datetime' => now()->addHour(),
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);
        $token = base64_encode($visit->id.'::'.hash_hmac('sha256', (string) $visit->id, (string) config('app.key')));

        $this->actingAs($admin)->withSession(['sa_school_id' => $dormitory->school_id]);

        $this->get(route('user.asrama.visits.card', [
            'userId' => $admin->id,
            'asramaUuid' => $dormitory->id,
            'visitUuid' => $visit->id,
        ]))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->postJson(route('user.asrama.visits.scan.store', [
            'userId' => $admin->id,
            'asramaUuid' => $dormitory->id,
        ]), ['scan_url' => $token])->assertOk()->assertJsonPath('success', true);

        $this->assertSame('arrived', $visit->fresh()->status);

        $this->postJson(route('user.asrama.visits.scan.store', [
            'userId' => $admin->id,
            'asramaUuid' => $dormitory->id,
        ]), ['scan_url' => $token])->assertOk()->assertJsonPath('success', true);

        $this->assertSame('checked_out', $visit->fresh()->status);
    }

}
