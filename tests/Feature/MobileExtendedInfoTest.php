<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Models\WaliSantri;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileExtendedInfoTest extends TestCase
{
    use RefreshDatabase;

    private User $wali;

    private School $school;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = WorkUnit::factory()->create(['name' => 'Unit Informasi', 'is_active' => true]);
        $this->school = School::factory()->create([
            'name' => 'Sekolah Informasi',
            'work_unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $unique = str_pad((string) random_int(1, 99999999), 10, '0', STR_PAD_LEFT);
        $this->student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'name' => 'Santri Informasi',
            'nik' => '710201'.$unique,
            'nisn' => $unique,
            'gender' => 'L',
            'status' => 'active',
            'birth_place' => 'Mataram',
            'birth_date' => '2012-01-01',
        ]);

        $this->wali = User::factory()->create(['is_wali' => true, 'is_active' => true]);
        WaliSantri::create([
            'user_id' => $this->wali->id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'role' => 'ayah',
            'is_primary' => true,
            'status' => WaliSantri::STATUS_ACTIVE,
            'verified_at' => now(),
            'verified_by' => $this->wali->id,
        ]);

        Sanctum::actingAs($this->wali, ['*'], 'sanctum');
    }

    public function test_extended_child_information_endpoints_are_available(): void
    {
        $headers = ['X-Active-School-Id' => $this->school->id];
        $base = '/api/mobile/v1/santri/'.$this->student->id;

        foreach ([
            'health/history',
            'library/loans',
            'library/visits',
            'library/member-card',
            'class-history',
            'dormitory/history',
            'dormitory/rewards',
            'extracurricular',
            'competitions',
            'teachers',
            'exam-schedules',
        ] as $path) {
            $this->withHeaders($headers)->getJson($base.'/'.$path)->assertOk();
        }
    }

    public function test_extended_school_and_information_endpoints_are_available(): void
    {
        $headers = ['X-Active-School-Id' => $this->school->id];

        foreach ([
            'library/books',
            'library/programs',
            'agendas',
            'regulations',
            'prayer-times',
        ] as $path) {
            $this->withHeaders($headers)->getJson('/api/mobile/v1/'.$path)->assertOk();
        }

        $this->withHeaders($headers)
            ->getJson('/api/mobile/v1/schools/'.$this->school->id.'/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $this->school->id);
    }
}
