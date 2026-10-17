<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\JadwalKbm;
use App\Models\NotificationUniversal;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TeacherClassAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pengingat 5 menit sebelum jam pelajaran untuk guru.
 */
class LessonReminderNotificationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private StudyGroup $group;

    private Subject $subject;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-19 06:55:00')); // Senin

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit Test Pengingat',
            'code' => 'UTP',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '88888888',
            'name' => 'Sekolah Uji Pengingat',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $gradeLevelId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $gradeLevelId,
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $gradeLevelId,
            'name' => '7A',
            'code' => '7A',
            'room' => '701',
            'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MAT',
            'name' => 'Matematika',
            'credit_hours' => 6,
            'is_active' => true,
        ]);

        $this->teacher = User::create([
            'name' => 'Guru Pengingat',
            'email' => 'guru.pengingat@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_pengingat_terkirim_sekali_lima_menit_sebelum_jam_pelajaran(): void
    {
        $jadwal = $this->makeJadwal('07:00:00', '07:45:00');

        $this->artisan('alim:notify-lesson-reminders --minutes=5')->assertSuccessful();

        $this->assertDatabaseHas('notifications_universal', [
            'user_id' => $this->teacher->id,
            'action' => 'lesson_reminder',
            'reference_id' => $jadwal->id,
        ]);

        $notification = NotificationUniversal::where('user_id', $this->teacher->id)
            ->where('action', 'lesson_reminder')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('5 Menit Lagi', $notification->title);
        $this->assertStringContainsString('07:00', $notification->message);

        // Dijalankan ulang pada menit yang sama → tidak menggandakan.
        $this->artisan('alim:notify-lesson-reminders --minutes=5')->assertSuccessful();
        $this->assertSame(1, NotificationUniversal::where('user_id', $this->teacher->id)
            ->where('action', 'lesson_reminder')->count());
    }

    public function test_jadwal_yang_masih_lama_tidak_dinotifikasi(): void
    {
        $this->makeJadwal('07:10:00', '07:55:00');

        $this->artisan('alim:notify-lesson-reminders --minutes=5')->assertSuccessful();

        $this->assertSame(0, NotificationUniversal::where('user_id', $this->teacher->id)
            ->where('action', 'lesson_reminder')->count());
    }

    public function test_guru_yang_sudah_absen_masuk_tidak_dinotifikasi(): void
    {
        $jadwal = $this->makeJadwal('07:00:00', '07:45:00');

        TeacherClassAttendance::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'jadwal_kbm_id' => $jadwal->id,
            'teacher_id' => $this->teacher->id,
            'attendance_date' => now()->toDateString(),
            'scheduled_start_time' => $jadwal->start_time,
            'scheduled_end_time' => $jadwal->end_time,
            'actual_time_in' => now(),
            'actual_time_out' => null,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'duration_minutes' => 0,
            'status_masuk' => 'hadir',
            'status_keluar' => 'belum_keluar',
        ]);

        $this->artisan('alim:notify-lesson-reminders --minutes=5')->assertSuccessful();

        $this->assertSame(0, NotificationUniversal::where('user_id', $this->teacher->id)
            ->where('action', 'lesson_reminder')->count());
    }

    private function makeJadwal(string $start, string $end): JadwalKbm
    {
        return JadwalKbm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'study_group_id' => $this->group->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 1,
            'slot_index' => 1,
            'start_time' => $start,
            'end_time' => $end,
            'room' => $this->group->room,
            'is_active' => true,
        ]);
    }
}
