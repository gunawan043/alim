<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudyGroupProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_rombel_baru_menyalin_mapel_dan_jp_dari_tingkat(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit JP', 'code' => 'UJP',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '55555555',
            'name' => 'Sekolah JP', 'school_level' => 'smp', 'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
        ]);

        $grade = GradeLevel::create([
            'school_id' => $school->id, 'level' => 7, 'name' => 'Kelas 7', 'code' => '7',
        ]);

        $subject = Subject::create([
            'school_id' => $school->id, 'code' => 'MTK', 'name' => 'Matematika',
            'category' => 'nasional', 'credit_hours' => 4,
        ]);

        GradeLevelSubject::create([
            'grade_level_id' => $grade->id,
            'academic_year_id' => $year->id,
            'subject_id' => $subject->id,
            'allocation_hours' => 4,
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Admin JP', 'email' => 'admin.jp@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(), 'user_id' => $user->id, 'school_id' => $school->id,
            'status_kepegawaian' => 'PTT', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($user)->post(route('user.study-groups.store', ['userId' => $user->id]), [
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'code' => '7A',
            'capacity' => 30,
            'is_active' => 1,
        ])->assertRedirect();

        $group = StudyGroup::where('school_id', $school->id)->where('name', '7A')->firstOrFail();
        $assignment = StudyGroupSubject::where('study_group_id', $group->id)
            ->where('subject_id', $subject->id)
            ->first();

        $this->assertNotNull($assignment, 'Mapel tingkat harus disalin ke rombel baru.');
        $this->assertSame(4.0, (float) $assignment->weekly_hours);
    }
}
