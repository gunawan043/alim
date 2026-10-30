<?php

namespace Tests\Feature;

use App\Http\Controllers\GtkWizardController;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Support\GtkAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GtkAccountControlTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $ksp;

    private User $waka;

    private User $teacherA;

    private User $teacherB;

    private User $otherKsp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolA = $this->makeSchool('Sekolah A', '93000001');
        $this->schoolB = $this->makeSchool('Sekolah B', '93000002');

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);

        $this->ksp = $this->makeUser('KSP A', 'ksp.a@test.local', $role, $this->schoolA, 'Kepala Satuan Pendidikan', '9001');
        $this->waka = $this->makeUser('Waka A', 'waka.a@test.local', $role, $this->schoolA, 'Wakil Kepala Satuan Pendidikan', '9002');
        $this->teacherA = $this->makeUser('Guru A', 'guru.a@test.local', $role, $this->schoolA, 'Guru Umum', '9003');
        $this->teacherB = $this->makeUser('Guru B', 'guru.b@test.local', $role, $this->schoolB, 'Guru Umum', '9004');
        $this->otherKsp = $this->makeUser('KSP B', 'ksp.b@test.local', $role, $this->schoolB, 'Kepala Satuan Pendidikan', '9005');
    }

    public function test_ksp_dan_waka_hanya_boleh_mengelola_gtk_sekolah_sendiri(): void
    {
        $this->assertTrue(GtkAccess::canResetPassword($this->ksp, $this->teacherA, $this->schoolA->id));
        $this->assertTrue(GtkAccess::canResetPassword($this->waka, $this->teacherA, $this->schoolA->id));
        $this->assertFalse(GtkAccess::canResetPassword($this->ksp, $this->teacherB, $this->schoolA->id));
        $this->assertFalse(GtkAccess::canResetPassword($this->ksp, $this->otherKsp, $this->schoolA->id));
        $this->assertFalse(GtkAccess::canResetPassword($this->waka, $this->ksp, $this->schoolA->id));
    }

    public function test_ksp_dapat_reset_password_gtk_sekolah_sendiri(): void
    {
        $request = Request::create("/{$this->ksp->id}/gtk/{$this->teacherA->id}/reset-password", 'POST');
        $request->setUserResolver(fn () => $this->ksp);
        $request->attributes->set('schoolContextId', $this->schoolA->id);

        $response = app(GtkWizardController::class)->resetPassword($request, $this->ksp->id, $this->teacherA->id);

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertTrue($payload['success']);
        $this->assertSame('@9003', $payload['password']);

        $this->teacherA->refresh();
        $this->assertTrue($this->teacherA->must_change_password);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->ksp->id,
            'action' => 'GTK_PASSWORD_RESET',
            'record_id' => $this->teacherA->id,
        ]);
    }

    public function test_reset_password_lintas_sekolah_ditolak(): void
    {
        $request = Request::create("/{$this->ksp->id}/gtk/{$this->teacherB->id}/reset-password", 'POST');
        $request->setUserResolver(fn () => $this->ksp);
        $request->attributes->set('schoolContextId', $this->schoolA->id);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(GtkWizardController::class)->resetPassword($request, $this->ksp->id, $this->teacherB->id);
    }

    public function test_masuk_sebagai_gtk_dan_kembali_ke_akun_ksp(): void
    {
        $this->actingAs($this->ksp)
            ->post("/impersonate/{$this->teacherA->id}")
            ->assertRedirect();

        $this->assertAuthenticatedAs($this->teacherA);
        $this->assertSame($this->ksp->id, session('impersonate.actor_id'));
        $this->assertSame($this->ksp->name, session('impersonate.actor_name'));

        $this->post('/impersonate/stop')->assertRedirect();
        $this->assertAuthenticatedAs($this->ksp);
    }

    public function test_masuk_sebagai_gtk_lintas_sekolah_ditolak(): void
    {
        $this->actingAs($this->ksp)
            ->post("/impersonate/{$this->teacherB->id}")
            ->assertForbidden();

        $this->assertAuthenticatedAs($this->ksp);
    }

    private function makeSchool(string $name, string $npsn): School
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit '.$name,
            'code' => 'U-'.$npsn,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return School::create([
            'work_unit_id' => $workUnitId,
            'name' => $name,
            'npsn' => $npsn,
        ]);
    }

    private function makeUser(string $name, string $email, Role $role, School $school, string $jabatan, string $nupy): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        DB::table('gtk_employments')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'school_id' => $school->id,
            'nupy' => $nupy,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => $jabatan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user->fresh();
    }
}
