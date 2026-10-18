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
use App\Services\KurikulumAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class KurikulumAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_hanya_boleh_menyusun_mapel_yang_diampu(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Mapel', 'code' => 'UMP',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '40404040',
            'name' => 'Sekolah Uji Mapel',
        ]);

        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $subjectA = Subject::create([
            'school_id' => $school->id, 'code' => 'FIQ', 'name' => 'Fiqih',
            'credit_hours' => 2, 'is_active' => true,
        ]);
        $subjectB = Subject::create([
            'school_id' => $school->id, 'code' => 'BIO', 'name' => 'Biologi',
            'credit_hours' => 2, 'is_active' => true,
        ]);

        $teacher = User::create([
            'name' => 'Guru Mapel', 'email' => 'guru.mapel@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);

        $grade = GradeLevel::create([
            'school_id' => $school->id, 'level' => 7, 'name' => 'Kelas 7',
            'fase' => 'D', 'is_active' => true,
        ]);

        $group = StudyGroup::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A', 'is_active' => true,
        ]);

        $decree = InstitutionDecree::create([
            'decree_number' => 'SK-UJI-001',
            'decree_type' => 'SK Pembagian Tugas',
            'title' => 'SK Uji Mapel',
            'academic_year_id' => $year->id,
            'school_id' => $school->id,
            'issued_date' => now()->toDateString(),
            'effective_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        TeachingAssignment::create([
            'decree_id' => $decree->id,
            'teacher_id' => $teacher->id,
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'study_group_id' => $group->id,
            'subject_id' => $subjectA->id,
            'status' => 'active',
        ]);

        $access = app(KurikulumAccess::class);

        $this->assertSame([$subjectA->id], $access->taughtSubjectIds($teacher, $school->id, $year->id));
        $this->assertTrue($access->canAuthorSubject($teacher, $subjectA->id, $year->id, $school->id));
        $this->assertFalse($access->canAuthorSubject($teacher, $subjectB->id, $year->id, $school->id));

        $editor = User::create([
            'name' => 'Editor Kurikulum', 'email' => 'editor.kurikulum@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $this->assertNull($access->taughtSubjectIds($editor, $school->id, $year->id));
        $this->assertTrue($access->canAuthorSubject($editor, $subjectB->id, $year->id, $school->id));
    }
}
