<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class StudentPhotoBulkUploadTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Foto Masal',
            'code' => 'UFM',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '99887766',
            'name' => 'Sekolah Foto Masal',
            'school_level' => 'sd',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);
    }

    public function test_halaman_upload_foto_masal_tersedia(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->get(route('user.students.photos.bulk-form', ['userId' => $this->admin->id]))
            ->assertOk()
            ->assertSee('Upload Foto Masal')
            ->assertSee('Nama file = NIS / NISN');
    }

    public function test_banyak_foto_dicocokkan_berdasarkan_nis_dan_nisn(): void
    {
        $studentByNisn = $this->makeStudent('Santri NISN', '0012345678', 'NIS-001');
        $studentByNis = $this->makeStudent('Santri NIS', '0099999999', '2024001');

        $response = $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post(route('user.students.photos.bulk-process', ['userId' => $this->admin->id]), [
                'photos' => [
                    UploadedFile::fake()->image('0012345678.jpg', 120, 120),
                    UploadedFile::fake()->image('2024001.png', 120, 120),
                ],
            ]);

        $response->assertRedirect(route('user.students.photos.bulk-form', ['userId' => $this->admin->id]));
        $response->assertSessionHas('photo_bulk_result');

        $this->assertNotNull($studentByNisn->fresh()->photo_path);
        $this->assertNotNull($studentByNis->fresh()->photo_path);
        Storage::disk('public')->assertExists($studentByNisn->fresh()->photo_path);
        Storage::disk('public')->assertExists($studentByNis->fresh()->photo_path);

        $result = session('photo_bulk_result');
        $this->assertSame(2, $result['success']);
        $this->assertSame(0, $result['failed']);
    }

    public function test_foto_tidak_cocok_dilaporkan_tanpa_mengubah_santri(): void
    {
        $student = $this->makeStudent('Santri Tidak Cocok', '0011111111', 'NIS-111');

        $response = $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post(route('user.students.photos.bulk-process', ['userId' => $this->admin->id]), [
                'photos' => [UploadedFile::fake()->image('foto-tanpa-nis.jpg', 120, 120)],
            ]);

        $response->assertRedirect();
        $result = session('photo_bulk_result');

        $this->assertSame(0, $result['success']);
        $this->assertSame(1, $result['unmatched']);
        $this->assertNull($student->fresh()->photo_path);
    }

    public function test_zip_foto_masal_diproses(): void
    {
        $student = $this->makeStudent('Santri ZIP', '0077777777', 'NIS-777');

        $zipPath = tempnam(sys_get_temp_dir(), 'foto_zip_');
        $fakeImage = UploadedFile::fake()->image('x.jpg', 120, 120);
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        $zip->addFromString('0077777777.jpg', file_get_contents($fakeImage->getPathname()));
        $zip->close();

        $response = $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post(route('user.students.photos.bulk-process', ['userId' => $this->admin->id]), [
                'archive' => new UploadedFile($zipPath, 'foto-santri.zip', 'application/zip', null, true),
            ]);

        $response->assertRedirect();
        $result = session('photo_bulk_result');

        $this->assertSame(1, $result['success']);
        $this->assertNotNull($student->fresh()->photo_path);
        Storage::disk('public')->assertExists($student->fresh()->photo_path);

        @unlink($zipPath);
    }

    public function test_upload_foto_kelas_hanya_mengubah_santri_di_rombel_itu(): void
    {
        $inClass = $this->makeStudent('Santri Di Kelas', '0055555555', 'NIS-555');
        $otherClass = $this->makeStudent('Santri Kelas Lain', '0066666666', 'NIS-666');

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
            'student_id' => $inClass->id,
            'study_group_id' => $group->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['sa_school_id' => $this->school->id])
            ->post(route('user.students.photos.bulk-process', ['userId' => $this->admin->id]), [
                'study_group_id' => $group->id,
                'photos' => [
                    UploadedFile::fake()->image('0055555555.jpg', 120, 120),
                    UploadedFile::fake()->image('0066666666.jpg', 120, 120),
                ],
            ]);

        $response->assertRedirect();
        $result = session('photo_bulk_result');

        $this->assertSame(1, $result['success']);
        $this->assertSame(1, $result['unmatched']);
        $this->assertNotNull($inClass->fresh()->photo_path);
        $this->assertNull($otherClass->fresh()->photo_path);
    }

    private function makeStudent(string $name, string $nisn, string $nis): Student
    {
        return Student::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'nisn' => $nisn,
            'nis' => $nis,
            'gender' => 'L',
            'status' => 'active',
        ]);
    }
}
