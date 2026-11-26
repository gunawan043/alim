<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Support\StudentOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Daftar santri di menu Nilai harus urut nama, bukan urut nomor absen.
 *
 * attendance_number diisi mengikuti urutan input, sehingga santri yang masuk
 * belakangan mendapat nomor acak di tengah daftar dan tampilan nilai terlihat
 * berantakan (seperti urut inisial).
 */
class NilaiUrutSantriTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_nilai_urut_nama_bukan_nomor_absen(): void
    {
        $school = School::factory()->create();
        $ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $grade = GradeLevel::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'is_active' => true,
        ]);

        $group = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'is_active' => true,
        ]);

        // Nomor absen sengaja tidak sesuai nama: santri baru mendapat nomor
        // di akhir, padahal namanya paling depan.
        $rows = [['Zaki', 1], ['Budi', 2], ['Ahmad', 3]];

        foreach ($rows as [$name, $absen]) {
            $student = Student::create([
                'id' => (string) Str::uuid(),
                'school_id' => $school->id,
                'name' => $name,
                'nisn' => (string) random_int(1000000000, 9999999999),
                'gender' => 'L',
                'status' => 'active',
            ]);

            StudentClassHistory::create([
                'id' => (string) Str::uuid(),
                'student_id' => $student->id,
                'study_group_id' => $group->id,
                'academic_year_id' => $ay->id,
                'attendance_number' => $absen,
                'is_active' => true,
            ]);
        }

        $names = StudentClassHistory::with('student')
            ->where('study_group_id', $group->id)
            ->orderedByStudent()
            ->get()
            ->map(fn ($h) => $h->student->name)
            ->all();

        $this->assertSame(['Ahmad', 'Budi', 'Zaki'], $names);
    }

    public function test_urutan_nis_pada_daftar_nilai(): void
    {
        $school = School::factory()->create();
        $ay = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $grade = GradeLevel::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'is_active' => true,
        ]);

        $group = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'is_active' => true,
        ]);

        foreach ([['Sepuluh', '10'], ['Sembilan', '9'], ['Dua', '2']] as [$name, $nis]) {
            $student = Student::create([
                'id' => (string) Str::uuid(),
                'school_id' => $school->id,
                'name' => $name,
                'nis' => $nis,
                'nisn' => (string) random_int(1000000000, 9999999999),
                'gender' => 'L',
                'status' => 'active',
            ]);

            StudentClassHistory::create([
                'id' => (string) Str::uuid(),
                'student_id' => $student->id,
                'study_group_id' => $group->id,
                'academic_year_id' => $ay->id,
                'attendance_number' => 1,
                'is_active' => true,
            ]);
        }

        $names = StudentClassHistory::with('student')
            ->where('study_group_id', $group->id)
            ->orderedByStudent(StudentOrder::NIS)
            ->get()
            ->map(fn ($h) => $h->student->name)
            ->all();

        // "10" < "2" bila dibandingkan sebagai teks; harus urut angka.
        $this->assertSame(['Dua', 'Sembilan', 'Sepuluh'], $names);
    }
}
