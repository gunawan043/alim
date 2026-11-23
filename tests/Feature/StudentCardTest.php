<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentCardSetting;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentCardTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Kartu Pelajar',
            'code' => 'UKP',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '77665544',
            'name' => 'Sekolah Kartu Pelajar',
            'school_level' => 'sd',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'name' => 'Santri Kartu',
            'nis' => 'NIS-001',
            'nisn' => '0011223344',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id,
            'level' => 3,
            'name' => 'Kelas 3',
            'code' => '3',
        ]);

        $group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'name' => '3A',
            'code' => '3A',
            'capacity' => 30,
            'is_active' => true,
        ]);

        StudentClassHistory::create([
            'student_id' => $this->student->id,
            'study_group_id' => $group->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
    }

    public function test_halaman_kartu_pelajar_dapat_dibuka(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get(route('user.students.cards.index', ['userId' => $this->admin->id]))
            ->assertOk()
            ->assertSee('Preview Kartu')
            ->assertSee('Santri Kartu');
    }

    public function test_background_dan_ttd_dapat_disimpan(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post(route('user.students.cards.settings', ['userId' => $this->admin->id]), [
                'selected_student_id' => $this->student->id,
                'card_width_mm' => 85.6,
                'card_height_mm' => 53.98,
                'signature_name' => 'Kepala Sekolah',
                'signature_title' => 'Kepala Satuan Pendidikan',
                'validity_text' => 'Berlaku s.d. Juni 2027',
                'front_background' => UploadedFile::fake()->image('depan.png', 600, 380),
                'back_background' => UploadedFile::fake()->image('belakang.png', 600, 380),
                'signature' => UploadedFile::fake()->image('ttd.png', 300, 150),
            ]);

        $response->assertRedirect();
        $setting = StudentCardSetting::where('school_id', $this->school->id)->firstOrFail();

        $this->assertNotNull($setting->front_background_path);
        $this->assertNotNull($setting->back_background_path);
        $this->assertNotNull($setting->signature_path);
        Storage::disk('public')->assertExists($setting->front_background_path);
        Storage::disk('public')->assertExists($setting->back_background_path);
        Storage::disk('public')->assertExists($setting->signature_path);
    }

    public function test_cetak_pdf_kartu_menghasilkan_depan_dan_belakang(): void
    {
        StudentCardSetting::create([
            'school_id' => $this->school->id,
            'signature_name' => 'Kepala Sekolah',
            'signature_title' => 'Kepala Satuan Pendidikan',
            'validity_text' => 'Berlaku s.d. Juni 2027',
            'layout' => StudentCardSetting::defaultLayout(),
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get(route('user.students.cards.print', [
                'userId' => $this->admin->id,
                'student_id' => $this->student->id,
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        $pdf = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page\b/', $pdf));
        $this->assertMatchesRegularExpression('/MediaBox\s*\[?[^\]]*595\.?[0-9]*\s+841\.?[0-9]*\]?/', $pdf);
    }
}
