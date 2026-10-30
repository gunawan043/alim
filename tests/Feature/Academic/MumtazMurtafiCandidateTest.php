<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\MumtazMurtafiCandidate;
use App\Models\RaportRegistration;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\Academic\MumtazMurtafiCandidateService;
use App\Services\Arsip\RaporPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MumtazMurtafiCandidateTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_rapor_creates_mumtaz_candidate(): void
    {
        [$admin, $school, $year, $group, $student] = $this->fixtures();

        $raporPdf = Mockery::mock(RaporPdfService::class);
        $raporPdf->shouldReceive('availableSubjects')->once()->andReturn(collect([
            (object) ['id' => 'subject-demo'],
        ]));
        $raporPdf->shouldReceive('summariesForStudents')->once()->andReturn([
            $student->id => [
                'nilai' => 96.4,
                'predikat' => "Mumtaz Murtafi'",
                'rank' => 1,
            ],
        ]);

        $service = new MumtazMurtafiCandidateService($raporPdf);
        $candidates = $service->syncForPublishedRapor(
            $group,
            $year->id,
            'ganjil',
            $admin->id,
        );

        $this->assertCount(1, $candidates);
        $this->assertDatabaseHas('mumtaz_murtafi_candidates', [
            'student_id' => $student->id,
            'school_id' => $school->id,
            'status' => MumtazMurtafiCandidate::STATUS_PENDING,
            'class_rank' => 1,
        ]);
    }

    public function test_sync_published_rapor_backfills_candidate(): void
    {
        [$admin, $school, $year, $group, $student] = $this->fixtures();

        RaportRegistration::create([
            'student_id' => $student->id,
            'study_group_id' => $group->id,
            'academic_year_id' => $year->id,
            'semester' => 'ganjil',
            'status' => 'finalized',
            'is_published' => true,
            'published_at' => now(),
            'published_by' => $admin->id,
        ]);

        $raporPdf = Mockery::mock(RaporPdfService::class);
        $raporPdf->shouldReceive('availableSubjects')->once()->andReturn(collect([
            (object) ['id' => 'subject-demo'],
        ]));
        $raporPdf->shouldReceive('summariesForStudents')->once()->andReturn([
            $student->id => [
                'nilai' => 95.2,
                'predikat' => "Mumtaz Murtafi'",
                'rank' => 1,
            ],
        ]);

        $result = (new MumtazMurtafiCandidateService($raporPdf))->syncPublishedRapors(
            $school->id,
            $year->id,
            $admin->id,
        );

        $this->assertSame(1, $result['candidates']);
        $this->assertDatabaseHas('mumtaz_murtafi_candidates', [
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'semester' => 'ganjil',
            'status' => MumtazMurtafiCandidate::STATUS_PENDING,
        ]);
    }

    public function test_candidate_can_be_posted_as_student_achievement(): void
    {
        [$admin, $school, $year, $group, $student] = $this->fixtures();

        $candidate = MumtazMurtafiCandidate::create([
            'student_id' => $student->id,
            'school_id' => $school->id,
            'study_group_id' => $group->id,
            'academic_year_id' => $year->id,
            'semester' => 'ganjil',
            'average_score' => 96.4,
            'class_rank' => 1,
            'status' => MumtazMurtafiCandidate::STATUS_PENDING,
            'generated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('user.student-achievement.candidates.index', ['userId' => $admin->id]))
            ->assertOk()
            ->assertSee('Kandidat Mumtaz Murtafi')
            ->assertSee('filter-badge', false)
            ->assertSee('table-freeze', false);

        $this->actingAs($admin)
            ->get(route('user.student-achievement.candidates.show', [
                'userId' => $admin->id,
                'uuid' => $candidate->id,
            ]))
            ->assertOk()
            ->assertSee('Posting ke Prestasi Santri');

        $this->actingAs($admin)
            ->post(route('user.student-achievement.candidates.post', [
                'userId' => $admin->id,
                'uuid' => $candidate->id,
            ]), [
                'event_name' => "Mumtaz Murtafi' — Rapor Ganjil 2026/2027",
                'event_date' => now()->toDateString(),
                'organizer' => $school->name,
                'notify_wali' => 0,
                'notify_dormitory' => 0,
            ])
            ->assertRedirect(route('user.student-achievement.candidates.index', ['userId' => $admin->id]));

        $candidate->refresh();
        $this->assertSame(MumtazMurtafiCandidate::STATUS_POSTED, $candidate->status);
        $this->assertNotNull($candidate->student_achievement_id);

        $this->assertDatabaseHas('student_achievements', [
            'id' => $candidate->student_achievement_id,
            'student_id' => $student->id,
            'position' => 'mumtaz_murtafi',
            'achievement_type' => 'akademik',
        ]);
    }

    private function fixtures(): array
    {
        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);
        $school = School::factory()->create();
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $grade = GradeLevel::create([
            'school_id' => $school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'code' => 'VII',
            'is_active' => true,
        ]);
        $group = StudyGroup::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'code' => '7A',
            'is_active' => true,
        ]);
        $student = Student::create([
            'school_id' => $school->id,
            'name' => 'Santri Mumtaz',
            'nisn' => '7770000001',
            'gender' => 'L',
            'status' => 'active',
        ]);
        StudentClassHistory::create([
            'student_id' => $student->id,
            'study_group_id' => $group->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);

        return [$admin, $school, $year, $group, $student];
    }
}
