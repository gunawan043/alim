<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Menu yang sebelumnya bertanda "Soon" pada sidebar tugas tambahan.
 *
 * Sebagian memakai tabel baru (piket kesiswaan, asuransi, konsumsi, kegiatan
 * malam), sebagian memakai tabel yang sudah ada (keterlambatan → absensi
 * harian, jadwal latihan → ekstrakurikuler).
 */
class SupportMenuTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private School $school;

    private string $workUnitId;

    private string $academicYearId;

    private string $studyGroupId;

    private string $studentId;

    private string $dormitoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workUnitId = (string) Str::uuid();

        DB::table('work_units')->insert([
            'id' => $this->workUnitId,
            'name' => 'Unit Satuan Pendidikan',
            'code' => 'USP'.random_int(100, 999),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $this->workUnitId,
            'npsn' => '7'.random_int(1000000, 9999999),
            'name' => 'Sekolah Uji Menu',
            'school_level' => 'SMP',
            'is_active' => true,
        ]);

        $this->academicYearId = (string) Str::uuid();
        DB::table('academic_years')->insert([
            'id' => $this->academicYearId,
            'name' => '2026/2027',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $gradeLevelId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $gradeLevelId,
            'school_id' => $this->school->id,
            'level' => 7,
            'name' => 'Kelas 7',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->studyGroupId = (string) Str::uuid();
        DB::table('study_groups')->insert([
            'id' => $this->studyGroupId,
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYearId,
            'grade_level_id' => $gradeLevelId,
            'name' => '7A',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->studentId = (string) Str::uuid();
        DB::table('students')->insert([
            'id' => $this->studentId,
            'school_id' => $this->school->id,
            'name' => 'Santri Uji',
            'nis' => '26001',
            'gender' => 'L',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('student_class_histories')->insert([
            'id' => (string) Str::uuid(),
            'student_id' => $this->studentId,
            'study_group_id' => $this->studyGroupId,
            'academic_year_id' => $this->academicYearId,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->dormitoryId = (string) Str::uuid();
        DB::table('dormitories')->insert([
            'id' => $this->dormitoryId,
            'work_unit_id' => $this->workUnitId,
            'school_id' => $this->school->id,
            'code' => 'ASR-01',
            'name' => 'Asrama Uji',
            'gender' => 'putra',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->user = User::create([
            'name' => 'Kepala Uji',
            'email' => 'kepala-uji-'.random_int(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        $this->user->assignRole(Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web']));

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $this->user->id,
            'school_id' => $this->school->id,
            'jabatan' => 'KEPALA SATUAN PENDIDIKAN',
            'status_kepegawaian' => 'PTT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function url(string $path): string
    {
        return '/'.$this->user->id.$path;
    }

    public function test_semua_halaman_menu_pendukung_dapat_dirender(): void
    {
        foreach ([
            '/kesiswaan/keterlambatan',
            '/kesiswaan/piket',
            '/kesiswaan/jadwal-latihan',
            '/uks/asuransi',
            '/uks/notifikasi-wali',
            '/konsumsi-asrama',
            '/kegiatan-malam',
        ] as $path) {
            $this->actingAs($this->user)
                ->get($this->url($path))
                ->assertOk();
        }
    }

    public function test_keterlambatan_tercatat_ke_absensi_harian(): void
    {
        $this->actingAs($this->user)
            ->post($this->url('/kesiswaan/keterlambatan'), [
                'student_id' => $this->studentId,
                'attendance_date' => now()->toDateString(),
                'arrival_time' => '07:45',
                'notes' => 'Terlambat 15 menit',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('admin_presensi_harian', [
            'student_id' => $this->studentId,
            'status' => 'terlambat',
            'source' => 'kesiswaan',
        ]);
    }

    public function test_piket_kesiswaan_dapat_ditambahkan(): void
    {
        $this->actingAs($this->user)
            ->post($this->url('/kesiswaan/piket'), [
                'user_id' => $this->user->id,
                'duty_date' => now()->toDateString(),
                'shift' => 'pagi',
                'location' => 'Gerbang utama',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kesiswaan_duty_schedules', [
            'user_id' => $this->user->id,
            'shift' => 'pagi',
        ]);
    }

    public function test_asuransi_santri_dapat_ditambahkan(): void
    {
        $this->actingAs($this->user)
            ->post($this->url('/uks/asuransi'), [
                'student_id' => $this->studentId,
                'provider' => 'BPJS Kesehatan',
                'card_number' => '0001234567890',
                'status' => 'aktif',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('uks_student_insurances', [
            'student_id' => $this->studentId,
            'provider' => 'BPJS Kesehatan',
        ]);
    }

    public function test_konsumsi_asrama_dapat_dicatat(): void
    {
        $this->actingAs($this->user)
            ->post($this->url('/konsumsi-asrama'), [
                'dormitory_id' => $this->dormitoryId,
                'consumption_date' => now()->toDateString(),
                'meal_type' => 'makan_siang',
                'students_count' => 120,
                'guests_count' => 4,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('dormitory_meal_consumptions', [
            'dormitory_id' => $this->dormitoryId,
            'meal_type' => 'makan_siang',
            'students_count' => 120,
        ]);
    }

    public function test_kegiatan_malam_dapat_ditambahkan(): void
    {
        $this->actingAs($this->user)
            ->post($this->url('/kegiatan-malam'), [
                'dormitory_id' => $this->dormitoryId,
                'activity_date' => now()->toDateString(),
                'name' => 'Muhadharah',
                'start_time' => '19:30',
                'end_time' => '21:00',
                'status' => 'rencana',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('dormitory_night_activities', [
            'dormitory_id' => $this->dormitoryId,
            'name' => 'Muhadharah',
        ]);
    }

    public function test_sidebar_tidak_lagi_memuat_badge_soon(): void
    {
        $response = $this->actingAs($this->user)
            ->get($this->url('/dashboard/satuan-pendidikan'));

        $response->assertOk();
        $response->assertDontSee('>Soon<', false);
    }
}
