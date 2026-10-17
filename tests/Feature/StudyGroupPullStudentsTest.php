<?php

namespace Tests\Feature;

use App\Http\Controllers\StudyGroupApiController;
use App\Models\AcademicYear;
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
 * Regresi "Tarik Santri ke Rombel" (halaman data santri per rombel).
 *
 * Bug yang pernah terjadi:
 * - kandidat santri dikecualikan lintas tahun ajaran → daftar kosong,
 * - penambahan memakai TA aktif global (bukan TA rombel) → santri tidak masuk,
 * - re-add menggandakan histori.
 */
class StudyGroupPullStudentsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ayActive;

    private AcademicYear $ayTarget;

    private StudyGroup $rombelTarget;

    private Student $studentPindahan;

    private Student $studentBaru;

    private User $wali;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Tarik', 'code' => 'UUT',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '22222222',
            'name' => 'Sekolah Uji Tarik',
        ]);

        $this->ayActive = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30',
        ]);

        $this->ayTarget = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => false,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create(['school_id' => $this->school->id, 'level' => 1, 'name' => 'Kelas 1', 'is_active' => true]);

        $this->wali = User::create([
            'name' => 'Wali Tarik', 'email' => 'wali.tarik@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $this->rombelTarget = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->ayTarget->id,
            'grade_level_id' => $grade->id, 'name' => '1A', 'code' => '1A', 'capacity' => 30,
            'is_active' => true, 'homeroom_teacher_id' => $this->wali->id,
        ]);

        $this->studentPindahan = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Pindahan',
            'nisn' => '9991112223', 'gender' => 'L', 'status' => 'active',
        ]);

        // Santri ini masih aktif di rombel TA berjalan → tetap harus muncul sebagai kandidat
        // untuk rombel TA tujuan.
        StudentClassHistory::create([
            'student_id' => $this->studentPindahan->id,
            'study_group_id' => $this->rombelTarget->id === null ? null : StudyGroup::create([
                'school_id' => $this->school->id, 'academic_year_id' => $this->ayActive->id,
                'grade_level_id' => $grade->id, 'name' => '1B', 'code' => '1B', 'capacity' => 30, 'is_active' => true,
            ])->id,
            'academic_year_id' => $this->ayActive->id,
            'is_active' => true,
            'join_date' => '2025-07-01',
            'attendance_number' => 1,
        ]);

        $this->studentBaru = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Baru',
            'nisn' => '9991112224', 'gender' => 'P', 'status' => 'active',
        ]);
    }

    public function test_kandidat_tarik_santri_muncul_dan_masuk_ke_ta_rombel(): void
    {
        $controller = app(StudyGroupApiController::class);

        // 1) Kandidat: kedua santri muncul (yang aktif di TA lain tidak dikecualikan).
        $listResponse = $controller->unassignedStudents($this->makeRequest(), $this->wali->id, $this->rombelTarget->id);
        $candidates = collect($listResponse->getData(true)['data'])->pluck('id')->all();

        $this->assertContains($this->studentPindahan->id, $candidates, 'Santri dari TA lain harus tetap muncul sebagai kandidat.');
        $this->assertContains($this->studentBaru->id, $candidates, 'Santri tanpa histori harus muncul sebagai kandidat.');

        // 2) Tarik kedua santri ke rombel target (TA rombel = 2026/2027, BUKAN TA aktif).
        $addResponse = $controller->bulkAddStudents($this->makeRequest([
            'student_ids' => [$this->studentPindahan->id, $this->studentBaru->id],
        ]), $this->wali->id, $this->rombelTarget->id);

        $this->assertTrue($addResponse->getData(true)['success']);

        foreach ([$this->studentPindahan->id, $this->studentBaru->id] as $studentId) {
            $this->assertDatabaseHas('student_class_histories', [
                'student_id' => $studentId,
                'study_group_id' => $this->rombelTarget->id,
                'academic_year_id' => $this->ayTarget->id,
                'is_active' => 1,
            ]);
        }

        // 3) Idempoten: tarik ulang tidak menggandakan baris pada TA target.
        $controller->bulkAddStudents($this->makeRequest([
            'student_ids' => [$this->studentBaru->id],
        ]), $this->wali->id, $this->rombelTarget->id);

        $this->assertSame(1, StudentClassHistory::where('student_id', $this->studentBaru->id)
            ->where('academic_year_id', $this->ayTarget->id)
            ->count());
    }

    private function makeRequest(array $data = []): Request
    {
        $request = Request::create('/api/study-groups/students', 'POST', $data);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->wali);

        return $request;
    }
}
