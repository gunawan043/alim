<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\V1\RaportController;
use App\Http\Controllers\Api\Mobile\V1\SantriDataController;
use App\Http\Controllers\NilaiKelasController;
use App\Models\AcademicYear;
use App\Models\NilaiSumatif;
use App\Models\RaportRegistration;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherAdminBook;
use App\Models\User;
use App\Models\WaliSantri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Publikasi rapor & buku nilai: wali hanya melihat data yang dipublikasikan.
 */
class RaporPublishTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private AcademicYear $ay;

    private StudyGroup $group;

    private Student $student;

    private Subject $subject;

    private TeacherAdminBook $book;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Publish', 'code' => 'UUPB',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '12121212', 'name' => 'Sekolah Uji Publish',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30',
        ]);

        $gradeLevelId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $gradeLevelId, 'school_id' => $this->school->id,
            'level' => 7, 'name' => 'Kelas 7', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->ay->id,
            'grade_level_id' => $gradeLevelId, 'name' => '7A', 'code' => '7A',
            'room' => '701', 'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Guru Publish', 'email' => 'guru.publish@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
            'is_system_admin' => true,
        ]);

        $this->group->update(['homeroom_teacher_id' => $this->admin->id]);

        $this->student = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Publish',
            'nisn' => '55556666', 'gender' => 'L', 'status' => 'active',
        ]);

        StudentClassHistory::create([
            'student_id' => $this->student->id, 'study_group_id' => $this->group->id,
            'academic_year_id' => $this->ay->id, 'is_active' => true,
            'join_date' => now()->toDateString(), 'attendance_number' => 1,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id, 'code' => 'PUB', 'name' => 'Mapel Publish',
            'credit_hours' => 2, 'is_active' => true,
        ]);

        $this->book = TeacherAdminBook::create([
            'teacher_id' => $this->admin->id,
            'subject_id' => $this->subject->id,
            'study_group_id' => $this->group->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        NilaiSumatif::create([
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'admin_book_id' => $this->book->id,
            'student_id' => $this->student->id,
            'sts' => 80,
            'sas' => 85,
            'nr_final' => 82,
        ]);

        $this->actingAs($this->admin);
    }

    public function test_wali_hanya_melihat_rapor_dan_nilai_yang_dipublikasikan(): void
    {
        $controller = app(NilaiKelasController::class);
        $request = $this->request('POST', [
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
        ]);

        // Sebelum publish: belum ada rapor terlihat.
        $wali = User::create([
            'name' => 'Wali Publish', 'email' => 'wali.publish@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        WaliSantri::create([
            'user_id' => $wali->id, 'student_id' => $this->student->id,
            'school_id' => $this->school->id, 'role' => 'wali',
            'is_primary' => true, 'status' => 'active',
        ]);

        $raportController = app(RaportController::class);
        $waliRequest = $this->request('GET', [], $wali);
        $waliRequest->setUserResolver(function () use ($wali) {
            return $wali;
        });
        $this->actingAs($wali);
        app()->instance('request', $waliRequest);

        $before = $raportController->status($waliRequest)->getData(true);
        $this->assertCount(0, $before['data']['registrations']);

        $gradesBefore = app(SantriDataController::class)->grades($waliRequest, $this->student->id)->getData(true);
        $this->assertCount(0, $gradesBefore['data']['sumatif']);

        // Publish buku mapel + rapor.
        $controller->bookPublish($request, $this->admin->id, $this->group->id, $this->book->id);
        $controller->raporPublish($request, $this->admin->id, $this->group->id);

        $this->assertNotNull($this->book->fresh()->published_at);
        $this->assertTrue(RaportRegistration::where('student_id', $this->student->id)->firstOrFail()->is_published);

        // Notifikasi untuk wali (in-app web + API mobile nanti).
        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $wali->id,
            'action' => 'nilai_published',
        ]);
        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $wali->id,
            'action' => 'rapor_published',
        ]);


        $after = $raportController->status($waliRequest)->getData(true);
        $this->assertCount(1, $after['data']['registrations']);
        $this->assertTrue($after['data']['is_published']);

        $gradesAfter = app(SantriDataController::class)->grades($waliRequest, $this->student->id)->getData(true);
        $this->assertCount(1, $gradesAfter['data']['sumatif']);

        $show = $raportController->show($waliRequest, $this->student->id);
        $this->assertSame(200, $show->getStatusCode());
        $this->assertCount(1, $show->getData(true)['data']['nilai']);

        // Batalkan publish buku → nilai hilang dari rapor wali, rapor tetap ada.
        $controller->bookUnpublish($request, $this->admin->id, $this->group->id, $this->book->id);
        $show2 = $raportController->show($waliRequest, $this->student->id);
        $this->assertCount(0, $show2->getData(true)['data']['nilai']);

        // Batalkan publish rapor → wali tidak bisa membuka rapor.
        $controller->raporUnpublish($request, $this->admin->id, $this->group->id);
        $blocked = $raportController->show($waliRequest, $this->student->id);
        $this->assertSame(403, $blocked->getStatusCode());
    }

    public function test_publish_berhasil_dengan_peringatan_nilai_di_bawah_kktp(): void
    {
        NilaiSumatif::where('admin_book_id', $this->book->id)
            ->update(['sts' => 60, 'sas' => 60, 'nr_final' => 60]);

        $controller = app(NilaiKelasController::class);
        $request = $this->request('POST', [
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'jenis' => 'sas',
        ]);

        $controller->raporPublish($request, $this->admin->id, $this->group->id);

        $this->assertTrue(
            RaportRegistration::where('student_id', $this->student->id)->where('is_published', true)->exists()
        );
        $this->assertTrue(session()->has('warning'));
        $this->assertStringContainsString('bawah KKTP', (string) session('warning'));
    }

    public function test_publish_sts_tidak_memerlukan_nilai_sas(): void
    {
        NilaiSumatif::where('admin_book_id', $this->book->id)
            ->update(['sas' => null, 'nr_final' => null]);

        $controller = app(NilaiKelasController::class);
        $request = $this->request('POST', [
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'jenis' => 'sts',
        ]);

        $controller->raporPublish($request, $this->admin->id, $this->group->id);

        $this->assertTrue(
            RaportRegistration::where('student_id', $this->student->id)->where('is_published', true)->exists()
        );
    }

    public function test_halaman_rapor_menampilkan_modal_publish_dibatalkan(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['error' => 'Publikasi dibatalkan: leger belum terisi lengkap.'])
            ->get(route('user.schools.nilai-kelas.rapor', [
                'userId' => $this->admin->id,
                'studyGroupId' => $this->group->id,
                'academic_year_id' => $this->ay->id,
                'semester' => 'ganjil',
            ]))
            ->assertOk()
            ->assertSee('publishBlockedModal', false)
            ->assertSee('926-road-barrier.gif', false);
    }

    public function test_publish_ditolak_jika_leger_belum_lengkap(): void
    {
        NilaiSumatif::where('admin_book_id', $this->book->id)
            ->update(['sas' => null, 'nr_final' => null]);

        $controller = app(NilaiKelasController::class);
        $request = $this->request('POST', [
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
        ]);

        $bookResponse = $controller->bookPublish($request, $this->admin->id, $this->group->id, $this->book->id);
        $this->assertNull($this->book->fresh()->published_at);
        $this->assertTrue(session()->has('error'));

        $raporResponse = $controller->raporPublish($request, $this->admin->id, $this->group->id);
        $this->assertFalse(
            RaportRegistration::where('student_id', $this->student->id)->where('is_published', true)->exists()
        );
        $this->assertTrue(session()->has('error'));
    }

    public function test_mapel_liqo_tidak_menghalangi_publish_walau_kosong(): void
    {
        $liqo = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'LIQO',
            'name' => "Liqo Maftuh (Upacara)",
            'credit_hours' => 1,
            'is_active' => true,
        ]);

        $liqoBook = TeacherAdminBook::create([
            'teacher_id' => $this->admin->id,
            'subject_id' => $liqo->id,
            'study_group_id' => $this->group->id,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $controller = app(NilaiKelasController::class);
        $request = $this->request('POST', [
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
        ]);

        $controller->bookPublish($request, $this->admin->id, $this->group->id, $liqoBook->id);
        $this->assertNotNull($liqoBook->fresh()->published_at);

        $controller->raporPublish($request, $this->admin->id, $this->group->id);
        $this->assertTrue(
            RaportRegistration::where('student_id', $this->student->id)->where('is_published', true)->exists()
        );
    }

    public function test_publish_ditolak_jika_tidak_ada_buku_mapel_aktif(): void
    {
        $this->book->update(['is_active' => false]);

        $controller = app(NilaiKelasController::class);
        $request = $this->request('POST', [
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
        ]);

        $controller->raporPublish($request, $this->admin->id, $this->group->id);

        $this->assertFalse(
            RaportRegistration::where('student_id', $this->student->id)->where('is_published', true)->exists()
        );
        $this->assertTrue(session()->has('error'));
        $this->assertStringContainsString('belum ada mapel', (string) session('error'));
    }

    public function test_publish_mapel_ditolak_jika_tidak_ada_santri_aktif(): void
    {
        StudentClassHistory::where('student_id', $this->student->id)->update(['is_active' => false]);

        $controller = app(NilaiKelasController::class);
        $request = $this->request('POST', [
            'academic_year_id' => $this->ay->id,
            'semester' => 'ganjil',
        ]);

        $controller->bookPublish($request, $this->admin->id, $this->group->id, $this->book->id);

        $this->assertNull($this->book->fresh()->published_at);
        $this->assertTrue(session()->has('error'));
        $this->assertStringContainsString('belum ada santri aktif', (string) session('error'));
    }

    private function request(string $method, array $data = [], ?User $user = null): Request
    {
        $request = Request::create('/nilai-kelas', $method, $data);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $user ?? $this->admin);

        return $request;
    }
}