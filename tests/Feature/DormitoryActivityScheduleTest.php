<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryActivity;
use App\Models\DormitoryActivitySchedule;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\Dormitory\DormitoryActivityGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Kegiatan rutin asrama: sesi presensi dibentuk harian/mingguan dan idempoten.
 */
class DormitoryActivityScheduleTest extends TestCase
{
    use RefreshDatabase;

    private Dormitory $dormitory;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Uji Rutin', 'code' => 'UURT',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '56565656', 'name' => 'Sekolah Rutin',
        ]);

        $ay = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30',
        ]);

        $gradeLevelId = (string) Str::uuid();
        DB::table('grade_levels')->insert([
            'id' => $gradeLevelId, 'school_id' => $school->id,
            'level' => 7, 'name' => 'Kelas 7', 'created_at' => now(), 'updated_at' => now(),
        ]);

        StudyGroup::create([
            'school_id' => $school->id, 'academic_year_id' => $ay->id,
            'grade_level_id' => $gradeLevelId, 'name' => '7A', 'code' => '7A',
            'room' => '701', 'is_active' => true,
        ]);

        $this->dormitory = Dormitory::create([
            'work_unit_id' => $workUnitId, 'school_id' => $school->id,
            'code' => 'RUTIN-01', 'name' => 'Asrama Rutin', 'gender' => 'putra',
            'capacity' => 20, 'is_active' => true,
        ]);
    }

    public function test_jadwal_harian_membentuk_sesi_dan_idempoten(): void
    {
        $schedule = DormitoryActivitySchedule::create([
            'dormitory_id' => $this->dormitory->id,
            'name' => 'Kajian Malam',
            'recurrence' => 'daily',
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(2)->toDateString(),
            'start_time' => '20:00',
            'end_time' => '21:00',
            'target_type' => 'all',
            'is_active' => true,
        ]);

        $generator = app(DormitoryActivityGenerator::class);

        $created = $generator->generateRange(today(), 5, $this->dormitory->id);
        $this->assertSame(3, $created);
        $this->assertSame(3, DormitoryActivity::where('schedule_id', $schedule->id)->count());

        // Dijalankan ulang tidak menggandakan.
        $this->assertSame(0, $generator->generateRange(today(), 5, $this->dormitory->id));
        $this->assertSame(3, DormitoryActivity::where('schedule_id', $schedule->id)->count());
    }

    public function test_jadwal_mingguan_hanya_pada_hari_tertentu(): void
    {
        $schedule = DormitoryActivitySchedule::create([
            'dormitory_id' => $this->dormitory->id,
            'name' => 'Kajian Ahad',
            'recurrence' => 'weekly',
            'days_of_week' => [7],
            'start_date' => today()->startOfWeek()->toDateString(),
            'end_date' => today()->startOfWeek()->addDays(6)->toDateString(),
            'target_type' => 'all',
            'is_active' => true,
        ]);

        $created = app(DormitoryActivityGenerator::class)
            ->generateRange(today()->startOfWeek(), 7, $this->dormitory->id);

        $this->assertSame(1, $created);
        $this->assertSame(1, DormitoryActivity::where('schedule_id', $schedule->id)
            ->whereDate('activity_date', today()->startOfWeek()->addDays(6)->toDateString())
            ->count());
    }
}
