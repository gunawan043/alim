<?php

namespace Tests\Feature\Dormitory;

use App\Imports\StudentAchievementImport;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryPointCatalog;
use App\Models\DormitoryResident;
use App\Models\DormitoryReward;
use App\Models\DormitoryRewardRequest;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\GtkEmployment;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentMahrom;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\Boarding\DormitoryPolicyAccess;
use Database\Seeders\DormitoryPointSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class DormitoryRewardApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_achievement_creates_pending_request_without_reward_or_points(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [$admin, $dorm, $student, $year] = $this->fixtures();

        $this->actingAs($admin);

        $achievement = $this->achievement($student, $year, [
            'event_name' => 'Olimpiade Matematika Provinsi',
            'level' => 'provinsi',
            'position' => 'juara_1',
        ], notify: false);

        $request = DormitoryRewardRequest::where('student_achievement_id', $achievement->id)->firstOrFail();

        $this->assertSame(DormitoryRewardRequest::STATUS_PENDING, $request->status);
        $this->assertSame($dorm->id, $request->dormitory_id);
        $this->assertSame('R-14', $request->pointCatalog?->code);
        $this->assertSame(70, $request->points);
        $this->assertDatabaseMissing('dormitory_rewards', [
            'source_achievement_id' => $achievement->id,
        ]);
        $this->assertDatabaseMissing('dormitory_point_ledger', [
            'reference_type' => 'dormitory_reward',
        ]);
    }

    public function test_achievement_without_active_dormitory_does_not_create_request(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [$admin, , , $year] = $this->fixtures();

        $school = School::firstOrFail();
        $student = Student::create([
            'school_id' => $school->id,
            'name' => 'Santri Non Asrama',
            'nisn' => '55500001',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->actingAs($admin);
        $achievement = $this->achievement($student, $year, [], notify: false);

        $this->assertDatabaseMissing('dormitory_reward_requests', [
            'student_achievement_id' => $achievement->id,
        ]);
    }

    public function test_approval_creates_reward_ledger_and_links_source(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [$admin, $dorm, $student, $year] = $this->fixtures();

        $this->actingAs($admin);
        $achievement = $this->achievement($student, $year, [
            'event_name' => 'Lomba Sains Nasional',
            'level' => 'nasional',
            'position' => 'juara_2',
        ], notify: false);
        $request = DormitoryRewardRequest::where('student_achievement_id', $achievement->id)->firstOrFail();
        $catalog = DormitoryPointCatalog::where('code', 'R-15')->firstOrFail();

        $this->post(route('user.asrama.rewards.approvals.approve', [
            'userId' => $admin->id,
            'asramaUuid' => $dorm->id,
            'requestUuid' => $request->id,
        ]), [
            'point_catalog_id' => $catalog->id,
            'awarded_date' => now()->toDateString(),
            'send_notification' => 0,
        ])->assertRedirect();

        $request->refresh();
        $this->assertSame(DormitoryRewardRequest::STATUS_APPROVED, $request->status);
        $this->assertNotNull($request->dormitory_reward_id);

        $reward = DormitoryReward::findOrFail($request->dormitory_reward_id);
        $this->assertSame($achievement->id, $reward->source_achievement_id);
        $this->assertSame(100, $reward->points);
        $this->assertSame('Lomba Sains Nasional', $reward->source_label);
        $this->assertDatabaseHas('dormitory_point_ledger', [
            'reference_type' => 'dormitory_reward',
            'reference_id' => $reward->id,
            'kind' => 'reward',
            'points' => 100,
        ]);
    }

    public function test_guardian_notification_is_sent_only_after_approval(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [$admin, $dorm, $student, $year] = $this->fixtures();
        config()->set('whatsapp.delivery_mode', 'log');

        $guardian = User::factory()->create([
            'is_active' => true,
            'is_wali' => true,
            'no_hp' => '6280000000009',
        ]);
        StudentMahrom::create([
            'student_id' => $student->id,
            'name' => 'Wali Uji',
            'relationship' => 'ayah',
            'phone' => '6280000000009',
            'is_active' => true,
            'is_primary' => true,
        ]);
        WaliSantri::create([
            'student_id' => $student->id,
            'user_id' => $guardian->id,
            'school_id' => $student->school_id,
            'role' => 'ayah',
            'is_primary' => true,
            'access_token' => Str::random(64),
            'verified_at' => now(),
            'status' => WaliSantri::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin);
        $achievement = $this->achievement($student, $year, [
            'event_name' => 'Prestasi Notifikasi Wali',
            'level' => 'provinsi',
            'position' => 'juara_1',
        ], notify: false);
        $request = DormitoryRewardRequest::where('student_achievement_id', $achievement->id)->firstOrFail();

        $this->assertDatabaseMissing('notifications_universal', [
            'user_id' => $guardian->id,
            'reference_type' => 'dormitory_reward',
        ]);

        $catalog = DormitoryPointCatalog::where('code', 'R-14')->firstOrFail();
        $this->post(route('user.asrama.rewards.approvals.approve', [
            'userId' => $admin->id,
            'asramaUuid' => $dorm->id,
            'requestUuid' => $request->id,
        ]), [
            'point_catalog_id' => $catalog->id,
            'send_notification' => 1,
        ])->assertRedirect();

        $notification = \DB::table('notifications_universal')
            ->where('user_id', $guardian->id)
            ->where('reference_type', 'dormitory_reward')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Alhamdulillahilladzi bini\'matihi tatimmush sholihaat', $notification->message);
        $this->assertStringContainsString('telah disetujui', $notification->message);
        $this->assertStringNotContainsString('Selamat', $notification->message);
    }

    public function test_editing_pending_achievement_updates_request_and_deleting_cancels_it(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [$admin, , $student, $year] = $this->fixtures();

        $this->actingAs($admin);
        $achievement = $this->achievement($student, $year, [
            'event_name' => 'Lomba Kecamatan',
            'level' => 'kecamatan',
            'position' => 'juara_1',
        ], notify: false);
        $request = DormitoryRewardRequest::where('student_achievement_id', $achievement->id)->firstOrFail();
        $this->assertSame(30, $request->points);

        $achievement->update([
            'event_name' => 'Lomba Provinsi',
            'level' => 'provinsi',
        ]);

        $request->refresh();
        $this->assertSame('Lomba Provinsi', $request->title);
        $this->assertSame(70, $request->points);

        $achievement->delete();
        $request->refresh();
        $this->assertSame(DormitoryRewardRequest::STATUS_CANCELLED, $request->status);
    }

    public function test_deleting_approved_source_marks_reward_but_keeps_it(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [$admin, $dorm, $student, $year] = $this->fixtures();
        $this->actingAs($admin);

        $achievement = $this->achievement($student, $year, [], notify: false);
        $request = DormitoryRewardRequest::where('student_achievement_id', $achievement->id)->firstOrFail();
        $catalog = DormitoryPointCatalog::where('code', 'R-14')->firstOrFail();

        $this->post(route('user.asrama.rewards.approvals.approve', [
            'userId' => $admin->id,
            'asramaUuid' => $dorm->id,
            'requestUuid' => $request->id,
        ]), [
            'point_catalog_id' => $catalog->id,
            'send_notification' => 0,
        ])->assertRedirect();

        $reward = DormitoryReward::where('source_achievement_id', $achievement->id)->firstOrFail();
        $achievement->delete();

        $reward->refresh();
        $this->assertDatabaseHas('dormitory_rewards', ['id' => $reward->id]);
        $this->assertNotNull($reward->source_deleted_at);
    }

    public function test_notification_flag_controls_approver_notification(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [$admin, , $student, $year] = $this->fixtures();

        $this->actingAs($admin);
        $this->achievement($student, $year, ['event_name' => 'Prestasi dengan Notifikasi'], notify: true);

        $notification = \DB::table('notifications_universal')
            ->where('user_id', $admin->id)
            ->where('reference_type', 'dormitory_reward_request')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Alhamdulillahilladzi bini\'matihi tatimmush sholihaat', $notification->message);
        $this->assertStringContainsString('Satuan pendidikan mengajukan prestasi', $notification->message);
        $this->assertStringNotContainsString('Selamat', $notification->message);

        $this->achievement($student, $year, ['event_name' => 'Prestasi Tanpa Notifikasi'], notify: false);
        $this->assertSame(1, \DB::table('notifications_universal')
            ->where('reference_type', 'dormitory_reward_request')
            ->count());
    }

    public function test_import_creates_pending_request(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [$admin, , $student, $year] = $this->fixtures();
        $school = School::findOrFail($student->school_id);

        $csv = implode("\n", [
            'nisn,nama_lomba,penyelenggara,tingkat,peringkat,tanggal,lokasi,keterangan',
            "{$student->nisn},Lomba Import Provinsi,Dinas Pendidikan,provinsi,juara 1,24/10/2026,Bandung,Import test",
        ]);

        $import = new StudentAchievementImport(
            $admin->id,
            $school->id,
            'akademik',
            null,
            $year->id,
            [],
            false
        );

        Excel::import($import, UploadedFile::fake()->createWithContent('achievements.csv', $csv));

        $achievement = StudentAchievement::where('event_name', 'Lomba Import Provinsi')->firstOrFail();
        $this->assertDatabaseHas('dormitory_reward_requests', [
            'student_achievement_id' => $achievement->id,
            'status' => DormitoryRewardRequest::STATUS_PENDING,
        ]);
    }

    public function test_asrama_sidebar_shows_pending_approval_badge(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [$admin, $dorm, $student, $year] = $this->fixtures();

        $this->actingAs($admin);
        $this->achievement($student, $year, ['event_name' => 'Prestasi untuk Badge'], notify: false);

        $approver = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => false,
        ]);
        $role = Role::firstOrCreate(['name' => 'Asrama'], ['level' => 4]);
        \DB::table('model_has_roles')->insert([
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $approver->id,
        ]);
        $approver->load('roles');
        $dorm->update(['head_id' => $approver->id]);

        $this->actingAs($approver);
        $html = view('layouts.sidebar')->render();

        $this->assertStringContainsString('Persetujuan Penghargaan', $html);
        $this->assertStringContainsString('bg-danger rounded-pill ms-auto', $html);
    }

    public function test_approver_can_open_approval_page(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [$admin, $dorm, $student, $year] = $this->fixtures();

        $this->actingAs($admin);
        $achievement = $this->achievement($student, $year, [], notify: false);
        $request = DormitoryRewardRequest::where('student_achievement_id', $achievement->id)->firstOrFail();

        $this->get(route('user.asrama.rewards.approvals.index', [
            'userId' => $admin->id,
            'asramaUuid' => $dorm->id,
        ]))
            ->assertOk()
            ->assertSee('Pengajuan Penghargaan');

        $this->get(route('user.asrama.rewards.approvals.show', [
            'userId' => $admin->id,
            'asramaUuid' => $dorm->id,
            'requestUuid' => $request->id,
        ]))
            ->assertOk()
            ->assertSee('Detail Prestasi');
    }

    public function test_wakil_kepala_asrama_is_recognized_as_approver(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [, $dorm] = $this->fixtures();

        $wakil = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => false,
        ]);
        GtkEmployment::create([
            'user_id' => $wakil->id,
            'school_id' => $dorm->school_id,
            'jabatan' => 'Wakil Kepala Asrama',
            'status_kepegawaian' => 'GTT',
        ]);

        $this->assertTrue(
            app(DormitoryPolicyAccess::class)
                ->canApproveReward($wakil, $dorm->id)
        );
    }

    public function test_non_approver_cannot_open_approval_page(): void
    {
        $this->seed(DormitoryPointSeeder::class);
        [, $dorm] = $this->fixtures();

        $user = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $this->actingAs($user)
            ->get(route('user.asrama.rewards.approvals.index', [
                'userId' => $user->id,
                'asramaUuid' => $dorm->id,
            ]))
            ->assertForbidden();
    }

    private function achievement(Student $student, AcademicYear $year, array $overrides = [], bool $notify = false): StudentAchievement
    {
        $achievement = new StudentAchievement(array_merge([
            'student_id' => $student->id,
            'school_id' => $student->school_id,
            'academic_year_id' => $year->id,
            'achievement_type' => 'akademik',
            'event_name' => 'Lomba Prestasi',
            'level' => 'provinsi',
            'position' => 'juara_1',
            'event_date' => now()->toDateString(),
            'created_by' => auth()->id(),
        ], $overrides));
        $achievement->notifyDormitoryReward = $notify;
        $achievement->save();

        return $achievement;
    }

    private function fixtures(): array
    {
        $workUnitId = (string) Str::uuid();
        \DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Prestasi Asrama',
            'code' => 'UPA-'.Str::upper(Str::random(4)),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '91827364',
            'name' => 'Sekolah Prestasi Asrama',
        ]);

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $admin = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);

        $dorm = Dormitory::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $school->id,
            'code' => 'PA-01',
            'name' => 'Asrama Prestasi',
            'gender' => 'putra',
            'capacity' => 10,
            'is_active' => true,
            'head_id' => $admin->id,
        ]);

        $wing = DormitoryWing::create([
            'dormitory_id' => $dorm->id,
            'code' => 'W1',
            'name' => 'Gedung 1',
            'floor' => 1,
            'gender' => 'putra',
            'capacity' => 10,
            'total_rooms' => 1,
            'is_active' => true,
        ]);

        $room = DormitoryRoom::create([
            'dormitory_id' => $dorm->id,
            'wing_id' => $wing->id,
            'code' => 'P-01',
            'name' => 'Kamar P1',
            'floor' => 1,
            'gender' => 'putra',
            'capacity' => 10,
            'room_type' => 'reguler',
            'is_active' => true,
        ]);

        $student = Student::create([
            'school_id' => $school->id,
            'name' => 'Santri Prestasi',
            'nisn' => '55500002',
            'gender' => 'L',
            'status' => 'active',
        ]);

        DormitoryResident::create([
            'student_id' => $student->id,
            'room_id' => $room->id,
            'dormitory_id' => $dorm->id,
            'academic_year_id' => $year->id,
            'bed_number' => 1,
            'is_active' => true,
            'check_in_date' => now()->subMonth()->toDateString(),
        ]);

        $this->actingAs($admin);

        return [$admin, $dorm, $student, $year];
    }
}
