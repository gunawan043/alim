<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolUnitLink;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\SchoolUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SchoolUnitTest extends TestCase
{
    use RefreshDatabase;

    private function school(string $name, string $npsn): School
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => $name, 'code' => 'WU'.substr($npsn, -3),
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => $npsn,
            'name' => $name,
            'school_level' => 'smp',
            'is_active' => true,
        ]);
    }

    public function test_halaman_mapping_dapodik_dapat_dibuka_dan_disimpan(): void
    {
        $primary = $this->school('SMP IT Putra', '52010203');
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
        ]);
        $gradeId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $gradeId, 'school_id' => $primary->id, 'level' => 7,
            'name' => 'Kelas 7', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $group = StudyGroup::create([
            'school_id' => $primary->id, 'academic_year_id' => $year->id, 'grade_level_id' => $gradeId,
            'name' => '7A', 'code' => '7A', 'capacity' => 30, 'is_active' => true,
        ]);
        $student = Student::create([
            'school_id' => $primary->id, 'name' => 'Santri Mapping', 'nisn' => '4444444444',
            'gender' => 'L', 'status' => 'active', 'entry_date' => '2026-07-01',
        ]);
        StudentClassHistory::create([
            'student_id' => $student->id, 'study_group_id' => $group->id,
            'academic_year_id' => $year->id, 'is_active' => true, 'join_date' => '2026-07-01',
        ]);

        $user = User::create([
            'name' => 'Admin Mapping', 'email' => 'admin.mapping@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(), 'user_id' => $user->id, 'school_id' => $primary->id,
            'status_kepegawaian' => 'PTT', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($user)->get(route('user.students.dapodik-mapping', ['userId' => $user->id, 'school_id' => $primary->id]))
            ->assertOk()
            ->assertSee('Mapping Rombel Dapodik');

        $this->actingAs($user)->post(route('user.students.dapodik-mapping.save', ['userId' => $user->id]), [
            'school_id' => $primary->id,
            'academic_year_id' => $year->id,
            'mappings' => [
                $student->id => [
                    'dapodik_school_npsn' => '52010203',
                    'dapodik_rombel_name' => '7A-DAPODIK',
                    'dapodik_rombel_code' => '7A-D',
                    'dapodik_level' => '7',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('student_dapodik_mappings', [
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'dapodik_rombel_name' => '7A-DAPODIK',
        ]);
    }

    public function test_santri_hanya_punya_satu_riwayat_kelas_aktif_per_tahun_ajaran(): void
    {
        $school = $this->school('SMP IT Putra', '52010203');
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
        ]);
        $gradeId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $gradeId, 'school_id' => $school->id, 'level' => 7,
            'name' => 'Kelas 7', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $groupA = StudyGroup::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'grade_level_id' => $gradeId,
            'name' => '7A', 'code' => '7A', 'capacity' => 30, 'is_active' => true,
        ]);
        $yearGenap = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'genap', 'is_active' => false,
            'start_date' => '2027-01-01', 'end_date' => '2027-06-30',
        ]);
        $groupB = StudyGroup::create([
            'school_id' => $school->id, 'academic_year_id' => $yearGenap->id, 'grade_level_id' => $gradeId,
            'name' => '7B', 'code' => '7B', 'capacity' => 30, 'is_active' => true,
        ]);
        $student = Student::create([
            'school_id' => $school->id, 'name' => 'Santri Uji', 'nisn' => '3333333333',
            'gender' => 'L', 'status' => 'active', 'entry_date' => '2026-07-01',
        ]);

        StudentClassHistory::create([
            'student_id' => $student->id, 'study_group_id' => $groupA->id,
            'academic_year_id' => $year->id, 'is_active' => true, 'join_date' => '2026-07-01',
        ]);
        StudentClassHistory::create([
            'student_id' => $student->id, 'study_group_id' => $groupB->id,
            'academic_year_id' => $yearGenap->id, 'is_active' => true, 'join_date' => '2027-01-01',
        ]);

        $this->assertSame(1, StudentClassHistory::where('student_id', $student->id)->where('is_active', true)->count());
        $this->assertDatabaseHas('student_class_histories', [
            'student_id' => $student->id,
            'study_group_id' => $groupB->id,
            'is_active' => true,
        ]);
    }

    public function test_unit_operasional_hanya_melihat_santri_yang_ditempatkan_di_unitnya(): void
    {
        $primary = $this->school('SMP IT Putra', '52010203');
        $operational = $this->school('SMP & SMA IT Putra', '52010205');

        SchoolUnitLink::create([
            'primary_school_id' => $primary->id,
            'operational_school_id' => $operational->id,
            'relation_type' => 'operational',
            'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
        ]);

        $gradeId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $gradeId, 'school_id' => $primary->id, 'level' => 7,
            'name' => 'Kelas 7', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $primaryGroup = StudyGroup::create([
            'school_id' => $primary->id, 'unit_type' => 'primary', 'data_owner_school_id' => $primary->id,
            'academic_year_id' => $year->id, 'grade_level_id' => $gradeId,
            'name' => '7A', 'code' => '7A-P', 'capacity' => 30, 'is_active' => true,
        ]);
        $operationalGroup = StudyGroup::create([
            'school_id' => $operational->id, 'unit_type' => 'operational', 'data_owner_school_id' => $primary->id,
            'academic_year_id' => $year->id, 'grade_level_id' => $gradeId,
            'name' => '7O', 'code' => '7O-O', 'capacity' => 30, 'is_active' => true,
        ]);

        $primaryStudent = Student::create([
            'school_id' => $primary->id, 'name' => 'Santri Mondok', 'nisn' => '1111111111',
            'gender' => 'L', 'status' => 'active', 'entry_date' => '2026-07-01',
        ]);
        $operationalStudent = Student::create([
            'school_id' => $primary->id, 'name' => 'Santri Non Mondok', 'nisn' => '2222222222',
            'gender' => 'P', 'status' => 'active', 'entry_date' => '2026-07-01',
        ]);

        StudentClassHistory::create([
            'student_id' => $primaryStudent->id, 'study_group_id' => $primaryGroup->id,
            'academic_year_id' => $year->id, 'is_active' => true, 'join_date' => '2026-07-01',
        ]);
        StudentClassHistory::create([
            'student_id' => $operationalStudent->id, 'study_group_id' => $operationalGroup->id,
            'academic_year_id' => $year->id, 'is_active' => true, 'join_date' => '2026-07-01',
        ]);

        $service = app(SchoolUnitService::class);

        $this->assertTrue($service->isOperational($operational->id));
        $this->assertFalse($service->isOperational($primary->id));
        $this->assertSame($primary->id, $service->primarySchoolId($operational->id));
        $this->assertSame($primary->id, $service->dataOwnerSchool($operational->id)?->id);
        $this->assertTrue($service->canReadSchool($primary->id, $operational->id));
        $this->assertFalse($service->canManageSchool($primary->id, $operational->id));
        $this->assertTrue($service->canManageSchool($operational->id, $operational->id));

        $primaryIds = $service->scopeStudents(Student::query(), $primary->id)->pluck('id')->all();
        $operationalIds = $service->scopeStudents(Student::query(), $operational->id)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$primaryStudent->id, $operationalStudent->id], $primaryIds);
        $this->assertSame([$operationalStudent->id], $operationalIds);
    }

    /**
     * Modal upload kop/TTD/stempel harus tetap bisa di-scroll.
     *
     * Bootstrap hanya bisa men-scroll .modal-body kalau header/body/footer
     * menjadi anak langsung .modal-content. Form yang membungkus ketiganya
     * memutus rantai flex itu, sehingga isi modal terpotong dan tidak bisa
     * digulir — persis keluhan pada halaman edit data sekolah.
     */
    public function test_form_modal_kop_berada_di_dalam_modal_body(): void
    {
        $school = $this->school('SMP Modal Kop', '52010777');

        $user = User::create([
            'name' => 'Admin Modal', 'email' => 'admin.modal@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(), 'user_id' => $user->id, 'school_id' => $school->id,
            'status_kepegawaian' => 'PTT', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Kepala satuan pendidikan berhak membuka halaman edit sekolah.
        $school->update(['principal_user_id' => $user->id]);

        // Permission harus ada di database; canPermission() melempar exception
        // bila namanya belum ter-seed.
        DB::table('permissions')->insertOrIgnore([
            'id' => (string) Str::uuid(), 'name' => 'school-update', 'guard_name' => 'web',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $html = $this->actingAs($user)
            ->get(route('user.schools.edit', ['userId' => $user->id, 'schoolId' => $school->id]))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<div class="modal-body">\s*<form id="schoolMediaForm"/',
            $html,
            'Form modal harus berada di dalam .modal-body agar isi modal dapat di-scroll.'
        );

        $this->assertStringContainsString('form="schoolMediaForm"', $html);

        preg_match('/<form id="schoolMediaForm".*?<\/form>/s', $html, $form);

        $this->assertNotEmpty($form, 'Form modal tidak ditemukan pada halaman edit sekolah.');
        $this->assertStringNotContainsString('modal-header', $form[0]);
        $this->assertStringNotContainsString('modal-footer', $form[0]);
    }
}
