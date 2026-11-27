<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\InstitutionDecree;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\InstitutionDecreeMatrixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Memindahkan ampu mapel + rombel dari satu GTK ke GTK lain lewat matriks SK.
 *
 * Form mengizinkan centang dipindah (bukan ditolak), dan sisi server
 * menyinkronkan penugasan: baris guru lama dihapus, baris guru baru dibuat.
 * Mapel Tahfidz dikecualikan karena boleh diampu lebih dari satu guru.
 */
class DecreeSubjectTransferTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private StudyGroup $group;

    private Subject $math;

    private Subject $tahfidz;

    private InstitutionDecree $decree;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = WorkUnit::create(['name' => 'Unit Uji SK', 'code' => 'UUSK', 'type' => 'Unit Akademik', 'is_active' => true]);
        $this->school = School::create(['work_unit_id' => $unit->id, 'npsn' => '90909090', 'name' => 'Sekolah Uji SK']);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 6, 'name' => 'Kelas 6',
            'fase' => 'C', 'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id, 'name' => '6C', 'code' => '6C', 'is_active' => true,
        ]);

        $this->math = Subject::create([
            'school_id' => $this->school->id, 'code' => 'MTK', 'name' => 'Matematika',
            'category' => 'nasional', 'is_active' => true,
        ]);

        $this->tahfidz = Subject::create([
            'school_id' => $this->school->id, 'code' => 'THF', 'name' => "Tahfidzul Qur'an",
            'category' => 'nasional', 'is_active' => true,
        ]);

        $this->decree = InstitutionDecree::create([
            'decree_number' => '020/SK/2026',
            'decree_type' => 'pembagian_tugas',
            'title' => 'Pembagian Tugas Uji',
            'academic_year_id' => $this->year->id,
            'school_id' => $this->school->id,
            'issued_date' => '2026-07-01',
            'effective_date' => '2026-07-01',
            'status' => 'draft',
        ]);
    }

    public function test_mapel_dan_rombel_pindah_ke_gtk_lain(): void
    {
        $guruA = $this->makeTeacher('Guru A', 'guru.a@test.local');
        $guruB = $this->makeTeacher('Guru B', 'guru.b@test.local');

        $this->assign($guruA, $this->math, 4);

        // Guru B mencentang Matematika 6C. Form mengirim hanya guru B,
        // karena kotak guru A sudah dilepas saat dipindah.
        $this->sync([
            $guruB->id => [
                $this->math->id => [
                    $this->group->id => ['hours' => 4],
                ],
            ],
        ]);

        $this->assertDatabaseMissing('teaching_assignments', [
            'decree_id' => $this->decree->id,
            'teacher_id' => $guruA->id,
            'subject_id' => $this->math->id,
            'study_group_id' => $this->group->id,
        ]);

        $this->assertDatabaseHas('teaching_assignments', [
            'decree_id' => $this->decree->id,
            'teacher_id' => $guruB->id,
            'subject_id' => $this->math->id,
            'study_group_id' => $this->group->id,
            'weekly_hours' => 4,
            'status' => 'active',
        ]);
    }

    public function test_tahfidz_boleh_diampu_lebih_dari_satu_gtk(): void
    {
        $guruA = $this->makeTeacher('Guru Tahfidz A', 'guru.ta@test.local');
        $guruB = $this->makeTeacher('Guru Tahfidz B', 'guru.tb@test.local');

        $this->assign($guruA, $this->tahfidz, 3);

        // Kedua guru mengampu Tahfidz di rombel yang sama.
        $this->sync([
            $guruA->id => [$this->tahfidz->id => [$this->group->id => ['hours' => 3]]],
            $guruB->id => [$this->tahfidz->id => [$this->group->id => ['hours' => 3]]],
        ]);

        $this->assertSame(
            2,
            TeachingAssignment::where('decree_id', $this->decree->id)
                ->where('subject_id', $this->tahfidz->id)
                ->where('study_group_id', $this->group->id)
                ->where('status', 'active')
                ->count(),
            'Tahfidz boleh diampu lebih dari satu guru.'
        );
    }

    public function test_pemindahan_menjaga_baris_guru_lama_pada_mapel_lain(): void
    {
        $guruA = $this->makeTeacher('Guru A Dua', 'guru.a2@test.local');
        $guruB = $this->makeTeacher('Guru B Dua', 'guru.b2@test.local');

        // Guru A mengampu Matematika dan Tahfidz di 6C.
        $this->assign($guruA, $this->math, 4);
        $this->assign($guruA, $this->tahfidz, 3);

        // Matematika pindah ke guru B; Tahfidz tetap di guru A.
        $this->sync([
            $guruA->id => [$this->tahfidz->id => [$this->group->id => ['hours' => 3]]],
            $guruB->id => [$this->math->id => [$this->group->id => ['hours' => 4]]],
        ]);

        $this->assertDatabaseHas('teaching_assignments', [
            'decree_id' => $this->decree->id,
            'teacher_id' => $guruA->id,
            'subject_id' => $this->tahfidz->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('teaching_assignments', [
            'decree_id' => $this->decree->id,
            'teacher_id' => $guruB->id,
            'subject_id' => $this->math->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseMissing('teaching_assignments', [
            'decree_id' => $this->decree->id,
            'teacher_id' => $guruA->id,
            'subject_id' => $this->math->id,
            'status' => 'active',
        ]);
    }

    private function assign(User $teacher, Subject $subject, int $hours): void
    {
        TeachingAssignment::create([
            'decree_id' => $this->decree->id,
            'teacher_id' => $teacher->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $subject->id,
            'weekly_hours' => $hours,
            'status' => 'active',
            'role' => 'guru_mapel',
        ]);
    }

    /**
     * @param  array<string, array<string, array<string, array<string, int>>>>  $assignments
     */
    private function sync(array $assignments): void
    {
        app(InstitutionDecreeMatrixService::class)->sync(
            $this->decree,
            $assignments,
            [],
            $this->school->id,
            true
        );
    }

    private function makeTeacher(string $name, string $email): User
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => $name, 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Pendidik',
            'jabatan' => 'Guru',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }
}
