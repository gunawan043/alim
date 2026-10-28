<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssetRoom;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudyGroupRoomSyncTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $ay;

    private GradeLevel $grade;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Ruang', 'code' => 'UR',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '91919191', 'name' => 'Sekolah Ruang',
        ]);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
        ]);

        $this->grade = GradeLevel::create([
            'school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7', 'is_active' => true,
        ]);

        $this->guru = User::create([
            'name' => 'Guru Ruang', 'email' => 'guru.ruang@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
    }

    public function test_rombel_baru_otomatis_membuat_ruang_sarpras(): void
    {
        $group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'homeroom_teacher_id' => $this->guru->id,
            'name' => '7A',
            'code' => '7A',
            'capacity' => 32,
            'is_active' => true,
        ]);

        $room = AssetRoom::where('study_group_id', $group->id)->first();

        $this->assertNotNull($room, 'Ruang Sarpras harus dibuat otomatis.');
        $this->assertSame('kelas', $room->room_type);
        $this->assertSame('7A', $room->room_name);
        $this->assertSame($this->school->id, $room->school_id);
        $this->assertSame($this->school->work_unit_id, $room->work_unit_id);
        $this->assertSame(32, $room->capacity);
        $this->assertSame($this->guru->id, $room->responsible_user_id);
        $this->assertTrue((bool) $room->is_active);
    }

    public function test_perubahan_nama_dan_kapasitas_rombel_ikut_tersinkron(): void
    {
        $group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7A',
            'capacity' => 30,
            'is_active' => true,
        ]);

        $group->update([
            'name' => '7A Unggulan',
            'capacity' => 36,
            'homeroom_teacher_id' => $this->guru->id,
        ]);

        $room = AssetRoom::where('study_group_id', $group->id)->firstOrFail();

        $this->assertSame('7A Unggulan', $room->room_name);
        $this->assertSame(36, $room->capacity);
        $this->assertSame($this->guru->id, $room->responsible_user_id);
    }

    public function test_command_backfill_membuat_ruang_untuk_rombel_lama(): void
    {
        $group = StudyGroup::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->ay->id,
            'grade_level_id' => $this->grade->id,
            'name' => '7B',
            'capacity' => 30,
            'is_active' => true,
        ]);

        AssetRoom::where('study_group_id', $group->id)->delete();

        $this->artisan('alim:sync-study-group-rooms', ['--school' => $this->school->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('asset_rooms', [
            'study_group_id' => $group->id,
            'room_name' => '7B',
            'room_type' => 'kelas',
            'school_id' => $this->school->id,
        ]);
    }
}
