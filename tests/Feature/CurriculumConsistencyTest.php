<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\CurriculumConsistencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CurriculumConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_menemukan_selisih_jp_sk_dan_jadwal(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Mutu', 'code' => 'UMT',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '66666666',
            'name' => 'Sekolah Mutu', 'school_level' => 'smp', 'is_active' => true,
        ]);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
        ]);
        $grade = GradeLevel::create([
            'school_id' => $school->id, 'level' => 7, 'name' => 'Kelas 7', 'code' => '7',
        ]);
        $group = StudyGroup::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A',
            'capacity' => 30, 'is_active' => true,
        ]);
        $subject = Subject::create([
            'school_id' => $school->id, 'code' => 'MTK', 'name' => 'Matematika',
            'category' => 'nasional', 'credit_hours' => 4,
        ]);

        StudyGroupSubject::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'study_group_id' => $group->id, 'subject_id' => $subject->id,
            'weekly_hours' => 4, 'is_active' => true,
        ]);

        $teacher = User::create([
            'name' => 'Guru Uji', 'email' => 'guru.mutu@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId, 'decree_number' => 'SK-MUTU-1', 'decree_type' => 'guru',
            'title' => 'SK Uji Mutu', 'academic_year_id' => $year->id,
            'issued_date' => '2026-07-01', 'effective_date' => '2026-07-01',
            'school_id' => $school->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // SK hanya 2 JP, seharusnya 4.
        DB::table('teaching_assignments')->insert([
            'id' => (string) Str::uuid(), 'decree_id' => $decreeId, 'teacher_id' => $teacher->id, 'school_id' => $school->id,
            'academic_year_id' => $year->id, 'study_group_id' => $group->id,
            'subject_id' => $subject->id, 'weekly_hours' => 2, 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $report = app(CurriculumConsistencyService::class)->build([$school->id], $year->id, 'ganjil');

        $this->assertSame(1, $report['counts']['warning']);
        $this->assertStringContainsString('JP SK', implode(' ', $report['mismatches']->first()['issues']));
        $this->assertSame(0, $report['coverage']['buku_nilai']);
        $this->assertSame(0, $report['readiness']['students']);
    }
}
