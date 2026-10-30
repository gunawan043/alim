<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\HumasComplaint;
use App\Models\HumasSatisfactionSurvey;
use App\Models\RaportRegistration;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentAttendance;
use App\Models\StudyGroup;
use App\Models\TahfidzCertificate;
use App\Models\User;
use App\Models\WaliSantri;
use App\Models\WorkUnit;
use App\Services\Arsip\RaporPdfService;
use App\Services\Pdf\DocumentPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WaliSurveyComplaintTest extends TestCase
{
    use RefreshDatabase;

    private function school(string $name): School
    {
        $workUnit = WorkUnit::factory()->create(['name' => 'Unit '.$name, 'is_active' => true]);

        return School::factory()->create([
            'name' => $name,
            'work_unit_id' => $workUnit->id,
            'is_active' => true,
        ]);
    }

    private function student(School $school, string $name): Student
    {
        return Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'name' => $name,
            'nisn' => $this->digits(10),
            'nik' => $this->digits(16),
            'no_kk' => $this->digits(16),
            'gender' => 'L',
            'birth_place' => 'Mataram',
            'birth_date' => '2012-01-01',
            'mother_name' => 'Siti Aisah',
            'status' => 'active',
        ]);
    }

    private function wali(string $name): User
    {
        return User::factory()->create(['name' => $name, 'is_active' => true, 'is_wali' => true]);
    }

    private function link(User $wali, Student $student, School $school): WaliSantri
    {
        return WaliSantri::create([
            'user_id' => $wali->id,
            'student_id' => $student->id,
            'school_id' => $school->id,
            'role' => 'wali',
            'is_primary' => true,
            'status' => 'active',
            'verified_at' => now(),
        ]);
    }

    private function humas(): User
    {
        $user = User::factory()->create(['name' => 'Humas', 'is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'Humas Personalia', 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }

    private function survey(School $school, string $title): HumasSatisfactionSurvey
    {
        return HumasSatisfactionSurvey::create([
            'school_id' => $school->id,
            'title' => $title,
            'description' => 'Deskripsi '.$title,
            'audience' => 'wali',
            'status' => 'published',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'created_by' => $this->humas()->id,
        ]);
    }

    private function digits(int $length): string
    {
        $number = '';
        for ($i = 0; $i < $length; $i++) {
            $number .= (string) random_int(0, 9);
        }

        return $number;
    }

    public function test_surveys_are_scoped_to_wali_schools_and_global(): void
    {
        $schoolA = $this->school('Sekolah A');
        $schoolB = $this->school('Sekolah B');
        $wali = $this->wali('Wali A');

        $this->link($wali, $this->student($schoolA, 'Santri A'), $schoolA);

        $surveyA = $this->survey($schoolA, 'Survei Sekolah A');
        $surveyB = $this->survey($schoolB, 'Survei Sekolah B');
        $global = $this->survey($schoolA, 'Survei Global');
        $global->update(['school_id' => null]);

        Sanctum::actingAs($wali);

        $response = $this->getJson('/api/mobile/v1/'.$wali->id.'/wali/surveys');
        $response->assertOk();

        $titles = collect($response->json('data.surveys'))->pluck('title');
        $this->assertTrue($titles->contains('Survei Sekolah A'));
        $this->assertTrue($titles->contains('Survei Global'));
        $this->assertFalse($titles->contains('Survei Sekolah B'));

        $this->postJson("/api/mobile/v1/{$wali->id}/wali/surveys/{$surveyA->id}", [
            'rating' => 5,
            'feedback' => 'Pelayanan baik.',
        ])->assertOk();

        $this->assertDatabaseHas('humas_satisfaction_responses', [
            'humas_satisfaction_survey_id' => $surveyA->id,
            'user_id' => $wali->id,
            'rating' => 5,
        ]);

        $this->postJson("/api/mobile/v1/{$wali->id}/wali/surveys/{$surveyB->id}", [
            'rating' => 1,
        ])->assertNotFound();
    }

    public function test_wali_can_submit_and_view_own_complaint(): void
    {
        $school = $this->school('Sekolah A');
        $wali = $this->wali('Wali A');
        $student = $this->student($school, 'Santri A');
        $this->link($wali, $student, $school);

        $humas = $this->humas();

        Sanctum::actingAs($wali);

        $response = $this->postJson('/api/mobile/v1/'.$wali->id.'/wali/complaints', [
            'student_id' => $student->id,
            'category' => 'layanan',
            'subject' => 'Pelayanan kantin',
            'message' => 'Mohon kebersihan kantin ditingkatkan.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subject', 'Pelayanan kantin')
            ->assertJsonPath('data.status', 'open');

        $complaint = HumasComplaint::firstOrFail();
        $this->assertSame($school->id, $complaint->school_id);
        $this->assertSame($wali->id, $complaint->submitted_by);

        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $humas->id,
            'reference_id' => $complaint->id,
        ]);

        $this->getJson('/api/mobile/v1/'.$wali->id.'/wali/complaints')
            ->assertOk()
            ->assertJsonPath('data.complaints.0.id', $complaint->id);

        $this->getJson("/api/mobile/v1/{$wali->id}/wali/complaints/{$complaint->id}")
            ->assertOk()
            ->assertJsonPath('data.message', 'Mohon kebersihan kantin ditingkatkan.');
    }

    public function test_wali_cannot_view_other_wali_complaint(): void
    {
        $school = $this->school('Sekolah A');
        $waliA = $this->wali('Wali A');
        $waliB = $this->wali('Wali B');
        $student = $this->student($school, 'Santri A');
        $this->link($waliA, $student, $school);

        $complaint = HumasComplaint::create([
            'school_id' => $school->id,
            'submitted_by' => $waliA->id,
            'category' => 'umum',
            'subject' => 'Aduan A',
            'message' => 'Pesan A',
            'status' => 'open',
        ]);

        Sanctum::actingAs($waliB);

        $this->getJson("/api/mobile/v1/{$waliB->id}/wali/complaints/{$complaint->id}")
            ->assertNotFound();
    }

    public function test_wali_report_returns_attendance_and_document_sections(): void
    {
        $school = $this->school('Sekolah Report');
        $wali = $this->wali('Wali Report');
        $student = $this->student($school, 'Santri Report');
        $this->link($wali, $student, $school);

        $academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'is_active' => true,
        ]);

        $gradeLevel = GradeLevel::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'is_active' => true,
        ]);

        $studyGroup = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => '7A',
            'is_active' => true,
        ]);

        foreach (['hadir', 'hadir', 'izin', 'alpa'] as $index => $status) {
            StudentAttendance::create([
                'id' => (string) Str::uuid(),
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'study_group_id' => $studyGroup->id,
                'student_id' => $student->id,
                'recorded_by' => $wali->id,
                'attendance_date' => now()->subDays($index)->toDateString(),
                'status' => $status,
            ]);
        }

        Sanctum::actingAs($wali);

        $response = $this->getJson('/api/mobile/v1/'.$wali->id.'/wali/reports');

        $response->assertOk()
            ->assertJsonPath('data.reports.0.student.name', 'Santri Report')
            ->assertJsonPath('data.reports.0.attendance.hadir', 2)
            ->assertJsonPath('data.reports.0.attendance.izin', 1)
            ->assertJsonPath('data.reports.0.attendance.alpa', 1)
            ->assertJsonPath('data.reports.0.attendance.total', 4)
            ->assertJsonStructure([
                'data' => ['reports' => ['*' => [
                    'student', 'period', 'attendance', 'permits', 'visits',
                    'tahfidz', 'violations', 'achievements', 'rewards', 'raport',
                ]]],
            ]);
    }

    public function test_wali_can_download_raport_pdf_for_published_registration(): void
    {
        $school = $this->school('Sekolah Rapor');
        $wali = $this->wali('Wali Rapor');
        $student = $this->student($school, 'Santri Rapor');
        $this->link($wali, $student, $school);

        $academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'is_active' => true,
        ]);
        $gradeLevel = GradeLevel::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'is_active' => true,
        ]);
        $studyGroup = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => '7A',
            'is_active' => true,
        ]);
        RaportRegistration::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'study_group_id' => $studyGroup->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
            'status' => 'published',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->mock(RaporPdfService::class, function ($mock) {
            $mock->shouldReceive('renderSingle')->once()->andReturn('%PDF-1.4 test');
        });

        Sanctum::actingAs($wali);

        $this->get("/api/mobile/v1/{$wali->id}/wali/raport/{$student->id}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_wali_can_download_achievement_certificate_only_for_own_child(): void
    {
        Storage::fake('local');

        $school = $this->school('Sekolah Piagam');
        $wali = $this->wali('Wali Piagam');
        $otherWali = $this->wali('Wali Lain');
        $student = $this->student($school, 'Santri Piagam');
        $this->link($wali, $student, $school);

        $academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'is_active' => true,
        ]);

        Storage::disk('local')->put('student-achievements/certificate.pdf', 'PDF-CONTENT');

        $achievement = StudentAchievement::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'achievement_type' => 'akademik',
            'event_name' => 'Olimpiade Sains',
            'level' => 'kabupaten_kota',
            'position' => 'juara_1',
            'event_date' => now()->toDateString(),
            'certificate_path' => 'student-achievements/certificate.pdf',
            'is_verified' => true,
            'created_by' => $wali->id,
        ]);

        Sanctum::actingAs($wali);

        $this->get("/api/mobile/v1/{$wali->id}/wali/achievements/{$achievement->id}/certificate")
            ->assertOk();

        Sanctum::actingAs($otherWali);

        $this->get("/api/mobile/v1/{$otherWali->id}/wali/achievements/{$achievement->id}/certificate")
            ->assertNotFound();
    }

    public function test_wali_can_list_and_download_tahfidz_certificate(): void
    {
        $school = $this->school('Sekolah Syahadah');
        $wali = $this->wali('Wali Syahadah');
        $student = $this->student($school, 'Santri Syahadah');
        $this->link($wali, $student, $school);

        $academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'is_active' => true,
        ]);

        $certificate = TahfidzCertificate::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'school_id' => $school->id,
            'academic_year_id' => $academicYear->id,
            'certificate_type' => '5_juz',
            'certificate_number' => 'SYH-001',
            'issued_date' => now()->toDateString(),
            'total_juz_completed' => 5,
            'nilai_akhir' => 90,
            'predikat' => 'mumtaz',
        ]);

        Sanctum::actingAs($wali);

        $this->getJson("/api/mobile/v1/{$wali->id}/wali/tahfidz/certificates/{$student->id}")
            ->assertOk()
            ->assertJsonPath('data.certificates.0.id', $certificate->id);

        $this->mock(DocumentPdfService::class, function ($mock) {
            $mock->shouldReceive('fromView')->once()->andReturn(response('PDF', 200, ['Content-Type' => 'application/pdf']));
        });

        $this->get("/api/mobile/v1/{$wali->id}/wali/tahfidz/certificates/{$certificate->id}/pdf")
            ->assertOk();
    }

    public function test_wali_api_rejects_mismatched_user_id_path(): void
    {
        $wali = $this->wali('Wali Scope');
        $other = $this->wali('Wali Lain');

        Sanctum::actingAs($wali);

        $this->getJson("/api/mobile/v1/{$other->id}/wali/surveys")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'USER_SCOPE_MISMATCH');
    }
}
