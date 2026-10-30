<?php

namespace Tests\Feature;

use App\Http\Controllers\DormitoryPostController;
use App\Http\Middleware\BindOrganizationContext;
use App\Http\Middleware\DenyStudentOperatorAi;
use App\Http\Middleware\EnsureDormitoryStudentOperatorAccess;
use App\Http\Middleware\EnsureEmployeeAccess;
use App\Http\Middleware\EnsureRoleAccess;
use App\Http\Middleware\EnsureStudentPasswordChanged;
use App\Http\Middleware\RestrictAdminIp;
use App\Http\Middleware\SchoolContextMiddleware;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\GradeLevel;
use App\Models\GtkEmployment;
use App\Models\HumasAnnouncement;
use App\Models\Kaldik;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\User;
use App\Models\WaliSantri;
use App\Models\WorkUnit;
use App\Services\Humas\AnnouncementCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Scoping pengumuman:
 * - sekolah A hanya untuk wali sekolah A,
 * - global (school_id null) untuk semua,
 * - feed mobile menghormati multi-anak.
 */
class AnnouncementScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, class-string>
     */
    private function routeGuards(): array
    {
        return [
            EnsureEmployeeAccess::class,
            EnsureRoleAccess::class,
            SchoolContextMiddleware::class,
            BindOrganizationContext::class,
            RestrictAdminIp::class,
            EnsureStudentPasswordChanged::class,
            DenyStudentOperatorAi::class,
            EnsureDormitoryStudentOperatorAccess::class,
        ];
    }

    private function school(string $name): School
    {
        $workUnit = WorkUnit::factory()->create(['name' => 'Unit '.$name, 'is_active' => true]);

        return School::factory()->create([
            'name' => $name,
            'work_unit_id' => $workUnit->id,
            'is_active' => true,
        ]);
    }

    private function student(School $school, string $name): Student
    {
        return Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $school->id,
            'name' => $name,
            'nisn' => $this->digits(10),
            'nik' => $this->digits(16),
            'no_kk' => $this->digits(16),
            'gender' => 'L',
            'birth_place' => 'Mataram',
            'birth_date' => '2012-01-01',
            'mother_name' => 'Siti Aisah',
            'status' => 'active',
        ]);
    }

    private function wali(string $name): User
    {
        return User::factory()->create(['name' => $name, 'is_active' => true, 'is_wali' => true]);
    }

    private function link(User $wali, Student $student, School $school): WaliSantri
    {
        return WaliSantri::create([
            'user_id' => $wali->id,
            'student_id' => $student->id,
            'school_id' => $school->id,
            'role' => 'wali',
            'is_primary' => true,
            'status' => 'active',
            'verified_at' => now(),
        ]);
    }

    private function humas(): User
    {
        $user = User::factory()->create(['name' => 'Humas', 'is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'Humas Personalia', 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user;
    }

    private function announcement(School $school, string $title, string $audience = 'wali'): HumasAnnouncement
    {
        return HumasAnnouncement::create([
            'school_id' => $school->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(5),
            'category' => 'umum',
            'audience' => $audience,
            'excerpt' => 'Ringkasan '.$title,
            'content' => 'Isi '.$title,
            'status' => 'draft',
        ]);
    }

    private function digits(int $length): string
    {
        $number = '';
        for ($i = 0; $i < $length; $i++) {
            $number .= (string) random_int(0, 9);
        }

        return $number;
    }

    public function test_school_announcement_only_notifies_wali_from_that_school(): void
    {
        $schoolA = $this->school('Sekolah A');
        $schoolB = $this->school('Sekolah B');

        $waliA = $this->wali('Wali A');
        $waliB = $this->wali('Wali B');

        $studentA = $this->student($schoolA, 'Santri A');
        $studentB = $this->student($schoolB, 'Santri B');

        $this->link($waliA, $studentA, $schoolA);
        $this->link($waliB, $studentB, $schoolB);

        $announcement = $this->announcement($schoolA, 'Pengumuman Sekolah A');
        $humas = $this->humas();

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($humas)
            ->post("/{$humas->id}/humas/announcements/{$announcement->id}/publish")
            ->assertRedirect();

        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $waliA->id,
            'reference_id' => $announcement->id,
        ]);

        $this->assertDatabaseMissing('notifications_universal', [
            'user_id' => $waliB->id,
            'reference_id' => $announcement->id,
        ]);
    }

    public function test_global_announcement_notifies_all_wali(): void
    {
        $schoolA = $this->school('Sekolah A');
        $schoolB = $this->school('Sekolah B');

        $waliA = $this->wali('Wali A');
        $waliB = $this->wali('Wali B');

        $this->link($waliA, $this->student($schoolA, 'Santri A'), $schoolA);
        $this->link($waliB, $this->student($schoolB, 'Santri B'), $schoolB);

        $announcement = $this->announcement($schoolA, 'Pengumuman Global');
        $announcement->update(['school_id' => null]);
        $humas = $this->humas();

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($humas)
            ->post("/{$humas->id}/humas/announcements/{$announcement->id}/publish")
            ->assertRedirect();

        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $waliA->id,
            'reference_id' => $announcement->id,
        ]);
        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $waliB->id,
            'reference_id' => $announcement->id,
        ]);
    }

    public function test_mobile_feed_respects_multi_school_children_and_student_filter(): void
    {
        $schoolA = $this->school('Sekolah A');
        $schoolB = $this->school('Sekolah B');
        $schoolC = $this->school('Sekolah C');

        $wali = $this->wali('Wali Multi');
        $studentA = $this->student($schoolA, 'Santri A');
        $studentB = $this->student($schoolB, 'Santri B');

        $this->link($wali, $studentA, $schoolA);
        $this->link($wali, $studentB, $schoolB);

        $announcementA = $this->announcement($schoolA, 'Pengumuman A');
        $announcementB = $this->announcement($schoolB, 'Pengumuman B');
        $announcementC = $this->announcement($schoolC, 'Pengumuman C');
        $global = $this->announcement($schoolA, 'Pengumuman Global');
        $global->update(['school_id' => null]);

        foreach ([$announcementA, $announcementB, $announcementC, $global] as $announcement) {
            $announcement->update(['status' => 'published', 'published_at' => now()]);
        }

        Sanctum::actingAs($wali);

        $response = $this->getJson('/api/mobile/v1/'.$wali->id.'/wali/announcements');
        $response->assertOk();
        $titles = collect($response->json('data.announcements'))->pluck('title');

        $this->assertTrue($titles->contains('Pengumuman A'));
        $this->assertTrue($titles->contains('Pengumuman B'));
        $this->assertTrue($titles->contains('Pengumuman Global'));
        $this->assertFalse($titles->contains('Pengumuman C'));

        $filtered = $this->getJson('/api/mobile/v1/'.$wali->id.'/wali/announcements?student_id='.$studentA->id);
        $filtered->assertOk();
        $filteredTitles = collect($filtered->json('data.announcements'))->pluck('title');

        $this->assertTrue($filteredTitles->contains('Pengumuman A'));
        $this->assertTrue($filteredTitles->contains('Pengumuman Global'));
        $this->assertFalse($filteredTitles->contains('Pengumuman B'));
    }

    private function schoolUser(School $school): User
    {
        $user = User::factory()->create(['name' => 'Admin '.$school->name, 'is_active' => true]);

        GtkEmployment::create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Kependidikan',
            'jabatan' => 'Kepala Tata Usaha',
        ]);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web']);
        $user->assignRole($role);

        return $user->fresh();
    }

    public function test_school_can_post_announcement_only_for_its_school(): void
    {
        $schoolA = $this->school('Sekolah A');
        $schoolB = $this->school('Sekolah B');

        $waliA = $this->wali('Wali A');
        $waliB = $this->wali('Wali B');
        $this->link($waliA, $this->student($schoolA, 'Santri A'), $schoolA);
        $this->link($waliB, $this->student($schoolB, 'Santri B'), $schoolB);

        $schoolAdmin = $this->schoolUser($schoolA);

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($schoolAdmin)
            ->post("/{$schoolAdmin->id}/humas/announcements", [
                'title' => 'Pengumuman Sekolah A',
                'category' => 'umum',
                'audience' => 'wali',
                'content' => 'Isi pengumuman sekolah A',
            ])
            ->assertRedirect();

        $announcement = HumasAnnouncement::where('title', 'Pengumuman Sekolah A')->firstOrFail();
        $this->assertSame($schoolA->id, $announcement->school_id);

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($schoolAdmin)
            ->post("/{$schoolAdmin->id}/humas/announcements/{$announcement->id}/publish")
            ->assertRedirect();

        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $waliA->id,
            'reference_id' => $announcement->id,
        ]);
        $this->assertDatabaseMissing('notifications_universal', [
            'user_id' => $waliB->id,
            'reference_id' => $announcement->id,
        ]);
    }

    public function test_personalia_post_is_global_even_when_school_context_exists(): void
    {
        $school = $this->school('Sekolah A');
        $humas = $this->humas();

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($humas)
            ->withSession(['sa_school_id' => $school->id])
            ->post("/{$humas->id}/humas/announcements", [
                'title' => 'Pengumuman Global Personalia',
                'category' => 'umum',
                'audience' => 'wali',
                'content' => 'Isi pengumuman global',
            ])
            ->assertRedirect();

        $announcement = HumasAnnouncement::where('title', 'Pengumuman Global Personalia')->firstOrFail();
        $this->assertNull($announcement->school_id);
    }

    public function test_school_cannot_manage_announcement_from_other_school(): void
    {
        $schoolA = $this->school('Sekolah A');
        $schoolB = $this->school('Sekolah B');
        $schoolAdmin = $this->schoolUser($schoolA);

        $announcementB = $this->announcement($schoolB, 'Pengumuman Sekolah B');

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($schoolAdmin)
            ->post("/{$schoolAdmin->id}/humas/announcements/{$announcementB->id}/publish")
            ->assertForbidden();
    }

    public function test_central_broadcast_to_school_only_reaches_that_school(): void
    {
        $schoolA = $this->school('Sekolah Broadcast A');
        $schoolB = $this->school('Sekolah Broadcast B');

        $waliA = $this->wali('Wali Broadcast A');
        $waliB = $this->wali('Wali Broadcast B');
        $this->link($waliA, $this->student($schoolA, 'Santri Broadcast A'), $schoolA);
        $this->link($waliB, $this->student($schoolB, 'Santri Broadcast B'), $schoolB);

        $humas = $this->humas();

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($humas)
            ->post("/{$humas->id}/broadcasts", [
                'title' => 'Broadcast Sekolah A',
                'content' => 'Informasi untuk sekolah A.',
                'target_type' => 'school',
                'target_id' => $schoolA->id,
                'recipient_group' => 'wali',
                'channels' => ['inapp'],
                'severity' => 'info',
                'ack_required' => '1',
            ])
            ->assertRedirect();

        $broadcast = HumasAnnouncement::where('title', 'Broadcast Sekolah A')->firstOrFail();
        $this->assertSame('school', $broadcast->target_type);
        $this->assertSame($schoolA->id, $broadcast->school_id);
        $this->assertTrue($broadcast->ack_required);

        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $waliA->id,
            'reference_id' => $broadcast->id,
        ]);
        $this->assertDatabaseMissing('notifications_universal', [
            'user_id' => $waliB->id,
            'reference_id' => $broadcast->id,
        ]);

        Sanctum::actingAs($waliA);

        $feed = $this->getJson('/api/mobile/v1/'.$waliA->id.'/wali/announcements');
        $feed->assertOk();
        $feedItem = collect($feed->json('data.announcements'))->firstWhere('id', $broadcast->id);
        $this->assertNotNull($feedItem);
        $this->assertTrue($feedItem['requires_popup']);
        $this->assertTrue($feedItem['ack_required']);
        $this->assertSame('school', $feedItem['target_type']);

        $this->postJson("/api/mobile/v1/{$waliA->id}/wali/announcements/{$broadcast->id}/read")
            ->assertOk();
        $this->postJson("/api/mobile/v1/{$waliA->id}/wali/announcements/{$broadcast->id}/ack")
            ->assertOk();

        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $waliA->id,
            'reference_id' => $broadcast->id,
            'is_read' => true,
        ]);
        $this->assertDatabaseHas('announcement_acknowledgements', [
            'reference_type' => HumasAnnouncement::class,
            'reference_id' => $broadcast->id,
            'user_id' => $waliA->id,
        ]);
    }

    public function test_broadcast_banner_is_normalized_to_1080x1350(): void
    {
        Storage::fake('public');

        $school = $this->school('Sekolah Banner');
        $humas = $this->humas();

        $response = $this->withoutMiddleware($this->routeGuards())
            ->actingAs($humas)
            ->post("/{$humas->id}/broadcasts", [
                'title' => 'Broadcast Banner',
                'content' => 'Isi broadcast dengan banner.',
                'target_type' => 'school',
                'target_id' => $school->id,
                'recipient_group' => 'wali',
                'channels' => ['inapp'],
                'severity' => 'info',
                'banner' => UploadedFile::fake()->image('banner.jpg', 1080, 1350),
            ]);

        $response->assertRedirect();

        $broadcast = HumasAnnouncement::where('title', 'Broadcast Banner')->firstOrFail();
        $this->assertNotNull($broadcast->banner_path);
        $this->assertNotNull($broadcast->banner_url);
        $this->assertStringContainsString($broadcast->banner_path, $broadcast->banner_url);
        Storage::disk('public')->assertExists($broadcast->banner_path);

        $size = getimagesize(Storage::disk('public')->path($broadcast->banner_path));
        $this->assertSame(1080, $size[0]);
        $this->assertSame(1350, $size[1]);
    }

    public function test_announcement_supports_multiple_carousel_banners(): void
    {
        Storage::fake('public');

        $school = $this->school('Sekolah Carousel');
        $wali = $this->wali('Wali Carousel');
        $student = $this->student($school, 'Santri Carousel');
        $this->link($wali, $student, $school);

        $humas = $this->humas();

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($humas)
            ->post("/{$humas->id}/humas/announcements", [
                'title' => 'Pengumuman Carousel',
                'category' => 'umum',
                'audience' => 'wali',
                'content' => 'Isi pengumuman carousel.',
                'banners' => [
                    UploadedFile::fake()->image('slide-1.jpg', 1080, 1350),
                    UploadedFile::fake()->image('slide-2.jpg', 1080, 1350),
                ],
            ])
            ->assertRedirect();

        $announcement = HumasAnnouncement::where('title', 'Pengumuman Carousel')->firstOrFail();
        $this->assertCount(2, $announcement->images);
        $this->assertSame($announcement->images->first()->path, $announcement->banner_path);
        Storage::disk('public')->assertExists($announcement->images->first()->path);
        Storage::disk('public')->assertExists($announcement->images->last()->path);

        $announcement->update(['status' => 'published', 'published_at' => now()]);

        Sanctum::actingAs($wali);

        $response = $this->getJson('/api/mobile/v1/'.$wali->id.'/wali/announcements');
        $response->assertOk();

        $item = collect($response->json('data.announcements'))
            ->firstWhere('id', $announcement->id);

        $this->assertNotNull($item);
        $this->assertCount(2, $item['banners']);
        $this->assertSame(1080, $item['banners'][0]['width']);
        $this->assertSame(1350, $item['banners'][0]['height']);
        $this->assertSame($announcement->images->first()->url, $item['banner_url']);
        $this->assertSame($announcement->images->first()->url, $item['banners'][0]['url']);
    }

    public function test_announcement_carousel_images_can_be_removed_and_limit_is_enforced(): void
    {
        Storage::fake('public');

        $school = $this->school('Sekolah Carousel Edit');
        $humas = $this->humas();

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($humas)
            ->post("/{$humas->id}/humas/announcements", [
                'title' => 'Pengumuman Carousel Edit',
                'category' => 'umum',
                'audience' => 'wali',
                'content' => 'Isi.',
                'banners' => [
                    UploadedFile::fake()->image('slide-1.jpg', 1080, 1350),
                    UploadedFile::fake()->image('slide-2.jpg', 1080, 1350),
                ],
            ])
            ->assertRedirect();

        $announcement = HumasAnnouncement::where('title', 'Pengumuman Carousel Edit')->firstOrFail();
        $removed = $announcement->images->first();
        $removedPath = $removed->path;

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($humas)
            ->put("/{$humas->id}/humas/announcements/{$announcement->id}", [
                'title' => 'Pengumuman Carousel Edit',
                'category' => 'umum',
                'audience' => 'wali',
                'content' => 'Isi.',
                'remove_images' => [$removed->id],
                'banners' => [
                    UploadedFile::fake()->image('slide-3.jpg', 1080, 1350),
                ],
            ])
            ->assertRedirect();

        $announcement->refresh();
        $this->assertCount(2, $announcement->images);
        $this->assertDatabaseMissing('humas_announcement_images', ['id' => $removed->id]);
        Storage::disk('public')->assertMissing($removedPath);
        $this->assertSame($announcement->images->first()->path, $announcement->banner_path);

        $tooMany = [];
        for ($i = 0; $i < 4; $i++) {
            $tooMany[] = UploadedFile::fake()->image('too-many-'.$i.'.jpg', 1080, 1350);
        }

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($humas)
            ->put("/{$humas->id}/humas/announcements/{$announcement->id}", [
                'title' => 'Pengumuman Carousel Edit',
                'category' => 'umum',
                'audience' => 'wali',
                'content' => 'Isi.',
                'banners' => $tooMany,
            ])
            ->assertSessionHasErrors('banners');

        $this->assertCount(2, $announcement->fresh()->images);
    }

    public function test_dormitory_broadcast_only_reaches_wali_of_residents(): void
    {
        $school = $this->school('Sekolah Dorm Scope');
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $dormA = Dormitory::factory()->create(['school_id' => $school->id, 'name' => 'Asrama A']);
        $dormB = Dormitory::factory()->create(['school_id' => $school->id, 'name' => 'Asrama B']);

        $waliA = $this->wali('Wali Dorm A');
        $waliB = $this->wali('Wali Dorm B');
        $studentA = $this->student($school, 'Santri Dorm A');
        $studentB = $this->student($school, 'Santri Dorm B');

        $this->link($waliA, $studentA, $school);
        $this->link($waliB, $studentB, $school);

        foreach ([[$studentA, $dormA], [$studentB, $dormB]] as [$student, $dorm]) {
            DormitoryResident::create([
                'student_id' => $student->id,
                'dormitory_id' => $dorm->id,
                'academic_year_id' => $year->id,
                'is_active' => true,
                'check_in_date' => now()->toDateString(),
            ]);
        }

        $broadcast = HumasAnnouncement::create([
            'school_id' => $school->id,
            'target_type' => 'dormitory',
            'dormitory_id' => $dormA->id,
            'title' => 'Broadcast Asrama A',
            'slug' => 'broadcast-asrama-a',
            'category' => 'broadcast',
            'severity' => 'urgent',
            'ack_required' => true,
            'audience' => 'wali',
            'content' => 'Hanya untuk penghuni asrama A.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Sanctum::actingAs($waliA);
        $responseA = $this->getJson('/api/mobile/v1/'.$waliA->id.'/wali/announcements');
        $responseA->assertOk();
        $item = collect($responseA->json('data.announcements'))->firstWhere('id', $broadcast->id);
        $this->assertNotNull($item);
        $this->assertTrue($item['requires_popup']);
        $this->assertTrue($item['ack_required']);
        $this->assertSame('urgent', $item['severity']);

        Sanctum::actingAs($waliB);
        $responseB = $this->getJson('/api/mobile/v1/'.$waliB->id.'/wali/announcements');
        $responseB->assertOk();
        $this->assertNull(collect($responseB->json('data.announcements'))->firstWhere('id', $broadcast->id));
        $this->getJson('/api/mobile/v1/'.$waliB->id.'/wali/announcements/'.$broadcast->id)
            ->assertNotFound();
    }

    public function test_class_broadcast_only_reaches_wali_of_students_in_class(): void
    {
        $school = $this->school('Sekolah Class Scope');
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
        $grade = GradeLevel::create([
            'school_id' => $school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'code' => 'VII',
            'is_active' => true,
        ]);

        $classA = StudyGroup::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'name' => '7A',
            'code' => 'VII-A',
            'is_active' => true,
        ]);
        $classB = StudyGroup::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'name' => '7B',
            'code' => 'VII-B',
            'is_active' => true,
        ]);

        $waliA = $this->wali('Wali Class A');
        $waliB = $this->wali('Wali Class B');
        $studentA = $this->student($school, 'Santri Class A');
        $studentB = $this->student($school, 'Santri Class B');
        $this->link($waliA, $studentA, $school);
        $this->link($waliB, $studentB, $school);

        foreach ([[$studentA, $classA], [$studentB, $classB]] as [$student, $class]) {
            StudentClassHistory::create([
                'student_id' => $student->id,
                'study_group_id' => $class->id,
                'academic_year_id' => $year->id,
                'is_active' => true,
            ]);
        }

        $broadcast = HumasAnnouncement::create([
            'school_id' => $school->id,
            'target_type' => 'class',
            'study_group_id' => $classA->id,
            'title' => 'Broadcast Kelas 7A',
            'slug' => 'broadcast-kelas-7a',
            'category' => 'broadcast',
            'severity' => 'info',
            'audience' => 'wali',
            'content' => 'Hanya untuk kelas 7A.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Sanctum::actingAs($waliA);
        $responseA = $this->getJson('/api/mobile/v1/'.$waliA->id.'/wali/announcements');
        $responseA->assertOk();
        $this->assertNotNull(collect($responseA->json('data.announcements'))->firstWhere('id', $broadcast->id));

        Sanctum::actingAs($waliB);
        $responseB = $this->getJson('/api/mobile/v1/'.$waliB->id.'/wali/announcements');
        $responseB->assertOk();
        $this->assertNull(collect($responseB->json('data.announcements'))->firstWhere('id', $broadcast->id));
    }

    public function test_announcement_admin_page_renders_existing_carousel_images(): void
    {
        $humas = $this->humas();
        $school = $this->school('Sekolah Carousel Admin');
        $announcement = $this->announcement($school, 'Pengumuman Carousel Admin');
        $image = $announcement->images()->create([
            'path' => 'announcements/banners/carousel-admin.webp',
            'sort_order' => 0,
        ]);

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($humas)
            ->get("/{$humas->id}/humas/announcements")
            ->assertOk()
            ->assertSee('Banner Carousel')
            ->assertSee('carousel-admin.webp');
    }

    public function test_broadcast_admin_page_renders_banner_and_target_filter(): void
    {
        $humas = $this->humas();

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($humas)
            ->get("/{$humas->id}/broadcasts")
            ->assertOk()
            ->assertSee('Banner (wajib 4:5, 1080×1350)')
            ->assertSee('targetSelect')
            ->assertSee('targetHelp');
    }

    public function test_dormitory_post_multiple_banners_are_exposed_to_mobile(): void
    {
        $school = $this->school('Sekolah Asrama Carousel');
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
        $dorm = Dormitory::factory()->create(['school_id' => $school->id, 'name' => 'Asrama Carousel']);

        $wali = $this->wali('Wali Asrama Carousel');
        $student = $this->student($school, 'Santri Asrama Carousel');
        $this->link($wali, $student, $school);

        DormitoryResident::create([
            'student_id' => $student->id,
            'dormitory_id' => $dorm->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
            'check_in_date' => now()->toDateString(),
        ]);

        $post = HumasAnnouncement::create([
            'school_id' => $school->id,
            'target_type' => 'dormitory',
            'dormitory_id' => $dorm->id,
            'title' => 'Informasi Carousel Asrama',
            'slug' => 'informasi-carousel-asrama',
            'content' => 'Isi informasi asrama.',
            'category' => 'pengumuman',
            'audience' => 'wali',
            'visibility' => 'wali',
            'recipient_group' => 'wali',
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $this->humas()->id,
        ]);

        $first = $post->images()->create([
            'path' => 'announcements/banners/asrama-slide-1.webp',
            'sort_order' => 0,
        ]);
        $post->images()->create([
            'path' => 'announcements/banners/asrama-slide-2.webp',
            'sort_order' => 1,
        ]);

        Sanctum::actingAs($wali);
        $response = $this->getJson('/api/mobile/v1/'.$wali->id.'/wali/announcements');
        $response->assertOk();

        $item = collect($response->json('data.announcements'))->firstWhere('id', $post->id);
        $this->assertNotNull($item);
        $this->assertCount(2, $item['banners']);
        $this->assertSame($first->url, $item['banner_url']);
        $this->assertSame($first->url, $item['banners'][0]['url']);
    }

    public function test_asrama_pages_use_the_shared_humas_views(): void
    {
        $school = $this->school('Sekolah Shared View');
        $dormitory = Dormitory::factory()->create(['school_id' => $school->id, 'name' => 'Asrama Shared']);
        $admin = $this->humas();

        $this->actingAs($admin);
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $admin);

        $controller = app(DormitoryPostController::class);

        $index = $controller->index($request, $admin->id, $dormitory->id);
        $this->assertSame('humas.announcements.index', $index->name());
        $this->assertStringContainsString('Banner Carousel', $index->render());

        $broadcasts = $controller->broadcastIndex($request, $admin->id, $dormitory->id);
        $this->assertSame('broadcasts.index', $broadcasts->name());
        $this->assertStringContainsString('Banner (wajib 4:5, 1080×1350)', $broadcasts->render());
    }

    public function test_activity_dates_are_synced_to_kaldik_with_smart_type_and_color(): void
    {
        $school = $this->school('Sekolah Kaldik');
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
        $humas = $this->humas();

        $announcement = HumasAnnouncement::create([
            'school_id' => $school->id,
            'target_type' => 'school',
            'title' => 'Festival Santri dan Pentas Seni',
            'slug' => 'festival-santri',
            'category' => 'kegiatan',
            'audience' => 'wali',
            'content' => 'Kegiatan festival santri akan dilaksanakan bersama.',
            'status' => 'published',
            'published_at' => now(),
            'activity_start_date' => '2026-11-01',
            'activity_end_date' => '2026-11-02',
            'created_by' => $humas->id,
        ]);

        app(AnnouncementCalendarService::class)->sync($announcement, $humas);

        $kaldik = Kaldik::where('source_type', HumasAnnouncement::class)
            ->where('source_id', $announcement->id)
            ->firstOrFail();

        $this->assertSame($school->work_unit_id, $kaldik->work_unit_id);
        $this->assertSame(Kaldik::CATEGORY_AGENDA, $kaldik->category);
        $this->assertSame(Kaldik::TYPE_KEGIATAN, $kaldik->type);
        $this->assertSame(kaldik_default_color(Kaldik::TYPE_KEGIATAN), $kaldik->color);
        $this->assertSame('2026-11-01', $kaldik->start_date->format('Y-m-d'));
        $this->assertSame('2026-11-02', $kaldik->end_date->format('Y-m-d'));
        $this->assertSame($year->id, $kaldik->academic_year_id);

        $announcement->update(['activity_end_date' => '2026-11-03']);
        app(AnnouncementCalendarService::class)->sync($announcement->fresh(), $humas);

        $this->assertSame(1, Kaldik::where('source_id', $announcement->id)->count());
        $this->assertSame(
            '2026-11-03',
            Kaldik::where('source_id', $announcement->id)->firstOrFail()->end_date->format('Y-m-d')
        );

        $this->assertSame(Kaldik::TYPE_LIBUR, app(AnnouncementCalendarService::class)->classify('Libur Idul Fitri'));

        $dormitory = Dormitory::factory()->create(['school_id' => $school->id, 'name' => 'Asrama Kaldik']);
        $dormAnnouncement = HumasAnnouncement::create([
            'school_id' => $school->id,
            'target_type' => 'dormitory',
            'dormitory_id' => $dormitory->id,
            'title' => 'Kegiatan Asrama',
            'slug' => 'kegiatan-asrama',
            'category' => 'kegiatan',
            'audience' => 'wali',
            'content' => 'Kegiatan asrama.',
            'status' => 'published',
            'published_at' => now(),
            'activity_start_date' => '2026-11-05',
            'activity_end_date' => '2026-11-05',
            'created_by' => $humas->id,
        ]);

        app(AnnouncementCalendarService::class)->sync($dormAnnouncement, $humas);

        $dormKaldik = Kaldik::where('source_id', $dormAnnouncement->id)->firstOrFail();
        $this->assertSame($dormitory->work_unit_id, $dormKaldik->work_unit_id);

        $announcement->delete();
        $this->assertDatabaseMissing('kaldik', [
            'source_type' => HumasAnnouncement::class,
            'source_id' => $announcement->id,
        ]);
    }

    public function test_banner_with_wrong_ratio_is_rejected(): void
    {
        $school = $this->school('Sekolah Banner Salah');
        $humas = $this->humas();

        $this->withoutMiddleware($this->routeGuards())
            ->actingAs($humas)
            ->post("/{$humas->id}/broadcasts", [
                'title' => 'Broadcast Rasio Salah',
                'content' => 'Isi.',
                'target_type' => 'school',
                'target_id' => $school->id,
                'recipient_group' => 'wali',
                'channels' => ['inapp'],
                'severity' => 'info',
                'banner' => UploadedFile::fake()->image('wide.jpg', 1600, 900),
            ])
            ->assertSessionHasErrors('banner');

        $this->assertDatabaseMissing('humas_announcements', [
            'title' => 'Broadcast Rasio Salah',
        ]);
    }
}
