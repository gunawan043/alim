<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\TahfidzGradebookCalculator;
use App\Services\TahfidzGradebookService;
use App\Services\TahfidzSubjectResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Buku nilai tahfidz diikat ke halaqah, bukan rombel.
 *
 * Yang dijaga di sini:
 *  1. Buku hanya muncul untuk guru halaqah tersebut, termasuk bila guru
 *     tercatat lewat tabel pengajar halaqah (bukan hanya teacher_id).
 *  2. Anggota yang sudah keluar tidak lagi tampil dan tidak bisa diberi nilai.
 *  3. Satu halaqah lintas rombel: nilai tiap santri membawa rombelnya sendiri
 *     agar masuk ke rapor yang benar.
 *  4. NR Final dihitung dari empat kolom dengan normalisasi bobot.
 */
class TahfidzGradebookHalaqahTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private WorkUnit $unit;

    private AcademicYear $year;

    private User $teacher;

    private Subject $tahfidz;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = WorkUnit::create(['name' => 'Unit Tahfidz', 'code' => 'UT-THF', 'type' => 'Unit Akademik', 'is_active' => true]);
        $this->unit = $unit;
        $this->school = School::create(['work_unit_id' => $unit->id, 'npsn' => '80808081', 'name' => 'Sekolah Tahfidz']);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $this->teacher = User::create([
            'name' => 'Guru Halaqah', 'email' => 'guru.halaqah@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $this->tahfidz = Subject::create([
            'school_id' => $this->school->id, 'code' => 'THF', 'name' => "Tahfidzul Qur'an",
            'category' => 'nasional', 'is_active' => true,
            'penilaian_format' => TahfidzSubjectResolver::FORMAT_TAHFIDZ,
        ]);
        Subject::create([
            'school_id' => $this->school->id, 'code' => 'MTK', 'name' => 'Matematika',
            'category' => 'nasional', 'is_active' => true,
            'penilaian_format' => TahfidzSubjectResolver::FORMAT_UMUM,
        ]);
    }

    public function test_buku_hanya_untuk_guru_halaqah_dan_mapel_tahfidz_saja(): void
    {
        $group = $this->makeGroup('Halaqah A');

        $books = app(TahfidzGradebookService::class)
            ->booksForTeacher($this->teacher->id, $this->school->id, $this->year->id);

        $flat = $books->flatten();

        $this->assertCount(1, $flat, 'Hanya mapel tahfidz yang mendapat buku.');
        $this->assertSame($this->tahfidz->id, $flat->first()->subject_id);
        $this->assertSame($group->id, $flat->first()->tahfidz_group_id);
    }

    public function test_guru_lain_tidak_mendapat_buku_halaqah_ini(): void
    {
        $this->makeGroup('Halaqah A');

        $other = User::create([
            'name' => 'Guru Lain', 'email' => 'guru.lain@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $books = app(TahfidzGradebookService::class)
            ->booksForTeacher($other->id, $this->school->id, $this->year->id);

        $this->assertCount(0, $books->flatten());
    }

    public function test_santri_keluar_tidak_tampil_dan_tidak_bisa_dinilai(): void
    {
        $group = $this->makeGroup('Halaqah A');
        $kelas = $this->makeClass('7A');

        $aktif = $this->makeStudent('Santri Aktif', 'AK-1', $kelas);
        $keluar = $this->makeStudent('Santri Keluar', 'KL-1', $kelas);

        $this->addMember($group, $aktif, 'aktif');
        $this->addMember($group, $keluar, 'pindah_halaqah');

        $service = app(TahfidzGradebookService::class);
        $members = $service->activeMembers($group, $this->year->id);

        $this->assertCount(1, $members, 'Anggota keluar tidak boleh tampil.');
        $this->assertSame($aktif->id, $members->first()['student_id']);

        $book = $service->booksForTeacher($this->teacher->id, $this->school->id, $this->year->id)->flatten()->first();

        $saved = $service->saveScores($book, [
            $aktif->id => ['ziyadah' => 80],
            $keluar->id => ['ziyadah' => 90],
        ], $members);

        $this->assertSame(1, $saved, 'Nilai untuk anggota keluar ditolak.');
        $this->assertSame(1, $book->scores()->count());
    }

    public function test_satu_halaqah_lintas_rombel_membawa_rombel_masing_masing(): void
    {
        $group = $this->makeGroup('Halaqah Lintas');
        $kelasA = $this->makeClass('7A');
        $kelasB = $this->makeClass('7B');

        $santriA = $this->makeStudent('Santri 7A', 'AA-1', $kelasA);
        $santriB = $this->makeStudent('Santri 7B', 'BB-1', $kelasB);

        $this->addMember($group, $santriA, 'aktif');
        $this->addMember($group, $santriB, 'aktif');

        $service = app(TahfidzGradebookService::class);
        $members = $service->activeMembers($group, $this->year->id)->keyBy('student_id');

        $this->assertSame($kelasA->id, $members[$santriA->id]['study_group_id']);
        $this->assertSame($kelasB->id, $members[$santriB->id]['study_group_id']);

        $book = $service->booksForTeacher($this->teacher->id, $this->school->id, $this->year->id)->flatten()->first();
        $service->saveScores($book, [
            $santriA->id => ['ziyadah' => 85],
            $santriB->id => ['ziyadah' => 75],
        ], $members->values());

        $this->assertSame(
            $kelasA->id,
            $book->scores()->where('student_id', $santriA->id)->value('study_group_id'),
        );
        $this->assertSame(
            $kelasB->id,
            $book->scores()->where('student_id', $santriB->id)->value('study_group_id'),
        );
    }

    public function test_nr_final_memakai_bobot_dan_menormalisasi_komponen_kosong(): void
    {
        $group = $this->makeGroup('Halaqah A');
        $kelas = $this->makeClass('7A');
        $santri = $this->makeStudent('Santri A', 'AA-2', $kelas);
        $this->addMember($group, $santri, 'aktif');

        // Bobot milik departemen: semester ganjil = NPT 25 · Ziyadah 20 ·
        // Rabt 20 · Tikrar 10 · STS 25 (SAS 0).
        \App\Models\TahfidzWeightSetting::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'semester' => 'ganjil',
            'weight_npt' => 25, 'weight_ziyadah' => 20, 'weight_rabt' => 20,
            'weight_tikrar' => 10, 'weight_sts' => 25, 'weight_sas' => 0,
        ]);

        $service = app(TahfidzGradebookService::class);
        $book = $service->booksForTeacher($this->teacher->id, $this->school->id, $this->year->id)->flatten()->first();
        $members = $service->activeMembers($group, $this->year->id);

        // NPT kosong (tidak ikut dinormalisasi): (80×20 + 70×20 + 90×25)/65 = 80.77
        $service->saveScores($book, [
            $santri->id => ['ziyadah' => 80, 'rabt' => 70, 'sts' => 90, 'sas' => 85],
        ], $members);

        $this->assertSame(80.77, $book->scores()->first()->fresh()->nr_final);

        // Departemen mengubah bobot → NR lama dihitung ulang, tidak tertinggal.
        $book->update(['weight_npt' => 0, 'weight_ziyadah' => 70, 'weight_rabt' => 10, 'weight_tikrar' => 0, 'weight_sts' => 10, 'weight_sas' => 10]);
        $book->refresh()->recalcAll();

        // (80×70 + 70×10 + 90×10 + 85×10)/100 = 80.5
        $this->assertSame(80.5, $book->scores()->first()->fresh()->nr_final);
    }

    public function test_bobot_harus_berjumlah_seratus(): void
    {
        $calc = app(TahfidzGradebookCalculator::class);

        $this->assertSame([], $calc->validateWeights([
            'npt' => 10, 'ziyadah' => 30, 'rabt' => 15, 'tikrar' => 15, 'sts' => 15, 'sas' => 15,
        ]));

        $errors = $calc->validateWeights([
            'npt' => 10, 'ziyadah' => 50, 'rabt' => 15, 'tikrar' => 15, 'sts' => 15, 'sas' => 15,
        ]);
        $this->assertArrayHasKey('total', $errors);
    }

    private function makeGroup(string $name): TahfidzGroup
    {
        return TahfidzGroup::create([
            'work_unit_id' => $this->unit->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => $name, 'gender' => 'putra',
            'teacher_id' => $this->teacher->id, 'is_active' => true,
        ]);
    }

    private function makeClass(string $name): StudyGroup
    {
        $grade = GradeLevel::firstOrCreate(
            ['school_id' => $this->school->id, 'level' => 7],
            ['name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true],
        );

        return StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id, 'name' => $name, 'code' => $name, 'is_active' => true,
        ]);
    }

    private function makeStudent(string $name, string $nis, StudyGroup $class): Student
    {
        $student = Student::create([
            'school_id' => $this->school->id, 'name' => $name, 'nis' => $nis, 'nisn' => $nis,
            'gender' => 'L', 'status' => 'active',
        ]);

        StudentClassHistory::create([
            'student_id' => $student->id, 'study_group_id' => $class->id,
            'academic_year_id' => $this->year->id, 'is_active' => true,
        ]);

        return $student;
    }

    private function addMember(TahfidzGroup $group, Student $student, string $status): void
    {
        TahfidzGroupMember::create([
            'tahfidz_group_id' => $group->id, 'student_id' => $student->id,
            'academic_year_id' => $this->year->id, 'status' => $status,
            'join_date' => '2026-07-15',
        ]);
    }
}
