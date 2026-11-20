<?php

declare(strict_types=1);

namespace Tests\Feature\Dormitory;

use App\Models\AcademicYear;
use App\Models\AdditionalTaskType;
use App\Models\Dormitory;
use App\Models\DormitoryStaffAssignment;
use App\Models\GtkEmployment;
use App\Models\GtkWorkUnit;
use App\Models\JenisGtk;
use App\Models\Role;
use App\Models\StructuralPosition;
use App\Models\User;
use App\Services\Boarding\DormitoryPolicyAccess;
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

    private StructuralPosition $kepalaPosition;

    private StructuralPosition $wakilPosition;

    private AdditionalTaskType $waliKamarTask;

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

        $jenisGtk = JenisGtk::create([
            'nama' => 'Tenaga Kependidikan',
            'is_active' => true,
            'urutan' => 1,
        ]);

        $asramaRole = Role::firstOrCreate(['name' => 'Asrama', 'guard_name' => 'web']);

        $this->kepalaPosition = $this->makePosition($asramaRole->id, 'Kepala Asrama', 'KEPALA-ASR', 1);
        $this->wakilPosition = $this->makePosition($asramaRole->id, 'Wakil Kepala Asrama', 'WAKIL-ASR', 2);

        $this->waliKamarTask = AdditionalTaskType::create([
            'jenis_gtk_id' => $jenisGtk->id,
            'nama' => 'Wali Kamar',
            'kode' => 'wali-kamar',
            'urutan' => 1,
            'is_active' => true,
        ]);

        AdditionalTaskType::create([
            'jenis_gtk_id' => $jenisGtk->id,
            'nama' => 'Staf Asrama',
            'kode' => 'staf-asrama',
            'urutan' => 2,
            'is_active' => true,
        ]);

        foreach ([$this->leader, $this->wakil, $this->staff] as $user) {
            GtkEmployment::create([
                'user_id' => $user->id,
                'school_id' => $this->dormitory->school_id,
                'status_kepegawaian' => 'PTY',
                'jenis_gtk' => 'Tendik',
                'jenis_gtk_id' => $jenisGtk->id,
                'jabatan' => 'Staf Asrama',
            ]);

            GtkWorkUnit::create([
                'user_id' => $user->id,
                'work_unit_id' => $this->dormitory->work_unit_id,
                'jabatan' => 'Staf Asrama',
                'is_primary' => true,
            ]);
        }

        // GtkEmployment observer melakukan syncRoles; pastikan role akses
        // dasar tetap ada untuk kebutuhan test HTTP.
        $role = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => 'web']);
        foreach ([$this->leader, $this->wakil, $this->staff] as $user) {
            $user->assignRole($role);
        }
    }

    private function makePosition(string $roleId, string $name, string $code, int $urutan): StructuralPosition
    {
        return StructuralPosition::create([
            'code' => $code,
            'name' => $name,
            'level' => 'asrama',
            'hierarchy_level' => $urutan,
            'is_active' => true,
            'role_id' => $roleId,
            'kategori' => 'Jabatan',
            'urutan' => $urutan,
        ]);
    }

    private function assignmentUrl(string $name = 'index'): string
    {
        return route('user.asrama.task-assignments.'.$name, [
            'userId' => $this->leader->id,
            'asramaUuid' => $this->dormitory->id,
        ]);
    }

    public function test_kepala_asrama_dapat_menetapkan_jabatan_dan_tugas_staf(): void
    {
        $this->assertSame($this->leader->id, $this->dormitory->head_id);
        $this->assertTrue(app(DormitoryPolicyAccess::class)
            ->canManageQuota($this->leader, $this->dormitory->id));

        $this->actingAs($this->leader)
            ->post($this->assignmentUrl('store'), [
                'user_id' => $this->wakil->id,
                'position_id' => $this->wakilPosition->id,
                'shift' => 'pagi',
                'duties' => ['kebersihan', 'keamanan'],
                'task_type_ids' => [$this->waliKamarTask->id],
                'jp' => [$this->waliKamarTask->id => 6],
                'nomor_sk' => 'SK/ASR/2026/001',
                'tmt' => '2026-07-01',
                'tst' => '2027-06-30',
                'start_date' => now()->toDateString(),
                'notes' => 'Wakil urusan operasional harian.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $assignment = DormitoryStaffAssignment::where('user_id', $this->wakil->id)
            ->where('dormitory_id', $this->dormitory->id)
            ->firstOrFail();

        $this->assertSame($this->wakilPosition->id, $assignment->position_id);
        $this->assertSame('Wakil Kepala Asrama', $assignment->structuralPosition?->name);
        $this->assertSame(DormitoryPosition::WAKIL, $assignment->position);
        $this->assertSame('pagi', $assignment->shift);
        $this->assertSame(['kebersihan', 'keamanan'], $assignment->duties);
        $this->assertSame('SK/ASR/2026/001', $assignment->nomor_sk);

        $task = $assignment->taskTypes->firstWhere('id', $this->waliKamarTask->id);
        $this->assertNotNull($task);
        $this->assertSame(6, (int) $task->pivot->hours_per_week);

        // Jabatan ikut tersinkron ke penempatan satuan kerja asrama.
        $this->assertDatabaseHas('gtk_work_unit', [
            'user_id' => $this->wakil->id,
            'work_unit_id' => $this->dormitory->work_unit_id,
            'position_id' => $this->wakilPosition->id,
        ]);
    }

    public function test_kepala_asrama_tidak_dapat_diubah_dari_halaman_pembagian_tugas(): void
    {
        $this->actingAs($this->leader)
            ->get($this->assignmentUrl())
            ->assertOk()
            ->assertDontSee('value="'.$this->kepalaPosition->id.'"', false);

        $this->actingAs($this->leader)
            ->post($this->assignmentUrl('store'), [
                'user_id' => $this->wakil->id,
                'position_id' => $this->kepalaPosition->id,
                'shift' => 'pagi',
                'duties' => ['kebersihan'],
                'start_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('position_id');

        $this->assertDatabaseMissing('dormitory_staff_assignments', [
            'user_id' => $this->wakil->id,
            'dormitory_id' => $this->dormitory->id,
            'position_id' => $this->kepalaPosition->id,
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
            'position_id' => $this->wakilPosition->id,
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

    public function test_gtk_yang_belum_ditugaskan_tampil_untuk_diberi_tugas(): void
    {
        $this->actingAs($this->leader)
            ->get($this->assignmentUrl())
            ->assertOk()
            ->assertSee($this->staff->name)
            ->assertSee('Belum ditugaskan')
            ->assertSee('Tugaskan');
    }

    public function test_jabatan_yang_tampil_mengikuti_master_bukan_salinan_lama(): void
    {
        DormitoryStaffAssignment::create([
            'user_id' => $this->wakil->id,
            'dormitory_id' => $this->dormitory->id,
            'assigned_by_id' => $this->leader->id,
            'start_date' => now()->subDay()->toDateString(),
            'status' => 'active',
            'position' => DormitoryPosition::WAKIL,
            'position_id' => $this->wakilPosition->id,
        ]);

        // Ubah nama di master: halaman harus mengikuti nama baru.
        $this->wakilPosition->update(['name' => 'Wakil Kepala Asrama (Uji)']);

        $this->actingAs($this->leader)
            ->get($this->assignmentUrl())
            ->assertOk()
            ->assertSee('Wakil Kepala Asrama (Uji)');
    }

    public function test_sidebar_menampilkan_menu_data_gtk_satuan_kerja(): void
    {
        $this->actingAs($this->leader)
            ->get($this->assignmentUrl())
            ->assertOk()
            ->assertSee('Data GTK', false)
            ->assertSee(route('user.gtk.by-work-unit', [
                'userId' => $this->leader->id,
                'satuanKerja' => $this->dormitory->work_unit_id,
            ]), false);
    }

    public function test_sidebar_asrama_tidak_mengarahkan_kepala_ke_gtk_positions_global(): void
    {
        $this->actingAs($this->leader)
            ->get($this->assignmentUrl())
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
            ->get($this->assignmentUrl('print'))
            ->assertOk()
            ->assertSee('Pembagian Tugas Staf Asrama')
            ->assertSee('Kepala Asrama');
    }
}
