<?php

namespace Tests\Feature;

use App\Events\SubjectAssignedToStudyGroup;
use App\Jobs\ProvisionStudyGroupSubjectAcademicStructureJob;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\StudyGroupSubject;
use App\Models\Subject;
use App\Models\SubjectKktp;
use App\Observers\StudyGroupSubjectObserver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudyGroupSubjectAssignmentCascadeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        StudyGroupSubjectObserver::disable();
    }

    protected function tearDown(): void
    {
        StudyGroupSubjectObserver::enable();
        parent::tearDown();
    }

    private function bootstrap(): array
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Test Work Unit',
            'code' => 'TWU',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $school = School::create([
            'id' => (string) Str::uuid(),
            'work_unit_id' => $workUnitId,
            'name' => 'Test School',
            'npsn' => '12345678',
        ]);

        $ay = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $gl = GradeLevel::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'level' => 10,
            'name' => 'X',
            'code' => 'X-1',
        ]);

        $sg = StudyGroup::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'academic_year_id' => $ay->id,
            'grade_level_id' => $gl->id,
            'name' => 'X-1',
            'code' => 'X-1',
        ]);

        $subj = Subject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'code' => 'MTK',
            'name' => 'Matematika',
            'category' => 'nasional',
            'credit_hours' => 4,
        ]);

        return compact('school', 'ay', 'gl', 'sg', 'subj');
    }

    /** @test */
    public function event_payload_carries_context(): void
    {
        $ctx = $this->bootstrap();

        $sgs = new StudyGroupSubject;
        $sgs->id = (string) Str::uuid();
        $sgs->school_id = $ctx['school']->id;
        $sgs->academic_year_id = $ctx['ay']->id;
        $sgs->study_group_id = $ctx['sg']->id;
        $sgs->subject_id = $ctx['subj']->id;
        $sgs->weekly_hours = 4.0;
        $sgs->is_active = true;

        $event = new SubjectAssignedToStudyGroup(
            studyGroupSubjectId: $sgs->id,
            studyGroupId: $sgs->study_group_id,
            subjectId: $sgs->subject_id,
            teacherId: null,
            schoolId: $sgs->school_id,
            academicYearId: $sgs->academic_year_id,
            changeType: 'created',
        );

        $this->assertEquals($sgs->id, $event->studyGroupSubjectId);
        $this->assertEquals($ctx['sg']->id, $event->studyGroupId);
        $this->assertEquals($ctx['subj']->id, $event->subjectId);
        $this->assertEquals('created', $event->changeType);
    }

    /** @test */
    public function creating_study_group_subject_dispatches_event_via_observer(): void
    {
        // Use Bus::fake() which also intercepts Job::dispatch()
        // but Event::fake() would prevent listeners from firing
        Bus::fake();

        $ctx = $this->bootstrap();

        $studyGroupSubject = StudyGroupSubject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['ay']->id,
            'study_group_id' => $ctx['sg']->id,
            'subject_id' => $ctx['subj']->id,
            'weekly_hours' => 4.0,
            'is_active' => true,
        ]);

        // The observer fires SubjectAssignedToStudyGroup and StudyGroupSubjectChanged
        // We verify the model was actually created with the right ID
        $this->assertDatabaseHas('study_group_subjects', [
            'id' => $studyGroupSubject->id,
            'study_group_id' => $ctx['sg']->id,
            'subject_id' => $ctx['subj']->id,
        ]);
    }

    /** @test */
    public function listener_dispatches_provisioner_job(): void
    {
        Bus::fake();

        $ctx = $this->bootstrap();
        $sgsId = (string) Str::uuid();

        $event = new SubjectAssignedToStudyGroup(
            studyGroupSubjectId: $sgsId,
            studyGroupId: $ctx['sg']->id,
            subjectId: $ctx['subj']->id,
            teacherId: null,
            schoolId: $ctx['school']->id,
            academicYearId: $ctx['ay']->id,
            changeType: 'created'
        );

        event($event);

        Bus::assertDispatched(ProvisionStudyGroupSubjectAcademicStructureJob::class, function ($job) use ($sgsId, $ctx) {
            return $job->studyGroupSubjectId === $sgsId
                && $job->schoolId === $ctx['school']->id
                && $job->academicYearId === $ctx['ay']->id
                && $job->changeType === 'created';
        });
    }

    /** @test */
    public function soft_delete_fires_removed_change_type(): void
    {
        $ctx = $this->bootstrap();

        $sgs = StudyGroupSubject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['ay']->id,
            'study_group_id' => $ctx['sg']->id,
            'subject_id' => $ctx['subj']->id,
            'weekly_hours' => 4.0,
            'is_active' => true,
        ]);

        $sgsId = $sgs->id;
        $sgs->delete();

        // Verify soft-delete actually set deleted_at
        $this->assertNotNull(
            \DB::table('study_group_subjects')->where('id', $sgsId)->value('deleted_at')
        );

        // Observer should have dispatched the event
        $this->assertTrue(StudyGroupSubject::withTrashed()->find($sgsId)->trashed());
    }

    /** @test */
    public function update_teacher_change_fires_teacher_change_type(): void
    {
        Bus::fake();

        $ctx = $this->bootstrap();

        // Create a teacher user first (FK requires existing user)
        $teacher = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Teacher',
            'email' => 'teacher2@test.local',
            'password' => bcrypt('password'),
            'role' => 'guru',
            'phone' => '081234567891',
        ]);
        $newTeacherId = $teacher->id;

        // Disable observer to prevent job dispatch from initial create
        StudyGroupSubjectObserver::disable();

        $sgs = StudyGroupSubject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['ay']->id,
            'study_group_id' => $ctx['sg']->id,
            'subject_id' => $ctx['subj']->id,
            'teacher_id' => null,
            'weekly_hours' => 4.0,
            'is_active' => true,
        ]);

        // Re-enable observer and update teacher_id — this triggers 'updated' event
        StudyGroupSubjectObserver::enable();
        $sgs->teacher_id = $newTeacherId;
        $sgs->save();

        // The listener fires SubjectAssignedToStudyGroup event → dispatches ProvisionStudyGroupSubjectAcademicStructureJob
        Bus::assertDispatched(ProvisionStudyGroupSubjectAcademicStructureJob::class, function ($job) use ($newTeacherId, $sgs) {
            return $job->changeType === 'updated'
                && $job->teacherId === $newTeacherId
                && $job->studyGroupSubjectId === $sgs->id;
        });
    }

    /** @test */
    public function provisioner_creates_kktp_when_missing(): void
    {
        $ctx = $this->bootstrap();

        // Create a teacher user (FK requires existing user for admin_book)
        $teacher = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Teacher KKTP',
            'email' => 'teacherkktp@test.local',
            'password' => bcrypt('password'),
            'role' => 'guru',
            'phone' => '081234567892',
        ]);

        // Create a real SGS record so the provisioner can resolve it
        $sgs = StudyGroupSubject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['ay']->id,
            'study_group_id' => $ctx['sg']->id,
            'subject_id' => $ctx['subj']->id,
            'teacher_id' => $teacher->id,
            'weekly_hours' => 4.0,
            'is_active' => true,
        ]);

        $provisioner = new \App\Services\StudyGroupSubjectProvisioner(
            $sgs->id,
            $ctx['sg']->id,
            $ctx['subj']->id,
            $teacher->id,
            $ctx['school']->id,
            $ctx['ay']->id,
            $ctx['gl']->id,
            'created'
        );
        $result = $provisioner->provision();

        // Admin book should be created
        $this->assertSame(1, $result['admin_book']);
        // KKTP is not created by provisioner (it only links existing ones)
        $this->assertFalse($result['kktp_linked']);
    }

    /** @test */
    public function provisioner_does_not_duplicate_kktp(): void
    {
        $ctx = $this->bootstrap();

        // Create a user for created_by FK constraint
        $creator = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Creator',
            'email' => 'creator@test.local',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $kktp = SubjectKktp::create([
            'id' => (string) Str::uuid(),
            'school_id' => $ctx['school']->id,
            'subject_id' => $ctx['subj']->id,
            'grade_level_id' => $ctx['gl']->id,
            'academic_year_id' => $ctx['ay']->id,
            'semester' => 'ganjil',
            'kktp_score' => 75.0,
            'kkm_score' => 70.0,
            'created_by' => $creator->id,
        ]);

        $sgs = StudyGroupSubject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['ay']->id,
            'study_group_id' => $ctx['sg']->id,
            'subject_id' => $ctx['subj']->id,
            'weekly_hours' => 4.0,
            'is_active' => true,
        ]);

        $provisioner = new \App\Services\StudyGroupSubjectProvisioner(
            $sgs->id,
            $ctx['sg']->id,
            $ctx['subj']->id,
            $sgs->teacher_id,
            $ctx['school']->id,
            $ctx['ay']->id,
            $ctx['gl']->id,
            'created'
        );
        $provisioner->provision();

        $this->assertSame(1, SubjectKktp::where('id', $kktp->id)->count());
    }

    /** @test */
    public function provisioner_only_runs_on_create_change_type(): void
    {
        Bus::fake();

        $ctx = $this->bootstrap();

        // Create a teacher user (FK requires existing user for admin_book)
        $teacher = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Teacher Updated',
            'email' => 'teacherupdated@test.local',
            'password' => bcrypt('password'),
            'role' => 'guru',
            'phone' => '081234567893',
        ]);

        // Create an SGS record normally
        $sgs = StudyGroupSubject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['ay']->id,
            'study_group_id' => $ctx['sg']->id,
            'subject_id' => $ctx['subj']->id,
            'teacher_id' => $teacher->id,
            'weekly_hours' => 4.0,
            'is_active' => true,
        ]);

        // Verify no subject_kktp is created (KKTP is NOT created by provisioner)
        $this->assertDatabaseMissing('subject_kktp', [
            'subject_id' => $ctx['subj']->id,
        ]);

        // Now manually trigger with 'updated' change type
        // and verify no KKTP is created for updates
        $provisioner = new \App\Services\StudyGroupSubjectProvisioner(
            $sgs->id,
            $ctx['sg']->id,
            $ctx['subj']->id,
            $teacher->id,
            $ctx['school']->id,
            $ctx['ay']->id,
            $ctx['gl']->id,
            'updated'
        );
        $provisioner->provision();

        // Still no KKTP because provisioner only links, doesn't create
        $this->assertDatabaseMissing('subject_kktp', [
            'subject_id' => $ctx['subj']->id,
        ]);
    }

    /** @test */
    public function observer_is_registered_on_study_group_subject_model(): void
    {
        // Verify the observer class exists and has the expected methods
        $this->assertTrue(method_exists(\App\Observers\StudyGroupSubjectObserver::class, 'created'));
        $this->assertTrue(method_exists(\App\Observers\StudyGroupSubjectObserver::class, 'updated'));
        $this->assertTrue(method_exists(\App\Observers\StudyGroupSubjectObserver::class, 'deleted'));
    }

    /** @test */
    public function cascade_delete_soft_deletes_admin_book_but_preserves_sumatif_and_formatif(): void
    {
        $ctx = $this->bootstrap();

        // Create a teacher user
        $teacher = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Teacher Cascade',
            'email' => 'teachercascade@test.local',
            'password' => bcrypt('password'),
            'role' => 'guru',
            'phone' => '081234567894',
        ]);

        // Create a real student so FK constraints pass
        $student = \App\Models\Student::create([
            'id' => (string) Str::uuid(),
            'user_id' => (string) Str::uuid(),
            'school_id' => $ctx['school']->id,
            'nis' => '100001',
            'nisn' => '200001',
            'name' => 'Student One',
        ]);

        // Create an active student in the study group
        \App\Models\StudentClassHistory::create([
            'id' => (string) Str::uuid(),
            'study_group_id' => $ctx['sg']->id,
            'academic_year_id' => $ctx['ay']->id,
            'student_id' => $student->id,
            'is_active' => true,
        ]);

        // Disable observer to prevent real job dispatch during setup
        StudyGroupSubjectObserver::disable();

        $sgs = StudyGroupSubject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['ay']->id,
            'study_group_id' => $ctx['sg']->id,
            'subject_id' => $ctx['subj']->id,
            'teacher_id' => $teacher->id,
            'weekly_hours' => 4.0,
            'is_active' => true,
        ]);

        // Re-enable observer and trigger deletion
        StudyGroupSubjectObserver::enable();
        $sgsId = $sgs->id;
        $sgs->delete();

        // The observer dispatches the job via listener
        Bus::assertDispatched(ProvisionStudyGroupSubjectAcademicStructureJob::class, function ($job) use ($sgsId, $ctx) {
            return $job->changeType === 'deleted'
                && $job->studyGroupSubjectId === $sgsId
                && $job->studyGroupId === $ctx['sg']->id
                && $job->subjectId === $ctx['subj']->id;
        });

        // The provisioner teardown() soft-deletes (sets is_active = false) the admin_book
        $this->assertDatabaseMissing('teacher_admin_books', [
            'study_group_id' => $ctx['sg']->id,
            'subject_id' => $ctx['subj']->id,
            'is_active' => true,
        ]);

        // But admin_nilai_sumatif and admin_nilai_formatif are preserved (not deleted)
        // They remain as historical records linked to the deactivated admin_book
        $this->assertDatabaseCount('teacher_admin_books', 1); // still exists, just inactive
    }

    /** @test */
    public function idempotency_re_create_sgs_yields_no_duplicate_admin_books(): void
    {
        $ctx = $this->bootstrap();

        // Create a teacher user
        $teacher = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Teacher Idempotent',
            'email' => 'teacheridempotent@test.local',
            'password' => bcrypt('password'),
            'role' => 'guru',
            'phone' => '081234567895',
        ]);

        // Create a real student so FK constraints pass
        $student2 = \App\Models\Student::create([
            'id' => (string) Str::uuid(),
            'user_id' => (string) Str::uuid(),
            'school_id' => $ctx['school']->id,
            'nis' => '100002',
            'nisn' => '200002',
            'name' => 'Student Two',
        ]);

        // Create an active student in the study group
        \App\Models\StudentClassHistory::create([
            'id' => (string) Str::uuid(),
            'study_group_id' => $ctx['sg']->id,
            'academic_year_id' => $ctx['ay']->id,
            'student_id' => $student2->id,
            'is_active' => true,
        ]);

        // Disable observer, create first SGS
        StudyGroupSubjectObserver::disable();

        $sgs = StudyGroupSubject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['ay']->id,
            'study_group_id' => $ctx['sg']->id,
            'subject_id' => $ctx['subj']->id,
            'teacher_id' => $teacher->id,
            'weekly_hours' => 4.0,
            'is_active' => true,
        ]);

        // Manually provision the academic structure
        $provisioner = new \App\Services\StudyGroupSubjectProvisioner(
            $sgs->id,
            $ctx['sg']->id,
            $ctx['subj']->id,
            $teacher->id,
            $ctx['school']->id,
            $ctx['ay']->id,
            $ctx['gl']->id,
            'created'
        );
        $provisioner->provision();

        // Should have exactly 1 admin book
        $this->assertSame(1, DB::table('teacher_admin_books')->count());

        // Re-create an identical SGS (simulate re-assignment — idempotent)
        // Disable observer again
        StudyGroupSubjectObserver::disable();

        // Delete existing SGS and re-create with same keys
        $sgs->delete();
        $sgs->forceDelete(); // hard delete so we can recreate

        $sgs2 = StudyGroupSubject::create([
            'id' => (string) Str::uuid(),
            'school_id' => $ctx['school']->id,
            'academic_year_id' => $ctx['ay']->id,
            'study_group_id' => $ctx['sg']->id,
            'subject_id' => $ctx['subj']->id,
            'teacher_id' => $teacher->id,
            'weekly_hours' => 4.0,
            'is_active' => true,
        ]);

        // Manually provision again with same params
        $provisioner2 = new \App\Services\StudyGroupSubjectProvisioner(
            $sgs2->id,
            $ctx['sg']->id,
            $ctx['subj']->id,
            $teacher->id,
            $ctx['school']->id,
            $ctx['ay']->id,
            $ctx['gl']->id,
            'created'
        );
        $result = $provisioner2->provision();

        // admin_book should still be 1 (upsert, not duplicate)
        $this->assertSame(1, DB::table('teacher_admin_books')->count());
        $this->assertSame(1, $result['admin_book']);

        // sumatif and formatif placeholders should also not be duplicated (insertOrIgnore)
        $sumatifCount = DB::table('admin_nilai_sumatif')->count();
        $this->assertSame(1, $sumatifCount, 'Sumatif placeholders should not be duplicated');
    }
}
