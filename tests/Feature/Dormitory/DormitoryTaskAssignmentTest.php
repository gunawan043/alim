<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\GtkEmployment;
use App\Models\Role;
use App\Models\User;
use App\Support\DormitoryPosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DormitoryTaskAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Dormitory $dormitory;

    private User $leader;

    private User $wakil;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->leader = User::factory()->create(['is_active' => true, 'is_system_admin' => false]);
        $this->wakil = User::factory()->create(['is_active' => true, 'is_system_admin' => false]);
        $this->staff = User::factory()->create(['is_active' => true, 'is_system_admin' => false]);

        $this->dormitory = Dormitory::factory()
            ->withHead($this->leader->id)
            ->create(['is_active' => true, 'gender' => 'putra']);

        $this->dormitory->school?->update(['boarding_type' => 'boarding']);

        foreach ([$this->leader, $this->wakil, $this->staff] as $user) {
            GtkEmployment::create([
                'user_id' => $user->id,
                'school_id' => $this->dormitory->school_id,
                'status_kepegawaian' => 'PTY',
                'jenis_gtk' => 'Tendik',
                'jabatan' => 'Staf Asrama',
            ]);
        }

        // GtkEmployment observer melakukan syncRoles; pastikan role akses
        // dasar tetap ada untuk kebutuhan test HTTP.
        $role = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);
        foreach ([$this->leader, $this->wakil, $this->staff] as $user) {
            $user->assignRole($role);
        }
    }

    public function test_kepala_asrama_dapat_menetapkan_jabatan_dan_tugas_staf(): void
    {
        $this->assertSame($this->leader->id, $this->dormitory->head_id);
        $this->assertTrue(app(\App\Services\Boarding\DormitoryPolicyAccess::class)
            ->canManageQuota($this->leader, $this->dormitory->id));

        $this->actingAs($this->leader)
            ->post(route('user.asrama.task-assignments.store', [
                'userId' => $this->leader->id,
                'asramaUuid' => $this->dormitory->id,
            ]), [
                'user_id' => $this->wakil->id,
                'position' => DormitoryPosition::WAKIL,
                'shift' => 'pagi',
                'duties' => ['kebersihan', 'keamanan'],
                'start_date' => now()->toDateString(),
                'notes' => 'Wakil urusan operasional harian.',
            ])
            ->assertRedirect();

        $assignment = DormitoryStaffAssignment::where('user_id', $this->wakil->id)
            ->where('dormitory_id', $this->dormitory->id)
            ->firstOrFail();

        $this->assertSame(DormitoryPosition::WAKIL, $assignment->position);
        $this->assertSame('pagi', $assignment->shift);
        $this->assertSame(['kebersihan', 'keamanan'], $assignment->duties);
    }

    public function test_kepala_asrama_tidak_dapat_diubah_dari_halaman_pembagian_tugas(): void
    {
        $this->actingAs($this->leader)
            ->get(route('user.asrama.task-assignments.index', [
                'userId' => $this->leader->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertDontSee('value="kepala_asrama"', false);

        $this->actingAs($this->leader)
            ->post(route('user.asrama.task-assignments.store', [
                'userId' => $this->leader->id,
                'asramaUuid' => $this->dormitory->id,
            ]), [
                'user_id' => $this->wakil->id,
                'position' => DormitoryPosition::KEPALA,
                'shift' => 'pagi',
                'duties' => ['kebersihan'],
                'start_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('position');

        $this->assertDatabaseMissing('dormitory_staff_assignments', [
            'user_id' => $this->wakil->id,
            'dormitory_id' => $this->dormitory->id,
            'position' => DormitoryPosition::KEPALA,
        ]);
    }

    public function test_wakil_yang_ditetapkan_dapat_mengakses_pembagian_tugas(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->wakil->id,
            'dormitory_id' => $this->dormitory->id,
            'assigned_by_id' => $this->leader->id,
            'start_date' => now()->subDay()->toDateString(),
            'status' => 'active',
            'position' => DormitoryPosition::WAKIL,
            'shift' => 'siang',
            'duties' => ['perizinan'],
        ]);

        $this->actingAs($this->wakil)
            ->get(route('user.asrama.task-assignments.index', [
                'userId' => $this->wakil->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('Pembagian Tugas Staf Asrama')
            ->assertSee($this->leader->name);
    }

    public function test_sidebar_asrama_tidak_mengarahkan_kepala_ke_gtk_positions_global(): void
    {
        $this->actingAs($this->leader)
            ->get(route('user.asrama.task-assignments.index', [
                'userId' => $this->leader->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('Pembagian Tugas')
            ->assertDontSee(route('user.gtk-positions.index', ['userId' => $this->leader->id]), false);
    }

    public function test_url_gtk_positions_mengarah_ke_pembagian_tugas_untuk_kepala_asrama(): void
    {
        $this->actingAs($this->leader)
            ->get(route('user.gtk-positions.index', ['userId' => $this->leader->id]))
            ->assertRedirect(route('user.asrama.task-assignments.index', [
                'userId' => $this->leader->id,
                'asramaUuid' => $this->dormitory->id,
            ]));
    }

    public function test_staf_tanpa_jabatan_tidak_dapat_membuka_halaman(): void
    {
        $this->actingAs($this->staff)
            ->get(route('user.asrama.task-assignments.index', [
                'userId' => $this->staff->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertForbidden();
    }

    public function test_halaman_cetak_dapat_dibuka_kepala_asrama(): void
    {
        $this->actingAs($this->leader)
            ->get(route('user.asrama.task-assignments.print', [
                'userId' => $this->leader->id,
                'asramaUuid' => $this->dormitory->id,
            ]))
            ->assertOk()
            ->assertSee('Pembagian Tugas Staf Asrama')
            ->assertSee('Kepala Asrama');
    }
}
