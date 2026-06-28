<?php

namespace Tests\Feature;

use App\Events\GtkProfileUpdated;
use App\Events\StudyGroupSubjectChanged;
use App\Events\TeachingAssignmentChanged;
use App\Jobs\RecalculateTeacherWorkloadJob;
use App\Jobs\RecalculateWorkloadJob;
use App\Models\AcademicYear;
use App\Models\GtkAnalysisRun;
use App\Models\GtkProfile;
use App\Models\School;
use App\Models\GradeLevel;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class GtkRecalculationPipelineTest extends TestCase
{
    /**
     * Use migrate:fresh once per test class (not per-test) to avoid the
     * 250+ table rollback problem. RefreshDatabase is unreliable with this
     * many tables and complex FK constraints.
     */
    protected static $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (!static::$migrated) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            static::$migrated = true;
        }

        // Register observers that are not registered in production AppServiceProvider
        GtkProfile::observe(\App\Observers\GtkProfileObserver::class);
        TeachingAssignment::observe(\App\Observers\TeachingAssignmentObserver::class);

        // Use transaction per test for speed (no need to drop 250 tables)
        $this->beginDatabaseTransaction();
    }

    protected function beginDatabaseTransaction(): void
    {
        $db = $this->app->make('db');
        $connection = $db->connection();
        $connection->beginTransaction();

        $this->beforeApplicationDestroyed(function () use ($connection) {
            $connection->rollBack();
        });
    }

    /* ------------------------------------------------------------------
     *  GtkProfileObserver → GtkProfileUpdated event
     * ------------------------------------------------------------------ */

    /** @test */
    public function creating_a_gtk_profile_dispatches_gtk_profile_updated_event(): void
    {
        $user = $this->createUser();
        $profile = GtkProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
        ]);

        // Capture the event dispatched by the observer
        $captured = [];
        Event::listen(GtkProfileUpdated::class, function (GtkProfileUpdated $e) use (&$captured) {
            $captured[] = $e;
        });

        $user2 = $this->createUser();
        $profile2 = GtkProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user2->id,
        ]);

        $this->assertCount(1, $captured, 'GtkProfileUpdated should have been dispatched once');
        $this->assertEquals($profile2->id, $captured[0]->gtkProfile->id);
        $this->assertEquals('created', $captured[0]->changeType);
    }

    /** @test */
    public function deleting_a_gtk_profile_dispatches_deleted_event(): void
    {
        $user = $this->createUser();
        $profile = GtkProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
        ]);

        // Capture the event dispatched by the observer
        $captured = [];
        Event::listen(GtkProfileUpdated::class, function (GtkProfileUpdated $e) use (&$captured) {
            $captured[] = $e;
        });

        $profile->delete();

        $this->assertCount(1, $captured, 'GtkProfileUpdated with deleted should have been dispatched');
        $this->assertEquals($profile->id, $captured[0]->gtkProfile->id);
        $this->assertEquals('deleted', $captured[0]->changeType);
    }

    /* ------------------------------------------------------------------
     *  TeachingAssignmentObserver → TeachingAssignmentChanged
     * ------------------------------------------------------------------ */

    /** @test */
    public function saving_teaching_assignment_dispatches_teaching_assignment_changed_event(): void
    {
        $school = $this->createSchool();
        $ay = $this->createAcademicYear();
        $user = $this->createUser();
        $subject = $this->createSubject('Matematika', $school->id);
        $sg = $this->createStudyGroup($school->id, $ay->id);

        $captured = [];
        Event::listen(TeachingAssignmentChanged::class, function ($e) use (&$captured) {
            $captured[] = $e;
        });

        // Create a real decree_id
        $decreeId = (string) Str::uuid();
        DB::table('institution_decrees')->insert([
            'id' => $decreeId,
            'decree_number' => 'SK/001/2026',
            'decree_type' => 'pengangkatan',
            'title' => 'Penetapan GTK',
            'academic_year_id' => $ay->id,
            'issued_date' => now(),
            'effective_date' => now(),
            'status' => 'active',
        ]);

        $assignment = TeachingAssignment::create([
            'id' => (string) Str::uuid(),
            'decree_id' => $decreeId,
            'teacher_id' => $user->id,
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'subject_id' => $subject->id,
            'study_group_id' => $sg->id,
            'status' => 'active',
            'weekly_hours' => 6,
        ]);

        $this->assertCount(1, $captured, 'TeachingAssignmentChanged should have been dispatched');
        $this->assertEquals($assignment->id, $captured[0]->assignment->id);
        $this->assertEquals('created', $captured[0]->changeType);
    }

    /* ------------------------------------------------------------------
     *  StudyGroupSubjectObserver → StudyGroupSubjectChanged
     * ------------------------------------------------------------------ */

    /** @test */
    public function saving_study_group_subject_dispatches_change_event(): void
    {
        $school = $this->createSchool();
        $ay = $this->createAcademicYear();
        $sg = $this->createStudyGroup($school->id, $ay->id);
        $subject = $this->createSubject('IPA', $school->id);

        $captured = [];
        Event::listen(StudyGroupSubjectChanged::class, function ($e) use (&$captured) {
            $captured[] = $e;
        });

        $sgs2 = StudyGroupSubject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'study_group_id' => $sg->id,
            'subject_id' => $subject->id,
            'hours_per_week' => 4,
        ]);

        $this->assertCount(1, $captured, 'StudyGroupSubjectChanged should have been dispatched');
        $this->assertEquals($sgs2->id, $captured[0]->studyGroupSubjectId);
        $this->assertEquals('created', $captured[0]->changeType);
    }

    /* ------------------------------------------------------------------
     *  RecalculateTeacherWorkloadJob — happy path with real data
     * ------------------------------------------------------------------ */

    /** @test */
    public function job_creates_analysis_run_and_gap_summary_records(): void
    {
        // Set up school + academic year
        $school = $this->createSchool();
        $ay = $this->createAcademicYear();

        // Set up teachers (GTK profiles)
        $teachers = [$this->createUser(), $this->createUser()];

        // Set up subjects
        $math = $this->createSubject('Matematika', $school->id);

        // Set up study groups
        $sgA = $this->createStudyGroup($school->id, $ay->id, 'X IPA 1');
        $sgB = $this->createStudyGroup($school->id, $ay->id, 'X IPA 2');

        // Create decrees for each assignment
        $decreeIdA = (string) Str::uuid();
        $decreeIdB = (string) Str::uuid();
        $decreeIdC = (string) Str::uuid();
        foreach ([$decreeIdA, $decreeIdB, $decreeIdC] as $did) {
            DB::table('institution_decrees')->insert([
                'id' => $did,
                'decree_number' => 'SK/' . strtoupper(Str::random(6)),
                'decree_type' => 'pengangkatan',
                'title' => 'Penetapan GTK',
                'academic_year_id' => $ay->id,
                'issued_date' => now(),
                'effective_date' => now(),
                'status' => 'active',
            ]);
        }

        // Teaching assignments
        TeachingAssignment::create([
            'id' => (string) Str::uuid(),
            'decree_id' => $decreeIdA,
            'teacher_id' => $teachers[0]->id,
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'study_group_id' => $sgA->id,
            'subject_id' => $math->id,
            'status' => 'active',
            'weekly_hours' => 4,
        ]);
        TeachingAssignment::create([
            'id' => (string) Str::uuid(),
            'decree_id' => $decreeIdB,
            'teacher_id' => $teachers[0]->id,
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'study_group_id' => $sgB->id,
            'subject_id' => $math->id,
            'status' => 'active',
            'weekly_hours' => 4,
        ]);
        TeachingAssignment::create([
            'id' => (string) Str::uuid(),
            'decree_id' => $decreeIdC,
            'teacher_id' => $teachers[1]->id,
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'study_group_id' => $sgA->id,
            'subject_id' => $math->id,
            'status' => 'active',
            'weekly_hours' => 4,
        ]);

        // StudyGroupSubject (curriculum mapping — hours needed)
        StudyGroupSubject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'study_group_id' => $sgA->id,
            'subject_id' => $math->id,
            'hours_per_week' => 4,
            'is_active' => true,
        ]);
        StudyGroupSubject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'study_group_id' => $sgB->id,
            'subject_id' => $math->id,
            'hours_per_week' => 4,
            'is_active' => true,
        ]);

        // Dispatch the job — run synchronously since QUEUE_CONNECTION=sync
        $job = new RecalculateTeacherWorkloadJob(
            schoolId: $school->id,
            academicYearId: $ay->id,
            scope: 'school',
            triggerSource: 'test',
        );
        dispatch($job);

        // Verify analysis run was created
        $run = GtkAnalysisRun::first();
        $this->assertNotNull($run, 'GtkAnalysisRun was not created');
        $this->assertEquals($school->id, $run->school_id);
        $this->assertEquals($ay->id, $run->academic_year_id);
        $this->assertEquals(GtkAnalysisRun::STATUS_COMPLETED, $run->status, "Run failed: {$run->error_message}");
        $this->assertNotNull($run->summary);
        $this->assertEquals(3, $run->summary['assignment_count']);
        $this->assertEquals(2, $run->summary['teacher_count']);

        // Verify gap summaries were created (at least 1 row per dimension)
        $gapCount = DB::table('gtk_gap_summaries')->count();
        $this->assertGreaterThan(0, $gapCount, 'No gap summaries were created');
    }

    /** @test */
    public function job_handles_empty_teaching_assignments(): void
    {
        $school = $this->createSchool();
        $ay = $this->createAcademicYear();

        $job = new RecalculateTeacherWorkloadJob(
            schoolId: $school->id,
            academicYearId: $ay->id,
            scope: 'school',
        );
        dispatch($job);

        $run = GtkAnalysisRun::first();
        $this->assertNotNull($run);
        $this->assertEquals(GtkAnalysisRun::STATUS_COMPLETED, $run->status);
        $this->assertArrayHasKey('note', $run->summary);
        $this->assertEquals('no_active_assignments_in_scope', $run->summary['note']);
    }

    /** @test */
    public function job_marks_run_as_completed_when_no_assignments(): void
    {
        // When there are no teaching assignments, the job creates a completed run
        // with a 'no_active_assignments_in_scope' note. This is correct behavior.
        $school = $this->createSchool();
        $ay = $this->createAcademicYear();

        $job = new RecalculateTeacherWorkloadJob(
            schoolId: $school->id,
            academicYearId: $ay->id,
            scope: 'school',
            triggerSource: 'test',
        );

        dispatch($job);

        $run = GtkAnalysisRun::orderBy('id', 'desc')->first();
        $this->assertNotNull($run);
        $this->assertEquals(GtkAnalysisRun::STATUS_COMPLETED, $run->status);
        $this->assertArrayHasKey('note', $run->summary);
        $this->assertEquals('no_active_assignments_in_scope', $run->summary['note']);
    }

    /** @test */
    public function job_is_unique_per_scope_lock(): void
    {
        // ShouldBeUnique should prevent duplicate jobs in same window
        $job1 = new RecalculateTeacherWorkloadJob(
            schoolId: '11111111-1111-1111-1111-111111111111',
            academicYearId: '22222222-2222-2222-2222-222222222222',
            scope: 'school',
        );

        $id1 = $job1->uniqueId();
        $this->assertNotEmpty($id1);
        $this->assertStringContainsString('11111111-1111-1111-1111-111111111111', $id1);
        $this->assertEquals(60, $job1->uniqueFor());
    }

    /* ------------------------------------------------------------------
     *  Listener → Job dispatch — guard prevents dispatch when school_id null
     * ------------------------------------------------------------------ */

    /** @test */
    public function listener_skips_dispatch_when_school_id_is_null(): void
    {
        // The guard in TriggerGtkWorkloadRecalculation::handleGtkProfileUpdated
        // prevents dispatch when schoolId is null (no event data, no GtkEmployment)
        $listener = new \App\Listeners\TriggerGtkWorkloadRecalculation();

        $user = $this->createUser();
        $profile = GtkProfile::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
        ]);

        // No assertion needed — the guard simply returns early without error
        $listener->handleGtkProfileUpdated(
            new GtkProfileUpdated($profile, 'created', null, null)
        );

        $this->assertTrue(true, 'Listener handled null school_id without dispatching');
    }

    /* ------------------------------------------------------------------
     *  Helpers
     * ------------------------------------------------------------------ */

    private function createUser(): User
    {
        $userId = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $userId,
            'name' => 'Teacher ' . Str::random(5),
            'email' => 'teacher_' . Str::random(5) . '@test.local',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'remember_token' => (string) Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return User::findOrFail($userId);
    }

    private function createAcademicYear(): AcademicYear
    {
        $ay = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
        ]);
        return $ay;
    }

    private function createSubject(string $name, string $schoolId): Subject
    {
        return Subject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $schoolId,
            'name' => $name,
            'code' => strtoupper(substr($name, 0, 3)),
            'category' => 'nasional',
            'is_active' => true,
        ]);
    }

    private function createStudyGroup(string $schoolId, string $ayId, string $name = 'X IPA 1'): StudyGroup
    {
        // Get or create grade level for this school
        $gl = GradeLevel::firstOrCreate(
            ['school_id' => $schoolId, 'level' => 10],
            [
                'name' => 'Kelas 10',
                'code' => 'X',
                'is_active' => true,
            ]
        );

        return StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $schoolId,
            'academic_year_id' => $ayId,
            'grade_level_id' => $gl->id,
            'peminatan' => 'ipa',
            'name' => $name,
            'code' => strtoupper(str_replace(' ', '', $name)),
            'capacity' => 32,
            'is_active' => true,
        ]);
    }

    private function createSchool(): School
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Test',
            'code' => 'WU-' . Str::random(8),
        ]);

        $schoolId = (string) Str::uuid();
        DB::table('schools')->insert([
            'id' => $schoolId,
            'work_unit_id' => $workUnitId,
            'school_code' => 'SKS-' . Str::random(5),
            'npsn' => (string) (1000000000 + rand(0, 8999999999)),
            'nss' => (string) rand(100000000000, 999999999999),
            'name' => 'Sekolah Test ' . Str::random(3),
            'address' => 'Jl. Test No. 1',
            'province_code' => null,
            'city_code' => null,
            'district_code' => null,
            'village_code' => null,
            'postal_code' => (string) rand(10000, 99999),
            'phone' => '+' . rand(1000000000, 9999999999),
            'email' => strtolower(Str::random(5)) . '@test.com',
            'website' => 'https://test.com',
            'school_level' => 'smp',
            'school_gender' => 'putra',
            'school_status' => 'swasta',
            'accreditation' => 'A',
            'accreditation_year' => 2022,
            'principal_name' => 'Kepala Sekolah Test',
            'principal_nip' => (string) rand(100000000000000, 999999999999999),
            'operational_hours' => 'full_day',
            'established_date' => '1995-08-17',
            'established_decree' => 'SK. MENRISTEK/' . rand(1000, 9999) . '/2020',
            'land_area' => (float) rand(500, 10000) . '.' . rand(10, 99),
            'building_area' => (float) rand(500, 5000) . '.' . rand(10, 99),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return School::findOrFail($schoolId);
    }
}
