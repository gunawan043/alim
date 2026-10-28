<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AdminPresensiHarian;
use App\Models\GradeLevel;
use App\Models\Kaldik;
use App\Models\NilaiSumatif;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentMutationOut;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\Todo;
use App\Models\User;
use App\Services\SidebarIndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Indikator sidebar harus tepat sasaran: hanya pekerjaan yang menjadi
 * kewenangan pengguna, mengikuti sekolah + TA aktif, dan tidak menghitung
 * absensi pada hari libur.
 */
class SidebarIndicatorTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private Subject $subject;

    private User $wali;

    private User $guru;

    private User $kepala;

    private StudyGroup $group;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Indikator', 'code' => 'UI',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '90909090', 'name' => 'Sekolah Indikator',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7', 'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'MAT', 'name' => 'Matematika',
            'credit_hours' => 4, 'is_active' => true,
        ]);

        $this->wali = $this->makeUser('Wali Kelas', 'wali.indikator@test.local', 'Guru Umum');
        $this->guru = $this->makeUser('Guru Mapel', 'guru.indikator@test.local', 'Guru Umum');
        $this->kepala = $this->makeUser('Kepala Sekolah', 'kepala.indikator@test.local', 'Kepala Satuan Pendidikan');

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'homeroom_teacher_id' => $this->wali->id,
            'name' => '7A', 'code' => '7A', 'capacity' => 30, 'is_active' => true,
        ]);

        $this->student = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Indikator',
            'nisn' => '9090909001', 'gender' => 'L', 'status' => 'active',
        ]);

        StudentClassHistory::create([
            'student_id' => $this->student->id,
            'study_group_id' => $this->group->id,
            'academic_year_id' => $this->ay->id,
            'is_active' => true,
            'attendance_number' => 1,
        ]);

        // Sidebar wali kelas dirender dari tugas tambahan, bukan sekadar
        // kolom homeroom_teacher_id pada rombel.
        DB::table('gtk_additional_tasks')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $this->wali->id,
            'work_unit_id' => $this->school->work_unit_id,
            'nama_tugas' => 'Wali Kelas',
            'hours_per_week' => 2,
            'tmt' => now()->subMonth()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_absensi_wali_dihitung_hanya_untuk_kelas_belum_diabsen(): void
    {
        $service = app(SidebarIndicatorService::class);

        $this->assertSame(1, $service->forUser($this->wali, $this->school->id)['absensi_wali']);

        AdminPresensiHarian::create([
            'study_group_id' => $this->group->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'attendance_date' => now()->toDateString(),
            'student_id' => $this->student->id,
            'status' => 'hadir',
        ]);

        $this->assertSame(0, $service->forUser($this->wali, $this->school->id)['absensi_wali']);
    }

    public function test_absensi_tidak_dihitung_pada_hari_libur(): void
    {
        Kaldik::create([
            'name' => 'Libur Uji',
            'category' => Kaldik::CATEGORY_KALDIK,
            'type' => Kaldik::TYPE_LIBUR,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'is_active' => true,
            'work_unit_id' => $this->school->work_unit_id,
        ]);

        $this->assertSame(
            0,
            app(SidebarIndicatorService::class)->forUser($this->wali, $this->school->id)['absensi_wali']
        );
    }

    public function test_nilai_guru_menghitung_buku_yang_belum_lengkap(): void
    {
        $book = TeacherAdminBook::create([
            'teacher_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'study_group_id' => $this->group->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $service = app(SidebarIndicatorService::class);
        $this->assertSame(1, $service->forUser($this->guru, $this->school->id)['nilai_guru']);

        NilaiSumatif::create([
            'admin_book_id' => $book->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'sts' => 80,
            'sas' => 85,
            'nr_final' => 82,
        ]);

        $this->assertSame(0, $service->forUser($this->guru, $this->school->id)['nilai_guru']);
    }

    public function test_indikator_hanya_untuk_kewenangan_pengguna(): void
    {
        StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'homeroom_teacher_id' => null,
            'name' => '7B', 'code' => '7B', 'capacity' => 30, 'is_active' => true,
        ]);

        StudentMutationOut::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'status' => 'submitted',
            'out_type' => 'mutation',
            'student_name' => $this->student->name,
        ]);

        $service = app(SidebarIndicatorService::class);

        $wali = $service->forUser($this->wali, $this->school->id);
        $this->assertSame(0, $wali['rombel_tanpa_wali'], 'Wali kelas tidak mengelola seluruh rombel.');
        $this->assertSame(0, $wali['mutasi_pending'], 'Wali kelas tidak memproses mutasi.');

        $kepala = $service->forUser($this->kepala, $this->school->id);
        $this->assertSame(1, $kepala['rombel_tanpa_wali']);
        $this->assertSame(1, $kepala['mutasi_pending']);
    }

    public function test_todo_overdue_hanya_milik_pengguna(): void
    {
        Todo::create([
            'owner_id' => $this->guru->id,
            'created_by' => $this->guru->id,
            'title' => 'Tugas Lewat Tenggat',
            'priority' => 'tinggi',
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'belum_mulai',
        ]);

        Todo::create([
            'owner_id' => $this->wali->id,
            'created_by' => $this->wali->id,
            'title' => 'Tugas Wali',
            'priority' => 'sedang',
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'belum_mulai',
        ]);

        $service = app(SidebarIndicatorService::class);

        $this->assertSame(1, $service->forUser($this->guru, $this->school->id)['todo_overdue']);
        $this->assertSame(0, $service->forUser($this->wali, $this->school->id)['todo_overdue']);
    }

    public function test_sidebar_menampilkan_indikator_untuk_wali_kelas(): void
    {
        $this->actingAs($this->wali)
            ->get("/{$this->wali->id}/notifications/page")
            ->assertOk()
            ->assertSee('sidebar-indicator', false)
            ->assertSee('Kelas Anda belum diabsen hari ini');
    }

    private function makeUser(string $name, string $email, string $jabatan): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'Satuan Pendidikan', 'guard_name' => 'web'],
            ['level' => 8]
        );

        $user = User::create([
            'name' => $name, 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $user->assignRole($role);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => $jabatan,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('gtk_work_unit')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'work_unit_id' => $this->school->work_unit_id,
            'jabatan' => $jabatan,
            'is_primary' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $user->fresh();
    }
}
