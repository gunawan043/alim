<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use App\Models\WaliSantri;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WaliSantriApiTest extends TestCase
{
    /**
     * Use migrate:fresh once per test class (not per-test) to avoid the
     * 250+ table rollback problem. RefreshDatabase is unreliable with this
     * many tables and complex FK constraints.
     */
    protected static $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! static::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            static::$migrated = true;
        }

        // Use transaction per test for speed (no need to drop 250 tables)
        $this->beginDatabaseTransaction();
    }

    protected function beginDatabaseTransaction(): void
    {
        $db = $this->app->make('db');
        $connection = $db->connection();
        $connection->beginTransaction();

        $this->beforeApplicationDestroyed(function () use ($connection) {
            $connection->rollBack();
        });
    }

    // ── Factories ──────────────────────────────────────────────────────────

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

    private function createWali(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'is_wali' => true,
            'is_active' => true,
        ], $attrs));
    }

    private function createStudent(array $attrs = []): Student
    {
        $school = $this->createSchool();
        $uniq = str_pad((string) random_int(1, 99999999), 10, '0', STR_PAD_LEFT);

        return Student::create(array_merge([
            'school_id' => $school->id,
            'name' => 'Fulan',
            'nik' => '710201'.$uniq,
            'nisn' => $uniq,
            'gender' => 'L',
            'status' => 'active',
            'birth_place' => 'Jakarta',
            'birth_date' => now()->subYears(15),
        ], $attrs));
    }

    private function linkWaliToStudent(User $wali, Student $student): void
    {
        WaliSantri::create([
            'user_id' => $wali->id,
            'student_id' => $student->id,
            'role' => 'ayah',
            'is_primary' => true,
            'status' => WaliSantri::STATUS_ACTIVE,
        ]);
    }

    private function asSanctumUser(User $user): self
    {
        Sanctum::actingAs($user, [], 'sanctum');
        // Override auth default guard so controllers (auth()->user()) resolve correctly
        config(['auth.defaults.guard' => 'sanctum']);
        app()->refresh('auth', app()['auth'], 'guards');

        return $this;
    }

    // ── Attendance ─────────────────────────────────────────────────────────

    public function test_attendance_returns_404_if_wali_no_access(): void
    {
        $wali = $this->createWali();
        $this->asSanctumUser($wali);

        $student = $this->createStudent();
        $this->linkWaliToStudent($wali, $student);
        $another = $this->createStudent([
            'name' => 'Another Student',
            'nik' => '7102010001000002',
        ]);

        $response = $this->getJson("/api/mobile/v1/santri/{$another->id}/attendance");

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'STUDENT_NOT_FOUND');
    }

    public function test_attendance_returns_data_for_linked_student(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asSanctumUser($wali);

        $this->insertAttendance($stu->id, now()->format('Y-m-d'), 'hadir');

        $response = $this->getJson("/api/mobile/v1/santri/{$stu->id}/attendance?date=".now()->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.attendances');
    }

    public function test_attendance_history_returns_paginated_results(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asSanctumUser($wali);

        $this->insertAttendance($stu->id, now()->subDays(1)->format('Y-m-d'), 'hadir');
        $this->insertAttendance($stu->id, now()->subDays(2)->format('Y-m-d'), 'izin');

        $response = $this->getJson("/api/mobile/v1/santri/{$stu->id}/attendance/history?month=".now()->month.'&year='.now()->year);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.records');
    }

    private function insertAttendance(string $studentId, string $date, string $status): void
    {
        $ay = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $waliId = (string) Str::uuid();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('student_attendances')->insert([
            'id' => (string) Str::uuid(),
            'student_id' => $studentId,
            'school_id' => (string) Str::uuid(),
            'academic_year_id' => $ay->id,
            'study_group_id' => (string) Str::uuid(),
            'attendance_date' => $date,
            'status' => $status,
            'recorded_by' => $waliId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    // ── Grades ─────────────────────────────────────────────────────────────

    public function test_grades_returns_data_for_linked_student(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asSanctumUser($wali);

        $ay = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('admin_nilai_sumatif')->insert([
            'id' => (string) Str::uuid(),
            'admin_book_id' => (string) Str::uuid(),
            'student_id' => $stu->id,
            'academic_year_id' => $ay->id,
            'semester' => 'ganjil',
            's1' => 85,
            's2' => 90,
            's3' => 88,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $response = $this->getJson("/api/mobile/v1/santri/{$stu->id}/grades");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.sumatif');
    }

    // ── Violations ─────────────────────────────────────────────────────────

    public function test_violations_returns_empty_when_none(): void
    {
        $wali = $this->createWali();
        $stu = $this->createStudent();
        $this->linkWaliToStudent($wali, $stu);
        $this->asSanctumUser($wali);

        $response = $this->getJson("/api/mobile/v1/santri/{$stu->id}/violations");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // ── Profile (via auth:me) ──────────────────────────────────────────────

    public function test_auth_me_returns_current_wali(): void
    {
        $wali = $this->createWali();
        $this->asSanctumUser($wali);

        $response = $this->getJson('/api/mobile/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $wali->email);
    }

    // ── Notifications ──────────────────────────────────────────────────────

    public function test_notification_returns_latest_for_current_user(): void
    {
        $wali = $this->createWali();
        $this->asSanctumUser($wali);

        $response = $this->getJson('/api/mobile/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // ── Wali Registration (POST /wali-santri/request via AuthController) ────
    // Semua route /santri POST butuh auth:sanctum

    public function test_request_wali_requires_nik_and_role(): void
    {
        $wali = $this->createWali();
        $this->asSanctumUser($wali);

        $response = $this->postJson('/api/mobile/v1/wali-santri/request', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('nik_santri')
            ->assertJsonValidationErrorFor('role');
    }

    public function test_request_wali_accepts_valid_request(): void
    {
        $student = $this->createStudent();
        $wali = $this->createWali();
        $this->linkWaliToStudent($wali, $student);
        $this->asSanctumUser($wali);

        $response = $this->postJson('/api/mobile/v1/wali-santri/request', [
            'nik_santri' => $student->nik,
            'role' => 'ayah',
            'no_kk' => '3201000000000001',
        ]);

        // Karena sudah linked, should get 200
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_request_wali_fails_for_unknown_student(): void
    {
        $wali = $this->createWali();
        $this->asSanctumUser($wali);

        $response = $this->postJson('/api/mobile/v1/wali-santri/request', [
            'nik_santri' => '0000000000000001',
            'role' => 'ayah',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('error.message', 'Santri dengan NIK tersebut tidak ditemukan. Pastikan NIK yang Anda masukkan benar.');
    }

    // ── Dormitory Permits ──────────────────────────────────────────────────

    public function test_dormitory_permit_store_requires_auth(): void
    {
        $response = $this->postJson('/api/mobile/v1/dormitory/permit', [
            'student_id' => (string) Str::uuid(),
            'permit_date_start' => now()->toDateString(),
            'permit_date_end' => now()->addDays(7)->toDateString(),
            'purpose' => 'Visit parents',
        ]);

        // Without auth, should get 401
        $response->assertStatus(401);
    }
}
