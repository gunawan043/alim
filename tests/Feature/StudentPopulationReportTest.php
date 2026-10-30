<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Alumni;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentLifecycleAudit;
use App\Models\StudentMutationIn;
use App\Models\StudentMutationOut;
use App\Models\StudentTransfer;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\Academic\StudentPopulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentPopulationReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private School $otherSchool;

    private AcademicYear $ay1;

    private AcademicYear $ay2;

    private StudyGroup $group1;

    private StudyGroup $group2;

    private StudyGroup $group3;

    private array $students = [];

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Populasi', 'code' => 'UPOP',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '90000001', 'name' => 'Sekolah Populasi',
            'school_level' => 'SMP', 'is_active' => true,
        ]);
        $this->otherSchool = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '90000002', 'name' => 'Sekolah Luar',
            'school_level' => 'SMP', 'is_active' => true,
        ]);

        $this->ay1 = AcademicYear::create([
            'name' => '2024/2025', 'semester' => 'ganjil', 'is_active' => false,
            'start_date' => '2024-07-01', 'end_date' => '2025-06-30',
        ]);
        $this->ay2 = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30',
        ]);

        $grade7 = (string) Str::uuid();
        $grade8 = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            ['id' => $grade7, 'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $grade8, 'school_id' => $this->school->id, 'level' => 8, 'name' => 'Kelas 8', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->group1 = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->ay1->id,
            'grade_level_id' => $grade7, 'name' => '7A', 'code' => '7A', 'capacity' => 30, 'is_active' => true,
        ]);
        $this->group2 = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->ay2->id,
            'grade_level_id' => $grade8, 'name' => '8A', 'code' => '8A', 'capacity' => 30, 'is_active' => true,
        ]);
        $this->group3 = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->ay2->id,
            'grade_level_id' => $grade7, 'name' => '7A', 'code' => '7A-2', 'capacity' => 30, 'is_active' => true,
        ]);
        $group4 = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->ay1->id,
            'grade_level_id' => $grade7, 'name' => '7B', 'code' => '7B', 'capacity' => 30, 'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Populasi', 'email' => 'admin.populasi@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $this->students = [
            'promoted' => $this->makeStudent('Santri Naik', 'L', '2009-01-10', '2024-07-01'),
            'retained' => $this->makeStudent('Santri Tinggal', 'P', '2009-03-10', '2024-07-01'),
            'new' => $this->makeStudent('Santri Baru', 'L', '2012-02-10', '2025-07-01'),
            'dropout' => $this->makeStudent('Santri Berhenti', 'P', '2009-05-10', '2024-07-01', 'dropped'),
            'transfer_in' => $this->makeStudent('Santri Pindahan', 'L', '2012-04-10', '2025-07-01'),
            'graduate' => $this->makeStudent('Santri Lulus', 'P', '2008-06-10', '2024-07-01', 'graduate'),
        ];

        $this->history($this->students['promoted'], $this->group1, $this->ay1);
        $this->history($this->students['promoted'], $this->group2, $this->ay2);
        $this->history($this->students['retained'], $this->group1, $this->ay1);
        $this->history($this->students['retained'], $this->group3, $this->ay2);
        $this->history($this->students['new'], $this->group3, $this->ay2);
        $this->history($this->students['dropout'], $this->group1, $this->ay1);
        $this->history($this->students['transfer_in'], $this->group3, $this->ay2);
        $this->history($this->students['graduate'], $this->group1, $this->ay1);

        StudentMutationIn::create([
            'student_id' => $this->students['transfer_in']->id,
            'school_id' => $this->school->id,
            'status' => 'approved',
            'student_name' => $this->students['transfer_in']->name,
            'student_nisn' => $this->students['transfer_in']->nisn,
            'origin_school_name' => 'Sekolah Asal',
            'established_date' => '2025-07-15',
            'reason' => 'Pindah domisili',
        ]);
        StudentMutationOut::create([
            'student_id' => $this->students['dropout']->id,
            'school_id' => $this->school->id,
            'status' => 'approved',
            'out_type' => 'dropout',
            'student_name' => $this->students['dropout']->name,
            'established_date' => '2024-11-01',
            'reason' => 'Bekerja',
        ]);
        StudentMutationOut::create([
            'student_id' => $this->students['graduate']->id,
            'school_id' => $this->school->id,
            'status' => 'approved',
            'out_type' => 'graduation',
            'student_name' => $this->students['graduate']->name,
            'established_date' => '2025-06-01',
            'graduation_year' => 2025,
            'graduation_school_name' => 'Sekolah Lanjutan',
        ]);
        Alumni::create([
            'student_id' => $this->students['graduate']->id,
            'school_id' => $this->school->id,
            'graduation_year' => 2025,
            'graduation_date' => '2025-06-01',
        ]);
        StudentLifecycleAudit::create([
            'event' => 'student.moved_rombel',
            'student_id' => $this->students['retained']->id,
            'school_id' => $this->school->id,
            'payload' => ['from_sg' => $this->group1->id, 'to_sg' => $group4->id],
            'occurred_at' => '2024-08-01 08:00:00',
        ]);

        // Duplikasi transfer untuk santri yang sama pada tanggal yang sama harus diabaikan.
        StudentTransfer::create([
            'student_id' => $this->students['transfer_in']->id,
            'from_school_id' => $this->otherSchool->id,
            'to_school_id' => $this->school->id,
            'transfer_date' => '2025-07-15',
            'transfer_type' => 'masuk',
            'approval_status' => 'approved',
            'reason' => 'Data ganda',
        ]);

        $this->actingAs($this->admin);
    }

    public function test_laporan_mutasi_menggabungkan_sumber_dan_menghindari_data_ganda(): void
    {
        $report = $this->service()->mutations([$this->school->id], ['academic_year_id' => $this->ay2->id]);

        $this->assertSame(1, $report['summary']['pindahan_masuk']);
        $this->assertSame(0, $report['summary']['internal_grade'] + $report['summary']['internal_unit'] + $report['summary']['class_move']);
        $this->assertSame(0, $report['summary']['berhenti']);
        $this->assertSame(0, $report['summary']['lulus']);
        $this->assertSame(1, $report['summary']['total']);

        $prior = $this->service()->mutations([$this->school->id], ['academic_year_id' => $this->ay1->id]);
        $this->assertSame(1, $prior['summary']['berhenti']);
        $this->assertSame(1, $prior['summary']['lulus']);
        $this->assertSame(1, $prior['summary']['class_move']);
        $this->assertSame(3, $prior['summary']['total']);
    }

    public function test_pertumbuhan_merekonsiliasi_awal_akhir_dan_tidak_menghitung_ganda(): void
    {
        $report = $this->service()->growth([$this->school->id], ['academic_year_id' => $this->ay2->id]);
        $summary = $report['summary'];

        $this->assertSame(2, $summary['opening']);
        $this->assertSame(1, $summary['new']);
        $this->assertSame(1, $summary['mutation_in']);
        $this->assertSame(0, $summary['mutation_out']);
        $this->assertSame(0, $summary['dropout']);
        $this->assertSame(0, $summary['graduates']);
        $this->assertSame(4, $summary['closing']);
        $this->assertSame(4, $summary['closing_direct']);
        $this->assertSame(0, $summary['reconciliation_difference']);
        $this->assertSame(100.0, (float) $summary['growth_rate']);
    }

    public function test_profil_menghitung_santri_unik_gender_dan_rombel(): void
    {
        $report = $this->service()->profile([$this->school->id], ['academic_year_id' => $this->ay2->id]);

        $this->assertSame(4, $report['summary']['total']);
        $this->assertSame(3, $report['summary']['male']);
        $this->assertSame(1, $report['summary']['female']);
        $this->assertSame(2, $report['summary']['rombel']);
        $this->assertSame(2.0, (float) $report['summary']['average_per_rombel']);
    }

    public function test_keberlanjutan_per_angkatan_dan_tidak_menyebut_putus_sekolah(): void
    {
        $report = $this->service()->continuity([$this->school->id], ['academic_year_id' => $this->ay1->id]);

        $this->assertSame(4, $report['summary']['cohort']);
        $this->assertSame(2, $report['summary']['continued']);
        $this->assertSame(2, $report['summary']['not_continued']);
        $this->assertSame(50.0, (float) $report['summary']['rate']);
        $this->assertStringContainsString('tidak otomatis berarti putus sekolah', $report['source_note']);
    }

    public function test_kenaikan_kelas_kelulusan_dan_masa_studi(): void
    {
        $report = $this->service()->promotions([$this->school->id], ['academic_year_id' => $this->ay1->id]);

        $this->assertSame(1, $report['summary']['promoted']);
        $this->assertSame(1, $report['summary']['retained']);
        $this->assertSame(1, $report['summary']['graduated']);
        $this->assertSame(1, $report['summary']['rombel_moved']);
        $this->assertSame(1, $report['graduation']['graduates']);
        $this->assertSame(25.0, (float) $report['graduation']['rate']);
        $this->assertSame(1, $report['graduation_by_unit']->sum('graduates'));
        $this->assertSame(1, $report['graduation_by_grade']->sum('graduates'));
    }

    public function test_halaman_dan_ekspor_laporan_populasi_dapat_dibuka(): void
    {
        $params = ['userId' => $this->admin->id, 'academic_year_id' => $this->ay2->id];
        $pages = [
            'user.laporan.populasi.mutasi' => 'Mutasi Santri',
            'user.laporan.populasi.pertumbuhan' => 'Pertumbuhan Populasi',
            'user.laporan.populasi.profil' => 'Profil & Sebaran Santri',
            'user.laporan.populasi.keberlanjutan' => 'Keberlanjutan & Retensi',
            'user.laporan.populasi.kenaikan-kelulusan' => 'Kenaikan & Kelulusan',
        ];

        foreach ($pages as $route => $text) {
            $this->get(route($route, $params))->assertOk()->assertSee($text, false);
        }

        foreach ([
            'user.laporan.populasi.mutasi.export',
            'user.laporan.populasi.pertumbuhan.export',
            'user.laporan.populasi.profil.export',
            'user.laporan.populasi.keberlanjutan.export',
            'user.laporan.populasi.kenaikan-kelulusan.export',
        ] as $route) {
            $response = $this->get(route($route, $params));
            $response->assertOk();
            $this->assertSame('PK', substr($response->streamedContent(), 0, 2), $route);
        }

        foreach ([
            'user.laporan.populasi.mutasi.pdf',
            'user.laporan.populasi.pertumbuhan.pdf',
            'user.laporan.populasi.profil.pdf',
            'user.laporan.populasi.keberlanjutan.pdf',
            'user.laporan.populasi.kenaikan-kelulusan.pdf',
        ] as $route) {
            $response = $this->get(route($route, $params));
            $response->assertOk();
            $this->assertStringStartsWith('%PDF', (string) $response->getContent(), $route);
        }
    }

    private function service(): StudentPopulationService
    {
        return app(StudentPopulationService::class);
    }

    private function makeStudent(string $name, string $gender, string $birthDate, string $entryDate, string $status = 'active'): Student
    {
        return Student::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'nisn' => (string) random_int(10000000, 99999999),
            'gender' => $gender,
            'birth_date' => $birthDate,
            'entry_date' => $entryDate,
            'status' => $status,
        ]);
    }

    private function history(Student $student, StudyGroup $group, AcademicYear $academicYear): StudentClassHistory
    {
        return StudentClassHistory::create([
            'student_id' => $student->id,
            'study_group_id' => $group->id,
            'academic_year_id' => $academicYear->id,
            'is_active' => true,
            'join_date' => $academicYear->start_date->toDateString(),
            'attendance_number' => 1,
        ]);
    }
}
