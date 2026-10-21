<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Osipp\OsippActivity;
use App\Models\Osipp\OsippCoaching;
use App\Models\Osipp\OsippDocument;
use App\Models\Osipp\OsippEvaluation;
use App\Models\Osipp\OsippMember;
use App\Models\Osipp\OsippPeriod;
use App\Models\Osipp\OsippProgram;
use App\Models\Osipp\OsippQism;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OsippModuleSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_osipp_workspace_pages_and_core_workflows_are_available(): void
    {
        $workUnit = WorkUnit::create([
            'id' => (string) Str::uuid(),
            'name' => 'Satuan Pendidikan OSIPP',
            'code' => 'OSIPP-SMOKE',
            'is_active' => true,
        ]);
        $school = School::create([
            'work_unit_id' => $workUnit->id,
            'npsn' => '99990010',
            'name' => 'Sekolah Uji Modul OSIPP',
        ]);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $admin = User::create([
            'name' => 'Admin OSIPP Uji',
            'email' => 'osipp.smoke@example.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $admin->forceFill(['is_system_admin' => true])->save();

        $student = Student::create([
            'school_id' => $school->id,
            'nisn' => '9999001001',
            'name' => 'Santri OSIPP Uji',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $period = OsippPeriod::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Kepengurusan OSIPP 2026/2027',
            'start_date' => '2026-07-01',
            'status' => OsippPeriod::STATUS_ACTIVE,
            'created_by' => $admin->id,
        ]);

        $member = OsippMember::create([
            'period_id' => $period->id,
            'student_id' => $student->id,
            'position' => 'Ketua',
            'member_role' => OsippMember::ROLE_PENGURUS,
            'status' => OsippMember::STATUS_ACTIVE,
        ]);

        $program = OsippProgram::create([
            'period_id' => $period->id,
            'name' => 'Program Uji OSIPP',
            'responsible_member_id' => $member->id,
            'progress' => 10,
            'status' => OsippProgram::STATUS_RUNNING,
        ]);

        $activity = OsippActivity::create([
            'period_id' => $period->id,
            'program_id' => $program->id,
            'title' => 'Kegiatan Uji OSIPP',
            'activity_date' => '2026-08-10',
            'responsible_member_id' => $member->id,
            'status' => OsippActivity::STATUS_DRAFT,
        ]);

        OsippDocument::create([
            'period_id' => $period->id,
            'activity_id' => $activity->id,
            'document_type' => 'proposal',
            'title' => 'Proposal Uji OSIPP',
            'status' => OsippDocument::STATUS_DRAFT,
        ]);

        OsippCoaching::create([
            'period_id' => $period->id,
            'member_id' => $member->id,
            'coaching_type' => OsippCoaching::TYPE_INDIVIDUAL,
            'coaching_date' => '2026-08-01',
            'topic' => 'Pembinaan Uji',
            'status' => OsippCoaching::STATUS_COMPLETED,
        ]);

        OsippEvaluation::create([
            'period_id' => $period->id,
            'evaluation_date' => '2026-08-20',
            'scores' => ['perencanaan' => 4, 'pelaksanaan' => 4, 'administrasi' => 4, 'kepemimpinan' => 4, 'pelaporan' => 4],
            'total_score' => OsippEvaluation::computeTotal(['perencanaan' => 4, 'pelaksanaan' => 4, 'administrasi' => 4, 'kepemimpinan' => 4, 'pelaporan' => 4]),
            'grade' => 'B',
            'status' => OsippEvaluation::STATUS_DRAFT,
        ]);

        Storage::fake('local');

        $this->actingAs($admin)->withSession(['sa_school_id' => $school->id]);

        foreach ([
            'osipp.dashboard',
            'osipp.periode.index',
            'osipp.anggota.index',
            'osipp.program.index',
            'osipp.kegiatan.index',
            'osipp.administrasi.index',
            'osipp.pembinaan.index',
            'osipp.evaluasi.index',
        ] as $routeName) {
            $this->get(route($routeName, ['userId' => $admin->id, 'period_id' => $period->id]))
                ->assertOk();
        }

        $this->post(route('osipp.kegiatan.submit', ['userId' => $admin->id, 'activity' => $activity->id]), [
            'period_id' => $period->id,
        ])->assertRedirect();

        $this->assertSame(OsippActivity::STATUS_SUBMITTED, $activity->fresh()->status);

        $this->post(route('osipp.kegiatan.verify', ['userId' => $admin->id, 'activity' => $activity->id]), [
            'period_id' => $period->id,
            'verification_notes' => 'Dokumen kegiatan lengkap.',
        ])->assertRedirect();

        $verified = $activity->fresh();
        $this->assertSame(OsippActivity::STATUS_VERIFIED, $verified->status);
        $this->assertSame($admin->id, $verified->verified_by);

        $this->post(route('osipp.program.progress', ['userId' => $admin->id, 'program' => $program->id]), [
            'period_id' => $period->id,
            'progress' => 75,
        ])->assertRedirect();

        $this->assertSame(75, $program->fresh()->progress);

        $this->post(route('osipp.evaluasi.store', ['userId' => $admin->id]), [
            'period_id' => $period->id,
            'evaluation_type' => 'akhir',
            'evaluation_date' => '2026-08-25',
            'status' => 'draft',
            'scores' => ['perencanaan' => 5, 'pelaksanaan' => 4, 'administrasi' => 4, 'kepemimpinan' => 5, 'pelaporan' => 4],
        ])->assertRedirect();

        $this->assertDatabaseHas('osipp_evaluations', [
            'period_id' => $period->id,
            'grade' => 'B',
        ]);

        $this->post(route('osipp.periode.store', ['userId' => $admin->id]), [
            'name' => 'Periode Tambahan Uji',
            'start_date' => '2026-07-01',
            'status' => 'draft',
        ])->assertRedirect();

        $this->assertDatabaseHas('osipp_periods', [
            'school_id' => $school->id,
            'name' => 'Periode Tambahan Uji',
        ]);

        $student2 = Student::create([
            'school_id' => $school->id,
            'nisn' => '9999001002',
            'name' => 'Santri OSIPP Uji 2',
            'gender' => 'P',
            'status' => 'active',
        ]);

        $this->post(route('osipp.anggota.store', ['userId' => $admin->id]), [
            'period_id' => $period->id,
            'student_id' => $student2->id,
            'position' => 'Sekretaris',
            'member_role' => 'anggota',
            'status' => 'active',
        ])->assertRedirect();

        $member2 = OsippMember::query()->where('student_id', $student2->id)->firstOrFail();
        $this->assertSame('Sekretaris', $member2->position);

        $this->post(route('osipp.qism.store', ['userId' => $admin->id]), [
            'period_id' => $period->id,
            'name' => 'Qism Uji',
            'code' => 'QISM-UJI',
            'leader_member_id' => $member2->id,
            'member_ids' => [$member2->id],
            'is_active' => 1,
        ])->assertRedirect();

        $qism = OsippQism::query()->where('name', 'Qism Uji')->firstOrFail();
        $this->assertSame($qism->id, $member2->fresh()->qism_id);
        $this->assertSame(OsippMember::ROLE_KETUA_QISM, $member2->fresh()->member_role);

        $this->get(route('osipp.qism.index', ['userId' => $admin->id, 'period_id' => $period->id]))
            ->assertOk()
            ->assertSee('Qism Uji');

        $this->put(route('osipp.qism.update', ['userId' => $admin->id, 'qism' => $qism->id]), [
            'period_id' => $period->id,
            'name' => 'Qism Uji Update',
            'code' => 'QISM-UJI',
            'leader_member_id' => $member2->id,
            'member_ids' => [$member2->id],
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertSame('Qism Uji Update', $qism->fresh()->name);

        $this->post(route('osipp.program.store', ['userId' => $admin->id]), [
            'period_id' => $period->id,
            'name' => 'Program Baru Uji',
            'status' => 'planned',
            'progress' => 0,
        ])->assertRedirect();

        $this->post(route('osipp.administrasi.store', ['userId' => $admin->id]), [
            'period_id' => $period->id,
            'title' => 'Proposal Baru Uji',
            'document_type' => 'proposal',
            'activity_id' => $activity->id,
            'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $document = OsippDocument::query()->where('title', 'Proposal Baru Uji')->firstOrFail();
        $this->assertNotNull($document->file_path);
        Storage::disk('local')->assertExists($document->file_path);

        $this->post(route('osipp.pembinaan.store', ['userId' => $admin->id]), [
            'period_id' => $period->id,
            'coaching_type' => 'kelompok',
            'coaching_date' => '2026-08-26',
            'topic' => 'Pembinaan Kelompok Uji',
            'member_ids' => [$member->id, $member2->id],
            'status' => 'completed',
        ])->assertRedirect();

        $coaching = OsippCoaching::query()->where('topic', 'Pembinaan Kelompok Uji')->firstOrFail();
        $this->assertCount(2, $coaching->attendances);
    }
}
