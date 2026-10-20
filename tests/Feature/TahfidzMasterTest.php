<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\TahfidzLessonPlan;
use App\Models\TahfidzMuqorrorPlan;
use App\Models\TahfidzPackage;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\TahfidzFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzMasterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private AcademicYear $year;

    private GradeLevel $grade;

    private TahfidzPackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        $schoolUnit = WorkUnit::create([
            'name' => 'Sekolah Master Tahfidz', 'code' => 'SCH-MTH', 'type' => 'Unit Akademik', 'is_active' => true,
        ]);
        $this->school = School::create(['work_unit_id' => $schoolUnit->id, 'npsn' => '60606060', 'name' => 'Sekolah Master Tahfidz']);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true,
        ]);
        $this->admin = User::create([
            'name' => 'Super Admin Tahfidz', 'email' => 'superadmin.master.tahfidz@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        $this->seed(TahfidzFoundationSeeder::class);
        $this->package = TahfidzPackage::firstOrFail();
    }

    public function test_muqorror_dapat_disusun_dilihat_dan_dicetak(): void
    {
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/muqorror")
            ->assertOk()
            ->assertSee('Muqorror Tahfizh');

        $response = $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/muqorror", [
                'title' => 'Muqorror Kelas 7',
                'jenjang' => 'smp',
                'semester' => 'ganjil',
                'academic_year_id' => $this->year->id,
                'tahfidz_package_id' => $this->package->id,
                'grade_level_id' => $this->grade->id,
                'school_id' => $this->school->id,
                'months' => [
                    ['month' => 1, 'target_juz_start' => 1, 'target_juz_end' => 1, 'target_pages' => 20, 'target_lines_month' => 300, 'target_lines_day' => 15, 'active_days' => 20, 'from_surah_name' => 'Al-Fatihah', 'from_ayat' => 1, 'to_surah_name' => 'Al-Baqarah', 'to_ayat' => 5, 'exam_type' => null],
                    ['month' => 2, 'target_juz_start' => 1, 'target_juz_end' => 1, 'target_pages' => 20, 'target_lines_month' => 300, 'target_lines_day' => 15, 'active_days' => 20, 'exam_type' => 'sts'],
                ],
            ]);

        $response->assertRedirect();
        $plan = TahfidzMuqorrorPlan::firstOrFail();
        $this->assertSame(2, $plan->months()->count());

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/muqorror/{$plan->id}")
            ->assertOk()
            ->assertSee('Al-Fatihah');

        $print = $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/muqorror/{$plan->id}/print");
        $print->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $print->getContent());
    }

    public function test_rpp_tahfizh_dan_iqro_dapat_disusun_dilihat_dan_dicetak(): void
    {
        foreach (['tahfizh', 'iqro'] as $type) {
            $title = $type === 'iqro' ? 'RPP Iqro Jilid 1' : 'RPP Tahfizh Pekan 1';
            $response = $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
                ->post("/{$this->admin->id}/tahfidz/rpp", [
                    'type' => $type,
                    'title' => $title,
                    'semester' => 'ganjil',
                    'academic_year_id' => $this->year->id,
                    'grade_level_id' => $this->grade->id,
                    'school_id' => $this->school->id,
                    'kbm_month' => 'Januari 2026',
                    'monthly_target' => '1 juz',
                    'items' => [
                        [
                            'week' => 1, 'section' => 'Pembukaan', 'learning_day' => 'Senin',
                            'week_target' => '1 halaman', 'daily_target' => '15 baris',
                            'teacher_activity' => 'Menyimak bacaan santri.',
                            'student_activity' => 'Membaca dan menyetorkan hafalan.',
                            'duration_minutes' => 15,
                        ],
                    ],
                ]);

            $response->assertRedirect();
        }

        $this->assertSame(2, TahfidzLessonPlan::count());
        $plan = TahfidzLessonPlan::where('type', 'iqro')->firstOrFail();
        $this->assertSame(1, $plan->items()->count());

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/rpp/{$plan->id}")
            ->assertOk()
            ->assertSee('Menyimak bacaan santri');

        $print = $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/rpp/{$plan->id}/print");
        $print->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $print->getContent());
    }
}
