<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\InstitutionDecree;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Observer penugasan mengajar memindahkan kepemilikan buku nilai ke guru
 * pengganti. Pemindahan tidak boleh menabrak aturan unik buku
 * (guru + mapel + rombel + TA + semester): bila guru pengganti sudah punya
 * buku pada konteks yang sama, buku lama cukup dinonaktifkan.
 */
class TeachingAssignmentBookOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private StudyGroup $group;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = WorkUnit::create(['name' => 'Unit Uji', 'code' => 'UU', 'type' => 'Unit Akademik', 'is_active' => true]);
        $this->school = School::create(['work_unit_id' => $unit->id, 'npsn' => '11112222', 'name' => 'Sekolah Uji']);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7',
            'fase' => 'D', 'is_active' => true,
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'MTK', 'name' => 'Matematika',
            'category' => 'nasional', 'is_active' => true,
        ]);
    }

    public function test_pemindahan_buku_tidak_tabrakan_saat_guru_baru_sudah_punya_buku(): void
    {
        $guruLama = $this->makeTeacher('Guru Lama', 'guru.lama@test.local');
        $guruBaru = $this->makeTeacher('Guru Baru', 'guru.baru@test.local');

        // Konteks mapel + rombel + TA + semester yang sama.
        $bukuLama = $this->makeBook($guruLama);
        $bukuBaru = $this->makeBook($guruBaru);

        // Guru lama dinonaktifkan penugasannya; guru baru mengambil konteks ini.
        $decree = $this->makeDecree();

        $penugasanLama = TeachingAssignment::create([
            'decree_id' => $decree->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $guruLama->id,
            'subject_id' => $this->subject->id,
            'study_group_id' => $this->group->id,
            'weekly_hours' => 2,
            'status' => 'active',
            'assignment_source' => 'unit',
        ]);

        // Serah terima: penugasan guru lama dialihkan ke guru baru.
        // Memicu updated() -> syncBookOwnership().
        $penugasanLama->update(['teacher_id' => $guruBaru->id]);

        // Tidak boleh ada dua buku aktif untuk guru yang sama pada konteks sama.
        $this->assertSame(
            1,
            TeacherAdminBook::query()
                ->where('teacher_id', $guruBaru->id)
                ->where('subject_id', $this->subject->id)
                ->where('study_group_id', $this->group->id)
                ->where('academic_year_id', $this->year->id)
                ->where('semester', 'ganjil')
                ->count(),
            'Guru baru hanya boleh punya satu buku pada satu konteks.'
        );

        // Buku lama tetap ada demi riwayat nilai, tetapi tidak aktif.
        $this->assertDatabaseHas('teacher_admin_books', [
            'id' => $bukuLama->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('teacher_admin_books', [
            'id' => $bukuBaru->id,
            'is_active' => true,
        ]);
    }

    public function test_pemindahan_buku_tetap_berjalan_bila_guru_baru_belum_punya_buku(): void
    {
        $guruLama = $this->makeTeacher('Guru Lama Dua', 'guru.lama2@test.local');
        $guruBaru = $this->makeTeacher('Guru Baru Dua', 'guru.baru2@test.local');

        $bukuLama = $this->makeBook($guruLama);

        $decree = $this->makeDecree();

        $penugasan = TeachingAssignment::create([
            'decree_id' => $decree->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $guruLama->id,
            'subject_id' => $this->subject->id,
            'study_group_id' => $this->group->id,
            'weekly_hours' => 2,
            'status' => 'inactive',
            'assignment_source' => 'unit',
        ]);

        $penugasan->update(['teacher_id' => $guruBaru->id, 'status' => 'active']);

        $this->assertDatabaseHas('teacher_admin_books', [
            'id' => $bukuLama->id,
            'teacher_id' => $guruBaru->id,
            'is_active' => true,
        ]);
    }

    private function makeTeacher(string $name, string $email): User
    {
        return User::create([
            'id' => (string) Str::uuid(),
            'name' => $name, 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
    }

    private function makeDecree(): InstitutionDecree
    {
        return InstitutionDecree::create([
            'decree_number' => 'SK-'.Str::random(6),
            'decree_type' => 'pembagian_tugas',
            'title' => 'SK Pembagian Tugas Uji',
            'academic_year_id' => $this->year->id,
            'school_id' => $this->school->id,
            'issued_date' => '2026-07-01',
            'effective_date' => '2026-07-01',
            'status' => 'active',
        ]);
    }

    private function makeBook(User $teacher): TeacherAdminBook
    {
        return TeacherAdminBook::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $this->subject->id,
            'study_group_id' => $this->group->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
    }
}
