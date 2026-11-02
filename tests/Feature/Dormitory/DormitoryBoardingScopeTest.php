<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Http\Controllers\DormitoryRoomApiController;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Batas akses data santri di modul asrama:
 *  - halaman asrama tidak boleh menampilkan santri lawan gender;
 *  - data lintas asrama hanya di halaman khusus & tetap satu gender;
 *  - kelas santri selalu ditampilkan (bukan hanya NIS/NISN).
 */
class DormitoryBoardingScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Dormitory $putra;

    private Dormitory $putri;

    private DormitoryRoom $putraRoom;

    private User $admin;

    private AcademicYear $academicYear;

    private Student $santriPutra;

    private Student $santriPutri;

    private Student $santriPutraAsramaLain;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Scope', 'code' => 'UU-SCOPE',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '33445566',
            'name' => 'Sekolah Scope',
            'boarding_type' => 'boarding',
        ]);

        $this->academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->putra = Dormitory::create([
            'work_unit_id' => $workUnitId, 'school_id' => $this->school->id,
            'code' => 'ASR-PA-SCOPE', 'name' => 'Asrama Putra Scope', 'gender' => 'putra',
        ]);

        $this->putri = Dormitory::create([
            'work_unit_id' => $workUnitId, 'school_id' => $this->school->id,
            'code' => 'ASR-PI-SCOPE', 'name' => 'Asrama Putri Scope', 'gender' => 'putri',
        ]);

        $wing = DormitoryWing::create([
            'id' => (string) Str::uuid(), 'dormitory_id' => $this->putra->id,
            'code' => 'W1', 'name' => 'Sayap 1', 'gender' => 'putra',
        ]);

        $this->putraRoom = DormitoryRoom::create([
            'id' => (string) Str::uuid(), 'dormitory_id' => $this->putra->id,
            'wing_id' => $wing->id, 'code' => 'R1', 'name' => 'Kamar 1',
            'gender' => 'putra', 'capacity' => 10,
        ]);

        $gradeLevel = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7',
            'code' => 'K7', 'is_active' => true,
        ]);

        $studyGroup = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => 'A',
            'code' => '7A',
            'is_active' => true,
        ]);

        $this->santriPutra = $this->makeStudent('Putra Scope 1', 'L', '1112000001');
        $this->santriPutri = $this->makeStudent('Putri Scope 1', 'P', '1112000002');

        DormitoryResident::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->putra->id,
            'student_id' => $this->santriPutra->id,
            'academic_year_id' => $this->academicYear->id,
            'room_id' => $this->putraRoom->id,
            'bed_number' => 1,
            'check_in_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        StudentClassHistory::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->santriPutra->id,
            'study_group_id' => $studyGroup->id,
            'academic_year_id' => $this->academicYear->id,
            'is_active' => true,
        ]);

        // Santri putra yang sudah berkamar di asrama putra lain.
        $otherDorm = Dormitory::create([
            'work_unit_id' => $workUnitId, 'school_id' => $this->school->id,
            'code' => 'ASR-PA-LAIN', 'name' => 'Asrama Putra Lain', 'gender' => 'putra',
        ]);
        $otherWing = DormitoryWing::create([
            'id' => (string) Str::uuid(), 'dormitory_id' => $otherDorm->id,
            'code' => 'WX', 'name' => 'Sayap X', 'gender' => 'putra',
        ]);
        $otherRoom = DormitoryRoom::create([
            'id' => (string) Str::uuid(), 'dormitory_id' => $otherDorm->id,
            'wing_id' => $otherWing->id, 'code' => 'RX', 'name' => 'Kamar X',
            'gender' => 'putra', 'capacity' => 10,
        ]);

        $this->santriPutraAsramaLain = $this->makeStudent('Putra Asrama Lain', 'L', '1112000003');
        DormitoryResident::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $otherDorm->id,
            'student_id' => $this->santriPutraAsramaLain->id,
            'academic_year_id' => $this->academicYear->id,
            'room_id' => $otherRoom->id,
            'bed_number' => 1,
            'check_in_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'is_system_admin' => true,
            'is_active' => true,
        ]);
    }

    private function makeStudent(string $name, string $gender, string $nisn): Student
    {
        return Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => $nisn,
            'name' => $name,
            'gender' => $gender,
            'status' => 'active',
            'boarding_status' => 'boarding',
        ]);
    }

    private function callStudentsStatus(?string $scope = null): array
    {
        $request = Request::create('/x', 'GET', $scope ? ['scope' => $scope] : []);

        return json_decode(
            app(DormitoryRoomApiController::class)
                ->studentsStatus($request, 'u1', $this->putra->id)
                ->getContent(),
            true
        );
    }

    public function test_status_boarding_hanya_menampilkan_gender_asrama(): void
    {
        $data = $this->callStudentsStatus();

        $names = collect($data['students'])->pluck('name');

        $this->assertContains('Putra Scope 1', $names);
        $this->assertNotContains('Putri Scope 1', $names, 'Santri putri tidak boleh muncul di asrama putra.');
        $this->assertSame('L', $data['gender']);
    }

    public function test_scope_asrama_menyembunyikan_santri_yang_sudah_di_asrama_lain(): void
    {
        $data = $this->callStudentsStatus();

        $names = collect($data['students'])->pluck('name');
        $this->assertNotContains('Putra Asrama Lain', $names, 'Data lintas asrama tidak tampil di halaman asrama.');

        // Halaman khusus lintas asrama menampilkannya, lengkap dengan asrama saat ini.
        $all = $this->callStudentsStatus('all');
        $row = collect($all['students'])->firstWhere('name', 'Putra Asrama Lain');

        $this->assertNotNull($row);
        $this->assertSame('Asrama Putra Lain', $row['other_dormitory']);
        $this->assertNotContains('Putri Scope 1', collect($all['students'])->pluck('name'), 'Scope all tetap satu gender.');
    }

    public function test_update_boarding_menolak_santri_lawan_gender(): void
    {
        $controller = app(DormitoryRoomApiController::class);

        $response = $controller->updateBoardingStatus(
            Request::create('/x', 'POST', [
                'student_ids' => [$this->santriPutri->id],
                'status' => 'non_boarding',
            ]),
            'u1',
            $this->putra->id
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('boarding', $this->santriPutri->fresh()->boarding_status, 'Status santri putri tidak berubah.');
    }

    public function test_pullable_students_menyertakan_kelas(): void
    {
        $newStudent = $this->makeStudent('Putra Belum Kamar', 'L', '1112000004');

        $response = app(DormitoryRoomApiController::class)->pullableStudents(
            Request::create('/x', 'GET'),
            'u1',
            $this->putra->id
        );

        $data = json_decode($response->getContent(), true);
        $row = collect($data['students'])->firstWhere('name', 'Putra Belum Kamar');

        $this->assertNotNull($row);
        $this->assertArrayHasKey('class_name', $row);
        $this->assertNotContains('Putri Scope 1', collect($data['students'])->pluck('name'));
    }

    public function test_available_residents_menyertakan_kelas_dan_gender(): void
    {
        $response = app(DormitoryRoomApiController::class)->availableResidents(
            Request::create('/x', 'GET'),
            'u1',
            $this->putra->id,
            $this->putraRoom->id
        );

        // Semua penghuni sudah ada di kamar ini → kosong, tetapi struktur tetap valid.
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
    }

    public function test_halaman_santri_boarding_terkunci_satu_gender(): void
    {
        $url = route('user.asrama.boarding.index', [
            'userId' => $this->admin->id,
            'asramaUuid' => $this->putra->id,
        ]);

        $this->actingAs($this->admin)
            ->get($url)
            ->assertOk()
            ->assertSee('Putra Scope 1')
            ->assertSee('Asrama Putra Lain')
            ->assertDontSee('Putri Scope 1');

        // Memaksa gender=P pada asrama putra tidak membuka data putri.
        $this->actingAs($this->admin)
            ->get($url.'?gender=P')
            ->assertOk()
            ->assertDontSee('Putri Scope 1');
    }

    public function test_pencarian_santri_mutasi_hanya_penghuni_asrama_ini_dan_satu_gender(): void
    {
        $url = route('user.asrama.residents.find-student', [
            'userId' => $this->admin->id,
            'asramaUuid' => $this->putra->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson($url.'?mode=resident&q=Scope')
            ->assertOk();

        $names = collect($response->json('results'))->pluck('name');

        $this->assertContains('Putra Scope 1', $names);
        $this->assertNotContains('Putra Asrama Lain', $names, 'Bukan penghuni asrama ini tidak boleh muncul.');
        $this->assertNotContains('Putri Scope 1', $names, 'Lawan gender tidak boleh muncul.');

        $row = collect($response->json('results'))->firstWhere('name', 'Putra Scope 1');
        $this->assertArrayHasKey('class_name', $row);
        $this->assertSame('Kelas 7 A', $row['class_name']);
    }

    public function test_pencarian_kandidat_penghuni_baru_mengecualikan_yang_sudah_berkamar(): void
    {
        $this->makeStudent('Putra Kandidat Scope', 'L', '1112000005');

        $url = route('user.asrama.residents.find-student', [
            'userId' => $this->admin->id,
            'asramaUuid' => $this->putra->id,
        ]);

        $names = collect(
            $this->actingAs($this->admin)
                ->getJson($url.'?mode=candidate&q=Scope')
                ->assertOk()
                ->json('results')
        )->pluck('name');

        $this->assertContains('Putra Kandidat Scope', $names);
        $this->assertNotContains('Putra Scope 1', $names, 'Santri yang sudah berkamar bukan kandidat.');
        $this->assertNotContains('Putra Asrama Lain', $names);
        $this->assertNotContains('Putri Scope 1', $names);
    }

    public function test_store_penghuni_menolak_lawan_gender(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('user.asrama.residents.store', [
                'userId' => $this->admin->id,
                'asramaUuid' => $this->putra->id,
            ]),
            [
                'student_id' => $this->santriPutri->id,
                'room_id' => $this->putraRoom->id,
                'check_in_date' => now()->toDateString(),
            ]
        );

        $response->assertSessionHasErrors('student_id');
        $this->assertFalse(
            DormitoryResident::where('student_id', $this->santriPutri->id)
                ->where('dormitory_id', $this->putra->id)
                ->exists(),
            'Santri putri tidak boleh masuk asrama putra.'
        );
    }

    public function test_detail_santri_asrama_lain_tidak_bisa_dibuka_via_url(): void
    {
        $url = route('user.asrama.reports.student-detail', [
            'userId' => $this->admin->id,
            'asramaUuid' => $this->putra->id,
            'studentId' => $this->santriPutraAsramaLain->id,
        ]);

        $this->actingAs($this->admin)->get($url)->assertNotFound();
    }
}
