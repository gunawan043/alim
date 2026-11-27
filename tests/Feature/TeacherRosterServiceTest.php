<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GtkWorkUnit;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\TeacherRosterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sebagian sekolah mencatat GTK hanya lewat penempatan satuan kerja
 * (gtk_work_unit), tanpa baris kepegawaian. Roster SK harus tetap menemukan
 * mereka; kalau tidak, matriks SK tampil kosong padahal penugasannya ada.
 */
class TeacherRosterServiceTest extends TestCase
{
    use RefreshDatabase;

    private WorkUnit $unit;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = WorkUnit::create([
            'name' => 'Unit Roster', 'code' => 'URST', 'type' => 'Unit Akademik', 'is_active' => true,
        ]);

        $this->school = School::create([
            'work_unit_id' => $this->unit->id, 'npsn' => '31313131', 'name' => 'Sekolah Roster',
        ]);
    }

    public function test_roster_memakai_penempatan_satuan_kerja_bila_kepegawaian_kosong(): void
    {
        $guru = $this->makeUser('Guru Penempatan', 'guru.penempatan@test.local');

        GtkWorkUnit::create([
            'user_id' => $guru->id,
            'work_unit_id' => $this->unit->id,
            'jabatan' => 'Guru Umum',
            'is_primary' => true,
        ]);

        $ids = app(TeacherRosterService::class)->idsForSchool($this->school->id);

        $this->assertContains($guru->id, $ids, 'GTK yang ditempatkan di unit ini harus masuk roster.');
    }

    public function test_roster_tidak_memuat_gtk_dari_unit_lain(): void
    {
        $unitLain = WorkUnit::create([
            'name' => 'Unit Lain', 'code' => 'ULN', 'type' => 'Unit Akademik', 'is_active' => true,
        ]);

        $guruLain = $this->makeUser('Guru Unit Lain', 'guru.unitlain@test.local');

        GtkWorkUnit::create([
            'user_id' => $guruLain->id,
            'work_unit_id' => $unitLain->id,
            'jabatan' => 'Guru Umum',
            'is_primary' => true,
        ]);

        $ids = app(TeacherRosterService::class)->idsForSchool($this->school->id);

        $this->assertNotContains($guruLain->id, $ids, 'GTK unit lain tidak boleh ikut roster sekolah ini.');
    }

    public function test_roster_memakai_kepegawaian_bila_tersedia(): void
    {
        $guru = $this->makeUser('Guru Kepegawaian', 'guru.kepegawaian@test.local');

        \DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $guru->id,
            'school_id' => $this->school->id,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Pendidik',
            'jabatan' => 'Guru',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ids = app(TeacherRosterService::class)->idsForSchool($this->school->id);

        $this->assertContains($guru->id, $ids);
    }

    public function test_sekolah_tanpa_gtk_menghasilkan_roster_kosong(): void
    {
        $ids = app(TeacherRosterService::class)->idsForSchool($this->school->id);

        $this->assertSame([], $ids);
    }

    private function makeUser(string $name, string $email): User
    {
        return User::create([
            'id' => (string) Str::uuid(),
            'name' => $name, 'email' => $email,
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
    }
}
