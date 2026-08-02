<?php

namespace Tests\Feature\Mobile;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\Room;
use App\Models\Student;
use App\Models\StudentMahrom;
use App\Models\User;
use App\Models\WaliSantri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Verify the new Mahrom management integrates correctly with the
 * existing visit-permit / visit flows. Inactive mahrom must be rejected.
 */
class MobileMahromIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function createSchool(): \App\Models\School
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Test',
            'code' => 'WU-'.Str::random(8),
        ]);

        $schoolId = (string) Str::uuid();
        DB::table('schools')->insert([
            'id' => $schoolId,
            'work_unit_id' => $workUnitId,
            'npsn' => (string) random_int(10000000, 99999999),
            'name' => 'Sekolah Test',
            'school_status' => 'negeri',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return \App\Models\School::find($schoolId);
    }

    private function createWali(): User
    {
        return User::factory()->create([
            'is_wali' => true,
            'is_active' => true,
        ]);
    }

    private function createStudent(\App\Models\School $school): Student
    {
        $uniq = str_pad((string) random_int(1, 99999999), 10, '0', STR_PAD_LEFT);

        return Student::create([
            'school_id' => $school->id,
            'name' => 'Fulan',
            'nik' => '710201'.$uniq,
            'nisn' => $uniq,
            'gender' => 'L',
            'status' => 'active',
            'birth_place' => 'Jakarta',
            'birth_date' => now()->subYears(15),
        ]);
    }

    private function linkWali(User $wali, Student $student): void
    {
        WaliSantri::create([
            'user_id' => $wali->id,
            'student_id' => $student->id,
            'school_id' => $student->school_id,
            'role' => 'ayah',
            'is_primary' => true,
            'status' => WaliSantri::STATUS_ACTIVE,
        ]);
    }

    private function asWali(User $user): void
    {
        Sanctum::actingAs($user, [], 'sanctum');
        config(['auth.defaults.guard' => 'sanctum']);
        app()->refresh('auth', app()['auth'], 'guards');
    }

    private function createResident(Student $student): DormitoryResident
    {
        $dorm = Dormitory::create([
            'school_id' => $student->school_id,
            'name' => 'Asrama A',
            'code' => 'A-'.Str::random(4),
        ]);

        $room = Room::create([
            'dormitory_id' => $dorm->id,
            'name' => '101',
            'code' => '101',
        ]);

        $ay = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        return DormitoryResident::create([
            'student_id' => $student->id,
            'dormitory_id' => $dorm->id,
            'room_id' => $room->id,
            'academic_year_id' => $ay->id,
            'check_in_date' => now()->subMonths(2)->format('Y-m-d'),
            'is_active' => true,
        ]);
    }

    public function test_visit_permit_rejects_inactive_mahrom(): void
    {
        $wali = $this->createWali();
        $school = $this->createSchool();
        $stu = $this->createStudent($school);
        $this->linkWali($wali, $stu);
        $this->createResident($stu);
        $this->asWali($wali);

        $mahrom = StudentMahrom::create([
            'student_id' => $stu->id,
            'name' => 'Inactive Mahrom',
            'relationship' => 'ayah',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/mobile/v1/dormitory-permit', [
            'student_id' => $stu->id,
            'permit_type' => 'pulang',
            'destination' => 'Rumah',
            'purpose' => 'Libur keluarga',
            'departure_datetime' => now()->addDay()->toDateTimeString(),
            'expected_return_datetime' => now()->addDays(3)->toDateTimeString(),
            'mahrom_id' => $mahrom->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'MAHROM_INACTIVE');
    }

    public function test_visit_permit_rejects_mahrom_from_other_student(): void
    {
        $wali = $this->createWali();
        $school = $this->createSchool();
        $stu = $this->createStudent($school);
        $this->linkWali($wali, $stu);
        $this->createResident($stu);
        $this->asWali($wali);

        $other = $this->createStudent($school);
        $mahrom = StudentMahrom::create([
            'student_id' => $other->id,
            'name' => 'Other Mahrom',
            'relationship' => 'ayah',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/mobile/v1/dormitory-permit', [
            'student_id' => $stu->id,
            'permit_type' => 'pulang',
            'destination' => 'Rumah',
            'purpose' => 'Libur keluarga',
            'departure_datetime' => now()->addDay()->toDateTimeString(),
            'expected_return_datetime' => now()->addDays(3)->toDateTimeString(),
            'mahrom_id' => $mahrom->id,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'MAHROM_NOT_FOUND');
    }

    public function test_visit_permit_accepts_active_mahrom(): void
    {
        $wali = $this->createWali();
        $school = $this->createSchool();
        $stu = $this->createStudent($school);
        $this->linkWali($wali, $stu);
        $this->createResident($stu);
        $this->asWali($wali);

        $mahrom = StudentMahrom::create([
            'student_id' => $stu->id,
            'name' => 'Active Mahrom',
            'relationship' => 'ayah',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/mobile/v1/dormitory-permit', [
            'student_id' => $stu->id,
            'permit_type' => 'pulang',
            'destination' => 'Rumah',
            'purpose' => 'Libur keluarga',
            'departure_datetime' => now()->addDay()->toDateTimeString(),
            'expected_return_datetime' => now()->addDays(3)->toDateTimeString(),
            'mahrom_id' => $mahrom->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    public function test_visit_log_rejects_inactive_mahrom(): void
    {
        $wali = $this->createWali();
        $school = $this->createSchool();
        $stu = $this->createStudent($school);
        $this->linkWali($wali, $stu);
        $this->createResident($stu);
        $this->asWali($wali);

        $mahrom = StudentMahrom::create([
            'student_id' => $stu->id,
            'name' => 'Inactive Mahrom',
            'relationship' => 'ayah',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/mobile/v1/dormitory/visit', [
            'student_id' => $stu->id,
            'visitor_name' => 'Visitor',
            'visitor_phone' => '081234567890',
            'visitor_relationship' => 'mahrom',
            'purpose' => 'menjenguk',
            'expected_arrival_datetime' => now()->addHour()->toDateTimeString(),
            'mahrom_id' => $mahrom->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'MAHROM_INACTIVE');
    }

    public function test_visit_log_accepts_active_mahrom(): void
    {
        $wali = $this->createWali();
        $school = $this->createSchool();
        $stu = $this->createStudent($school);
        $this->linkWali($wali, $stu);
        $this->createResident($stu);
        $this->asWali($wali);

        $mahrom = StudentMahrom::create([
            'student_id' => $stu->id,
            'name' => 'Active Mahrom',
            'relationship' => 'ayah',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/mobile/v1/dormitory/visit', [
            'student_id' => $stu->id,
            'visitor_name' => 'Visitor',
            'visitor_phone' => '081234567890',
            'visitor_relationship' => 'mahrom',
            'purpose' => 'menjenguk',
            'expected_arrival_datetime' => now()->addHour()->toDateTimeString(),
            'mahrom_id' => $mahrom->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    }
}
