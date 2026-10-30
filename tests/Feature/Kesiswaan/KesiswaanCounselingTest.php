<?php

namespace Tests\Feature\Kesiswaan;

use App\Models\AcademicYear;
use App\Models\GtkEmployment;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KesiswaanCounselingTest extends TestCase
{
    use RefreshDatabase;

    public function test_counseling_index_is_available_under_kesiswaan(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('user.kesiswaan.counseling-records.index', ['userId' => $admin->id]))
            ->assertOk();
    }

    public function test_legacy_uks_counseling_url_still_works(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('user.uks.counseling-records.index', ['userId' => $admin->id]))
            ->assertOk();
    }

    public function test_kesiswaan_sidebar_places_counseling_under_kesiswaan(): void
    {
        $user = $this->userWithRole('Satuan Pendidikan', 3);
        GtkEmployment::create([
            'user_id' => $user->id,
            'jabatan' => 'Koordinator Kesiswaan',
            'status_kepegawaian' => 'GTT',
        ]);

        $this->actingAs($user);

        $html = view('layouts.sidebar')->render();

        $this->assertStringContainsString('Konseling Santri', $html);
        $this->assertStringContainsString(
            route('user.kesiswaan.counseling-records.index', ['userId' => $user->id]),
            $html
        );
    }

    public function test_uks_sidebar_no_longer_contains_counseling(): void
    {
        $user = $this->userWithRole('UKS', 22);
        GtkEmployment::create([
            'user_id' => $user->id,
            'jabatan' => 'Kepala UKS',
            'status_kepegawaian' => 'GTT',
        ]);

        $this->actingAs($user);

        $html = view('layouts.sidebar')->render();

        $this->assertStringNotContainsString('Catatan Konseling', $html);
        $this->assertStringNotContainsString('Konseling Santri', $html);
    }

    public function test_counseling_store_redirects_to_kesiswaan_index(): void
    {
        $admin = $this->admin();
        $school = School::factory()->create();
        $academicYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
        $student = Student::create([
            'school_id' => $school->id,
            'nisn' => '0077777777',
            'name' => 'Santri Konseling',
            'gender' => 'P',
        ]);

        $this->actingAs($admin)
            ->post(route('user.kesiswaan.counseling-records.store', ['userId' => $admin->id]), [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'session_date' => now()->toDateString(),
                'session_type' => 'individu',
                'topic' => 'Adaptasi belajar',
                'description' => 'Sesi awal.',
                'is_confidential' => 1,
            ])
            ->assertRedirect(route('user.kesiswaan.counseling-records.index', ['userId' => $admin->id]));

        $this->assertDatabaseHas('student_counseling_records', [
            'student_id' => $student->id,
            'school_id' => $school->id,
            'topic' => 'Adaptasi belajar',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);
    }

    private function userWithRole(string $roleName, int $level): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['level' => $level]);
        $user = User::factory()->create([
            'is_active' => true,
            'is_system_admin' => false,
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
        $user->load('roles');

        return $user;
    }
}
