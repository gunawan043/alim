<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\QrTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClassQrReadabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_kelas_memakai_payload_pendek_dan_png(): void
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit QR', 'code' => 'UQR',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '77777777',
            'name' => 'Sekolah QR', 'school_level' => 'smp', 'is_active' => true,
        ]);
        $year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
        ]);
        $grade = GradeLevel::create([
            'school_id' => $school->id, 'level' => 7, 'name' => 'Kelas 7', 'code' => '7',
        ]);
        $group = StudyGroup::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id, 'name' => '7A', 'code' => '7A',
            'capacity' => 30, 'is_active' => true,
        ]);
        $user = User::create([
            'name' => 'Admin QR', 'email' => 'admin.qr@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(), 'user_id' => $user->id, 'school_id' => $school->id,
            'status_kepegawaian' => 'PTT', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $token = app(QrTokenService::class)->findOrCreate($group, $year->id);
        $payload = json_encode(app(QrTokenService::class)->buildQrPayload($token));

        $this->assertLessThan(120, strlen($payload), 'Payload QR kelas harus pendek agar tidak terlalu padat.');

        $response = $this->actingAs($user)->get(route('user.qr.image', [
            'userId' => $user->id,
            'study_group_id' => $group->id,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertGreaterThan(500, strlen($response->getContent()));

        $print = $this->actingAs($user)->get(route('user.qr.print', [
            'userId' => $user->id,
            'study_group_id' => $group->id,
        ]));

        $print->assertOk();
        $print->assertHeader('Content-Type', 'application/pdf');
        $pdfContent = (string) $print->getContent();
        $this->assertStringStartsWith('%PDF', $pdfContent);
        $this->assertMatchesRegularExpression('/MediaBox\s*\[?[^\]]*297\.?[0-9]*\s+419\.?[0-9]*\]?/', $pdfContent);
    }
}
